<?php
// admin/view_lead.php
session_start();
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: users/login.php'); exit;
}
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: leads.php'); exit; }

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) die("DB connect failed");

// load lead
$stmt = $mysqli->prepare("SELECT * FROM contacts WHERE id = ? LIMIT 1");
$stmt->bind_param('i',$id);
$stmt->execute();
$lead = $stmt->get_result()->fetch_assoc();
$stmt->close();

// load notes
$notesStmt = $mysqli->prepare("SELECT * FROM lead_notes WHERE lead_id = ? ORDER BY created_at DESC");
$notesStmt->bind_param('i',$id);
$notesStmt->execute();
$notesRes = $notesStmt->get_result();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8"><title>Lead #<?php echo $id;?> — ASISA CRM</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
  <a class="btn btn-sm btn-secondary mb-3" href="leads.php">← Back to leads</a>
  <div class="row">
    <div class="col-md-8">
      <div class="card mb-3 p-3">
        <h5><?php echo htmlspecialchars($lead['name']); ?> <small class="text-muted">#<?php echo $lead['id']; ?></small></h5>
        <p><a href="mailto:<?php echo htmlspecialchars($lead['email']); ?>"><?php echo htmlspecialchars($lead['email']); ?></a> | <?php echo htmlspecialchars($lead['phone']); ?></p>
        <p><strong>Service:</strong> <?php echo htmlspecialchars($lead['service']); ?></p>
        <p><strong>Brief:</strong><br><pre style="white-space:pre-wrap"><?php echo htmlspecialchars($lead['brief']); ?></pre></p>
        <p><strong>Tags:</strong> <?php echo htmlspecialchars($lead['tags']); ?></p>
        <p><strong>Priority:</strong> <?php echo htmlspecialchars($lead['priority']); ?></p>
        <p><strong>Source:</strong> <?php echo htmlspecialchars($lead['source']); ?></p>
      </div>

      <div class="card p-3 mb-3">
        <h6>Activity / Notes</h6>
        <form id="noteForm" method="post" action="ajax_add_note.php">
          <input type="hidden" name="lead_id" value="<?php echo $lead['id']; ?>">
          <div class="mb-2">
            <textarea name="note" class="form-control" rows="3" placeholder="Add a note or activity (call summary, next steps)"></textarea>
          </div>
          <div class="mb-2">
            <button class="btn btn-primary">Add note</button>
          </div>
        </form>

        <ul class="list-group">
          <?php while($n = $notesRes->fetch_assoc()): ?>
            <li class="list-group-item">
              <div class="small text-muted"><?php echo htmlspecialchars($n['created_at']); ?> by <?php echo htmlspecialchars($n['author']); ?></div>
              <div><?php echo nl2br(htmlspecialchars($n['note'])); ?></div>
            </li>
          <?php endwhile; ?>
        </ul>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card p-3 mb-3">
        <h6>Manage Lead</h6>
        <form id="manageForm" method="post" action="ajax_update_lead.php">
          <input type="hidden" name="id" value="<?php echo $lead['id']; ?>">
          <div class="mb-2">
            <label>Status</label>
            <select name="status" class="form-control">
              <?php foreach (['New','Contacted','Qualified','Proposal Sent','Won','Lost'] as $st): ?>
                <option value="<?php echo $st;?>" <?php if($lead['status']===$st) echo 'selected';?>><?php echo $st;?></option>
              <?php endforeach;?>
            </select>
          </div>
          <div class="mb-2">
            <label>Assignee</label>
            <input name="assignee" class="form-control" value="<?php echo htmlspecialchars($lead['assignee']); ?>">
          </div>
          <div class="mb-2">
            <label>Next follow-up</label>
            <input type="datetime-local" name="next_followup" class="form-control" value="<?php echo $lead['next_followup']?date('Y-m-d\TH:i', strtotime($lead['next_followup'])):''; ?>">
          </div>
          <div class="mb-2">
            <button class="btn btn-primary">Save</button>
          </div>
        </form>
      </div>

      <div class="card p-3">
        <h6>Quick actions</h6>
        <a class="btn btn-outline-success w-100 mb-2" href="mailto:<?php echo htmlspecialchars($lead['email']); ?>">Email lead</a>
        <?php if (!empty($lead['attachment'])): ?>
          <a class="btn btn-outline-primary w-100" href="../uploads/<?php echo rawurlencode($lead['attachment']); ?>" download>Download attachment</a>
        <?php endif;?>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('noteForm').addEventListener('submit', function(e){
  e.preventDefault();
  const fd = new FormData(this);
  fetch('ajax_add_note.php', {method:'POST', body: fd})
    .then(r=>r.json()).then(j=>{
      if(j.ok) location.reload();
      else alert('Error: '+(j.err||'unknown'));
    });
});
document.getElementById('manageForm').addEventListener('submit', function(e){
  e.preventDefault();
  const fd = new FormData(this);
  const data = { id: fd.get('id'), action:'manage', status:fd.get('status'), assignee: fd.get('assignee'), next_followup: fd.get('next_followup') };
  fetch('ajax_update_lead.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data)})
    .then(r=>r.json()).then(j=>{
      if(j.ok) location.reload();
      else alert('Error: '+(j.err||'unknown'));
    });
});
</script>
</body>
</html>
<?php
$notesStmt->close();
$mysqli->close();
