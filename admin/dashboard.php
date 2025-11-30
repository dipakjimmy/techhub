<?php
// admin/dashboard.php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: users/login.php');
    exit;
}

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) {
    die("DB connection failed: " . $mysqli->connect_error);
}

// Get CRM Statistics
$statusStats = [];
$statuses = ['New', 'Contacted', 'Qualified', 'Proposal Sent', 'Won', 'Lost'];
foreach ($statuses as $status) {
    $stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM contacts WHERE COALESCE(status, 'New') = ?");
    $stmt->bind_param('s', $status);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $statusStats[$status] = (int)$result['cnt'];
    $stmt->close();
}

// Total leads
$totalLeads = array_sum($statusStats);
$wonLeads = $statusStats['Won'] ?? 0;
$conversionRate = $totalLeads > 0 ? round(($wonLeads / $totalLeads) * 100, 1) : 0;

// Active pipeline (excluding Won/Lost)
$activePipeline = $statusStats['New'] + $statusStats['Contacted'] + $statusStats['Qualified'] + $statusStats['Proposal Sent'];

// Recent leads (last 7 days)
$stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM contacts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->execute();
$recentLeads = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

// Leads needing follow-up (next_followup <= today)
$stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM contacts WHERE next_followup IS NOT NULL AND next_followup <= NOW() AND COALESCE(status, 'New') NOT IN ('Won', 'Lost')");
$stmt->execute();
$needsFollowup = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

// Unassigned leads
$stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM contacts WHERE (assignee IS NULL OR assignee = '') AND COALESCE(status, 'New') NOT IN ('Won', 'Lost')");
$stmt->execute();
$unassigned = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

// Recent activity (last 10 leads)
$recentActivity = $mysqli->query("SELECT id, name, email, service, COALESCE(status, 'New') AS status, created_at FROM contacts ORDER BY created_at DESC LIMIT 10");

// Top services
$topServices = $mysqli->query("SELECT service, COUNT(*) AS cnt FROM contacts WHERE service IS NOT NULL AND service != '' GROUP BY service ORDER BY cnt DESC LIMIT 5");

// Filters for the main table
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$from = isset($_GET['from']) ? trim($_GET['from']) : '';
$to = isset($_GET['to']) ? trim($_GET['to']) : '';
$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page-1)*$perPage;

$where = [];
$types = '';
$params = [];

