<?php
require_once __DIR__.'/../includes/bootstrap.php';
admin_required();
$current_page = basename($_SERVER['PHP_SELF']);
try { $new_booking_count = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='Pending'")->fetchColumn(); } catch (Throwable $e) { $new_booking_count = 0; }
function nav_active($files){
    global $current_page;
    return in_array($current_page, (array)$files, true) ? ' class="active"' : '';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo e($page_title??'Admin'); ?> | Rustic Rose</title>
<link rel="icon" type="image/png" sizes="512x512" href="../assets/images/favicon.png?v=20260810-circle">
<link rel="shortcut icon" type="image/x-icon" href="../assets/images/favicon.ico?v=20260810-circle">
<link rel="apple-touch-icon" href="../assets/images/favicon.png?v=20260810-circle">
<link rel="stylesheet" href="assets/admin.css?v=20260812-mobile-v13">
</head>
<body>
<div class="admin-shell">
<aside class="sidebar" id="adminSidebar">
  <div class="sidebar-main">
    <div class="admin-brand">
      <span class="brand-mark"><img src="../assets/rustic-rose-logo.png" alt="Rustic Rose Productions"></span>
      <div>Rustic Rose<small>Administration</small></div>
    </div>
    <div class="sidebar-section">
      <div class="sidebar-section-label">Workspace</div>
      <nav class="sidebar-nav workspace-nav" aria-label="Workspace navigation">
        <a href="dashboard.php"<?php echo nav_active('dashboard.php'); ?>><span>Overview</span></a>
        <a href="bookings.php"<?php echo nav_active(array('bookings.php','booking-view.php')); ?>><span>Leads & bookings</span><?php if($new_booking_count>0): ?><b class="nav-count"><?php echo $new_booking_count>99?'99+':$new_booking_count; ?></b><?php endif; ?></a>
        <a href="calendar.php"<?php echo nav_active('calendar.php'); ?>><span>Calendar</span></a>
      </nav>
    </div>
    <div class="sidebar-section">
      <div class="sidebar-section-label">Administration</div>
      <nav class="sidebar-nav admin-nav" aria-label="Administration navigation">
        <a href="admins.php"<?php echo nav_active('admins.php'); ?>><span>Admin accounts</span></a>
      </nav>
    </div>
  </div>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <span class="user-avatar"><?php echo e(strtoupper(substr($_SESSION['admin_name']??'A',0,1))); ?></span>
      <div><strong><?php echo e($_SESSION['admin_name']??'Administrator'); ?></strong><small>Administrator</small></div>
    </div>
    <a class="logout-link" href="logout.php">Log Out</a>
  </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<main class="admin-main">
<header class="admin-top">
  <button class="side-toggle" type="button" aria-label="Open navigation" aria-controls="adminSidebar">Menu</button>
  <div class="top-heading"><strong><?php echo e($page_title??'Admin'); ?></strong><small>Manage your bookings and business operations</small></div>
  <div class="top-actions"><button class="theme-toggle" id="themeToggle" type="button" title="Toggle dark mode">Dark mode</button><a class="new-bookings-link" href="bookings.php?status=Pending">New bookings <b><?php echo (int)$new_booking_count; ?></b></a><a class="view-site" href="../index.php" target="_blank" rel="noopener">View Website</a></div>
</header>
<div class="admin-content">
