<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = rtrim((string)(getenv('FRONTEND_ORIGIN') ?: ''), '/');
if ($origin && $allowed && $origin === $allowed) {
    header('Access-Control-Allow-Origin: '.$origin);
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
function api_fail($message,$status=400){ http_response_code($status); echo json_encode(['ok'=>false,'error'=>$message]); exit; }
function api_ok($data=[]){ echo json_encode(array_merge(['ok'=>true],$data)); exit; }
