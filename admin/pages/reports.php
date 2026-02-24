<?php
// FILE: pages/reports.php
$pageTitle = 'Reports - All Applicants';

/* -----------------------------------------------------------
   INLINE JSON ENDPOINT (NO LAYOUT): full timeline for applicant
   GET  reports.php?action=history&id=123
   Returns: applicant_reports + applicant_status_reports
            + (optionally) client_bookings + blacklisted_applicants
------------------------------------------------------------ */
if (isset($_GET['action']) && $_GET['action'] === 'history' && isset($_GET['id'])) {
    header('Content-Type: application/json; charset=UTF-8');

    require_once '../includes/config.php';
    require_once '../includes/Database.php';

    $database = new Database();              // mysqli
    $conn = $database->getConnection();

    $id = (int)$_GET['id'];
    $data = [];

    // Extended unified timeline attempt: notes + status + bookings + blacklist (fallback-safe)
    $sqlExtended = "
        (
            SELECT
                'note'                        AS item_type,
                ar.note_text                  AS body,
                NULL                          AS from_status,
                NULL                          AS to_status,
                ar.created_at                 AS created_at,
                ar.id                         AS origin_id,
                COALESCE(NULLIF(au.full_name,''), NULLIF(au.username,''), NULLIF(au.email,'')) AS admin_name
            FROM applicant_reports ar
            LEFT JOIN admin_users au ON au.id = ar.admin_id
            WHERE ar.applicant_id = ?
        )
        UNION ALL
        (
            SELECT
                'status'                      AS item_type,
                asr.report_text               AS body,
                asr.from_status               AS from_status,
                asr.to_status                 AS to_status,
                asr.created_at                AS created_at,
                asr.id                        AS origin_id,
                COALESCE(NULLIF(au2.full_name,''), NULLIF(au2.username,''), NULLIF(au2.email,'')) AS admin_name
            FROM applicant_status_reports asr
            LEFT JOIN admin_users au2 ON au2.id = asr.admin_id
            WHERE asr.applicant_id = ?
        )
        UNION ALL
        (
            SELECT
                'booking'                     AS item_type,
                CONCAT(
                    'Client booking: ',
                    COALESCE(cb.appointment_type, 'Appointment'),
                    CASE
                      WHEN COALESCE(cb.client_first_name,'') <> '' OR COALESCE(cb.client_last_name,'') <> '' THEN
                        CONCAT(' — Client: ',
                               TRIM(CONCAT(COALESCE(cb.client_first_name,''),' ',COALESCE(cb.client_last_name,''))))
                      ELSE ''
                    END
                )                              AS body,
                NULL                            AS from_status,
                NULL                            AS to_status,
                cb.created_at                   AS created_at,
                cb.id                           AS origin_id,
                /* admin may be null if your table doesn't track it */
                COALESCE(NULLIF(au3.full_name,''), NULLIF(au3.username,''), NULLIF(au3.email,'')) AS admin_name
            FROM client_bookings cb
            LEFT JOIN admin_users au3 ON au3.id = cb.admin_id
            WHERE cb.applicant_id = ?
        )
        UNION ALL
        (
            SELECT
                'blacklist'                   AS item_type,
                CASE
                  WHEN b.is_active = 1
                    THEN CONCAT('Blacklisted', CASE WHEN NULLIF(b.reason, '') IS NOT NULL THEN CONCAT(' — ', b.reason) ELSE '' END)
                  ELSE 'Blacklist removed'
                END                            AS body,
                NULL                            AS from_status,
                NULL                            AS to_status,
                b.created_at                    AS created_at,
                b.id                            AS origin_id,
                COALESCE(NULLIF(au4.full_name,''), NULLIF(au4.username,''), NULLIF(au4.email,'')) AS admin_name
            FROM blacklisted_applicants b
            LEFT JOIN admin_users au4 ON au4.id = b.admin_id
            WHERE b.applicant_id = ?
        )
        ORDER BY created_at DESC, origin_id DESC
    ";

    // Original (safe) SQL with two sources only (fallback)
    $sqlBasic = "
        (
            SELECT
                'note'                        AS item_type,
                ar.note_text                  AS body,
                NULL                          AS from_status,
                NULL                          AS to_status,
                ar.created_at                 AS created_at,
                ar.id                         AS origin_id,
                COALESCE(NULLIF(au.full_name,''), NULLIF(au.username,''), NULLIF(au.email,'')) AS admin_name
            FROM applicant_reports ar
            LEFT JOIN admin_users au ON au.id = ar.admin_id
            WHERE ar.applicant_id = ?
        )
        UNION ALL
        (
            SELECT
                'status'                      AS item_type,
                asr.report_text               AS body,
                asr.from_status               AS from_status,
                asr.to_status                 AS to_status,
                asr.created_at                AS created_at,
                asr.id                        AS origin_id,
                COALESCE(NULLIF(au2.full_name,''), NULLIF(au2.username,''), NULLIF(au2.email,'')) AS admin_name
            FROM applicant_status_reports asr
            LEFT JOIN admin_users au2 ON au2.id = asr.admin_id
            WHERE asr.applicant_id = ?
        )
        ORDER BY created_at DESC, origin_id DESC
    ";

    try {
        // Try extended
        if ($stmt = $conn->prepare($sqlExtended)) {
            $stmt->bind_param("iiii", $id, $id, $id, $id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $data[] = [
                    'item_type'   => (string)($row['item_type'] ?? 'note'),
                    'body'        => (string)($row['body'] ?? ''),
                    'from_status' => (string)($row['from_status'] ?? ''),
                    'to_status'   => (string)($row['to_status'] ?? ''),
                    'created_at'  => (string)($row['created_at'] ?? ''),
                    'admin_name'  => (string)($row['admin_name'] ?? '—'),
                ];
            }
            $stmt->close();
        } else {
            // Fallback basic
            if ($stmt2 = $conn->prepare($sqlBasic)) {
                $stmt2->bind_param("ii", $id, $id);
                $stmt2->execute();
                $res2 = $stmt2->get_result();
                while ($row = $res2->fetch_assoc()) {
                    $data[] = [
                        'item_type'   => (string)($row['item_type'] ?? 'note'),
                        'body'        => (string)($row['body'] ?? ''),
                        'from_status' => (string)($row['from_status'] ?? ''),
                        'to_status'   => (string)($row['to_status'] ?? ''),
                        'created_at'  => (string)($row['created_at'] ?? ''),
                        'admin_name'  => (string)($row['admin_name'] ?? '—'),
                    ];
                }
                $stmt2->close();
            }
        }
    } catch (Throwable $e) {
        // Silent failure -> return what we have (maybe empty)
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* -----------------------------------------------------------
   NORMAL PAGE: include header to bootstrap DB/auth/session
------------------------------------------------------------ */
require_once '../includes/header.php';
require_once '../includes/Applicant.php';
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
$applicant = new Applicant($database);

/* ---------------- CSRF ---------------- */
if (empty($_SESSION['csrf_token'])) {
    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }
    catch (Throwable $e) { $_SESSION['csrf_token'] = bin2hex((string)mt_rand()); }
}

/* ---------------- Search memory ---------------- */
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    unset($_SESSION['reports_q']);
    redirect('reports.php'); exit;
}
$q = '';
if (isset($_GET['q'])) {
    $q = trim((string)$_GET['q']);
    if (mb_strlen($q) > 100) { $q = mb_substr($q, 0, 100); }
    $_SESSION['reports_q'] = $q;
} elseif (!empty($_SESSION['reports_q'])) {
    $q = (string)$_SESSION['reports_q'];
}

