<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/functions.php';

$database = new Database();
$auth     = new Auth($database);
$auth->requireLogin();

$currentUser = $auth->getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Role flags
$currentRole     = $currentUser['role'] ?? 'employee';
$isSuperAdmin    = ($currentRole === 'super_admin');
$isAdmin         = ($currentRole === 'admin');
$isEmployee      = ($currentRole === 'employee');
$canViewActivity = ($isAdmin || $isSuperAdmin);

// Coming soon regions
$showRegionPlaceholders = true;

/* ---------- Counts for sidebar ---------- */
$conn = $database->getConnection();
function csnk_count($conn, string $sql): int {
    if (!($conn instanceof mysqli)) return 0;
    $res = $conn->query($sql);
    if ($res) {
        $row = $res->fetch_row();
        return (int)($row[0] ?? 0);
    }
    return 0;
}

/* Exclude actively blacklisted applicants */
$notBlacklisted = " AND NOT EXISTS (
    SELECT 1 FROM blacklisted_applicants b
    WHERE b.applicant_id = applicants.id AND b.is_active = 1
)";

$totalApplicants  = csnk_count($conn, "SELECT COUNT(*) FROM applicants WHERE deleted_at IS NULL{$notBlacklisted}");
$pendingCount     = csnk_count($conn, "SELECT COUNT(*) FROM applicants WHERE status='pending' AND deleted_at IS NULL{$notBlacklisted}");
$onProcessCount   = csnk_count($conn, "SELECT COUNT(*) FROM applicants WHERE status='on_process' AND deleted_at IS NULL{$notBlacklisted}");
$approvedCount    = csnk_count($conn, "SELECT COUNT(*) FROM applicants WHERE status='approved' AND deleted_at IS NULL{$notBlacklisted}");
$deletedCount     = csnk_count($conn, "SELECT COUNT(*) FROM applicants WHERE deleted_at IS NOT NULL{$notBlacklisted}");

/* Active blacklisted applicants (visible to admin/super_admin) */
$blacklistedCount = csnk_count($conn, "SELECT COUNT(*) FROM blacklisted_applicants WHERE is_active = 1");

/* ---------- Recent bookings for bell dropdown ---------- */
$recentBookings = [];
if ($conn instanceof mysqli) {
    $sqlBookings = "
        SELECT cb.id,
               cb.applicant_id,
               cb.created_at,
               cb.client_first_name,
               cb.client_last_name,
               cb.appointment_type,
               a.first_name, a.middle_name, a.last_name, a.suffix, a.status
        FROM client_bookings AS cb
        LEFT JOIN applicants AS a ON cb.applicant_id = a.id
        WHERE a.status = 'on_process'
        ORDER BY cb.created_at DESC
        LIMIT 5
    ";
    if ($res = $conn->query($sqlBookings)) {
        $recentBookings = $res->fetch_all(MYSQLI_ASSOC);
    }
}

/* ---------- Sidebar submenu state ---------- */
$isApplicantsActive   = in_array($currentPage, ['applicants','pending','on-process','approved','deleted','blacklisted','blacklisted-view'], true);
$collapseApplicantsId = 'csnkApplicantsMenu';

