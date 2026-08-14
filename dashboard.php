<?php
$page_title='Dashboard';
require 'header.php';

$cols=booking_columns($pdo);
$revenueExpr=$cols['estimated_revenue'] ? "COALESCE(SUM(CASE WHEN status='Completed' THEN ".qi($cols['estimated_revenue'])." ELSE 0 END),0)" : "0";
$stats=$pdo->query("SELECT COUNT(*) total, COUNT(*) FILTER (WHERE status='Pending') pending, COUNT(*) FILTER (WHERE status='Confirmed') confirmed, COUNT(*) FILTER (WHERE status='Completed') completed, COUNT(*) FILTER (WHERE status='Cancelled') cancelled, $revenueExpr revenue FROM bookings")->fetch();
$stats = is_array($stats) ? $stats : array();
foreach(array('total','pending','confirmed','completed','cancelled','revenue') as $k){ if(!isset($stats[$k]) || $stats[$k]===null) $stats[$k]=0; }

$today=(int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE created_at::date=CURRENT_DATE")->fetchColumn();
$thisWeek=(int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE DATE_TRUNC('week',created_at)=DATE_TRUNC('week',CURRENT_TIMESTAMP)")->fetchColumn();
$thisMonth=(int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE DATE_TRUNC('month',created_at)=DATE_TRUNC('month',CURRENT_TIMESTAMP)")->fetchColumn();
$upcoming=(int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE event_date>=CURRENT_DATE AND status IN ('Pending','Confirmed')")->fetchColumn();

$recent=array();
foreach($pdo->query('SELECT * FROM bookings ORDER BY created_at DESC LIMIT 8')->fetchAll() as $row){ $recent[]=normalize_booking_row($row,$cols); }

$upcomingRows=array();
foreach($pdo->query("SELECT * FROM bookings WHERE event_date>=CURRENT_DATE AND status IN ('Pending','Confirmed') ORDER BY event_date ASC LIMIT 5")->fetchAll() as $row){ $upcomingRows[]=normalize_booking_row($row,$cols); }

$monthly=array();
$monthLabels=array();
for($i=5;$i>=0;$i--){
    $key=date('Y-m',strtotime('-'.$i.' months'));
    $monthLabels[$key]=date('M',strtotime($key.'-01'));
    $monthly[$key]=0;
}
try{
    $mrows=$pdo->query("SELECT TO_CHAR(created_at,'YYYY-MM') ym, COUNT(*) total FROM bookings WHERE created_at >= DATE_TRUNC('month',CURRENT_DATE) - INTERVAL '5 months' GROUP BY 1 ORDER BY 1")->fetchAll();
    foreach($mrows as $m){ if(isset($monthly[$m['ym']])) $monthly[$m['ym']]=(int)$m['total']; }
}catch(Throwable $e){}
$maxMonth = max(array_merge(array(1), array_values($monthly)));
$total=(int)$stats['total'];
function dash_pct($value,$total){ return $total>0 ? round(((int)$value/$total)*100) : 0; }
?>

<section class="dashboard-hero premium-command-hero">
  <div>
    <span class="eyebrow">Business command center</span>
    <h1>Welcome back, <?php echo e($_SESSION['admin_name']??'Administrator'); ?>.</h1>
    <p>Bookings, schedules, client pipeline and performance — all in one place.</p>
  </div>
  <div class="hero-actions command-actions">
    <a class="button button-light" href="bookings.php?status=Pending">Review pending</a>
    <a class="button" href="calendar.php">Open calendar</a>
  </div>
</section>

<div class="stats premium-stats-grid">
  <article class="stat-card stat-total"><div class="stat-top"><span>Total bookings</span><i></i></div><strong><?php echo $total; ?></strong><small>All client requests</small></article>
  <article class="stat-card stat-pending"><div class="stat-top"><span>Pending</span><i></i></div><strong><?php echo (int)$stats['pending']; ?></strong><small>Needs your attention</small></article>
  <article class="stat-card stat-confirmed"><div class="stat-top"><span>Confirmed</span><i></i></div><strong><?php echo (int)$stats['confirmed']; ?></strong><small>Scheduled clients</small></article>
  <article class="stat-card stat-completed"><div class="stat-top"><span>Completed</span><i></i></div><strong><?php echo (int)$stats['completed']; ?></strong><small>Successfully delivered</small></article>
  <article class="stat-card stat-today"><div class="stat-top"><span>Today</span><i></i></div><strong><?php echo $today; ?></strong><small>New requests today</small></article>
  <article class="stat-card stat-week"><div class="stat-top"><span>This week</span><i></i></div><strong><?php echo $thisWeek; ?></strong><small>Requests this week</small></article>
  <article class="stat-card stat-month"><div class="stat-top"><span>This month</span><i></i></div><strong><?php echo $thisMonth; ?></strong><small>Requests this month</small></article>
  <article class="stat-card stat-upcoming"><div class="stat-top"><span>Upcoming</span><i></i></div><strong><?php echo $upcoming; ?></strong><small>Pending + confirmed events</small></article>
</div>

<div class="analytics-grid">
  <section class="panel analytics-panel">
    <div class="panel-head"><div><span class="eyebrow">Performance</span><h2>Booking trend</h2></div><span class="panel-meta">Last 6 months</span></div>
    <div class="mini-chart" role="img" aria-label="Bookings over the last six months">
      <?php foreach($monthly as $key=>$count): $height=max(8,round(($count/$maxMonth)*100)); ?>
      <div class="chart-column"><div class="chart-value"><?php echo (int)$count; ?></div><div class="chart-track"><div class="chart-bar" style="height:<?php echo (int)$height; ?>%"></div></div><small><?php echo e($monthLabels[$key]); ?></small></div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="panel pipeline-panel">
    <div class="panel-head"><div><span class="eyebrow">Pipeline</span><h2>Status overview</h2></div><span class="panel-meta"><?php echo $total; ?> total</span></div>
    <?php foreach(array('Pending'=>'pending','Confirmed'=>'confirmed','Completed'=>'completed','Cancelled'=>'cancelled') as $label=>$key): $count=(int)$stats[$key]; $pct=dash_pct($count,$total); ?>
    <div class="pipeline-row">
      <div class="pipeline-label"><span><?php echo e($label); ?></span><b><?php echo $count; ?> <small><?php echo $pct; ?>%</small></b></div>
      <div class="pipeline-track"><span class="pipeline-fill <?php echo strtolower($label); ?>" style="width:<?php echo $pct; ?>%"></span></div>
    </div>
    <?php endforeach; ?>
  </section>
</div>

<div class="dashboard-grid dashboard-grid-v2">
  <section class="panel">
    <div class="panel-head"><div><span class="eyebrow">Latest activity</span><h2>Recent bookings</h2></div><a class="text-link" href="bookings.php">View all</a></div>
    <div class="table-wrap"><table><thead><tr><th>Reference</th><th>Customer</th><th>Service</th><th>Event date</th><th>Status</th></tr></thead><tbody>
    <?php if(!$recent): ?><tr><td colspan="5" class="empty-state">No bookings yet. New inquiries will appear here.</td></tr><?php endif; ?>
    <?php foreach($recent as $b): ?><tr><td data-label="Reference"><a class="reference-link" href="booking-view.php?id=<?php echo (int)$b['id']; ?>"><?php echo e($b['reference_no']); ?></a></td><td data-label="Customer"><strong><?php echo e($b['first_name'].' '.$b['last_name']); ?></strong><small><?php echo e($b['email']??''); ?></small></td><td data-label="Service"><?php echo e($b['service']); ?></td><td data-label="Event date"><?php echo e($b['event_date']?:'Not provided'); ?></td><td data-label="Status"><span class="status <?php echo strtolower($b['status']); ?>"><?php echo e($b['status']); ?></span></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </section>

  <aside class="dashboard-side">
    <section class="panel upcoming-card">
      <div class="panel-head"><div><span class="eyebrow">Schedule</span><h2>Next events</h2></div><a class="text-link" href="calendar.php">Calendar</a></div>
      <div class="upcoming-list">
      <?php if(!$upcomingRows): ?><p class="empty-mini">No upcoming events yet.</p><?php endif; ?>
      <?php foreach($upcomingRows as $b): ?>
        <a class="upcoming-item" href="booking-view.php?id=<?php echo (int)$b['id']; ?>"><span class="upcoming-date"><b><?php echo e(date('d',strtotime($b['event_date']))); ?></b><small><?php echo e(date('M',strtotime($b['event_date']))); ?></small></span><span><strong><?php echo e($b['first_name'].' '.$b['last_name']); ?></strong><small><?php echo e($b['service']); ?></small></span></a>
      <?php endforeach; ?>
      </div>
    </section>
    <section class="panel quick-actions premium-quick-actions"><span class="eyebrow">Quick actions</span><h2>Manage faster</h2><a href="bookings.php?status=Pending"><strong>Review New Bookings</strong><small>Open pending client requests</small></a><a href="bookings.php"><strong>Search All Bookings</strong><small>Find a client, reference or phone</small></a><a href="calendar.php"><strong>Open Calendar</strong><small>See upcoming events</small></a><a href="admins.php"><strong>Admin Accounts</strong><small>Manage dashboard access</small></a></section>
  </aside>
</div>
<?php require 'footer.php'; ?>
