FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev unzip \
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

COPY rustic-rose-app.zip /tmp/rustic-rose-app.zip

RUN rm -rf /var/www/html/* \
    && unzip -q /tmp/rustic-rose-app.zip -d /var/www/html \
    && rm /tmp/rustic-rose-app.zip \
    && mkdir -p /var/www/html/logs/rate-limit \
    && chown -R www-data:www-data /var/www/html/logs \
    && chmod -R 750 /var/www/html/logs \
    && test -f /var/www/html/includes/bootstrap.php \
    && test -f /var/www/html/admin/login.php \
    && test -f /var/www/html/api/submit-booking.php

EXPOSE 80
