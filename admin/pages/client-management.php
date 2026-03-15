<?php
/* Client Management – Modern Light UI (Applicants.php inspired) */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Client Management';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';
require_once '../includes/Admin.php';
require_once '../includes/functions.php';

$applicant = new Applicant($database);
$admin = new Admin($database);

$currentRole = $currentUser['role'] ?? 'employee';
$isSuperAdmin = ($currentRole === 'super_admin');
$isAdmin = ($currentRole === 'admin');
$canSeeAdminUX = ($isAdmin || $isSuperAdmin);

$userAgency = isset($_SESSION['agency']) ? strtolower(trim($_SESSION['agency'])) : '';
$validAgencies = ['csnk' => 1, 'smc' => 2];

$viewAgency = isset($_GET['agency']) ? strtolower(trim($_GET['agency'])) : ($userAgency ?: 'csnk');
if (!array_key_exists($viewAgency, $validAgencies)) {
    $viewAgency = 'csnk';
}

if (!$canSeeAdminUX && isset($validAgencies[$userAgency])) {
    $viewAgency = $userAgency;
}

$bookingSearch = $_GET['booking_search'] ?? '';
$applicantStatus = $_GET['status'] ?? 'all';
$sortBy = $_GET['sort'] ?? 'latest';

$allowedStatuses = ['all', 'on_process', 'approved'];
if (!in_array($applicantStatus, $allowedStatuses, true)) {
    $applicantStatus = 'all';
}

$allowedSorts = ['latest', 'oldest', 'agency_asc', 'agency_desc'];
if (!in_array($sortBy, $allowedSorts, true)) {
    $sortBy = 'latest';
}

$csnkData = ['bookings' => [], 'counts' => ['on_process'=>0,'approved'=>0,'total'=>0]];
$smcData  = ['bookings' => [], 'counts' => ['on_process'=>0,'approved'=>0,'total'=>0]];

