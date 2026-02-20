<?php
require_once 'config.php';
require_admin();

$cfg  = get_settings();
$pdo  = db();
$msg  = '';
$msgT = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_company') {
        $fields = ['company_name','company_address','company_phone','company_email','company_website','form_title'];
        foreach ($fields as $f) {
            $val = trim($_POST[$f] ?? '');
            $pdo->prepare("INSERT INTO app_settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([$f,$val]);
        }
        $msg = 'Company information saved.';
    }

    if ($action === 'save_colours') {
        $colourKeys = ['brand_primary','brand_secondary','brand_accent'];
        foreach ($colourKeys as $k) {
            $val = trim($_POST[$k] ?? '');
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $val)) $val = '#2563eb';
            $pdo->prepare("INSERT INTO app_settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([$k,$val]);
            // Keep legacy key in sync
            if ($k === 'brand_primary') {
                $pdo->prepare("INSERT INTO app_settings (setting_key,setting_value) VALUES ('brand_color',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([$val]);
            }
        }
        $msg = 'Brand colours saved.';
    }

    if ($action === 'save_logo') {
        if (!empty($_FILES['logo']['tmp_name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['svg','png','jpg','jpeg','webp'])) {
                $old = $cfg['logo_path'] ?? '';
                if ($old && file_exists(UPLOAD_DIR.$old)) @unlink(UPLOAD_DIR.$old);
                $fname = 'logo_'.uniqid().'.'.$ext;
                move_uploaded_file($_FILES['logo']['tmp_name'], UPLOAD_DIR.$fname);
                $pdo->prepare("INSERT INTO app_settings (setting_key,setting_value) VALUES ('logo_path',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([$fname]);
                $msg = 'Logo uploaded.';
            } else {
                $msg = 'Logo must be SVG, PNG, JPG, or WebP.'; $msgT = 'danger';
            }
        }
    }

    if ($action === 'remove_logo') {
        $old = $cfg['logo_path'] ?? '';
        if ($old && file_exists(UPLOAD_DIR.$old)) @unlink(UPLOAD_DIR.$old);
        $pdo->prepare("UPDATE app_settings SET setting_value='' WHERE setting_key='logo_path'")->execute();
        $msg = 'Logo removed.';
    }

    // Reload
    header("Location: settings.php?tab=".($_POST['active_tab']??'company')."&saved=1&msg=".urlencode($msg));
    exit;
}

if (isset($_GET['saved'])) { $msg = urldecode($_GET['msg'] ?? 'Saved.'); $msgT = 'success'; }
$cfg   = get_settings();
$brand = get_brand();
$activeTab = $_GET['tab'] ?? 'company';

$adminName = $_SESSION['admin_username'] ?? 'Admin';
$initial   = strtoupper(substr($adminName,0,1));

// Colour presets
$presets = [
    '#2563eb'=>'Blue',   '#7c3aed'=>'Purple', '#0891b2'=>'Teal',
    '#16a34a'=>'Green',  '#dc2626'=>'Red',     '#d97706'=>'Amber',
    '#db2777'=>'Pink',   '#0f172a'=>'Slate',   '#374151'=>'Charcoal',
    '#b45309'=>'Bronze', '#0284c7'=>'Sky',     '#4f46e5'=>'Indigo',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Settings — <?= h($cfg['company_name']??APP_NAME) ?></title>
<link rel="stylesheet" href="style.css">
<?= brand_css() ?>
<style>
.preset-dot { width:24px;height:24px;border-radius:50%;border:2.5px solid transparent;cursor:pointer;transition:all .15s;flex-shrink:0; }
.preset-dot:hover { transform:scale(1.15); border-color:var(--n-400); }
.preset-dot.selected { border-color:var(--n-800); box-shadow:0 0 0 2px #fff,0 0 0 4px var(--n-800); }
.colour-trio { display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px; }
.colour-card { border:1.5px solid var(--n-200);border-radius:var(--r-md);padding:18px;background:var(--n-0); }
.colour-card .cc-role { font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--n-400);margin-bottom:6px; }
.colour-card .cc-bar { height:6px;border-radius:3px;margin-bottom:14px; }
.colour-card .cc-where { font-size:.72rem;color:var(--n-400);margin-top:8px;line-height:1.6; }
.hex-row { display:flex;align-items:center;gap:8px; }
.hex-row .swatch { width:34px;height:34px;border-radius:var(--r-sm);border:1.5px solid var(--n-200);flex-shrink:0;cursor:pointer; }
.hex-input { flex:1;font-family:var(--font-mono);font-size:.85rem;text-transform:uppercase; }
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
    <a href="report.php"       class="sidebar-link"><span class="link-icon">📊</span>Dashboard</a>
    <a href="settings.php"     class="sidebar-link active"><span class="link-icon">⚙</span>Settings</a>
    <a href="field_config.php" class="sidebar-link"><span class="link-icon">🗂</span>Field Rules</a>
    <a href="index.php"        class="sidebar-link" target="_blank"><span class="link-icon">📝</span>View Form</a>
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
    <h1>Settings</h1>
    <div class="topbar-actions">
      <a href="report.php" class="btn btn-sec btn-sm">← Dashboard</a>
    </div>
  </div>
  <div class="admin-content">

    <?php if ($msg): ?><div class="alert alert-<?= $msgT ?>" style="margin-bottom:18px">✓ <?= h($msg) ?></div><?php endif; ?>

    <!-- Tabs -->
    <div class="tabs">
      <button type="button" class="tab-btn <?= $activeTab==='company'?'active':'' ?>" onclick="switchTab('company')">🏢 Company Info</button>
      <button type="button" class="tab-btn <?= $activeTab==='colours'?'active':'' ?>" onclick="switchTab('colours')">🎨 Brand Colours</button>
      <button type="button" class="tab-btn <?= $activeTab==='logo'?'active':'' ?>"    onclick="switchTab('logo')">🖼 Logo</button>
    </div>

    <!-- ── TAB: Company Info ───────────────────────────────── -->
    <div id="tab-company" class="tab-panel <?= $activeTab==='company'?'active':'' ?>">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_company">
        <input type="hidden" name="active_tab" value="company">
        <div class="card">
          <div class="card-head"><div class="card-head-icon">🏢</div><div><h2>Company Information</h2><p>Appears on all form pages and printed letterheads</p></div></div>
          <div class="card-body">
            <div class="field-grid">
              <div class="field col-2">
                <label>Company / Organisation Name</label>
                <input type="text" name="company_name" value="<?= h($cfg['company_name']??'') ?>" placeholder="e.g. Acme Corporation Pvt. Ltd.">
              </div>
              <div class="field col-2">
                <label>Address (shown in letterhead)</label>
                <input type="text" name="company_address" value="<?= h($cfg['company_address']??'') ?>" placeholder="123 MG Road, Mumbai — 400001">
              </div>
              <div class="field">
                <label>Phone / WhatsApp</label>
                <input type="text" name="company_phone" value="<?= h($cfg['company_phone']??'') ?>">
              </div>
              <div class="field">
                <label>HR Email</label>
                <input type="email" name="company_email" value="<?= h($cfg['company_email']??'') ?>">
              </div>
              <div class="field">
                <label>Website</label>
                <input type="text" name="company_website" value="<?= h($cfg['company_website']??'') ?>">
              </div>
              <div class="field">
                <label>Form Title</label>
                <input type="text" name="form_title" value="<?= h($cfg['form_title']??'Employee Joining Form') ?>">
              </div>
            </div>
          </div>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:12px">
          <button type="submit" class="btn btn-pri">💾 Save Company Info</button>
        </div>
      </form>
    </div>

    <!-- ── TAB: Brand Colours ─────────────────────────────── -->
    <div id="tab-colours" class="tab-panel <?= $activeTab==='colours'?'active':'' ?>">
      <form method="post">
        <input type="hidden" name="action" value="save_colours">
        <input type="hidden" name="active_tab" value="colours">

        <!-- Live preview -->
        <div class="card" style="margin-bottom:16px">
          <div class="card-head"><div class="card-head-icon">👁</div><div><h2>Live Preview</h2><p>Updates as you pick colours</p></div></div>
          <div class="card-body">
            <div id="livePreview" style="border:1px solid var(--n-150);border-radius:var(--r);overflow:hidden;font-size:.82rem">
              <!-- Header bar -->
              <div id="prev-header" style="padding:10px 16px;display:flex;align-items:center;gap:10px">
                <div id="prev-logo" style="width:24px;height:24px;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;color:#fff;flex-shrink:0"></div>
                <span id="prev-coname" style="font-weight:700;font-size:.85rem"></span>
                <span style="margin-left:auto;display:flex;gap:6px">
                  <span id="prev-btn" style="padding:5px 12px;border-radius:5px;font-size:.72rem;font-weight:600;color:#fff">Button</span>
                  <span id="prev-badge" style="padding:3px 9px;border-radius:99px;font-size:.68rem;font-weight:700;border:1.5px solid">Accent</span>
                </span>
              </div>
              <!-- Table row preview -->
              <div style="padding:10px 16px;border-top:1px solid #f1f5f9;display:flex;gap:16px;align-items:center">
                <span id="prev-ref" style="padding:2px 8px;border-radius:99px;font-size:.68rem;font-weight:700;border:1.5px solid;font-family:monospace">JF-00001</span>
                <span style="font-weight:600;font-size:.82rem;color:#0f172a">John Smith</span>
                <span style="font-size:.78rem;color:#64748b;flex:1">HR Manager</span>
                <span id="prev-status" style="padding:2px 8px;border-radius:99px;font-size:.68rem;font-weight:700;border:1.5px solid">✓ Complete</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Three colour pickers -->
        <div class="colour-trio" style="margin-bottom:16px">
          <?php
          $colourDefs = [
              'brand_primary'   => ['role'=>'Primary',   'desc'=>'Main actions, buttons, links, section headers, progress bar, letterhead rule.', 'bar'=>$brand['primary']],
              'brand_secondary' => ['role'=>'Secondary', 'desc'=>'Sidebar background, table headers, dark text elements, cover band on print.', 'bar'=>$brand['secondary']],
              'brand_accent'    => ['role'=>'Accent',    'desc'=>'Highlights, badges, notification dots, "incomplete" indicators, call-to-action contrast.', 'bar'=>$brand['accent']],
          ];
          foreach ($colourDefs as $key => $def):
              $curVal = $cfg[$key] ?? ($key==='brand_primary'?$brand['primary']:($key==='brand_secondary'?$brand['secondary']:$brand['accent']));
          ?>
          <div class="colour-card">
            <div class="cc-role"><?= $def['role'] ?></div>
            <div class="cc-bar" id="bar-<?= $key ?>" style="background:<?= h($curVal) ?>"></div>
            <div class="hex-row">
              <input type="color" id="picker-<?= $key ?>" value="<?= h($curVal) ?>"
                     style="width:34px;height:34px;border-radius:var(--r-sm);border:1.5px solid var(--n-200);cursor:pointer;padding:2px"
                     oninput="syncHex('<?= $key ?>',this.value)">
              <input type="text" name="<?= $key ?>" id="hex-<?= $key ?>"
                     class="hex-input standalone"
                     value="<?= h($curVal) ?>"
                     placeholder="#000000"
                     maxlength="7"
                     oninput="syncPicker('<?= $key ?>',this.value)">
            </div>
            <div class="cc-where"><?= $def['desc'] ?></div>

            <!-- Presets for this colour -->
            <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:12px;padding-top:10px;border-top:1px solid var(--n-100)">
              <?php foreach ($presets as $hex=>$name): ?>
              <div class="preset-dot <?= strtolower($curVal)===strtolower($hex)?'selected':'' ?>"
                   title="<?= h($name) ?>"
                   style="background:<?= h($hex) ?>"
                   onclick="applyPreset('<?= $key ?>','<?= h($hex) ?>')"></div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div style="display:flex;justify-content:flex-end">
          <button type="submit" class="btn btn-pri">💾 Save Brand Colours</button>
        </div>
      </form>
    </div>

    <!-- ── TAB: Logo ───────────────────────────────────────── -->
    <div id="tab-logo" class="tab-panel <?= $activeTab==='logo'?'active':'' ?>">
      <div class="card">
        <div class="card-head"><div class="card-head-icon">🖼</div><div><h2>Company Logo</h2><p>SVG strongly recommended — scales perfectly at any size, ideal for watermarks.</p></div></div>
        <div class="card-body">
          <div style="display:grid;grid-template-columns:1fr 240px;gap:24px;align-items:start">
            <div>
              <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_logo">
                <input type="hidden" name="active_tab" value="logo">
                <div class="field" style="margin-bottom:14px">
                  <label>Upload Logo File</label>
                  <input type="file" name="logo" accept=".svg,.png,.jpg,.jpeg,.webp" onchange="previewLogo(this)" style="border:1.5px solid var(--n-200);border-radius:var(--r);padding:8px;background:var(--n-0);font-size:.85rem">
                  <p style="font-size:.72rem;color:var(--n-400);margin-top:4px">SVG, PNG, JPG, or WebP · Max 2MB</p>
                </div>
                <button type="submit" class="btn btn-pri btn-sm">Upload Logo</button>
              </form>

              <?php if (!empty($cfg['logo_path'])): ?>
              <form method="post" style="margin-top:12px" onsubmit="return confirm('Remove logo?')">
                <input type="hidden" name="action" value="remove_logo">
                <input type="hidden" name="active_tab" value="logo">
                <button type="submit" class="btn btn-danger btn-sm">Remove Current Logo</button>
              </form>
              <?php endif; ?>

              <div style="margin-top:20px;padding:14px;background:var(--n-50);border-radius:var(--r);border:1px solid var(--n-150)">
                <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--n-500);margin-bottom:8px">Logo is used for:</div>
                <div style="font-size:.78rem;color:var(--n-600);line-height:2">
                  ✓ Sidebar top-left in admin panel<br>
                  ✓ Top bar of public form<br>
                  ✓ Letterhead on every printed page<br>
                  ✓ Diagonal watermark across all print pages
                </div>
              </div>
            </div>
            <div>
              <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--n-500);margin-bottom:10px">Preview</div>
              <div id="logoPreviewBox" style="border:1.5px dashed var(--n-200);border-radius:var(--r-md);padding:24px;text-align:center;min-height:110px;display:flex;align-items:center;justify-content:center;background:var(--n-25)">
                <?php if (!empty($cfg['logo_path'])): ?>
                <img src="<?= UPLOAD_URL.h($cfg['logo_path']) ?>" id="logoImg" style="max-height:70px;max-width:180px" alt="Logo">
                <?php else: ?>
                <span id="logoImg" style="color:var(--n-300);font-size:.82rem">No logo uploaded</span>
                <?php endif; ?>
              </div>
              <div style="font-size:.7rem;color:var(--n-400);text-align:center;margin-top:6px">Shown at display size</div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /admin-content -->