if ($q !== '') {
    $where[] = "(name LIKE CONCAT('%',?,'%') OR email LIKE CONCAT('%',?,'%') OR service LIKE CONCAT('%',?,'%') OR brief LIKE CONCAT('%',?,'%'))";
    $types .= 'ssss';
    array_push($params, $q, $q, $q, $q);
}
if ($from !== '') {
    $where[] = "created_at >= ?";
    $types .= 's';
    $params[] = $from . ' 00:00:00';
}
if ($to !== '') {
    $where[] = "created_at <= ?";
    $types .= 's';
    $params[] = $to . ' 23:59:59';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countSql = "SELECT COUNT(*) AS cnt FROM contacts $where_sql";
$stmt = $mysqli->prepare($countSql);
if ($where) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

$totalPages = max(1, (int)ceil($total/$perPage));

$sql = "SELECT id,name,email,phone,service,brief,attachment,COALESCE(status, 'New') AS status,created_at FROM contacts $where_sql ORDER BY created_at DESC LIMIT ?, ?";
$stmt = $mysqli->prepare($sql);
if ($where) {
    $types2 = $types . 'ii';
    $bindParams = array_merge($params, [$offset, $perPage]);
    $stmt->bind_param($types2, ...$bindParams);
} else {
    $stmt->bind_param('ii', $offset, $perPage);
}
$stmt->execute();
$res = $stmt->get_result();

$statSql = "SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN attachment IS NOT NULL AND attachment <> '' THEN 1 ELSE 0 END) AS with_attachments
  FROM contacts $where_sql";
$sstmt = $mysqli->prepare($statSql);
if ($where) $sstmt->bind_param($types, ...$params);
$sstmt->execute();
$stats = $sstmt->get_result()->fetch_assoc();
$sstmt->close();
?>
<!doctype html>
<html>
<head>
  
  <meta charset="utf-8">
  <title>CRM Dashboard — ASISA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    body { background:#f7fafc; }
    .container { max-width:1400px; }
    
    .stat-card {
        border-left: 4px solid;
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .stat-card.new { border-left-color: #0d6efd; }
    .stat-card.contacted { border-left-color: #ffc107; }
    .stat-card.qualified { border-left-color: #198754; }
    .stat-card.proposal { border-left-color: #6f42c1; }
    .stat-card.won { border-left-color: #20c997; }
    .stat-card.lost { border-left-color: #dc3545; }
    
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

</style>

</head>

<body>
  
<nav class="navbar navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">ASISA CRM Dashboard</a>
    <div>
      <a href="leads.php" class="btn btn-outline-light btn-sm me-2">Leads Management</a>
      <a href="users/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <!-- CRM KPI Cards -->
  <div class="row mb-4">
    <div class="col-md-3 mb-3">
      <div class="card stat-card new h-100">
        <div class="card-body">
          <h6 class="text-muted mb-2">Total Leads</h6>
          <h2 class="mb-0"><?php echo number_format($totalLeads); ?></h2>
          <small class="text-muted">All time</small>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card stat-card qualified h-100">
        <div class="card-body">
          <h6 class="text-muted mb-2">Active Pipeline</h6>
          <h2 class="mb-0"><?php echo number_format($activePipeline); ?></h2>
          <small class="text-muted">In progress</small>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card stat-card won h-100">
        <div class="card-body">
          <h6 class="text-muted mb-2">Conversion Rate</h6>
          <h2 class="mb-0"><?php echo $conversionRate; ?>%</h2>
          <small class="text-muted"><?php echo $wonLeads; ?> won</small>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card stat-card contacted h-100">
        <div class="card-body">
          <h6 class="text-muted mb-2">Needs Follow-up</h6>
          <h2 class="mb-0"><?php echo number_format($needsFollowup); ?></h2>
          <small class="text-muted">Action required</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Pipeline Status Overview -->
  <div class="row mb-4">
    <div class="col-md-8 mb-3">
      <div class="card">
        <div class="card-header bg-white">
          <h5 class="mb-0">Sales Pipeline</h5>
        </div>
        <div class="card-body">
          <canvas id="pipelineChart" height="100"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-3">
      <div class="card">
        <div class="card-header bg-white">
          <h5 class="mb-0">Quick Stats</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span>New Leads (7 days)</span>
              <strong><?php echo $recentLeads; ?></strong>
            </div>
          </div>
          <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span>Unassigned Leads</span>
              <strong class="text-warning"><?php echo $unassigned; ?></strong>
            </div>
          </div>
          <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span>Won</span>
              <strong class="text-success"><?php echo $statusStats['Won']; ?></strong>
            </div>
          </div>
          <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span>Lost</span>
              <strong class="text-danger"><?php echo $statusStats['Lost']; ?></strong>
            </div>
          </div>
          <hr>
          <h6 class="mb-2">Top Services</h6>
          <?php if ($topServices && $topServices->num_rows > 0): ?>
            <?php while ($service = $topServices->fetch_assoc()): ?>
              <div class="d-flex justify-content-between mb-1">
                <span><?php echo htmlspecialchars($service['service']); ?></span>
                <strong><?php echo $service['cnt']; ?></strong>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p class="text-muted small">No data</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Status Breakdown Cards -->
  <div class="row mb-4">
    <?php 
    $statusConfig = [
        'New' => ['class' => 'new', 'label' => 'New'],
        'Contacted' => ['class' => 'contacted', 'label' => 'Contacted'],
        'Qualified' => ['class' => 'qualified', 'label' => 'Qualified'],
        'Proposal Sent' => ['class' => 'proposal', 'label' => 'Proposal'],
        'Won' => ['class' => 'won', 'label' => 'Won'],
        'Lost' => ['class' => 'lost', 'label' => 'Lost']
    ];
    foreach ($statusConfig as $status => $config): 
        $count = $statusStats[$status] ?? 0;
        $percentage = $totalLeads > 0 ? round(($count / $totalLeads) * 100, 1) : 0;
    ?>
    <div class="col-md-2 mb-3">
      <div class="card stat-card <?php echo $config['class']; ?> text-center">
        <div class="card-body">
          <h6 class="text-muted mb-1"><?php echo $config['label']; ?></h6>
          <h3 class="mb-1"><?php echo $count; ?></h3>
          <small class="text-muted"><?php echo $percentage; ?>%</small>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Recent Activity -->
  <div class="row mb-4">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Recent Activity</h5>
          <a href="leads.php" class="btn btn-sm btn-outline-primary">View All Leads</a>
        </div>
        <div class="card-body">
          <?php if ($recentActivity && $recentActivity->num_rows > 0): ?>
            <div class="table-responsive">
              <table class="table table-sm table-hover">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Name / Email</th>
                    <th>Service</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($activity = $recentActivity->fetch_assoc()): ?>
                    <tr>
                      <td><?php echo date('M d, Y', strtotime($activity['created_at'])); ?></td>
                      <td>
                        <strong><?php echo htmlspecialchars($activity['name']); ?></strong><br>
                        <small class="text-muted"><?php echo htmlspecialchars($activity['email']); ?></small>
                      </td>
                      <td><?php echo htmlspecialchars($activity['service']); ?></td>
                      <td>
                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $activity['status'])); ?>">
                          <?php echo htmlspecialchars($activity['status']); ?>
                        </span>
                      </td>
                      <td>
                        <a href="view_lead.php?id=<?php echo (int)$activity['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <p class="text-muted text-center py-3">No recent activity</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Requests Table -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>All Requests</h4>
    <div>
      <a href="export_csv.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success btn-sm">Export CSV</a>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <form class="row g-2" method="get" action="dashboard.php">
        <div class="col-md-4">
          <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" class="form-control" placeholder="Search name / email / service">
        </div>
        <div class="col-auto">
          <input type="date" name="from" class="form-control" value="<?php echo htmlspecialchars($from); ?>">
        </div>
        <div class="col-auto">
          <input type="date" name="to" class="form-control" value="<?php echo htmlspecialchars($to); ?>">
        </div>
        <div class="col-auto">
          <button class="btn btn-primary">Filter</button>
        </div>
      </form>
    </div>
  </div>


  <div class="table-responsive">
    <table class="table table-striped table-hover">
      <thead class="table-light">
        <tr>
          <th>#</th><th>Date</th><th>Name / Email</th><th>Service</th><th>Status</th><th>Brief</th><th>Attachment</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $res->fetch_assoc()): ?>
          <tr>
            <td><?php echo (int)$row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
            <td>
              <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
              <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>"><?php echo htmlspecialchars($row['email']); ?></a>
            </td>
            <td><?php echo htmlspecialchars($row['service']); ?></td>
            <td>
              <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>">
                <?php echo htmlspecialchars($row['status']); ?>
              </span>
            </td>
            <td style="max-width:360px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?php echo htmlspecialchars(substr($row['brief'],0,200)); ?></td>
            <td><?php echo !empty($row['attachment']) ? '<a href="../uploads/'.rawurlencode($row['attachment']).'" download>Download</a>' : '—'; ?></td>
            <td><a class="btn btn-sm btn-outline-primary" href="view.php?id=<?php echo (int)$row['id']; ?>">View</a></td>
          </tr>
        <?php endwhile; ?>
        <?php if ($res->num_rows === 0): ?>
          <tr><td colspan="8" class="text-center py-4 text-muted">No requests found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <nav>
    <ul class="pagination">
      <?php
      $base = $_GET;
      for ($p=1;$p<=$totalPages;$p++):
        $base['page']=$p;
        $href = 'dashboard.php?' . http_build_query($base);
      ?>
        <li class="page-item <?php if($p==$page) echo 'active'; ?>"><a class="page-link" href="<?php echo $href; ?>"><?php echo $p; ?></a></li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>

<script>
// Pipeline Chart
const ctx = document.getElementById('pipelineChart');
if (ctx) {
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['New', 'Contacted', 'Qualified', 'Proposal Sent', 'Won', 'Lost'],
      datasets: [{
        label: 'Leads by Status',
        data: [
          <?php echo $statusStats['New']; ?>,
          <?php echo $statusStats['Contacted']; ?>,
          <?php echo $statusStats['Qualified']; ?>,
          <?php echo $statusStats['Proposal Sent']; ?>,
          <?php echo $statusStats['Won']; ?>,
          <?php echo $statusStats['Lost']; ?>
        ],
        backgroundColor: [
          '#0d6efd',
          '#ffc107',
          '#198754',
          '#6f42c1',
          '#20c997',
          '#dc3545'
        ]
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
}
</script>

</body>
</html>
<?php
$stmt->close();
$mysqli->close();
