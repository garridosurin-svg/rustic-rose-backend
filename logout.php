<?php
require_once __DIR__.'/../includes/bootstrap.php';
if(!empty($_SESSION['admin_id'])) log_activity($pdo,'logout','Administrator signed out');
$_SESSION=array();
if(ini_get('session.use_cookies')){$p=session_get_cookie_params();setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);}
session_destroy(); header('Location: login.php'); exit;