/* ---------------- Status filter & Sort ---------------- */
$allowedStatuses = ['all','pending','on_process','approved','on_hold','deleted'];
$status = isset($_GET['status']) ? strtolower(trim((string)$_GET['status'])) : 'all';
if (!in_array($status, $allowedStatuses, true)) $status = 'all';

$allowedSort = ['latest','reports','name','status'];
$sort = isset($_GET['sort']) ? strtolower(trim((string)$_GET['sort'])) : 'latest';
if (!in_array($sort, $allowedSort, true)) $sort = 'latest';

/* ---------------- Handle POST: add a report/note (MySQLi) ---------------- */
if (
    isset($_POST['action']) && $_POST['action'] === 'add_applicant_report' &&
    isset($_POST['id'], $_POST['note_text'], $_POST['csrf_token'])
) {
    $id       = (int)$_POST['id'];
    $noteText = trim((string)$_POST['note_text']);

    // preserve filters on redirect
    $qs = [];
    if ($q !== '') $qs['q'] = $q;
    if ($status !== 'all') $qs['status'] = $status;
    if ($sort !== 'latest') $qs['sort'] = $sort;
    $qs = $qs ? ('?' . http_build_query($qs)) : '';

    // CSRF
    $validCsrf = isset($_SESSION['csrf_token'])
        && hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token']);
    if (!$validCsrf) {
        setFlashMessage('error', 'Invalid or missing security token. Please refresh and try again.');
        redirect('reports.php' . $qs); exit;
    }

    if ($noteText === '' || mb_strlen($noteText) < 3) {
        setFlashMessage('error', 'Please write a short report (min 3 characters).');
        redirect('reports.php' . $qs); exit;
    }

    $conn = $database->getConnection(); // mysqli
    $adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;

    try {
        if ($stmt = $conn->prepare("INSERT INTO applicant_reports (applicant_id, admin_id, note_text) VALUES (?, ?, ?)")) {
            $stmt->bind_param("iis", $id, $adminId, $noteText);
            $ok = $stmt->execute();
            $stmt->close();
            if ($ok) {
                setFlashMessage('success', 'Report saved.');
                if (isset($auth) && method_exists($auth,'logActivity') && isset($_SESSION['admin_id'])) {
                    $auth->logActivity((int)$_SESSION['admin_id'], 'Add Applicant Report', "Applicant ID {$id}: " . mb_substr($noteText, 0, 200));
                }
            } else {
                setFlashMessage('error', 'Failed to save report. Please try again.');
            }
        } else {
            setFlashMessage('error', 'Failed to save report. Please try again.');
        }
    } catch (Throwable $e) {
        setFlashMessage('error', 'Failed to save report. Please try again.');
    }

    redirect('reports.php' . $qs); exit;
}

