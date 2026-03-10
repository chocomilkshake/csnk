<?php
/* BLOCK SMC employees from accessing CSNK dashboard - must be before any output */
// Start session explicitly
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user agency is SMC
$userAgencyCheck = isset($_SESSION['agency']) ? strtolower($_SESSION['agency']) : '';

if ($userAgencyCheck === 'smc') {
    // SMC employees should use turkey_dashboard.php - redirect immediately
    header('Location: turkey_dashboard.php');
    exit;
}

$pageTitle = 'Client Profile';
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

// Get booking ID from URL
$bookingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($bookingId === 0) {
    setFlashMessage('error', 'Invalid client booking ID.');
    redirect('client-management.php');
    exit;
}

$conn = $database->getConnection();

// Get the initial booking to identify the client
$initialBooking = null;
$clientBookings = [];

if ($conn instanceof mysqli) {
    // First, get the booking details to identify the client
    $initialSql = "SELECT * FROM client_bookings WHERE id = ?";
    $stmt = $conn->prepare($initialSql);
    if ($stmt) {
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $initialBooking = $result->fetch_assoc();
        $stmt->close();
    }

    if (!$initialBooking) {
        setFlashMessage('error', 'Booking not found.');
        redirect('client-management.php');
        exit;
    }

    // Get client identification details
    $clientFirstName = $initialBooking['client_first_name'];
    $clientMiddleName = $initialBooking['client_middle_name'] ?? '';
    $clientLastName = $initialBooking['client_last_name'];
    $clientPhone = $initialBooking['client_phone'];
    $clientEmail = $initialBooking['client_email'];

    // Now find all bookings for this client (same client name, phone, or email)
    // We'll use a combination approach - match by name + (phone OR email)
    $searchSql = "
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
            a.status AS applicant_status,
            bu.name AS business_unit_name
        FROM client_bookings cb
        INNER JOIN applicants a ON a.id = cb.applicant_id
        LEFT JOIN business_units bu ON bu.id = cb.business_unit_id
        WHERE (
            (cb.client_first_name = ? AND cb.client_last_name = ?)
            AND (cb.client_phone = ? OR cb.client_email = ?)
        )
        ORDER BY cb.created_at DESC
    ";

    $stmt = $conn->prepare($searchSql);
    if ($stmt) {
        $stmt->bind_param("ssss", $clientFirstName, $clientLastName, $clientPhone, $clientEmail);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $clientBookings[] = $row;
        }
        $stmt->close();
    }

    // If no matches by phone/email, try by name only
    if (empty($clientBookings)) {
        $searchSqlNameOnly = "
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
                a.status AS applicant_status,
                bu.name AS business_unit_name
            FROM client_bookings cb
            INNER JOIN applicants a ON a.id = cb.applicant_id
            LEFT JOIN business_units bu ON bu.id = cb.business_unit_id
            WHERE cb.client_first_name = ? AND cb.client_last_name = ?
            ORDER BY cb.created_at DESC
        ";

        $stmt = $conn->prepare($searchSqlNameOnly);
        if ($stmt) {
            $stmt->bind_param("ss", $clientFirstName, $clientLastName);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $clientBookings[] = $row;
            }
            $stmt->close();
        }
    }

    // Get replacement information for all bookings
    $replacementMap = [];
    if (!empty($clientBookings)) {
        $bookingIds = array_column($clientBookings, 'booking_id');
        $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));

        $replacementSql = "
            SELECT 
                ar.id,
                ar.client_booking_id,
                ar.original_applicant_id,
                ar.replacement_applicant_id,
                ar.status AS replacement_status,
                ar.created_at,
                ao.first_name AS original_first_name,
                ao.middle_name AS original_middle_name,
                ao.last_name AS original_last_name,
                ra.first_name AS replacement_first_name,
                ra.middle_name AS replacement_middle_name,
                ra.last_name AS replacement_last_name
            FROM applicant_replacements ar
            LEFT JOIN applicants ao ON ao.id = ar.original_applicant_id
            LEFT JOIN applicants ra ON ra.id = ar.replacement_applicant_id
            WHERE ar.client_booking_id IN ($placeholders)
            ORDER BY ar.created_at DESC
        ";

        $stmt = $conn->prepare($replacementSql);
        if ($stmt) {
            $types = str_repeat('i', count($bookingIds));
            $stmt->bind_param($types, ...$bookingIds);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $replacementMap[$row['client_booking_id']] = $row;
            }
            $stmt->close();
        }
    }
}

