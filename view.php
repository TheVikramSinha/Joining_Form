<?php
require_once 'config.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('report.php');

$pdo = db();
$c=$pdo->prepare("SELECT * FROM candidates WHERE id=?"); $c->execute([$id]); $cd=$c->fetch();
if (!$cd) redirect('report.php');

$edu=$pdo->prepare("SELECT * FROM education WHERE candidate_id=?"); $edu->execute([$id]); $eduRows=$edu->fetchAll();
$trn=$pdo->prepare("SELECT * FROM training WHERE candidate_id=?"); $trn->execute([$id]); $trnRows=$trn->fetchAll();
$fam=$pdo->prepare("SELECT * FROM family_members WHERE candidate_id=?"); $fam->execute([$id]); $famRows=$fam->fetchAll();
$svc=$pdo->prepare("SELECT * FROM service_history WHERE candidate_id=?"); $svc->execute([$id]); $svcRows=$svc->fetchAll();
$docs=$pdo->prepare("SELECT * FROM candidate_documents WHERE candidate_id=? ORDER BY uploaded_at"); $docs->execute([$id]); $docRows=$docs->fetchAll();
$notes=$pdo->prepare("SELECT * FROM admin_notes WHERE candidate_id=? ORDER BY created_at DESC"); $notes->execute([$id]); $noteRows=$notes->fetchAll();

$cfg      = get_settings();
$fullName = trim(implode(' ', array_filter([$cd['first_name'],$cd['middle_name'],$cd['last_name']])));
$refNo    = 'JF-'.str_pad($id,5,'0',STR_PAD_LEFT);

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add_note') {
    $note = trim($_POST['note_text']??'');
    if ($note!=='') {
        $pdo->prepare("INSERT INTO admin_notes (candidate_id,admin_username,note) VALUES (?,?,?)")
            ->execute([$id, $_SESSION['admin_username']??'admin', $note]);
        redirect("view.php?id=$id#notes");
    }
}
if (isset($_GET['del_note'])) {
    $pdo->prepare("DELETE FROM admin_notes WHERE id=? AND candidate_id=?")->execute([(int)$_GET['del_note'],$id]);
    redirect("view.php?id=$id#notes");
}

function drow(string $label, $val): string {
    $v = trim((string)($val??''));
    $out = $v ? h($v) : '<span style="color:var(--n-300)">—</span>';
    return "<tr><th>".h($label)."</th><td>$out</td></tr>";
}

