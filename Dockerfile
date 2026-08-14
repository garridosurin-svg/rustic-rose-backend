FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && a2enmod rewrite headers expires deflate \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache.conf /etc/apache2/conf-available/rustic-rose.conf
RUN a2enconf rustic-rose

COPY . /var/www/html/

RUN mkdir -p /var/www/html/logs/rate-limit \
    && chown -R www-data:www-data /var/www/html/logs \
    && chmod -R 750 /var/www/html/logs

EXPOSE 80
