<?php
// ============================================================
// reset_password.php
// Visit this page ONCE to set the admin password, then DELETE this file.
// ============================================================
require_once 'config.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newpass = $_POST['newpass'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $username = trim($_POST['username'] ?? 'admin');

    if (strlen($newpass) < 8) {
        $message = 'Password must be at least 8 characters.';
    } elseif ($newpass !== $confirm) {
        $message = 'Passwords do not match.';
    } else {
        $hash = password_hash($newpass, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = db()->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)");
        $stmt->execute([$username, $hash]);
        $success = true;
        $message = "Password for '$username' set successfully! Please delete this file now.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset Admin Password</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-icon">🔑</div>
    <h2>Set Admin Password</h2>
    <p>Run once, then <strong>delete this file</strong> from your server.</p>

    <?php if ($message): ?>
    <div class="alert <?= $success ? 'alert-success' : 'alert-danger' ?>" style="margin-bottom:16px"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="post">
      <div class="form-group" style="margin-bottom:14px">
        <label>Admin Username</label>
        <input type="text" name="username" value="admin">
      </div>
      <div class="form-group" style="margin-bottom:14px">
        <label>New Password (min 8 chars)</label>
        <input type="password" name="newpass" autofocus>
      </div>
      <div class="form-group" style="margin-bottom:20px">
        <label>Confirm Password</label>
        <input type="password" name="confirm">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Set Password</button>
    </form>
    <?php else: ?>
    <a href="login.php" class="btn btn-success" style="width:100%;justify-content:center;margin-top:8px">→ Go to Login</a>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
