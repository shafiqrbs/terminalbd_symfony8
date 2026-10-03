#!/bin/sh
# Symfony build steps, run during `docker build` after the autoloader is
# optimized and the full source is present. A throwaway DATABASE_URL is provided
# by the Dockerfile so cache warmup works; the real one is injected at runtime.
#
# terminalbd serves a committed public/ asset tree and uses asset-mapper /
# importmap (no Node stage): assets:install copies bundle assets, importmap:install
# downloads the vendor JS into assets/vendor.
set -eux

export APP_ENV=prod
export APP_DEBUG=0

# -d memory_limit=-1: warming this app's large Twig template set exceeds the
# 512M runtime limit from prod.ini. The override is build-time only (these are
# one-off CLI invocations); the runtime FPM/Apache limit is unaffected.
php -d memory_limit=-1 bin/console assets:install public --no-interaction
php -d memory_limit=-1 bin/console importmap:install --no-interaction
php -d memory_limit=-1 bin/console cache:clear --no-interaction
php -d memory_limit=-1 bin/console cache:warmup --no-interaction
