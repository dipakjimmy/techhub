<?php
// admin/ajax_add_note.php
session_start();
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403); echo json_encode(['ok'=>false,'err'=>'not_logged_in']); exit;
}
$lead_id = isset($_POST['lead_id']) ? (int)$_POST['lead_id'] : 0;
$note = isset($_POST['note']) ? trim($_POST['note']) : '';
if (!$lead_id || $note === '') { echo json_encode(['ok'=>false,'err'=>'missing']); exit; }

$author = $_SESSION['admin_user'] ?? 'admin';
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) { echo json_encode(['ok'=>false,'err'=>'db']); exit; }

$stmt = $mysqli->prepare("INSERT INTO lead_notes (lead_id, author, note) VALUES (?, ?, ?)");
$stmt->bind_param('iss', $lead_id, $author, $note);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    // Optionally update last_contacted
    $u = $mysqli->prepare("UPDATE contacts SET last_contacted = NOW() WHERE id = ?");
    $u->bind_param('i', $lead_id); $u->execute(); $u->close();

    echo json_encode(['ok'=>true]);
} else {
    echo json_encode(['ok'=>false,'err'=>'insert_failed']);
}
$mysqli->close();
