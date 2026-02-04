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
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: #fff;
            border-right: 1px solid #e5e7eb;
            overflow-y: auto;
            z-index: 1000;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: #f8f9fa;
        }
        .navbar-top {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 1.5rem;
        }
        .sidebar-brand {
            padding: 1.5rem 1rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e40af;
            border-bottom: 1px solid #e5e7eb;
        }
        .sidebar-menu {
            padding: 1rem 0;
        }
        .sidebar-item {
            display: block;
            padding: 0.75rem 1.5rem;
            color: #4b5563;
            text-decoration: none;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        .sidebar-item:hover {
            background: #f3f4f6;
            color: #1e40af;
        }
        .sidebar-item.active {
            background: #eff6ff;
            color: #1e40af;
            border-left-color: #1e40af;
        }
        .sidebar-item i {
            width: 20px;
            margin-right: 0.75rem;
        }
        .sidebar-submenu {
            padding-left: 3rem;
        }
        .content-wrapper {
            padding: 2rem;
        }
        .card {
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-4px);
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
                <i class="bi bi-speedometer2"></i>Dashboard
            </a>

            <div class="mt-3 px-3">
                <small class="text-muted text-uppercase fw-semibold">Applicants</small>
            </div>
            <a href="applicants.php" class="sidebar-item <?php echo $currentPage === 'applicants' ? 'active' : ''; ?>">
                <i class="bi bi-people"></i>List of Applicants
            </a>
            <a href="on-process.php" class="sidebar-item <?php echo $currentPage === 'on-process' ? 'active' : ''; ?>">
                <i class="bi bi-hourglass-split"></i>On Process
            </a>
            <a href="approved.php" class="sidebar-item <?php echo $currentPage === 'approved' ? 'active' : ''; ?>">
                <i class="bi bi-check-circle"></i>Approved
            </a>
            <a href="pending.php" class="sidebar-item <?php echo $currentPage === 'pending' ? 'active' : ''; ?>">
                <i class="bi bi-clock-history"></i>Pending Applicants
            </a>
            <a href="deleted.php" class="sidebar-item <?php echo $currentPage === 'deleted' ? 'active' : ''; ?>">
                <i class="bi bi-trash"></i>Deleted Applicants
            </a>

            <div class="mt-3 px-3">
                <small class="text-muted text-uppercase fw-semibold">Settings</small>
            </div>
            <a href="accounts.php" class="sidebar-item <?php echo $currentPage === 'accounts' ? 'active' : ''; ?>">
                <i class="bi bi-person-badge"></i>Accounts
            </a>
            <a href="profile.php" class="sidebar-item <?php echo $currentPage === 'profile' ? 'active' : ''; ?>">
                <i class="bi bi-person-circle"></i>Profile
            </a>

            <div class="mt-4"></div>
            <a href="logout.php" class="sidebar-item text-danger">
                <i class="bi bi-box-arrow-right"></i>Logout
            </a>
        </nav>
    </div>

    <div class="main-content">
        <nav class="navbar-top d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold"><?php echo $pageTitle ?? 'Dashboard'; ?></h5>
            <div class="d-flex align-items-center">
                <span class="me-3">Welcome, <strong><?php echo htmlspecialchars($currentUser['full_name']); ?></strong></span>
                <?php if ($currentUser['avatar']): ?>
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
            if ($flashMessage):
                $alertClass = $flashMessage['type'] === 'success' ? 'alert-success' : 'alert-danger';
            ?>
                <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($flashMessage['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
