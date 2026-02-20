<?php
require_once 'config.php';
require_admin();

$cfg   = get_settings();
$pdo   = db();

$search   = trim($_GET['search'] ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to']   ?? '';
$status   = $_GET['status']    ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 25;
$offset   = ($page-1)*$perPage;

$where=[]; $params=[];
if ($search!=='') { $where[]="(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR pan_card LIKE ? OR initial_designation LIKE ?)"; $like="%$search%"; $params=array_merge($params,[$like,$like,$like,$like,$like]); }
if ($dateFrom) { $where[]="DATE(submitted_at) >= ?"; $params[]=$dateFrom; }
if ($dateTo)   { $where[]="DATE(submitted_at) <= ?"; $params[]=$dateTo; }
if ($status)   { $where[]="status = ?"; $params[]=$status; }
$sql = $where ? 'WHERE '.implode(' AND ',$where) : '';

if (isset($_GET['export']) && $_GET['export']==='csv') {
    $all=$pdo->prepare("SELECT id,first_name,middle_name,last_name,gender,email,present_phone,initial_designation,date_of_joining,basic_pay,pan_card,status,submitted_at FROM candidates $sql ORDER BY submitted_at DESC");
    $all->execute($params);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="joining_forms_'.date('Ymd_His').'.csv"');
    $fp=fopen('php://output','w'); fprintf($fp,chr(0xEF).chr(0xBB).chr(0xBF));
    $rows=$all->fetchAll();
    if ($rows) { fputcsv($fp,array_keys($rows[0])); foreach($rows as $r) fputcsv($fp,$r); }
    fclose($fp); exit;
}

$total=$pdo->prepare("SELECT COUNT(*) FROM candidates $sql"); $total->execute($params); $total=$total->fetchColumn();
$totalPages=max(1,ceil($total/$perPage));
$records=$pdo->prepare("SELECT * FROM candidates $sql ORDER BY submitted_at DESC LIMIT $perPage OFFSET $offset"); $records->execute($params); $records=$records->fetchAll();

$todayCnt =$pdo->query("SELECT COUNT(*) FROM candidates WHERE DATE(submitted_at)=CURDATE()")->fetchColumn();
$weekCnt  =$pdo->query("SELECT COUNT(*) FROM candidates WHERE submitted_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();
$allCnt   =$pdo->query("SELECT COUNT(*) FROM candidates")->fetchColumn();
$incompl  =$pdo->query("SELECT COUNT(*) FROM candidates WHERE status='Incomplete'")->fetchColumn();

$adminName = $_SESSION['admin_username'] ?? 'Admin';
$initial   = strtoupper(substr($adminName,0,1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — <?= h($cfg['company_name']??APP_NAME) ?></title>
<link rel="stylesheet" href="style.css">
<?= brand_css() ?>
</head>
<body>
<div class="admin-shell">

<!-- Sidebar -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <?php if (!empty($cfg['logo_path'])): ?><img src="<?= UPLOAD_URL.h($cfg['logo_path']) ?>" alt="Logo"><?php else: ?><div style="width:28px;height:28px;background:var(--pri);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0">📋</div><?php endif; ?>
    <div><div class="logo-text"><?= h($cfg['company_name']??'Company') ?></div><div class="logo-sub">Admin Panel</div></div>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-section-label">Navigation</div>
    <a href="report.php" class="sidebar-link active"><span class="link-icon">📊</span>Dashboard<?php if($incompl>0): ?><span class="link-badge"><?= $incompl ?></span><?php endif; ?></a>
    <a href="settings.php" class="sidebar-link"><span class="link-icon">⚙</span>Settings</a>
    <a href="field_config.php" class="sidebar-link"><span class="link-icon">🗂</span>Field Rules</a>
    <a href="index.php" class="sidebar-link" target="_blank"><span class="link-icon">📝</span>View Form</a>
  </nav>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="avatar"><?= h($initial) ?></div>
      <div><div class="user-name"><?= h($adminName) ?></div><div class="user-role">Administrator</div></div>
      <a href="logout.php" class="logout-link" title="Sign out">⏻</a>
    </div>
  </div>
</aside>

<!-- Main -->
<div class="admin-main">
  <div class="admin-topbar">
    <h1>Dashboard</h1>
    <div class="topbar-actions">
      <a href="?<?= http_build_query(array_merge($_GET,['export'=>'csv'])) ?>" class="btn btn-sec btn-sm">⬇ Export CSV</a>
    </div>
  </div>
  <div class="admin-content">

    <!-- Stats -->
    <div class="stat-grid">
      <div class="stat-card">
        <div class="stat-label">Total Submissions</div>
        <div class="stat-val"><?= number_format($allCnt) ?></div>
        <div class="stat-sub">All time</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Today</div>
        <div class="stat-val"><?= $todayCnt ?></div>
        <div class="stat-sub"><?= date('d M Y') ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">This Week</div>
        <div class="stat-val"><?= $weekCnt ?></div>
        <div class="stat-sub">Last 7 days</div>
      </div>
      <div class="stat-card <?= $incompl>0?'':''; ?>">
        <div class="stat-label">Incomplete</div>
        <div class="stat-val" style="<?= $incompl>0?'color:var(--warning)':'' ?>"><?= $incompl ?></div>
        <div class="stat-sub">Need follow-up</div>
      </div>
    </div>

    <!-- Filter bar -->
    <form method="get" class="filter-bar">
      <div class="field"><label>Search</label><input type="text" class="standalone" name="search" value="<?= h($search) ?>" placeholder="Name, email, PAN…"></div>
      <div class="field"><label>From</label><input type="date" class="standalone" name="date_from" value="<?= h($dateFrom) ?>"></div>
      <div class="field"><label>To</label><input type="date" class="standalone" name="date_to" value="<?= h($dateTo) ?>"></div>
      <div class="field">
        <label>Status</label>
        <select class="standalone" name="status" style="width:130px">
          <option value="">All statuses</option>
          <option value="Complete" <?= $status==='Complete'?'selected':'' ?>>Complete</option>
          <option value="Incomplete" <?= $status==='Incomplete'?'selected':'' ?>>Incomplete</option>
        </select>
      </div>
      <div style="display:flex;gap:6px;align-items:flex-end">
        <button type="submit" class="btn btn-pri btn-sm">Filter</button>
        <a href="report.php" class="btn btn-sec btn-sm">Clear</a>
      </div>
    </form>

    <!-- Table -->
    <div class="card">
      <div class="card-head" style="padding:14px 18px">
        <div style="flex:1">
          <h2 style="font-size:.9rem"><?= number_format($total) ?> record<?= $total!=1?'s':'' ?><?= ($search||$dateFrom||$dateTo||$status)?' <span style="color:var(--n-400);font-weight:400;font-size:.78rem">(filtered)</span>':'' ?></h2>
        </div>
      </div>
      <div style="overflow-x:auto">
        <table class="data-table">
          <thead>
            <tr>
              <th>Ref</th><th>Name</th><th>Designation</th><th>Email</th><th>Phone</th>
              <th>Joined</th><th>Submitted</th><th>Status</th><th style="text-align:right">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($records)): ?>
            <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--n-400);font-size:.88rem">No records found.</td></tr>
          <?php else: ?>
          <?php foreach ($records as $r):
            $name  = trim(implode(' ',array_filter([$r['first_name'],$r['middle_name'],$r['last_name']])));
            $refNo = 'JF-'.str_pad($r['id'],5,'0',STR_PAD_LEFT);
            $done  = $r['status']==='Complete';
          ?>
            <tr>
              <td><span class="badge badge-sec" style="font-family:var(--font-mono)"><?= h($refNo) ?></span></td>
              <td><span style="font-weight:600;color:var(--n-900)"><?= h($name) ?></span></td>
              <td><?= h($r['initial_designation']??'—') ?></td>
              <td style="font-size:.8rem;color:var(--n-500)"><?= h($r['email']??'—') ?></td>
              <td><?= h($r['present_phone']??'—') ?></td>
              <td><?= $r['date_of_joining']?date('d M Y',strtotime($r['date_of_joining'])):'—' ?></td>
              <td style="font-size:.8rem;color:var(--n-500)"><?= date('d M Y',strtotime($r['submitted_at'])) ?></td>
              <td><span class="badge <?= $done?'badge-green':'badge-amber' ?>"><?= $done?'✓ Complete':'⚠ Incomplete' ?></span></td>
              <td style="text-align:right;white-space:nowrap">
                <a href="view.php?id=<?= $r['id'] ?>" class="btn btn-sec btn-xs">View</a>
                <a href="print.php?id=<?= $r['id'] ?>" class="btn btn-pri btn-xs" target="_blank">Print</a>
                <a href="delete.php?id=<?= $r['id'] ?>&csrf=<?= urlencode(md5(session_id().$r['id'])) ?>"
                   class="btn btn-danger btn-xs"
                   onclick="return confirm('Delete this record permanently?')">✕</a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if ($totalPages>1):
      $qs=fn($pg)=>'?'.http_build_query(array_filter(['search'=>$search,'date_from'=>$dateFrom,'date_to'=>$dateTo,'status'=>$status,'page'=>$pg]));
    ?>
    <div class="pagination">
      <?php if ($page>1) echo "<a href='{$qs($page-1)}'>‹</a>"; ?>
      <?php for ($p=max(1,$page-2);$p<=min($totalPages,$page+2);$p++): ?>
        <?php echo $p===$page?"<span class='current'>$p</span>":"<a href='{$qs($p)}'>$p</a>"; ?>
      <?php endfor; ?>
      <?php if ($page<$totalPages) echo "<a href='{$qs($page+1)}'>›</a>"; ?>
    </div>
    <?php endif; ?>

  </div><!-- /admin-content -->
</div><!-- /admin-main -->
</div><!-- /admin-shell -->
</body>
</html>