// Helpers
function safe(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

// Build client full name from initial booking
$clientFullName = trim(($initialBooking['client_first_name'] ?? '') . ' ' . ($initialBooking['client_middle_name'] ?? '') . ' ' . ($initialBooking['client_last_name'] ?? ''));
if ($clientFullName === '')
    $clientFullName = '—';

// Client contact info
$clientPhone = $initialBooking['client_phone'] ?? '';
$clientEmail = $initialBooking['client_email'] ?? '';
$clientAddress = $initialBooking['client_address'] ?? '';

// Get status counts
$statusCounts = ['pending' => 0, 'on_process' => 0, 'approved' => 0];
foreach ($clientBookings as $cb) {
    $status = $cb['applicant_status'] ?? 'pending';
    if (isset($statusCounts[$status])) {
        $statusCounts[$status]++;
    }
}
$totalBookings = count($clientBookings);
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
    <div class="d-flex align-items-center gap-3">
        <a href="client-management.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h4 class="mb-1 fw-semibold">Client Profile</h4>
            <small class="text-muted">View client details and all booked applicants</small>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="../includes/excel_client_profile.php?id=<?php echo (int) $bookingId; ?>" class="btn btn-success"
            target="_blank">
            <i class="bi bi-file-earmark-excel me-1"></i> Export to Excel
        </a>
        <span class="badge bg-secondary">
            Total Bookings: <?php echo $totalBookings; ?>
        </span>
    </div>
</div>

<!-- Client Information Card -->
<div class="glass-card mb-4">
    <div class="soft-divider"></div>
    <div class="p-4">
        <div class="row">
            <div class="col-md-6">
                <h5 class="fw-semibold mb-3">
                    <i class="bi bi-person-fill me-2 text-primary"></i>Client Information
                </h5>
                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="small-label text-muted">Full Name</div>
                        <div class="fw-semibold"><?php echo safe($clientFullName); ?></div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="small-label text-muted">Phone</div>
                        <div class="fw-semibold"><?php echo !empty($clientPhone) ? safe($clientPhone) : '—'; ?></div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="small-label text-muted">Email</div>
                        <div class="fw-semibold"><?php echo !empty($clientEmail) ? safe($clientEmail) : '—'; ?></div>
                    </div>
                    <div class="col-12">
                        <div class="small-label text-muted">Address</div>
                        <div class="fw-semibold"><?php echo !empty($clientAddress) ? safe($clientAddress) : '—'; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <h5 class="fw-semibold mb-3">
                    <i class="bi bi-bar-chart-fill me-2 text-primary"></i>Booking Summary
                </h5>
                <div class="row text-center">
                    <div class="col-4">
                        <div class="glass-card p-3">
                            <div class="h4 mb-0 text-warning"><?php echo $statusCounts['pending']; ?></div>
                            <small class="text-muted">Pending</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="glass-card p-3">
                            <div class="h4 mb-0 text-info"><?php echo $statusCounts['on_process']; ?></div>
                            <small class="text-muted">On Process</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="glass-card p-3">
                            <div class="h4 mb-0 text-success"><?php echo $statusCounts['approved']; ?></div>
                            <small class="text-muted">Approved</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Applicants Table -->
<div class="glass-card">
    <div class="soft-divider"></div>
    <div class="p-0">
        <div class="p-3 bg-light border-bottom">
            <h5 class="mb-0 fw-semibold">
                <i class="bi bi-people-fill me-2"></i>Booked Applicants
            </h5>
        </div>
        <?php if (empty($clientBookings)): ?>
            <div class="text-center py-5">
                <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-3 mb-0">No applicants booked for this client.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Status</th>
                            <th>Agency</th>
                            <th>Appointment</th>
                            <th>Schedule</th>
                            <th>Booked Date</th>
                            <th>Replaced Applicant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientBookings as $booking): ?>
                            <?php
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

                            // Status badge
                            $statusColors = [
                                'pending' => 'warning',
                                'on_process' => 'info',
                                'approved' => 'success'
                            ];
                            $badgeColor = $statusColors[$appStatus] ?? 'secondary';

                            // Appointment type
                            $apptType = $booking['appointment_type'] ?? '—';

                            // Replacement info
                            $replacedApplicant = '—';
                            $bookingIdKey = $booking['booking_id'];
                            if (isset($replacementMap[$bookingIdKey])) {
                                $rep = $replacementMap[$bookingIdKey];
                                if (!empty($rep['replacement_applicant_id'])) {
                                    // This booking has a replacement - show original applicant
                                    $replacedApplicant = getFullName(
                                        $rep['original_first_name'] ?? '',
                                        $rep['original_middle_name'] ?? '',
                                        $rep['original_last_name'] ?? ''
                                    );
                                } elseif (!empty($rep['original_applicant_id'])) {
                                    // Replacement in progress - show as "Pending Replacement"
                                    $replacedApplicant = '<span class="badge bg-warning">Pending Replacement</span>';
                                }
                            }
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo safe($viewApplicantUrl); ?>" class="text-decoration-none">
                                        <div class="fw-semibold text-primary"><?php echo safe($applicantFullName); ?></div>
                                    </a>
                                    <div class="text-muted small">ID: <?php echo (int) ($booking['applicant_id'] ?? 0); ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $badgeColor; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $appStatus)); ?>
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
                                    <span class="badge bg-light text-dark">
                                        <?php echo safe($apptType); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info"
                                        style="background-color: #c3c1c1; color: #303030;">
                                        <i class="bi bi-calendar-event me-1"></i><?php echo safe($contractTerm); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="small text-muted"><?php echo safe($bookedDate); ?></span>
                                </td>
                                <td>
                                    <?php echo $replacedApplicant; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .small-label {
        font-size: .75rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
</style>

<?php require_once '../includes/footer.php'; ?>