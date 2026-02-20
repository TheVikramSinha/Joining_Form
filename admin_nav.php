<?php
// admin_nav.php — Include at top of every admin page
// Usage: require_once 'admin_nav.php'; then call admin_shell_start($pageTitle, $activePage)
// After page content, call admin_shell_end()

function admin_shell_start(string $title, string $active): void {
    require_once 'config.php';
    require_admin();
    $cfg = get_settings();
    $adminName = $_SESSION['admin_username'] ?? 'Admin';
    $initial   = strtoupper(substr($adminName, 0, 1));

    // Count incomplete for badge
    try { $incomplete = db()->query("SELECT COUNT(*) FROM candidates WHERE status='Incomplete'")->fetchColumn(); }
    catch (Exception $e) { $incomplete = 0; }

    $nav = [
        ['href'=>'report.php',       'icon'=>'📊', 'label'=>'Dashboard',     'key'=>'report'],
        ['href'=>'view.php',          'icon'=>'👁',  'label'=>'Records',      'key'=>'view',    'hide'=>true],
        ['href'=>'settings.php',      'icon'=>'⚙',  'label'=>'Settings',      'key'=>'settings'],
        ['href'=>'field_config.php',  'icon'=>'🗂',  'label'=>'Field Rules',  'key'=>'fields'],
        ['href'=>'index.php',         'icon'=>'📝',  'label'=>'View Form',    'key'=>'form',    'ext'=>true],
    ];

    $brand = get_brand();
    ?>
    <div class="admin-shell">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-logo">
        <?php if (!empty($cfg['logo_path'])): ?>
        <img src="<?= UPLOAD_URL.h($cfg['logo_path']) ?>" alt="Logo" style="height:26px;width:auto">
        <?php else: ?>
        <div style="width:28px;height:28px;background:var(--pri);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0">📋</div>
        <?php endif; ?>
        <div>
          <div class="logo-text"><?= h($cfg['company_name'] ?? 'Company') ?></div>
          <div class="logo-sub">Admin Panel</div>
        </div>
      </div>

      <nav class="sidebar-nav">
        <div class="sidebar-section-label">Navigation</div>
        <?php foreach ($nav as $item): if (!empty($item['hide'])) continue; ?>
        <a href="<?= $item['href'] ?>" class="sidebar-link <?= $active===$item['key']?'active':'' ?>"
           <?= !empty($item['ext']) ? 'target="_blank"' : '' ?>>
          <span class="link-icon"><?= $item['icon'] ?></span>
          <?= $item['label'] ?>
          <?php if ($item['key']==='report' && $incomplete > 0): ?>
          <span class="link-badge"><?= $incomplete ?></span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </nav>

      <div class="sidebar-footer">
        <div class="sidebar-user">
          <div class="avatar"><?= h($initial) ?></div>
          <div>
            <div class="user-name"><?= h($adminName) ?></div>
            <div class="user-role">Administrator</div>
          </div>
          <a href="logout.php" class="logout-link" title="Sign out">⏻</a>
        </div>
      </div>
    </aside>

    <!-- Main -->
    <div class="admin-main">
      <div class="admin-topbar">
        <h1><?= h($title) ?></h1>
        <div class="topbar-actions">
    <?php
}

function admin_shell_end(): void {
    echo '</div></div></div></div>';
    // close: topbar-actions, admin-topbar, admin-content, admin-main, admin-shell
}
// Usage note: caller must echo closing divs for admin-content manually
// Pattern: admin_shell_start() ... echo '<div class="admin-content">' ... content ... echo '</div>'; admin_shell_end_bare();
