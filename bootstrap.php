<?php
require_once __DIR__ . '/security.php';

$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['timezone']);
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        session_set_cookie_params(0, '/; samesite=Lax', '', $https, true);
    }
    session_start();
}

// Expire authenticated administrator sessions after 30 minutes of inactivity.
if (!empty($_SESSION['admin_id'])) {
    $timeout = 1800;
    if (!empty($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > $timeout) {
        $_SESSION = array();
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        session_start();
        $_SESSION['login_error'] = 'Your session expired. Please sign in again.';
    } else {
        $_SESSION['last_activity'] = time();
    }
}
try {
    $pdo = new PDO(
        'pgsql:host='.$config['db_host'].';port='.$config['db_port'].';dbname='.$config['db_name'].';sslmode='.$config['db_sslmode'],
        $config['db_user'],
        $config['db_pass'],
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        )
    );
} catch (Throwable $e) {
    error_log(date('c').' DB: '.$e->getMessage().PHP_EOL, 3, __DIR__.'/../logs/error.log');
    http_response_code(500);
    exit('The service is temporarily unavailable.');
}

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function qi($identifier){ return '"'.str_replace('"','""',(string)$identifier).'"'; }
function csrf(){ if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; }
function check_csrf(){ $posted=isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : ''; $expected=isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; if($posted==='' || $expected==='' || !hash_equals($expected,$posted)){ http_response_code(419); exit('Invalid security token. Please reload the page and try again.'); } }
function admin_required(){ if(empty($_SESSION['admin_id'])){ header('Location: login.php'); exit; } $_SESSION['last_activity']=time(); }
function money($n){ return '$'.number_format((float)$n,2); }

/** Return the first existing column from a safe, hard-coded candidate list. */
function db_column($pdo, $table, $candidates, $fallback=''){
    static $cache = array();
    if (!isset($cache[$table])) {
        $cache[$table] = array();
        try {
            // Table names used here are hard-coded by this application.
            $st = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ?");
            $st->execute(array($table));
            $rows = $st->fetchAll();
            foreach ($rows as $row) $cache[$table][$row['column_name']] = true;
        } catch (Throwable $e) {
            error_log(date('c').' Schema check: '.$e->getMessage().PHP_EOL, 3, __DIR__.'/../logs/error.log');
        }
    }
    foreach ($candidates as $candidate) {
        if (isset($cache[$table][$candidate])) return $candidate;
    }
    return $fallback;
}

/** Booking schema compatibility for old and new SQL versions. */
function booking_columns($pdo){
    return array(
        'reference' => db_column($pdo, 'bookings', array('reference_no','booking_reference'), 'reference_no'),
        'consult_date' => db_column($pdo, 'bookings', array('consult_date','consultation_date'), 'consult_date'),
        'consult_time' => db_column($pdo, 'bookings', array('consult_time','consultation_time'), 'consult_time'),
        'budget' => db_column($pdo, 'bookings', array('budget_range','budget'), 'budget_range'),
        'message' => db_column($pdo, 'bookings', array('message','notes'), 'message'),
        'internal_notes' => db_column($pdo, 'bookings', array('internal_notes','admin_notes'), ''),
        'estimated_revenue' => db_column($pdo, 'bookings', array('estimated_revenue','revenue'), '')
    );
}

function service_name_column($pdo){
    return db_column($pdo, 'services', array('name','service_name'), 'name');
}

function activity_details_column($pdo){
    return db_column($pdo, 'activity_logs', array('details','description'), 'details');
}

function normalize_booking_row($row, $cols){
    if (!$row) return $row;
    $row['reference_no'] = isset($row[$cols['reference']]) ? $row[$cols['reference']] : '';
    $row['consult_date'] = isset($row[$cols['consult_date']]) ? $row[$cols['consult_date']] : '';
    $row['consult_time'] = isset($row[$cols['consult_time']]) ? $row[$cols['consult_time']] : '';
    $row['budget_range'] = isset($row[$cols['budget']]) ? $row[$cols['budget']] : '';
    $row['message'] = isset($row[$cols['message']]) ? $row[$cols['message']] : '';
    $row['internal_notes'] = ($cols['internal_notes'] && isset($row[$cols['internal_notes']])) ? $row[$cols['internal_notes']] : '';
    $row['estimated_revenue'] = ($cols['estimated_revenue'] && isset($row[$cols['estimated_revenue']])) ? $row[$cols['estimated_revenue']] : 0;
    return $row;
}

function log_activity($pdo,$action,$details=''){
    try {
        $detailsColumn = activity_details_column($pdo);
        $sql = 'INSERT INTO activity_logs(admin_id,action,'.qi($detailsColumn).',ip_address) VALUES(?,?,?,?)';
        $st = $pdo->prepare($sql);
        $st->execute(array(isset($_SESSION['admin_id'])?$_SESSION['admin_id']:null,$action,$details,isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:''));
    } catch (Throwable $e) {
        // Logging must never break login, booking, or dashboard pages.
        error_log(date('c').' Activity log: '.$e->getMessage().PHP_EOL, 3, __DIR__.'/../logs/error.log');
    }
}


function client_ip(){ return isset($_SERVER['REMOTE_ADDR']) ? substr((string)$_SERVER['REMOTE_ADDR'],0,45) : 'unknown'; }
function login_rate_file($username){
    $dir=__DIR__.'/../logs/rate-limit';
    if(!is_dir($dir)) @mkdir($dir,0750,true);
    return $dir.'/'.hash('sha256',strtolower(trim((string)$username)).'|'.client_ip()).'.json';
}
function login_rate_status($username){
    $file=login_rate_file($username); $now=time(); $window=900; $max=5;
    $data=array('attempts'=>array(),'blocked_until'=>0);
    if(is_file($file)){ $raw=@file_get_contents($file); $decoded=json_decode((string)$raw,true); if(is_array($decoded)) $data=array_merge($data,$decoded); }
    $attempts=array(); foreach((array)$data['attempts'] as $t){ if((int)$t > $now-$window) $attempts[]=(int)$t; }
    $data['attempts']=$attempts;
    if((int)$data['blocked_until']>$now) return array(false,(int)$data['blocked_until']-$now,$data,$file);
    if(count($attempts)>=$max){ $data['blocked_until']=$now+900; @file_put_contents($file,json_encode($data),LOCK_EX); return array(false,900,$data,$file); }
    return array(true,0,$data,$file);
}
function login_rate_fail($username){
    list($allowed,$retry,$data,$file)=login_rate_status($username); $data['attempts'][]=time();
    if(count($data['attempts'])>=5) $data['blocked_until']=time()+900;
    @file_put_contents($file,json_encode($data),LOCK_EX);
}
function login_rate_clear($username){ $file=login_rate_file($username); if(is_file($file)) @unlink($file); }
function valid_length($value,$min,$max){ $len=function_exists('mb_strlen')?mb_strlen((string)$value,'UTF-8'):strlen((string)$value); return $len>=$min && $len<=$max; }
function valid_event_date($date){ $d=DateTime::createFromFormat('Y-m-d',(string)$date); return $d && $d->format('Y-m-d')===$date && $date>=date('Y-m-d') && $date<=date('Y-m-d',strtotime('+5 years')); }
