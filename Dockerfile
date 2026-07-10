# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage 1: Build stage — PHP (with extensions) + Node 22 + Composer
#
# The Wayfinder Vite plugin shells out to `php artisan wayfinder:generate`
# during `vite build`, so PHP must be present alongside Node here.
# ---------------------------------------------------------------------------
FROM php:8.3-fpm-alpine AS build

# Bring in a known-good Node 22 (Vite 8 requires >= 22.12) and Composer
COPY --from=node:22-alpine /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-alpine /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
    && ln -s /usr/local/lib/node_modules/corepack/dist/corepack.js /usr/local/bin/corepack \
    && corepack enable

# PHP extensions required to boot Laravel (for Wayfinder generation) + build
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        libpng-dev \
        oniguruma-dev \
    && docker-php-ext-configure gd \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        bcmath \
        intl \
        zip \
        gd \
        pcntl \
        opcache

WORKDIR /app

# Install PHP dependencies first (better layer caching)
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --no-scripts \
        --no-autoloader \
        --ignore-platform-reqs

# Install JS dependencies
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile

# Copy the rest of the source and produce the optimized autoloader
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && composer run-script post-autoload-dump

# Build frontend assets (Wayfinder can now call `php artisan`)
RUN pnpm run build

# ---------------------------------------------------------------------------
# Stage 2: Runtime image — Nginx + PHP-FPM + Supervisor
# ---------------------------------------------------------------------------
FROM php:8.3-fpm-alpine AS app

# Runtime dependencies + PHP extensions
RUN apk add --no-cache \
        nginx \
        supervisor \
        bash \
        icu-libs \
        libzip \
        libpng \
        oniguruma \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        libpng-dev \
        oniguruma-dev \
    && docker-php-ext-configure gd \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        bcmath \
        intl \
        zip \
        gd \
        pcntl \
        opcache \
    && apk del .build-deps

WORKDIR /var/www/html

# Application source
COPY . .

# Compiled dependencies and built assets from the build stage
COPY --from=build /app/vendor ./vendor
COPY --from=build /app/public/build ./public/build

# Container configuration
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Permissions for Laravel writable directories
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
