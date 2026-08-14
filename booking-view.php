<?php
require_once __DIR__.'/../includes/bootstrap.php';
admin_required();
$cols = booking_columns($pdo);
$id = (int)(isset($_GET['id']) ? $_GET['id'] : 0);

if ($id < 1) {
    http_response_code(400);
    exit('Invalid booking ID.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = isset($_POST['action']) ? $_POST['action'] : 'update';

    if ($action === 'delete') {
        try {
            $st = $pdo->prepare('SELECT * FROM bookings WHERE id=?');
            $st->execute(array($id));
            $existing = normalize_booking_row($st->fetch(), $cols);
            if (!$existing) {
                http_response_code(404);
                exit('Booking not found.');
            }

            $pdo->prepare('DELETE FROM bookings WHERE id=?')->execute(array($id));
            log_activity($pdo, 'booking_delete', 'Deleted booking '.$existing['reference_no'].' (#'.$id.')');
            header('Location: bookings.php?deleted=1');
            exit;
        } catch (Throwable $e) {
            error_log(date('c').' Booking delete: '.$e->getMessage().PHP_EOL, 3, __DIR__.'/../logs/error.log');
            header('Location: booking-view.php?id='.$id.'&delete_error=1');
            exit;
        }
    }

    $status = isset($_POST['status']) ? $_POST['status'] : 'Pending';
    if (!in_array($status, array('Pending','Confirmed','Completed','Cancelled'), true)) $status = 'Pending';
    $sets = array('status=?',qi($cols['consult_date']).'=?',qi($cols['consult_time']).'=?');
    $values = array(
        $status,
        isset($_POST['consult_date']) && $_POST['consult_date'] !== '' ? $_POST['consult_date'] : null,
        trim(isset($_POST['consult_time']) ? $_POST['consult_time'] : '')
    );
    if ($cols['internal_notes']) {
        $sets[] = qi($cols['internal_notes']).'=?';
        $values[] = trim(isset($_POST['internal_notes']) ? $_POST['internal_notes'] : '');
    }
    if ($cols['estimated_revenue']) {
        $sets[] = qi($cols['estimated_revenue']).'=?';
        $values[] = is_numeric(isset($_POST['estimated_revenue']) ? $_POST['estimated_revenue'] : 0) ? $_POST['estimated_revenue'] : 0;
    }
    $values[] = $id;
    $sql = 'UPDATE bookings SET '.implode(', ', $sets).' WHERE id=?';
    $pdo->prepare($sql)->execute($values);
    log_activity($pdo, 'booking_update', 'Updated booking #'.$id);
    header('Location: booking-view.php?id='.$id.'&saved=1');
    exit;
}

$st = $pdo->prepare('SELECT * FROM bookings WHERE id=?');
$st->execute(array($id));
$b = normalize_booking_row($st->fetch(), $cols);
if (!$b) {
    http_response_code(404);
    exit('Booking not found');
}
$page_title = 'Booking '.$b['reference_no'];
require 'header.php';
?>

<section class="page-intro booking-view-intro">
  <div>
    <span class="eyebrow">Booking profile</span>
    <div class="booking-reference-wrap"><h1><?php echo e($b['reference_no']); ?></h1><span class="status booking-status-large <?php echo strtolower($b['status']); ?>"><?php echo e($b['status']); ?></span></div>
    <p>Review the complete client request, schedule the consultation, and keep private notes together.</p>
  </div>
  <a class="button button-light" href="bookings.php">Back to Bookings</a>
</section>

<?php if (isset($_GET['delete_error'])): ?><div class="alert">The booking could not be deleted. Please check your database permissions.</div><?php endif; ?>

<div class="detail-grid">
  <section class="panel">
    <div class="customer-hero">
      <div class="customer-avatar"><?php echo e(strtoupper(substr($b['first_name'],0,1).substr($b['last_name'],0,1))); ?></div>
      <div><span class="eyebrow">Client</span><h3><?php echo e($b['first_name'].' '.$b['last_name']); ?></h3><p>Booking created <?php echo e(isset($b['created_at']) && $b['created_at'] ? date('M j, Y · g:i A',strtotime($b['created_at'])) : 'recently'); ?></p></div>
    </div>

    <div class="panel-head"><div><span class="eyebrow">Request information</span><h2>Booking details</h2></div></div>
    <div class="info-grid">
      <div class="info-card"><span>Email address</span><a href="mailto:<?php echo e($b['email']); ?>"><?php echo e($b['email']?:'Not provided'); ?></a></div>
      <div class="info-card"><span>Phone number</span><a href="tel:<?php echo e($b['phone']); ?>"><?php echo e($b['phone']?:'Not provided'); ?></a></div>
      <div class="info-card"><span>Requested service</span><strong><?php echo e($b['service']?:'Not selected'); ?></strong></div>
      <div class="info-card"><span>Preferred event date</span><strong><?php echo e($b['event_date']?:'Not provided'); ?></strong></div>
      <div class="info-card"><span>Guest count</span><strong><?php echo e($b['guest_count']?:'Not provided'); ?></strong></div>
      <div class="info-card"><span>Current status</span><strong><?php echo e($b['status']); ?></strong></div>
      <div class="info-card full"><span>Client message</span><div class="message-box"><?php echo $b['message'] !== '' ? nl2br(e($b['message'])) : '<span class="muted-text">No additional message was provided.</span>'; ?></div></div>
    </div>

    <div class="danger-zone">
      <div><span class="eyebrow">Danger zone</span><h3>Delete this booking</h3><p>This permanently removes the record and cannot be undone.</p></div>
      <form method="post" onsubmit="return confirm('Delete this booking permanently? This action cannot be undone.');">
        <input type="hidden" name="action" value="delete"><input type="hidden" name="csrf_token" value="<?php echo e(csrf()); ?>">
        <button type="submit" class="danger-button">Delete Booking</button>
      </form>
    </div>
  </section>

  <form class="panel form-panel" method="post">
    <span class="eyebrow">Booking controls</span><h2>Manage appointment</h2>
    <?php if (isset($_GET['saved'])): ?><div class="success">Booking updated successfully.</div><?php endif; ?>
    <label>Status<select name="status"><?php foreach (array('Pending','Confirmed','Completed','Cancelled') as $s): ?><option <?php echo $b['status'] === $s ? 'selected' : ''; ?>><?php echo e($s); ?></option><?php endforeach; ?></select></label>
    <label>Consultation date<input type="date" name="consult_date" value="<?php echo e($b['consult_date']); ?>"></label>
    <label>Consultation time<input type="time" name="consult_time" value="<?php echo e($b['consult_time']); ?>"></label>
    <label>Estimated revenue<input type="number" min="0" step="0.01" name="estimated_revenue" value="<?php echo e($b['estimated_revenue']); ?>"></label>
    <label>Internal notes<textarea name="internal_notes" rows="8" placeholder="Add private notes for your team..."><?php echo e($b['internal_notes']); ?></textarea></label>
    <input type="hidden" name="action" value="update"><input type="hidden" name="csrf_token" value="<?php echo e(csrf()); ?>">
    <button type="submit">Save Booking Changes</button>
  </form>
</div>
<?php require 'footer.php'; ?>
