<?php
require "config.php";

// simple helper to sanitize input
function clean($v) {
    return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}

$name    = isset($_POST['name']) ? clean($_POST['name']) : '';
$email   = isset($_POST['email']) ? clean($_POST['email']) : '';
$phone   = isset($_POST['phone']) ? clean($_POST['phone']) : '';
$service = isset($_POST['service']) ? clean($_POST['service']) : '';
$message = isset($_POST['message']) ? clean($_POST['message']) : '';

// Prepared statement insertion
$stmt = $conn->prepare('INSERT INTO contact_messages (name, email, phone, service, message) VALUES (?, ?, ?, ?, ?)');
if (!$stmt) {
    error_log('Prepare failed: ' . $conn->error);
    http_response_code(500);
    echo "Server error";
    exit;
}
$stmt->bind_param('sssss', $name, $email, $phone, $service, $message);
$ok = $stmt->execute();

if ($ok) {
    // send notification email (simple)
    $to = 'dipak.zagade8@gmail.com';
    $subject = 'New website contact: ' . ($service ?: 'General');
    $body = "Name: $name\nEmail: $email\nPhone: $phone\nService: $service\n\nMessage:\n$message\n";
    $headers = 'From: noreply@yourdomain.com' . "\r\n" .
               'Reply-To: ' . $email . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    @mail($to, $subject, $body, $headers);

    echo json_encode(['status' => 'ok']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'error' => $stmt->error]);
}
$stmt->close();
$conn->close();
?>
