<?php
// admin/leads.php - fixed / resilient version
session_start();
require_once __DIR__ . '/../config.php';

// require login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: users/login.php'); exit;
}

// connect
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo "DB connection failed: " . htmlspecialchars($mysqli->connect_error);
    exit;
}

// Input / filters
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$assignee = isset($_GET['assignee']) ? trim($_GET['assignee']) : '';
$from = isset($_GET['from']) ? trim($_GET['from']) : '';
$to = isset($_GET['to']) ? trim($_GET['to']) : '';
$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page-1)*$perPage;

// Build WHERE and params safely
$whereParts = [];
$types = '';
$params = [];

if ($q !== '') {
    $whereParts[] = "(name LIKE CONCAT('%',?,'%') OR email LIKE CONCAT('%',?,'%') OR service LIKE CONCAT('%',?,'%') OR brief LIKE CONCAT('%',?,'%'))";
    $types .= 'ssss';
    array_push($params, $q, $q, $q, $q);
}
if ($status !== '') {
    $whereParts[] = "status = ?";
    $types .= 's'; $params[] = $status;
}
if ($assignee !== '') {
    $whereParts[] = "assignee = ?";
    $types .= 's'; $params[] = $assignee;
}
if ($from !== '') {
    $whereParts[] = "created_at >= ?";
    $types .= 's'; $params[] = $from . ' 00:00:00';
}
if ($to !== '') {
    $whereParts[] = "created_at <= ?";
    $types .= 's'; $params[] = $to . ' 23:59:59';
}

$where_sql = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

// Count total rows
$countSql = "SELECT COUNT(*) AS cnt FROM contacts $where_sql";
$stmt = $mysqli->prepare($countSql);
if ($stmt === false) {
    echo "Prepare failed: " . htmlspecialchars($mysqli->error);
    exit;
}
if ($whereParts) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$cres = $stmt->get_result();
$total = 0;
if ($cres) {
    $row = $cres->fetch_assoc();
    $total = (int)$row['cnt'];
}
$stmt->close();

$totalPages = max(1, (int)ceil($total / $perPage));

// Main fetch (note: MySQL on Windows is case-insensitive for column names,
// but we access results using lowercase keys — mysqli returns column names as returned by query)
$sql = "SELECT id, name, email, phone, service, status, assignee, priority, next_followup, created_at
        FROM contacts
        $where_sql
        ORDER BY FIELD(status,'New','Contacted','Qualified','Proposal Sent','Won','Lost') ASC, created_at DESC
        LIMIT ?, ?";
$stmt = $mysqli->prepare($sql);
if ($stmt === false) {
    echo "Prepare failed: " . htmlspecialchars($mysqli->error);
    exit;
}

if ($whereParts) {
    // bind types + ii
    $types2 = $types . 'ii';
    $bindArr = array_merge($params, [$offset, $perPage]);
    $stmt->bind_param($types2, ...$bindArr);
} else {
    $stmt->bind_param('ii', $offset, $perPage);
}

$stmt->execute();
$res = $stmt->get_result();

// Safe fetch for assignees & sources (may return false)
$assRes = $mysqli->query("SELECT DISTINCT COALESCE(assignee,'') AS assignee FROM contacts ORDER BY assignee");
$sourcesRes = $mysqli->query("SELECT DISTINCT COALESCE(source,'') AS source FROM contacts ORDER BY source");

// stats
$statSql = "SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN attachment IS NOT NULL AND attachment <> '' THEN 1 ELSE 0 END) AS with_attachments
  FROM contacts $where_sql";
