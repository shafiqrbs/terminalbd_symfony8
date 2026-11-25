#!/bin/sh
set -e

mkdir -p var/cache var/log var/sessions public/uploads public/cache

# Create the session table (framework.yaml: session.handler.shared) if it is
# missing. The SQL is CREATE TABLE IF NOT EXISTS, so an existing table is left
# alone. A failure only warns: Apache must still start if the DB is down.
# timeout keeps an unreachable DB host from blocking startup.
create_session_table() {
    attempt=1
    while [ "$attempt" -le 3 ]; do
        if timeout 10 php bin/console dbal:run-sql "$(cat docker/sql/sessions.sql)" --no-interaction; then
            echo "entrypoint: sessions table is ready"
            return 0
        fi
        echo "entrypoint: sessions table attempt $attempt of 3 failed" >&2
        attempt=$((attempt + 1))
        if [ "$attempt" -le 3 ]; then sleep 3; fi
    done
    echo "entrypoint: WARNING: could not create the sessions table; logins will fail if it does not exist. Check DATABASE_URL and the CREATE privilege." >&2
    return 1
}
create_session_table || true

# Ensure writable dirs are owned by the Apache user. This matters for Coolify
# persistent volumes (public/uploads, public/cache, var/log, var/sessions),
# which are often created root-owned on first mount. Runs after the console
# command above, which may leave root-owned files in var/.
chown -R www-data:www-data var public/uploads public/cache 2>/dev/null || true

# NOTE: apart from the sessions table, schema is imported manually into MySQL.
# The container intentionally does NOT run migrations or schema:update.

exec docker-php-entrypoint "$@"
