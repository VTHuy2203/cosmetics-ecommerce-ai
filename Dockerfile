FROM composer:2.8 AS composer_deps
WORKDIR /app
COPY TTTT/composer.json TTTT/composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

FROM php:8.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl libcurl4-openssl-dev libicu-dev libonig-dev \
    && docker-php-ext-install mysqli pdo_mysql mbstring intl curl opcache \
    && a2enmod rewrite headers expires remoteip \
    && rm -rf /var/lib/apt/lists/*

COPY deploy/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY deploy/php/production.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY deploy/docker-entrypoint.sh /usr/local/bin/moshop-entrypoint
COPY TTTT /var/www/html/TTTT
COPY --from=composer_deps /app/vendor /var/www/html/TTTT/vendor

RUN chmod +x /usr/local/bin/moshop-entrypoint \
    && mkdir -p /var/www/html/TTTT/WebBanHangMyPham/uploads \
    && chown -R www-data:www-data /var/www/html/TTTT

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl --fail --silent http://127.0.0.1/TTTT/health.php || exit 1

ENTRYPOINT ["moshop-entrypoint"]
CMD ["apache2-foreground"]