function safe($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$conn = $database->getConnection();

if ($conn instanceof mysqli) {
    foreach (['csnk' => 1, 'smc' => 2] as $agencyKey => $agencyId) {
        $data =& ${$agencyKey . 'Data'};
        $where = ["bu.agency_id = ?"];
        $params = [$agencyId];
        $types = 'i';

        if ($applicantStatus !== 'all') {
            $where[] = "a.status = ?";
            $params[] = $applicantStatus;
            $types .= 's';
        }

        if ($bookingSearch !== '') {
            $where[] = "(cb.client_first_name LIKE ? OR cb.client_last_name LIKE ? OR cb.client_email LIKE ? OR cb.client_phone LIKE ?)";
            $term = "%$bookingSearch%";
            array_push($params, $term, $term, $term, $term);
            $types .= 'ssss';
        }

        $orderBy = match ($sortBy) {
            'oldest' => 'cb.created_at ASC',
            'agency_asc' => 'bu.name ASC',
            'agency_desc' => 'bu.name DESC',
            default => 'cb.created_at DESC'
        };

        $sql = "
            SELECT cb.*, a.*, bu.name AS business_unit_name,
                   cb.created_at AS booking_created_at,
                   cb.id AS booking_id,
                   a.status AS applicant_status
            FROM client_bookings cb
            JOIN applicants a ON a.id = cb.applicant_id
            LEFT JOIN business_units bu ON bu.id = cb.business_unit_id
            WHERE ".implode(' AND ', $where)."
            ORDER BY $orderBy
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $data['bookings'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $countSql = "
            SELECT a.status, COUNT(*) c
            FROM client_bookings cb
            JOIN applicants a ON a.id = cb.applicant_id
            LEFT JOIN business_units bu ON bu.id = cb.business_unit_id
            WHERE bu.agency_id = ?
            GROUP BY a.status
        ";

        $stmt = $conn->prepare($countSql);
        $stmt->bind_param('i', $agencyId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            if (isset($data['counts'][$r['status']])) {
                $data['counts'][$r['status']] = (int)$r['c'];
                $data['counts']['total'] += (int)$r['c'];
            }
        }
        $stmt->close();
    }
}
?>

<link rel="stylesheet" href="../tailwind.css">

<div class="container-fluid bg-slate-50 min-h-screen py-4">
<div class="max-w-7xl mx-auto px-4">

<!-- Header -->
<div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm mb-4">
  <h4 class="fw-semibold mb-1">Client Management</h4>
  <div class="flex flex-wrap gap-2 mt-2">
    <span class="badge bg-light text-dark border">Status: <?= ucfirst(str_replace('_',' ',$applicantStatus)) ?></span>
    <span class="badge bg-light text-dark border">Sort: <?= strtoupper(str_replace('_',' ',$sortBy)) ?></span>
  </div>
</div>

<?php if ($canSeeAdminUX): ?>
<!-- Agency Tabs -->
<ul class="nav nav-pills mb-4 gap-3" id="clientTabs">
  <li class="nav-item flex-fill">
    <button
      class="nav-link w-full rounded-xl py-3 fw-bold text-white <?= $viewAgency==='csnk'?'active':'' ?>"
      style="background:#dc2626;"
      data-bs-toggle="tab"
      data-bs-target="#csnk-pane"
      data-agency="csnk">
      CSNK Clients
      <span class="badge bg-white text-danger ms-2"><?= $csnkData['counts']['total'] ?></span>
    </button>
  </li>
  <li class="nav-item flex-fill">
    <button
      class="nav-link w-full rounded-xl py-3 fw-bold <?= $viewAgency==='smc'?'active':'' ?>"
      style="background:#0f172a;color:#facc15;"
      data-bs-toggle="tab"
      data-bs-target="#smc-pane"
      data-agency="smc">
      SMC Clients
      <span class="badge ms-2" style="background:#facc15;color:#0f172a;">
        <?= $smcData['counts']['total'] ?>
      </span>
    </button>
  </li>
</ul>
<?php endif; ?>

<div class="tab-content">

<?php
function renderTable($data) {
    if (empty($data)) {
        echo '<div class="text-center text-muted py-5">
              <i class="bi bi-people fs-1 d-block mb-2"></i>No clients found</div>';
        return;
    }
    echo '<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>Client</th>
          <th>Applicant</th>
          <th>Contact</th>
          <th>Appointment</th>
          <th>Status</th>
          <th>Business Unit</th>
          <th>Booked</th>
          <th></th>
        </tr>
      </thead><tbody>';

    foreach ($data as $b) {
        $appId = (int)$b['applicant_id'];
        $bookingId = (int)$b['booking_id'];
        $status = $b['applicant_status'];
        $viewUrl = $status==='approved' ? 'view_approved.php' : 'view_onprocess.php';

        echo "<tr>
          <td><strong>".safe($b['client_first_name'].' '.$b['client_last_name'])."</strong></td>
          <td><a href='{$viewUrl}?id={$appId}'>".safe(getFullName(
              $b['first_name'],$b['middle_name'],$b['last_name'],$b['suffix']
          ))."</a></td>
          <td class='text-muted'>".safe($b['client_phone'])."</td>
          <td>".safe($b['appointment_type'])."</td>
          <td><span class='badge bg-".($status==='approved'?'success':'info')."'>".ucfirst(str_replace('_',' ',$status))."</span></td>
          <td>".safe($b['business_unit_name'] ?? '—')."</td>
          <td class='text-muted'>".formatDateTime($b['booking_created_at'])."</td>
          <td>
            <a href='client-profile.php?id={$bookingId}' class='btn btn-sm btn-outline-primary'>
              <i class='bi bi-eye'></i>
            </a>
          </td>
        </tr>";
    }
    echo '</tbody></table></div>';
}
?>

<div class="tab-pane fade <?= $viewAgency==='csnk'?'show active':'' ?>" id="csnk-pane">
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <?php renderTable($csnkData['bookings']); ?>
    </div>
  </div>
</div>

<div class="tab-pane fade <?= $viewAgency==='smc'?'show active':'' ?>" id="smc-pane">
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <?php renderTable($smcData['bookings']); ?>
    </div>
  </div>
</div>

</div>
</div>
</div>

<script>
(function () {
  const tabs = document.querySelectorAll('#clientTabs [data-agency]');
  tabs.forEach(tab => {
    tab.addEventListener('shown.bs.tab', function () {
      const agency = this.dataset.agency;
      const url = new URL(window.location);
      url.searchParams.set('agency', agency);
      history.replaceState({}, '', url);
    });
  });
})();
</script>

<?php require_once '../includes/footer.php'; ?>