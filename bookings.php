<?php
$page_title = 'Bookings';
require 'header.php';

$cols = booking_columns($pdo);
$q = trim(isset($_GET['q']) ? $_GET['q'] : '');
$status = trim(isset($_GET['status']) ? $_GET['status'] : '');
$service = trim(isset($_GET['service']) ? $_GET['service'] : '');
$date_from = trim(isset($_GET['date_from']) ? $_GET['date_from'] : '');
$date_to = trim(isset($_GET['date_to']) ? $_GET['date_to'] : '');
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
if(!in_array($per_page,array(10,25,50,100),true)) $per_page=25;
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);

$where = array('1');
$params = array();
if ($q !== '') {
    $where[] = '('.qi($cols['reference']).' ILIKE ? OR first_name ILIKE ? OR last_name ILIKE ? OR email ILIKE ? OR phone ILIKE ? OR service ILIKE ?)';
    $like = '%'.$q.'%';
    for($i=0;$i<6;$i++) $params[]=$like;
}
if (in_array($status, array('Pending','Confirmed','Completed','Cancelled'), true)) { $where[]='status = ?'; $params[]=$status; }
if ($service !== '') { $where[]='service = ?'; $params[]=$service; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/',$date_from)) { $where[]='event_date >= ?'; $params[]=$date_from; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/',$date_to)) { $where[]='event_date <= ?'; $params[]=$date_to; }
$whereSql=implode(' AND ',$where);

$countSt=$pdo->prepare('SELECT COUNT(*) FROM bookings WHERE '.$whereSql);
$countSt->execute($params);
$total_rows=(int)$countSt->fetchColumn();
$total_pages=max(1,(int)ceil($total_rows/$per_page));
if($page>$total_pages) $page=$total_pages;
$offset=($page-1)*$per_page;

$sql='SELECT * FROM bookings WHERE '.$whereSql.' ORDER BY created_at DESC LIMIT '.(int)$per_page.' OFFSET '.(int)$offset;
$st=$pdo->prepare($sql); $st->execute($params);
$rows=array(); foreach($st->fetchAll() as $row){ $rows[]=normalize_booking_row($row,$cols); }

$services=array();
try { $services=$pdo->query("SELECT DISTINCT service FROM bookings WHERE service IS NOT NULL AND service<>'' ORDER BY service")->fetchAll(PDO::FETCH_COLUMN); } catch(Throwable $e){}

function premium_date_only($value) {
    $value = trim((string)$value);
    if ($value === '' || $value === '0000-00-00' || strpos($value, '0000-00-00') === 0) return '';
    $datePart = substr($value, 0, 10); $date = DateTime::createFromFormat('Y-m-d', $datePart);
    return $date ? $date->format('M j, Y') : $datePart;
}
function booking_qs($overrides=array()){
    $base=$_GET;
    foreach($overrides as $k=>$v){ if($v===null) unset($base[$k]); else $base[$k]=$v; }
    return http_build_query($base);
}
?>

<?php if (isset($_GET['deleted'])): ?><div class="success admin-message">Lead deleted successfully.</div><?php endif; ?>
<?php if (isset($_GET['delete_error'])): ?><div class="alert admin-message">The lead could not be deleted. Please refresh the page and try again.</div><?php endif; ?>

<section class="page-intro bookings-intro">
  <div><span class="eyebrow">Client Pipeline</span><h1>Booking Management</h1><p>Search, filter and manage every consultation request from one workspace.</p></div>
  <div class="intro-actions"><a class="button button-light" href="bookings.php">Clear Filters</a><a class="button" href="export-csv.php">Export CSV</a></div>
</section>

<div class="booking-summary-strip">
  <a href="bookings.php" class="summary-chip <?php echo $status===''?'active':''; ?>"><span>All</span><b><?php echo $total_rows; ?></b></a>
  <?php foreach(array('Pending','Confirmed','Completed','Cancelled') as $s): ?>
  <a href="bookings.php?status=<?php echo urlencode($s); ?>" class="summary-chip <?php echo $status===$s?'active':''; ?>"><span><?php echo e($s); ?></span></a>
  <?php endforeach; ?>
</div>

