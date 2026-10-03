#!/bin/sh
set -e

# Ensure writable dirs exist and are owned by the Apache user. This matters for
# Coolify persistent volumes (public/uploads, public/cache, var/log, var/sessions),
# which are often created root-owned on first mount.
mkdir -p var/cache var/log var/sessions public/uploads public/cache
chown -R www-data:www-data var public/uploads public/cache 2>/dev/null || true

# NOTE: schema is imported manually into MySQL. The container intentionally does
# NOT run migrations or schema:update.

exec docker-php-entrypoint "$@"
