#!/usr/bin/env bash
# Full SF2.8 -> SF8.1 port, from the untouched legacy tree to a compiling app.
# Idempotent: rebuilds next/src, next/templates and next/config/routes/legacy
# from scratch every run. The hand-written compatibility layer
# (src/Security, src/Comment, src/Doctrine) and next/config are never touched.
set -euo pipefail
cd "$(dirname "$0")/../.."
PHP=${PHP:-php8.5}

# --check does not build anything here. It builds into a scratch tree and asks
# whether this tree, and terminalbd_api's copy, still agree with what the legacy
# source implies (§7 of migration/API-REACT-PLAN.md).
if [ "${1:-}" = "--check" ]; then
    exec ./migration/bin/check-pipeline.sh "${@:2}"
fi

# Set by check-pipeline.sh inside the scratch tree. The comparison is of source,
# so publishing 125MB of assets and booting the kernel are skipped: neither
# produces anything the comparison reads, and both are slow.
CHECK=${PIPELINE_CHECK:-0}

echo "############ 1. entity graph ############"
./migration/bin/port.sh

echo
echo "############ 2. HTTP layer + templates ############"
rm -rf next/config/routes/legacy
# Only clear directories this pipeline generates. Hand-written template
# directories are prefixed with "_" and must survive a rebuild.
find next/templates -mindepth 1 -maxdepth 1 -type d -not -name '_*' -exec rm -rf {} +
$PHP migration/bin/port-http.php
# ReportBundle declared its routes in docblocks, not a routing.yml. Symfony 7
# removed the annotation loader, so those have to become attributes or the
# `type: attribute` import port-routing.php writes silently loads nothing.
# Runs before Rector so the import it rewrites is a live attribute reference
# rather than a docblock one, which withImportNames would be free to drop.
$PHP migration/bin/port-route-annotations.php next/src

echo
echo "############ 3. flag non-mechanical createForm() sites ############"
# Must precede Rector: it writes the skip list Rector reads.
$PHP migration/bin/mark-manual-forms.php next/src

echo
echo "############ 4. SF2 -> SF8 API modernisation ############"
# Rector exits non-zero if it cannot parse any single file. Report that but do
# not abort the pipeline - the remaining steps are independent, and the final
# verify block is what decides whether the build is good.
rector_rc=0
(cd next && $PHP vendor/bin/rector process --config rector-http.php --no-progress-bar --clear-cache 2>&1 | tail -8) || rector_rc=$?
[ "$rector_rc" -eq 0 ] || echo "!! rector exited $rector_rc - see errors above"

echo
echo "############ 5. controllers: AbstractController + constructor injection ############"
$PHP migration/bin/port-controllers.php next/src

echo
echo "############ 6. return types required by SF8 / Doctrine 3 base classes ############"
$PHP migration/bin/fix-signatures.php next/src

echo
echo "############ 7. JMS @Secure -> #[IsGranted] ############"
$PHP migration/bin/secure-to-isgranted.php next/src

echo
echo "############ 8. Doctrine short-name aliases -> FQCN ############"
$PHP migration/bin/fix-dql-aliases.php next/src 2>&1 | head -4 || true

echo
echo "############ 9. routing ############"
rm -rf next/config/routes/legacy
$PHP migration/bin/port-routing.php
$PHP migration/bin/port-auth-routes.php 2>&1 | head -1 || true

echo
echo "############ 10. FOSUserBundle residue ############"
# Before the form and controller passes: those treat a class extending
# AbstractType differently from one extending a bundle's base type.
$PHP migration/bin/port-fosuser.php

echo
echo "############ 11. Symfony 2 form API ############"
$PHP migration/bin/port-forms.php
$PHP migration/bin/port-form-options.php
$PHP migration/bin/port-form-instances.php
$PHP migration/bin/port-form-types.php
# Symfony 7 removed the array-of-options constructor for validator constraints.
$PHP migration/bin/port-constraint-options.php

echo
echo "############ 12. Symfony 2 controller idioms ############"
$PHP migration/bin/port-controller-api.php
$PHP migration/bin/port-container-aware.php
# Doctrine 3 types remove()/persist(), so where the legacy relied on catching
# ORMInvalidArgumentException the same condition now throws a TypeError that
# catch (Exception) does not see (§5.47). Runs before the dead-import prune so
# the \TypeError it adds is fully qualified and needs no import.
$PHP migration/bin/port-doctrine-typed-remove.php next/src
# ORM 3 hydrates a DQL COUNT() through its own integer type, so an aggregate the
# mobile clients received as "0" now arrives as 0 (§5.48).
$PHP migration/bin/port-api-aggregate-types.php
$PHP migration/bin/prune-dead-imports.php

echo
echo "############ 13. abandoned dependencies ############"
$PHP migration/bin/port-phpexcel.php
if [ "$CHECK" = 1 ]; then
    echo "(skipped in --check: publishes assets, which the source comparison does not read)"
else
    $PHP migration/bin/port-assetic.php
fi
$PHP migration/bin/port-twig-legacy.php
# KnpMenuBundle 3 dropped the Bundle:Class:method alias the templates use.
# Rewrites them and generates the matching builder tags; both halves have to
# be produced together, since port-http.php rebuilds the templates each run.
$PHP migration/bin/port-knp-menus.php

