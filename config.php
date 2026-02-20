<?php
// ============================================================
// config.php v3 — Edit ONLY the DB credentials below
// ============================================================
define('DB_HOST',    'localhost');
define('DB_NAME',    'joining_form');
define('DB_USER',    'your_db_user');
define('DB_PASS',    'your_db_password');
define('DB_CHARSET', 'utf8mb4');
define('UPLOAD_DIR',       __DIR__ . '/uploads/');
define('UPLOAD_URL',       'uploads/');
define('DOCS_DIR',         __DIR__ . '/uploads/docs/');
define('MAX_DOC_SIZE',     10 * 1024 * 1024);
define('ADMIN_SESSION_KEY','jf_admin_v3');
define('APP_NAME',         'Employee Joining Form');

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]);
    } catch (PDOException $e) {
        die('<div style="font-family:sans-serif;padding:48px;max-width:540px;margin:60px auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px"><h2 style="color:#111">⚠️ Database Error</h2><p style="color:#6b7280">Check credentials in <code>config.php</code></p><code style="display:block;background:#f9fafb;padding:12px;border-radius:6px;margin-top:12px;font-size:.8rem">'.h($e->getMessage()).'</code></div>');
    }
    return $pdo;
}

function get_settings(): array {
    static $s = null;
    if ($s !== null) return $s;
    try { $s = db()->query("SELECT setting_key, setting_value FROM app_settings")->fetchAll(PDO::FETCH_KEY_PAIR); }
    catch (Exception $e) { $s = []; }
    return $s;
}
function setting(string $key, string $default = ''): string { return get_settings()[$key] ?? $default; }

function adjust_hex(string $hex, int $amount): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex)===3) $hex=$hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    $r=hexdec(substr($hex,0,2)); $g=hexdec(substr($hex,2,2)); $b=hexdec(substr($hex,4,2));
    if ($amount > 0) { $p=$amount/100; $r=(int)($r+(255-$r)*$p); $g=(int)($g+(255-$g)*$p); $b=(int)($b+(255-$b)*$p); }
    else { $p=abs($amount)/100; $r=(int)($r*(1-$p)); $g=(int)($g*(1-$p)); $b=(int)($b*(1-$p)); }
    return sprintf('#%02x%02x%02x',min(255,max(0,$r)),min(255,max(0,$g)),min(255,max(0,$b)));
}

function get_brand(): array {
    $cfg = get_settings();
    $pri = $cfg['brand_primary']   ?? $cfg['brand_color'] ?? '#2563eb';
    $sec = $cfg['brand_secondary'] ?? '#0f172a';
    $acc = $cfg['brand_accent']    ?? '#f59e0b';
    return [
        'primary'     => $pri, 'primary_dk'  => adjust_hex($pri,-30),
        'primary_lt'  => adjust_hex($pri,88), 'primary_mid' => adjust_hex($pri,55),
        'secondary'   => $sec, 'secondary_lt'=> adjust_hex($sec,80),
        'accent'      => $acc, 'accent_dk'   => adjust_hex($acc,-25), 'accent_lt' => adjust_hex($acc,85),
    ];
}

function brand_css(): string {
    $b = get_brand();
    return "<style>:root{--pri:{$b['primary']};--pri-dk:{$b['primary_dk']};--pri-lt:{$b['primary_lt']};--pri-mid:{$b['primary_mid']};--sec:{$b['secondary']};--sec-lt:{$b['secondary_lt']};--acc:{$b['accent']};--acc-dk:{$b['accent_dk']};--acc-lt:{$b['accent_lt']};}</style>";
}

function get_field_config(): array {
    static $fc = null;
    if ($fc !== null) return $fc;
    try {
        $rows = db()->query("SELECT field_key,custom_label,original_label,status FROM field_config")->fetchAll();
        $fc = [];
        foreach ($rows as $r) $fc[$r['field_key']] = $r;
    } catch (Exception $e) { $fc = []; }
    return $fc;
}
function fc(string $key): array {
    $cfg = get_field_config();
    if (!isset($cfg[$key])) return ['label'=>ucwords(str_replace('_',' ',$key)),'status'=>'optional'];
    return ['label'=>($cfg[$key]['custom_label']?:$cfg[$key]['original_label']),'status'=>$cfg[$key]['status']];
}
function fc_visible(string $key): bool { return fc($key)['status']!=='hidden'; }
function fc_required(string $key): bool { return fc($key)['status']==='required'; }
function fc_label(string $key): string { return fc($key)['label']; }
function req_star(string $key): string { return fc_required($key)?'<span class="req">*</span>':''; }
function req_attr(string $key): string { return fc_required($key)?' required':''; }

function h(string $s): string { return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); }
function redirect(string $url): void { header("Location: $url"); exit; }
function session_start_safe(): void { if (session_status()===PHP_SESSION_NONE) session_start(); }
function is_admin_logged_in(): bool { session_start_safe(); return ($_SESSION[ADMIN_SESSION_KEY]??false)===true; }
function require_admin(): void { if (!is_admin_logged_in()) redirect('login.php'); }
function fmt_date($d): string { return $d ? date('d M Y',strtotime($d)) : ''; }
function fmt_money($v): string { return $v ? 'Rs. '.number_format((float)$v,2) : ''; }
function fv($v): string { $v=trim((string)($v??'')); return $v===''?'—':h($v); }

foreach ([UPLOAD_DIR,DOCS_DIR] as $dir) if (!is_dir($dir)) mkdir($dir,0755,true);
