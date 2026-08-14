RUSTIC ROSE PRODUCTIONS - SECURITY-HARDENED BUILD

INSTALLATION
1. Create a MySQL database and a restricted database user. Do not use root in production.
2. Update includes/config.php with the database credentials. Prefer environment variables on production hosting.
3. Import database/schema.sql. No default administrator is created.
4. From a terminal in the project folder, run: php scripts/create-admin.php
5. Serve the site over HTTPS.
6. Confirm Apache AllowOverride is enabled so .htaccess protections are applied. For Nginx, recreate the same deny rules in the server configuration.
7. Ensure logs/ is writable by PHP but not publicly accessible.

SECURITY CHANGES
- Removed public setup/reset/password-hash utilities and the known default administrator.
- Added CSRF protection to both public inquiry and popup administrator login forms.
- Added login rate limiting, session timeout, secure cookie settings, stricter validation, generic production errors, CSV formula protection, and security headers.
- Renamed index.html to index.php so forms can receive secure session tokens.

IMPORTANT
The cookie Secure flag activates automatically when HTTPS is detected. Always deploy with HTTPS.
The provided .htaccess is for Apache. Configure equivalent security rules manually when using Nginx or another web server.
