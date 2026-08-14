<?php
// Lightweight public-session bootstrap used by public pages that need CSRF-protected forms.
// It intentionally does not connect to the database.
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params(array(
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax'
        ));
    } else {
        session_set_cookie_params(0, '/; samesite=Lax', '', $https, true);
    }
    session_start();
}

if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || strlen($_SESSION['csrf_token']) < 64) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
