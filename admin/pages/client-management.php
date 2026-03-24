<?php
/* SMC and CSNK employees both use client-management.php with agency-based filtering */
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/functions.php';

$database = new Database();
$auth = new Auth($database);
$auth->requireLogin();
$currentUser = $auth->getCurrentUser();

require_once '../includes/Applicant.php';
require_once '../includes/Admin.php';

$applicant = new Applicant($database);
$admin = new Admin($database);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
  header('Content-Type: application/json');
  $conn = $database->getConnection();
  $bookingId = (int) ($_POST['id'] ?? 0);
  $currentRole = strtolower((string) ($currentUser['role'] ?? 'employee'));
  $userAgency = strtolower((string) ($_SESSION['current_agency_view'] ?? $_SESSION['agency'] ?? $currentUser['agency'] ?? ''));
  $userAgencyId = ($userAgency === 'csnk') ? 1 : (($userAgency === 'smc') ? 2 : 0);

  if ($bookingId > 0) {
    $sql = "
      UPDATE client_bookings cb
      LEFT JOIN business_units bu ON bu.id = cb.business_unit_id
      SET cb.deleted_at = NOW()
      WHERE cb.id = ?
        AND cb.deleted_at IS NULL
    ";
    $bindTypes = 'i';
    $bindValues = [$bookingId];

    if ($userAgencyId > 0) {
      $sql .= " AND bu.agency_id = ?";
      $bindTypes .= 'i';
      $bindValues[] = $userAgencyId;
    } elseif (!in_array($currentRole, ['admin', 'super_admin'], true)) {
      echo json_encode(['success' => false, 'error' => 'Your account is missing an agency assignment.']);
      exit;
    }

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
      echo json_encode(['success' => false, 'error' => 'Unable to prepare delete query.']);
      exit;
    }

    $stmt->bind_param($bindTypes, ...$bindValues);
    $success = $stmt->execute();
    if (!$success) {
      echo json_encode(['success' => false, 'error' => 'Database query failed: ' . $stmt->error]);
      $stmt->close();
      exit;
    }

    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    if ($success && $affectedRows > 0) {
      // Log activity
      $ip = $_SERVER['REMOTE_ADDR'] ?? '';
      $userId = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0; // Use admin_id first, fallback
      $desc = "Soft deleted client booking ID {$bookingId}";
      $logStmt = $conn->prepare("INSERT INTO activity_logs (admin_id, action, description, ip_address) VALUES (?, 'Delete Client Booking', ?, ?)");

      if ($logStmt) {
        $logStmt->bind_param("iss", $userId, $desc, $ip);
        $logStmt->execute();
        $logStmt->close();
      }
    }

    if ($affectedRows === 0) {
      echo json_encode(['success' => false, 'error' => 'Client booking not found or already deleted.']);
      exit;
    }

    echo json_encode(['success' => true]);
  } else {
    echo json_encode(['success' => false, 'error' => 'Invalid booking ID or agency.']);
  }
  exit;
}

$pageTitle = 'Client Management';
require_once '../includes/header.php';

// Role flags from header.php
$currentRole = $currentUser['role'] ?? 'employee';
$isSuperAdmin = ($currentRole === 'super_admin');
$isAdmin = ($currentRole === 'admin');
$canSeeAdminUX = ($isAdmin || $isSuperAdmin);

// Get user agency from session
$userAgency = strtolower((string) ($_SESSION['current_agency_view'] ?? $_SESSION['agency'] ?? $currentUser['agency'] ?? ''));

/* ---------------------- Client Bookings (All Statuses) ---------------------- */
$clientBookings = [];
$bookingSearch = isset($_GET['booking_search']) ? trim($_GET['booking_search']) : '';
$applicantStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$sortBy = isset($_GET['sort']) ? strtolower(trim($_GET['sort'])) : 'csnk';

// Validate sort option
$allowedSorts = ['csnk', 'smc'];
if (!in_array($sortBy, $allowedSorts, true)) {
  $sortBy = 'csnk';
}

