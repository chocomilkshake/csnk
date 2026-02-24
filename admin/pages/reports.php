<?php
// FILE: pages/reports.php
$pageTitle = 'Reports - All Applicants';

/* -----------------------------------------------------------
   INLINE JSON ENDPOINT (NO LAYOUT): Full history for applicant
   GET  reports.php?action=history&id=123
   Combines: applicant_reports + applicant_status_reports + applicant_replacements
   Sorted: past -> recent (ASC)
------------------------------------------------------------ */
if (isset($_GET['action']) && $_GET['action'] === 'history' && isset($_GET['id'])) {
    header('Content-Type: application/json; charset=UTF-8');

    require_once '../includes/config.php';
    require_once '../includes/Database.php';

    $database = new Database();              // mysqli
    $conn = $database->getConnection();

    $id = (int)$_GET['id'];
    $data = [];

    // Unified timeline (3 sources) in ascending order
    $sql = "
        (
            SELECT
                'note' AS item_type,
                ar.note_text AS body,
                NULL AS from_status,
                NULL AS to_status,
                ar.created_at AS created_at,
                ar.id AS origin_id,
                COALESCE(NULLIF(au.full_name,''), NULLIF(au.username,''), NULLIF(au.email,'')) AS admin_name,
                NULL AS role,
                NULL AS orig_id,
                NULL AS repl_id,
                NULL AS reason,
                NULL AS replacement_status,
                NULL AS assigned_at
            FROM applicant_reports ar
            LEFT JOIN admin_users au ON au.id = ar.admin_id
            WHERE ar.applicant_id = ?
        )
        UNION ALL
        (
            SELECT
                'status' AS item_type,
                asr.report_text AS body,
                asr.from_status AS from_status,
                asr.to_status AS to_status,
                asr.created_at AS created_at,
                asr.id AS origin_id,
                COALESCE(NULLIF(au2.full_name,''), NULLIF(au2.username,''), NULLIF(au2.email,'')) AS admin_name,
                NULL AS role,
                NULL AS orig_id,
                NULL AS repl_id,
                NULL AS reason,
                NULL AS replacement_status,
                NULL AS assigned_at
            FROM applicant_status_reports asr
            LEFT JOIN admin_users au2 ON au2.id = asr.admin_id
            WHERE asr.applicant_id = ?
        )
        UNION ALL
        (
            SELECT
                'replacement' AS item_type,
                ar2.report_text AS body,
                NULL AS from_status,
                NULL AS to_status,
                ar2.created_at AS created_at,
                ar2.id AS origin_id,
                COALESCE(NULLIF(au3.full_name,''), NULLIF(au3.username,''), NULLIF(au3.email,'')) AS admin_name,
                CASE
                    WHEN ar2.original_applicant_id = ? THEN 'replacement_out'
                    WHEN ar2.replacement_applicant_id = ? THEN 'replacement_in'
                    ELSE 'replacement'
                END AS role,
                ar2.original_applicant_id AS orig_id,
                ar2.replacement_applicant_id AS repl_id,
                ar2.reason AS reason,
                ar2.status AS replacement_status,
                ar2.assigned_at AS assigned_at
            FROM applicant_replacements ar2
            LEFT JOIN admin_users au3 ON au3.id = ar2.created_by
            WHERE ar2.original_applicant_id = ? OR ar2.replacement_applicant_id = ?
        )
        ORDER BY created_at ASC, origin_id ASC
    ";

    try {
        if ($stmt = $conn->prepare($sql)) {
            // placeholders: notes(1), status(1), role-check(2), filter(2) => total 6
            $stmt->bind_param("iiiiii", $id, $id, $id, $id, $id, $id);
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
                    'role'        => (string)($row['role'] ?? ''),
                    'orig_id'     => isset($row['orig_id']) ? (int)$row['orig_id'] : null,
                    'repl_id'     => isset($row['repl_id']) ? (int)$row['repl_id'] : null,
                    'reason'      => (string)($row['reason'] ?? ''),
                    'replacement_status' => (string)($row['replacement_status'] ?? ''),
                    'assigned_at' => (string)($row['assigned_at'] ?? ''),
                ];
            }
            $stmt->close();
        }
    } catch (Throwable $e) {
        // swallow; return what we have
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
   - Latest STATUS change per applicant by MAX(id) (used for history only).
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
  /* Latest STATUS row kept for context (not shown inline) */
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
  /* ====== Modern Table Layout ====== */
  .table-card, .table-card .card-body { overflow: visible !important; }
  .table-card .table-responsive { overflow: visible !important; }

  .table-modern {
    table-layout: fixed;
    border-collapse: separate;
    border-spacing: 0;
  }
  .table-modern thead th {
    font-weight: 600;
    color: #6b7280;
    white-space: nowrap;
  }
  .table-modern tbody tr { border-bottom: 1px solid #eef2f7; }
  .table-modern tbody tr:hover { background: #fafbfc; }

  td.actions-cell { white-space: nowrap; }

  /* Column widths via colgroup */
  col.col-photo    { width: 64px; }
  col.col-name     { width: 280px; }
  col.col-status   { width: 140px; }
  col.col-latest   { width: auto; }
  col.col-count    { width: 80px;  }
  col.col-by       { width: 190px; }
  col.col-at       { width: 170px; }
  col.col-actions  { width: 260px; }

  /* Photo */
  .avatar-48 { width: 48px; height: 48px; object-fit: cover; border-radius: .6rem; }
  .avatar-fallback {
    width: 48px; height: 48px; border-radius: .6rem;
    background: #e5e7eb; color: #374151; font-weight: 700;
    display: grid; place-items: center;
  }

  /* Name */
  .name-cell { font-weight: 600; line-height: 1.2; }

  /* Status badge only (no icon, no extra text) */
  .status-badge { font-weight: 600; border-radius: 999px; padding: .25rem .6rem; font-size: .8rem; white-space: nowrap; }

  /* Latest report text clamp */
  .note-clamp {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    color: #4b5563;
    max-width: 100%;
  }

  /* Count */
  .report-count-badge {
    font-size: .75rem; border-radius: 999px; padding: .25rem .5rem;
    background: #eef6ff; color: #1d4ed8; border: 1px solid #dbeafe;
  }

  /* Toolbar */
  .toolbar .form-select, .toolbar .form-control { border-radius: .7rem; }
  .toolbar .btn { border-radius: .7rem; }

  /* ====== History modal (vertical timeline) ====== */
  .timeline {
    position: relative;
    margin-left: .5rem;
    padding-left: 1.25rem;
  }
  .timeline:before {
    content: "";
    position: absolute;
    left: .25rem;
    top: .25rem;
    bottom: .25rem;
    width: 2px;
    background: #e5e7eb;
  }
  .tl-item {
    position: relative;
    padding: .5rem 0 .5rem 1rem;
  }
  .tl-dot {
    position: absolute;
    left: -2px;
    width: .75rem; height: .75rem;
    border-radius: 999px;
    background: #94a3b8;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #e5e7eb;
    top: .9rem;
  }
  .tl-item.note   .tl-dot { background: #0d6efd; }
  .tl-item.status .tl-dot { background: #20c997; }
  .tl-item.repl   .tl-dot { background: #6366f1; }

  .tl-head {
    display: flex; justify-content: space-between; align-items: baseline;
    margin-bottom: .25rem;
  }
  .tl-title { font-weight: 600; }
  .tl-meta  { color: #6b7280; font-size: .85rem; white-space: nowrap; margin-left: .5rem; }
  .tl-body  { color: #4b5563; white-space: pre-wrap; }

  .text-indigo { color: #6366f1; }
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
      <table class="table table-borderless align-middle table-modern">
        <colgroup>
          <col class="col-photo">
          <col class="col-name">
          <col class="col-status">
          <col class="col-latest">
          <col class="col-count">
          <col class="col-by">
          <col class="col-at">
          <col class="col-actions">
        </colgroup>

        <thead class="border-bottom">
          <tr class="text-muted small">
            <th>Photo</th>
            <th>Applicant</th>
            <th>Status</th>
            <th>Latest Report</th>
            <th class="text-center">Count</th>
            <th>Reported By</th>
            <th>Reported At</th>
            <th>Actions</th>
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

                $props = statusBadgeProps($statusVal); // badge only (no icon)
              ?>
              <tr>
                <td>
                  <?php if (!empty($r['picture'])): ?>
                    <img src="<?php echo htmlspecialchars(getFileUrl($r['picture']), ENT_QUOTES, 'UTF-8'); ?>"
                         alt="Photo" class="avatar-48">
                  <?php else: ?>
                    <div class="avatar-fallback">
                      <?php echo strtoupper(substr((string)$r['first_name'], 0, 1)); ?>
                    </div>
                  <?php endif; ?>
                </td>

                <td class="name-cell">
                  <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
                </td>

                <td>
                  <span class="badge status-badge <?php echo $props['badge']; ?>">
                    <?php echo htmlspecialchars($props['label'], ENT_QUOTES, 'UTF-8'); ?>
                  </span>
                </td>

                <td>
                  <?php if ($latestNote !== ''): ?>
                    <div class="note-clamp">
                      <?php echo nl2br(htmlspecialchars($latestNote, ENT_QUOTES, 'UTF-8')); ?>
                    </div>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>

                <td class="text-center">
                  <span class="report-count-badge" title="Total reports for this applicant">
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
                  <div class="d-inline-flex gap-2 align-items-center">
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

  // Full History (unified timeline: notes + status + replacements)
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
          var html = '';
          items.forEach(function (it) {
            var type = (it.item_type || 'note').toLowerCase();
            var admin = it.admin_name || '—';
            var when  = it.created_at || '';
            var body  = it.body || '';

            if (type === 'status') {
              var froms = (it.from_status || '').replaceAll('_',' ');
              var tos   = (it.to_status   || '').replaceAll('_',' ');
              html += '<div class="tl-item status">';
              html +=   '<span class="tl-dot"></span>';
              html +=   '<div class="tl-head">';
              html +=     '<div class="tl-title text-success"><i class="bi bi-arrow-left-right me-1"></i>'+ escapeHtml(cap(froms)) +' → '+ escapeHtml(cap(tos)) +'</div>';
              html +=     '<div class="tl-meta">'+ escapeHtml(when) +'</div>';
              html +=   '</div>';
              if (body) {
                html +=   '<div class="tl-body">'+ escapeHtml(body) +'</div>';
              }
              html +=   '<div class="small text-muted mt-1">By '+ escapeHtml(admin) +'</div>';
              html += '</div>';
            } else if (type === 'replacement') {
              var role = (it.role || '').toLowerCase();
              var orig = it.orig_id || '';
              var repl = it.repl_id || '';
              var reason = it.reason || '';
              var rstat  = it.replacement_status || '';
              var assigned = it.assigned_at || '';
              var line = '';
              if (role === 'replacement_out') {
                line = 'Replacement created — Replaced by Applicant ID ' + escapeHtml(String(repl || '—')) + (reason ? (' • Reason: ' + escapeHtml(reason)) : '');
              } else if (role === 'replacement_in') {
                line = 'Replacement assignment — Assigned as replacement for Applicant ID ' + escapeHtml(String(orig || '—')) + (reason ? (' • Reason: ' + escapeHtml(reason)) : '');
              } else {
                line = 'Replacement entry';
              }
              if (rstat) { line += ' • Status: ' + escapeHtml(rstat); }
              if (assigned) { line += ' • Assigned at: ' + escapeHtml(assigned); }

              html += '<div class="tl-item repl">';
              html +=   '<span class="tl-dot"></span>';
              html +=   '<div class="tl-head">';
              html +=     '<div class="tl-title text-indigo"><i class="bi bi-arrow-repeat me-1"></i>' + line + '</div>';
              html +=     '<div class="tl-meta">'+ escapeHtml(when) +'</div>';
              html +=   '</div>';
              if (body) {
                html +=   '<div class="tl-body">'+ escapeHtml(body) +'</div>';
              }
              html +=   '<div class="small text-muted mt-1">By '+ escapeHtml(admin) +'</div>';
              html += '</div>';
            } else {
              // note
              html += '<div class="tl-item note">';
              html +=   '<span class="tl-dot"></span>';
              html +=   '<div class="tl-head">';
              html +=     '<div class="tl-title"><i class="bi bi-journal-text me-1"></i>Report</div>';
              html +=     '<div class="tl-meta">'+ escapeHtml(when) +'</div>';
              html +=   '</div>';
              html +=   '<div class="tl-body">'+ escapeHtml(body) +'</div>';
              html +=   '<div class="small text-muted mt-1">By '+ escapeHtml(admin) +'</div>';
              html += '</div>';
            }
          });
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