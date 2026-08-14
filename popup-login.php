<?php
require_once __DIR__.'/../includes/bootstrap.php';
if (!empty($_SESSION['admin_id'])) { header('Location: dashboard.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php'); exit; }
check_csrf();
if (!empty($_POST['website'])) { header('Location: ../index.php'); exit; }
$username=trim(isset($_POST['username'])?$_POST['username']:'');
$password=isset($_POST['password'])?$_POST['password']:'';
list($allowed,$retryAfter)=login_rate_status($username);
if(!$allowed){ $_SESSION['login_error']='Too many login attempts. Try again later.'; header('Location: login.php'); exit; }
if(!valid_length($username,1,80)||!valid_length($password,8,200)){ login_rate_fail($username); $_SESSION['login_error']='Invalid username or password.'; header('Location: login.php'); exit; }
try {
    $st=$pdo->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1'); $st->execute(array($username)); $admin=$st->fetch();
    if($admin && !empty($admin['is_active']) && password_verify($password,$admin['password'])){
        login_rate_clear($username); session_regenerate_id(true); $_SESSION['csrf_token']=bin2hex(random_bytes(32));
        $_SESSION['admin_id']=$admin['id']; $_SESSION['admin_name']=$admin['name']; $_SESSION['last_activity']=time();
        log_activity($pdo,'login','Administrator signed in from frontend popup'); header('Location: dashboard.php'); exit;
    }
    login_rate_fail($username); $_SESSION['login_error']='Invalid username or password.'; header('Location: login.php'); exit;
} catch(Throwable $e){ error_log(date('c').' Popup login error: '.$e->getMessage().PHP_EOL,3,__DIR__.'/../logs/error.log'); $_SESSION['login_error']='Unable to sign in right now. Please try again.'; header('Location: login.php'); exit; }
