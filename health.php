<?php
header('Content-Type: application/json; charset=utf-8');
try {
    require __DIR__.'/includes/bootstrap.php';
    $pdo->query('SELECT 1');
    echo json_encode(['ok'=>true,'service'=>'rustic-rose-backend','database'=>'connected']);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['ok'=>false,'service'=>'rustic-rose-backend','database'=>'unavailable']);
}
