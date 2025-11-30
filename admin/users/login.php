<?php
// admin/users/login.php
session_start();
require_once __DIR__ . '/../../config.php';

// If already logged in, redirect
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: ../dashboard.php');
    exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = isset($_POST['username']) ? trim($_POST['username']) : '';
    $pass = isset($_POST['password']) ? $_POST['password'] : '';

    if ($user === ADMIN_USER && password_verify($pass, ADMIN_PASS_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = ADMIN_USER;
        header('Location: ../dashboard.php');
        exit;
    } else {
        $err = 'Invalid username or password.';
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Login — ASISA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>.login-box{max-width:420px;margin:60px auto;padding:24px;background:#fff;border-radius:8px;box-shadow:0 8px 22px rgba(12,34,56,0.06)}</style>
</head>
<body style="background:#f5f7fb">
  <div class="login-box">
    <h4 class="mb-3">Admin sign in</h4>
    <?php if ($err): ?><div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
    <form method="post" action="">
      <div class="mb-2"><input name="username" class="form-control" placeholder="Username" required autofocus></div>
      <div class="mb-3"><input name="password" type="password" class="form-control" placeholder="Password" required></div>
      <div><button class="btn btn-dark w-100" type="submit">Sign in</button></div>
    </form>
    <p class="mt-3 text-muted small">Login to view contact requests.</p>
  </div>
</body>
</html>
