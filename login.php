<?php
require_once 'config.php';
session_start_safe();
if (is_admin_logged_in()) redirect('report.php');

$cfg   = get_settings();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = db()->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin && $admin['password_hash'] === 'NOT_SET_RUN_RESET_PASSWORD') {
        $error = 'Password not set. Visit <a href="reset_password.php" style="color:inherit;font-weight:700">reset_password.php</a> first.';
    } elseif ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION[ADMIN_SESSION_KEY] = true;
        $_SESSION['admin_username']  = $username;
        redirect('report.php');
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login — <?= h($cfg['company_name']??APP_NAME) ?></title>
<link rel="stylesheet" href="style.css">
<?= brand_css() ?>
</head>
<body>
<div class="login-shell">

  <!-- Left panel -->
  <div class="login-left">
    <?php if (!empty($cfg['logo_path'])): ?>
    <img src="<?= UPLOAD_URL.h($cfg['logo_path']) ?>" style="height:36px;width:auto">
    <?php else: ?>
    <div style="width:40px;height:40px;background:var(--pri);border-radius:var(--r);display:flex;align-items:center;justify-content:center;font-size:1.2rem">📋</div>
    <?php endif; ?>
    <div class="ll-title"><?= h($cfg['form_title'] ?? 'Joining Form') ?></div>
    <div class="ll-sub"><?= h($cfg['company_name'] ?? '') ?><br>Admin Dashboard — secure access only.</div>
    <div style="margin-top:40px;border-top:1px solid rgba(255,255,255,.08);padding-top:28px">
      <div style="font-size:.72rem;color:rgba(255,255,255,.25);line-height:1.8">
        📊 View all submissions<br>
        🖨 Print &amp; export PDF<br>
        ⚙ Manage form settings<br>
        🗂 Configure field rules
      </div>
    </div>
  </div>

  <!-- Right panel -->
  <div class="login-right">
    <div class="login-card">
      <h2>Sign in</h2>
      <p class="login-sub">Admin access to reports and settings.</p>

      <?php if ($error): ?>
      <div class="alert alert-danger" style="margin-bottom:18px">⚠ <?= $error ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="field" style="margin-bottom:13px">
          <label>Username</label>
          <input type="text" class="standalone" name="username" autocomplete="username" autofocus value="<?= h($_POST['username']??'') ?>">
        </div>
        <div class="field" style="margin-bottom:20px">
          <label>Password</label>
          <input type="password" class="standalone" name="password" autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-pri" style="width:100%;justify-content:center;padding:11px;font-size:.92rem">Sign in →</button>
      </form>

      <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--n-100);font-size:.75rem;color:var(--n-400);text-align:center">
        First time? <a href="reset_password.php" style="color:var(--pri);font-weight:600">Set your password →</a>
      </div>
      <div style="margin-top:8px;text-align:center">
        <a href="index.php" style="font-size:.75rem;color:var(--n-400)">← Back to form</a>
      </div>
    </div>
  </div>

</div>
</body>
</html>