$conn = $database->getConnection();
if ($conn instanceof mysqli) {
  // Build agency filter based on user role
  $agencyFilter = '';
  if ($userAgency === 'csnk') {
    $agencyFilter = " AND bu.agency_id = 1";
  } elseif ($userAgency === 'smc') {
    $agencyFilter = " AND bu.agency_id = 2";
  }

  $bookingSql = "
    SELECT 
      cb.id AS booking_id,
      cb.client_first_name,
      cb.client_middle_name,
      cb.client_last_name,
      cb.client_phone,
      cb.client_email,
      cb.client_address,
      cb.appointment_type,
      cb.appointment_date,
      cb.appointment_time,
      cb.status AS booking_status,
      cb.created_at AS booking_created_at,
      a.id AS applicant_id,
      a.first_name AS app_first_name,
      a.middle_name AS app_middle_name,
      a.last_name AS app_last_name,
      a.suffix AS app_suffix,
      a.phone_number AS app_phone,
      a.email AS app_email,
      a.status AS applicant_status,
      bu.name AS business_unit_name
    FROM client_bookings cb
    INNER JOIN applicants a ON a.id = cb.applicant_id
    LEFT JOIN business_units bu ON bu.id = cb.business_unit_id
    WHERE cb.deleted_at IS NULL AND 1=1
  ";

  $bookingSql .= $agencyFilter;

  if ($applicantStatus !== 'all' && in_array($applicantStatus, ['on_process', 'approved'], true)) {
    $bookingSql .= " AND a.status = '" . $conn->real_escape_string($applicantStatus) . "'";
  }

  if (!empty($bookingSearch)) {
    $searchTerm = '%' . $conn->real_escape_string($bookingSearch) . '%';
    $bookingSql .= " AND (
      CONCAT(cb.client_first_name, ' ', cb.client_last_name) LIKE '$searchTerm'
      OR CONCAT(a.first_name, ' ', a.last_name) LIKE '$searchTerm'
      OR cb.client_email LIKE '$searchTerm'
      OR cb.client_phone LIKE '$searchTerm'
    )";
  }

  if ($sortBy === 'csnk') {
    $orderBy = '(bu.agency_id = 1) DESC, cb.created_at DESC';
  } else { // smc
    $orderBy = '(bu.agency_id = 2) DESC, cb.created_at DESC';
  }

  $bookingSql .= " ORDER BY " . $orderBy;

  if ($bookingResult = $conn->query($bookingSql)) {
    $clientBookings = $bookingResult->fetch_all(MYSQLI_ASSOC);
  }

  $countSql = "SELECT a.status, COUNT(*) as count 
                 FROM client_bookings cb 
                 INNER JOIN applicants a ON a.id = cb.applicant_id
                 LEFT JOIN business_units bu ON bu.id = cb.business_unit_id
                 WHERE 1=1";

  $countSql .= $agencyFilter;
  $countSql .= " GROUP BY a.status";

  $statusCounts = ['on_process' => 0, 'approved' => 0, 'total' => 0];
  if ($countResult = $conn->query($countSql)) {
    while ($row = $countResult->fetch_assoc()) {
      if (isset($statusCounts[$row['status']])) {
        $statusCounts[$row['status']] = (int) $row['count'];
      }
      $statusCounts['total'] += (int) $row['count'];
    }
  }
}

