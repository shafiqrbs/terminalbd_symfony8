#!/usr/bin/env bash
# Reproducible Phase-1 pipeline: lift the SF2.8 entity graph into next/ on PHP 8.5.
# Idempotent - safe to re-run after editing the legacy tree.
set -euo pipefail
cd "$(dirname "$0")/../.."
PHP=${PHP:-php8.5}

echo "== 1/7 copy entity graph + rewrite namespaces =="
rm -rf next/src/{Accounting,Appearance,Business,Content,DoctorPrescription,DomainUser,Hospital,HumanResource,Inventory,Location,Media,Medicine,Pos,Product,Report,Restaurant,Tool,User,Customer,Dms,Domain,Bindu}
$PHP migration/bin/port-entities.php | tail -3

echo "== 2/7 repair PHP 8 compile errors carried over from the legacy tree =="
# `_constructor()` is a typo for `__construct()`, so this parent call never ran on
# PHP 7. PHP 8 rejects it at compile time; dropping it preserves legacy behaviour.
grep -rl 'parent::__construct' next/src --include=*.php 2>/dev/null | while read -r f; do
  grep -q 'function __construct' "$f" || perl -0pi -e 's/\n\s*parent::__construct\(\);\n/\n/' "$f"
done

echo "== 3/7 @Gedmo docblocks -> attributes (ORM 3 has no AnnotationDriver) =="
$PHP migration/bin/gedmo-to-attributes.php next/src | tail -3

echo "== 4/7 Rector: @ORM -> #[ORM], PHP 8.5 level =="
(cd next && $PHP vendor/bin/rector process src --no-progress-bar --clear-cache 2>&1 | tail -3)

echo "== 5/7 collapse duplicated non-repeatable ORM attributes =="
$PHP migration/bin/fix-duplicate-attributes.php next/src

echo "== 6/7 drop JoinColumn attributes ORM 3 rejects =="
$PHP migration/bin/fix-joincolumns.php next/src

echo "== 7/7 corrections for malformed legacy annotations =="
$PHP migration/bin/fixups.php

echo "== verify =="
fail=0
while read -r f; do $PHP -l "$f" >/dev/null 2>&1 || { echo "SYNTAX: $f"; fail=1; }; done < <(find next/src -name '*.php')
left_orm=$(grep -rl '@ORM\\\|@Doctrine\\ORM' next/src --include=*.php 2>/dev/null | wc -l || true)
left_gedmo=$(grep -rl '@Gedmo\\' next/src --include=*.php 2>/dev/null | wc -l || true)
# DBAL 4 deleted these constants; Rector's Doctrine set likes to introduce them.
dead_types=$(grep -rn 'Types::ARRAY\|Types::OBJECT\|Types::JSON_ARRAY' next/src --include=*.php 2>/dev/null | wc -l || true)
echo "references to DBAL types removed in v4: $dead_types (must be 0)"
echo "files with leftover @ORM annotations:   $left_orm"
echo "files with leftover @Gedmo annotations: $left_gedmo"
[ "$fail" = 0 ] && echo "syntax: clean" || echo "syntax: FAILURES"
