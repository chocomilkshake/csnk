<?php
// FILE: pages/approved.php  (CSNK-only Approved Applicants)
// Purpose: List ONLY CSNK applicants; changes are hardened to CSNK scope. No deletions on this page.

$pageTitle = 'Approved Applicants';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

// Ensure session (for search persistence + CSRF)
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}
if (empty($_SESSION['csrf_token'])) {
    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }
    catch (Throwable $e) { $_SESSION['csrf_token'] = bin2hex((string)mt_rand()); }
}

$applicant = new Applicant($database);

// --- CSNK scope & statuses ---
const CSNK_AGENCY_CODE = 'csnk';
const ALLOWED_STATUSES = ['pending', 'on_process', 'approved'];

/**
 * Helper: build URL preserving q and adding optional params (e.g., csrf)
 */
function buildUrl(string $path, array $params = []): string {
    if (!empty($_SESSION['approved_q']) && !isset($params['q'])) {
        $params['q'] = (string)$_SESSION['approved_q'];
    }
    $qs = $params ? ('?' . http_build_query($params)) : '';
    return $path . $qs;
}

/**
 * Helper: get applicant's agency code by id
 */
function getApplicantAgencyCode($database, int $applicantId): ?string {
    try {
        $conn = method_exists($database, 'getConnection') ? $database->getConnection() : null;
        if ($conn instanceof mysqli) {
            $sql = "
                SELECT ag.code AS agency_code
                FROM applicants a
                JOIN business_units bu ON bu.id = a.business_unit_id
                JOIN agencies ag ON ag.id = bu.agency_id
                WHERE a.id = ?
                LIMIT 1
            ";
            $stmt = $conn->prepare($sql);
            if (!$stmt) return null;
            $stmt->bind_param("i", $applicantId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            return $row['agency_code'] ?? null;
        }
        if ($database instanceof PDO) {
            $sql = "
                SELECT ag.code AS agency_code
                FROM applicants a
                JOIN business_units bu ON bu.id = a.business_unit_id
                JOIN agencies ag ON ag.id = bu.agency_id
                WHERE a.id = :id
                LIMIT 1
            ";
            $stmt = $database->prepare($sql);
            $stmt->execute([':id' => $applicantId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['agency_code'] ?? null;
        }
    } catch (Throwable $e) {
        // swallow
    }
    return null;
}

/**
 * --- Search Memory Behavior ---
 */
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    unset($_SESSION['approved_q']);
    redirect('approved.php');
    exit;
}

$q = '';
if (isset($_GET['q'])) {
    $q = trim((string)$_GET['q']);
    if (mb_strlen($q) > 100) $q = mb_substr($q, 0, 100);
    $_SESSION['approved_q'] = $q;
} elseif (!empty($_SESSION['approved_q'])) {
    $q = (string)$_SESSION['approved_q'];
}

/**
 * DELETE action is disabled on Approved page (front-end button removed + backend blocked)
 */
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    // Intentionally disabled
    if (function_exists('setFlashMessage')) {
        setFlashMessage('error', 'Deletion is disabled on the Approved page.');
    }
    $qs = $q !== '' ? ('?q=' . urlencode($q)) : '';
    redirect('approved.php' . $qs);
    exit;
}

/**
 * Handle status update (Change Status dropdown) — CSNK-ONLY + CSRF
 */
if (isset($_GET['action'], $_GET['id'], $_GET['to']) && $_GET['action'] === 'update_status') {
    $id = (int)$_GET['id'];
    $to = strtolower(trim((string)$_GET['to']));
    $qs = $q !== '' ? ('?q=' . urlencode($q)) : '';

    // CSRF check
    $csrfOK = isset($_GET['csrf'], $_SESSION['csrf_token']) &&
              hash_equals((string)$_SESSION['csrf_token'], (string)$_GET['csrf']);
    if (!$csrfOK) {
        if (function_exists('setFlashMessage')) setFlashMessage('error', 'Invalid or missing security token.');
        redirect('approved.php' . $qs);
        exit;
    }

    // Enforce agency scope
    $agency = getApplicantAgencyCode($database, $id);
    if ($agency !== CSNK_AGENCY_CODE) {
        if (function_exists('setFlashMessage')) setFlashMessage('error', 'Operation blocked: applicant does not belong to CSNK.');
        redirect('approved.php' . $qs);
        exit;
    }

    if (in_array($to, ALLOWED_STATUSES, true)) {
        $updated = false;
        // capture previous status & business unit for logging
        $fromStatus = '';
        $businessUnitId = null;
        if (isset($database) && method_exists($database, 'getConnection')) {
            $conn2 = $database->getConnection();
            if ($conn2 instanceof mysqli) {
                $stmtChk = $conn2->prepare("SELECT status, business_unit_id FROM applicants WHERE id = ? LIMIT 1");
                if ($stmtChk) {
                    $stmtChk->bind_param('i', $id);
                    $stmtChk->execute();
                    $resChk = $stmtChk->get_result();
                    if ($resChk && ($rowChk = $resChk->fetch_assoc())) {
                        $fromStatus = $rowChk['status'];
                        $businessUnitId = $rowChk['business_unit_id'];
                    }
                    $stmtChk->close();
                }
            }
        }

        if (method_exists($applicant, 'updateStatus')) {
            $updated = (bool) $applicant->updateStatus($id, $to);
        } elseif (method_exists($applicant, 'update')) {
            $updated = (bool) $applicant->update($id, ['status' => $to]);
        } else {
            try {
                if ($database instanceof PDO) {
                    $stmt = $database->prepare("UPDATE applicants SET status = :st WHERE id = :id");
                    $updated = $stmt->execute([':st' => $to, ':id' => $id]);
                } elseif (method_exists($database, 'getConnection') && $database->getConnection() instanceof mysqli) {
                    $m = $database->getConnection();
                    $stmt = $m->prepare("UPDATE applicants SET status = ? WHERE id = ?");
                    $stmt->bind_param('si', $to, $id);
                    $updated = $stmt->execute();
                    $stmt->close();
                }
            } catch (Throwable $e) {
                $updated = false;
            }
        }

        if (function_exists('setFlashMessage')) {
            $updated ? setFlashMessage('success', 'Status updated successfully.')
                     : setFlashMessage('error', 'Failed to update status. Please try again.');
        }

        if ($updated && isset($auth) && method_exists($auth, 'logActivity') && isset($_SESSION['admin_id'])) {
            $fullName = null;
            if (method_exists($applicant, 'getById')) {
                $row = $applicant->getById($id);
                if (is_array($row)) {
                    $fullName = getFullName($row['first_name'] ?? '', $row['middle_name'] ?? '', $row['last_name'] ?? '');
                }
            }
            $label = $fullName ?: "ID {$id}";
            $auth->logActivity((int)$_SESSION['admin_id'], 'Update Applicant Status', "Updated status for {$label} → {$to} (CSNK)");
        }
        
        // insert status report if change actually occurred
        if ($updated && $fromStatus !== '' && $fromStatus !== $to) {
            $adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
            $reportText = "Status changed from " . ucfirst(str_replace('_', ' ', $fromStatus))
                        . " to " . ucfirst(str_replace('_', ' ', $to));
            $buIdForReport = $businessUnitId !== null ? $businessUnitId : 1;
            if (isset($database) && method_exists($database, 'getConnection')) {
                $conn2 = $database->getConnection();
                if ($conn2 instanceof mysqli) {
                    if ($stmtR = $conn2->prepare("INSERT INTO applicant_status_reports (applicant_id, business_unit_id, from_status, to_status, report_text, admin_id) VALUES (?, ?, ?, ?, ?, ?)") ) {
                        $stmtR->bind_param('iisssi', $id, $buIdForReport, $fromStatus, $to, $reportText, $adminId);
                        $stmtR->execute();
                        $stmtR->close();
                    }
                }
            }
        }
    } else {
        if (function_exists('setFlashMessage')) setFlashMessage('error', 'Invalid status selected.');
    }

    redirect('approved.php' . $qs);
    exit;
}

/** Load approved applicants — strictly CSNK */
$applicants = $applicant->getAll('approved', null, CSNK_AGENCY_CODE);

/**
 * Helpers
 */
function renderPreferredLocation(?string $json, int $maxLen = 30): string {
    if (empty($json)) return 'N/A';
    $arr = json_decode($json, true);
    if (!is_array($arr)) {
        $fallback = trim($json);
        $fallback = trim($fallback, " \t\n\r\0\x0B[]\"");
        return $fallback !== '' ? $fallback : 'N/A';
    }
    $cities = array_values(array_filter(array_map('trim', $arr), fn($v) => is_string($v) && $v !== ''));
    if (empty($cities)) return 'N/A';
    $full = implode(', ', $cities);
    return (mb_strlen($full) > $maxLen) ? $cities[0] : $full;
}

function filterRowsByQuery(array $rows, string $query): array {
    if ($query === '') return $rows;
    $needle = mb_strtolower($query);

    return array_values(array_filter($rows, function(array $row) use ($needle) {
        $first  = (string)($row['first_name']   ?? '');
        $middle = (string)($row['middle_name']  ?? '');
        $last   = (string)($row['last_name']    ?? '');
        $suffix = (string)($row['suffix']       ?? '');

        $email  = (string)($row['email']        ?? '');
        $phone  = (string)($row['phone_number'] ?? '');
        $loc    = renderPreferredLocation($row['preferred_location'] ?? null, 999);

        $fullName1 = trim($first . ' ' . $last);
        $fullName2 = trim($first . ' ' . $middle . ' ' . $last);
        $fullName3 = trim($last . ', ' . $first . ' ' . $middle);
        $fullName4 = trim($first . ' ' . $middle . ' ' . $last . ' ' . $suffix);

        $haystack = mb_strtolower(implode(' | ', [
            $first, $middle, $last, $suffix,
            $fullName1, $fullName2, $fullName3, $fullName4,
            $email, $phone, $loc
        ]));

        return mb_strpos($haystack, $needle) !== false;
    }));
}

if ($q !== '') {
    $applicants = filterRowsByQuery($applicants, $q);
}

// Export URL (search preserved automatically if present)
$exportUrl = buildUrl('../includes/excel_approved.php', []);
?>
<!-- ===== Fix dropdown clipping & remove table scroll wrapper ===== -->
<style>
    .table-card, .table-card .card-body { overflow: visible !important; }
    .table-card .table-responsive { overflow: visible !important; }
    .table-card table, .table-card tbody, .table-card tr { overflow: visible !important; }
    td.actions-cell { position: relative; overflow: visible; z-index: 10; white-space: nowrap; }

    .dd-modern .dropdown-menu {
        border-radius: .75rem; border: 1px solid #e5e7eb; box-shadow: 0 12px 28px rgba(15, 23, 42, .12);
        z-index: 9999 !important; min-width: 160px;
    }
    .dd-modern .dropdown-item { display: flex; align-items: center; gap: .5rem; padding: .55rem .9rem; font-weight: 500; }
    .dd-modern .dropdown-item .bi { font-size: 1rem; opacity: .9; }
    .dd-modern .dropdown-item:hover { background-color: #f8fafc; }
    .dd-modern .dropdown-item.disabled,
    .dd-modern .dropdown-item:disabled { color: #9aa0a6; background-color: transparent; pointer-events: none; }
    .btn-status { border-radius: .75rem; }
    table.table-styled { margin-bottom: 0; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-semibold">Approved Applicants (CSNK)</h4>
    <a href="<?php echo htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">
        <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
    </a>
</div>

<!-- 🔎 Search bar on the right -->
<div class="mb-3 d-flex justify-content-end">
    <form method="get" action="approved.php" class="w-100" style="max-width: 420px;">
        <div class="input-group">
            <input
                type="text"
                name="q"
                class="form-control"
                placeholder="Search approved applicants..."
                value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>"
                autocomplete="off"
            >
            <button class="btn btn-outline-secondary" type="submit" title="Search">
                <i class="bi bi-search"></i>
            </button>
            <?php if ($q !== ''): ?>
                <a class="btn btn-outline-secondary" href="approved.php?clear=1" title="Clear">
                    <i class="bi bi-x-lg"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card table-card">
    <div class="card-body">
        <!-- Removed .table-responsive to avoid scroll/clipping -->
        <table class="table table-b
<!-- ===== Replace Modal (Light-only, Senior-friendly) ===== -->
<div class="modal fade rep-modal" id="replaceModal" tabindex="-1" aria-hidden="true" role="dialog" aria-labelledby="repModalTitle">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content rep-surface">
      <form id="replaceInitForm" enctype="multipart/form-data" novalidate>
        <!-- Header -->
        <div class="modal-header rep-
          </section>

          <!-- Form fields -->
          <div class="row g-4">
            <!-- Reason -->
            <div class="col-12 col-md-5">
              <label class="form-la
<style>
:root {
  --rep-surface: #ffffff;
  --rep-border: #d9dee6;
  --rep-shadow: 0 10px 28px rgba(15, 23, 42, 0.12);
  --rep-text: #0f172a;
  --rep-muted: #475569;
  --rep-soft: #f8fafc;
  --rep-primary: #1d4ed8;
  --rep-primary-500: #2563eb;
  --rep-focus: rgba(29, 78, 216, 0.25);
}
    }