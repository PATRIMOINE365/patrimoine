# syntax=docker/dockerfile:1

# ---------- Stage 1: frontend assets (Vite + Tailwind 4) ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci --ignore-scripts
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---------- Stage 2: PHP dependencies ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts \
    --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-scripts

# ---------- Stage 3: runtime (php-fpm + nginx + scheduler under supervisord) ----------
FROM php:8.4-fpm-alpine

# icu-data-full is NOT optional. Alpine's icu-libs ships English only, so
# every server-formatted date and number in a French organisation silently
# comes out in English -- invoices, receipts, statements, exports. Without
# it IntlDateFormatter('fr_FR') answers "28 August 2026".
RUN apk add --no-cache nginx supervisor curl icu-libs icu-data-full libzip libpng libjpeg-turbo freetype \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql bcmath intl zip gd opcache \
    && apk del .build-deps

WORKDIR /app

COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/patrimoine.ini
COPY docker/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/patrimoine-entrypoint

RUN chmod +x /usr/local/bin/patrimoine-entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -fsS http://127.0.0.1/up || exit 1

ENTRYPOINT ["patrimoine-entrypoint"]