$adminName = $_SESSION['admin_username'] ?? 'Admin';
$initial   = strtoupper(substr($adminName,0,1));
$done      = $cd['status']==='Complete';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($fullName) ?> — <?= h($refNo) ?></title>
<link rel="stylesheet" href="style.css">
<?= brand_css() ?>
<style>
.two-col { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
@media(max-width:680px){.two-col{grid-template-columns:1fr;}}
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
    <a href="field_config.php"class="sidebar-link"><span class="link-icon">🗂</span>Field Rules</a>
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
    <h1 style="font-size:.92rem"><?= h($fullName) ?> <span style="font-family:var(--font-mono);font-weight:400;color:var(--n-400);font-size:.78rem;margin-left:6px"><?= h($refNo) ?></span></h1>
    <div class="topbar-actions">
      <span class="badge <?= $done?'badge-green':'badge-amber' ?>"><?= $done?'✓ Complete':'⚠ Incomplete' ?></span>
      <a href="report.php" class="btn btn-sec btn-sm">← Back</a>
      <a href="print.php?id=<?= $id ?>" class="btn btn-pri btn-sm" target="_blank">🖨 Print PDF</a>
      <a href="delete.php?id=<?= $id ?>&csrf=<?= urlencode(md5(session_id().$id)) ?>"
         class="btn btn-danger btn-sm"
         onclick="return confirm('Permanently delete this record and all files?')">✕ Delete</a>
    </div>
  </div>
  <div class="admin-content">

    <div class="two-col">
      <!-- Personal -->
      <div class="card">
        <div class="card-head" style="padding:12px 16px"><div class="card-head-icon">👤</div><div><h2>Personal</h2></div></div>
        <div style="padding:0">
          <?php if (!empty($cd['photo_path'])): ?>
          <div style="padding:14px 16px;border-bottom:1px solid var(--n-100)">
            <img src="<?= UPLOAD_URL.h($cd['photo_path']) ?>" style="width:70px;height:88px;object-fit:cover;border-radius:var(--r);border:2px solid var(--n-150)">
          </div>
          <?php endif; ?>
          <table class="detail-table">
            <?= drow('Full Name', $fullName) ?>
            <?= drow('Date of Birth', fmt_date($cd['date_of_birth'])) ?>
            <?= drow('Gender', $cd['gender']) ?>
            <?= drow('Marital Status', $cd['marital_status']) ?>
            <?= drow('Father/Mother/Husband', $cd['parent_name']) ?>
            <?= drow('Email', $cd['email']) ?>
          </table>
        </div>
      </div>

      <!-- Joining & Salary -->
      <div class="card">
        <div class="card-head" style="padding:12px 16px"><div class="card-head-icon">💼</div><div><h2>Joining &amp; Salary</h2></div></div>
        <div style="padding:0">
          <table class="detail-table">
            <?= drow('Date of Appointment', fmt_date($cd['date_of_appointment'])) ?>
            <?= drow('Date of Joining', fmt_date($cd['date_of_joining'])) ?>
            <?= drow('Designation', $cd['initial_designation']) ?>
            <?= drow('Office at Joining', $cd['office_at_initial_joining']) ?>
            <?= drow('Mode of Recruitment', $cd['mode_of_recruitment']) ?>
            <?= drow('Basic Pay', fmt_money($cd['basic_pay'])) ?>
            <?= drow('Bank', $cd['bank_name']) ?>
            <?= drow('Account No.', $cd['account_no']) ?>
            <?= drow('IFSC', $cd['ifsc_code']) ?>
            <?= drow('PAN', $cd['pan_card']) ?>
          </table>
        </div>
      </div>

      <!-- Address -->
      <div class="card">
        <div class="card-head" style="padding:12px 16px"><div class="card-head-icon">🏠</div><div><h2>Address</h2></div></div>
        <div style="padding:0">
          <table class="detail-table">
            <?= drow('Present Address', $cd['present_address']) ?>
            <?= drow('State / District', trim(($cd['present_state']??'').' / '.($cd['present_district']??''),' / ')) ?>
            <?= drow('Phone', $cd['present_phone']) ?>
            <?= drow('Permanent Address', $cd['permanent_address']) ?>
            <?= drow('Perm. State / District', trim(($cd['permanent_state']??'').' / '.($cd['permanent_district']??''),' / ')) ?>
          </table>
        </div>
      </div>

      <!-- Nomination -->
      <div class="card">
        <div class="card-head" style="padding:12px 16px"><div class="card-head-icon">📋</div><div><h2>Nomination &amp; Emergency</h2></div></div>
        <div style="padding:0">
          <table class="detail-table">
            <?= drow('Nominee', $cd['nominee_name']) ?>
            <?= drow('Relation', $cd['nominee_relation']) ?>
            <?= drow('Nominee Address', $cd['nominee_address']) ?>
            <?= drow('Emergency Contact', $cd['emergency_contact_name']) ?>
            <?= drow('Emergency Phone', $cd['emergency_phone']) ?>
            <?= drow('Reference 1', $cd['reference_1']) ?>
            <?= drow('Reference 2', $cd['reference_2']) ?>
          </table>
        </div>
      </div>
    </div>

    <!-- Education -->
    <?php if (!empty($eduRows)): ?>
    <div class="card">
      <div class="card-head" style="padding:12px 16px"><div class="card-head-icon">🎓</div><div><h2>Education</h2></div></div>
      <div style="overflow-x:auto">
        <table class="data-table">
          <thead><tr><th>Type</th><th>Board / University</th><th>Marks %</th><th>Year</th><th>Stream</th><th>Grade</th></tr></thead>
          <tbody>
            <?php foreach($eduRows as $r): ?>
            <tr><td><?= h($r['education_type']) ?></td><td><?= h($r['board_university']??'') ?></td><td><?= h($r['marks_percent']??'') ?></td><td><?= h($r['passing_year']??'') ?></td><td><?= h($r['stream']??'') ?></td><td><?= h($r['grade']??'') ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Training -->
    <?php if (!empty($trnRows)): ?>
    <div class="card">
      <div class="card-head" style="padding:12px 16px"><div class="card-head-icon">🏋</div><div><h2>Training</h2></div></div>
      <div style="overflow-x:auto">
        <table class="data-table">
          <thead><tr><th>Location</th><th>Type</th><th>Topic</th><th>Institute</th><th>Sponsored By</th><th>From</th><th>To</th></tr></thead>
          <tbody>
            <?php foreach($trnRows as $r): ?>
            <tr><td><?= h($r['training_location']) ?></td><td><?= h($r['training_type']??'') ?></td><td><?= h($r['topic_name']??'') ?></td><td><?= h($r['institute_name']??'') ?></td><td><?= h($r['sponsored_by']??'') ?></td><td><?= fmt_date($r['date_from']) ?></td><td><?= fmt_date($r['date_to']) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Family & Service -->
    <?php if (!empty($famRows)): ?>
    <div class="card">
      <div class="card-head" style="padding:12px 16px"><div class="card-head-icon">👨‍👩‍👧</div><div><h2>Family Members</h2></div></div>
      <div style="overflow-x:auto">
        <table class="data-table">
          <thead><tr><th>Name</th><th>Relation</th><th>Date of Birth</th><th>Dependent</th><th>Employment</th></tr></thead>
          <tbody>
            <?php foreach($famRows as $r): ?><tr><td><?= h($r['member_name']) ?></td><td><?= h($r['relation']??'') ?></td><td><?= fmt_date($r['date_of_birth']) ?></td><td><?= h($r['dependent']??'') ?></td><td><?= h($r['employment_status']??'') ?></td></tr><?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($svcRows)): ?>
    <div class="card">
      <div class="card-head" style="padding:12px 16px"><div class="card-head-icon">🏢</div><div><h2>Service History</h2></div></div>
      <div style="overflow-x:auto">
        <table class="data-table">
          <thead><tr><th>Organisation</th><th>Designation</th><th>Salary (Rs.)</th><th>From</th><th>To</th></tr></thead>
          <tbody>
            <?php foreach($svcRows as $r): ?><tr><td><?= h($r['org_name']) ?></td><td><?= h($r['designation']??'') ?></td><td><?= $r['salary_drawn']?number_format((float)$r['salary_drawn'],2):'—' ?></td><td><?= fmt_date($r['duration_from']) ?></td><td><?= fmt_date($r['duration_to']) ?></td></tr><?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Documents -->
    <div class="card" id="documents">
      <div class="card-head" style="padding:12px 16px"><div class="card-head-icon">📎</div><div><h2>Documents</h2><p><?= count($docRows) ?> file<?= count($docRows)!=1?'s':'' ?> uploaded</p></div></div>
      <div class="card-body">
        <?php if (empty($docRows)): ?>
        <p style="color:var(--n-400);font-size:.85rem">No documents uploaded.</p>
        <?php else: ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <?php foreach($docRows as $doc):
            $isPdf = strtolower(pathinfo($doc['file_path'],PATHINFO_EXTENSION))==='pdf';
            $icon  = $isPdf?'📄':'🖼';
            $size  = $doc['file_size']?round($doc['file_size']/1024,1).' KB':'';
            $url   = UPLOAD_URL.'docs/'.h($doc['file_path']);
          ?>
          <div class="doc-card">
            <div class="doc-icon"><?= $icon ?></div>
            <div class="doc-info">
              <strong><?= h($doc['doc_label']) ?></strong>
              <div style="font-size:.75rem;color:var(--n-500);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= h($doc['file_name']) ?></div>
              <small><?= h($size) ?> · <?= date('d M Y',strtotime($doc['uploaded_at'])) ?></small>
            </div>
            <div class="doc-actions">
              <a href="<?= $url ?>" target="_blank" class="btn btn-sec btn-xs">View</a>
              <?php if (!$isPdf): ?>
              <button type="button" class="btn btn-pri btn-xs" onclick="printImg('<?= $url ?>')">Print</button>
              <?php endif; ?>
              <a href="serve_doc.php?id=<?= $doc['id'] ?>&dl=1" class="btn btn-sec btn-xs">⬇</a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Doc checklist status -->
        <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--n-100)">
          <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--n-400);margin-bottom:10px">Checklist Status (self-reported)</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
            <?php
            $check=['docs_certificates'=>'Certificates','docs_dob'=>'DOB Proof','docs_experience'=>'Experience Cert.','docs_relieving'=>'Relieving Letter'];
            foreach($check as $k=>$lbl):
              $v=$cd[$k]??''; $icon=$v==='Submitted'?'✅':($v==='Will Submit'?'🕐':'❓');
            ?>
            <div style="display:flex;align-items:center;gap:8px;font-size:.8rem">
              <span><?= $icon ?></span>
              <div><strong style="display:block;font-size:.78rem"><?= h($lbl) ?></strong><span style="color:var(--n-400);font-size:.72rem"><?= h($v?:'Not stated') ?></span></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Admin Notes -->
    <div class="card" id="notes">
      <div class="card-head" style="padding:12px 16px"><div class="card-head-icon">📝</div><div><h2>HR Notes</h2><p>Internal · Not visible to candidate</p></div></div>
      <div class="card-body">
        <?php foreach($noteRows as $n): ?>
        <div class="note-item">
          <div class="note-meta"><?= h($n['admin_username']) ?> · <?= date('d M Y, g:i A',strtotime($n['created_at'])) ?>
            <a href="?id=<?= $id ?>&del_note=<?= $n['id'] ?>" style="float:right;color:var(--danger);font-size:.72rem;text-decoration:none" onclick="return confirm('Delete note?')">✕</a>
          </div>
          <div class="note-text"><?= nl2br(h($n['note'])) ?></div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($noteRows)): ?><p style="color:var(--n-400);font-size:.83rem;margin-bottom:12px">No notes yet.</p><?php endif; ?>
        <form method="post" style="margin-top:10px">
          <input type="hidden" name="action" value="add_note">
          <div class="field" style="margin-bottom:8px">
            <label>Add Note</label>
            <textarea class="standalone" name="note_text" rows="3" placeholder="e.g. Pending original certificates. Follow up on 15 Mar…" required></textarea>
          </div>
          <button type="submit" class="btn btn-pri btn-sm">Add Note</button>
        </form>
      </div>
    </div>

  </div><!-- /admin-content -->
</div><!-- /admin-main -->
</div><!-- /admin-shell -->

<script>
function printImg(url) {
    const w = window.open('','_blank','width=900,height=700');
    w.document.write(`<html><head><style>body{margin:0;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#111}img{max-width:100%;max-height:100vh}</style></head><body><img src="${url}" onload="window.print()"></body></html>`);
    w.document.close();
}
</script>
</body>
</html>
