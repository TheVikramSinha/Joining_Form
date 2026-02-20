<?php
// serve_doc.php — Securely serves uploaded candidate documents (admin only)
require_once 'config.php';
require_admin();

$docId    = (int)($_GET['id'] ?? 0);
$download = isset($_GET['dl']) && $_GET['dl'] === '1';

if (!$docId) { http_response_code(400); die('Bad request'); }

$stmt = db()->prepare("SELECT * FROM candidate_documents WHERE id = ?");
$stmt->execute([$docId]);
$doc = $stmt->fetch();

if (!$doc) { http_response_code(404); die('Document not found'); }

$filePath = DOCS_DIR . $doc['file_path'];

if (!file_exists($filePath)) { http_response_code(404); die('File not found on server'); }

// Determine MIME type
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$mimes = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
];
$mime = $mimes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filePath));

if ($download) {
    header('Content-Disposition: attachment; filename="' . addslashes($doc['file_name']) . '"');
} else {
    header('Content-Disposition: inline; filename="' . addslashes($doc['file_name']) . '"');
}

// Prevent caching sensitive docs
header('Cache-Control: private, no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

readfile($filePath);
exit;
