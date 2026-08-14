FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev unzip \
    && docker-php-ext-install pdo_pgsql \
    && a2enmod headers expires deflate rewrite \
    && rm -rf /var/lib/apt/lists/*

# Configure Apache directly. No .htaccess files are required.
RUN cat > /etc/apache2/conf-available/rustic-rose.conf <<'APACHECONF'
ServerName localhost

<Directory /var/www/html>
    AllowOverride None
    Require all granted
    Options -Indexes
    DirectoryIndex index.php
</Directory>

# Protect backend-only/sensitive directories from direct HTTP access.
<Directory /var/www/html/includes>
    Require all denied
</Directory>

<Directory /var/www/html/database>
    Require all denied
</Directory>

<Directory /var/www/html/scripts>
    Require all denied
</Directory>

<Directory /var/www/html/logs>
    Require all denied
</Directory>

<FilesMatch "(^\.|config\.php$|\.(env|ini|log|sql|bak|dist)$|README\.txt$)">
    Require all denied
</FilesMatch>

Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "SAMEORIGIN"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
APACHECONF

RUN a2enconf rustic-rose

COPY rustic-rose-app-clean.zip /tmp/rustic-rose-app.zip

RUN rm -rf /var/www/html/* \
    && unzip -q /tmp/rustic-rose-app.zip -d /var/www/html \
    && rm /tmp/rustic-rose-app.zip \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && mkdir -p /var/www/html/logs/rate-limit \
    && chown -R www-data:www-data /var/www/html/logs \
    && chmod 750 /var/www/html/logs /var/www/html/logs/rate-limit \
    && test -r /var/www/html/health.php \
    && test -r /var/www/html/includes/bootstrap.php \
    && test -r /var/www/html/admin/login.php \
    && test -r /var/www/html/api/submit-booking.php \
    && ! find /var/www/html -name '.htaccess' -print -quit | grep -q .

EXPOSE 80
