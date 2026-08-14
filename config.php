<?php
return array(
    'db_host' => getenv('DB_HOST') !== false ? getenv('DB_HOST') : 'localhost',
    'db_port' => getenv('DB_PORT') !== false ? getenv('DB_PORT') : '5432',
    'db_name' => getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'postgres',
    'db_user' => getenv('DB_USER') !== false ? getenv('DB_USER') : 'postgres',
    'db_pass' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '',
    'db_sslmode' => getenv('DB_SSLMODE') !== false ? getenv('DB_SSLMODE') : 'require',
    'timezone' => getenv('APP_TIMEZONE') !== false ? getenv('APP_TIMEZONE') : 'America/Chicago'
);
