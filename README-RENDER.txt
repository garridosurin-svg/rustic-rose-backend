RUSTIC ROSE — FULL RENDER BACKEND

This is the fully expanded Render backend. No nested app.zip is required.

Upload the EXTRACTED contents of this ZIP to the ROOT of a clean/private GitHub repository.
Preserve the folders exactly.

The repository root should show:
Dockerfile
render.yaml
health.php
index.php
admin/
api/
assets/
includes/
logs/
scripts/

Render environment variables:
DB_HOST
DB_PORT=5432
DB_NAME=postgres
DB_USER
DB_PASSWORD
DB_SSLMODE=require
APP_TIMEZONE=America/Chicago

Then deploy as a Docker Web Service.

This package contains NO .htaccess files. Apache is configured directly in Dockerfile.
