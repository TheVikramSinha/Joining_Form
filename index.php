<?php
require_once 'config.php';
session_start_safe();

$steps = [
    1=>['icon'=>'👤','label'=>'Personal'],  2=>['icon'=>'🏠','label'=>'Address'],
    3=>['icon'=>'💼','label'=>'Joining'],   4=>['icon'=>'💰','label'=>'Salary'],
    5=>['icon'=>'🎓','label'=>'Education'], 6=>['icon'=>'🏋','label'=>'Training'],
    7=>['icon'=>'👨‍👩‍👧','label'=>'Family'],   8=>['icon'=>'📋','label'=>'Nomination'],
    9=>['icon'=>'📎','label'=>'Documents'],
];
$totalSteps = count($steps);

// Filter hidden sections
$fc = get_field_config();
$sectionVisible = [
    5 => ($fc['section_education']['status'] ?? 'optional') !== 'hidden',
    6 => ($fc['section_training']['status']  ?? 'optional') !== 'hidden',
    7 => ($fc['section_family']['status']    ?? 'optional') !== 'hidden' || ($fc['section_service']['status'] ?? 'optional') !== 'hidden',
];

if (!isset($_SESSION['jf_step'])) $_SESSION['jf_step'] = 1;
if (!isset($_SESSION['jf_data'])) $_SESSION['jf_data'] = [];

$currentStep = (int)($_GET['step'] ?? $_SESSION['jf_step']);
$currentStep = max(1, min($totalSteps, $currentStep));
$error = '';
$cfg   = get_settings();