</div><!-- /admin-main -->
</div><!-- /admin-shell -->

<script>
// Tab switching
function switchTab(name) {
    document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    document.getElementById('tab-'+name).classList.add('active');
    event.target.classList.add('active');
}

// Colour sync
function syncHex(key, val) {
    document.getElementById('hex-'+key).value = val.toUpperCase();
    document.getElementById('bar-'+key).style.background = val;
    updatePreview();
    document.querySelectorAll(`[onclick*="applyPreset('${key}'"]`).forEach(d => {
        d.classList.toggle('selected', d.style.background.toLowerCase()===val.toLowerCase());
    });
}
function syncPicker(key, val) {
    if (/^#[0-9a-fA-F]{6}$/.test(val)) {
        document.getElementById('picker-'+key).value = val;
        document.getElementById('bar-'+key).style.background = val;
        updatePreview();
    }
}
function applyPreset(key, hex) {
    document.getElementById('picker-'+key).value = hex;
    document.getElementById('hex-'+key).value = hex.toUpperCase();
    document.getElementById('bar-'+key).style.background = hex;
    updatePreview();
}

function lighten(hex, pct) {
    const h = hex.replace('#','');
    const r=parseInt(h.slice(0,2),16), g=parseInt(h.slice(2,4),16), b=parseInt(h.slice(4,6),16);
    const nr=Math.round(r+(255-r)*pct), ng=Math.round(g+(255-g)*pct), nb=Math.round(b+(255-b)*pct);
    return '#'+[nr,ng,nb].map(v=>Math.min(255,v).toString(16).padStart(2,'0')).join('');
}

