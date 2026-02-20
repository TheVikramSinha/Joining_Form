<?php
require_once 'config.php';
require_admin();

$cfg  = get_settings();
$pdo  = db();
$msg  = '';
$msgT = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys    = $_POST['field_key']    ?? [];
    $statuses = $_POST['status']      ?? [];
    $labels  = $_POST['custom_label'] ?? [];

    foreach ($keys as $i => $key) {
        $key    = trim($key);
        $status = $statuses[$i] ?? 'optional';
        $label  = trim($labels[$i] ?? '');
        if (!in_array($status, ['required','optional','hidden'])) $status = 'optional';
        $pdo->prepare("UPDATE field_config SET status=?, custom_label=? WHERE field_key=?")
            ->execute([$status, $label===''?null:$label, $key]);
    }
    // Clear cached field config
    $msg = 'Field rules saved successfully.';
}

// Load all field config grouped by section
$fields = $pdo->query("SELECT * FROM field_config ORDER BY section, display_order, field_key")->fetchAll();
$sections = [];
foreach ($fields as $f) $sections[$f['section']][] = $f;

$adminName = $_SESSION['admin_username'] ?? 'Admin';
$initial   = strtoupper(substr($adminName,0,1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Field Rules — <?= h($cfg['company_name']??APP_NAME) ?></title>
<link rel="stylesheet" href="style.css">
<?= brand_css() ?>
<style>
.fc-label-input { border: 1.5px solid var(--n-200); border-radius: var(--r-sm); padding: 6px 9px; font-size: .82rem; font-family: var(--font-body); color: var(--n-800); background: var(--n-0); width: 100%; transition: border-color .12s; }
.fc-label-input:focus { outline: none; border-color: var(--pri); box-shadow: 0 0 0 2px var(--pri-lt); }
.fc-label-input::placeholder { color: var(--n-300); }
.status-btn { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 99px; font-size: .72rem; font-weight: 600; border: 1.5px solid; cursor: pointer; transition: all .12s; white-space: nowrap; background: none; font-family: var(--font-body); }
.status-btn.required { background: var(--danger-lt); color: var(--danger); border-color: var(--danger-bd); }
.status-btn.optional { background: var(--success-lt); color: var(--success); border-color: var(--success-bd); }
.status-btn.hidden   { background: var(--n-100); color: var(--n-400); border-color: var(--n-200); }
.select-status { border: 1.5px solid var(--n-200); border-radius: 99px; padding: 4px 20px 4px 10px; font-size: .72rem; font-weight: 600; font-family: var(--font-body); cursor: pointer; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='5'%3E%3Cpath fill='%2394a3b8' d='M4 5L0 0h8z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position: right 7px center; }
.sel-required { background-color: var(--danger-lt); color: var(--danger); border-color: var(--danger-bd); }
.sel-optional { background-color: var(--success-lt); color: var(--success); border-color: var(--success-bd); }
.sel-hidden   { background-color: var(--n-100); color: var(--n-500); border-color: var(--n-200); }
</style>
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
    <a href="report.php"      class="sidebar-link"><span class="link-icon">📊</span>Dashboard</a>
    <a href="settings.php"    class="sidebar-link"><span class="link-icon">⚙</span>Settings</a>
    <a href="field_config.php"class="sidebar-link active"><span class="link-icon">🗂</span>Field Rules</a>
    <a href="index.php"       class="sidebar-link" target="_blank"><span class="link-icon">📝</span>View Form</a>
  </nav>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="avatar"><?= h($initial) ?></div>
      <div><div class="user-name"><?= h($adminName) ?></div><div class="user-role">Administrator</div></div>
      <a href="logout.php" class="logout-link" title="Sign out">⏻</a>
    </div>
  </div>
</aside>

<div class="admin-main">
  <div class="admin-topbar">
    <h1>Field Rules</h1>
    <div class="topbar-actions">
      <button form="fcForm" type="submit" class="btn btn-pri btn-sm">💾 Save Changes</button>
    </div>
  </div>
  <div class="admin-content">

    <?php if ($msg): ?><div class="alert alert-<?= $msgT ?>" style="margin-bottom:18px">✓ <?= h($msg) ?></div><?php endif; ?>

    <div class="alert alert-info" style="margin-bottom:20px">
      <div>
        <strong>How it works:</strong> Set each field to <strong>Required</strong> (candidate must fill), <strong>Optional</strong> (shown but not mandatory), or <strong>Hidden</strong> (removed from form entirely). You can also rename any label — the original name is shown as placeholder.
      </div>
    </div>

    <!-- Bulk actions -->
    <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;align-items:center">
      <span style="font-size:.78rem;color:var(--n-500);font-weight:500">Set all to:</span>
      <button type="button" onclick="setAll('required')" class="btn btn-sec btn-xs" style="color:var(--danger);border-color:var(--danger-bd)">All Required</button>
      <button type="button" onclick="setAll('optional')" class="btn btn-sec btn-xs" style="color:var(--success);border-color:var(--success-bd)">All Optional</button>
    </div>

    <form id="fcForm" method="post">
    <?php foreach ($sections as $sectionName => $sectionFields): ?>
    <div class="card" style="margin-bottom:14px">
      <div class="card-head" style="padding:12px 18px">
        <div style="flex:1">
          <h2 style="font-size:.9rem"><?= h($sectionName) ?></h2>
          <p><?= count($sectionFields) ?> field<?= count($sectionFields)!=1?'s':'' ?> in this section</p>
        </div>
        <?php
        // Check if this is a section-toggle field
        $isSectionToggle = (count($sectionFields)===1 && str_starts_with($sectionFields[0]['field_key'],'section_'));
        if ($isSectionToggle):
            $f = $sectionFields[0];
            $curStatus = $f['status'];
        ?>
        <div style="display:flex;align-items:center;gap:8px">
          <span style="font-size:.75rem;color:var(--n-500)">Entire section:</span>
          <input type="hidden" name="field_key[]" value="<?= h($f['field_key']) ?>">
          <input type="hidden" name="custom_label[]" value="">
          <select name="status[]" class="select-status sel-<?= h($curStatus) ?>"
                  onchange="this.className='select-status sel-'+this.value">
            <option value="optional" <?= $curStatus==='optional'?'selected':'' ?>>✓ Show</option>
            <option value="hidden"   <?= $curStatus==='hidden'?'selected':'' ?>>✕ Hide entire section</option>
          </select>
        </div>
        <?php endif; ?>
      </div>

      <?php if (!$isSectionToggle): ?>
      <table class="field-config-table">
        <thead>
          <tr>
            <th style="width:30%">Field</th>
            <th style="width:30%">Custom Label <span style="color:var(--n-300);font-weight:400">(optional rename)</span></th>
            <th style="width:22%">Status</th>
            <th style="width:18%;color:var(--n-400)">Original Label</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sectionFields as $f):
            $curStatus = $f['status'];
            $customLbl = $f['custom_label'] ?? '';
            $origLbl   = $f['original_label'];
          ?>
          <tr>
            <td>
              <input type="hidden" name="field_key[]" value="<?= h($f['field_key']) ?>">
              <code style="font-family:var(--font-mono);font-size:.72rem;color:var(--n-500)"><?= h($f['field_key']) ?></code>
            </td>
            <td>
              <input type="text" class="fc-label-input"
                     name="custom_label[]"
                     value="<?= h($customLbl) ?>"
                     placeholder="<?= h($origLbl) ?>">
            </td>
            <td>
              <select name="status[]" class="select-status sel-<?= h($curStatus) ?>"
                      onchange="this.className='select-status sel-'+this.value">
                <option value="required" <?= $curStatus==='required'?'selected':'' ?>>★ Required</option>
                <option value="optional" <?= $curStatus==='optional'?'selected':'' ?>>✓ Optional</option>
                <option value="hidden"   <?= $curStatus==='hidden'?'selected':'' ?>>✕ Hidden</option>
              </select>
            </td>
            <td style="color:var(--n-400);font-size:.78rem"><?= h($origLbl) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div style="display:flex;justify-content:flex-end;margin-top:12px">
      <button type="submit" class="btn btn-pri">💾 Save All Field Rules</button>
    </div>
    </form>

  </div>
</div>
</div>

<script>
function setAll(status) {
    document.querySelectorAll('select[name="status[]"]').forEach(sel => {
        sel.value = status;
        sel.className = 'select-status sel-' + status;
    });
}
</script>
</body>
</html>
