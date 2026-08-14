<?php
require_once __DIR__.'/../includes/bootstrap.php';admin_required();$cols=booking_columns($pdo);log_activity($pdo,'csv_export','Exported bookings');
header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="rustic-rose-bookings-'.date('Y-m-d').'.csv"');
function csv_safe($v){$v=(string)$v;return preg_match('/^[=+\-@]/',$v)?"'".$v:$v;}
$o=fopen('php://output','w');fputcsv($o,array('Reference','Customer','Email','Phone','Service','Event Date','Consultation Date','Time','Status','Revenue','Created'));
foreach($pdo->query('SELECT * FROM bookings ORDER BY created_at DESC') as $row){$b=normalize_booking_row($row,$cols);fputcsv($o,array(csv_safe($b['reference_no']),csv_safe($b['first_name'].' '.$b['last_name']),csv_safe($b['email']),csv_safe($b['phone']),csv_safe($b['service']),csv_safe($b['event_date']),csv_safe($b['consult_date']),csv_safe($b['consult_time']),csv_safe($b['status']),csv_safe($b['estimated_revenue']),csv_safe($b['created_at'])));}
fclose($o);
