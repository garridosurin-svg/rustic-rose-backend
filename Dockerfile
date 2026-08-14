FROM php:8.3-apache
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev unzip \
 && docker-php-ext-install pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*

RUN printf '%s\n' \
 'ServerName localhost' \
 '<Directory /var/www/html>' \
 'AllowOverride None' \
 'Require all granted' \
 'Options -Indexes' \
 'DirectoryIndex index.php' \
 '</Directory>' \
 > /etc/apache2/conf-available/rustic-rose.conf \
 && a2enconf rustic-rose

COPY app.zip /tmp/app.zip
RUN find /var/www/html -mindepth 1 -delete \
 && unzip -q /tmp/app.zip -d /var/www/html \
 && rm /tmp/app.zip \
 && find /var/www/html -name '.htaccess' -delete \
 && find /var/www/html -type d -exec chmod 755 {} \; \
 && find /var/www/html -type f -exec chmod 644 {} \; \
 && mkdir -p /var/www/html/logs/rate-limit \
 && chown -R www-data:www-data /var/www/html/logs \
 && chmod 750 /var/www/html/logs /var/www/html/logs/rate-limit \
 && apache2ctl configtest

EXPOSE 80