echo
echo "############ 14. container config ############"
$PHP migration/bin/port-parameters.php
$PHP migration/bin/port-services.php
$PHP migration/bin/gen-service-excludes.php
$PHP migration/bin/port-service-locator.php

echo
echo "############ 15. mapping repairs ############"
$PHP migration/bin/fix-attribute-names.php
$PHP migration/bin/fix-associations.php
# DBAL 4 refuses a decimal without precision or a varchar without length, which
# Doctrine 2 defaulted; every schema operation fails without these.
$PHP migration/bin/fix-decimal-precision.php
$PHP migration/bin/fix-string-length.php
# ORM 3 removed EntityRepository::$_em; 2,056 reads of it evaluate to null.
$PHP migration/bin/fix-repository-internals.php
# DBAL 4 made Statement::execute() private and renamed the fetch methods.
$PHP migration/bin/port-dbal-statements.php

echo
echo "############ 16. drop routes with no controller ############"
$PHP migration/bin/check-routes.php --prune --report=migration/contract/unresolvable-routes.md | tail -3

if [ "$CHECK" = 1 ]; then
    echo
    echo "############ scratch build complete ############"
    exit 0
fi

echo
echo "############ verify ############"
fail=0
while read -r f; do $PHP -l "$f" >/dev/null 2>&1 || { echo "SYNTAX: $f"; fail=1; }; done < <(find next/src -name '*.php')
[ "$fail" = 0 ] && echo "php syntax: clean" || echo "php syntax: FAILURES"

for pat in '@Secure' '@Security' '@Route' '@ORM\\' '@Gedmo\\' 'Types::ARRAY'; do
  n=$(grep -rl "$pat" next/src --include=*.php 2>/dev/null | wc -l || true)
  printf "leftover %-14s %s files\n" "$pat" "$n"
done
n=$(grep -rhoE '[A-Za-z]+Bundle:[A-Za-z0-9_]*:' next/src next/templates 2>/dev/null | wc -l || true)
echo "leftover Bundle:X: refs   $n"
n=$(grep -rhoE "['\"][A-Za-z]+Bundle::?[A-Za-z0-9_]+['\"]" next/src --include=*.php 2>/dev/null | wc -l || true)
echo "leftover DQL short names  $n (all name entities that exist in neither tree - see §5.43)"

echo
echo "-- does it actually build? --"
(cd next && $PHP bin/console cache:clear --no-warmup 2>&1 | tail -1)
(cd next && $PHP bin/console lint:container 2>&1 | tail -2 | head -1)
routes=$( (cd next && $PHP bin/console debug:router --format=json 2>/dev/null) | $PHP -r '$d=json_decode(stream_get_contents(STDIN),true); echo $d?count($d):0;')
echo "routes loaded: $routes"
loadable=$( (cd next && $PHP -r '
require "vendor/autoload.php";
$ok=0;$bad=0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("src", FilesystemIterator::SKIP_DOTS)) as $f) {
  if ($f->getExtension()!=="php" || !str_contains($f->getPathname(),"/Controller/")) continue;
  $cls="App\\".str_replace("/","\\",substr($f->getPathname(),4,-4));
  try { class_exists($cls) ? $ok++ : $bad++; } catch (\Throwable $e) { $bad++; }
}
echo "$ok/".($ok+$bad);') )
echo "controllers loadable: $loadable"
(cd next && $PHP vendor/bin/phpunit 2>&1 | tail -1)

echo
echo "-- is the mapping right? --"
# Reporting, not gating: each of these exits non-zero when it finds something,
# and the point here is to print what it found.
(cd next && $PHP bin/validate-mapping.php 2>&1 | tail -1) || true
$PHP migration/bin/verify-columns.php --report=migration/contract/column-verification.md 2>&1 | tail -2 || true
$PHP migration/bin/check-routes.php 2>&1 | tail -3 || true
$PHP migration/bin/check-api-contract.php --report=migration/contract/api-contract.md 2>&1 | tail -4 || true
# Two templates are FOSCommentBundle's own and need its Twig extension; the
# comment API is not ported. See §5 of the plan.
(cd next && $PHP bin/console lint:twig templates/ 2>&1 | tail -1) || true
$PHP migration/bin/check-template-routes.php --report=migration/contract/template-route-links.md 2>&1 | tail -5 || true
# Per-module rollup of the two sweeps, which is the unit §8 flips the proxy at.
$PHP migration/bin/module-summary.php --report=migration/contract/module-verification.md 2>&1 | tail -3 || true
# The navigation tree is the scope of the React port (migration/API-REACT-PLAN.md).
$PHP migration/bin/nav-inventory.php --report=migration/contract/nav-scope.md 2>&1 | tail -3 || true

# Only when a dump is present: it is gitignored, being tenant data.
if [ -f bm_db.sql.gz ]; then
  echo
  echo "-- against a real database dump --"
  $PHP migration/bin/schema-diff.php bm_db.sql.gz --report=migration/contract/schema-diff.md 2>&1 | tail -5 || true
fi
