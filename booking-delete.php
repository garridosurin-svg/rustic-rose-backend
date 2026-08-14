<?php
require_once __DIR__.'/../includes/bootstrap.php';
admin_required();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

check_csrf();
$id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
if ($id < 1) {
    header('Location: bookings.php?delete_error=invalid');
    exit;
}

try {
    $cols = booking_columns($pdo);
    $st = $pdo->prepare('SELECT * FROM bookings WHERE id = ? LIMIT 1');
    $st->execute(array($id));
    $row = $st->fetch();

    if (!$row) {
        header('Location: bookings.php?delete_error=missing');
        exit;
    }

    $booking = normalize_booking_row($row, $cols);
    $del = $pdo->prepare('DELETE FROM bookings WHERE id = ?');
    $del->execute(array($id));

    if ($del->rowCount() < 1) {
        throw new RuntimeException('No booking row was deleted.');
    }

    log_activity(
        $pdo,
        'booking_delete',
        'Deleted booking '.$booking['reference_no'].' (#'.$id.') from the booking list'
    );

    header('Location: bookings.php?deleted=1');
    exit;
} catch (Throwable $e) {
    error_log(date('c').' Booking list delete: '.$e->getMessage().PHP_EOL, 3, __DIR__.'/../logs/error.log');
    header('Location: bookings.php?delete_error=failed');
    exit;
}
