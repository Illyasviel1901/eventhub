FROM docker.io/library/php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . .

ENV PORT=8080
EXPOSE 8080

CMD ["sh", "-c", "php -d upload_max_filesize=5M -d post_max_size=30M -S 0.0.0.0:${PORT:-8080} router.php"]
