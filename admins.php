<?php
require_once __DIR__.'/../includes/bootstrap.php';
admin_required();
$err = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = isset($_POST['action']) ? $_POST['action'] : 'create';

    if ($action === 'delete') {
        $deleteId = (int)(isset($_POST['admin_id']) ? $_POST['admin_id'] : 0);
        if ($deleteId < 1) {
            $err = 'Invalid administrator account.';
        } elseif ($deleteId === (int)$_SESSION['admin_id']) {
            $err = 'You cannot delete the account you are currently using.';
        } else {
            try {
                $target = $pdo->prepare('SELECT id,name,username FROM admins WHERE id=?');
                $target->execute(array($deleteId));
                $targetAdmin = $target->fetch();
                if (!$targetAdmin) {
                    $err = 'Administrator account not found.';
                } else {
                    $adminCount = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
                    if ($adminCount <= 1) {
                        $err = 'The final administrator account cannot be deleted.';
                    } else {
                        $pdo->prepare('DELETE FROM admins WHERE id=?')->execute(array($deleteId));
                        log_activity($pdo, 'admin_delete', 'Deleted admin '.$targetAdmin['username'].' (#'.$deleteId.')');
                        header('Location: admins.php?deleted=1');
                        exit;
                    }
                }
            } catch (Throwable $e) {
                error_log(date('c').' Admin delete: '.$e->getMessage().PHP_EOL, 3, __DIR__.'/../logs/error.log');
                $err = 'This administrator could not be deleted. It may still be linked to activity records.';
            }
        }
    } else {
        $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
        $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        if ($name === '' || $username === '') {
            $err = 'Name and username are required.';
        } elseif (strlen($password) < 8) {
            $err = 'Password must contain at least 8 characters.';
        } else {
            try {
                $pdo->prepare('INSERT INTO admins(name,username,password,is_active) VALUES(?,?,?,1)')->execute(array($name,$username,password_hash($password,PASSWORD_DEFAULT)));
                log_activity($pdo, 'admin_create', 'Created admin '.$username);
                header('Location: admins.php?created=1');
                exit;
            } catch (Throwable $e) {
                $err = 'Username already exists or the account could not be created.';
            }
        }
    }
}

$page_title = 'Admin Accounts';
require 'header.php';
$rows = $pdo->query('SELECT id,name,username,is_active,created_at FROM admins ORDER BY created_at DESC')->fetchAll();
?>
<section class="page-intro account-page-head">
  <div>
    <span class="eyebrow">Access Control</span>
    <h1>Admin Accounts</h1>
    <p>Manage your booking system administrators and control secure dashboard access.</p>
  </div>
  <div class="account-page-actions">
  </div>
</section>

<?php if ($err): ?><div class="alert admin-message"><?php echo e($err); ?></div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="success admin-message">Administrator account deleted successfully.</div><?php endif; ?>

<div class="accounts-stats">
  <div class="account-stat"><span class="account-stat-icon">👥</span><div><strong><?php echo count($rows); ?></strong><span>Total Administrators</span></div></div>
  <div class="account-stat"><span class="account-stat-icon">✓</span><div><strong><?php echo count(array_filter($rows, function($r){ return !empty($r['is_active']); })); ?></strong><span>Active Accounts</span></div></div>
  <div class="account-stat"><span class="account-stat-icon">★</span><div><strong>1</strong><span>Protected Super Admin</span></div></div>
  <div class="account-stat"><span class="account-stat-icon">▦</span><div><strong><?php echo count($rows) ? e(date('M j', strtotime($rows[0]['created_at']))) : '—'; ?></strong><span>Latest Account Added</span></div></div>
</div>

<div class="detail-grid premium-two-column">
  <form class="panel form-panel premium-form" method="post">
    <div class="form-title-wrap"><span class="section-icon">♢</span><div><span class="eyebrow">Secure Access</span><h2>Create Administrator Account</h2></div></div>
    <p class="panel-copy">Add a trusted team member with secure access to the booking dashboard.</p>
    <?php if (isset($_GET['created'])): ?><div class="success">Administrator account created.</div><?php endif; ?>
    <label>Full name<input name="name" required placeholder="Enter full name"></label>
    <label>Username<input name="username" required autocomplete="off" placeholder="Choose a unique username"></label>
    <label>Password<input type="password" name="password" minlength="8" required autocomplete="new-password" placeholder="Minimum 8 characters"></label>
    <input type="hidden" name="action" value="create">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf()); ?>">
    <div class="form-actions-row"><button type="submit">Create Administrator</button></div>
    <div class="security-tip"><strong>Security Tip:</strong> Use strong passwords and give administrator access only to trusted team members.</div>
  </form>

  <section class="panel admin-directory">
    <div class="panel-head">
      <div class="directory-title-wrap"><span class="section-icon">♙</span><div><span class="eyebrow">Team Access</span><h2>Administrator Directory</h2><p class="panel-copy">Manage and monitor administrator accounts.</p></div></div>
      <span class="count-pill"><?php echo count($rows); ?> Accounts</span>
    </div>
    <div class="admin-directory-list">
    <?php foreach ($rows as $r): $isCurrent=((int)$r['id']===(int)$_SESSION['admin_id']); ?>
      <div class="admin-account-card">
        <span class="directory-avatar"><?php echo e(strtoupper(substr($r['name'],0,1))); ?></span>
        <div class="admin-identity">
          <strong><?php echo e($r['name']); ?></strong>
          <small>@<?php echo e($r['username']); ?> · Added <?php echo e(date('M j, Y',strtotime($r['created_at']))); ?></small>
          <span class="role-badge <?php echo $isCurrent ? '' : 'standard'; ?>"><?php echo $isCurrent ? 'Super Admin' : 'Administrator'; ?></span>
        </div>
        <span class="account-state <?php echo $r['is_active'] ? 'active-state' : 'inactive-state'; ?>"><?php echo $r['is_active'] ? 'Active' : 'Disabled'; ?></span>
        <?php if (!$isCurrent): ?>
        <form method="post" class="admin-delete-form" onsubmit="return confirm('Delete administrator <?php echo e(addslashes($r['username'])); ?>? They will immediately lose dashboard access.');">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="admin_id" value="<?php echo (int)$r['id']; ?>">
          <input type="hidden" name="csrf_token" value="<?php echo e(csrf()); ?>">
          <button type="submit" class="icon-danger-button" aria-label="Delete <?php echo e($r['name']); ?>">Delete</button>
        </form>
        <?php else: ?><span class="protected-account">Protected</span><?php endif; ?>
      </div>
    <?php endforeach; ?>
    </div>
    <div class="directory-footer">Showing all <?php echo count($rows); ?> administrator account<?php echo count($rows)===1?'':'s'; ?>.</div>
  </section>
</div>

<div class="security-strip">
  <div class="security-item"><i>★</i><div><strong>Secure Access Control</strong><span>Your dashboard is protected with password hashing and session security.</span></div></div>
  <div class="security-item"><i>◷</i><div><strong>Current Session</strong><span>Signed in as <?php echo e($_SESSION['admin_name']??'Administrator'); ?>.</span></div></div>
  <div class="security-item"><i>⌁</i><div><strong>Account Protection</strong><span>Your currently signed-in administrator account cannot be deleted.</span></div></div>
</div>
<?php require 'footer.php'; ?>