// ── POST ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $step   = (int)($_POST['step'] ?? 1);
    $posted = $_POST;
    unset($posted['action'], $posted['step']);

    if ($step === 1 && !empty($_FILES['photo']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $fname = 'photo_'.uniqid().'.'.$ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_DIR.$fname);
            $posted['photo_path'] = $fname;
        }
    }

    $_SESSION['jf_data'] = array_merge($_SESSION['jf_data'], $posted);

    if ($action === 'back')   { $prev = max(1,$step-1); $_SESSION['jf_step']=$prev; redirect("index.php?step=$prev"); }
    if ($action === 'next')   { $next = $step+1; $_SESSION['jf_step']=$next; redirect("index.php?step=$next"); }

    if ($action === 'submit') {
        $data = $_SESSION['jf_data'];
        $pdo  = db();
        $pdo->beginTransaction();
        try {
            $nul = fn($v) => ($v===''||$v===null)?null:$v;
            $req = ['first_name','last_name','date_of_joining','initial_designation'];
            $complete = true;
            foreach ($req as $rf) if (empty($data[$rf])) { $complete=false; break; }

            $stmt = $pdo->prepare("INSERT INTO candidates (
                first_name,middle_name,last_name,date_of_birth,parent_name,gender,marital_status,photo_path,
                present_address,present_state,present_district,present_phone,email,
                permanent_address,permanent_state,permanent_district,permanent_phone,
                date_of_appointment,office_at_initial_joining,date_of_joining,initial_designation,mode_of_recruitment,
                basic_pay,bank_name,ifsc_code,account_no,pan_card,commitment_person,commitment_text,
                nominee_name,nominee_relation,nominee_age,nominee_address,nominee_state,nominee_block,nominee_district,
                reference_1,reference_2,emergency_contact_name,emergency_address,emergency_phone,
                docs_certificates,docs_dob,docs_experience,docs_relieving,status
            ) VALUES (
                :fn,:mn,:ln,:dob,:pn,:g,:ms,:pp,
                :pa,:ps,:pd,:pph,:em,
                :pma,:pms,:pmd,:pmph,
                :doa,:oij,:doj,:id,:mr,
                :bp,:bn,:ifsc,:an,:pan,:cp,:ct,
                :nn,:nr,:na,:nad,:ns,:nb,:nd,
                :r1,:r2,:ecn,:ea,:ep,
                :dc,:dd,:de,:dr,:st
            )");
            $stmt->execute([
                ':fn'=>$nul($data['first_name']??''),   ':mn'=>$nul($data['middle_name']??''),
                ':ln'=>$nul($data['last_name']??''),    ':dob'=>$nul($data['date_of_birth']??''),
                ':pn'=>$nul($data['parent_name']??''),  ':g'=>$nul($data['gender']??''),
                ':ms'=>$nul($data['marital_status']??''),':pp'=>$nul($data['photo_path']??''),
                ':pa'=>$nul($data['present_address']??''),':ps'=>$nul($data['present_state']??''),
                ':pd'=>$nul($data['present_district']??''),':pph'=>$nul($data['present_phone']??''),
                ':em'=>$nul($data['email']??''),
                ':pma'=>$nul($data['permanent_address']??''),':pms'=>$nul($data['permanent_state']??''),
                ':pmd'=>$nul($data['permanent_district']??''),':pmph'=>$nul($data['permanent_phone']??''),
                ':doa'=>$nul($data['date_of_appointment']??''),':oij'=>$nul($data['office_at_initial_joining']??''),
                ':doj'=>$nul($data['date_of_joining']??''),':id'=>$nul($data['initial_designation']??''),
                ':mr'=>$nul($data['mode_of_recruitment']??''),
                ':bp'=>$nul($data['basic_pay']??''),    ':bn'=>$nul($data['bank_name']??''),
                ':ifsc'=>$nul($data['ifsc_code']??''),  ':an'=>$nul($data['account_no']??''),
                ':pan'=>$nul($data['pan_card']??''),    ':cp'=>$nul($data['commitment_person']??''),
                ':ct'=>$nul($data['commitment_text']??''),
                ':nn'=>$nul($data['nominee_name']??''),  ':nr'=>$nul($data['nominee_relation']??''),
                ':na'=>$nul($data['nominee_age']??''),   ':nad'=>$nul($data['nominee_address']??''),
                ':ns'=>$nul($data['nominee_state']??''), ':nb'=>$nul($data['nominee_block']??''),
                ':nd'=>$nul($data['nominee_district']??''),
                ':r1'=>$nul($data['reference_1']??''),   ':r2'=>$nul($data['reference_2']??''),
                ':ecn'=>$nul($data['emergency_contact_name']??''),':ea'=>$nul($data['emergency_address']??''),
                ':ep'=>$nul($data['emergency_phone']??''),
                ':dc'=>$nul($data['docs_certificates']??''),':dd'=>$nul($data['docs_dob']??''),
                ':de'=>$nul($data['docs_experience']??''),':dr'=>$nul($data['docs_relieving']??''),
                ':st'=>$complete?'Complete':'Incomplete',
            ]);
            $cid = $pdo->lastInsertId();

            foreach (($data['edu_type']??[]) as $i=>$t) {
                if (empty($data['edu_board'][$i])) continue;
                $pdo->prepare("INSERT INTO education (candidate_id,education_type,board_university,marks_percent,passing_year,stream,grade) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$cid,$t,$data['edu_board'][$i],$nul($data['edu_marks'][$i]??''),$nul($data['edu_year'][$i]??''),$nul($data['edu_stream'][$i]??''),$nul($data['edu_grade'][$i]??'')]);
            }
            foreach (($data['trn_location']??[]) as $i=>$loc) {
                if (empty($data['trn_topic'][$i])) continue;
                $pdo->prepare("INSERT INTO training (candidate_id,training_location,training_type,topic_name,institute_name,sponsored_by,date_from,date_to) VALUES (?,?,?,?,?,?,?,?)")
                    ->execute([$cid,$loc,$nul($data['trn_type'][$i]??''),$data['trn_topic'][$i],$nul($data['trn_institute'][$i]??''),$nul($data['trn_sponsored'][$i]??''),$nul($data['trn_from'][$i]??''),$nul($data['trn_to'][$i]??'')]);
            }
            foreach (($data['fam_name']??[]) as $i=>$n) {
                if (empty($n)) continue;
                $pdo->prepare("INSERT INTO family_members (candidate_id,member_name,relation,date_of_birth,dependent,employment_status) VALUES (?,?,?,?,?,?)")
                    ->execute([$cid,$n,$nul($data['fam_relation'][$i]??''),$nul($data['fam_dob'][$i]??''),$nul($data['fam_dependent'][$i]??''),$nul($data['fam_employment'][$i]??'')]);
            }
            foreach (($data['svc_org']??[]) as $i=>$o) {
                if (empty($o)) continue;
                $pdo->prepare("INSERT INTO service_history (candidate_id,org_name,designation,salary_drawn,duration_from,duration_to) VALUES (?,?,?,?,?,?)")
                    ->execute([$cid,$o,$nul($data['svc_desig'][$i]??''),$nul($data['svc_salary'][$i]??''),$nul($data['svc_from'][$i]??''),$nul($data['svc_to'][$i]??'')]);
            }

            $docLabels = [
                'docs_upload_certificates'=>'Certificates / Mark Sheets',
                'docs_upload_dob'=>'Date of Birth Proof',
                'docs_upload_experience'=>'Experience Certificate',
                'docs_upload_relieving'=>'Relieving Letter',
                'docs_upload_other'=>'Other Document',
            ];
            foreach ($docLabels as $field=>$label) {
                if (empty($_FILES[$field]['tmp_name'])) continue;
                $names=$_FILES[$field]['name']; $tmps=$_FILES[$field]['tmp_name'];
                $types=$_FILES[$field]['type']; $sizes=$_FILES[$field]['size']; $errs=$_FILES[$field]['error'];
                if (!is_array($names)) { $names=[$names]; $tmps=[$tmps]; $types=[$types]; $sizes=[$sizes]; $errs=[$errs]; }
                foreach ($names as $fi=>$orig) {
                    if ($errs[$fi]!==UPLOAD_ERR_OK||empty($tmps[$fi])) continue;
                    if ($sizes[$fi]>MAX_DOC_SIZE) continue;
                    $ext2=strtolower(pathinfo($orig,PATHINFO_EXTENSION));
                    if (!in_array($ext2,['jpg','jpeg','png','gif','webp','pdf'])) continue;
                    $safe='doc_'.$cid.'_'.uniqid().'.'.$ext2;
                    move_uploaded_file($tmps[$fi],DOCS_DIR.$safe);
                    $pdo->prepare("INSERT INTO candidate_documents (candidate_id,doc_label,file_name,file_path,file_type,file_size) VALUES (?,?,?,?,?,?)")
                        ->execute([$cid,$label,$orig,$safe,$types[$fi],$sizes[$fi]]);
                }
            }
            $pdo->commit();
            $_SESSION['jf_data']=[]; $_SESSION['jf_step']=1;
            redirect("success.php?id=$cid");
        } catch (Exception $e) {
            $pdo->rollBack();
            $error='Error saving: '.$e->getMessage();
        }
    }
}

