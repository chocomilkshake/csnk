<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/functions.php';

$database = new Database();
$auth = new Auth($database);
$auth->requireLogin();

$currentUser = $auth->getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

/* ---------- Real-time counts for sidebar badges ---------- */
$conn = $database->getConnection();
function csnk_count($conn, string $sql): int {
    $res = $conn->query($sql);
    if ($res) {
        $row = $res->fetch_row();
        return (int) ($row[0] ?? 0);
    }
    return 0;
}
$totalApplicants = csnk_count($conn, "SELECT COUNT(*) FROM applicants WHERE deleted_at IS NULL");
$pendingCount    = csnk_count($conn, "SELECT COUNT(*) FROM applicants WHERE status='pending' AND deleted_at IS NULL");
$onProcessCount  = csnk_count($conn, "SELECT COUNT(*) FROM applicants WHERE status='on_process' AND deleted_at IS NULL");
$approvedCount   = csnk_count($conn, "SELECT COUNT(*) FROM applicants WHERE status='approved' AND deleted_at IS NULL");
$deletedCount    = csnk_count($conn, "SELECT COUNT(*) FROM applicants WHERE deleted_at IS NOT NULL");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
        --sidebar-width: 260px;

        /* CSNK brand core */
        --csnk-red: #c40000;
        --csnk-red-700: #991b1b;

        /* Neutral foundation */
        --csnk-gray-50: #f8fafc;
        --csnk-gray-100: #f1f5f9;
        --csnk-gray-400: #94a3b8;
        --csnk-gray-600: #475569;

        /* Soft ring / elevation */
        --csnk-ring: rgba(196, 0, 0, 0.14);   /* subtle brand‑tinted ring */
        --csnk-shadow-sm: 0 1px 2px rgba(0,0,0,.06);
        --csnk-shadow-md: 0 6px 16px rgba(0,0,0,.12);

        /* Modern accent gradient (used for badges / hovers) */
        --csnk-accent-bg: linear-gradient(
            180deg,
            rgba(196, 0, 0, 0.9) 0%,
            rgba(153, 27, 27, 0.92) 100%
        );
        --csnk-accent-bg-hover: linear-gradient(
            180deg,
            rgba(153, 27, 27, 1) 0%,
            rgba(127, 29, 29, 1) 100%
        );
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100vh; width: var(--sidebar-width);
            background: #fff; border-right: 1px solid #e5e7eb; overflow-y: auto; z-index: 1000;
        }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; background: #f8f9fa; }
        .navbar-top { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 1rem 1.5rem; }
        .sidebar-brand { padding: 1.5rem 1rem; font-size: 1.5rem; font-weight: 700; color: #1e40af; border-bottom: 1px solid #e5e7eb; }
        .sidebar-menu { padding: 1rem 0; }

        /* Sidebar item as a positioned container so the badge can stick to the right edge */
        .sidebar-item {
            position: relative;
            display: flex; align-items: center; gap: .75rem;
            padding: 0.75rem 1.5rem; color: #4b5563; text-decoration: none;
            transition: background .2s ease, color .2s ease; border-left: 3px solid transparent;
        }
        .sidebar-item:hover { background: #f3f4f6; color: #1e40af; }
        .sidebar-item.active { background: #eff6ff; color: #1e40af; border-left-color: #1e40af; }
        .sidebar-item i { width: 20px; margin-right: 0.75rem; }
        .sidebar-item .label { display: inline-flex; align-items: center; gap: .5rem; }
        .sidebar-item .label .text { white-space: nowrap; }

        /* Perfect right alignment for numbers: absolute to the right padding */
        .sidebar-item .side-badge {
            position: absolute;
            right: 1.5rem; /* matches link horizontal padding so it hugs the inner right edge */
            top: 50%;
            transform: translateY(-50%);
        }

        .content-wrapper { padding: 2rem; }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }

        /* ===== Modern pill number ===== */
        .pill-count {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 28px; height: 22px; padding: 0 .55rem;
            border-radius: 999px;
            background: linear-gradient(180deg, #d81515 0%, var(--csnk-red) 100%);
            color: #fff; font-weight: 900; font-size: .78rem; line-height: 1;
            letter-spacing: .2px;
            border: 1px solid rgba(0,0,0,.08);
            box-shadow: 0 1px 0 rgba(255,255,255,.35) inset, 0 1px 0 rgba(0,0,0,.05);
            /* Align digits vertically with equal width */
            font-variant-numeric: tabular-nums;
            -moz-font-feature-settings: "tnum";
            -webkit-font-feature-settings: "tnum";
            font-feature-settings: "tnum";
        }
        .sidebar-item:hover .pill-count { background: linear-gradient(180deg, #b51212 0%, var(--csnk-red-700) 100%); }

        /* Optional: show zero as muted but still aligned – toggle this if you want all red */
        .pill-count.is-zero {
            background: #e9eef5;
            color: #6b7280;
            border-color: rgba(0,0,0,.05);
            box-shadow: 0 1px 0 rgba(255,255,255,.45) inset, 0 1px 0 rgba(0,0,0,.03);
        }

        /* Small ring accent around the pill for depth on active row */
        .sidebar-item.active .pill-count {
            box-shadow:
                0 0 0 2px #ffffff,
                0 0 0 4px var(--csnk-ring),
                0 1px 0 rgba(255,255,255,.35) inset,
                0 1px 0 rgba(0,0,0,.05);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <!-- Brand / Logo -->
        <div class="sidebar-brand text-center py-4 border-bottom">
            <img
                src="../resources/img/csnklogo.png"
                alt="CSNK Logo"
                class="img-fluid d-block mx-auto rounded-2 w-40 w-md-25"
            >
        </div>

        <!-- Menu -->
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="sidebar-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                <span class="label"><i class="bi bi-speedometer2"></i><span class="text">Dashboard</span></span>
            </a>

            <div class="mt-3 px-3">
                <small class="text-muted text-uppercase fw-semibold">Applicants</small>
            </div>

            <a href="applicants.php" class="sidebar-item <?php echo $currentPage === 'applicants' ? 'active' : ''; ?>">
                <span class="label"><i class="bi bi-people"></i><span class="text">List of Applicants</span></span>
                <span class="side-badge">
                    <span class="pill-count <?php echo $totalApplicants === 0 ? 'is-zero' : ''; ?>"
                          aria-label="Total applicants count"><?php echo (int)$totalApplicants; ?></span>
                </span>
            </a>

            <a href="pending.php" class="sidebar-item <?php echo $currentPage === 'pending' ? 'active' : ''; ?>">
                <span class="label"><i class="bi bi-clock-history"></i><span class="text">Pending Applicants</span></span>
                <span class="side-badge">
                    <span class="pill-count <?php echo $pendingCount === 0 ? 'is-zero' : ''; ?>"
                          aria-label="Pending applicants count"><?php echo (int)$pendingCount; ?></span>
                </span>
            </a>

            <a href="on-process.php" class="sidebar-item <?php echo $currentPage === 'on-process' ? 'active' : ''; ?>">
                <span class="label"><i class="bi bi-hourglass-split"></i><span class="text">On Process</span></span>
                <span class="side-badge">
                    <span class="pill-count <?php echo $onProcessCount === 0 ? 'is-zero' : ''; ?>"
                          aria-label="On process applicants count"><?php echo (int)$onProcessCount; ?></span>
                </span>
            </a>

            <a href="approved.php" class="sidebar-item <?php echo $currentPage === 'approved' ? 'active' : ''; ?>">
                <span class="label"><i class="bi bi-check-circle"></i><span class="text">Approved</span></span>
                <span class="side-badge">
                    <span class="pill-count <?php echo $approvedCount === 0 ? 'is-zero' : ''; ?>"
                          aria-label="Approved applicants count"><?php echo (int)$approvedCount; ?></span>
                </span>
            </a>

            <a href="deleted.php" class="sidebar-item <?php echo $currentPage === 'deleted' ? 'active' : ''; ?>">
                <span class="label"><i class="bi bi-trash"></i><span class="text">Deleted Applicants</span></span>
                <span class="side-badge">
                    <span class="pill-count <?php echo $deletedCount === 0 ? 'is-zero' : ''; ?>"
                          aria-label="Deleted applicants count"><?php echo (int)$deletedCount; ?></span>
                </span>
            </a>

            <div class="mt-3 px-3">
                <small class="text-muted text-uppercase fw-semibold">Settings</small>
            </div>

            <a href="accounts.php" class="sidebar-item <?php echo $currentPage === 'accounts' ? 'active' : ''; ?>">
                <span class="label"><i class="bi bi-person-badge"></i><span class="text">Accounts</span></span>
            </a>
            <a href="profile.php" class="sidebar-item <?php echo $currentPage === 'profile' ? 'active' : ''; ?>">
                <span class="label"><i class="bi bi-person-circle"></i><span class="text">Profile</span></span>
            </a>

            <div class="mt-4"></div>
            <a href="logout.php" class="sidebar-item text-danger">
                <span class="label"><i class="bi bi-box-arrow-right"></i><span class="text">Logout</span></span>
            </a>
        </nav>
    </div>

    <div class="main-content">
        <nav class="navbar-top d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold"><?php echo $pageTitle ?? 'Dashboard'; ?></h5>
            <div class="d-flex align-items-center gap-2">
                <span class="ms-2 me-3">Welcome, <strong><?php echo htmlspecialchars($currentUser['full_name']); ?></strong></span>
                <?php if (!empty($currentUser['avatar'])): ?>
                    <img src="<?php echo getFileUrl($currentUser['avatar']); ?>" alt="Avatar" class="rounded-circle" width="40" height="40">
                <?php else: ?>
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
        </nav>

        <div class="content-wrapper">
            <?php
            $flashMessage = getFlashMessage();
            // Ensure we have an array and the expected keys to avoid null/undefined notices
            if (!empty($flashMessage) && is_array($flashMessage)) {
                $type = $flashMessage['type'] ?? '';
                $alertClass = $type === 'success' ? 'alert-success' : 'alert-danger';
                $message = $flashMessage['message'] ?? '';
                if ($message !== '') {
            ?>
                <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php
                }
            } ?>