<?php
// FILE: pages/view_approved.php
$pageTitle = 'View Approved (Applicant + Client)';

if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

/* ------------------------------------------------------------------
   Absolutely NO output before this point.
   Make sure your editor hasn't added a UTF-8 BOM or spaces before <?php
-------------------------------------------------------------------*/

// (A) Bootstrap DB and common includes FIRST (so $conn exists for endpoints)
require_once '../includes/config.php';
require_once '../includes/Database.php';
$database = new Database();
$conn = $database->getConnection(); // mysqli

// (B) CSRF token (needed by POST endpoint later)
if (empty($_SESSION['csrf_token'])) {
    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }
    catch (Throwable $e) { $_SESSION['csrf_token'] = bin2hex((string)mt_rand()); }
}

// (C) Preserve search (q) for back links / redirects
$q = '';
if (isset($_GET['q'])) {
    $q = trim((string)$_GET['q']);
    if (mb_strlen($q) > 200) $q = mb_substr($q, 0, 200);
}


/* ------------------------------------------------------------------
   Inline endpoint: History (JSON)
   GET view_approved.php?action=history&id=123
-------------------------------------------------------------------*/
if (isset($_GET['action']) && $_GET['action'] === 'history' && isset($_GET['id'])) {
    header('Content-Type: application/json; charset=UTF-8');
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
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ($res = $stmt->get_result()) {
            while ($row = $res->fetch_assoc()) {
                $data[] = [
                    'note_text'  => (string)($row['note_text'] ?? ''),
                    'created_at' => (string)($row['created_at'] ?? ''),
                    'admin_name' => (string)($row['admin_name'] ?? '—'),
                ];
            }
        }
        $stmt->close();
    } catch (Throwable $e) {
        error_log('History fetch (view_approved) failed: ' . $e->getMessage());
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ------------------------------------------------------------------
   Inline endpoint: Add report (POST)
   POST view_approved.php  action=add_applicant_report
-------------------------------------------------------------------*/
if (
    isset($_POST['action']) && $_POST['action'] === 'add_applicant_report' &&
    isset($_POST['id'], $_POST['note_text'], $_POST['csrf_token'])
) {
    $id       = (int)$_POST['id'];
    $noteText = trim((string)$_POST['note_text']);

    // keep q + id on redirect
    $qsBase = '?id=' . $id . ($q !== '' ? ('&q=' . urlencode($q)) : '');

    // CSRF
    $validCsrf = isset($_SESSION['csrf_token']) &&
                 hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token']);
    if (!$validCsrf) {
        // invalid CSRF: show inline error flag
        header('Location: view_approved.php' . $qsBase . '&err=csrf'); exit;
    }
    if ($noteText === '' || mb_strlen($noteText) < 3) {
        // too short: show inline error flag
        header('Location: view_approved.php' . $qsBase . '&err=short'); exit;
    }

    $adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;

    try {
        $stmt = $conn->prepare("INSERT INTO applicant_reports (applicant_id, admin_id, note_text) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $id, $adminId, $noteText);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            // optional activity log
            if (isset($auth) && method_exists($auth,'logActivity') && isset($_SESSION['admin_id'])) {
                $auth->logActivity((int)$_SESSION['admin_id'], 'Add Applicant Report', "Applicant ID {$id}: " . mb_substr($noteText, 0, 200));
            }
            header('Location: view_approved.php' . $qsBase . '&saved=1'); // ✅ success flag
            exit;
        } else {
            header('Location: view_approved.php' . $qsBase . '&err=save'); exit;
        }
    } catch (Throwable $e) {
        error_log('Add report (view_approved) failed: ' . $e->getMessage());
        header('Location: view_approved.php' . $qsBase . '&err=exception'); exit;
    }
}


// After all endpoints, start your normal page output:
require_once '../includes/header.php';
require_once '../includes/Applicant.php';
$applicant = new Applicant($database);



if (isset($_GET['q'])) {
    $q = trim((string)$_GET['q']);
    if (mb_strlen($q) > 200) $q = mb_substr($q, 0, 200);
}

if (!isset($_GET['id'])) {
    $dest = 'approved.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
    redirect($dest);
    exit;
}
$id = (int)$_GET['id'];

/* ------------------------------------------------------------------
   Inline endpoint: Add report (POST)
   POST view_approved.php  action=add_applicant_report
-------------------------------------------------------------------*/
if (
    isset($_POST['action']) && $_POST['action'] === 'add_applicant_report' &&
    isset($_POST['id'], $_POST['note_text'], $_POST['csrf_token'])
) {
    $id       = (int)$_POST['id'];
    $noteText = trim((string)$_POST['note_text']);

    // keep q + id on redirect
    $qsBase = '?id=' . $id . ($q !== '' ? ('&q=' . urlencode($q)) : '');

    // CSRF
    $validCsrf = isset($_SESSION['csrf_token']) &&
                 hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token']);
    if (!$validCsrf) {
        header('Location: view_approved.php' . $qsBase . '&err=csrf'); exit;
    }
    if ($noteText === '' || mb_strlen($noteText) < 3) {
        header('Location: view_approved.php' . $qsBase . '&err=short'); exit;
    }

    $adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;

    try {
        $stmt = $conn->prepare("INSERT INTO applicant_reports (applicant_id, admin_id, note_text) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $id, $adminId, $noteText);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            if (isset($auth) && method_exists($auth,'logActivity') && isset($_SESSION['admin_id'])) {
                $auth->logActivity((int)$_SESSION['admin_id'], 'Add Applicant Report', "Applicant ID {$id}: " . mb_substr($noteText, 0, 200));
            }
            header('Location: view_approved.php' . $qsBase . '&saved=1'); // ✅ success flag
            exit;
        } else {
            header('Location: view_approved.php' . $qsBase . '&err=save'); exit;
        }
    } catch (Throwable $e) {
        error_log('Add report (view_approved) failed: ' . $e->getMessage());
        header('Location: view_approved.php' . $qsBase . '&err=exception'); exit;
    }
}

