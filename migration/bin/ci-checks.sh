#!/usr/bin/env bash
# The checks that guard the migration, all runnable without a database.
#
# Run by .github/workflows/migration-checks.yml and by hand:
#
#     ./migration/bin/ci-checks.sh
#
# Every check reports, and the script exits non-zero if any of them failed, so
# a single run tells you everything that is wrong rather than stopping at the
# first thing. PHP=php8.4 ./migration/bin/ci-checks.sh to pin an interpreter.
set -uo pipefail
cd "$(dirname "$0")/../.."
PHP=${PHP:-php8.5}

failed=()

run() {
    local name=$1
    shift

    echo
    echo "── ${name}"

    if "$@"; then
        return 0
    fi

    failed+=("$name")
}

# The shared database is the contract: a column the two applications spell
# differently silently splits the data. This is the check that matters most.
run "column names vs the legacy annotations" \
    $PHP migration/bin/verify-columns.php --report=migration/contract/column-verification.md

# Every entity's mapping resolves, including both sides of every association.
run "Doctrine mapping" \
    bash -c "cd next && $PHP bin/validate-mapping.php | tail -3"

# No route points at a controller or action that does not exist.
run "route resolution" \
    $PHP migration/bin/check-routes.php --strict

# The 96 wire-locked mobile endpoints still answer at their contract paths.
run "mobile API contract" \
    $PHP migration/bin/check-api-contract.php --report=migration/contract/api-contract.md

# Services can be built and their arguments type-check.
run "container" \
    bash -c "cd next && $PHP bin/console lint:container"

# Templates parse. _foscomment is excluded: those two are FOSCommentBundle's
# own and need its Twig extension, and its API is not ported (§5 of the plan).
run "templates" \
    bash -c "cd next && $PHP bin/console lint:twig templates/ --excludes=_foscomment | tail -2"

run "tests" \
    bash -c "cd next && $PHP vendor/bin/phpunit | tail -3"

# Only when a dump is present: it is gitignored, being tenant data. Reports
# rather than gates - a dump is one tenant at one point in time, and columns it
# predates are not port defects.
if [ -f bm_db.sql.gz ]; then
    echo
    echo "── schema diff against bm_db.sql.gz (informational)"
    $PHP migration/bin/schema-diff.php bm_db.sql.gz --report=migration/contract/schema-diff.md || true
fi

echo
if [ ${#failed[@]} -eq 0 ]; then
    echo "all checks passed"
    exit 0
fi

echo "FAILED: ${failed[*]}"
exit 1
