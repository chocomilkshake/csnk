<?php
// FILE: pages/reports.php
$pageTitle = 'Reports (Approved Applicants)';
require_once '../includes/Applicant.php'; // <-- load only what you need for now

if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

/* ---------------- Inline endpoint: fetch full history as JSON ----------------
   GET  reports.php?action=history&id=123
   Returns [{admin_name, created_at, note_text}, ...] (newest first)
-------------------------------------------------------------------------------*/
if (isset($_GET['action']) && $_GET['action'] === 'history' && isset($_GET['id'])) {
    header('Content-Type: application/json; charset=UTF-8');

    // You still need $database. Bootstrap the DB like header.php does:
    require_once '../includes/config.php';
    require_once '../includes/Database.php';
    $database = new Database();
    $conn = $database->getConnection(); // mysqli

    $id = (int)$_GET['id'];
    $data = [];
    try {
        $sqlH = "
            SELECT
                ar.note_text,
                ar.created_at,
                COALESCE(NULLIF(au.full_name,''), NULLIF(au.username,''), NULLIF(au.email,'')) AS admin_name
            FROM applicant_reports ar
            LEFT JOIN admin_users au ON au.id = ar.admin_id
            WHERE ar.applicant_id = ?
            ORDER BY ar.created_at DESC, ar.id DESC
        ";
        $stmt = $conn->prepare($sqlH);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $data[] = [
                'note_text'  => (string)($row['note_text'] ?? ''),
                'created_at' => (string)($row['created_at'] ?? ''),
                'admin_name' => (string)($row['admin_name'] ?? '—'),
            ];
        }
        $stmt->close();
    } catch (Throwable $e) {
        error_log('History fetch failed: ' . $e->getMessage());
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ---- Normal page rendering continues below ----
require_once '../includes/header.php';
require_once '../includes/Applicant.php';
$applicant = new Applicant($database);
/* ---------------- CSRF ---------------- */
if (empty($_SESSION['csrf_token'])) {
    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }
    catch (Throwable $e) { $_SESSION['csrf_token'] = bin2hex((string)mt_rand()); }
}

/* ---------------- Search memory (similar to approved.php) ---------------- */
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

/* ---------------- Inline endpoint: fetch full history as JSON ----------------
   GET  reports.php?action=history&id=123
   Returns [{admin_name, created_at, note_text}, ...] (newest first)
-------------------------------------------------------------------------------*/
if (isset($_GET['action']) && $_GET['action'] === 'history' && isset($_GET['id'])) {
    header('Content-Type: application/json; charset=UTF-8');
    $id = (int)$_GET['id'];
    $conn = $database->getConnection(); // mysqli
    $data = [];
    try {
        $sqlH = "
            SELECT
                ar.note_text,
                ar.created_at,
                COALESCE(NULLIF(au.full_name,''), NULLIF(au.username,''), NULLIF(au.email,'')) AS admin_name
            FROM applicant_reports ar
            LEFT JOIN admin_users au ON au.id = ar.admin_id
            WHERE ar.applicant_id = ?
            ORDER BY ar.created_at DESC, ar.id DESC
        ";
        $stmt = $conn->prepare($sqlH);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $data[] = [
                'note_text'  => (string)($row['note_text'] ?? ''),
                'created_at' => (string)($row['created_at'] ?? ''),
                'admin_name' => (string)($row['admin_name'] ?? '—'),
            ];
        }
        $stmt->close();
    } catch (Throwable $e) {
        error_log('History fetch failed: ' . $e->getMessage());
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------------- Handle POST: add a report/note (MySQLi) ----------------
   APPENDS a new row always (never updates) => all previous reports are preserved
-------------------------------------------------------------------------------*/
if (
    isset($_POST['action']) && $_POST['action'] === 'add_applicant_report' &&
    isset($_POST['id'], $_POST['note_text'], $_POST['csrf_token'])
) {
    $id       = (int)$_POST['id'];
    $noteText = trim((string)$_POST['note_text']);

    // preserve q on redirect
    $qs = $q !== '' ? ('?q=' . urlencode($q)) : '';

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
        $stmt = $conn->prepare("INSERT INTO applicant_reports (applicant_id, admin_id, note_text) VALUES (?, ?, ?)");
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
    } catch (Throwable $e) {
        error_log('Add report failed: ' . $e->getMessage());
        setFlashMessage('error', 'Failed to save report. Please try again.');
    }

    redirect('reports.php' . $qs); exit;
}

/* ---------------- Fetch Approved + Latest Report + Report Count ------------- */
$conn = $database->getConnection(); // mysqli
$sql = "
SELECT
  a.*,
  /* Latest report per applicant */
  lr.note_text         AS latest_note,
  lr.created_at        AS latest_note_at,
  COALESCE(NULLIF(au.full_name,''), NULLIF(au.username,''), NULLIF(au.email,'')) AS latest_note_admin,
  /* Total report count for badge/info */
  (SELECT COUNT(*) FROM applicant_reports r2 WHERE r2.applicant_id = a.id) AS report_count
FROM applicants a
LEFT JOIN (
  SELECT ar1.*
  FROM applicant_reports ar1
  INNER JOIN (
    SELECT applicant_id, MAX(created_at) AS max_created
    FROM applicant_reports
    GROUP BY applicant_id
  ) t ON t.applicant_id = ar1.applicant_id AND t.max_created = ar1.created_at
) lr ON lr.applicant_id = a.id
LEFT JOIN admin_users au ON au.id = lr.admin_id
WHERE a.deleted_at IS NULL AND a.status = 'approved'
ORDER BY a.created_at DESC
";