$d = $_SESSION['jf_data'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($cfg['form_title']??APP_NAME) ?> — Step <?= $currentStep ?></title>
<link rel="stylesheet" href="style.css">
<?= brand_css() ?>
</head>
<body>
<div class="form-shell">

<!-- Top bar -->
<header class="form-topbar">
  <?php if (!empty($cfg['logo_path'])): ?>
  <img src="<?= UPLOAD_URL.h($cfg['logo_path']) ?>" class="co-logo" alt="Logo">
  <?php else: ?>
  <div style="width:28px;height:28px;background:var(--pri);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;color:#fff"><?= strtoupper(substr($cfg['company_name']??'C',0,2)) ?></div>
  <?php endif; ?>
  <span class="co-name"><?= h($cfg['company_name']??'') ?></span>
  <div class="divider"></div>
  <span class="form-title"><?= h($cfg['form_title']??APP_NAME) ?></span>
  <div style="margin-left:auto">
    <a href="login.php" style="font-size:.75rem;color:var(--n-400);text-decoration:none">Admin ↗</a>
  </div>
</header>

<!-- Step progress -->
<div class="stepper-wrap">
  <div class="stepper">
    <?php foreach ($steps as $n=>$s):
      // Skip visually hidden steps
      if (isset($sectionVisible[$n]) && !$sectionVisible[$n]) continue;
    ?>
    <div class="step-item <?= $n<$currentStep?'done':($n===$currentStep?'active':'') ?>">
      <div class="step-dot"><?= $n<$currentStep?'✓':$n ?></div>
      <div class="step-name"><?= $s['label'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Form body -->
<div class="form-body">
<div class="form-pane">

<?php if ($error): ?><div class="alert alert-danger" style="margin-bottom:16px">⚠ <?= h($error) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" id="jfForm">
<input type="hidden" name="step" value="<?= $currentStep ?>">

<?php if ($currentStep===1): // ── PERSONAL ───────────────── ?>
<div class="card">
  <div class="card-head">
    <div class="card-head-icon">👤</div>
    <div><h2>Personal Information</h2><p>Candidate basic details</p></div>
  </div>
  <div class="card-body">
    <div style="display:grid;grid-template-columns:1fr 130px;gap:22px;align-items:start">
      <div class="field-grid c1">
        <?php if (fc_visible('first_name')): ?>
        <div class="field">
          <label><?= h(fc_label('first_name')) ?> <?= req_star('first_name') ?></label>
          <input type="text" name="first_name" value="<?= h($d['first_name']??'') ?>"<?= req_attr('first_name') ?>>
        </div>
        <?php endif; ?>
        <?php if (fc_visible('middle_name')): ?>
        <div class="field">
          <label><?= h(fc_label('middle_name')) ?> <?= req_star('middle_name') ?></label>
          <input type="text" name="middle_name" value="<?= h($d['middle_name']??'') ?>"<?= req_attr('middle_name') ?>>
        </div>
        <?php endif; ?>
        <?php if (fc_visible('last_name')): ?>
        <div class="field">
          <label><?= h(fc_label('last_name')) ?> <?= req_star('last_name') ?></label>
          <input type="text" name="last_name" value="<?= h($d['last_name']??'') ?>"<?= req_attr('last_name') ?>>
        </div>
        <?php endif; ?>
      </div>

      <?php if (fc_visible('photo')): ?>
      <div>
        <div style="font-size:.7rem;font-weight:700;color:var(--n-500);text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px"><?= h(fc_label('photo')) ?></div>
        <div class="photo-zone" onclick="document.getElementById('photoInput').click()">
          <?php $hp=!empty($d['photo_path']); ?>
          <img class="photo-preview" id="photoPreview" src="<?= $hp?UPLOAD_URL.h($d['photo_path']):'' ?>" style="display:<?= $hp?'block':'none' ?>">
          <div class="photo-placeholder" id="photoPlaceholder" style="display:<?= $hp?'none':'flex' ?>">
            <div class="ph-icon">📷</div>
            <div>Click to upload</div>
          </div>
          <div class="photo-zone-hint" style="<?= $hp?'display:none':'' ?>">Passport size · JPG / PNG</div>
          <input type="file" id="photoInput" name="photo" accept="image/*" onchange="previewPhoto(this)">
        </div>
        <?php if ($hp): ?><input type="hidden" name="photo_path" value="<?= h($d['photo_path']) ?>"><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="field-grid" style="margin-top:20px">
      <?php if (fc_visible('date_of_birth')): ?>
      <div class="field">
        <label><?= h(fc_label('date_of_birth')) ?> <?= req_star('date_of_birth') ?></label>
        <input type="date" name="date_of_birth" value="<?= h($d['date_of_birth']??'') ?>"<?= req_attr('date_of_birth') ?>>
      </div>
      <?php endif; ?>
      <?php if (fc_visible('parent_name')): ?>
      <div class="field">
        <label><?= h(fc_label('parent_name')) ?> <?= req_star('parent_name') ?></label>
        <input type="text" name="parent_name" value="<?= h($d['parent_name']??'') ?>"<?= req_attr('parent_name') ?>>
      </div>
      <?php endif; ?>
      <?php if (fc_visible('gender')): ?>
      <div class="field">
        <label><?= h(fc_label('gender')) ?> <?= req_star('gender') ?></label>
        <div class="radio-row">
          <?php foreach(['Male','Female','Other'] as $g): ?>
          <label><input type="radio" name="gender" value="<?= $g ?>" <?= ($d['gender']??'')===$g?'checked':'' ?> <?= fc_required('gender')?'required':'' ?>><?= $g ?></label>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php if (fc_visible('marital_status')): ?>
      <div class="field">
        <label><?= h(fc_label('marital_status')) ?> <?= req_star('marital_status') ?></label>
        <select name="marital_status"<?= req_attr('marital_status') ?>>
          <option value="">— Select —</option>
          <?php foreach(['Single','Married','Divorced','Widowed'] as $ms): ?>
          <option value="<?= $ms ?>" <?= ($d['marital_status']??'')===$ms?'selected':'' ?>><?= $ms ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php elseif ($currentStep===2): // ── ADDRESS ────────────────── ?>
<div class="card">
  <div class="card-head"><div class="card-head-icon">🏠</div><div><h2>Address Information</h2><p>Present and permanent address</p></div></div>
  <div class="card-body">
    <div class="sub-head">Present Address</div>
    <div class="field-grid">
      <?php if (fc_visible('present_address')): ?>
      <div class="field col-2"><label><?= h(fc_label('present_address')) ?> <?= req_star('present_address') ?></label><textarea name="present_address" rows="2"<?= req_attr('present_address') ?>><?= h($d['present_address']??'') ?></textarea></div>
      <?php endif; ?>
      <?php if (fc_visible('present_state')): ?>
      <div class="field"><label><?= h(fc_label('present_state')) ?> <?= req_star('present_state') ?></label><input type="text" name="present_state" value="<?= h($d['present_state']??'') ?>"<?= req_attr('present_state') ?>></div>
      <?php endif; ?>
      <?php if (fc_visible('present_district')): ?>
      <div class="field"><label><?= h(fc_label('present_district')) ?> <?= req_star('present_district') ?></label><input type="text" name="present_district" value="<?= h($d['present_district']??'') ?>"<?= req_attr('present_district') ?>></div>
      <?php endif; ?>
      <?php if (fc_visible('present_phone')): ?>
      <div class="field"><label><?= h(fc_label('present_phone')) ?> <?= req_star('present_phone') ?></label><input type="tel" name="present_phone" value="<?= h($d['present_phone']??'') ?>"<?= req_attr('present_phone') ?>></div>
      <?php endif; ?>
      <?php if (fc_visible('email')): ?>
      <div class="field"><label><?= h(fc_label('email')) ?> <?= req_star('email') ?></label><input type="email" name="email" value="<?= h($d['email']??'') ?>"<?= req_attr('email') ?>></div>
      <?php endif; ?>
    </div>

    <div style="display:flex;align-items:center;gap:12px;margin:22px 0 6px">
      <div class="sub-head" style="margin:0">Permanent Address</div>
      <label style="display:flex;align-items:center;gap:6px;font-size:.8rem;color:var(--pri);cursor:pointer;font-weight:600;white-space:nowrap">
        <input type="checkbox" id="sameAddr" onchange="copyAddress(this)" style="accent-color:var(--pri)"> Same as present
      </label>
    </div>
    <div class="field-grid">
      <?php if (fc_visible('permanent_address')): ?>
      <div class="field col-2"><label><?= h(fc_label('permanent_address')) ?></label><textarea name="permanent_address" id="perm_address" rows="2"><?= h($d['permanent_address']??'') ?></textarea></div>
      <?php endif; ?>
      <?php if (fc_visible('permanent_state')): ?>
      <div class="field"><label><?= h(fc_label('permanent_state')) ?></label><input type="text" name="permanent_state" id="perm_state" value="<?= h($d['permanent_state']??'') ?>"></div>
      <?php endif; ?>
      <?php if (fc_visible('permanent_district')): ?>
      <div class="field"><label><?= h(fc_label('permanent_district')) ?></label><input type="text" name="permanent_district" id="perm_district" value="<?= h($d['permanent_district']??'') ?>"></div>
      <?php endif; ?>
      <?php if (fc_visible('permanent_phone')): ?>
      <div class="field"><label><?= h(fc_label('permanent_phone')) ?></label><input type="tel" name="permanent_phone" value="<?= h($d['permanent_phone']??'') ?>"></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php elseif ($currentStep===3): // ── JOINING ────────────────── ?>
<div class="card">
  <div class="card-head"><div class="card-head-icon">💼</div><div><h2>Joining Details</h2><p>Appointment and designation information</p></div></div>
  <div class="card-body">
    <div class="field-grid">
      <?php if (fc_visible('date_of_appointment')): ?>
      <div class="field"><label><?= h(fc_label('date_of_appointment')) ?> <?= req_star('date_of_appointment') ?></label><input type="date" name="date_of_appointment" value="<?= h($d['date_of_appointment']??'') ?>"<?= req_attr('date_of_appointment') ?>></div>
      <?php endif; ?>
      <?php if (fc_visible('date_of_joining')): ?>
      <div class="field"><label><?= h(fc_label('date_of_joining')) ?> <?= req_star('date_of_joining') ?></label><input type="date" name="date_of_joining" value="<?= h($d['date_of_joining']??'') ?>"<?= req_attr('date_of_joining') ?>></div>
      <?php endif; ?>
      <?php if (fc_visible('office_at_initial_joining')): ?>
      <div class="field col-2"><label><?= h(fc_label('office_at_initial_joining')) ?> <?= req_star('office_at_initial_joining') ?></label><input type="text" name="office_at_initial_joining" value="<?= h($d['office_at_initial_joining']??'') ?>"<?= req_attr('office_at_initial_joining') ?>></div>
      <?php endif; ?>
      <?php if (fc_visible('initial_designation')): ?>
      <div class="field"><label><?= h(fc_label('initial_designation')) ?> <?= req_star('initial_designation') ?></label><input type="text" name="initial_designation" value="<?= h($d['initial_designation']??'') ?>"<?= req_attr('initial_designation') ?>></div>
      <?php endif; ?>
      <?php if (fc_visible('mode_of_recruitment')): ?>
      <div class="field">
        <label><?= h(fc_label('mode_of_recruitment')) ?> <?= req_star('mode_of_recruitment') ?></label>
        <select name="mode_of_recruitment"<?= req_attr('mode_of_recruitment') ?>>
          <option value="">— Select —</option>
          <?php foreach(['Direct','Promotion','Transfer','Deputation','Contract','Other'] as $m): ?>
          <option value="<?= $m ?>" <?= ($d['mode_of_recruitment']??'')===$m?'selected':'' ?>><?= $m ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php elseif ($currentStep===4): // ── SALARY ─────────────────── ?>
<div class="card">
  <div class="card-head"><div class="card-head-icon">💰</div><div><h2>Salary &amp; Bank Details</h2><p>At time of initial joining</p></div></div>
  <div class="card-body">
    <div class="field-grid">
      <?php if (fc_visible('basic_pay')): ?>
      <div class="field"><label><?= h(fc_label('basic_pay')) ?> <?= req_star('basic_pay') ?></label><input type="number" name="basic_pay" step="0.01" value="<?= h($d['basic_pay']??'') ?>"<?= req_attr('basic_pay') ?>></div>
      <?php endif; ?>
      <?php if (fc_visible('bank_name')): ?>
      <div class="field"><label><?= h(fc_label('bank_name')) ?> <?= req_star('bank_name') ?></label><input type="text" name="bank_name" value="<?= h($d['bank_name']??'') ?>"<?= req_attr('bank_name') ?>></div>
      <?php endif; ?>
      <?php if (fc_visible('ifsc_code')): ?>
      <div class="field"><label><?= h(fc_label('ifsc_code')) ?> <?= req_star('ifsc_code') ?></label><input type="text" name="ifsc_code" value="<?= h($d['ifsc_code']??'') ?>" style="text-transform:uppercase"<?= req_attr('ifsc_code') ?>></div>
      <?php endif; ?>
      <?php if (fc_visible('account_no')): ?>
      <div class="field"><label><?= h(fc_label('account_no')) ?> <?= req_star('account_no') ?></label><input type="text" name="account_no" value="<?= h($d['account_no']??'') ?>"<?= req_attr('account_no') ?>></div>
      <?php endif; ?>
      <?php if (fc_visible('pan_card')): ?>
      <div class="field"><label><?= h(fc_label('pan_card')) ?> <?= req_star('pan_card') ?></label><input type="text" name="pan_card" value="<?= h($d['pan_card']??'') ?>" style="text-transform:uppercase" maxlength="10"<?= req_attr('pan_card') ?>></div>
      <?php endif; ?>
    </div>
    <?php if (fc_visible('commitment_person') || fc_visible('commitment_text')): ?>
    <div class="sub-head" style="margin-top:20px">Commitment (if any)</div>
    <div class="alert alert-info" style="margin-bottom:14px;font-size:.82rem">Any employment commitment made by a company representative must be noted here.</div>
    <div class="field-grid">
      <?php if (fc_visible('commitment_person')): ?>
      <div class="field"><label><?= h(fc_label('commitment_person')) ?></label><input type="text" name="commitment_person" value="<?= h($d['commitment_person']??'') ?>"></div>
      <?php endif; ?>
      <?php if (fc_visible('commitment_text')): ?>
      <div class="field col-2"><label><?= h(fc_label('commitment_text')) ?></label><textarea name="commitment_text" rows="3"><?= h($d['commitment_text']??'') ?></textarea></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($currentStep===5 && ($sectionVisible[5]??true)): // ── EDUCATION ?>
<div class="card">
  <div class="card-head"><div class="card-head-icon">🎓</div><div><h2><?= h(fc_label('section_education')) ?></h2><p>Add qualifications — leave rows blank to skip</p></div></div>
  <div class="card-body">
    <?php
    $eduRows=[];
    if (!empty($d['edu_type'])) foreach($d['edu_type'] as $i=>$t) $eduRows[]=['type'=>$t,'board'=>$d['edu_board'][$i]??'','marks'=>$d['edu_marks'][$i]??'','year'=>$d['edu_year'][$i]??'','stream'=>$d['edu_stream'][$i]??'','grade'=>$d['edu_grade'][$i]??''];
    if(empty($eduRows)) $eduRows=[['type'=>'Basic','board'=>'','marks'=>'','year'=>'','stream'=>'','grade'=>''],['type'=>'Grade','board'=>'','marks'=>'','year'=>'','stream'=>'','grade'=>''],['type'=>'Professional','board'=>'','marks'=>'','year'=>'','stream'=>'','grade'=>'']];
    ?>
    <table class="dyn-table" id="eduTable">
      <thead><tr><th>Type</th><th>Board / University</th><th>Marks %</th><th>Year</th><th>Stream</th><th>Grade</th><th style="width:32px"></th></tr></thead>
      <tbody>
        <?php foreach($eduRows as $r): ?>
        <tr>
          <td><select name="edu_type[]"><option value="Basic" <?= $r['type']==='Basic'?'selected':'' ?>>Basic</option><option value="Grade" <?= $r['type']==='Grade'?'selected':'' ?>>Grade</option><option value="Professional" <?= $r['type']==='Professional'?'selected':'' ?>>Professional</option></select></td>
          <td><input type="text" name="edu_board[]" value="<?= h($r['board']) ?>"></td>
          <td><input type="number" name="edu_marks[]" value="<?= h($r['marks']) ?>" step="0.01" min="0" max="100" style="width:70px"></td>
          <td><input type="number" name="edu_year[]" value="<?= h($r['year']) ?>" min="1960" max="2035" style="width:80px"></td>
          <td><input type="text" name="edu_stream[]" value="<?= h($r['stream']) ?>"></td>
          <td><input type="text" name="edu_grade[]" value="<?= h($r['grade']) ?>" style="width:56px"></td>
          <td><button type="button" class="btn-del-row" onclick="delRow(this,'eduTable')">✕</button></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <button type="button" class="btn-add-row" onclick="addEdu()">+ Add Row</button>
  </div>
</div>

<?php elseif ($currentStep===6 && ($sectionVisible[6]??true)): // ── TRAINING ?>
<div class="card">
  <div class="card-head"><div class="card-head-icon">🏋</div><div><h2><?= h(fc_label('section_training')) ?></h2><p>Leave rows blank to skip</p></div></div>
  <div class="card-body">
    <?php
    $trnRows=[];
    if(!empty($d['trn_location'])) foreach($d['trn_location'] as $i=>$loc) $trnRows[]=['location'=>$loc,'type'=>$d['trn_type'][$i]??'','topic'=>$d['trn_topic'][$i]??'','institute'=>$d['trn_institute'][$i]??'','sponsored'=>$d['trn_sponsored'][$i]??'','from'=>$d['trn_from'][$i]??'','to'=>$d['trn_to'][$i]??''];
    if(empty($trnRows)) $trnRows=[['location'=>'India','type'=>'','topic'=>'','institute'=>'','sponsored'=>'','from'=>'','to'=>'']];
    ?>
    <div style="overflow-x:auto">
    <table class="dyn-table" id="trnTable">
      <thead><tr><th>Location</th><th>Type</th><th>Topic</th><th>Institute</th><th>Sponsored By</th><th>From</th><th>To</th><th style="width:32px"></th></tr></thead>
      <tbody>
        <?php foreach($trnRows as $r): ?>
        <tr>
          <td><select name="trn_location[]" style="width:84px"><option value="India" <?= $r['location']==='India'?'selected':'' ?>>India</option><option value="Abroad" <?= $r['location']==='Abroad'?'selected':'' ?>>Abroad</option></select></td>
          <td><input type="text" name="trn_type[]" value="<?= h($r['type']) ?>"></td>
          <td><input type="text" name="trn_topic[]" value="<?= h($r['topic']) ?>"></td>
          <td><input type="text" name="trn_institute[]" value="<?= h($r['institute']) ?>"></td>
          <td><input type="text" name="trn_sponsored[]" value="<?= h($r['sponsored']) ?>"></td>
          <td><input type="date" name="trn_from[]" value="<?= h($r['from']) ?>"></td>
          <td><input type="date" name="trn_to[]" value="<?= h($r['to']) ?>"></td>
          <td><button type="button" class="btn-del-row" onclick="delRow(this,'trnTable')">✕</button></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <button type="button" class="btn-add-row" onclick="addTrn()">+ Add Training</button>
  </div>
</div>

<?php elseif ($currentStep===7): // ── FAMILY & SERVICE ────────── ?>
<?php if ($sectionVisible[7]??true): ?>
<div class="card">
  <div class="card-head"><div class="card-head-icon">👨‍👩‍👧</div><div><h2>Family &amp; Service History</h2></div></div>
  <div class="card-body">
    <?php if (($fc['section_family']['status']??'optional')!=='hidden'): ?>
    <div class="sub-head">Family Members</div>
    <?php
    $famRows=[];
    if(!empty($d['fam_name'])) foreach($d['fam_name'] as $i=>$n) $famRows[]=['name'=>$n,'relation'=>$d['fam_relation'][$i]??'','dob'=>$d['fam_dob'][$i]??'','dependent'=>$d['fam_dependent'][$i]??'','employment'=>$d['fam_employment'][$i]??''];
    if(empty($famRows)) $famRows=[['name'=>'','relation'=>'','dob'=>'','dependent'=>'','employment'=>'']];
    ?>
    <table class="dyn-table" id="famTable">
      <thead><tr><th>Name</th><th>Relation</th><th>Date of Birth</th><th>Dependent</th><th>Employment</th><th style="width:32px"></th></tr></thead>
      <tbody>
        <?php foreach($famRows as $r): ?>
        <tr>
          <td><input type="text" name="fam_name[]" value="<?= h($r['name']) ?>"></td>
          <td><input type="text" name="fam_relation[]" value="<?= h($r['relation']) ?>"></td>
          <td><input type="date" name="fam_dob[]" value="<?= h($r['dob']) ?>"></td>
          <td><select name="fam_dependent[]" style="width:76px"><option value="">—</option><option value="Yes" <?= $r['dependent']==='Yes'?'selected':'' ?>>Yes</option><option value="No" <?= $r['dependent']==='No'?'selected':'' ?>>No</option></select></td>
          <td><select name="fam_employment[]" style="width:100px"><option value="">—</option><option value="Employed" <?= $r['employment']==='Employed'?'selected':'' ?>>Employed</option><option value="Unemployed" <?= $r['employment']==='Unemployed'?'selected':'' ?>>Unemployed</option></select></td>
          <td><button type="button" class="btn-del-row" onclick="delRow(this,'famTable')">✕</button></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <button type="button" class="btn-add-row" onclick="addFam()">+ Add Member</button>
    <?php endif; ?>

    <?php if (($fc['section_service']['status']??'optional')!=='hidden'): ?>
    <div class="sub-head" style="margin-top:24px">Previous Service History</div>
    <?php
    $svcRows=[];
    if(!empty($d['svc_org'])) foreach($d['svc_org'] as $i=>$o) $svcRows[]=['org'=>$o,'desig'=>$d['svc_desig'][$i]??'','salary'=>$d['svc_salary'][$i]??'','from'=>$d['svc_from'][$i]??'','to'=>$d['svc_to'][$i]??''];
    if(empty($svcRows)) $svcRows=[['org'=>'','desig'=>'','salary'=>'','from'=>'','to'=>'']];
    ?>
    <table class="dyn-table" id="svcTable">
      <thead><tr><th>Organisation</th><th>Designation</th><th>Salary (Rs.)</th><th>From</th><th>To</th><th style="width:32px"></th></tr></thead>
      <tbody>
        <?php foreach($svcRows as $r): ?>
        <tr>
          <td><input type="text" name="svc_org[]" value="<?= h($r['org']) ?>"></td>
          <td><input type="text" name="svc_desig[]" value="<?= h($r['desig']) ?>"></td>
          <td><input type="number" name="svc_salary[]" value="<?= h($r['salary']) ?>" step="0.01" style="width:96px"></td>
          <td><input type="date" name="svc_from[]" value="<?= h($r['from']) ?>"></td>
          <td><input type="date" name="svc_to[]" value="<?= h($r['to']) ?>"></td>
          <td><button type="button" class="btn-del-row" onclick="delRow(this,'svcTable')">✕</button></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <button type="button" class="btn-add-row" onclick="addSvc()">+ Add Employment</button>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php elseif ($currentStep===8): // ── NOMINATION ─────────────── ?>
<div class="card">
  <div class="card-head"><div class="card-head-icon">📋</div><div><h2>Nomination, References &amp; Emergency</h2></div></div>
  <div class="card-body">
    <div class="sub-head">Nominee Details</div>
    <div class="field-grid">
      <?php if (fc_visible('nominee_name')): ?><div class="field"><label><?= h(fc_label('nominee_name')) ?> <?= req_star('nominee_name') ?></label><input type="text" name="nominee_name" value="<?= h($d['nominee_name']??'') ?>"<?= req_attr('nominee_name') ?>></div><?php endif; ?>
      <?php if (fc_visible('nominee_relation')): ?><div class="field"><label><?= h(fc_label('nominee_relation')) ?></label><input type="text" name="nominee_relation" value="<?= h($d['nominee_relation']??'') ?>"></div><?php endif; ?>
      <?php if (fc_visible('nominee_age')): ?><div class="field"><label><?= h(fc_label('nominee_age')) ?></label><input type="number" name="nominee_age" value="<?= h($d['nominee_age']??'') ?>" style="width:90px"></div><?php endif; ?>
      <?php if (fc_visible('nominee_address')): ?><div class="field col-2"><label><?= h(fc_label('nominee_address')) ?></label><textarea name="nominee_address" rows="2"><?= h($d['nominee_address']??'') ?></textarea></div><?php endif; ?>
      <?php if (fc_visible('nominee_state')): ?><div class="field"><label><?= h(fc_label('nominee_state')) ?></label><input type="text" name="nominee_state" value="<?= h($d['nominee_state']??'') ?>"></div><?php endif; ?>
      <?php if (fc_visible('nominee_block')): ?><div class="field"><label><?= h(fc_label('nominee_block')) ?></label><input type="text" name="nominee_block" value="<?= h($d['nominee_block']??'') ?>"></div><?php endif; ?>
      <?php if (fc_visible('nominee_district')): ?><div class="field"><label><?= h(fc_label('nominee_district')) ?></label><input type="text" name="nominee_district" value="<?= h($d['nominee_district']??'') ?>"></div><?php endif; ?>
    </div>
    <div class="sub-head">References</div>
    <?php if (fc_visible('reference_1')): ?><div class="field" style="margin-bottom:12px"><label><?= h(fc_label('reference_1')) ?></label><textarea name="reference_1" rows="2"><?= h($d['reference_1']??'') ?></textarea></div><?php endif; ?>
    <?php if (fc_visible('reference_2')): ?><div class="field"><label><?= h(fc_label('reference_2')) ?></label><textarea name="reference_2" rows="2"><?= h($d['reference_2']??'') ?></textarea></div><?php endif; ?>
    <div class="sub-head">Emergency Contact</div>
    <div class="field-grid">
      <?php if (fc_visible('emergency_contact_name')): ?><div class="field"><label><?= h(fc_label('emergency_contact_name')) ?></label><input type="text" name="emergency_contact_name" value="<?= h($d['emergency_contact_name']??'') ?>"></div><?php endif; ?>
      <?php if (fc_visible('emergency_phone')): ?><div class="field"><label><?= h(fc_label('emergency_phone')) ?></label><input type="tel" name="emergency_phone" value="<?= h($d['emergency_phone']??'') ?>"></div><?php endif; ?>
      <?php if (fc_visible('emergency_address')): ?><div class="field col-2"><label><?= h(fc_label('emergency_address')) ?></label><textarea name="emergency_address" rows="2"><?= h($d['emergency_address']??'') ?></textarea></div><?php endif; ?>
    </div>
  </div>
</div>

<?php elseif ($currentStep===9): // ── DOCUMENTS & DECLARATION ── ?>
<div class="card">
  <div class="card-head"><div class="card-head-icon">📎</div><div><h2>Documents &amp; Declaration</h2><p>Upload supporting files · PDF, JPG, PNG · Max 10MB each</p></div></div>
  <div class="card-body">
    <div class="alert alert-info" style="margin-bottom:18px;font-size:.82rem">Upload clear scans or photos of each document. Accepted: JPG, PNG, PDF. All originals must be self-attested.</div>
    <?php
    $docFields=[
        'docs_upload_certificates'=>['key'=>'docs_certificates','label'=>fc_label('docs_certificates'),'icon'=>'📜'],
        'docs_upload_dob'=>['key'=>'docs_dob','label'=>fc_label('docs_dob'),'icon'=>'🪪'],
        'docs_upload_experience'=>['key'=>'docs_experience','label'=>fc_label('docs_experience'),'icon'=>'📄'],
        'docs_upload_relieving'=>['key'=>'docs_relieving','label'=>fc_label('docs_relieving'),'icon'=>'📋'],
        'docs_upload_other'=>['key'=>null,'label'=>'Other Document (optional)','icon'=>'📁'],
    ];
    foreach($docFields as $field=>$info):
        if ($info['key'] && !fc_visible($info['key'])) continue;
    ?>
    <div style="border:1px solid var(--n-150);border-radius:var(--r-md);padding:13px 15px;margin-bottom:10px;background:var(--n-25);display:grid;grid-template-columns:auto 1fr <?= $info['key']?'auto':'' ?>;gap:12px;align-items:center">
      <div style="font-size:1.3rem"><?= $info['icon'] ?></div>
      <div>
        <div style="font-size:.83rem;font-weight:600;color:var(--n-800);margin-bottom:5px"><?= h($info['label']) ?></div>
        <input type="file" name="<?= $field ?>[]" accept=".jpg,.jpeg,.png,.pdf" multiple style="font-size:.78rem;color:var(--n-600)">
      </div>
      <?php if ($info['key']): ?>
      <div>
        <div style="font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--n-500);margin-bottom:5px">Status</div>
        <select name="<?= $info['key'] ?>" style="width:140px;border:1.5px solid var(--n-200);border-radius:var(--r-sm);padding:6px 8px;font-size:.8rem;font-family:var(--font-body)">
          <option value="">— Select —</option>
          <option value="Submitted" <?= ($d[$info['key']]??'')==='Submitted'?'selected':'' ?>>✅ Submitting now</option>
          <option value="Will Submit" <?= ($d[$info['key']]??'')==='Will Submit'?'selected':'' ?>>🕐 Will submit later</option>
        </select>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="sub-head" style="margin-top:22px">Declaration</div>
    <div style="background:var(--n-50);border:1px solid var(--n-200);border-radius:var(--r);padding:16px;font-size:.84rem;line-height:1.9;color:var(--n-700);margin-bottom:14px">
      I declare that the information given herein is true and correct to the best of my knowledge and belief and nothing material has been concealed. I understand that if any information is found false or incorrect at any time during my employment, my services may be terminated without notice or compensation.
    </div>
    <label style="display:flex;align-items:flex-start;gap:9px;cursor:pointer;font-size:.87rem;color:var(--n-700)">
      <input type="checkbox" name="declaration_agreed" value="1" required style="margin-top:3px;accent-color:var(--pri)">
      I agree to the above declaration and confirm all information is true and accurate.
    </label>
  </div>
</div>
<?php endif; ?>

<!-- Navigation -->
<div class="form-nav">
  <div>
    <?php if ($currentStep > 1): ?>
    <button type="submit" name="action" value="back" class="btn btn-sec">← Back</button>
    <?php endif; ?>
  </div>
  <div class="form-nav-right">
    <span class="step-counter"><?= $currentStep ?> / <?= $totalSteps ?></span>
    <?php if ($currentStep < $totalSteps): ?>
    <button type="submit" name="action" value="next" class="btn btn-pri">Continue →</button>
    <?php else: ?>
    <button type="submit" name="action" value="submit" class="btn btn-success">✓ Submit Form</button>
    <?php endif; ?>
  </div>
</div>

</form>
</div><!-- /form-pane -->
</div><!-- /form-body -->
</div><!-- /form-shell -->

<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const r = new FileReader();
        r.onload = e => {
            document.getElementById('photoPreview').src = e.target.result;
            document.getElementById('photoPreview').style.display = 'block';
            document.getElementById('photoPlaceholder').style.display = 'none';
        };
        r.readAsDataURL(input.files[0]);
    }
}
function copyAddress(cb) {
    if (!cb.checked) return;
    document.getElementById('perm_address').value  = document.querySelector('[name="present_address"]')?.value || '';
    document.getElementById('perm_state')?.setAttribute('value', document.querySelector('[name="present_state"]')?.value || '');
    if(document.getElementById('perm_state')) document.getElementById('perm_state').value = document.querySelector('[name="present_state"]')?.value||'';
    if(document.getElementById('perm_district')) document.getElementById('perm_district').value = document.querySelector('[name="present_district"]')?.value||'';
}
function delRow(btn, tableId) {
    const tbody = document.querySelector('#'+tableId+' tbody');
    if (tbody.rows.length > 1) btn.closest('tr').remove();
}
function addEdu() {
    document.querySelector('#eduTable tbody').insertAdjacentHTML('beforeend', `<tr><td><select name="edu_type[]"><option>Basic</option><option>Grade</option><option>Professional</option></select></td><td><input type="text" name="edu_board[]"></td><td><input type="number" name="edu_marks[]" step="0.01" min="0" max="100" style="width:70px"></td><td><input type="number" name="edu_year[]" min="1960" max="2035" style="width:80px"></td><td><input type="text" name="edu_stream[]"></td><td><input type="text" name="edu_grade[]" style="width:56px"></td><td><button type="button" class="btn-del-row" onclick="delRow(this,'eduTable')">✕</button></td></tr>`);
}
function addTrn() {
    document.querySelector('#trnTable tbody').insertAdjacentHTML('beforeend', `<tr><td><select name="trn_location[]" style="width:84px"><option>India</option><option>Abroad</option></select></td><td><input type="text" name="trn_type[]"></td><td><input type="text" name="trn_topic[]"></td><td><input type="text" name="trn_institute[]"></td><td><input type="text" name="trn_sponsored[]"></td><td><input type="date" name="trn_from[]"></td><td><input type="date" name="trn_to[]"></td><td><button type="button" class="btn-del-row" onclick="delRow(this,'trnTable')">✕</button></td></tr>`);
}
function addFam() {
    document.querySelector('#famTable tbody').insertAdjacentHTML('beforeend', `<tr><td><input type="text" name="fam_name[]"></td><td><input type="text" name="fam_relation[]"></td><td><input type="date" name="fam_dob[]"></td><td><select name="fam_dependent[]" style="width:76px"><option value="">—</option><option>Yes</option><option>No</option></select></td><td><select name="fam_employment[]" style="width:100px"><option value="">—</option><option>Employed</option><option>Unemployed</option></select></td><td><button type="button" class="btn-del-row" onclick="delRow(this,'famTable')">✕</button></td></tr>`);
}
function addSvc() {
    document.querySelector('#svcTable tbody').insertAdjacentHTML('beforeend', `<tr><td><input type="text" name="svc_org[]"></td><td><input type="text" name="svc_desig[]"></td><td><input type="number" name="svc_salary[]" step="0.01" style="width:96px"></td><td><input type="date" name="svc_from[]"></td><td><input type="date" name="svc_to[]"></td><td><button type="button" class="btn-del-row" onclick="delRow(this,'svcTable')">✕</button></td></tr>`);
}
</script>
</body>
</html>
