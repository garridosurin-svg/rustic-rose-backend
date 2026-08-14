<?php
require_once __DIR__.'/../includes/bootstrap.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : ''; unset($_SESSION['login_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    list($allowed,$retryAfter)=login_rate_status($username);
    if(!$allowed){ $error='Too many login attempts. Try again in '.ceil($retryAfter/60).' minute(s).'; }
    elseif(!valid_length($username,1,80) || !valid_length($password,8,200)){ $error='Invalid username or password.'; login_rate_fail($username); }
    else {
        try {
            $st=$pdo->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
            $st->execute(array($username)); $admin=$st->fetch();
            if($admin && !empty($admin['is_active']) && password_verify($password,$admin['password'])){
                login_rate_clear($username); session_regenerate_id(true); $_SESSION['csrf_token']=bin2hex(random_bytes(32));
                $_SESSION['admin_id']=$admin['id']; $_SESSION['admin_name']=$admin['name']; $_SESSION['last_activity']=time();
                log_activity($pdo,'login','Administrator signed in'); header('Location: dashboard.php'); exit;
            }
            login_rate_fail($username); $error='Invalid username or password.';
            error_log(date('c').' Failed login for '.preg_replace('/[^a-zA-Z0-9_.@-]/','',$username).' from '.client_ip().PHP_EOL,3,__DIR__.'/../logs/error.log');
        } catch(Throwable $e){ $error='Unable to sign in right now. Please try again.'; error_log(date('c').' Login error: '.$e->getMessage().PHP_EOL,3,__DIR__.'/../logs/error.log'); }
    }
}
?><!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Login</title>
    <link rel="icon" type="image/png" sizes="512x512" href="../assets/images/favicon.png?v=20260810-circle">
    <link rel="shortcut icon" type="image/x-icon" href="../assets/images/favicon.ico?v=20260810-circle">
    <link rel="apple-touch-icon" href="../assets/images/favicon.png?v=20260810-circle">
    <link rel="stylesheet" href="assets/admin.css?v=20260807-eye-fixed-v3">
</head>
<body class="login-body">
<form class="login-card" method="post">
    <div class="login-logo"><img src="../assets/rustic-rose-logo.png" alt="Rustic Rose Productions"></div>
    <span class="login-kicker">Rustic Rose Productions</span>
    <h1>Administrator Login</h1>
    <?php if ($error): ?><div class="alert"><?php echo e($error); ?></div><?php endif; ?>
    <label>Username<input name="username"  required autofocus></label>
    <label>Password
        <span class="password-field">
            <input id="admin-password" type="password" name="password" required autocomplete="current-password">
            <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false" title="Show password">
                <svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.2 12s3.5-6 9.8-6 9.8 6 9.8 6-3.5 6-9.8 6-9.8-6-9.8-6Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="icon-eye-slash" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 6.2A10.8 10.8 0 0 1 12 6c6.3 0 9.8 6 9.8 6a17.7 17.7 0 0 1-3.1 3.8"/><path d="M6.6 6.7C3.8 8.5 2.2 12 2.2 12s3.5 6 9.8 6a10.5 10.5 0 0 0 4.1-.8"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
            </button>
        </span>
    </label>
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf()); ?>">
    <button type="submit">Secure Login</button>
</form>
<script>
(function () {
    var password = document.getElementById('admin-password');
    var toggle = document.querySelector('.password-toggle');
    if (!password || !toggle) return;

    toggle.addEventListener('click', function () {
        var show = password.type === 'password';
        password.type = show ? 'text' : 'password';
        toggle.classList.toggle('is-visible', show);
        toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
        toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        toggle.setAttribute('title', show ? 'Hide password' : 'Show password');
        password.focus();
        try { password.setSelectionRange(password.value.length, password.value.length); } catch (e) {}
    });
})();
</script>
</body>
</html>
