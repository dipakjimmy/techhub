<?php
session_start();
require "../config.php";

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = isset($_POST['username']) ? $_POST['username'] : '';
    $pass = isset($_POST['password']) ? $_POST['password'] : '';

    if ($user === $ADMIN_USER && password_verify($pass, $ADMIN_PASS_HASH)) {
        $_SESSION['admin'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin Login</title>
<style>
body { background:#0f172a; color:#fff; font-family: Poppins, sans-serif; display:flex; align-items:center; justify-content:center; height:100vh; margin:0; }
.box{ background:#1e293b; padding:32px; border-radius:10px; width:360px; box-shadow:0 10px 30px rgba(0,0,0,0.5); }
input{ width:100%; padding:10px; margin:8px 0; border-radius:6px; border:none; }
.btn{ background:#ff7a1a; color:#fff; border:none; padding:10px 14px; width:100%; border-radius:6px; cursor:pointer; font-weight:600; }
.error{ color:#ffb4a1; margin-bottom:10px; }
</style>
</head>
<body>
<div class="box">
    <h2 style="margin:0 0 12px">Admin Login</h2>
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post">
        <input name="username" placeholder="Username" required>
        <input name="password" type="password" placeholder="Password" required>
        <button class="btn" type="submit">Login</button>
    </form>
</div>
</body>
</html>