/* ---------- Escape helper ---------- */
function h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/* ---------- Safe default for reports counter ---------- */
$reportNotesCount = (int)($reportNotesCount ?? 0);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle ?? 'Dashboard'); ?> - <?php echo h(APP_NAME); ?></title>
    <link rel="icon" type="image/png" href="/csnk/resources/img/csnk-iconz.png">
    <link rel="apple-touch-icon" href="/csnk/resources/img/favicons/apple-touch-icon-180.png">
    <link rel="icon" href="/csnk/resources/img/csnk-iconz.ico">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/csnk/resources/img/csnk-icon.png">

    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        :root {
            --sidebar-width: 300px;

            /* CSNK brand core */
            --csnk-red: #c40000;
            --csnk-red-700: #991b1b;

            /* Neutral foundation */
            --csnk-gray-25: #f9fafb;
            --csnk-gray-50: #f8fafc;
            --csnk-gray-100: #f1f5f9;
            --csnk-gray-200: #e5e7eb;
            --csnk-gray-300: #e2e8f0;
            --csnk-gray-400: #94a3b8;
            --csnk-gray-600: #475569;

            /* Soft ring / elevation */
            --csnk-ring: rgba(196, 0, 0, 0.14);
            --csnk-shadow-sm: 0 1px 2px rgba(0,0,0,.06);
            --csnk-shadow-md: 0 6px 16px rgba(0,0,0,.12);

            /* Accent gradient for badges */
            --csnk-accent-bg: linear-gradient(180deg, #d81515 0%, var(--csnk-red) 100%);
            --csnk-accent-bg-hover: linear-gradient(180deg, #b51212 0%, var(--csnk-red-700) 100%);
        }

        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100vh; width: var(--sidebar-width);
            background: #fff; border-right: 1px solid var(--csnk-gray-200); overflow-y: auto; z-index: 1000;
        }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; background: #f8f9fa; transition: margin-left .2s ease; }
        .navbar-top { background: #fff; border-bottom: 1px solid var(--csnk-gray-200); padding: .75rem 1rem; position: sticky; top: 0; z-index: 1020; }
        .content-wrapper { padding: 1.25rem; }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }

        .sidebar-brand {
            padding: 1rem .75rem; border-bottom: 1px solid var(--csnk-gray-200);
            display:flex; align-items:center; justify-content:center; gap:.5rem;
        }
        .sidebar-brand img { max-width: 160px; height: auto; }

        .sidebar-section-label {
            display:flex; align-items:center; gap:.5rem; padding:.75rem 1rem .35rem 1rem; color: var(--csnk-gray-600);
            text-transform: uppercase; font-size: .72rem; letter-spacing:.08em; font-weight:700;
        }
        .sidebar-section-label .region-icon {
            width: 40px; height: 40px; object-fit: contain; opacity: .95;
        }

        .sidebar-menu { padding: .25rem 0 1.25rem 0; }
        .sidebar-item {
            position: relative; display: flex; align-items: center; gap: .75rem;
            padding: .65rem 1rem; color: #475569; text-decoration: none;
            transition: background .15s ease, color .15s ease, border-left-color .15s ease;
            border-left: 3px solid transparent; border-radius: 0;
        }
        .sidebar-item:hover { background: var(--csnk-gray-50); color: #1e293b; }
        .sidebar-item.active { background: #eff6ff; color: #1d4ed8; border-left-color: #1d4ed8; }
        .sidebar-item i { width: 22px; text-align:center; font-size:1.05rem; }
        .sidebar-item .label { display: inline-flex; align-items: center; gap: .6rem; min-width: 0; }
        .sidebar-item .text { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .sidebar-toggle { width: 100%; background: transparent; border: 0; text-align: left; }
        .sidebar-submenu .sidebar-item { padding-left: 2.25rem; }

        .side-badge { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); }

        .pill-count {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 28px; height: 22px; padding: 0 .55rem; border-radius: 999px;
            background: var(--csnk-accent-bg); color: #fff; font-weight: 900; font-size: .78rem; line-height: 1;
            letter-spacing: .2px; border: 1px solid rgba(0,0,0,.08);
            box-shadow: 0 1px 0 rgba(255,255,255,.35) inset, 0 1px 0 rgba(0,0,0,.05);
            font-variant-numeric: tabular-nums;
        }
        .sidebar-item:hover .pill-count { background: var(--csnk-accent-bg-hover); }
        .pill-count.is-zero { background: #e9eef5; color: #6b7280; border-color: rgba(0,0,0,.05); }
        .sidebar-item.active .pill-count {
            box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px var(--csnk-ring),
                        0 1px 0 rgba(255,255,255,.35) inset, 0 1px 0 rgba(0,0,0,.05);
        }

        body.sidebar-collapsed .sidebar { width: var(--sidebar-collapsed-width); }
        body.sidebar-collapsed .main-content { margin-left: var(--sidebar-collapsed-width); }
        body.sidebar-collapsed .sidebar-item .text,
        body.sidebar-collapsed .sidebar-section-label,
        body.sidebar-collapsed .side-badge { display: none !important; }
        body.sidebar-collapsed .sidebar-brand img { max-width: 42px; }
        body.sidebar-collapsed .sidebar-submenu { display: none !important; }

        /* Keep collapse content visible - prevent overflow clipping */
        .sidebar .collapse { overflow: visible; }
        .sidebar .collapse.show { display: block; visibility: visible; }

        .sidebar-divider { height: 1px; margin: .5rem 1rem; background: var(--csnk-gray-200); }
        .nav-left { display:flex; align-items:center; gap:.5rem; }

        @media (max-width: 992px) {
            .sidebar { transform: translateX(0); transition: transform .2s ease; }
            body.sidebar-hidden .sidebar { transform: translateX(-100%); }
            body.sidebar-hidden .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="csnkSidebar">
        <div class="sidebar-brand text-center">
            <img src="../resources/img/csnklogo.png" alt="CSNK Logo" class="img-fluid d-block mx-auto rounded-2">
        </div>

        <nav class="sidebar-menu" aria-label="Primary">
            <!-- Dashboard -->
            <a href="dashboard.php"
               class="sidebar-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>"
               aria-current="<?php echo $currentPage === 'dashboard' ? 'page' : 'false'; ?>"
               data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard">
                <i class="bi bi-speedometer2"></i>
                <span class="label"><span class="text">Dashboard</span></span>
            </a>

            <!-- Applicants -->
            <div class="sidebar-section-label">Applicants</div>

            <!-- CSNK-Philippines dropdown -->
            <button
                class="sidebar-item sidebar-toggle <?php echo $isApplicantsActive ? 'active' : ''; ?>"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#<?php echo h($collapseApplicantsId); ?>"
                aria-expanded="<?php echo $isApplicantsActive ? 'true' : 'false'; ?>"
                aria-controls="<?php echo h($collapseApplicantsId); ?>"
                data-bs-placement="right" title="CSNK-Philippines">
                <i class="bi bi-geo-alt"></i>
                <span class="label"><span class="text">CSNK-Philippines</span></span>
                <span class="side-badge"><i class="bi bi-chevron-down"></i></span>
            </button>

            <div class="collapse <?php echo $isApplicantsActive ? 'show' : ''; ?> sidebar-submenu" id="<?php echo h($collapseApplicantsId); ?>">
                <a href="applicants.php"
                   class="sidebar-item <?php echo $currentPage === 'applicants' ? 'active' : ''; ?>"
                   aria-current="<?php echo $currentPage === 'applicants' ? 'page' : 'false'; ?>"
                   data-bs-toggle="tooltip" data-bs-placement="right" title="List of Applicants">
                    <i class="bi bi-people"></i>
                    <span class="label"><span class="text">List of Applicants</span></span>
                    <span class="side-badge">
                        <span class="pill-count <?php echo $totalApplicants === 0 ? 'is-zero' : ''; ?>"
                              aria-label="Total applicants count"><?php echo (int)$totalApplicants; ?></span>
                    </span>
                </a>

                <a href="pending.php"
                   class="sidebar-item <?php echo $currentPage === 'pending' ? 'active' : ''; ?>"
                   aria-current="<?php echo $currentPage === 'pending' ? 'page' : 'false'; ?>"
                   data-bs-toggle="tooltip" data-bs-placement="right" title="Pending Applicants">
                    <i class="bi bi-clock-history"></i>
                    <span class="label"><span class="text">Pending</span></span>
                    <span class="side-badge">
                        <span class="pill-count <?php echo $pendingCount === 0 ? 'is-zero' : ''; ?>"
                              aria-label="Pending applicants count"><?php echo (int)$pendingCount; ?></span>
                    </span>
                </a>

                <a href="on-process.php"
                   class="sidebar-item <?php echo $currentPage === 'on-process' ? 'active' : ''; ?>"
                   aria-current="<?php echo $currentPage === 'on-process' ? 'page' : 'false'; ?>"
                   data-bs-toggle="tooltip" data-bs-placement="right" title="On Process Applicants">
                    <i class="bi bi-hourglass-split"></i>
                    <span class="label"><span class="text">On Process</span></span>
                    <span class="side-badge">
                        <span class="pill-count <?php echo $onProcessCount === 0 ? 'is-zero' : ''; ?>"
                              aria-label="On process applicants count"><?php echo (int)$onProcessCount; ?></span>
                    </span>
                </a>

                <a href="approved.php"
                   class="sidebar-item <?php echo $currentPage === 'approved' ? 'active' : ''; ?>"
                   aria-current="<?php echo $currentPage === 'approved' ? 'page' : 'false'; ?>"
                   data-bs-toggle="tooltip" data-bs-placement="right" title="Approved Applicants">
                    <i class="bi bi-check-circle"></i>
                    <span class="label"><span class="text">Approved</span></span>
                    <span class="side-badge">
                        <span class="pill-count <?php echo $approvedCount === 0 ? 'is-zero' : ''; ?>"
                              aria-label="Approved applicants count"><?php echo (int)$approvedCount; ?></span>
                    </span>
                </a>

                <a href="deleted.php"
                   class="sidebar-item <?php echo $currentPage === 'deleted' ? 'active' : ''; ?>"
                   aria-current="<?php echo $currentPage === 'deleted' ? 'page' : 'false'; ?>"
                   data-bs-toggle="tooltip" data-bs-placement="right" title="Deleted Applicants">
                    <i class="bi bi-trash"></i>
                    <span class="label"><span class="text">Deleted</span></span>
                    <span class="side-badge">
                        <span class="pill-count <?php echo $deletedCount === 0 ? 'is-zero' : ''; ?>"
                              aria-label="Deleted applicants count"><?php echo (int)$deletedCount; ?></span>
                    </span>
                </a>

                <?php if ($isAdmin || $isSuperAdmin): ?>
                <a href="blacklisted.php"
                   class="sidebar-item <?php echo $currentPage === 'blacklisted' ? 'active' : ''; ?>"
                   aria-current="<?php echo $currentPage === 'blacklisted' ? 'page' : 'false'; ?>"
                   data-bs-toggle="tooltip" data-bs-placement="right" title="Blacklisted Applicants">
                    <i class="bi bi-slash-circle"></i>
                    <span class="label"><span class="text">Blacklisted</span></span>
                    <span class="side-badge">
                        <span class="pill-count <?php echo $blacklistedCount === 0 ? 'is-zero' : ''; ?>"
                              aria-label="Blacklisted applicants count"><?php echo (int)$blacklistedCount; ?></span>
                    </span>
                </a>
                <?php endif; ?>
            </div>

            <?php if ($showRegionPlaceholders): ?>
            <div class="sidebar-divider"></div>
            <div class="sidebar-section-label">
                <img src="../../resources/img/smc.png" alt="SMC" class="region-icon">SMC International
            </div>

            <!-- SMC-Turkey -->
            <button class="sidebar-item sidebar-toggle" type="button"
                    data-bs-toggle="collapse" data-bs-target="#smcTurkeyMenu"
                    aria-expanded="false" aria-controls="smcTurkeyMenu"
                    data-bs-placement="right" title="SMC-Turkey">
                <i class="bi bi-globe2"></i>
                <span class="label"><span class="text">SMC-Turkey</span></span>
                <span class="side-badge"><i class="bi bi-chevron-down"></i></span>
            </button>
            <div class="collapse sidebar-submenu" id="smcTurkeyMenu">
                <a href="#" class="sidebar-item disabled" tabindex="-1" aria-disabled="true">
                    <i class="bi bi-people"></i><span class="label"><span class="text">List of Applicants</span></span>
                </a>
                <a href="#" class="sidebar-item disabled" tabindex="-1" aria-disabled="true">
                    <i class="bi bi-clock-history"></i><span class="label"><span class="text">Pending</span></span>
                </a>
                <a href="#" class="sidebar-item disabled" tabindex="-1" aria-disabled="true">
                    <i class="bi bi-hourglass-split"></i><span class="label"><span class="text">On Process</span></span>
                </a>
                <a href="#" class="sidebar-item disabled" tabindex="-1" aria-disabled="true">
                    <i class="bi bi-check-circle"></i><span class="label"><span class="text">Approved</span></span>
                </a>
                <a href="#" class="sidebar-item disabled" tabindex="-1" aria-disabled="true">
                    <i class="bi bi-trash"></i><span class="label"><span class="text">Deleted</span></span>
                </a>
                <a href="#" class="sidebar-item disabled" tabindex="-1" aria-disabled="true">
                    <i class="bi bi-slash-circle"></i><span class="label"><span class="text">Blacklisted</span></span>
                </a>
            </div>

            <!-- SMC-Bahrain -->
            <button class="sidebar-item sidebar-toggle" type="button"
                    data-bs-toggle="collapse" data-bs-target="#smcBahrainMenu"
                    aria-expanded="false" aria-controls="smcBahrainMenu"
                    data-bs-placement="right" title="SMC-Bahrain">
                <i class="bi bi-globe2"></i>
                <span class="label"><span class="text">SMC-Bahrain</span></span>
                <span class="side-badge"><i class="bi bi-chevron-down"></i></span>
            </button>
            <div class="collapse sidebar-submenu" id="smcBahrainMenu">
                <a href="#" class="sidebar-item disabled" tabindex="-1" aria-disabled="true">
                    <i class="bi bi-people"></i><span class="label"><span class="text">List of Applicants</span></span>
                </a>
                <a href="#" class="sidebar-item disabled" tabindex="-1" aria-disabled="true">
                    <i class="bi bi-clock-history"></i><span class="label"><span class="text">Pending</span></span>
                </a>
                <a href="#" class="sidebar-item disabled" tabindex="-1" aria-disabled="true">
                    <i class="bi bi-hourglass-split"></i><span class="label"><span class="text">On Process</span></span>
                </a>
                <a href="#" class="sidebar-item disabled" tabindex="-1" aria-disabled="true">
                    <i class="bi bi-check-circle"></i><span class="label"><span class="text">Approved</span></span>
                </a>
                <a href="#" class="sidebar-item disabled" tabindex="-1" aria-disabled="true">
                    <i class="bi bi-trash"></i><span class="label"><span class="text">Deleted</span></span>
                </a>
                <a href="#" class="sidebar-item disabled" tabindex="-1" aria-disabled="true">
                    <i class="bi bi-slash-circle"></i><span class="label"><span class="text">Blacklisted</span></span>
                </a>
            </div>
            <?php endif; ?>

            <?php if ($canViewActivity): ?>
            <div class="sidebar-divider"></div>
            <div class="sidebar-section-label">Monitoring</div>
            <a href="activity-logs.php"
               class="sidebar-item <?php echo $currentPage === 'activity-logs' ? 'active' : ''; ?>"
               aria-current="<?php echo $currentPage === 'activity-logs' ? 'page' : 'false'; ?>"
               data-bs-toggle="tooltip" data-bs-placement="right" title="Activity Logs">
                <i class="bi bi-clipboard-data"></i>
                <span class="label"><span class="text">Activity Logs</span></span>
            </a>

            <?php if (!empty($canViewReports) && $canViewReports === true) : ?>
                <div class="sidebar-section-label">Reports</div>
                <a href="reports.php"
                   class="sidebar-item <?php echo ($currentPage === 'reports') ? 'active' : ''; ?>"
                   aria-current="<?php echo ($currentPage === 'reports') ? 'page' : 'false'; ?>"
                   data-bs-toggle="tooltip" data-bs-placement="right" title="Reports">
                    <i class="bi bi-journal-text" aria-hidden="true"></i>
                    <span class="label">
                        <span class="text">Reports</span>
                    </span>
                    <span class="side-badge" aria-live="polite">
                        <span class="pill-count <?php echo ((int)($reportNotesCount ?? 0) === 0) ? 'is-zero' : ''; ?>"
                              aria-label="Total reports count">
                            <?php echo (int)($reportNotesCount ?? 0); ?>
                        </span>
                    </span>
                </a>
            <?php endif; ?>
            <?php endif; /* <-- closes $canViewActivity */ ?>

            <div class="sidebar-divider"></div>
            <div class="sidebar-section-label">Settings</div>
            <a href="accounts.php"
               class="sidebar-item <?php echo $currentPage === 'accounts' ? 'active' : ''; ?>"
               aria-current="<?php echo $currentPage === 'accounts' ? 'page' : 'false'; ?>"
               data-bs-toggle="tooltip" data-bs-placement="right" title="Accounts">
                <i class="bi bi-person-badge"></i>
                <span class="label"><span class="text">Accounts</span></span>
            </a>
            <a href="profile.php"
               class="sidebar-item <?php echo $currentPage === 'profile' ? 'active' : ''; ?>"
               aria-current="<?php echo $currentPage === 'profile' ? 'page' : 'false'; ?>"
               data-bs-toggle="tooltip" data-bs-placement="right" title="Profile">
                <i class="bi bi-person-circle"></i>
                <span class="label"><span class="text">Profile</span></span>
            </a>

            <div class="sidebar-divider"></div>
            <a href="logout.php" class="sidebar-item text-danger" data-bs-toggle="tooltip" data-bs-placement="right" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
                <span class="label"><span class="text">Logout</span></span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <nav class="navbar-top d-flex justify-content-between align-items-center">
            <div class="nav-left">
                <button id="btnSidebarToggle" class="btn btn-light btn-sm border me-1" type="button" title="Toggle sidebar">
                    <i class="bi bi-layout-sidebar-inset"></i>
                </button>
                <h5 class="mb-0 fw-semibold"><?php echo h($pageTitle ?? 'Dashboard'); ?></h5>
            </div>

            <div class="d-flex align-items-center gap-3">
                <?php if (!empty($recentBookings) && ($isAdmin || $isSuperAdmin)): ?>
                <div class="dropdown">
                    <button class="btn btn-link text-decoration-none position-relative" type="button" id="bookingBell"
                            data-bs-toggle="dropdown" aria-expanded="false" aria-label="Recent client bookings">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo count($recentBookings); ?>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="bookingBell" style="min-width: 320px;">
                        <h6 class="dropdown-header">Latest Client Bookings</h6>
                        <?php foreach ($recentBookings as $booking): ?>
                            <?php
                                $appId      = (int)($booking['applicant_id'] ?? 0);
                                $appName    = getFullName($booking['first_name'] ?? '', $booking['middle_name'] ?? '', $booking['last_name'] ?? '', $booking['suffix'] ?? '');
                                $clientName = trim(($booking['client_first_name'] ?? '') . ' ' . ($booking['client_last_name'] ?? ''));
                                $createdAt  = formatDateTime($booking['created_at'] ?? '');
                                $apptType   = $booking['appointment_type'] ?? '';
                                $viewLink   = 'view_onprocess.php?id=' . $appId;
                            ?>
                            <a href="<?php echo h($viewLink); ?>" class="dropdown-item small">
                                <div class="fw-semibold text-truncate"><?php echo h($appName); ?></div>
                                <div class="text-muted small text-truncate">
                                    Booked by <?php echo h($clientName); ?> (<?php echo h($apptType); ?>)
                                </div>
                                <div class="text-muted small"><?php echo h($createdAt); ?></div>
                            </a>
                        <?php endforeach; ?>
                        <div class="dropdown-divider"></div>
                        <a href="on-process.php" class="dropdown-item text-center small text-primary">
                            View all on-process applicants
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <span class="ms-2 me-1">Welcome, <strong><?php echo h($currentUser['full_name'] ?? ''); ?></strong></span>
                <?php if (!empty($currentUser['avatar'])): ?>
                    <img src="<?php echo h(getFileUrl($currentUser['avatar'])); ?>" alt="Avatar" class="rounded-circle" width="40" height="40">
                <?php else: ?>
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                         style="width: 40px; height: 40px;">
                        <?php echo strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
        </nav>

        <div class="content-wrapper">
            <?php
            $flashMessage = getFlashMessage();
            if (!empty($flashMessage) && is_array($flashMessage)) {
                $type = $flashMessage['type'] ?? '';
                $alertClass = $type === 'success' ? 'alert-success' : 'alert-danger';
                $message = $flashMessage['message'] ?? '';
                if ($message !== '') {
                    ?>
                    <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
                        <?php echo h((string)$message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php
                }
            }
            ?>
            <!-- Page content continues from here ... (footer will close the tags) -->
<!-- Bootstrap Bundle (JS for dropdown/collapse/tooltip) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

<script>
/* Sidebar toggle with persisted state */
(function () {
    const btn = document.getElementById('btnSidebarToggle');
    const storageKey = 'csnk_sidebar_collapsed';

    function applyState(collapsed) {
        if (window.innerWidth <= 992) {
            document.body.classList.toggle('sidebar-hidden', collapsed);
            document.body.classList.remove('sidebar-collapsed');
        } else {
            document.body.classList.toggle('sidebar-collapsed', collapsed);
            document.body.classList.remove('sidebar-hidden');
        }
    }

    const initiallyCollapsed = localStorage.getItem(storageKey) === '1';
    applyState(initiallyCollapsed);

    let t;
    window.addEventListener('resize', () => {
        clearTimeout(t);
        t = setTimeout(() => applyState(localStorage.getItem(storageKey) === '1'), 120);
    });

    btn?.addEventListener('click', () => {
        const current = localStorage.getItem(storageKey) === '1';
        const next = !current;
        localStorage.setItem(storageKey, next ? '1' : '0');
        applyState(next);
    });

    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', () => {
        if (window.bootstrap && bootstrap.Tooltip) {
            const tipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tipTriggerList.forEach(el => new bootstrap.Tooltip(el));
        }

        // Sidebar collapse: keep submenu open when clicked (do not toggle closed)
        document.querySelectorAll('.sidebar-toggle[data-bs-toggle="collapse"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const targetId = btn.getAttribute('data-bs-target');
                if (!targetId) return;
                const target = document.querySelector(targetId);
                if (!target || !window.bootstrap?.Collapse) return;
                if (target.classList.contains('show')) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    const collapse = bootstrap.Collapse.getInstance(target);
                    if (collapse) collapse.show();
                }
            }, true);
        });
    });
})();
</script>