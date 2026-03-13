<?php
/* SMC and CSNK employees both use client-profile.php with agency-based filtering */
// Start session explicitly
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

// Get user agency from session
$userAgency = isset($_SESSION['agency']) ? strtolower($_SESSION['agency']) : '';

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

    // Security check: Ensure the user has access to this booking based on agency
    // Get the business_unit_id from the initial booking and check its agency
    $initialBuId = $initialBooking['business_unit_id'] ?? null;
    if ($initialBuId && $conn instanceof mysqli) {
        // Get the agency_id of the business unit
        $buSql = "SELECT agency_id FROM business_units WHERE id = ?";
        $buStmt = $conn->prepare($buSql);
        if ($buStmt) {
            $buStmt->bind_param("i", $initialBuId);
            $buStmt->execute();
            $buResult = $buStmt->get_result();
            $buInfo = $buResult->fetch_assoc();
            $buStmt->close();

            if ($buInfo) {
                $bookingAgencyId = (int) $buInfo['agency_id'];

                // Check if user has access to this agency's bookings
                if ($userAgency === 'csnk' && $bookingAgencyId !== 1) {
                    setFlashMessage('error', 'You do not have permission to view this client booking.');
                    redirect('client-management.php');
                    exit;
                } elseif ($userAgency === 'smc' && $bookingAgencyId !== 2) {
                    setFlashMessage('error', 'You do not have permission to view this client booking.');
                    redirect('client-management.php');
                    exit;
                }
            }
        }
    }

    // Get client identification details
    $clientFirstName = $initialBooking['client_first_name'];
    $clientMiddleName = $initialBooking['client_middle_name'] ?? '';
    $clientLastName = $initialBooking['client_last_name'];
    $clientPhone = $initialBooking['client_phone'];
    $clientEmail = $initialBooking['client_email'];

    // Now find all bookings for this client (same client name, phone, or email)
    // We'll use a combination approach - match by name + (phone OR email)

    // Build agency filter based on user role
    $agencyFilter = '';
    if ($userAgency === 'csnk') {
        $agencyFilter = " AND bu.agency_id = 1";
    } elseif ($userAgency === 'smc') {
        $agencyFilter = " AND bu.agency_id = 2";
    }

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
        " . $agencyFilter . "
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
            " . $agencyFilter . "
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
                    glass: '0 10px 30px rgba(0,0,0,.08)',
                    elegant: '0 4px 20px rgba(0,0,0,0.08)',
                    glow: '0 0 20px rgba(59, 130, 246, 0.3)'
                },
                animation: {
                    'fade-in': 'fadeIn 0.3s ease-out',
                    'slide-up': 'slideUp 0.4s ease-out',
                },
                keyframes: {
                    fadeIn: {
                        '0%': { opacity: '0' },
                        '100%': { opacity: '1' }
                    },
                    slideUp: {
                        '0%': { opacity: '0', transform: 'translateY(10px)' },
                        '100%': { opacity: '1', transform: 'translateY(0)' }
                    }
                }
            }
        }
    }
</script>

