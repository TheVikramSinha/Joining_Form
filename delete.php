<?php
require_once 'config.php';
require_admin();
session_start_safe();

$id   = (int)($_GET['id'] ?? 0);
$csrf = $_GET['csrf'] ?? '';

if ($csrf !== md5(session_id().$id) || $id <= 0) die('Invalid request.');

$pdo = db();

// Delete photo
$row = $pdo->prepare("SELECT photo_path FROM candidates WHERE id=?"); $row->execute([$id]); $rec=$row->fetch();
if ($rec && $rec['photo_path'] && file_exists(UPLOAD_DIR.$rec['photo_path'])) @unlink(UPLOAD_DIR.$rec['photo_path']);

// Delete associated documents
$docs = $pdo->prepare("SELECT file_path FROM candidate_documents WHERE candidate_id=?"); $docs->execute([$id]);
foreach ($docs->fetchAll() as $d) {
    $path = DOCS_DIR.$d['file_path'];
    if (file_exists($path)) @unlink($path);
}

$pdo->prepare("DELETE FROM candidates WHERE id=?")->execute([$id]);
redirect('report.php');
