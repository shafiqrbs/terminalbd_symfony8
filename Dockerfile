# syntax=docker/dockerfile:1
#
# Production image for terminalbd (Symfony 8.1 / PHP 8.5), built from the
# standardized Coolify PHP template (shared with rms_erp).
# Apache + mod_php, MySQL, committed public/ asset tree (no Node stage).
#
# Per-project knobs:
#   - PHP_VERSION build arg (defaults to 8.5 for this project).
#   - docker/build.sh -> Symfony build steps (assets:install, importmap, cache warmup).
#
# Build context comes from git via Coolify — composer.lock MUST be committed.

ARG PHP_VERSION=8.5
FROM php:${PHP_VERSION}-apache

# ---------------------------------------------------------------------------
# System libraries + PHP extensions
# ---------------------------------------------------------------------------
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        git \
        unzip \
        curl \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install pdo_mysql intl gd zip bcmath exif; \
    docker-php-ext-enable opcache; \
    rm -rf /var/lib/apt/lists/*

# ---------------------------------------------------------------------------
# Apache: enable rewrite + headers, custom vhost (docroot -> public/, AllowOverride All)
# ---------------------------------------------------------------------------
RUN a2enmod rewrite headers; \
    printf 'ServerName localhost\n' > /etc/apache2/conf-available/servername.conf; \
    a2enconf servername
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

# ---------------------------------------------------------------------------
# PHP config (production base + our overrides). EGPCS in prod.ini is required so
# Coolify's injected env vars reach $_ENV, which public/index.php bootEnv() reads.
# ---------------------------------------------------------------------------
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/prod.ini "$PHP_INI_DIR/conf.d/zz-app.ini"

# ---------------------------------------------------------------------------
# Composer
# ---------------------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# Dependencies first for layer caching (no scripts/autoloader yet — full source not here).
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress --no-interaction

# App source
COPY . .

# Optimize the autoloader, then run framework-specific build steps.
# A throwaway DATABASE_URL satisfies config parsing (Symfony cache warmup);
# the real one is injected by Coolify at runtime.
RUN set -eux; \
    composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts; \
    if [ -f docker/build.sh ]; then \
        DATABASE_URL="mysql://build:build@127.0.0.1:3306/build?serverVersion=8.0" \
            sh docker/build.sh; \
    fi; \
    mkdir -p var/cache var/log var/sessions public/uploads public/cache; \
    chown -R www-data:www-data var public/uploads public/cache; \
    chmod -R 775 var

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

# Static check — Apache is serving; does not depend on the DB.
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://localhost/robots.txt -o /dev/null || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
