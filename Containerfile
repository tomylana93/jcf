# Production image for a JCF application.
#
# Targets built from the same commit:
#   app  PHP-FPM runtime, also used for queue workers, the scheduler, Reverb and one-off Artisan commands
#   web  Nginx serving public/ and forwarding PHP requests to the app container
#   ssr  Node server for Inertia server-side rendering (only when INERTIA_SSR_ENABLED=true)
#
#   podman build --target app -t jcf-app:<sha> .
#
# VITE_* values are compiled into the client bundle, so pass the target environment's values as build arguments.
# Secrets never belong in build arguments; provide them to the running containers instead.

ARG PHP_VERSION=8.5
ARG NODE_VERSION=26
ARG POSTGRES_CLIENT_VERSION=18

FROM docker.io/library/node:${NODE_VERSION}-trixie-slim AS node

FROM docker.io/library/php:${PHP_VERSION}-fpm-trixie AS php-base
ARG POSTGRES_CLIENT_VERSION
COPY --from=docker.io/mlocati/php-extension-installer:2.13.1 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions bcmath exif gd intl pcntl pdo_pgsql zip
# Backups shell out to pg_dump and psql, which must match the PostgreSQL server major version.
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends ca-certificates curl; \
    install -d /usr/share/postgresql-common/pgdg; \
    curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc -o /usr/share/postgresql-common/pgdg/apt.postgresql.org.asc; \
    . /etc/os-release; \
    echo "deb [signed-by=/usr/share/postgresql-common/pgdg/apt.postgresql.org.asc] https://apt.postgresql.org/pub/repos/apt ${VERSION_CODENAME}-pgdg main" > /etc/apt/sources.list.d/pgdg.list; \
    apt-get update; \
    apt-get install -y --no-install-recommends "postgresql-client-${POSTGRES_CLIENT_VERSION}"; \
    rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html

FROM php-base AS vendor
COPY --from=docker.io/library/composer:2.11.0 /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction \
    && php artisan package:discover --ansi

FROM vendor AS assets
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && npm install --global --no-fund --no-audit "pnpm@$(node -p "require('./package.json').packageManager.split('@')[1]")"
RUN pnpm install --frozen-lockfile
ARG VITE_APP_NAME=Jalara
ARG VITE_BROADCAST_CONNECTION=null
ARG VITE_REVERB_APP_KEY=
ARG VITE_REVERB_HOST=
ARG VITE_REVERB_PORT=443
ARG VITE_REVERB_SCHEME=https
ENV VITE_APP_NAME=${VITE_APP_NAME} \
    VITE_BROADCAST_CONNECTION=${VITE_BROADCAST_CONNECTION} \
    VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY} \
    VITE_REVERB_HOST=${VITE_REVERB_HOST} \
    VITE_REVERB_PORT=${VITE_REVERB_PORT} \
    VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME}
RUN pnpm run build:ssr

FROM php-base AS app
ENV PHP_FPM_MAX_CHILDREN=4 \
    PHP_OPCACHE_MEMORY=96
COPY <<"EOF" /usr/local/etc/php/conf.d/zz-app.ini
expose_php = Off
memory_limit = 256M
upload_max_filesize = 100M
post_max_size = 110M
opcache.enable = 1
opcache.enable_cli = 0
opcache.memory_consumption = ${PHP_OPCACHE_MEMORY}
opcache.validate_timestamps = 0
EOF
COPY <<"EOF" /usr/local/etc/php-fpm.d/zz-app.conf
[global]
error_log = /proc/self/fd/2

[www]
pm = ondemand
pm.max_children = ${PHP_FPM_MAX_CHILDREN}
pm.process_idle_timeout = 30s
pm.max_requests = 500
clear_env = no
catch_workers_output = yes
decorate_workers_output = no
EOF
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --from=vendor /var/www/html /var/www/html
COPY --from=assets /var/www/html/public/build /var/www/html/public/build
COPY --from=assets /var/www/html/bootstrap/ssr /var/www/html/bootstrap/ssr
# Mount the persistent volume on storage/ itself: backup restore moves storage/app/private and storage/app/public, so they must not be mount points or symlinks.
RUN ln -sfn ../storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache
USER www-data
EXPOSE 9000
CMD ["php-fpm"]

FROM docker.io/library/nginx:1.30.5-alpine AS web
ENV PHP_FPM_ADDRESS=app:9000 \
    NGINX_CLIENT_MAX_BODY_SIZE=110m
COPY <<"EOF" /etc/nginx/templates/default.conf.template
server {
    listen 8080;
    server_name _;
    root /var/www/html/public;
    index index.php;
    client_max_body_size ${NGINX_CLIENT_MAX_BODY_SIZE};
    server_tokens off;

    location /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt { access_log off; log_not_found off; }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass ${PHP_FPM_ADDRESS};
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 120s;
        # Security, CSP and preload Link headers exceed the default 4k/8k header buffer.
        fastcgi_buffer_size 32k;
        fastcgi_buffers 16 16k;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF
COPY --from=app /var/www/html/public /var/www/html/public
EXPOSE 8080

FROM node AS ssr
WORKDIR /app
COPY --from=assets /usr/local/lib/node_modules/pnpm /usr/local/lib/node_modules/pnpm
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
RUN node /usr/local/lib/node_modules/pnpm/bin/pnpm.mjs install --prod --frozen-lockfile --ignore-scripts \
    && rm -rf /usr/local/lib/node_modules/pnpm /root/.local/share/pnpm /root/.cache
COPY --from=assets /var/www/html/bootstrap/ssr bootstrap/ssr
USER node
EXPOSE 13714
CMD ["node", "bootstrap/ssr/app.js"]