$rows = [];
if ($res = $conn->query($sql)) { $rows = $res->fetch_all(MYSQLI_ASSOC); }

/* ---------------- Filter in PHP (NO preferred location anymore) ------------- */
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

/* ---------------- Export link (keeps your existing exporter) ---------------- */
$exportUrl = '../includes/excel_reports.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
?>
<style>
    .table-card, .table-card .card-body { overflow: visible !important; }
    .table-card .table-responsive { overflow: visible !important; }
    td.actions-cell { position: relative; overflow: visible; z-index: 10; white-space: nowrap; }
    .report-count-badge { font-size: .8rem; }
    .history-note { white-space: pre-wrap; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-semibold">Reports — Approved Applicants</h4>
    <a href="<?php echo htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">
        <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
    </a>
</div>

<!-- Search -->
<div class="mb-3 d-flex justify-content-end">
    <form method="get" action="reports.php" class="w-100" style="max-width: 420px;">
        <div class="input-group">
            <input type="text" name="q" class="form-control"
                   placeholder="Search approved, notes or admin..."
                   value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
            <button class="btn btn-outline-secondary" type="submit" title="Search">
                <i class="bi bi-search"></i>
            </button>
            <?php if ($q !== ''): ?>
                <a class="btn btn-outline-secondary" href="reports.php?clear=1" title="Clear">
                    <i class="bi bi-x-lg"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card table-card">
  <div class="card-body">
    <table class="table table-bordered table-striped table-hover table-styled align-middle">
      <thead>
        <tr>
            <th>Photo</th>
            <th>Applicant</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Latest Report</th>
            <th>Reported By</th>
            <th>Reported At</th>
            <th style="width: 260px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="8" class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                <?php if ($q === ''): ?>
                    No approved applicants yet.
                <?php else: ?>
                    No results for "<strong><?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?></strong>".
                    <a href="reports.php?clear=1" class="ms-1">Clear search</a>
                <?php endif; ?>
            </td></tr>
        <?php else: ?>
            <?php foreach ($rows as $r): ?>
                <?php
                    $id = (int)$r['id'];
                    $name = getFullName($r['first_name'], $r['middle_name'], $r['last_name'], $r['suffix']);
                    $latestNote = (string)($r['latest_note'] ?? '');
                    $latestAdmin = (string)($r['latest_note_admin'] ?? '—');
                    $latestAt = (string)($r['latest_note_at'] ?? '');
                    $reportCount = (int)($r['report_count'] ?? 0);
                ?>
                <tr>
                    <td>
                        <?php if (!empty($r['picture'])): ?>
                            <img src="<?php echo htmlspecialchars(getFileUrl($r['picture']), ENT_QUOTES, 'UTF-8'); ?>"
                                 alt="Photo" class="rounded" width="50" height="50" style="object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center"
                                 style="width:50px;height:50px;"><?php echo strtoupper(substr((string)$r['first_name'],0,1)); ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="fw-semibold">
                        <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
                        <?php if ($reportCount > 0): ?>
                            <span class="badge bg-primary-subtle text-primary border ms-2 report-count-badge"
                                  title="Total reports for this applicant"><?php echo $reportCount; ?> report<?php echo $reportCount>1?'s':''; ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars((string)($r['email'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string)($r['phone_number'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo $latestNote !== '' ? nl2br(htmlspecialchars($latestNote, ENT_QUOTES, 'UTF-8')) : '—'; ?></td>
                    <td><?php echo htmlspecialchars($latestAdmin, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($latestAt !== '' ? formatDateTime($latestAt) : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="actions-cell">
                        <button class="btn btn-sm btn-primary write-report"
                                data-id="<?php echo $id; ?>"
                                data-name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="bi bi-journal-plus me-1"></i> Write Report
                        </button>
                        <button class="btn btn-sm btn-outline-secondary view-history"
                                data-id="<?php echo $id; ?>"
                                data-name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo $reportCount === 0 ? 'disabled' : ''; ?>>
                            <i class="bi bi-clock-history me-1"></i> History
                        </button>
                        <a class="btn btn-sm btn-info" href="view_approved.php?id=<?php echo $id; ?>">
                            <i class="bi bi-eye me-1"></i> View
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
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

<!-- Modal: Report History -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold">Report History — <span id="hist-applicant"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="hist-container">
          <!-- Filled by JS -->
        </div>
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

  // View History button
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
            container.innerHTML = '<div class="text-muted">No reports found for this applicant.</div>';
            return;
          }
          var html = '<div class="list-group">';
          items.forEach(function (it) {
            html += '<div class="list-group-item">';
            html +=   '<div class="d-flex justify-content-between align-items-center mb-1">';
            html +=     '<div class="fw-semibold">' + escapeHtml(it.admin_name || '—') + '</div>';
            html +=     '<div class="text-muted small">' + escapeHtml(it.created_at || '') + '</div>';
            html +=   '</div>';
            html +=   '<div class="history-note">' + escapeHtml(it.note_text || '') + '</div>';
            html += '</div>';
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
});
</script>

<?php require_once '../includes/footer.php'; ?>