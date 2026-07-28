# syntax=docker/dockerfile:1
#
# Production image for the Matrimony SaaS platform.
# Multi-stage: build frontend assets with Node, then run on PHP-FPM + Nginx
# via a lightweight process manager. Suitable for container platforms; for
# shared hosting see DEPLOYMENT.md.

# --- Stage 1: build frontend ---
FROM node:22-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.ts tsconfig.json components.json ./
COPY public ./public
# Composer packages that ship JS (e.g. ziggy) are resolved at runtime; build
# the app entrypoints here.
RUN npm run build

# --- Stage 2: PHP dependencies ---
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader --no-interaction

# --- Stage 3: runtime ---
FROM php:8.4-fpm-alpine AS app

RUN apk add --no-cache nginx supervisor icu-dev libzip-dev oniguruma-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo_mysql mbstring bcmath intl zip opcache \
    && apk del $PHPIZE_DEPS

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN chown -R www-data:www-data storage bootstrap/cache \
    && cp .env.example .env || true

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 80

ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
