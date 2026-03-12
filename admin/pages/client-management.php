<?php
/* SMC and CSNK employees both use client-management.php with agency-based filtering */
// Start session explicitly
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

// Get user agency from session
$userAgency = isset($_SESSION['agency']) ? strtolower($_SESSION['agency']) : '';

/* ---------------------- Client Bookings (All Statuses) ---------------------- */
$clientBookings = [];
$bookingSearch = isset($_GET['booking_search']) ? trim($_GET['booking_search']) : '';
$applicantStatus = isset($_GET['status']) ? $_GET['status'] : 'all';

$conn = $database->getConnection();
if ($conn instanceof mysqli) {
    // Build agency filter based on user role
    // CSNK employee -> agency_id = 1, SMC employee -> agency_id = 2, Admin/Super Admin -> all
    $agencyFilter = '';
    if ($userAgency === 'csnk') {
        // Filter to CSNK agencies only (agency_id = 1)
        $agencyFilter = " AND bu.agency_id = 1";
    } elseif ($userAgency === 'smc') {
        // Filter to SMC agencies only (agency_id = 2)
        $agencyFilter = " AND bu.agency_id = 2";
    }
    // If admin/super_admin or no agency, show all (empty filter)

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

    // Apply agency filter for employees
    $bookingSql .= $agencyFilter;

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

    // Get counts for badges based on applicant status - with agency filter
    $countSql = "SELECT a.status, COUNT(*) as count 
                 FROM client_bookings cb 
                 INNER JOIN applicants a ON a.id = cb.applicant_id
                 LEFT JOIN business_units bu ON bu.id = cb.business_unit_id
                 WHERE 1=1";

    // Apply agency filter to counts as well
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

    /* Filter Tabs - Modern Pill Design */
    .filter-tabs {
        display: flex;
        gap: 6px;
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        padding: 6px;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .filter-tab {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 18px;
        font-size: 0.875rem;
        font-weight: 600;
        color: #64748b;
        text-decoration: none;
        border-radius: 10px;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        background: transparent;
        border: 1px solid transparent;
    }

    .filter-tab:hover {
        color: #334155;
        background: #fff;
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .filter-tab.active {
        color: #fff;
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        border-color: transparent;
        box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
    }

    .filter-tab.active.on-process {
        background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        box-shadow: 0 4px 15px rgba(6, 182, 212, 0.4);
    }

    .filter-tab.active.approved {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
    }

    .filter-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 24px;
        padding: 0 7px;
        font-size: 0.75rem;
        font-weight: 700;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.25);
        letter-spacing: 0.02em;
    }

    .filter-tab:not(.active) .filter-badge {
        background: #e2e8f0;
        color: #64748b;
        font-weight: 600;
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
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .enhanced-table thead th:first-child {
        border-radius: 12px 0 0 0;
    }

    .enhanced-table thead th:last-child {
        border-radius: 0 12px 0 0;
    }


    .enhanced-table tbody td {
        padding: 14px 16px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }

    .enhanced-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Status Badges - Enhanced */
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
    .btn-view {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: #fff;
        border: none;
        padding: 8px 14px;
        font-size: 0.8rem;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
    }

    .btn-view:hover {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
        color: #fff;
    }

    /* Search Input Enhancement */
    .search-input-wrapper {
        position: relative;
    }

    .search-input-wrapper .search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 1rem;
        z-index: 10;
    }

    .search-input-wrapper input {
        padding-left: 42px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        transition: all 0.25s ease;
        background: #fff;
    }

    .search-input-wrapper input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        outline: none;
    }

    /* Empty State Enhancement */
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

    /* Card header gradient */
    .card-header-gradient {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid #e2e8f0;
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

    .table-row-animated:nth-child(1) {
        animation-delay: 0.02s;
    }

    .table-row-animated:nth-child(2) {
        animation-delay: 0.04s;
    }

    .table-row-animated:nth-child(3) {
        animation-delay: 0.06s;
    }

    .table-row-animated:nth-child(4) {
        animation-delay: 0.08s;
    }

    .table-row-animated:nth-child(5) {
        animation-delay: 0.1s;
    }

    .table-row-animated:nth-child(6) {
        animation-delay: 0.12s;
    }

    .table-row-animated:nth-child(7) {
        animation-delay: 0.14s;
    }

    .table-row-animated:nth-child(8) {
        animation-delay: 0.16s;
    }

    .table-row-animated:nth-child(9) {
        animation-delay: 0.18s;
    }

    .table-row-animated:nth-child(10) {
        animation-delay: 0.2s;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-semibold">Client Management</h4>
        <small class="text-muted">Manage all client bookings and contracts</small>
    </div>
    <div class="filter-tabs">
        <a href="client-management.php" class="filter-tab <?php echo $applicantStatus === 'all' ? 'active' : ''; ?>">
            All <span class="filter-badge"><?php echo $statusCounts['total']; ?></span>
        </a>
        <a href="?status=on_process"
            class="filter-tab <?php echo $applicantStatus === 'on_process' ? 'active on-process' : ''; ?>">
            On Process <span class="filter-badge"><?php echo $statusCounts['on_process']; ?></span>
        </a>
        <a href="?status=approved"
            class="filter-tab <?php echo $applicantStatus === 'approved' ? 'active approved' : ''; ?>">
            Approved <span class="filter-badge"><?php echo $statusCounts['approved']; ?></span>
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
            <div class="empty-state text-center">
                <div class="empty-state-icon mb-3">
                    <i class="bi bi-calendar-x"></i>
                </div>
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
                <table class="table align-middle mb-0 enhanced-table">
                    <thead>
                        <tr>
                            <th style="width: 14%;">
                                <i class="bi bi-person me-2"></i>Client
                            </th>
                            <th style="width: 14%;">
                                <i class="bi bi-people me-2"></i>Applicant
                            </th>
                            <th style="width: 10%;">
                                <i class="bi bi-telephone me-2"></i>Contact
                            </th>
                            <th style="width: 6%;">
                                <i class="bi bi-calendar-check me-2"></i>Appointment
                            </th>
                            <th style="width: 6%;">
                                <i class="bi bi-clock me-2"></i>Schedule
                            </th>
                            <th style="width: 8%;">
                                <i class="bi bi-info-circle me-2"></i>Status
                            </th>
                            <th style="width: 8%;">
                                <i class="bi bi-building me-2"></i>Agency
                            </th>
                            <th style="width: 8%;">
                                <i class="bi bi-calendar-plus me-2"></i>Booked Date
                            </th>
                            <th style="width: 8%;">
                                <i class="bi bi-gear me-2"></i>Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientBookings as $index => $booking): ?>
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

                            // Appointment type
                            $apptType = $booking['appointment_type'] ?? '—';
                            ?>
                            <tr class="table-row-animated" style="animation-delay: <?php echo $index * 0.03; ?>s;">
                                <td>
                                    <div class="fw-bold text-dark"><?php echo safe($clientFullName); ?></div>
                                    <div class="text-muted small text-truncate" style="max-width: 200px;"
                                        title="<?php echo safe($booking['client_address'] ?? ''); ?>">
                                        <i class="bi bi-geo-alt me-1"></i><?php echo safe($booking['client_address'] ?? '—'); ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="<?php echo safe($viewApplicantUrl); ?>" class="text-decoration-none">
                                        <div class="fw-semibold text-primary fw-bold"><?php echo safe($applicantFullName); ?>
                                        </div>
                                    </a>
                                    <div class="text-muted small"><i class="bi bi-hash me-1"></i>ID:
                                        <?php echo (int) ($booking['applicant_id'] ?? 0); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="small"><i
                                            class="bi bi-telephone me-1 text-muted"></i><?php echo safe($clientContact); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge"
                                        style="background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); color: #475569; font-weight: 600;">
                                        <i class="bi bi-briefcase me-1"></i><?php echo safe($apptType); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge"
                                        style="background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); color: #0369a1; font-weight: 600;">
                                        <i class="bi bi-calendar-event me-1"></i><?php echo safe($contractTerm); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = '';
                                    $statusIcon = '';
                                    if ($status === 'pending') {
                                        $statusClass = 'pending';
                                        $statusIcon = 'bi-clock';
                                    } elseif ($status === 'on_process') {
                                        $statusClass = 'on-process';
                                        $statusIcon = 'bi-arrow-repeat';
                                    } elseif ($status === 'approved') {
                                        $statusClass = 'approved';
                                        $statusIcon = 'bi-check-circle';
                                    }
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <i class="bi <?php echo $statusIcon; ?>"></i>
                                        <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($booking['business_unit_name'])): ?>
                                        <span class="badge"
                                            style="background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%); color: #6d28d9; font-weight: 600;">
                                            <i class="bi bi-building me-1"></i><?php echo safe($booking['business_unit_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="small text-muted"><i
                                            class="bi bi-calendar me-1"></i><?php echo safe($bookedDate); ?></span>
                                </td>
                                <td>
                                    <a href="client-profile.php?id=<?php echo (int) ($booking['booking_id'] ?? 0); ?>"
                                        class="btn-view" title="View Client Profile">
                                        <i class="bi bi-person-lines-fill me-1"></i> View
                                    </a>
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