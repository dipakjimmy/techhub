<?php
// admin/export_csv.php
session_start();
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('HTTP/1.1 403 Forbidden'); exit;
}

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) die("DB conn failed");

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$from = isset($_GET['from']) ? trim($_GET['from']) : '';
$to = isset($_GET['to']) ? trim($_GET['to']) : '';

$where = [];
$types = '';
$params = [];

if ($q !== '') {
    $where[] = "(name LIKE CONCAT('%',?,'%') OR email LIKE CONCAT('%',?,'%') OR service LIKE CONCAT('%',?,'%') OR brief LIKE CONCAT('%',?,'%'))";
    $types .= 'ssss';
    array_push($params, $q, $q, $q, $q);
}
if ($from !== '') { $where[] = "created_at >= ?"; $types .= 's'; $params[] = $from.' 00:00:00'; }
if ($to !== '') { $where[] = "created_at <= ?"; $types .= 's'; $params[] = $to.' 23:59:59'; }
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT id,name,company,email,phone,service,brief,attachment,created_at FROM contacts $where_sql ORDER BY created_at DESC";
$stmt = $mysqli->prepare($sql);
if ($where) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$filename = 'contacts_export_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');

$out = fopen('php://output', 'w');
fputcsv($out, ['id','created_at','name','company','email','phone','service','brief','attachment']);
while ($row = $result->fetch_assoc()) {
    fputcsv($out, [
        $row['id'],
        $row['created_at'],
        $row['name'],
        $row['company'],
        $row['email'],
        $row['phone'],
        $row['service'],
        $row['brief'],
        $row['attachment']
    ]);
}
fclose($out);
$stmt->close();
$mysqli->close();
exit;
