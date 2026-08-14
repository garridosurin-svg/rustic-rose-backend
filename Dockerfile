FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && a2enmod headers expires deflate rewrite \
    && rm -rf /var/lib/apt/lists/*

RUN printf '%s\n' \
    'ServerName localhost' \
    '<Directory /var/www/html>' \
    '    AllowOverride None' \
    '    Require all granted' \
    '    Options -Indexes' \
    '    DirectoryIndex index.php' \
    '</Directory>' \
    '<Directory /var/www/html/includes>' \
    '    Require all denied' \
    '</Directory>' \
    '<Directory /var/www/html/database>' \
    '    Require all denied' \
    '</Directory>' \
    '<Directory /var/www/html/scripts>' \
    '    Require all denied' \
    '</Directory>' \
    '<Directory /var/www/html/logs>' \
    '    Require all denied' \
    '</Directory>' \
    > /etc/apache2/conf-available/rustic-rose.conf \
    && a2enconf rustic-rose

COPY . /var/www/html/

RUN find /var/www/html -type f -name '.htaccess' -delete \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && mkdir -p /var/www/html/logs/rate-limit \
    && chown -R www-data:www-data /var/www/html/logs \
    && chmod 750 /var/www/html/logs /var/www/html/logs/rate-limit \
    && test -f /var/www/html/health.php \
    && test -f /var/www/html/includes/bootstrap.php \
    && test -f /var/www/html/admin/login.php \
    && test -f /var/www/html/api/submit-booking.php \
    && test -z "$(find /var/www/html -type f -name '.htaccess' -print -quit)" \
    && apache2ctl configtest

EXPOSE 80
