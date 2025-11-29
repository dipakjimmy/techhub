<?php
// upload.php — simple image upload endpoint for CKEditor 5
// Place this file in the same folder as save_post.php (e.g. /techpub/blog/upload.php)

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Allow requests from same origin (local dev)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // preflight
    http_response_code(204);
    exit;
}

// configuration
$maxFileSize = 5 * 1024 * 1024; // 5 MB
$allowedMime = [
    'image/jpeg' => '.jpg',
    'image/png'  => '.png',
    'image/gif'  => '.gif',
    'image/webp' => '.webp'
];

// storage folder (relative to this file)
$uploadsDir = __DIR__ . '/uploads/images';

// ensure folder exists
if (!is_dir($uploadsDir)) {
    if (!mkdir($uploadsDir, 0777, true)) {
        http_response_code(500);
        echo json_encode(['error' => ['message' => 'Failed to create upload directory']]);
        exit;
    }
}

// CKEditor sends the file under field name "upload"
if (empty($_FILES['upload']) || $_FILES['upload']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => ['message' => 'No file uploaded or upload error']]);
    exit;
}

$file = $_FILES['upload'];
// validate size
if ($file['size'] > $maxFileSize) {
    http_response_code(400);
    echo json_encode(['error' => ['message' => 'File too large. Max 5 MB']]);
    exit;
}

// validate mime type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!array_key_exists($mime, $allowedMime)) {
    http_response_code(400);
    echo json_encode(['error' => ['message' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed']]);
    exit;
}

// sanitize and create unique filename
$ext = $allowedMime[$mime];
$basename = bin2hex(random_bytes(8)) . '-' . time();
$filename = $basename . $ext;
$dest = $uploadsDir . '/' . $filename;

// move uploaded file
if (!move_uploaded_file($file['tmp_name'], $dest)) {
    http_response_code(500);
    echo json_encode(['error' => ['message' => 'Failed to save uploaded file']]);
    exit;
}

// Optional: you can create smaller thumbnails here or run additional checks

// Build public URL for returned JSON (adjust base path if your site is in subfolder)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST']; // e.g. localhost
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // e.g. /techpub/blog
// public path to file:
$publicUrl = $protocol . '://' . $host . $basePath . '/uploads/images/' . $filename;

// CKEditor expects JSON with "url" inside an object on success
echo json_encode(['url' => $publicUrl]);
exit;