<section class="panel premium-panel">
  <form class="filters premium-filters advanced-filters" method="get">
    <div class="field filter-search"><label>Search bookings</label><input id="bookingSearch" name="q" value="<?php echo e($q); ?>" placeholder="Reference, name, email, phone or service"></div>
    <div class="field"><label>Status</label><select name="status"><option value="">All statuses</option><?php foreach (array('Pending','Confirmed','Completed','Cancelled') as $s): ?><option value="<?php echo e($s); ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo e($s); ?></option><?php endforeach; ?></select></div>
    <div class="field"><label>Service</label><select name="service"><option value="">All services</option><?php foreach($services as $svc): ?><option value="<?php echo e($svc); ?>" <?php echo $service===$svc?'selected':''; ?>><?php echo e($svc); ?></option><?php endforeach; ?></select></div>
    <div class="field"><label>Event from</label><input type="date" name="date_from" value="<?php echo e($date_from); ?>"></div>
    <div class="field"><label>Event to</label><input type="date" name="date_to" value="<?php echo e($date_to); ?>"></div>
    <div class="field"><label>Rows</label><select name="per_page"><?php foreach(array(10,25,50,100) as $n): ?><option value="<?php echo $n; ?>" <?php echo $per_page===$n?'selected':''; ?>><?php echo $n; ?></option><?php endforeach; ?></select></div>
    <button type="submit">Apply Filters</button>
  </form>

  <div class="results-toolbar"><strong><?php echo $total_rows; ?> result<?php echo $total_rows===1?'':'s'; ?></strong><span>Page <?php echo $page; ?> of <?php echo $total_pages; ?></span></div>

  <div class="table-wrap premium-table">
    <table><thead><tr><th>Reference</th><th>Customer</th><th>Email</th><th>Phone</th><th>Service</th><th>Event Date</th><th>Status</th><th>Actions</th></tr></thead><tbody>
      <?php if (!$rows): ?><tr><td colspan="8" class="empty-state">No bookings match your filters.</td></tr><?php endif; ?>
      <?php foreach ($rows as $b): ?>
      <tr>
        <td data-label="Reference"><div class="reference-cell"><a class="reference-link" href="booking-view.php?id=<?php echo (int)$b['id']; ?>"><?php echo e($b['reference_no']); ?></a><button type="button" class="copy-ref" data-copy="<?php echo e($b['reference_no']); ?>" title="Copy reference">Copy</button></div></td>
        <td data-label="Customer"><strong><?php echo e($b['first_name'].' '.$b['last_name']); ?></strong></td>
        <td data-label="Email"><a class="booking-contact-link" href="mailto:<?php echo e($b['email']); ?>"><?php echo e($b['email']); ?></a></td>
        <td data-label="Phone"><a class="booking-contact-link" href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $b['phone'])); ?>"><?php echo e($b['phone'] ?: 'Not provided'); ?></a></td>
        <td data-label="Service"><?php echo e($b['service']); ?></td>
        <td data-label="Event date"><?php $eventDate = premium_date_only($b['event_date']); ?><span class="date-display"><span class="date-dot"></span><?php echo e($eventDate ?: 'Not provided'); ?></span></td>
        <td data-label="Status"><span class="status <?php echo strtolower($b['status']); ?>"><?php echo e($b['status']); ?></span></td>
        <td data-label="Actions"><div class="booking-row-actions"><a class="row-action" href="booking-view.php?id=<?php echo (int)$b['id']; ?>">View</a><a class="row-action row-email" href="mailto:<?php echo e($b['email']); ?>">Email</a><form class="booking-delete-form" method="post" action="booking-delete.php" onsubmit="return confirm('Delete this lead permanently? This cannot be undone.');"><input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>"><input type="hidden" name="csrf_token" value="<?php echo e(csrf()); ?>"><button type="submit" class="row-delete-action" aria-label="Delete lead <?php echo e($b['reference_no']); ?>">Delete</button></form></div></td>
      </tr>
      <?php endforeach; ?>
    </tbody></table>
  </div>

  <?php if($total_pages>1): ?>
  <nav class="pagination" aria-label="Bookings pages">
    <a class="page-btn <?php echo $page<=1?'disabled':''; ?>" href="?<?php echo e(booking_qs(array('page'=>max(1,$page-1)))); ?>">Previous</a>
    <span>Page <b><?php echo $page; ?></b> of <?php echo $total_pages; ?></span>
    <a class="page-btn <?php echo $page>=$total_pages?'disabled':''; ?>" href="?<?php echo e(booking_qs(array('page'=>min($total_pages,$page+1)))); ?>">Next</a>
  </nav>
  <?php endif; ?>
</section>
<?php require 'footer.php'; ?>
