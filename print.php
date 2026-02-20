<?php
require_once 'config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) die('<p style="font-family:sans-serif;padding:40px">Invalid record ID.</p>');

$pdo = db();
$c   = $pdo->prepare("SELECT * FROM candidates WHERE id = ?"); $c->execute([$id]); $cd = $c->fetch();
if (!$cd) die('<p style="font-family:sans-serif;padding:40px">Record not found.</p>');

$edu = $pdo->prepare("SELECT * FROM education      WHERE candidate_id = ? ORDER BY id"); $edu->execute([$id]); $eduRows = $edu->fetchAll();
$trn = $pdo->prepare("SELECT * FROM training        WHERE candidate_id = ? ORDER BY id"); $trn->execute([$id]); $trnRows = $trn->fetchAll();
$fam = $pdo->prepare("SELECT * FROM family_members  WHERE candidate_id = ? ORDER BY id"); $fam->execute([$id]); $famRows = $fam->fetchAll();
$svc = $pdo->prepare("SELECT * FROM service_history WHERE candidate_id = ? ORDER BY id"); $svc->execute([$id]); $svcRows = $svc->fetchAll();

$cfg      = get_settings();
$brand    = brand_shades($cfg['brand_color'] ?? '#1a56db');
$fullName = trim(implode(' ', array_filter([$cd['first_name'], $cd['middle_name'], $cd['last_name']])));
$refNo    = 'JF-' . str_pad($id, 5, '0', STR_PAD_LEFT);
$today    = date('d M Y');