<style>
    /* Hybrid polish for Bootstrap + Tailwind */
    .glass-card {
        backdrop-filter: blur(12px);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.85) 100%);
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
        transition: all 0.3s ease;
    }


    .soft-divider {
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(203, 213, 225, 0.5), transparent);
    }

    /* Stat Cards */
    .stat-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 14px;
        padding: 20px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--stat-color-start), var(--stat-color-end));
    }

    .stat-card.pending {
        --stat-color-start: #f59e0b;
        --stat-color-end: #d97706;
    }

    .stat-card.on-process {
        --stat-color-start: #06b6d4;
        --stat-color-end: #0891b2;
    }

    .stat-card.approved {
        --stat-color-start: #10b981;
        --stat-color-end: #059669;
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, #1e293b 0%, #475569 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .stat-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* Info Items */
    .info-item {
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 4px;
    }

    .info-value {
        font-size: 1rem;
        font-weight: 600;
        color: #1e293b;
    }

    /* Section Headers */
    .section-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 16px 20px;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid #e2e8f0;
        border-radius: 16px 16px 0 0;
    }

    .section-header-icon {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        font-size: 1.1rem;
    }

    .section-header-icon.client {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        color: #1d4ed8;
    }

    .section-header-icon.booking {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        color: #059669;
    }

    .section-header-icon.applicants {
        background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
        color: #6d28d9;
    }

    .section-title {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    /* Enhanced Table Styles */
    .enhanced-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .enhanced-table thead th {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        padding: 14px 16px;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        border-bottom: 2px solid #e2e8f0;
    }

    .enhanced-table thead th:first-child {
        border-radius: 0;
    }

    .enhanced-table thead th:last-child {
        border-radius: 0;
    }

    .enhanced-table tbody tr {
        transition: all 0.2s ease;
    }

    .enhanced-table tbody tr:hover {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.04) 0%, rgba(6, 182, 212, 0.04) 100%);
    }

    .enhanced-table tbody td {
        padding: 14px 16px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }

    /* Status Badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 12px;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 20px;
        text-transform: capitalize;
        letter-spacing: 0.02em;
    }

    .status-badge.pending {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        color: #92400e;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }

    .status-badge.on-process {
        background: linear-gradient(135deg, #cffafe 0%, #a5f3fc 100%);
        color: #0e7490;
        border: 1px solid rgba(6, 182, 212, 0.3);
    }

    .status-badge.approved {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        color: #065f46;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }

    /* Enhanced Buttons */
    .btn-export {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #fff;
        border: none;
        padding: 10px 18px;
        font-size: 0.85rem;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.2s ease;
        box-shadow: 0 2px 10px rgba(16, 185, 129, 0.3);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-export:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        color: #fff;
    }

    .btn-back {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        color: #475569;
        border: 1px solid #e2e8f0;
        padding: 10px 16px;
        font-size: 0.85rem;
        font-weight: 600;
        border-radius: 10px;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-back:hover {
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
        color: #1e293b;
        transform: translateY(-1px);
    }

    /* Badge Styles */
    .badge-type {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        color: #475569;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 8px;
    }

    .badge-schedule {
        background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
        color: #0369a1;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 8px;
    }

    .badge-agency {
        background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
        color: #6d28d9;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 8px;
    }

    /* Empty State */
    .empty-state {
        padding: 60px 20px;
    }

    .empty-state-icon {
        font-size: 4rem;
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* Row animation */
    @keyframes rowAppear {
        from {
            opacity: 0;
            transform: translateX(-10px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .table-row-animated {
        animation: rowAppear 0.3s ease-out forwards;
    }

    .small-label {
        font-size: .75rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="client-management.php" class="btn-back">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <div>
            <h4 class="mb-1 fw-bold text-dark">Client Profile</h4>
            <small class="text-muted">View client details and all booked applicants</small>
        </div>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <a href="../includes/excel_client_profile.php?id=<?php echo (int) $bookingId; ?>" class="btn-export"">
            <i class=" bi bi-file-earmark-excel"></i> Export to Excel
        </a>
        <span class="badge"
            style="background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); color: #475569; font-weight: 600; padding: 10px 16px; border-radius: 10px;">
            <i class="bi bi-bookmark-check me-1"></i> Total Bookings: <?php echo $totalBookings; ?>
        </span>
    </div>
</div>

<!-- Client Information Card -->
<div class="glass-card mb-4">
    <div class="soft-divider"></div>
    <div class="p-4">
        <div class="row">
            <div class="col-md-6">
                <div class="section-header" style="border-radius: 12px; margin-bottom: 20px;">
                    <div class="section-header-icon client">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <h5 class="section-title">Client Information</h5>
                </div>
                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="info-label"><i class="bi bi-person me-2"></i>Full Name</div>
                        <div class="info-value"><?php echo safe($clientFullName); ?></div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="info-label"><i class="bi bi-telephone me-2"></i>Phone</div>
                        <div class="info-value"><?php echo !empty($clientPhone) ? safe($clientPhone) : '—'; ?></div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="info-label"><i class="bi bi-envelope me-2"></i>Email</div>
                        <div class="info-value"><?php echo !empty($clientEmail) ? safe($clientEmail) : '—'; ?></div>
                    </div>
                    <div class="col-12">
                        <div class="info-label"><i class="bi bi-geo-alt me-2"></i>Address</div>
                        <div class="info-value"><?php echo !empty($clientAddress) ? safe($clientAddress) : '—'; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="section-header" style="border-radius: 12px; margin-bottom: 20px;">
                    <div class="section-header-icon booking">
                        <i class="bi bi-bar-chart-fill"></i>
                    </div>
                    <h5 class="section-title">Booking Summary</h5>
                </div>
                <div class="row text-center g-3">
                    <div class="col-4">
                        <div class="stat-card pending">
                            <div class="stat-number"><?php echo $statusCounts['pending']; ?></div>
                            <div class="stat-label"><i class="bi bi-clock me-1"></i>Pending</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-card on-process">
                            <div class="stat-number"><?php echo $statusCounts['on_process']; ?></div>
                            <div class="stat-label"><i class="bi bi-arrow-repeat me-1"></i>On Process</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-card approved">
                            <div class="stat-number"><?php echo $statusCounts['approved']; ?></div>
                            <div class="stat-label"><i class="bi bi-check-circle me-1"></i>Approved</div>
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
    <div class="section-header">
        <div class="section-header-icon applicants">
            <i class="bi bi-people-fill"></i>
        </div>
        <h5 class="section-title">Booked Applicants</h5>
    </div>
    <?php if (empty($clientBookings)): ?>
        <div class="empty-state text-center">
            <div class="empty-state-icon mb-3">
                <i class="bi bi-calendar-x"></i>
            </div>
            <p class="text-muted mt-3 mb-0">No applicants booked for this client.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0 enhanced-table">
                <thead>
                    <tr>
                        <th><i class="bi bi-person me-2"></i>Applicant</th>
                        <th><i class="bi bi-info-circle me-2"></i>Status</th>
                        <th><i class="bi bi-building me-2"></i>Agency</th>
                        <th><i class="bi bi-briefcase me-2"></i>Appointment</th>
                        <th><i class="bi bi-clock me-2"></i>Schedule</th>
                        <th><i class="bi bi-calendar-plus me-2"></i>Booked Date</th>
                        <th><i class="bi bi-arrow swap me-2"></i>Replaced Applicant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientBookings as $index => $booking): ?>
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
                                $replacedApplicant = '<span class="status-badge pending"><i class="bi bi-clock"></i> Pending Replacement</span>';
                            }
                        }
                        ?>
                        <tr class="table-row-animated" style="animation-delay: <?php echo $index * 0.05; ?>s;">
                            <td>
                                <a href="<?php echo safe($viewApplicantUrl); ?>" class="text-decoration-none">
                                    <div class="fw-bold text-primary"><?php echo safe($applicantFullName); ?></div>
                                </a>
                                <div class="text-muted small"><i class="bi bi-hash me-1"></i>ID:
                                    <?php echo (int) ($booking['applicant_id'] ?? 0); ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                $statusClass = '';
                                $statusIcon = '';
                                if ($appStatus === 'pending') {
                                    $statusClass = 'pending';
                                    $statusIcon = 'bi-clock';
                                } elseif ($appStatus === 'on_process') {
                                    $statusClass = 'on-process';
                                    $statusIcon = 'bi-arrow-repeat';
                                } elseif ($appStatus === 'approved') {
                                    $statusClass = 'approved';
                                    $statusIcon = 'bi-check-circle';
                                }
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <i class="bi <?php echo $statusIcon; ?>"></i>
                                    <?php echo ucfirst(str_replace('_', ' ', $appStatus)); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($booking['business_unit_name'])): ?>
                                    <span class="badge-agency">
                                        <i class="bi bi-building me-1"></i><?php echo safe($booking['business_unit_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-type">
                                    <i class="bi bi-briefcase me-1"></i><?php echo safe($apptType); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-schedule">
                                    <i class="bi bi-calendar-event me-1"></i><?php echo safe($contractTerm); ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-muted small"><i
                                        class="bi bi-calendar me-1"></i><?php echo safe($bookedDate); ?></span>
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

<?php require_once '../includes/footer.php'; ?>