function updatePreview() {
    const pri = document.getElementById('hex-brand_primary')?.value || '#2563eb';
    const sec = document.getElementById('hex-brand_secondary')?.value || '#0f172a';
    const acc = document.getElementById('hex-brand_accent')?.value || '#f59e0b';
    const priLt = lighten(pri, .88);
    const priMid = lighten(pri, .55);
    const accLt = lighten(acc, .85);

    const h = document.getElementById('prev-header');
    if (h) h.style.background = sec;
    const logo = document.getElementById('prev-logo');
    if (logo) { logo.style.background = pri; logo.textContent = (document.querySelector('[name="company_name"]')?.value||'Co').slice(0,2).toUpperCase(); }
    const coname = document.getElementById('prev-coname');
    if (coname) { coname.style.color = '#fff'; coname.textContent = document.querySelector('[name="company_name"]')?.value||'Company Name'; }
    const btn = document.getElementById('prev-btn');
    if (btn) btn.style.background = pri;
    const badge = document.getElementById('prev-badge');
    if (badge) { badge.style.background = accLt; badge.style.color = acc; badge.style.borderColor = lighten(acc,.7); }
    const ref = document.getElementById('prev-ref');
    if (ref) { ref.style.background = priLt; ref.style.color = pri; ref.style.borderColor = priMid; }
    const status = document.getElementById('prev-status');
    if (status) { status.style.background = '#f0fdf4'; status.style.color = '#16a34a'; status.style.borderColor = '#bbf7d0'; }
}

// Logo preview
function previewLogo(input) {
    if (input.files && input.files[0]) {
        const r = new FileReader();
        r.onload = e => {
            const box = document.getElementById('logoPreviewBox');
            box.innerHTML = `<img src="${e.target.result}" style="max-height:70px;max-width:180px">`;
        };
        r.readAsDataURL(input.files[0]);
    }
}

// Init preview on load
updatePreview();
</script>
</body>
</html>
