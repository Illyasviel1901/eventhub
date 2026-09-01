FROM docker.io/library/composer:2 AS composer

FROM docker.io/library/php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        dom \
        mbstring \
        pdo_mysql \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --classmap-authoritative

COPY . .

ENV APP_ENV=production
ENV PORT=8080

EXPOSE 8080

CMD ["sh", "-c", "php -d upload_max_filesize=5M -d post_max_size=30M -S 0.0.0.0:${PORT:-8080} router.php"]