/** Load Applicant (ensure not deleted) */
function safe(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** Load Applicant (ensure not deleted) */
try {
    $stmt = $database->prepare("SELECT * FROM applicants WHERE id = ? AND (status <> 'deleted' OR status IS NULL) LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $applicantData = $res ? $res->fetch_assoc() : false;
} catch (Throwable $e) {
    $applicantData = false;
}

if (!$applicantData) {
    $dest = 'approved.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
    header('Location: ' . $dest . '&err=notfound'); // or just redirect silently
    exit;
}

/* Load recent reports (latest 3) for quick view */
$recentReports = [];
$reportCount = 0;
try {
    $stmt = $conn->prepare("
        SELECT ar.note_text, ar.created_at,
              COALESCE(NULLIF(au.full_name,''), NULLIF(au.username,''), NULLIF(au.email,'')) AS admin_name
        FROM applicant_reports ar
        LEFT JOIN admin_users au ON au.id = ar.admin_id
        WHERE ar.applicant_id = ?
        ORDER BY ar.created_at DESC, ar.id DESC
        LIMIT 3
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $recentReports = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    $res2 = $conn->prepare("SELECT COUNT(*) AS c FROM applicant_reports WHERE applicant_id = ?");
    $res2->bind_param("i", $id);
    $res2->execute();
    $rc = $res2->get_result()->fetch_assoc();
    $reportCount = (int)($rc['c'] ?? 0);
    $res2->close();
} catch (Throwable $e) {
    $recentReports = [];
    $reportCount = 0;
}

/** Load Documents (if Applicant class supports it) */
$documents = [];
if (method_exists($applicant, 'getDocuments')) {
    $documents = $applicant->getDocuments($id);
}

/** Load latest booking (most recent) */
$latestBooking = null;
try {
    $stmt = $database->prepare("
        SELECT cb.*
        FROM client_bookings cb
        WHERE cb.applicant_id = ?
        ORDER BY cb.created_at DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $latestBooking = $res ? $res->fetch_assoc() : null;
} catch (Throwable $e) {
    $latestBooking = null;
}

/** Load all bookings (for table) */
$allBookings = [];
try {
    $stmt = $database->prepare("
        SELECT cb.*
        FROM client_bookings cb
        WHERE cb.applicant_id = ?
        ORDER BY cb.created_at DESC
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $allBookings = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
} catch (Throwable $e) {
    $allBookings = [];
}

/* ========= Helpers (renderers) ========= */

function renderBadgesFromJson(?string $json, string $badgeClass = 'bg-light text-primary border', int $max = 0): string {
    if ($json === null || trim($json) === '') return '<span class="text-muted">N/A</span>';
    $arr = json_decode($json, true);
    $items = [];
    if (is_array($arr)) {
        foreach ($arr as $v) if (is_string($v) && trim($v) !== '') $items[] = trim($v);
    } else {
        $fallback = trim($json, " \t\n\r\0\x0B[]\"");
        if ($fallback !== '') foreach (explode(',', $fallback) as $p) if (trim($p) !== '') $items[] = trim($p);
    }
    if (!$items) return '<span class="text-muted">N/A</span>';
    if ($max > 0) $items = array_slice($items, 0, $max);
    $out = [];
    foreach ($items as $label) $out[] = '<span class="badge rounded-pill '.$badgeClass.' fw-semibold">'.safe($label).'</span>';
    return implode(' ', $out);
}

function renderServicesBadges(?string $json): string {
    if ($json === null || trim($json) === '') return '<span class="text-muted">N/A</span>';
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) return '<span class="text-muted">'.safe($json).'</span>';
    $labels = [];
    if (is_array($data)) {
        foreach ($data as $item) {
            if (is_string($item) && trim($item) !== '') $labels[] = trim($item);
            elseif (is_array($item)) {
                foreach (['name','label','service','title'] as $k) {
                    if (!empty($item[$k]) && is_string($item[$k])) { $labels[] = trim($item[$k]); break; }
                }
            }
        }
    }
    if (!$labels) return '<span class="text-muted">N/A</span>';
    $out = [];
    foreach ($labels as $label) $out[] = '<span class="badge rounded-pill bg-light text-dark border fw-semibold">'.safe($label).'</span>';
    return '<div class="d-flex flex-wrap gap-1">'.implode(' ', $out).'</div>';
}

/** Prepared values */
$pictureUrl = !empty($applicantData['picture']) ? getFileUrl($applicantData['picture']) : null;
$fullName   = getFullName($applicantData['first_name'], $applicantData['middle_name'], $applicantData['last_name'], $applicantData['suffix']);
$status     = (string)($applicantData['status'] ?? 'approved');
$badgeColor = ['pending'=>'warning','on_process'=>'info','approved'=>'success','deleted'=>'secondary'][$status] ?? 'secondary';

$primaryPhone = trim((string)($applicantData['phone_number'] ?? ''));
$altPhone     = trim((string)($applicantData['alt_phone_number'] ?? ''));
$email        = (string)($applicantData['email'] ?? 'N/A');

$prefLocBadges = renderBadgesFromJson($applicantData['preferred_location'] ?? '', 'bg-light text-primary border', 8);
$skillsBadges  = renderBadgesFromJson($applicantData['specialization_skills'] ?? '', 'bg-light text-danger border', 10);

$languagesDisplay = (function($json){
    if ($json === null || trim($json) === '') return 'N/A';
    $arr = json_decode($json, true);
    if (!is_array($arr) || !$arr) return 'N/A';
    $clean = array_values(array_filter(array_map('trim', $arr)));
    return $clean ? safe(implode(', ', $clean)) : 'N/A';
})($applicantData['languages'] ?? '');

/** URLs */
$backUrl    = 'approved.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
$editUrl    = 'edit-applicant.php?id=' . $id . ($q !== '' ? ('&q=' . urlencode($q)) : '');
$printUrl   = 'print-applicant.php?id=' . $id . ($q !== '' ? ('&q=' . urlencode($q)) : '');
$historyUrl = 'view-applicant-history.php?id=' . (int)$id; // NEW: History page

?>
<style>
/* Minor cosmetics for readability */
.page-actions .btn { white-space: nowrap; }
.card { border: 1px solid #eef2f7; border-radius: .75rem; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
.small-label { font-size:.8rem; color:#6c757d; text-transform:uppercase; letter-spacing:.04em; }
.table td, .table th { vertical-align: middle; }
.accordion-button { font-weight:600; }
</style>

<!-- Header actions (Back → Edit → History → Print) -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex align-items-center gap-3">
    <h4 class="mb-0 fw-semibold">Applicant + Client (Approved)</h4>
    <span class="badge bg-<?php echo $badgeColor; ?>"><?php echo safe(ucfirst(str_replace('_',' ', $status))); ?></span>
  </div>
  <div class="page-actions d-flex gap-2">
    <a href="<?php echo safe($backUrl); ?>" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i> Back to Approved
    </a>
    <a href="<?php echo safe($editUrl); ?>" class="btn btn-warning">
      <i class="bi bi-pencil me-1"></i> Edit Applicant
    </a>
    <?php if (($isAdmin ?? false) || ($isSuperAdmin ?? false)): ?>
      <a href="<?php echo safe($historyUrl); ?>" class="btn btn-outline-info">
        <i class="bi bi-clock-history me-1"></i> History
      </a>
    <?php endif; ?>
    <a href="<?php echo safe($printUrl); ?>" target="_blank" class="btn btn-dark">
      <i class="bi bi-printer me-1"></i> Print / Save as PDF
    </a>
  </div>
</div>

<!-- Top row: Applicant (left) + Latest Client Booking (right) -->
<div class="row g-3">

  <!-- Applicant Card -->
  <div class="col-xl-6">
    <div class="card">
      <div class="card-header bg-white py-2">
        <div class="d-flex justify-content-between align-items-center">
          <div class="fw-semibold d-flex align-items-center gap-2">
            <i class="bi bi-person-badge"></i> Applicant
          </div>
          <span class="badge bg-<?php echo $badgeColor; ?> rounded-pill">
            <?php echo safe(ucfirst(str_replace('_',' ', $status))); ?>
          </span>
        </div>
      </div>
      <div class="card-body">

        <div class="d-flex align-items-center gap-3 mb-3">
          <?php if (!empty($pictureUrl)): ?>
            <img src="<?php echo safe($pictureUrl); ?>" alt="Photo" class="rounded-circle" style="width:100px;height:100px;object-fit:cover;">
          <?php else: ?>
            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width:100px;height:100px;">
              <span class="fw-bold fs-2"><?php echo strtoupper(substr($applicantData['first_name'], 0, 1)); ?></span>
            </div>
          <?php endif; ?>

          <div class="min-w-0">
            <div class="fw-bold fs-5 text-truncate"><?php echo safe($fullName); ?></div>
            <div class="text-muted small">Applied: <?php echo safe(formatDate($applicantData['created_at'])); ?></div>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-6">
            <div class="small-label mb-1">Phone (Primary)</div>
            <div class="fw-semibold"><?php echo $primaryPhone !== '' ? safe($primaryPhone) : 'N/A'; ?></div>
          </div>
          <div class="col-6">
            <div class="small-label mb-1">Phone (Alternate)</div>
            <div class="fw-semibold"><?php echo $altPhone !== '' ? safe($altPhone) : 'N/A'; ?></div>
          </div>
          <div class="col-6">
            <div class="small-label mb-1">Email</div>
            <div class="fw-semibold text-truncate"><?php echo safe($email); ?></div>
          </div>
          <div class="col-6">
            <div class="small-label mb-1">Date of Birth</div>
            <div class="fw-semibold"><?php echo safe(formatDate($applicantData['date_of_birth'])); ?></div>
          </div>
          <div class="col-6">
            <div class="small-label mb-1">Experience</div>
            <div class="fw-semibold">
              <?php $yrs = (int)($applicantData['years_experience'] ?? 0); echo $yrs . ($yrs === 1 ? ' year' : ' years'); ?>
            </div>
          </div>
          <div class="col-6">
            <div class="small-label mb-1">Employment</div>
            <div class="fw-semibold"><?php echo !empty($applicantData['employment_type']) ? safe($applicantData['employment_type']) : 'N/A'; ?></div>
          </div>

          <div class="col-12">
            <div class="small-label mb-1">Address</div>
            <div class="fw-semibold"><?php echo safe($applicantData['address']); ?></div>
          </div>

          <div class="col-12">
            <div class="small-label mb-1">Preferred Location(s)</div>
            <div class="d-flex flex-wrap gap-1"><?php echo $prefLocBadges; ?></div>
          </div>

          <div class="col-12">
            <div class="small-label mb-1">Specialization Skills</div>
            <div class="d-flex flex-wrap gap-1"><?php echo $skillsBadges; ?></div>
          </div>

          <div class="col-12">
            <div class="small-label mb-1">Languages</div>
            <div class="fw-semibold"><?php echo $languagesDisplay; ?></div>
          </div>

          <!-- Educational Attainment -->
          <div class="col-12">
            <div class="small-label mb-1">Educational Attainment</div>
            <?php
              $eduArr = json_decode($applicantData['educational_attainment'] ?? '', true);
              if (is_array($eduArr)) {
                $labels = ['elementary'=>'Elementary','highschool'=>'High School','senior_high'=>'Senior High','college'=>'College'];
                echo '<ul class="mb-0 ps-3">';
                foreach ($labels as $k=>$label) {
                  if (!empty($eduArr[$k]) && is_array($eduArr[$k])) {
                    $row = $eduArr[$k]; $parts = [];
                    if (!empty($row['school'])) $parts[] = $row['school'];
                    if (!empty($row['strand'])) $parts[] = $row['strand'];
                    if (!empty($row['course'])) $parts[] = $row['course'];
                    if (!empty($row['year']))   $parts[] = $row['year'];
                    if ($parts) echo '<li class="small">'.safe($label).': '.safe(implode(' • ', $parts)).'</li>';
                  }
                }
                echo '</ul>';
              } else { echo '<div class="text-muted">N/A</div>'; }
            ?>
          </div>

          <!-- Work History -->
          <div class="col-12">
            <div class="small-label mb-1">Work History</div>
            <?php
              $workArr = json_decode($applicantData['work_history'] ?? '', true);
              if (is_array($workArr) && $workArr) {
                echo '<ul class="mb-0 ps-3">';
                foreach ($workArr as $w) {
                  if (!is_array($w)) continue;
                  $parts = [];
                  if (!empty($w['company']))  $parts[] = $w['company'];
                  if (!empty($w['role']))     $parts[] = $w['role'];
                  if (!empty($w['years']))    $parts[] = $w['years'];
                  if (!empty($w['location'])) $parts[] = $w['location'];
                  if ($parts) echo '<li class="small">'.safe(implode(' — ', $parts)).'</li>';
                }
                echo '</ul>';
              } else { echo '<div class="text-muted">N/A</div>'; }
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>

 

  <!-- Latest Client Booking -->
  <div class="col-xl-6">
    <div class="card">
      <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <div class="fw-semibold d-flex align-items-center gap-2">
          <i class="bi bi-people"></i> Latest Client Booking
        </div>
        <?php if ($latestBooking): ?>
          <?php $bColor = ['submitted'=>'secondary','confirmed'=>'success','cancelled'=>'danger'][(string)$latestBooking['status']] ?? 'secondary'; ?>
          <span class="badge bg-<?php echo $bColor; ?> text-uppercase"><?php echo safe($latestBooking['status']); ?></span>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if (!$latestBooking): ?>
          <p class="text-muted mb-0">No client booking found for this applicant.</p>
        <?php else: ?>
          <?php
            $clientName = trim(($latestBooking['client_first_name'] ?? '') . ' ' . ($latestBooking['client_middle_name'] ?? '') . ' ' . ($latestBooking['client_last_name'] ?? ''));
            if ($clientName === '') $clientName = '—';
          ?>
          <div class="mb-2">
            <div class="small-label mb-1">Client</div>
            <div class="fw-semibold"><?php echo safe($clientName); ?></div>
          </div>

          <div class="row g-3">
            <div class="col-6">
              <div class="small-label mb-1">Client Email</div>
              <div class="fw-semibold text-truncate"><?php echo safe($latestBooking['client_email'] ?? '—'); ?></div>
            </div>
            <div class="col-6">
              <div class="small-label mb-1">Client Phone</div>
              <div class="fw-semibold"><?php echo safe($latestBooking['client_phone'] ?? '—'); ?></div>
            </div>
            <div class="col-6">
              <div class="small-label mb-1">Appointment</div>
              <div class="fw-semibold"><?php echo safe($latestBooking['appointment_type'] ?? '—'); ?></div>
            </div>
            <div class="col-6">
              <div class="small-label mb-1">Date & Time</div>
              <div class="fw-semibold">
                <?php
                  $d = !empty($latestBooking['appointment_date']) ? formatDate($latestBooking['appointment_date']) : '—';
                  $t = !empty($latestBooking['appointment_time']) ? $latestBooking['appointment_time'] : '';
                  echo safe(trim($d . ' ' . $t));
                ?>
              </div>
            </div>
          </div>

          <div class="mt-2">
            <div class="small-label mb-1">Client Address</div>
            <div class="fw-semibold"><?php echo safe($latestBooking['client_address'] ?? '—'); ?></div>
          </div>

          <div class="mt-2">
            <div class="small-label mb-1">Services</div>
            <?php echo renderServicesBadges($latestBooking['services_json'] ?? null); ?>
          </div>

          <div class="row g-3 mt-2">
            <div class="col-6">
              <div class="small-label mb-1">Created</div>
              <div class="fw-semibold"><?php echo !empty($latestBooking['created_at']) ? safe(formatDateTime($latestBooking['created_at'])) : '—'; ?></div>
            </div>
            <div class="col-6">
              <div class="small-label mb-1">Updated</div>
              <div class="fw-semibold"><?php echo !empty($latestBooking['updated_at']) ? safe(formatDateTime($latestBooking['updated_at'])) : '—'; ?></div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div><!-- /row -->
 <!-- Applicant Reports (Quick View + Add) -->
<div class="card mt-3">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <div class="fw-semibold"><i class="bi bi-journal-text me-2"></i>Applicant Reports</div>
      <div>
        <button class="btn btn-sm btn-outline-secondary" id="btnViewHistory">
          <i class="bi bi-clock-history me-1"></i> View Full History
        </button>
        <?php if ($reportCount > 0): ?>
          <a class="btn btn-sm btn-success ms-2"
            href="../includes/excel_reports.php?id=<?php echo (int)$id; ?>&q=<?php echo urlencode($q); ?>">
            <i class="bi bi-file-earmark-excel me-1"></i> Export (Excel)
          </a>
        <?php endif; ?>
        <?php if ($reportCount > 0): ?>
          <span class="badge bg-primary-subtle text-primary border ms-2" title="Total reports">
            <?php echo (int)$reportCount; ?> report<?php echo $reportCount>1?'s':''; ?>
          </span>
        <?php endif; ?>
      </div>
</div>

  <div class="card-body">
    <!-- Quick List -->
    <?php if (empty($recentReports)): ?>
      <p class="text-muted mb-3">No reports yet.</p>
    <?php else: ?>
      <div class="list-group mb-3">
        <?php foreach ($recentReports as $rpt): ?>
          <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <div class="fw-semibold"><?php echo htmlspecialchars($rpt['admin_name'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="text-muted small"><?php echo htmlspecialchars(formatDateTime((string)$rpt['created_at']), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="history-note"><?php echo nl2br(htmlspecialchars((string)($rpt['note_text'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Add Report -->
    <form method="post" action="view_approved.php?id=<?php echo (int)$id . ($q!=='' ? ('&q=' . urlencode($q)) : ''); ?>">
      <div class="mb-2 text-muted small">Add a new report for <span class="fw-semibold"><?php echo $fullName; ?></span></div>
      <div class="mb-3">
        <label for="note_text" class="form-label">Report / Notes <span class="text-danger">*</span></label>
        <textarea class="form-control" id="note_text" name="note_text" rows="4"
                  required minlength="3" maxlength="4000"
                  placeholder="Write your report or notes..."></textarea>
      </div>
      <input type="hidden" name="action" value="add_applicant_report">
      <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
      <div class="text-end">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save2 me-1"></i> Save Report
        </button>
      </div>
    </form>
  </div>
</div>
<!-- ACCORDIONS -->
<div class="accordion mt-3" id="extraInfoAccordion">

  <!-- All Bookings (with Email / Call actions) -->
  <div class="accordion-item">
    <h2 class="accordion-header" id="headingBookings">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBookings" aria-expanded="false" aria-controls="collapseBookings">
        All Client Bookings (Complete)
      </button>
    </h2>
    <div id="collapseBookings" class="accordion-collapse collapse" aria-labelledby="headingBookings" data-bs-parent="#extraInfoAccordion">
      <div class="accordion-body">
        <?php if (empty($allBookings)): ?>
          <p class="text-muted mb-0">No bookings yet.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Client</th>
                  <th>Contacts</th>
                  <th>Appointment</th>
                  <th>Date & Time</th>
                  <th>Status</th>
                  <th class="text-center">Actions</th>
                  <th>Created</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($allBookings as $i => $b): ?>
                  <?php
                    $cName  = trim(($b['client_first_name'] ?? '') . ' ' . ($b['client_middle_name'] ?? '') . ' ' . ($b['client_last_name'] ?? ''));
                    if ($cName === '') $cName = '—';
                    $statusB = (string)($b['status'] ?? 'submitted');
                    $badge   = ['submitted'=>'secondary','confirmed'=>'success','cancelled'=>'danger'][$statusB] ?? 'secondary';
                    $cid     = isset($b['id']) ? (int)$b['id'] : $i;
                    $emailB  = trim((string)($b['client_email'] ?? ''));
                    $phoneB  = trim((string)($b['client_phone'] ?? ''));
                    $subject = rawurlencode('Regarding your appointment');
                    $body    = rawurlencode("Hello $cName,\n\nFollowing up regarding your appointment.\n\nThank you,");
                    $mailto  = $emailB !== '' ? 'mailto:'.rawurlencode($emailB).'?subject='.$subject.'&amp;body='.$body : '';
                    $modalId = 'contactModal'.$cid;
                  ?>
                  <tr>
                    <td><?php echo (int)($i + 1); ?></td>
                    <td class="fw-semibold"><?php echo safe($cName); ?></td>
                    <td>
                      <div><?php echo safe($emailB !== '' ? $emailB : '—'); ?></div>
                      <div class="text-muted small"><?php echo safe($phoneB !== '' ? $phoneB : '—'); ?></div>
                    </td>
                    <td><?php echo safe($b['appointment_type'] ?? '—'); ?></td>
                    <td>
                      <?php
                        $d = !empty($b['appointment_date']) ? formatDate($b['appointment_date']) : '—';
                        $t = !empty($b['appointment_time']) ? $b['appointment_time'] : '';
                        echo safe(trim($d . ' ' . $t));
                      ?>
                    </td>
                    <td><span class="badge bg-<?php echo $badge; ?>"><?php echo safe($statusB); ?></span></td>
                    <td class="text-center">
                      <div class="btn-group">
                        <?php if ($mailto !== ''): ?>
                          <a href="<?php echo safe($mailto); ?>" class="btn btn-sm btn-outline-primary" title="Email Client">
                            <i class="bi bi-envelope"></i>
                          </a>
                        <?php endif; ?>
                        <button type="button" class="btn btn-sm btn-outline-success" title="Show Contact" data-bs-toggle="modal" data-bs-target="#<?php echo safe($modalId); ?>">
                          <i class="bi bi-telephone"></i>
                        </button>
                      </div>
                    </td>
                    <td><?php echo !empty($b['created_at']) ? safe(formatDateTime($b['created_at'])) : '—'; ?></td>
                  </tr>

                  <!-- Contact Modal -->
                  <div class="modal fade" id="<?php echo safe($modalId); ?>" tabindex="-1" aria-labelledby="<?php echo safe($modalId); ?>Label" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="<?php echo safe($modalId); ?>Label">
                            <i class="bi bi-person-lines-fill me-2"></i>Client Contact
                          </h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <div class="mb-3">
                            <div class="small-label mb-1">Client</div>
                            <div class="fw-semibold"><?php echo safe($cName); ?></div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-6">
                              <div class="small-label mb-1">Email</div>
                              <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-envelope text-muted"></i>
                                <?php if ($emailB !== ''): ?>
                                  <a href="mailto:<?php echo safe($emailB); ?>" class="value-sm text-decoration-none"><?php echo safe($emailB); ?></a>
                                <?php else: ?>
                                  <span class="text-muted">N/A</span>
                                <?php endif; ?>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="small-label mb-1">Phone</div>
                              <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-telephone text-muted"></i>
                                <?php if ($phoneB !== ''): ?>
                                  <a href="tel:<?php echo safe($phoneB); ?>" class="value-sm text-decoration-none"><?php echo safe($phoneB); ?></a>
                                <?php else: ?>
                                  <span class="text-muted">N/A</span>
                                <?php endif; ?>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <?php if ($mailto !== ''): ?>
                            <a href="<?php echo safe($mailto); ?>" class="btn btn-primary">
                              <i class="bi bi-envelope me-1"></i>Email
                            </a>
                          <?php endif; ?>
                          <?php if ($phoneB !== ''): ?>
                            <a href="tel:<?php echo safe($phoneB); ?>" class="btn btn-success">
                              <i class="bi bi-telephone me-1"></i>Call
                            </a>
                          <?php endif; ?>
                          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Documents -->
  <div class="accordion-item">
    <h2 class="accordion-header" id="headingDocs">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDocs" aria-expanded="false" aria-controls="collapseDocs">
        Applicant Documents
      </button>
    </h2>
    <div id="collapseDocs" class="accordion-collapse collapse" aria-labelledby="headingDocs" data-bs-parent="#extraInfoAccordion">
      <div class="accordion-body">
        <?php if (empty($documents)): ?>
          <p class="text-muted mb-0">No documents uploaded yet.</p>
        <?php else: ?>
          <div class="list-group">
            <?php foreach ($documents as $doc): ?>
              <div class="list-group-item d-flex justify-content-between align-items-center">
                <span>
                  <i class="bi bi-file-earmark-text me-2"></i>
                  <?php echo ucfirst(str_replace('_', ' ', (string)$doc['document_type'])); ?>
                </span>
                <?php if (!empty($doc['file_path'])): ?>
                  <a href="<?php echo safe(getFileUrl($doc['file_path'])); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye me-1"></i>View
                  </a>
                <?php else: ?>
                  <span class="text-muted small">N/A</span>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div><!-- /accordion -->

<!-- Modal: Report History -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold" id="historyModalLabel">
          Report History — <?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>
        </h5>
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
  var historyModalEl = document.getElementById('historyModal');
  var historyModal = (typeof bootstrap !== 'undefined' && bootstrap.Modal) ? new bootstrap.Modal(historyModalEl) : null;

  var btn = document.getElementById('btnViewHistory');
  if (btn) {
    btn.addEventListener('click', function () {
      var container = document.getElementById('hist-container');
      container.innerHTML = '<div class="text-muted">Loading history...</div>';
      fetch('view_approved.php?action=history&id=<?php echo (int)$id; ?>', { credentials: 'same-origin' })
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
  }

  function escapeHtml(s) {
    return (s||'').replace(/[&<>\"']/g, function(c){
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;', "'":'&#039;'}[c];
    });
  }
});
</script>

<?php require_once '../includes/footer.php'; ?>