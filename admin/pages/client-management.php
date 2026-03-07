<?php
/* BLOCK SMC employees from accessing CSNK dashboard - must be before any output */
// Start session explicitly
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user agency is SMC
$userAgencyCheck = isset($_SESSION['agency']) ? strtolower($_SESSION['agency']) : '';

if ($userAgencyCheck === 'smc') {
    // SMC employees should use turkey_dashboard.php - redirect immediately (relative path for hosting compatibility)
    header('Location: turkey_dashboard.php');
    exit;
}

$pageTitle = 'Client Management';
require_once '../includes/header.php';

require_once '../includes/Applicant.php';
require_once '../includes/Admin.php';

$applicant = new Applicant($database);
$admin = new Admin($database);

// Role flags from header.php
$currentRole = $currentUser['role'] ?? 'employee';
$isSuperAdmin = ($currentRole === 'super_admin');
$isAdmin = ($currentRole === 'admin');
$canSeeAdminUX = ($isAdmin || $isSuperAdmin);

/* ---------------------- Client Bookings (All Statuses) ---------------------- */
$clientBookings = [];
$bookingSearch = isset($_GET['booking_search']) ? trim($_GET['booking_search']) : '';
$applicantStatus = isset($_GET['status']) ? $_GET['status'] : 'all';

$conn = $database->getConnection();
if ($conn instanceof mysqli) {
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
    WHERE 1=1
  ";

    // Applicant status filter
    if ($applicantStatus !== 'all' && in_array($applicantStatus, ['on_process', 'approved'], true)) {
        $bookingSql .= " AND a.status = '" . $conn->real_escape_string($applicantStatus) . "'";
    }

    // Search filter
    if (!empty($bookingSearch)) {
        $searchTerm = '%' . $conn->real_escape_string($bookingSearch) . '%';
        $bookingSql .= " AND (
      CONCAT(cb.client_first_name, ' ', cb.client_last_name) LIKE '$searchTerm'
      OR CONCAT(a.first_name, ' ', a.last_name) LIKE '$searchTerm'
      OR cb.client_email LIKE '$searchTerm'
      OR cb.client_phone LIKE '$searchTerm'
    )";
    }

    $bookingSql .= " ORDER BY cb.created_at DESC";

    if ($bookingResult = $conn->query($bookingSql)) {
        $clientBookings = $bookingResult->fetch_all(MYSQLI_ASSOC);
    }

    // Get counts for badges based on applicant status
    $countSql = "SELECT a.status, COUNT(*) as count FROM client_bookings cb INNER JOIN applicants a ON a.id = cb.applicant_id GROUP BY a.status";
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

// Helpers
function safe(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
?>
<!-- Tailwind (via CDN) layered on top of Bootstrap) -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    csnk: { red: '#c40000', dark: '#991b1b' }
                },
                boxShadow: {
                    soft: '0 10px 25px rgba(0,0,0,.06)',
                    glass: '0 10px 30px rgba(0,0,0,.08)'
                }
            }
        }
    }
</script>