/* ---------------- Fetch Applicants (deduplicated) ----------------
   - Show each applicant ONCE only.
   - Require at least one note (applicant_reports exists).
   - Latest NOTE per applicant by MAX(id) to avoid timestamp ties.
   - Latest STATUS change per applicant by MAX(id) for arrow display.
-------------------------------------------------------------------- */
$conn = $database->getConnection(); // mysqli

$whereStatus = "a.deleted_at IS NULL AND (SELECT COUNT(*) FROM applicant_reports r2 WHERE r2.applicant_id = a.id) > 0";
$params = [];
$types  = '';

if ($status !== 'all') {
    $whereStatus .= " AND a.status = ?";
    $params[] = $status;
    $types   .= 's';
}

// Sorting
$orderSql = " ORDER BY lr.id DESC, a.id DESC"; // newest note first
if ($sort === 'reports') {
    $orderSql = " ORDER BY report_count DESC, lr.id DESC, a.id DESC";
} elseif ($sort === 'name') {
    $orderSql = " ORDER BY a.last_name ASC, a.first_name ASC, a.id ASC";
} elseif ($sort === 'status') {
    $orderSql = " ORDER BY a.status ASC, lr.id DESC, a.id DESC";
}

$sql = "
SELECT
  a.*,
  /* Latest NOTE per applicant - by MAX(id) to avoid duplicate ties */
  lr.note_text         AS latest_note,
  lr.created_at        AS latest_note_at,
  lr.id                AS latest_note_id,
  COALESCE(NULLIF(au.full_name,''), NULLIF(au.username,''), NULLIF(au.email,'')) AS latest_note_admin,
  /* Latest STATUS row for arrow display */
  lsr.from_status      AS last_from_status,
  lsr.to_status        AS last_to_status,
  lsr.created_at       AS last_status_at,
  /* Total NOTE count */
  (SELECT COUNT(*) FROM applicant_reports r2 WHERE r2.applicant_id = a.id) AS report_count
