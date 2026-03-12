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
        <table class="table table-bordered table-striped table-hover table-styled align-middle">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Applicant</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Preferred Location</th>
                    <th>Date Approved</th>
                    <th style="width: 420px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($applicants)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                            <?php if ($q === ''): ?>
                                No approved applicants yet.
                            <?php else: ?>
                                No results for "<strong><?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?></strong>".
                                <a href="approved.php?clear=1" class="ms-1">Clear search</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applicants as $row): ?>
                        <?php
                            $id = (int)$row['id'];
                            $currentStatus = (string)($row['status'] ?? 'approved');
                            $fullName = getFullName($row['first_name'], $row['middle_name'], $row['last_name'], $row['suffix']);
                            $photoUrl = !empty($row['picture']) ? getFileUrl($row['picture']) : '';

                            // Action URLs (with CSRF & q preserved)
                            $viewUrl   = buildUrl('view_approved.php', ['id' => $id]);
                            $editUrl   = buildUrl('edit-applicant.php', ['id' => $id]);
                            $toPendingUrl   = buildUrl('approved.php', [
                                'action' => 'update_status', 'id' => $id, 'to' => 'pending',
                                'csrf'   => $_SESSION['csrf_token'] ?? ''
                            ]);
                            $toOnProcessUrl = buildUrl('approved.php', [
                                'action' => 'update_status', 'id' => $id, 'to' => 'on_process',
                                'csrf'   => $_SESSION['csrf_token'] ?? ''
                            ]);
                            $toApprovedUrl  = buildUrl('approved.php', [
                                'action' => 'update_status', 'id' => $id, 'to' => 'approved',
                                'csrf'   => $_SESSION['csrf_token'] ?? ''
                            ]);
                        ?>
                        <tr>
                            <td>
                                <?php if (!empty($row['picture'])): ?>
                                    <img
                                        src="<?php echo htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                        alt="Photo"
                                        class="rounded"
                                        width="50"
                                        height="50"
                                        style="object-fit: cover;"
                                    >
                                <?php else: ?>
                                    <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center"
                                         style="width: 50px; height: 50px;">
                                        <?php echo strtoupper(substr((string)$row['first_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold">
                                    <?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($row['email'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['phone_number'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(renderPreferredLocation($row['preferred_location'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(formatDate($row['created_at']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="actions-cell">
                                <div class="btn-group dd-modern dropup">
                                    <!-- View -->
                                    <a href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-sm btn-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <!-- Edit -->
                                    <a href="<?php echo htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <!-- Replace (opens modal) -->
                                    <button class="btn btn-sm btn-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#replaceModal"
                                            data-applicant-id="<?php echo (int)$id; ?>"
                                            data-applicant-name="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>"
                                            title="Replace this approved applicant">
                                        <i class="bi bi-arrow-repeat me-1"></i> Replace
                                    </button>

                                    <!-- Change Status Dropdown -->
                                    <div class="dropdown dropup">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary dropdown-toggle btn-status"
                                                data-bs-toggle="dropdown"
                                                data-bs-auto-close="true"
                                                aria-expanded="false"
                                                aria-haspopup="true"
                                                title="Change Status"
                                                id="changeStatusBtn-<?php echo $id; ?>">
                                            <i class="bi bi-arrow-left-right me-1"></i> Change Status
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="changeStatusBtn-<?php echo $id; ?>">
                                            <li>
                                                <a class="dropdown-item <?php echo $currentStatus === 'pending' ? 'disabled' : ''; ?>"
                                                   href="<?php echo $currentStatus === 'pending' ? '#' : htmlspecialchars($toPendingUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi bi-hourglass-split text-warning"></i>
                                                    <span>Pending</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item <?php echo $currentStatus === 'on_process' ? 'disabled' : ''; ?>"
                                                   href="<?php echo $currentStatus === 'on_process' ? '#' : htmlspecialchars($toOnProcessUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi bi-arrow-repeat text-info"></i>
                                                    <span>On-Process</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item <?php echo $currentStatus === 'approved' ? 'disabled' : ''; ?>"
                                                   href="<?php echo $currentStatus === 'approved' ? '#' : htmlspecialchars($toApprovedUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi bi-check2-circle text-success"></i>
                                                    <span>Approved</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- NOTE: Delete button intentionally removed on this page -->
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ===== Replace Modal (Light-only, Senior-friendly) ===== -->
<div class="modal fade rep-modal" id="replaceModal" tabindex="-1" aria-hidden="true" role="dialog" aria-labelledby="repModalTitle">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content rep-surface">
      <form id="replaceInitForm" enctype="multipart/form-data" novalidate>
        <!-- Header -->
        <div class="modal-header rep-header border-0">
          <div class="d-flex align-items-center gap-3">
            <div class="rep-icon" aria-hidden="true">
              <i class="bi bi-arrow-repeat"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold mb-0" id="repModalTitle">Replace Approved Applicant</h5>
              <p class="text-muted mb-0" style="font-size: .975rem;">Start a replacement and get suggested candidates.</p>
            </div>
          </div>
          <button type="button" class="btn-close rep-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- Body -->
        <div class="modal-body">
          <!-- Hidden fields -->
          <input type="hidden" name="original_applicant_id" id="replaceOriginalId" value="">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

          <!-- Applicant summary -->
          <section class="rep-card mb-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-2">
              <div class="text-muted">Replacing:</div>
              <div class="fw-semibold fs-5" id="replaceApplicantName">Applicant Name</div>
            </div>
          </section>

          <!-- Form fields -->
          <div class="row g-4">
            <!-- Reason -->
            <div class="col-12 col-md-5">
              <label class="form-label fw-semibold" for="rep-reason">Reason <span class="text-danger">*</span></label>
              <select name="reason" id="rep-reason" class="form-select form-select-lg rep-input" required aria-describedby="rep-reason-help">
                <option value="" selected disabled>Select a reason</option>

                <!-- Attendance / Conduct -->
                <option value="AWOL">AWOL</option>
                <option value="Habitual Absences">Habitual Absences</option>
                <option value="Violation of Company Policies">Violation of Company Policies</option>

                <!-- Client-Related -->
                <option value="Client Left">Client Left</option>
                <option value="Client Requested Replacement">Client Requested Replacement</option>
                <option value="Client Feedback (Negative)">Client Feedback (Negative)</option>

                <!-- Assignment / Contract -->
                <option value="Not Finished Contract">Not Finished Contract</option>
                <option value="Did Not Report to Client">Did Not Report to Client</option>
                <option value="Mismatch to Client Requirements">Mismatch to Client Requirements</option>

                <!-- Performance -->
                <option value="Performance Issue">Performance Issue</option>
                <option value="Failed to Meet Job Expectations">Failed to Meet Job Expectations</option>

                <!-- Personal -->
                <option value="Personal Concern / Personal Reason">Personal Concern / Personal Reason</option>

                <!-- Fallback -->
                <option value="Other">Other</option>
              </select>
              <div id="rep-reason-help" class="form-text" style="font-size:.95rem;">Choose the most appropriate reason.</div>
            </div>

            <!-- Report / Note -->
            <div class="col-12 col-md-7">
              <label class="form-label fw-semibold d-flex justify-content-between align-items-center" for="rep-note">
                <span>Report / Note <span class="text-danger">*</span></span>
                <span class="badge bg-light text-secondary rep-counter" id="rep-note-counter">0/1000</span>
              </label>
              <textarea name="report_text" id="rep-note" class="form-control rep-input" rows="6" maxlength="1000" required
                        placeholder="Provide a clear summary (e.g., reason details, date of incident, client remarks)."
                        aria-describedby="rep-note-help"></textarea>
              <div id="rep-note-help" class="form-text" style="font-size:.95rem;">Minimum 5 characters. This will be saved to the report log.</div>
            </div>

            <!-- Attachments -->
            <div class="col-12">
              <label class="form-label fw-semibold" for="rep-files">Attachments (optional)</label>
              <input type="file" name="attachments[]" id="rep-files" class="form-control rep-input" multiple aria-describedby="rep-files-help">
              <div id="rep-files-help" class="form-text" style="font-size:.95rem;">You can upload images/documents/videos. <strong>Max 200MB per file.</strong></div>

              <!-- File preview list -->
              <div id="rep-files-list" class="rep-files mt-2"></div>
              <div id="rep-files-warning" class="alert alert-warning mt-3 d-none" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                One or more files exceed the 200MB limit. Please remove the highlighted files.
              </div>
            </div>
          </div>

          <!-- Info -->
          <div class="alert alert-info rep-info mt-4 mb-0" role="alert">
            <div class="d-flex align-items-start gap-2">
              <i class="bi bi-info-circle-fill mt-1" aria-hidden="true"></i>
              <div class="small" style="font-size: .975rem;">
                <strong>What happens next?</strong> We’ll log your reason and note, then suggest replacement candidates based on the client and role match.
              </div>
            </div>
          </div>

          <hr class="my-4">

          <!-- Suggestions -->
          <h6 class="fw-semibold mb-2">Suggested Replacement Candidates</h6>
          <div id="replacementCandidates" class="mt-2"></div>
        </div>

        <!-- Footer -->
        <div class="modal-footer rep-footer border-0">
          <button type="button" class="btn btn-outline-secondary btn-lg rep-btn-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-lg me-1"></i> Cancel
          </button>
          <button type="submit" class="btn btn-danger btn-lg rep-btn-primary" id="rep-submit">
            <i class="bi bi-arrow-repeat me-1"></i> Start &amp; Suggest
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===== Replace Modal Styles (Light-only, Senior-friendly) ===== -->
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
ariant-numeric: tabular-nums; }
</style>

<!-- ===== Scripts for Replace Modal binding & CSRF exposure ===== -->
<script src="../js/replacements.js"></script>
<script>
  // Expose CSRF to replacements.js
  window.CSRF_TOKEN = "<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>";

  (function() {
    const modalEl   = document.getElementById('replaceModal');
    const formEl    = document.getElementById('replaceInitForm');
    const nameEl    = document.getElementById('replaceApplicantName');
    const idInput   = document.getElementById('replaceOriginalId');
    const reasonSel = document.getElementById('rep-reason');
    const noteTA    = document.getElementById('rep-note');
    const counterEl = document.getElementById('rep-note-counter');
    const filesIn   = document.getElementById('rep-files');
    const filesList = document.getElementById('rep-files-list');
    const warnEl    = document.getElementById('rep-files-warning');
    const suggestEl = document.getElementById('replacementCandidates');

    const MAX_FILE_BYTES = 200 * 1024 * 1024; // 200MB

    function fmtSize(bytes) {
      const units = ['B', 'KB', 'MB', 'GB'];
      let i = 0, val = bytes;
      while (val >= 1024 && i < units.length - 1) { val /= 1024; i++; }
      return (Math.round(val * 10) / 10) + ' ' + units[i];
    }

    function updateCounter() {
      const max = parseInt(noteTA.getAttribute('maxlength') || '1000', 10);
      const len = noteTA.value.length;
      counterEl.textContent = `${len}/${max}`;
      counterEl.classList.toggle('text-danger', len < 5);
    }

    function renderFiles() {
      filesList.innerHTML = '';
      let anyTooBig = false;

      if (!filesIn.files || filesIn.files.length === 0) {
        warnEl.classList.add('d-none');
        return;
      }

      Array.from(filesIn.files).forEach((file) => {
        const tooBig = file.size > MAX_FILE_BYTES;
        anyTooBig = anyTooBig || tooBig;

        const row = document.createElement('div');
        row.className = 'rep-file' + (tooBig ? ' bad' : '');
        row.innerHTML = `
          <div class="meta">
            <i class="bi bi-paperclip me-1"></i>
            <strong>${file.name}</strong>
          </div>
          <div class="size">${fmtSize(file.size)}${tooBig ? ' • too large' : ''}</div>
        `;
        filesList.appendChild(row);
      });

      warnEl.classList.toggle('d-none', !anyTooBig);
    }

    // On modal open: fill data & reset state
    modalEl?.addEventListener('show.bs.modal', function (ev) {
      const btn = ev.relatedTarget;
      const id   = btn?.getAttribute('data-applicant-id') || '';
      const name = btn?.getAttribute('data-applicant-name') || '';

      idInput.value = id;
      nameEl.textContent = name || 'Applicant';

      formEl.reset();
      filesList.innerHTML = '';
      warnEl.classList.add('d-none');
      suggestEl.innerHTML = '';
      updateCounter();

<?php require_once '../includes/footer.php'; ?>