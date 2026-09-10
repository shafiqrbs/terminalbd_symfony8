#!/usr/bin/env bash
#
# The pipeline as a verifier rather than a producer (§7 of API-REACT-PLAN.md).
#
# terminalbd_api has its own copy of the 874 non-presentation files now, so an
# entity change there is an ordinary edit rather than a pipeline step. But the
# pipeline is still the only thing that reads the legacy tree, and four of the
# guards in ci-checks.sh depend on that reading - column names against the legacy
# annotations, route resolution, route annotations converted to attributes, and
# the mobile API contract. Those are the four that catch a whole construct
# evaporating, which is the failure mode that has cost this project the most.
#
# So it keeps running, into a scratch directory, and answers two questions:
#
#   1. does next/ still match what the legacy source implies?
#   2. does terminalbd_api's copy still agree with the same?
#
# Nothing is written outside the scratch tree. The legacy source is symlinked
# because it is only read; next/src, templates and config are copied because the
# pipeline rewrites them.
#
# vendor is NOT symlinked, and that is not a detail. Composer's autoloader maps
# App\ to a path relative to the vendor directory, and PHP resolves __DIR__
# through a symlink to its real target - so a symlinked vendor autoloads the REAL
# next/src. Every repair that drives off Doctrine's validator then inspects the
# tree it is not fixing, reports 0 problems, and does nothing, silently: step 15
# said "inversedBy dropped 0" and the scratch build came out with 186 files of
# reverted repairs. Hard-linked where the filesystem allows it, copied otherwise.
#
# usage: migration/bin/migrate.sh --check [--keep]
set -euo pipefail
cd "$(dirname "$0")/../.."
root=$(pwd)
PHP=${PHP:-php8.5}
keep=0

for arg in "$@"; do
    case "$arg" in
        --keep) keep=1 ;;
        *) echo "unknown option: $arg" >&2; exit 2 ;;
    esac
done

work=${PIPELINE_CHECK_DIR:-$(mktemp -d -t migrate-check-XXXXXX)}
cleanup() { [ "$keep" = 1 ] || rm -rf "$work"; }
trap cleanup EXIT

echo "scratch tree: $work"
mkdir -p "$work/next"
ln -sfn "$root/src" "$work/src"
ln -sfn "$root/app" "$work/app"
cp -r "$root/migration" "$work/migration"

for entry in "$root"/next/*; do
    name=$(basename "$entry")

    case "$name" in
        vendor)
            # cp -al is instant and costs no disk; it needs one filesystem, so
            # fall back to a real copy when the scratch tree is elsewhere.
            cp -al "$entry" "$work/next/vendor" 2>/dev/null || cp -a "$entry" "$work/next/vendor"
            ;;
        public) ln -sfn "$entry" "$work/next/public" ;;
        var) mkdir -p "$work/next/var" ;;
        *) cp -r "$entry" "$work/next/$name" ;;
    esac
done

for dotfile in "$root"/next/.env*; do
    [ -e "$dotfile" ] && cp "$dotfile" "$work/next/" || true
done

# Prove the scratch tree autoloads its own source before believing anything it
# reports. This is the check that would have caught the symlinked vendor.
autoloaded=$(cd "$work/next" && $PHP -r 'require "vendor/autoload.php";
$r = new ReflectionClass("App\\Kernel"); echo dirname($r->getFileName(), 2);')

if [ "$autoloaded" != "$work/next" ]; then
    echo "the scratch tree autoloads $autoloaded, not itself - the comparison would be meaningless" >&2
    exit 2
fi

echo "isolation: the scratch tree autoloads its own src"

echo
PIPELINE_CHECK=1 "$work/migration/bin/migrate.sh" > "$work/pipeline.log" 2>&1 || {
    echo "the pipeline itself failed - last 20 lines:"
    tail -20 "$work/pipeline.log"
    exit 1
}
echo "pipeline: rebuilt $(find "$work/next/src" -name '*.php' | wc -l) files into the scratch tree"

echo
echo "############ 1. does next/ match what the legacy source implies? ############"
if diff -rq "$root/next/src" "$work/next/src" > "$work/next-diff.txt" 2>&1; then
    echo "next/src: identical"
    tree_ok=1
else
    echo "next/src: $(wc -l < "$work/next-diff.txt") difference(s)"
    head -20 "$work/next-diff.txt"
    tree_ok=0
fi

echo
echo "############ 2. does terminalbd_api's copy agree? ############"
copy_ok=1
api=${API_SRC:-$(dirname "$root")/terminalbd_api/src}

if [ -d "$api" ]; then
    $PHP "$root/migration/bin/check-api-copy.php" --source="$work/next/src" --api="$api" || copy_ok=0
else
    # A CI job that checks out only this repository can still answer question 1,
    # which is the one that needs the legacy tree. Say what was skipped rather
    # than reporting a pass nobody earned.
    echo "SKIPPED: no API checkout at $api (set API_SRC to point at one)"
fi

echo
if [ "$tree_ok" = 1 ] && [ "$copy_ok" = 1 ]; then
    echo "PASS: the tree and the copy both agree with the legacy source."
    exit 0
fi

echo "FAIL: see above."
exit 1
