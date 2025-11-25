<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit;
}
require "../config.php";

// Try to fetch messages; if DB not configured yet, show guidance
$messages = [];
$db_ok = true;
$res = @$conn->query('SELECT * FROM contact_messages ORDER BY id DESC LIMIT 200');
if ($res) {
    while ($r = $res->fetch_assoc()) $messages[] = $r;
} else {
    $db_ok = false;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin Dashboard</title>
<style>
body{ margin:0; font-family:Poppins, sans-serif; background:#0f172a; color:#fff; }
.wrap{ display:flex; min-height:100vh; }
.sidebar{ width:220px; background:#1e293b; padding:20px; box-sizing:border-box; }
.main{ flex:1; padding:24px; }
.top{ background:#1e293b; padding:12px 16px; border-radius:8px; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; }
table{ width:100%; border-collapse:collapse; background:#0b1220; border-radius:8px; overflow:hidden; }
th, td{ padding:12px; border-bottom:1px solid #253342; text-align:left; vertical-align:top; }
th{ background:#16202a; color:#cfe7ff; }
.note{ background:#11202c; padding:14px; border-radius:8px; color:#bcd6ff; }
a.logout{ color:#ff7a1a; text-decoration:none; font-weight:600; }
</style>
</head>
<body>
<div class="wrap">
    <div class="sidebar">
        <h3 style="margin-top:0">Admin</h3>
        <p><a href="login.php" class="logout">Login Page</a></p>
        <p><a href="logout.php" class="logout">Logout</a></p>
    </div>
    <div class="main">
        <div class="top">
            <div>Welcome, Admin</div>
            <div><a href="logout.php" class="logout">Sign out</a></div>
        </div>

        <?php if (!$db_ok): ?>
            <div class="note">
                <strong>Database not configured yet.</strong><br>
                Please open backend/config.php and add your DB_HOST, DB_USER, DB_PASS and DB_NAME. 
                Also paste the password hash into $ADMIN_PASS_HASH (see README.md for instructions).
            </div>
        <?php else: ?>
            <h2>Contact Messages</h2>
            <table>
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Service</th><th>Message</th><th>Date</th></tr>
                <?php foreach($messages as $m): ?>
                <tr>
                    <td><?php echo htmlspecialchars($m['id']); ?></td>
                    <td><?php echo htmlspecialchars($m['name']); ?></td>
                    <td><?php echo htmlspecialchars($m['email']); ?></td>
                    <td><?php echo htmlspecialchars($m['service']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($m['message'])); ?></td>
                    <td><?php echo htmlspecialchars($m['created_at']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
