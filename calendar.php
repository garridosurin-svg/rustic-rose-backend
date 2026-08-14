<?php
$page_title='Calendar'; require 'header.php';
$cols=booking_columns($pdo); $month=$_GET['month']??date('Y-m'); if(!preg_match('/^\d{4}-\d{2}$/',$month))$month=date('Y-m');
$first=new DateTime($month.'-01'); $last=(clone $first)->modify('last day of this month');
$dateCol=qi($cols['consult_date']); $timeCol=qi($cols['consult_time']);
$sql="SELECT * FROM bookings WHERE {$dateCol} IS NOT NULL AND TO_CHAR({$dateCol},'YYYY-MM')=? ORDER BY {$dateCol}, {$timeCol}";
$st=$pdo->prepare($sql);$st->execute(array($month));$rows=array();$byDay=array();
foreach($st->fetchAll() as $row){$b=normalize_booking_row($row,$cols);$rows[]=$b;$d=(int)date('j',strtotime($b['consult_date']));$byDay[$d][]=$b;}
$offset=(int)$first->format('N')-1;$days=(int)$last->format('j');$today=date('Y-m-d');
?>
<section class="page-intro"><div><span class="eyebrow">Schedule overview</span><h1>Consultation Calendar</h1><p>A complete monthly view of every client consultation and appointment.</p></div></section>
<section class="panel">
<div class="calendar-toolbar"><div class="calendar-summary"><strong><?php echo count($rows); ?></strong><span>consultation<?php echo count($rows)===1?'':'s'; ?> scheduled in <?php echo e($first->format('F Y')); ?></span></div><form class="month-picker"><label>Month</label><input type="month" name="month" value="<?php echo e($month); ?>" onchange="this.form.submit()"></form></div>
<div class="calendar-weekdays"><?php foreach(array('Mon','Tue','Wed','Thu','Fri','Sat','Sun') as $day): ?><div><?php echo $day; ?></div><?php endforeach; ?></div>
<div class="calendar-grid">
<?php for($i=0;$i<$offset;$i++): ?><div class="calendar-day outside"></div><?php endfor; ?>
<?php for($day=1;$day<=$days;$day++): $date=$month.'-'.str_pad($day,2,'0',STR_PAD_LEFT);$events=$byDay[$day]??array(); ?>
<div class="calendar-day <?php echo $date===$today?'today':''; ?>"><div class="calendar-day-number"><b><?php echo $day; ?></b><?php if($events): ?><span><?php echo count($events); ?></span><?php endif; ?></div><div class="calendar-events"><?php foreach(array_slice($events,0,3) as $b): ?><a class="calendar-event" href="booking-view.php?id=<?php echo (int)$b['id']; ?>"><strong><?php echo e($b['first_name'].' '.$b['last_name']); ?></strong><small><?php echo e(($b['consult_time']?$b['consult_time'].' · ':'').$b['service']); ?></small></a><?php endforeach; ?><?php if(count($events)>3): ?><div class="calendar-more">+<?php echo count($events)-3; ?> more</div><?php endif; ?></div></div>
<?php endfor; ?>
</div>
<?php if(!$rows): ?><div class="calendar-empty"><strong>No consultations scheduled this month.</strong><p>Confirmed dates will appear automatically.</p></div><?php endif; ?>
</section>
<?php require 'footer.php'; ?>