<style>
    /* Hybrid polish for Bootstrap + Tailwind */
    .glass-card {
        backdrop-filter: blur(8px);
        background: linear-gradient(180deg, rgba(255, 255, 255, .72), rgba(255, 255, 255, .88));
        border: 1px solid rgba(230, 234, 242, .85);
        border-radius: 14px;
        box-shadow: 0 12px 30px rgba(16, 24, 40, .06);
    }

    .soft-divider {
        height: 1px;
        background: #eef2f7;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-semibold">Client Management</h4>
        <small class="text-muted">Manage all client bookings and contracts</small>
    </div>
    <div class="d-flex gap-2">
        <a href="client-management.php"
            class="btn btn-sm <?php echo $applicantStatus === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
            All <span class="badge bg-secondary"><?php echo $statusCounts['total']; ?></span>
        </a>
        <a href="?status=on_process"
            class="btn btn-sm <?php echo $applicantStatus === 'on_process' ? 'btn-info' : 'btn-outline-info'; ?>">
            On Process <span class="badge bg-secondary"><?php echo $statusCounts['on_process']; ?></span>
        </a>
        <a href="?status=approved"
            class="btn btn-sm <?php echo $applicantStatus === 'approved' ? 'btn-success' : 'btn-outline-success'; ?>">
            Approved <span class="badge bg-secondary"><?php echo $statusCounts['approved']; ?></span>
        </a>
    </div>
</div>

<!-- Search Form -->
<div class="glass-card mb-4">
    <div class="p-3">
        <form method="GET" action="" class="d-flex gap-2">
            <?php if (!empty($applicantStatus) && $applicantStatus !== 'all'): ?>
                <input type="hidden" name="status"
                    value="<?php echo htmlspecialchars($applicantStatus, ENT_QUOTES, 'UTF-8'); ?>">
            <?php endif; ?>
            <div class="input-group" style="max-width: 500px;">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" name="booking_search" class="form-control"
                    placeholder="Search by client name, applicant, email or phone..."
                    value="<?php echo htmlspecialchars($bookingSearch, ENT_QUOTES, 'UTF-8'); ?>">
                <?php if (!empty($bookingSearch)): ?>
                    <a href="?<?php echo !empty($applicantStatus) && $applicantStatus !== 'all' ? 'status=' . htmlspecialchars($applicantStatus) : ''; ?>"
                        class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>
</div>

<!-- Client Bookings Table -->
<div class="glass-card">
    <div class="soft-divider"></div>
    <div class="p-0">
        <?php if (empty($clientBookings)): ?>
            <div class="text-center py-5">
                <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-3 mb-0">
                    <?php if (!empty($bookingSearch) || ($applicantStatus !== 'all' && !empty($applicantStatus))): ?>
                        No bookings found matching your filters.
                    <?php else: ?>
                        No bookings yet.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 18%;">Client</th>
                            <th style="width: 18%;">Applicant</th>
                            <th style="width: 14%;">Contact</th>
                            <th style="width: 12%;">Appointment</th>
                            <th style="width: 12%;">Schedule</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 8%;">Agency</th>
                            <th style="width: 8%;">Booked Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientBookings as $booking): ?>
                            <?php
                            // Build client full name
                            $clientFullName = trim(($booking['client_first_name'] ?? '') . ' ' . ($booking['client_middle_name'] ?? '') . ' ' . ($booking['client_last_name'] ?? ''));
                            if ($clientFullName === '')
                                $clientFullName = '—';

                            // Build applicant full name
                            $applicantFullName = getFullName(
                                $booking['app_first_name'] ?? '',
                                $booking['app_middle_name'] ?? '',
                                $booking['app_last_name'] ?? '',
                                $booking['app_suffix'] ?? ''
                            );

                            // Contract term (appointment date + time)
                            $apptDate = !empty($booking['appointment_date']) ? formatDate($booking['appointment_date']) : '—';
                            $apptTime = !empty($booking['appointment_time']) ? date('h:i A', strtotime($booking['appointment_time'])) : '';
                            $contractTerm = trim($apptDate . ' ' . $apptTime);

                            // Client contact
                            $clientContact = '';
                            if (!empty($booking['client_phone']))
                                $clientContact .= $booking['client_phone'];
                            if (!empty($booking['client_email']))
                                $clientContact .= (!empty($clientContact) ? ' / ' : '') . $booking['client_email'];
                            if ($clientContact === '')
                                $clientContact = '—';

                            // Booked date
                            $bookedDate = !empty($booking['booking_created_at']) ? formatDateTime($booking['booking_created_at']) : '—';

                            // View applicant link based on status
                            $appStatus = $booking['applicant_status'] ?? 'pending';
                            if ($appStatus === 'on_process') {
                                $viewApplicantUrl = 'view_onprocess.php?id=' . (int) ($booking['applicant_id'] ?? 0);
                            } elseif ($appStatus === 'approved') {
                                $viewApplicantUrl = 'view_approved.php?id=' . (int) ($booking['applicant_id'] ?? 0);
                            } else {
                                $viewApplicantUrl = 'view-applicant.php?id=' . (int) ($booking['applicant_id'] ?? 0);
                            }

                            // Status badge - using applicant status
                            $status = $booking['applicant_status'] ?? 'pending';
                            $statusColors = [
                                'pending' => 'warning',
                                'on_process' => 'info',
                                'approved' => 'success'
                            ];
                            $badgeColor = $statusColors[$status] ?? 'secondary';

                            // Appointment type
                            $apptType = $booking['appointment_type'] ?? '—';
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?php echo safe($clientFullName); ?></div>
                                    <div class="text-muted small text-truncate" style="max-width: 200px;"
                                        title="<?php echo safe($booking['client_address'] ?? ''); ?>">
                                        <?php echo safe($booking['client_address'] ?? '—'); ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="<?php echo safe($viewApplicantUrl); ?>" class="text-decoration-none">
                                        <div class="fw-semibold text-primary"><?php echo safe($applicantFullName); ?></div>
                                    </a>
                                    <div class="text-muted small">ID: <?php echo (int) ($booking['applicant_id'] ?? 0); ?></div>
                                </td>
                                <td>
                                    <div class="small"><?php echo safe($clientContact); ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?php echo safe($apptType); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info"
                                        style="background-color: #cffafe; color: #0891b2;">
                                        <i class="bi bi-calendar-event me-1"></i><?php echo safe($contractTerm); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $badgeColor; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($booking['business_unit_name'])): ?>
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            <?php echo safe($booking['business_unit_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="small text-muted"><?php echo safe($bookedDate); ?></span>
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