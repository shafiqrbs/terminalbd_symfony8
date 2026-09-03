#!/usr/bin/env bash
# Full SF2.8 -> SF8.1 port, from the untouched legacy tree to a compiling app.
# Idempotent: rebuilds next/src, next/templates and next/config/routes/legacy
# from scratch every run. The hand-written compatibility layer
# (src/Security, src/Comment, src/Doctrine) and next/config are never touched.
set -euo pipefail
cd "$(dirname "$0")/../.."
PHP=${PHP:-php8.5}

echo "############ 1. entity graph ############"
./migration/bin/port.sh

echo
echo "############ 2. HTTP layer + templates ############"
rm -rf next/config/routes/legacy
# Only clear directories this pipeline generates. Hand-written template
# directories are prefixed with "_" and must survive a rebuild.
find next/templates -mindepth 1 -maxdepth 1 -type d -not -name '_*' -exec rm -rf {} +
$PHP migration/bin/port-http.php

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
echo "############ 5. return types required by SF8 / Doctrine 3 base classes ############"
$PHP migration/bin/fix-signatures.php next/src

echo
echo "############ 6. JMS @Secure -> #[IsGranted] ############"
$PHP migration/bin/secure-to-isgranted.php next/src

echo
echo "############ 7. Doctrine short-name aliases -> FQCN ############"
$PHP migration/bin/fix-dql-aliases.php next/src 2>&1 | head -4 || true

echo
echo "############ 8. routing ############"
rm -rf next/config/routes/legacy
$PHP migration/bin/port-routing.php
$PHP migration/bin/port-auth-routes.php 2>&1 | head -1 || true

echo
echo "############ 9. container config ############"
$PHP migration/bin/port-parameters.php
$PHP migration/bin/gen-service-excludes.php

echo
echo "############ verify ############"
fail=0
while read -r f; do $PHP -l "$f" >/dev/null 2>&1 || { echo "SYNTAX: $f"; fail=1; }; done < <(find next/src -name '*.php')
[ "$fail" = 0 ] && echo "php syntax: clean" || echo "php syntax: FAILURES"

for pat in '@Secure' '@ORM\\' '@Gedmo\\' 'Types::ARRAY'; do
  n=$(grep -rl "$pat" next/src --include=*.php 2>/dev/null | wc -l || true)
  printf "leftover %-14s %s files\n" "$pat" "$n"
done
n=$(grep -rhoE '[A-Za-z]+Bundle:[A-Za-z0-9_]*:' next/src next/templates 2>/dev/null | wc -l || true)
echo "leftover Bundle:X: refs   $n"
n=$(grep -rhoE "['\"][A-Za-z]+Bundle::?[A-Za-z0-9_]+['\"]" next/src --include=*.php 2>/dev/null | wc -l || true)
echo "leftover DQL short names  $n (99 are refs to entities deleted from the legacy tree)"

echo
echo "-- does it actually build? --"
(cd next && $PHP bin/console cache:clear --no-warmup 2>&1 | tail -1)
(cd next && $PHP bin/console lint:container 2>&1 | tail -2 | head -1)
routes=$( (cd next && $PHP bin/console debug:router --format=json 2>/dev/null) | $PHP -r '$d=json_decode(stream_get_contents(STDIN),true); echo $d?count($d):0;')
echo "routes loaded: $routes"
(cd next && $PHP vendor/bin/phpunit 2>&1 | tail -1)
