<?php
// admin/view.php
session_start();
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: users/login.php');
    exit;
}
if (!isset($_GET['id']) || !($id = (int)$_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) die("DB error");
$stmt = $mysqli->prepare("SELECT * FROM contacts WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();
$mysqli->close();
if (!$row) {
    echo "Not found";
    exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"><title>View Request</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>.card{max-width:900px;margin:30px auto;padding:18px;border-radius:8px}</style>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h4>Request #<?php echo (int)$row['id']; ?></h4>
        <div><a class="btn btn-sm btn-outline-primary" href="dashboard.php">Back to list</a></div>
      </div>
      <p class="text-muted">Submitted: <?php echo htmlspecialchars($row['created_at']); ?></p>
      <dl class="row">
        <dt class="col-sm-3">Name</dt><dd class="col-sm-9"><?php echo htmlspecialchars($row['name']); ?></dd>
        <dt class="col-sm-3">Company</dt><dd class="col-sm-9"><?php echo htmlspecialchars($row['company']); ?></dd>
        <dt class="col-sm-3">Email</dt><dd class="col-sm-9"><a href="mailto:<?php echo htmlspecialchars($row['email']); ?>"><?php echo htmlspecialchars($row['email']); ?></a></dd>
        <dt class="col-sm-3">Phone</dt><dd class="col-sm-9"><?php echo htmlspecialchars($row['phone']); ?></dd>
        <dt class="col-sm-3">Service</dt><dd class="col-sm-9"><?php echo htmlspecialchars($row['service']); ?></dd>
        <dt class="col-sm-3">Brief</dt><dd class="col-sm-9" style="white-space:pre-wrap"><?php echo htmlspecialchars($row['brief']); ?></dd>
        <dt class="col-sm-3">Attachment</dt>
        <dd class="col-sm-9">
          <?php if (!empty($row['attachment'])): ?>
            <a href="../uploads/<?php echo rawurlencode($row['attachment']); ?>" download>Download attachment</a>
          <?php else: ?>
            None
          <?php endif; ?>
        </dd>
      </dl>
    </div>
  </div>
</body>
</html>
