FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && a2enmod rewrite headers expires deflate \
    && printf '%s\n' \
       '<Directory /var/www/html>' \
       '    AllowOverride All' \
       '    Require all granted' \
       '    Options -Indexes' \
       '</Directory>' \
       '' \
       'ServerName localhost' \
       > /etc/apache2/conf-available/rustic-rose.conf \
    && a2enconf rustic-rose \
    && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/

RUN mkdir -p /var/www/html/logs/rate-limit \
    && chown -R www-data:www-data /var/www/html/logs \
    && chmod -R 750 /var/www/html/logs

EXPOSE 80