$sstmt = $mysqli->prepare($statSql);
if ($sstmt) {
    if ($whereParts) $sstmt->bind_param($types, ...$params);
    $sstmt->execute();
    $stats = $sstmt->get_result()->fetch_assoc();
    $sstmt->close();
} else {
    $stats = ['total' => $total, 'with_attachments' => 0];
}

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Leads — ASISA CRM</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    body { background:#f7fafc; }
    .container { max-width:1200px; }

    /* CRM Table Fixes */
    .table-responsive td {
        vertical-align: middle !important;
    }
    .table-responsive td:nth-child(4) {
        max-width: 320px;
        white-space: normal;
        word-break: break-word;
        overflow-wrap: break-word;
    }
    /* Admin CRM table: allow horizontal scroll and readable cells */
    /* Admin CRM table: allow horizontal scroll and readable cells */
.table-responsive {
  overflow-x: auto !important;
}

.table-responsive table {
  min-width: 1100px; /* ensure columns don't collapse; adjust as needed */
}

.table-responsive td,
.table-responsive th {
  vertical-align: middle !important;
  white-space: normal; /* allow wrapping */
  word-break: break-word;
  overflow-wrap: break-word;
}

/* Make the next-followup input fit */
input.lead-datetime {
  max-width: 200px;
}

.followup-overdue {
  background-color: #fff3cd !important;
  border-left: 3px solid #ffc107;
}

.followup-due-today {
  background-color: #d1ecf1 !important;
  border-left: 3px solid #0dcaf0;
}

.followup-upcoming {
  background-color: #d1e7dd !important;
  border-left: 3px solid #198754;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 600;
}
.status-new { background: #cfe2ff; color: #084298; }
.status-contacted { background: #fff3cd; color: #664d03; }
.status-qualified { background: #d1e7dd; color: #0f5132; }
.status-proposal { background: #e2d9f3; color: #422874; }
.status-won { background: #d1f2eb; color: #0c5460; }
.status-lost { background: #f8d7da; color: #842029; }

</style>

</head>
<body>
<nav class="navbar navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">ASISA CRM</a>
    <div>
      <a href="dashboard.php" class="btn btn-outline-light btn-sm me-2">Dashboard</a>
      <a class="btn btn-outline-light btn-sm" href="users/logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex justify-content-between mb-3 align-items-center">
    <h4>Leads CRM</h4>
    <div><a href="export_csv.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success btn-sm">Export CSV</a></div>
  </div>

  <div class="card mb-3 p-3">
    <form class="row g-2" method="get" action="leads.php">
      <div class="col-md-3"><input class="form-control" name="q" placeholder="Search name/email/service" value="<?php echo htmlspecialchars($q); ?>"></div>
      <div class="col-md-2">
        <select name="status" class="form-control">
          <option value="">All statuses</option>
          <?php foreach (['New','Contacted','Qualified','Proposal Sent','Won','Lost'] as $st): ?>
            <option value="<?php echo $st;?>" <?php if($status===$st) echo 'selected'; ?>><?php echo $st;?></option>
          <?php endforeach;?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="assignee" class="form-control">
          <option value="">Any assignee</option>
          <?php if ($assRes !== false): while ($ar = $assRes->fetch_assoc()): if (!$ar['assignee']) continue; ?>
            <option value="<?php echo htmlspecialchars($ar['assignee']);?>" <?php if($assignee===$ar['assignee']) echo 'selected'; ?>><?php echo htmlspecialchars($ar['assignee']);?></option>
          <?php endwhile; endif; ?>
        </select>
      </div>
      <div class="col-md-2"><input type="date" name="from" value="<?php echo htmlspecialchars($from);?>" class="form-control"></div>
      <div class="col-md-2"><input type="date" name="to" value="<?php echo htmlspecialchars($to);?>" class="form-control"></div>
      <div class="col-md-1"><button class="btn btn-primary w-100">Filter</button></div>
    </form>
  </div>

  <div class="row mb-3">
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <h6 class="mb-0 text-muted">Total Leads</h6>
          <h3 class="mb-0"><?php echo (int)($stats['total'] ?? $total); ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <h6 class="mb-0 text-muted">With Attachments</h6>
          <h3 class="mb-0"><?php echo (int)($stats['with_attachments'] ?? 0); ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <h6 class="mb-0 text-muted">Needs Follow-up</h6>
          <h3 class="mb-0 text-warning">
            <?php 
            $followupCount = $mysqli->query("SELECT COUNT(*) AS cnt FROM contacts WHERE next_followup IS NOT NULL AND next_followup <= NOW() AND COALESCE(status, 'New') NOT IN ('Won', 'Lost')");
            $fc = $followupCount ? (int)$followupCount->fetch_assoc()['cnt'] : 0;
            echo $fc;
            ?>
          </h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <h6 class="mb-0 text-muted">Unassigned</h6>
          <h3 class="mb-0 text-info">
            <?php 
            $unassignedCount = $mysqli->query("SELECT COUNT(*) AS cnt FROM contacts WHERE (assignee IS NULL OR assignee = '') AND COALESCE(status, 'New') NOT IN ('Won', 'Lost')");
            $uc = $unassignedCount ? (int)$unassignedCount->fetch_assoc()['cnt'] : 0;
            echo $uc;
            ?>
          </h3>
        </div>
      </div>
    </div>
  </div>

  <div class="table-responsive card p-0">
    <table class="table table-striped mb-0">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>When</th>
          <th>Name / Email</th>
          <th>Service</th>
          <th>Status</th>
          <th>Assignee</th>
          <th>Next Follow-up <small class="text-muted">(click to edit)</small></th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if ($res && $res->num_rows > 0):
          while ($r = $res->fetch_assoc()):
            // be defensive about field names - normalize keys to lowercase for PHP usage
            $row = array_change_key_case($r, CASE_LOWER);
        ?>
          <tr>
            <td><?php echo (int)$row['id'];?></td>
            <td><?php echo htmlspecialchars($row['created_at']);?></td>
            <td><strong><?php echo htmlspecialchars($row['name']);?></strong><br><a href="mailto:<?php echo htmlspecialchars($row['email']);?>"><?php echo htmlspecialchars($row['email']);?></a></td>
            <td><?php echo htmlspecialchars($row['service']);?></td>
            <td>
              <select class="form-select form-select-sm change-status" data-id="<?php echo (int)$row['id'];?>">
                <?php foreach (['New','Contacted','Qualified','Proposal Sent','Won','Lost'] as $st): ?>
                  <option value="<?php echo $st;?>" <?php if(($row['status'] ?? '')===$st) echo 'selected';?>><?php echo $st;?></option>
                <?php endforeach;?>
              </select>
            </td>
            <td>
              <input type="text" class="form-control form-control-sm change-assignee" data-id="<?php echo (int)$row['id'];?>" value="<?php echo htmlspecialchars($row['assignee'] ?? '');?>" placeholder="assign to...">
            </td>
            <?php
                // compute formatted datetime-local value (empty string if null)
                $nextVal = '';
                $followupClass = '';
                $followupLabel = '';
                if (!empty($row['next_followup'])) {
                    // ensure it's in server DATETIME format -> convert to HTML datetime-local: YYYY-MM-DDTHH:MM
                    $ts = strtotime($row['next_followup']);
                    if ($ts !== false) {
                        $nextVal = date('Y-m-d\TH:i', $ts);
                        $now = time();
                        $diff = $ts - $now;
                        $daysDiff = floor($diff / 86400);
                        
                        if ($diff < 0) {
                            $followupClass = 'followup-overdue';
                            $followupLabel = '<small class="text-danger d-block">Overdue</small>';
                        } elseif ($daysDiff == 0) {
                            $followupClass = 'followup-due-today';
                            $followupLabel = '<small class="text-info d-block">Due Today</small>';
                        } elseif ($daysDiff <= 3) {
                            $followupClass = 'followup-upcoming';
                            $followupLabel = '<small class="text-success d-block">Due Soon</small>';
                        }
                    }
                }
                ?>
                <td class="<?php echo $followupClass; ?>">
                  <input type="datetime-local" class="form-control form-control-sm lead-datetime change-followup" data-id="<?php echo (int)$row['id']; ?>" value="<?php echo htmlspecialchars($nextVal); ?>">
                  <?php echo $followupLabel; ?>
                </td>

            <td><a class="btn btn-sm btn-outline-primary" href="view_lead.php?id=<?php echo (int)$row['id'];?>">View</a></td>
          </tr>
        <?php
          endwhile;
        else:
        ?>
          <tr><td colspan="8" class="text-center py-4 text-muted">No leads found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <nav class="mt-3">
    <ul class="pagination">
      <?php
      $base = $_GET;
      for ($p=1; $p<=$totalPages; $p++):
        $base['page'] = $p;
        $href = 'leads.php?' . http_build_query($base);
      ?>
        <li class="page-item <?php if ($p == $page) echo 'active'; ?>"><a class="page-link" href="<?php echo $href; ?>"><?php echo $p; ?></a></li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>

<script>
document.querySelectorAll('.change-status').forEach(sel=>{
  sel.addEventListener('change', function(){
    const id = this.dataset.id;
    const status = this.value;
    fetch('ajax_update_lead.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({id:id, action:'status', status:status})
    }).then(r=>r.json()).then(j=>{
      if(!j.ok) alert('Update failed: '+(j.err||'unknown'));
      else {
        // Visual feedback
        const row = this.closest('tr');
        row.style.backgroundColor = '#d4edda';
        setTimeout(()=>{row.style.backgroundColor = '';}, 1000);
      }
    }).catch(e=>alert('Network error'));
  });
});

document.querySelectorAll('.change-assignee').forEach(input=>{
  let t;
  input.addEventListener('input', function(){
    clearTimeout(t);
    const id = this.dataset.id, val = this.value;
    t = setTimeout(()=> {
      fetch('ajax_update_lead.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({id:id, action:'assignee', assignee:val})
      }).then(r=>r.json()).then(j=>{
        if(!j.ok) console.log('Assignee update error', j);
        else {
          // Visual feedback
          const row = this.closest('tr');
          row.style.backgroundColor = '#d4edda';
          setTimeout(()=>{row.style.backgroundColor = '';}, 1000);
        }
      }).catch(()=>{/*silent*/});
    }, 700);
  });
});

document.querySelectorAll('.change-followup').forEach(input=>{
  input.addEventListener('change', function(){
    const id = this.dataset.id;
    const next_followup = this.value;
    const row = this.closest('tr');
    
    fetch('ajax_update_lead.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({id:id, action:'next_followup', next_followup:next_followup})
    }).then(r=>r.json()).then(j=>{
      if(!j.ok) {
        alert('Follow-up update failed: '+(j.err||'unknown'));
      } else {
        // Visual feedback
        row.style.backgroundColor = '#d4edda';
        setTimeout(()=>{
          row.style.backgroundColor = '';
          // Update followup label based on new date
          if (next_followup) {
            const followupDate = new Date(next_followup);
            const now = new Date();
            const diff = followupDate - now;
            const daysDiff = Math.floor(diff / (1000 * 60 * 60 * 24));
            const td = this.closest('td');
            
            // Remove old label
            const oldLabel = td.querySelector('small');
            if (oldLabel) oldLabel.remove();
            
            // Add new label
            let label = '';
            if (diff < 0) {
              td.className = 'followup-overdue';
              label = '<small class="text-danger d-block">Overdue</small>';
            } else if (daysDiff === 0) {
              td.className = 'followup-due-today';
              label = '<small class="text-info d-block">Due Today</small>';
            } else if (daysDiff <= 3) {
              td.className = 'followup-upcoming';
              label = '<small class="text-success d-block">Due Soon</small>';
            } else {
              td.className = '';
            }
            if (label) {
              this.insertAdjacentHTML('afterend', label);
            }
          }
        }, 1000);
      }
    }).catch(e=>{
      alert('Network error: ' + e.message);
    });
  });
});
</script>

</body>
</html>
<?php
// cleanup
if (isset($stmt) && $stmt) $stmt->close();
$mysqli->close();