FROM applicants a
/* latest note */
LEFT JOIN (
  SELECT ar1.*
  FROM applicant_reports ar1
  INNER JOIN (
    SELECT applicant_id, MAX(id) AS max_id
    FROM applicant_reports
    GROUP BY applicant_id
  ) t ON t.applicant_id = ar1.applicant_id AND t.max_id = ar1.id
) lr ON lr.applicant_id = a.id
LEFT JOIN admin_users au ON au.id = lr.admin_id
/* latest status change */
LEFT JOIN (
  SELECT asr1.*
  FROM applicant_status_reports asr1
  INNER JOIN (
    SELECT applicant_id, MAX(id) AS max_sid
    FROM applicant_status_reports
    GROUP BY applicant_id
  ) ts ON ts.applicant_id = asr1.applicant_id AND ts.max_sid = asr1.id
) lsr ON lsr.applicant_id = a.id
WHERE {$whereStatus}
{$orderSql}
";

$rows = [];
try {
    if ($types !== '') {
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            $rows = $res->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    } else {
        if ($res = $conn->query($sql)) {
            $rows = $res->fetch_all(MYSQLI_ASSOC);
        }
    }
} catch (Throwable $e) {
    $rows = [];
}

/* ---------------- Filter in PHP (search) ---------------- */
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
        $latest = (string)($row['latest_note']  ?? '');
        $admin  = (string)($row['latest_note_admin'] ?? '');

        $fullName = trim("$first $middle $last $suffix");
        $hay = mb_strtolower(implode(' | ', [$first,$middle,$last,$suffix,$fullName,$email,$phone,$latest,$admin]));
        return mb_strpos($hay, $needle) !== false;
    }));
}
if ($q !== '') { $rows = filterRowsByQuery($rows, $q); }

/* ---------------- Export link (keep, with filters) ---------------- */
$qParams = [];
if ($q !== '') $qParams['q'] = $q;
if ($status !== 'all') $qParams['status'] = $status;
if ($sort !== 'latest') $qParams['sort'] = $sort;
$exportUrl = '../includes/excel_reports.php' . ($qParams ? ('?' . http_build_query($qParams)) : '');

