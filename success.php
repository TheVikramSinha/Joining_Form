<?php
require_once 'config.php';
session_start_safe();
$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('index.php');
$stmt = db()->prepare("SELECT first_name,last_name,initial_designation,submitted_at FROM candidates WHERE id=?");
$stmt->execute([$id]); $c = $stmt->fetch();
if (!$c) redirect('index.php');
$cfg  = get_settings();
$name = h(trim($c['first_name'].' '.$c['last_name']));
$ref  = 'JF-'.str_pad($id,5,'0',STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Submitted — <?= h($cfg['form_title']??APP_NAME) ?></title>
<link rel="stylesheet" href="style.css">
<?= brand_css() ?>
</head>
<body>
<div class="success-shell">
  <div class="success-card">
    <span class="success-icon">✅</span>
    <h2>Form Submitted</h2>
    <p style="color:var(--n-500);margin-bottom:4px">Thank you, <strong style="color:var(--n-800)"><?= $name ?></strong>.</p>
    <?php if ($c['initial_designation']): ?><p style="color:var(--n-400);font-size:.85rem;margin-bottom:0"><?= h($c['initial_designation']) ?></p><?php endif; ?>
    <p style="color:var(--n-500);margin-top:14px;font-size:.87rem">Your joining form has been received. Save your reference number:</p>
    <div class="ref-tag"><?= h($ref) ?></div>
    <p style="font-size:.75rem;color:var(--n-400)">Submitted <?= date('d M Y, g:i A', strtotime($c['submitted_at'])) ?></p>
    <div style="display:flex;gap:10px;justify-content:center;margin-top:20px;flex-wrap:wrap">
      <a href="print.php?id=<?= $id ?>" class="btn btn-pri" target="_blank">🖨 Print / Save PDF</a>
      <a href="index.php" class="btn btn-sec">New Submission</a>
    </div>
  </div>
</div>
</body>
</html>
