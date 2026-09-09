#!/usr/bin/env bash
#
# Runs the golden capture against both applications from the same starting
# state, then compares them.
#
# Both applications share one scratch database, and a good half of the mobile
# API writes. Capturing one after the other therefore shows the second one a
# database the first one changed, and the diff reports that rather than the
# port - the first run of this comparison "found" 27 regressions, nearly all of
# them either that or the environment mismatch below. So the database is dumped
# once and restored between the two captures.
#
# The dump excludes TemplateCustomize, which is 127 columns wide and cannot be
# recreated unless innodb_strict_mode is off (§5.29). Nothing in the API writes
# it, so leaving it in place keeps the restore runnable as the application's own
# database user.
#
# Both applications must be serving in prod. The legacy front controller
# defaults to prod; Symfony's dev environment turns PHP warnings into 500s,
# which on PHP 8 makes ordinary legacy code look like a regression.
#
#   legacy  php7.4 -S 127.0.0.1:8801 -t web        <legacy router>
#   next    php8.5 -S 127.0.0.1:8802 -t next/public <next router, APP_ENV=prod>
#
# usage: migration/bin/capture-both.sh [--read-only] [--keep-baseline]
set -euo pipefail

cd "$(dirname "$0")/../.."
root=$(pwd)
work="${CAPTURE_WORK_DIR:-$root/migration/.capture-work}"
mkdir -p "$work"

extra=()
keep=0
for a in "$@"; do
    case "$a" in
        --read-only) extra+=(--read-only) ;;
        --keep-baseline) keep=1 ;;
        *) echo "unknown option: $a" >&2; exit 1 ;;
    esac
done

# Credentials come from next/.env.local, the same file the harnesses use.
eval "$(php8.5 -r '
$env = [];
foreach (file("next/.env.local") as $line) {
    if (preg_match("/^([A-Z_]+)=\"?([^\"\n]*)\"?/", trim($line), $m)) $env[$m[1]] = $m[2];
}
preg_match("#mysql://([^:]+):([^@]*)@([^:/]+):(\d+)/([^?]+)#", $env["DATABASE_URL"] ?? "", $m)
    or exit("echo \"could not parse DATABASE_URL\" >&2; exit 1");
printf("DB_USER=%s\nDB_PASS=%s\nDB_HOST=%s\nDB_PORT=%s\nDB_NAME=%s\n",
    escapeshellarg($m[1]), escapeshellarg($m[2]), escapeshellarg($m[3]), escapeshellarg($m[4]), escapeshellarg($m[5]));
')"

cnf="$work/my.cnf"
umask 077
printf '[client]\nhost=%s\nport=%s\nuser=%s\npassword="%s"\n' "$DB_HOST" "$DB_PORT" "$DB_USER" "$DB_PASS" > "$cnf"

baseline="$work/baseline.sql"

echo "── dumping $DB_NAME"
mysqldump --defaults-extra-file="$cnf" --single-transaction --add-drop-table \
    --ignore-table="$DB_NAME.TemplateCustomize" "$DB_NAME" > "$baseline" 2>/dev/null
echo "   $(du -h "$baseline" | cut -f1), $(grep -c '^CREATE TABLE' "$baseline") tables"

restore() {
    echo "── restoring the baseline"
    mysql --defaults-extra-file="$cnf" "$DB_NAME" < "$baseline"
}

echo
echo "── capturing legacy"
php8.5 migration/bin/capture.php legacy "${extra[@]+"${extra[@]}"}" | tail -1

restore

echo
echo "── capturing next"
php8.5 migration/bin/capture.php next "${extra[@]+"${extra[@]}"}" | tail -1

restore

echo
echo "── comparing"
php8.5 migration/bin/compare.php || true

[ "$keep" -eq 1 ] || rm -f "$baseline" "$cnf"