function safe(?string $s): string
{
  return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
?>
<!-- Tailwind CDN -->
<script src="https://cdn.tailwindcss.com"></script>



<!-- Header -->
<div class="mb-8">
  <h1 class="text-3xl font-bold text-gray-900 mb-2">Client Management</h1>
  <p class="text-gray-600">Manage all client bookings</p>
</div>

<!-- Filter Tabs -->
<div class="flex justify-end border-b border-gray-200 mb-6 gap-4">


  <a href="client-management.php"
    class="px-6 py-3 rounded-lg font-semibold text-sm <?= $applicantStatus === 'all' ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-white hover:shadow-sm hover:text-gray-900'; ?>">

    All <span
      class="ml-2 px-3 py-1.5 bg-white/60 rounded-lg text-sm font-bold <?= $applicantStatus === 'all' ? 'bg-white text-blue-900' : 'text-gray-600'; ?>">
      <?= $statusCounts['total'] ?>
    </span>

  </a>
  <a href="?status=on_process"
    class="px-6 py-3 rounded-lg font-semibold text-sm <?= $applicantStatus === 'on_process' ? 'bg-cyan-500 text-white ' : 'text-gray-700 hover:bg-white hover:shadow-sm hover:text-gray-900'; ?>">

    On Process <span
      class="ml-2 px-3 py-1.5 bg-white/60 rounded-lg text-sm font-bold <?= $applicantStatus === 'on_process' ? 'bg-white text-cyan-900' : 'text-gray-600'; ?>">
      <?= $statusCounts['on_process'] ?>
    </span>

  </a>
  <a href="?status=approved"
    class="px-6 py-3 rounded-lg font-semibold text-sm <?= $applicantStatus === 'approved' ? 'bg-emerald-500 text-white ' : 'text-gray-700 hover:bg-white hover:shadow-sm hover:text-gray-900'; ?>">

    Approved <span
      class="ml-2 px-3 py-1.5 bg-white/60 rounded-lg text-sm font-bold <?= $applicantStatus === 'approved' ? 'bg-white text-emerald-900' : 'text-gray-600'; ?>">
      <?= $statusCounts['approved'] ?>
    </span>

  </a>
</div>

<!-- Search Form -->
<form method="GET" action="" class="bg-white rounded-xl p-6 mb-8">
  <?php if (!empty($applicantStatus) && $applicantStatus !== 'all'): ?>
    <input type="hidden" name="status" value="<?= safe($applicantStatus) ?>">
  <?php endif; ?>
  <div class="flex flex-col lg:flex-row gap-4">
    <div class="relative flex-1">
      <svg class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
        stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
      <input type="text" name="booking_search" value="<?= safe($bookingSearch) ?>"
        placeholder="Search by client, applicant, email or phone..."
        class="w-full pl-12 pr-12 py-3 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500 focus:ring-blue-500 focus:border-blue-500">
      <?php if (!empty($bookingSearch)): ?>
        <a href="?<?= !empty($applicantStatus) && $applicantStatus !== 'all' ? 'status=' . safe($applicantStatus) . '&' : '' ?>sort=<?= safe($sortBy) ?>"
          class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </a>
      <?php endif; ?>
    </div>
    <div class="flex gap-2">
      <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
          stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6M3 16h4" />
        </svg>
        <select name="sort" onchange="this.form.submit()"
          class="pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-48">
          <option value="csnk" <?= $sortBy === 'csnk' ? 'selected' : '' ?>>CSNK</option>
          <option value="smc" <?= $sortBy === 'smc' ? 'selected' : '' ?>>SMC</option>
        </select>
      </div>
      <button type="submit"
        class="px-8 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:outline-none whitespace-nowrap">Search</button>
    </div>
  </div>
</form>

<!-- Table -->
<div class="bg-white  rounded-xl overflow-hidden max-w-none">
  <?php if (empty($clientBookings)): ?>
    <div class="text-center py-16">
      <svg class="mx-auto h-24 w-24 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
      </svg>
      <h3 class="text-xl font-bold text-gray-900 mb-2">No Bookings</h3>
      <p class="text-gray-500">
        <?= !empty($bookingSearch) || $applicantStatus !== 'all' ? 'No results for your filters.' : 'No bookings yet.' ?>
      </p>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th
              class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider w-96 border-r border-gray-300">
              Client</th>
            <th
              class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider w-96 border-r border-gray-300">
              Applicant</th>
            <th
              class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider w-80 border-r border-gray-300">
              Contact</th>
            <th
              class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider w-56 border-r border-gray-300">
              Appointment
            </th>
            <th
              class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider w-56 border-r border-gray-300">
              Status</th>
            <th
              class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider w-64 border-r border-gray-300">
              Business Unit
            </th>
            <th
              class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider w-48 border-r border-gray-300">
              Booked</th>
            <th
              class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider w-56 border-r border-gray-300">
              Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php foreach ($clientBookings as $booking): ?>
            <?php
            $clientFullName = trim(($booking['client_first_name'] ?? '') . ' ' . ($booking['client_middle_name'] ?? '') . ' ' . ($booking['client_last_name'] ?? ''));
            if (empty($clientFullName))
              $clientFullName = '—';

            $applicantFullName = getFullName(
              $booking['app_first_name'] ?? '',
              $booking['app_middle_name'] ?? '',
              $booking['app_last_name'] ?? '',
              $booking['app_suffix'] ?? ''
            );

            $apptDate = !empty($booking['appointment_date']) ? formatDate($booking['appointment_date']) : '—';
            $apptTime = !empty($booking['appointment_time']) ? date('h:i A', strtotime($booking['appointment_time'])) : '';
            $contractTerm = trim($apptDate . ' ' . $apptTime);

            $clientContact = '';
            if (!empty($booking['client_phone']))
              $clientContact .= $booking['client_phone'];
            if (!empty($booking['client_email']))
              $clientContact .= (!empty($clientContact) ? ' / ' : '') . $booking['client_email'];
            if (empty($clientContact))
              $clientContact = '—';

            $bookedDate = !empty($booking['booking_created_at']) ? formatDateTime($booking['booking_created_at']) : '—';

            $appStatus = $booking['applicant_status'] ?? 'pending';
            $viewApplicantUrl = match ($appStatus) {
              'on_process' => 'view_onprocess.php?id=' . (int) ($booking['applicant_id'] ?? 0),
              'approved' => 'view_approved.php?id=' . (int) ($booking['applicant_id'] ?? 0),
              default => 'view-applicant.php?id=' . (int) ($booking['applicant_id'] ?? 0)
            };

            $status = $booking['applicant_status'] ?? 'pending';
            $statusBg = match ($status) {
              'approved' => 'bg-emerald-100 text-emerald-800',
              'on_process' => 'bg-cyan-100 text-cyan-800',
              default => 'bg-yellow-100 text-yellow-800'
            };
            ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4 border-r border-gray-200">
                <div class="font-semibold text-gray-900">
                  <?= safe($clientFullName) ?>
                </div>
                <div class="text-sm text-gray-600 mt-1">
                  <?= safe($booking['client_address'] ?? '—') ?>
                </div>
              </td>
              <td class="px-6 py-4 border-r border-gray-200">
                <a href="<?= safe($viewApplicantUrl) ?>"
                  class="font-semibold text-blue-600 hover:text-blue-700 hover:underline">
                  <?= safe($applicantFullName) ?>
                </a>
                <div class="text-sm text-gray-600">ID:
                  <?= (int) ($booking['applicant_id'] ?? 0) ?>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-700 border-r border-gray-200">
                <?= safe($clientContact) ?>
              </td>
              <td class="px-6 py-4 border-r border-gray-200">
                <span class="px-3 py-1 bg-gray-100 text-gray-800 text-sm rounded-full">
                  <?= safe($booking['appointment_type'] ?? '—') ?>
                </span>
              </td>
              <td class="px-6 py-4 border-r border-gray-200">
                <span class="px-3 py-1 <?= $statusBg ?> text-sm font-medium rounded-full">
                  <?= ucfirst(str_replace('_', ' ', $status)) ?>
                </span>
              </td>
              <td class="px-6 py-4 text-sm font-medium border-r border-gray-200">
                <?= safe($booking['business_unit_name'] ?? '—') ?>
              </td>
              <td class="px-6 py-4 text-center text-sm text-gray-600 border-r border-gray-200">
                <?= safe($bookedDate) ?>
              </td>
              <td class="px-6 py-4 text-center border-r border-gray-200">
                <div class="flex items-center justify-center gap-2 whitespace-nowrap">
                  <a href="client-profile.php?id=<?= (int) ($booking['booking_id'] ?? 0) ?>"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600 text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    title="View client" aria-label="View client">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </a>
                  <?php if ($isAdmin || $isSuperAdmin): ?>
                    <button
                      class="delete-client-btn inline-flex h-10 w-10 items-center justify-center rounded-lg bg-red-600 text-white transition-colors hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:outline-none"
                      data-id="<?= (int) ($booking['booking_id'] ?? 0) ?>" title="Delete client booking"
                      aria-label="Delete client booking" type="button">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

</div>

<?php require_once '../includes/footer.php'; ?>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.delete-client-btn').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        if (confirm('Are you sure you want to soft delete this client booking? It will be marked as deleted but data preserved. OK to proceed?')) {
          const row = this.closest('tr');
          const id = this.dataset.id;
          const formData = new FormData();
          formData.append('action', 'delete');
          formData.append('id', id);

          fetch(window.location.href, {
            method: 'POST',
            body: formData
          })
            .then(async response => {
              const contentType = response.headers.get('content-type') || '';
              if (!contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error('Unexpected response: ' + text.slice(0, 120));
              }

              return response.json();
            })
            .then(data => {
              if (data.success) {
                row.style.animation = 'fadeOut 0.5s';
                setTimeout(() => row.remove(), 500);
              } else {
                alert('Delete failed: ' + (data.error || 'Unknown error'));
              }
            })
            .catch(err => {
              console.error('Delete error:', err);
              alert('Delete failed. Please try again.');
            });
        }
      });
    });
  });

  // Fade out animation
  const style = document.createElement('style');
  style.textContent = `
  @keyframes fadeOut {
    from { opacity: 1; }
    to { opacity: 0; }
  }
`;
  document.head.appendChild(style);
</script>