// ── Helpers ───────────────────────────────────────────────────
function fd($d): string { return $d ? date('d M Y', strtotime($d)) : '—'; }
function fv($v): string { $v = trim((string)($v ?? '')); return $v === '' ? '—' : htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function pf(string $label, $value, bool $full = false, bool $full3 = false): string {
    $cls = $full3 ? 'pf full3' : ($full ? 'pf full' : 'pf');
    return "<div class='$cls'><span class='lbl'>".htmlspecialchars($label)."</span><span class='val'>".fv($value)."</span></div>";
}

// Watermark HTML — logo SVG or company name text
function watermark_inner(array $cfg): string {
    $out = '';
    if (!empty($cfg['logo_path'])) {
        $src = UPLOAD_URL . htmlspecialchars($cfg['logo_path'], ENT_QUOTES);
        // repeat the image across the tile
        for ($i = 0; $i < 30; $i++) {
            $out .= "<img class='wm-img' src='$src' alt=''>";
        }
    } else {
        $name = htmlspecialchars($cfg['company_name'] ?? 'CONFIDENTIAL', ENT_QUOTES);
        for ($i = 0; $i < 20; $i++) {
            $out .= "<span class='wm-text'>$name</span>";
        }
    }
    return $out;
}

// Letterhead header HTML
function letterhead(string $refNo, string $pageLabel, array $cfg, array $brand, string $uploadUrl): string {
    $coName = htmlspecialchars($cfg['company_name']  ?? '', ENT_QUOTES);
    $coAddr = htmlspecialchars($cfg['company_address']?? '', ENT_QUOTES);
    $coPh   = htmlspecialchars($cfg['company_phone']  ?? '', ENT_QUOTES);
    $coEm   = htmlspecialchars($cfg['company_email']  ?? '', ENT_QUOTES);
    $coWeb  = htmlspecialchars($cfg['company_website']?? '', ENT_QUOTES);
    $ref    = htmlspecialchars($refNo, ENT_QUOTES);
    $lbl    = htmlspecialchars($pageLabel, ENT_QUOTES);

    $logo = '';
    if (!empty($cfg['logo_path'])) {
        $src = $uploadUrl . htmlspecialchars($cfg['logo_path'], ENT_QUOTES);
        $logo = "<img src='$src' class='lh-logo' alt='Logo'>";
    } else {
        $logo = "<span class='lh-logo-text'>" . mb_strtoupper(substr($cfg['company_name'] ?? 'CO', 0, 3)) . "</span>";
    }

    return "
    <div class='lh-bar'>
      $logo
      <div class='lh-company'>
        <div class='co-name'>$coName</div>
        <div class='co-addr'>$coAddr &nbsp;|&nbsp; $coPh &nbsp;|&nbsp; $coEm &nbsp;|&nbsp; $coWeb</div>
      </div>
      <div class='lh-ref'>
        <div class='ref-no'>$ref</div>
        <div>$lbl</div>
        <div>Printed: " . date('d M Y') . "</div>
      </div>
    </div>";
}

function wm_block(array $cfg): string {
    $inner = watermark_inner($cfg);
    return "<div class='wm-layer'><div class='wm-tile'>$inner</div></div>";
}

$lh = fn($label) => letterhead($refNo, $label, $cfg, $brand, UPLOAD_URL);
$wm = wm_block($cfg);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($fullName) ?> — <?= h($refNo) ?></title>
<link rel="stylesheet" href="style.css">
<style>
  :root {
    --brand:    <?= h($brand['primary']) ?>;
    --brand-dk: <?= h($brand['primary_dk']) ?>;
    --brand-lt: <?= h($brand['primary_lt']) ?>;
    --brand-md: <?= h($brand['primary_md']) ?>;
  }
  body { background: #d1d5db; }
</style>
</head>
<body>

<!-- Toolbar -->
<div class="print-toolbar no-print">
  <h3>🖨 <?= h($fullName) ?> &nbsp;·&nbsp; <?= h($refNo) ?></h3>
  <a href="view.php?id=<?= $id ?>" class="btn btn-secondary btn-sm">← Back</a>
  <button onclick="window.print()" class="btn btn-primary btn-sm">Print / Save PDF</button>
</div>

<div class="print-preview-wrap">

<!-- ═══════════════════════════════════════════════════════
     PAGE 1 — COVER PAGE
════════════════════════════════════════════════════════ -->
<div class="a4-page">
  <?= $wm ?>
  <div class="page-content">
    <?= $lh('Cover Page') ?>

    <!-- Colour band -->
    <div class="cover-band" style="margin-top:14pt">
      <h2><?= h($cfg['form_title'] ?? 'Employee Joining Form') ?></h2>
      <p>Confidential HR Document — <?= h($cfg['company_name'] ?? '') ?></p>
    </div>

    <!-- Candidate summary -->
    <div class="cover-photo-row" style="margin-top:18pt">
      <?php if (!empty($cd['photo_path'])): ?>
      <img src="<?= UPLOAD_URL . h($cd['photo_path']) ?>" class="cover-photo" alt="Photo">
      <?php else: ?>
      <div class="cover-photo-box">PHOTO<br>HERE</div>
      <?php endif; ?>

      <div class="cover-fields">
        <div style="font-size:15pt;font-weight:800;color:var(--brand-dk);border-bottom:2pt solid var(--brand);padding-bottom:5pt;margin-bottom:10pt;line-height:1.2">
          <?= h($fullName) ?>
        </div>
        <div class="cover-grid">
          <div class="cf-item"><div class="cf-label">Reference No.</div><div class="cf-val"><?= h($refNo) ?></div></div>
          <div class="cf-item"><div class="cf-label">Designation</div><div class="cf-val"><?= fv($cd['initial_designation']) ?></div></div>
          <div class="cf-item"><div class="cf-label">Date of Joining</div><div class="cf-val"><?= fd($cd['date_of_joining']) ?></div></div>
          <div class="cf-item"><div class="cf-label">Date of Appointment</div><div class="cf-val"><?= fd($cd['date_of_appointment']) ?></div></div>
          <div class="cf-item"><div class="cf-label">Department / Office</div><div class="cf-val"><?= fv($cd['office_at_initial_joining']) ?></div></div>
          <div class="cf-item"><div class="cf-label">Mode of Recruitment</div><div class="cf-val"><?= fv($cd['mode_of_recruitment']) ?></div></div>
          <div class="cf-item"><div class="cf-label">Email</div><div class="cf-val"><?= fv($cd['email']) ?></div></div>
          <div class="cf-item"><div class="cf-label">Phone</div><div class="cf-val"><?= fv($cd['present_phone']) ?></div></div>
        </div>
      </div>
    </div>

    <hr class="cover-hr" style="margin-top:14pt">

    <!-- Contents summary -->
    <div style="font-size:8pt;color:#555;margin-top:6pt">
      <strong style="font-size:8.5pt;color:var(--brand-dk);display:block;margin-bottom:6pt">This document contains:</strong>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:3pt 20pt">
        <span>① Personal & Address Information</span>
        <span>② Joining & Salary Details</span>
        <?php if (!empty($eduRows)): ?><span>③ Education Details (<?= count($eduRows) ?> record<?= count($eduRows)>1?'s':'' ?>)</span><?php endif; ?>
        <?php if (!empty($trnRows)): ?><span>④ Training Details (<?= count($trnRows) ?> record<?= count($trnRows)>1?'s':'' ?>)</span><?php endif; ?>
        <?php if (!empty($famRows)): ?><span>⑤ Family Information (<?= count($famRows) ?> member<?= count($famRows)>1?'s':'' ?>)</span><?php endif; ?>
        <?php if (!empty($svcRows)): ?><span>⑥ Service History (<?= count($svcRows) ?> record<?= count($svcRows)>1?'s':'' ?>)</span><?php endif; ?>
        <span>⑦ Nomination & References</span>
        <span>⑧ Documents & Declaration</span>
      </div>
    </div>

    <div class="pg-footer" style="position:absolute;bottom:14mm;left:16mm;right:16mm">
      <span><?= h($cfg['company_name']??'') ?> — Strictly Confidential</span>
      <span>Page 1</span>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     PAGE 2 — PERSONAL & ADDRESS
════════════════════════════════════════════════════════ -->
<div class="a4-page">
  <?= $wm ?>
  <div class="page-content">
    <?= $lh('Personal & Address Information') ?>

    <div class="ps-title">1. Personal Information</div>
    <div class="pg c3">
      <?= pf('First Name',    $cd['first_name']) ?>
      <?= pf('Middle Name',   $cd['middle_name']) ?>
      <?= pf('Last Name',     $cd['last_name']) ?>
      <?= pf('Date of Birth', fd($cd['date_of_birth'])) ?>
      <?= pf('Gender',        $cd['gender']) ?>
      <?= pf('Marital Status',$cd['marital_status']) ?>
      <?= pf('Father / Mother / Husband Name', $cd['parent_name'], false, true) ?>
    </div>

    <div class="ps-title">2. Present Address</div>
    <div class="pg">
      <?= pf('Address', $cd['present_address'], true) ?>
      <?= pf('State',       $cd['present_state']) ?>
      <?= pf('District',    $cd['present_district']) ?>
      <?= pf('Phone',       $cd['present_phone']) ?>
      <?= pf('Email',       $cd['email']) ?>
    </div>

    <div class="ps-title">3. Permanent Address</div>
    <div class="pg">
      <?= pf('Address', $cd['permanent_address'], true) ?>
      <?= pf('State',       $cd['permanent_state']) ?>
      <?= pf('District',    $cd['permanent_district']) ?>
      <?= pf('Phone',       $cd['permanent_phone']) ?>
    </div>

    <div class="pg-footer" style="position:absolute;bottom:14mm;left:16mm;right:16mm">
      <span><?= h($fullName) ?> · <?= h($refNo) ?></span>
      <span>Page 2</span>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     PAGE 3 — JOINING & SALARY
════════════════════════════════════════════════════════ -->
<div class="a4-page">
  <?= $wm ?>
  <div class="page-content">
    <?= $lh('Joining & Salary Details') ?>

    <div class="ps-title">4. Joining Details</div>
    <div class="pg">
      <?= pf('Date of Appointment',        fd($cd['date_of_appointment'])) ?>
      <?= pf('Date of Joining in Dept.',   fd($cd['date_of_joining'])) ?>
      <?= pf('Office at Initial Joining',  $cd['office_at_initial_joining'], true) ?>
      <?= pf('Initial Designation',        $cd['initial_designation']) ?>
      <?= pf('Mode of Recruitment',        $cd['mode_of_recruitment']) ?>
    </div>

    <div class="ps-title">5. Salary & Bank Details <span style="font-weight:400;font-style:italic;font-size:7pt">(At time of initial joining)</span></div>
    <div class="pg c3">
      <?= pf('Basic Pay',       $cd['basic_pay'] ? 'Rs. '.number_format((float)$cd['basic_pay'],2) : '—') ?>
      <?= pf('Bank Name',       $cd['bank_name']) ?>
      <?= pf('IFSC Code',       $cd['ifsc_code']) ?>
      <?= pf('Account Number',  $cd['account_no']) ?>
      <?= pf('PAN Card No.',    $cd['pan_card']) ?>
    </div>

    <?php if (!empty($cd['commitment_text']) || !empty($cd['commitment_person'])): ?>
    <div class="ps-title">6. Employment Commitment</div>
    <div class="pg">
      <?= pf('Commitment Made By', $cd['commitment_person']) ?>
      <?= pf('Commitment Details', $cd['commitment_text'], true) ?>
    </div>
    <?php endif; ?>

    <div class="pg-footer" style="position:absolute;bottom:14mm;left:16mm;right:16mm">
      <span><?= h($fullName) ?> · <?= h($refNo) ?></span>
      <span>Page 3</span>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     PAGE 4 — EDUCATION & TRAINING
════════════════════════════════════════════════════════ -->
<div class="a4-page">
  <?= $wm ?>
  <div class="page-content">
    <?= $lh('Education & Training') ?>

    <div class="ps-title">7. Education Details</div>
    <?php if (!empty($eduRows)): ?>
    <table class="pt">
      <tr><th>Type</th><th>Board / University</th><th>Marks %</th><th>Passing Year</th><th>Stream</th><th>Grade</th></tr>
      <?php foreach ($eduRows as $r): ?>
      <tr>
        <td><?= fv($r['education_type']) ?></td>
        <td><?= fv($r['board_university']) ?></td>
        <td><?= fv($r['marks_percent']) ?></td>
        <td><?= fv($r['passing_year']) ?></td>
        <td><?= fv($r['stream']) ?></td>
        <td><?= fv($r['grade']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php else: ?>
    <p style="font-size:8pt;color:#aaa;padding:6pt 0">No education details recorded.</p>
    <?php endif; ?>

    <div class="ps-title" style="margin-top:14pt">8. Training Details</div>
    <?php if (!empty($trnRows)): ?>
    <table class="pt">
      <tr><th>Location</th><th>Type</th><th>Topic</th><th>Institute</th><th>Sponsored By</th><th>From</th><th>To</th></tr>
      <?php foreach ($trnRows as $r): ?>
      <tr>
        <td><?= fv($r['training_location']) ?></td>
        <td><?= fv($r['training_type']) ?></td>
        <td><?= fv($r['topic_name']) ?></td>
        <td><?= fv($r['institute_name']) ?></td>
        <td><?= fv($r['sponsored_by']) ?></td>
        <td><?= fd($r['date_from']) ?></td>
        <td><?= fd($r['date_to']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php else: ?>
    <p style="font-size:8pt;color:#aaa;padding:6pt 0">No training details recorded.</p>
    <?php endif; ?>

    <div class="pg-footer" style="position:absolute;bottom:14mm;left:16mm;right:16mm">
      <span><?= h($fullName) ?> · <?= h($refNo) ?></span>
      <span>Page 4</span>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     PAGE 5 — FAMILY & SERVICE HISTORY
════════════════════════════════════════════════════════ -->
<div class="a4-page">
  <?= $wm ?>
  <div class="page-content">
    <?= $lh('Family & Service History') ?>

    <div class="ps-title">9. Family Information</div>
    <?php if (!empty($famRows)): ?>
    <table class="pt">
      <tr><th>Name</th><th>Relation</th><th>Date of Birth</th><th>Dependent</th><th>Employment Status</th></tr>
      <?php foreach ($famRows as $r): ?>
      <tr>
        <td><?= fv($r['member_name']) ?></td>
        <td><?= fv($r['relation']) ?></td>
        <td><?= fd($r['date_of_birth']) ?></td>
        <td><?= fv($r['dependent']) ?></td>
        <td><?= fv($r['employment_status']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php else: ?>
    <p style="font-size:8pt;color:#aaa;padding:6pt 0">No family members recorded.</p>
    <?php endif; ?>

    <div class="ps-title" style="margin-top:14pt">10. Service History</div>
    <?php if (!empty($svcRows)): ?>
    <table class="pt">
      <tr><th>Organisation</th><th>Designation</th><th>Salary (Rs.)</th><th>Duration From</th><th>Duration To</th></tr>
      <?php foreach ($svcRows as $r): ?>
      <tr>
        <td><?= fv($r['org_name']) ?></td>
        <td><?= fv($r['designation']) ?></td>
        <td><?= $r['salary_drawn'] ? number_format((float)$r['salary_drawn'],2) : '—' ?></td>
        <td><?= fd($r['duration_from']) ?></td>
        <td><?= fd($r['duration_to']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php else: ?>
    <p style="font-size:8pt;color:#aaa;padding:6pt 0">No previous service history recorded.</p>
    <?php endif; ?>

    <div class="pg-footer" style="position:absolute;bottom:14mm;left:16mm;right:16mm">
      <span><?= h($fullName) ?> · <?= h($refNo) ?></span>
      <span>Page 5</span>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     PAGE 6 — NOMINATION & REFERENCES
════════════════════════════════════════════════════════ -->
<div class="a4-page">
  <?= $wm ?>
  <div class="page-content">
    <?= $lh('Nomination, References & Emergency') ?>

    <div class="ps-title">11. Nomination Details</div>
    <div class="pg c3">
      <?= pf('Name of Nominee',       $cd['nominee_name']) ?>
      <?= pf('Relation with Candidate',$cd['nominee_relation']) ?>
      <?= pf('Nominee Age',           $cd['nominee_age']) ?>
      <?= pf('Address',               $cd['nominee_address'], false, true) ?>
      <?= pf('State',   $cd['nominee_state']) ?>
      <?= pf('Block',   $cd['nominee_block']) ?>
      <?= pf('District',$cd['nominee_district']) ?>
    </div>

    <div class="ps-title">12. References (Not Related to Candidate)</div>
    <div class="pg">
      <?= pf('Reference 1 — Name & Address', $cd['reference_1'], true) ?>
      <?= pf('Reference 2 — Name & Address', $cd['reference_2'], true) ?>
    </div>

    <div class="ps-title">13. Emergency Contact</div>
    <div class="pg">
      <?= pf('Contact Person', $cd['emergency_contact_name']) ?>
      <?= pf('Phone',          $cd['emergency_phone']) ?>
      <?= pf('Address',        $cd['emergency_address'], true) ?>
    </div>

    <div class="pg-footer" style="position:absolute;bottom:14mm;left:16mm;right:16mm">
      <span><?= h($fullName) ?> · <?= h($refNo) ?></span>
      <span>Page 6</span>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     PAGE 7 — DOCUMENTS & DECLARATION
════════════════════════════════════════════════════════ -->
<div class="a4-page">
  <?= $wm ?>
  <div class="page-content">
    <?= $lh('Documents & Declaration') ?>

    <div class="ps-title">14. Document Checklist</div>
    <table class="pt">
      <tr><th style="width:70%">Document</th><th>Status</th></tr>
      <?php
      $docMap = [
          'docs_certificates' => 'Certificates / Mark Sheets (self-attested)',
          'docs_dob'          => 'Proof of Date of Birth',
          'docs_experience'   => 'Experience Certificate from Previous Employer',
          'docs_relieving'    => 'Relieving Letter from Previous Employer',
      ];
      foreach ($docMap as $key => $label):
          $status = $cd[$key] ?? '';
          $icon   = $status === 'Submitted' ? '✓' : ($status === 'Will Submit' ? '○' : '—');
      ?>
      <tr>
        <td><?= h($label) ?></td>
        <td style="font-weight:600;color:<?= $status==='Submitted'?'#15803d':'#777' ?>"><?= $icon ?> <?= h($status ?: 'Not stated') ?></td>
      </tr>
      <?php endforeach; ?>
    </table>

    <div class="ps-title" style="margin-top:14pt">15. Declaration</div>
    <div class="decl-box">
      I DECLARE THAT THE INFORMATION GIVEN, HEREIN ABOVE, IS TRUE &amp; CORRECT TO THE BEST OF MY KNOWLEDGE &amp; BELIEF &amp; NOTHING MATERIAL HAS BEEN CONCEALED. I UNDERSTAND THAT IN CASE IF THE ABOVE INFORMATION IS FOUND FALSE OR INCORRECT, AT ANY TIME DURING THE COURSE OF MY EMPLOYMENT, THEN MY SERVICES CAN BE TERMINATED FORTHWITH WITHOUT ANY NOTICE OR COMPENSATION.
    </div>

    <div style="margin-top:10pt;font-size:8.5pt">
      <strong>Date of Submission:</strong> <?= fd($cd['submitted_at']) ?>
    </div>

    <div class="sign-row">
      <div class="sign-box">
        <div class="sign-line"></div>
        <div class="sign-lbl">HR Department</div>
      </div>
      <div class="sign-box">
        <div class="sign-line"></div>
        <div class="sign-lbl">Signature of Applicant</div>
      </div>
      <div class="sign-box">
        <div class="sign-line"></div>
        <div class="sign-lbl">Director / Authorised Signatory</div>
      </div>
    </div>

    <!-- Office use only box -->
    <div style="border:1pt solid #bbb;border-radius:3pt;padding:8pt;margin-top:14pt;background:#fafafa">
      <div style="font-size:7pt;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:#888;margin-bottom:8pt">For Office Use Only</div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8pt">
        <div><span style="font-size:7pt;color:#888;display:block">Employee Code</span><div style="border-bottom:1pt solid #ccc;min-height:14pt"></div></div>
        <div><span style="font-size:7pt;color:#888;display:block">Department</span><div style="border-bottom:1pt solid #ccc;min-height:14pt"></div></div>
        <div><span style="font-size:7pt;color:#888;display:block">Verified By</span><div style="border-bottom:1pt solid #ccc;min-height:14pt"></div></div>
      </div>
    </div>

    <div class="pg-footer" style="position:absolute;bottom:14mm;left:16mm;right:16mm">
      <span><?= h($fullName) ?> · <?= h($refNo) ?> · <?= h($cfg['company_name']??'') ?></span>
      <span>Page 7 of 7</span>
    </div>
  </div>
</div>

</div><!-- /print-preview-wrap -->

<script>
// Auto-trigger print dialog if ?print=1 in URL
if (new URLSearchParams(window.location.search).get('print') === '1') {
    window.addEventListener('load', () => setTimeout(() => window.print(), 600));
}
</script>
</body>
</html>
