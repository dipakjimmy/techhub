<?php
// admin/ajax_update_lead.php
session_start();
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403); echo json_encode(['ok'=>false,'err'=>'not_logged_in']); exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) $data = $_POST; // fallback to form posts

if (empty($data['id'])) { echo json_encode(['ok'=>false,'err'=>'missing_id']); exit; }
$id = (int)$data['id'];

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) { echo json_encode(['ok'=>false,'err'=>'db_connect']); exit; }

$allowedActions = ['status','assignee','manage','next_followup','priority','tags'];
$action = $data['action'] ?? '';

if (!in_array($action, $allowedActions, true)) {
    echo json_encode(['ok'=>false,'err'=>'invalid_action']); exit;
}

try {
    if ($action === 'status') {
        $status = $mysqli->real_escape_string(trim($data['status'] ?? ''));
        $stmt = $mysqli->prepare("UPDATE contacts SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'assignee') {
        $assignee = $mysqli->real_escape_string(trim($data['assignee'] ?? ''));
        $stmt = $mysqli->prepare("UPDATE contacts SET assignee = ? WHERE id = ?");
        $stmt->bind_param('si', $assignee, $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'next_followup') {
        $next_followup = !empty($data['next_followup']) ? trim($data['next_followup']) : null;
        // Convert datetime-local format to MySQL datetime format if provided
        if ($next_followup) {
            // Format: YYYY-MM-DDTHH:MM -> YYYY-MM-DD HH:MM:SS
            $next_followup = str_replace('T', ' ', $next_followup) . ':00';
        }
        // Use NULL if empty string
        if ($next_followup === '') {
            $next_followup = null;
        }
        $stmt = $mysqli->prepare("UPDATE contacts SET next_followup = ? WHERE id = ?");
        $stmt->bind_param('si', $next_followup, $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'manage') {
        $status = $data['status'] ?? null;
        $assignee = $data['assignee'] ?? null;
        $next = $data['next_followup'] ?? null;
        if ($next) {
            $next = str_replace('T', ' ', $next) . ':00';
        }
        $stmt = $mysqli->prepare("UPDATE contacts SET status = ?, assignee = ?, next_followup = ? WHERE id = ?");
        $stmt->bind_param('sssi', $status, $assignee, $next, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        echo json_encode(['ok'=>false,'err'=>'not_implemented']); exit;
    }
    echo json_encode(['ok'=>true]);
} catch (Exception $e) {
    echo json_encode(['ok'=>false,'err'=>$e->getMessage()]);
}
$mysqli->close();