/* ---------------- Helpers ---------------- */
function statusBadgeProps(string $status): array {
    switch($status) {
        case 'pending':    return ['badge' => 'bg-warning text-dark', 'label' => 'Pending'];
        case 'on_process': return ['badge' => 'bg-info text-dark',    'label' => 'On Process'];
        case 'approved':   return ['badge' => 'bg-success',           'label' => 'Approved'];
        case 'on_hold':    return ['badge' => 'bg-secondary',         'label' => 'On Hold'];
        case 'deleted':    return ['badge' => 'bg-danger',            'label' => 'Deleted'];
        default:           return ['badge' => 'bg-secondary',         'label' => $status];
    }
}
?>
<style>
  .table-card, .table-card .card-body { overflow: visible !important; }
  .table-card .table-responsive { overflow: visible !important; }
  td.actions-cell { white-space: nowrap; }
  .actions-inline { display: inline-flex; gap: .5rem; align-items: center; flex-wrap: nowrap; }
  .actions-inline .btn { flex: 0 0 auto; }
  .report-count-badge { font-size: .75rem; }
  .note-clamp {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    max-width: 560px;
  }
  .status-badge { font-weight: 600; }
  .toolbar .form-select, .toolbar .form-control { border-radius: .7rem; }
  .toolbar .btn { border-radius: .7rem; }

  /* History modal timeline */
  .timeline .list-group-item { border-left: 3px solid #e5e7eb; }
  .timeline .item-note      { border-left-color: #0d6efd; }   /* blue */
  .timeline .item-status    { border-left-color: #20c997; }   /* teal/green */
  .timeline .item-booking   { border-left-color: #6366f1; }   /* indigo */
  .timeline .item-blacklist { border-left-color: #ef4444; }   /* red */
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0 fw-semibold">Reports</h4>
    <div class="text-muted small">Applicants with at least one report</div>
  </div>
  <a href="<?php echo htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">
    <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
  </a>
</div>

<!-- Filters / Search toolbar -->
<form method="get" action="reports.php" class="toolbar mb-3">
  <div class="row g-2 align-items-center">
    <div class="col-12 col-md-5">
      <div class="input-group">
        <input
          type="text"
          name="q"
          class="form-control"
          placeholder="Search name, email, notes, admin…"
          value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>"
          autocomplete="off"
        >
        <?php if ($q !== ''): ?>
          <a class="btn btn-outline-secondary" href="reports.php?clear=1" title="Clear">
            <i class="bi bi-x-lg"></i>
          </a>
        <?php endif; ?>
        <button class="btn btn-outline-secondary" type="submit" title="Search">
          <i class="bi bi-search"></i>
        </button>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <select name="status" class="form-select" onchange="this.form.submit()">
        <?php
          $statusOptions = [
            'all'        => 'All statuses',
            'pending'    => 'Pending',
            'on_process' => 'On Process',
            'approved'   => 'Approved',
            'on_hold'    => 'On Hold',
            'deleted'    => 'Deleted'
          ];
          foreach ($statusOptions as $val => $label) {
              $sel = $status === $val ? 'selected' : '';
              echo '<option value="'.htmlspecialchars($val,ENT_QUOTES).'" '.$sel.'>'.htmlspecialchars($label).'</option>';
          }
        ?>
      </select>
    </div>
    <div class="col-6 col-md-3">
      <select name="sort" class="form-select" onchange="this.form.submit()">
        <?php
          $sortOptions = [
            'latest'  => 'Sort: Newest report',
            'reports' => 'Sort: Most reports',
            'name'    => 'Sort: Name (A–Z)',
            'status'  => 'Sort: Status'
          ];
          foreach ($sortOptions as $val => $label) {
              $sel = $sort === $val ? 'selected' : '';
              echo '<option value="'.htmlspecialchars($val,ENT_QUOTES).'" '.$sel.'>'.htmlspecialchars($label).'</option>';
          }
        ?>
      </select>
    </div>
    <div class="col-12 col-md-1 text-md-end"><!-- reserved --></div>
  </div>
</form>

<div class="card table-card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-borderless align-middle">
        <thead class="border-bottom">
          <tr class="text-muted small">
            <th style="width:56px">Photo</th>
            <th>Applicant</th>
            <th>Status</th>
            <th>Latest Report</th>
            <th>Count</th>
            <th>Reported By</th>
            <th>Reported At</th>
            <th style="width: 320px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr>
              <td colspan="8" class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                <?php if ($q === '' && $status === 'all'): ?>
                  No applicants with reports found.
                <?php else: ?>
                  No results for your current filters.
                  <a href="reports.php?clear=1" class="ms-1">Clear filters</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <?php
                $id = (int)$r['id'];
                $name = getFullName($r['first_name'], $r['middle_name'], $r['last_name'], $r['suffix']);
                $latestNote = (string)($r['latest_note'] ?? '');
                $latestAdmin = (string)($r['latest_note_admin'] ?? '—');
                $latestAt = (string)($r['latest_note_at'] ?? '');
                $reportCount = (int)($r['report_count'] ?? 0);
                $statusVal = (string)($r['status'] ?? '');

                // Status badge + last change arrow if present
                $props = statusBadgeProps($statusVal);
                $arrow = '';
                $last_from = (string)($r['last_from_status'] ?? '');
                $last_to   = (string)($r['last_to_status']   ?? '');
                if ($last_from !== '' && $last_to !== '' && strcasecmp($last_from, $last_to) !== 0) {
                    $arrow = '<div class="small text-muted mt-1">'.
                        htmlspecialchars(ucwords(str_replace('_',' ', $last_from)), ENT_QUOTES, 'UTF-8').' &rarr; '.
                        htmlspecialchars(ucwords(str_replace('_',' ', $last_to)), ENT_QUOTES, 'UTF-8').
                        '</div>';
                }
              ?>
              <tr class="border-bottom">
                <td>
                  <?php if (!empty($r['picture'])): ?>
                    <img src="<?php echo htmlspecialchars(getFileUrl($r['picture']), ENT_QUOTES, 'UTF-8'); ?>"
                         alt="Photo" class="rounded" width="48" height="48" style="object-fit: cover;">
                  <?php else: ?>
                    <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center"
                         style="width:48px;height:48px;">
                      <?php echo strtoupper(substr((string)$r['first_name'], 0, 1)); ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td class="fw-semibold">
                  <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
                </td>
                <td>
                  <span class="badge status-badge <?php echo $props['badge']; ?>">
                    <?php echo htmlspecialchars($props['label'], ENT_QUOTES, 'UTF-8'); ?>
                  </span>
                  <?php echo $arrow; ?>
                </td>
                <td>
                  <?php if ($latestNote !== ''): ?>
                    <div class="note-clamp text-secondary">
                      <?php echo nl2br(htmlspecialchars($latestNote, ENT_QUOTES, 'UTF-8')); ?>
                    </div>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge bg-primary-subtle text-primary border report-count-badge"
                        title="Total reports for this applicant">
                        <?php echo $reportCount; ?>
                  </span>
                </td>
                <td class="text-secondary">
                  <?php echo htmlspecialchars($latestAdmin ?: '—', ENT_QUOTES, 'UTF-8'); ?>
                </td>
                <td class="text-secondary">
                  <?php echo htmlspecialchars($latestAt !== '' ? formatDateTime($latestAt) : '—', ENT_QUOTES, 'UTF-8'); ?>
                </td>
                <td class="actions-cell">
                  <div class="actions-inline">
                    <!-- Write report -->
                    <button class="btn btn-sm btn-primary write-report"
                            data-id="<?php echo $id; ?>"
                            data-name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                      <i class="bi bi-journal-plus me-1"></i> Write Report
                    </button>

                    <!-- Full History (modal) -->
                    <button class="btn btn-sm btn-outline-secondary view-history"
                            data-id="<?php echo $id; ?>"
                            data-name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                      <i class="bi bi-clock-history me-1"></i> Full History
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal: Write Report -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" action="reports.php" id="reportForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold" id="reportModalLabel">Write Report</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2 text-muted small">Applicant: <span id="rpt-applicant" class="fw-semibold"></span></div>
        <div class="mb-3">
          <label for="note_text" class="form-label">Report / Notes <span class="text-danger">*</span></label>
          <textarea class="form-control" id="note_text" name="note_text" rows="5"
                    required minlength="3" maxlength="4000"
                    placeholder="Write your report or notes..."></textarea>
        </div>
        <input type="hidden" name="action" value="add_applicant_report">
        <input type="hidden" name="id" id="rpt-id" value="">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save2 me-1"></i> Save Report</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Unified Timeline (History) -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold">Full History — <span id="hist-applicant"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="hist-container" class="timeline"><!-- Filled by JS --></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var reportModalEl = document.getElementById('reportModal');
  var historyModalEl = document.getElementById('historyModal');
  var reportModal = (typeof bootstrap !== 'undefined' && bootstrap.Modal) ? new bootstrap.Modal(reportModalEl) : null;
  var historyModal = (typeof bootstrap !== 'undefined' && bootstrap.Modal) ? new bootstrap.Modal(historyModalEl) : null;

  // Write Report button
  document.querySelectorAll('.write-report').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.dataset.id || '';
      var name = btn.dataset.name || '';
      if (!id) return;
      document.getElementById('rpt-id').value = id;
      document.getElementById('rpt-applicant').textContent = name;
      document.getElementById('note_text').value = '';
      if (reportModal) reportModal.show();
    });
  });

  // Full History (unified timeline)
  document.querySelectorAll('.view-history').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.dataset.id || '';
      var name = btn.dataset.name || '';
      if (!id) return;
      document.getElementById('hist-applicant').textContent = name;
      var container = document.getElementById('hist-container');
      container.innerHTML = '<div class="text-muted">Loading history...</div>';

      fetch('reports.php?action=history&id=' + encodeURIComponent(id), { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (items) {
          if (!Array.isArray(items) || items.length === 0) {
            container.innerHTML = '<div class="text-muted">No history found for this applicant.</div>';
            return;
          }
          var html = '<div class="list-group">';
          items.forEach(function (it) {
            var type = (it.item_type || 'note').toLowerCase();
            var admin = it.admin_name || '—';
            var when  = it.created_at || '';
            var body  = it.body || '';

            if (type === 'status') {
              var froms = (it.from_status || '').replaceAll('_',' ');
              var tos   = (it.to_status   || '').replaceAll('_',' ');
              html += '<div class="list-group-item item-status py-3">';
              html +=   '<div class="d-flex justify-content-between align-items-center mb-1">';
              html +=     '<div class="fw-semibold text-success"><i class="bi bi-arrow-left-right me-1"></i>'+ escapeHtml(cap(froms)) +' → '+ escapeHtml(cap(tos)) +'</div>';
              html +=     '<div class="text-muted small">'+ escapeHtml(when) +'</div>';
              html +=   '</div>';
              if (body) {
                html +=   '<div class="text-secondary" style="white-space:pre-wrap;">'+ escapeHtml(body) +'</div>';
              }
              html +=   '<div class="small text-muted mt-1">By '+ escapeHtml(admin) +'</div>';
              html += '</div>';
            } else if (type === 'booking') {
              html += '<div class="list-group-item item-booking py-3">';
              html +=   '<div class="d-flex justify-content-between align-items-center mb-1">';
              html +=     '<div class="fw-semibold text-indigo"><i class="bi bi-calendar2-check me-1"></i>Booking</div>';
              html +=     '<div class="text-muted small">'+ escapeHtml(when) +'</div>';
              html +=   '</div>';
              html +=   '<div class="text-secondary" style="white-space:pre-wrap;">'+ escapeHtml(body) +'</div>';
              html +=   '<div class="small text-muted mt-1">'+ (admin ? ('By '+ escapeHtml(admin)) : 'System') +'</div>';
              html += '</div>';
            } else if (type === 'blacklist') {
              html += '<div class="list-group-item item-blacklist py-3">';
              html +=   '<div class="d-flex justify-content-between align-items-center mb-1">';
              html +=     '<div class="fw-semibold text-danger"><i class="bi bi-slash-circle me-1"></i>Blacklist</div>';
              html +=     '<div class="text-muted small">'+ escapeHtml(when) +'</div>';
              html +=   '</div>';
              html +=   '<div class="text-secondary" style="white-space:pre-wrap;">'+ escapeHtml(body) +'</div>';
              html +=   '<div class="small text-muted mt-1">By '+ escapeHtml(admin) +'</div>';
              html += '</div>';
            } else {
              // note
              html += '<div class="list-group-item item-note py-3">';
              html +=   '<div class="d-flex justify-content-between align-items-center mb-1">';
              html +=     '<div class="fw-semibold"><i class="bi bi-journal-text me-1"></i>Report</div>';
              html +=     '<div class="text-muted small">'+ escapeHtml(when) +'</div>';
              html +=   '</div>';
              html +=   '<div class="text-secondary" style="white-space:pre-wrap;">'+ escapeHtml(body) +'</div>';
              html +=   '<div class="small text-muted mt-1">By '+ escapeHtml(admin) +'</div>';
              html += '</div>';
            }
          });
          html += '</div>';
          container.innerHTML = html;
        })
        .catch(function () {
          container.innerHTML = '<div class="text-danger">Failed to load history. Please try again.</div>';
        });

      if (historyModal) historyModal.show();
    });
  });

  function escapeHtml(s) {
    return (s||'').replace(/[&<>"']/g, function(c){
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;', "'":'&#039;'}[c];
    });
  }
  function cap(s) {
    s = s || '';
    return s.replace(/\b\w/g, function(m){ return m.toUpperCase(); });
  }
});
</script>

<?php require_once '../includes/footer.php'; ?>