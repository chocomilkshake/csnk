<?php
// FILE: pages/CSNK-Applicant.php
$pageTitle = 'CSNK - Applicant Withdrawal';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

// From header.php: $database (mysqli), helpers: redirect(), formatDate(), getFileUrl(), getFullName(), setFlashMessage()
$applicant = new Applicant($database);

/* Optional search (for back links if ever needed) */
$q = '';
if (isset($_GET['q'])) {
    $q = trim((string)$_GET['q']);
    if (mb_strlen($q) > 200) $q = mb_substr($q, 0, 200);
}

/* Require applicant id */
if (!isset($_GET['id'])) {
    redirect('on-process.php' . ($q !== '' ? ('?q=' . urlencode($q)) : ''));
    exit;
}
$id = (int)$_GET['id'];

/* Utilities */
function safe(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function truncate(?string $s, int $len = 140): string {
    $s = (string)$s; return (mb_strlen($s) > $len) ? (mb_substr($s, 0, $len - 1) . '…') : $s;
}
function isImagePath(string $p): bool {
    $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg','jpeg','png','gif','webp']);
}
function isPdfPath(string $p): bool {
    return strtolower(pathinfo($p, PATHINFO_EXTENSION)) === 'pdf';
}

/* Load Applicant */
try {
    $stmt = $database->prepare("SELECT * FROM applicants WHERE id = ? AND (status <> 'deleted' OR status IS NULL) LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $applicantData = $res ? $res->fetch_assoc() : false;
} catch (Throwable $e) { $applicantData = false; }
if (!$applicantData) {
    setFlashMessage('error', 'Applicant not found.');
    redirect('on-process.php' . ($q !== '' ? ('?q=' . urlencode($q)) : ''));
    exit;
}

/* Latest Booking */
$latestBooking = null;
try {
    $stmt = $database->prepare("SELECT * FROM client_bookings WHERE applicant_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $latestBooking = $res ? $res->fetch_assoc() : null;
} catch (Throwable $e) { $latestBooking = null; }

/* All Bookings (complete) */
$allBookings = [];
try {
    $stmt = $database->prepare("SELECT * FROM client_bookings WHERE applicant_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $allBookings = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
} catch (Throwable $e) { $allBookings = []; }
$totalBookings = count($allBookings);

/* Documents (limit to 8 for a total of 9 pages including page 1) */
$documents = [];
if (method_exists($applicant, 'getDocuments')) {
    $docs = $applicant->getDocuments($id) ?: [];
    $documents = array_slice($docs, 0, 8);
}

/* Render helpers (Bootstrap utilities only) */
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
function renderServicesBadges(?string $json, int $max = 0): string {
    if ($json === null || trim($json) === '') return '<span class="text-muted">N/A</span>';
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) return '<span class="text-muted">'.safe(truncate($json, 60)).'</span>';
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
    if ($max > 0) $labels = array_slice($labels, 0, $max);
    $out = [];
    foreach ($labels as $label) $out[] = '<span class="badge rounded-pill bg-light text-dark border fw-semibold">'.safe($label).'</span>';
    return implode(' ', $out);
}
function renderLanguagesLine(?string $json): string {
    if ($json === null || trim($json) === '') return 'N/A';
    $arr = json_decode($json, true); if (!is_array($arr) || !$arr) return 'N/A';
    $clean = array_values(array_filter(array_map('trim', $arr))); return $clean ? safe(implode(', ', $clean)) : 'N/A';
}

/* Prepared values */
$pictureUrl  = !empty($applicantData['picture']) ? getFileUrl($applicantData['picture']) : null;
$fullName    = getFullName($applicantData['first_name'], $applicantData['middle_name'], $applicantData['last_name'], $applicantData['suffix']);
$status      = (string)($applicantData['status'] ?? 'pending');
$statusClass = ['pending'=>'bg-warning text-dark','on_process'=>'bg-info text-dark','approved'=>'bg-success','deleted'=>'bg-secondary'][$status] ?? 'bg-secondary';
$appliedDate = formatDate($applicantData['created_at']);
$dob         = formatDate($applicantData['date_of_birth']);
$experience  = (int)($applicantData['years_experience'] ?? 0);
$employment  = !empty($applicantData['employment_type']) ? $applicantData['employment_type'] : 'N/A';
$primaryPhone= trim((string)($applicantData['phone_number'] ?? ''));
$altPhone    = trim((string)($applicantData['alt_phone_number'] ?? ''));
$email       = (string)($applicantData['email'] ?? 'N/A');
$prefLoc     = renderBadgesFromJson($applicantData['preferred_location'] ?? '', 'bg-light text-primary border', 6);
$skills      = renderBadgesFromJson($applicantData['specialization_skills'] ?? '', 'bg-light text-danger border', 8);
$languages   = renderLanguagesLine($applicantData['languages'] ?? '');

$clientName  = ($latestBooking ? trim(($latestBooking['client_first_name'] ?? '').' '.($latestBooking['client_middle_name'] ?? '').' '.($latestBooking['client_last_name'] ?? '')) : '—');
if ($clientName === '') $clientName = '—';
$apptDateTime = (function($b){ if (!$b) return '—'; $d = !empty($b['appointment_date']) ? formatDate($b['appointment_date']) : '—'; $t = !empty($b['appointment_time']) ? $b['appointment_time'] : ''; return trim($d.' '.$t) ?: '—'; })($latestBooking);
$servicesBadges = renderServicesBadges($latestBooking['services_json'] ?? null, 6);

/* Logo (adjust path if needed) */
$csnkLogo = '../resources/img/csnk-logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Print — Applicant + Client</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Print isolation + page sizing + maximal doc fit -->
<style>
  :root{
    --page-m: 10mm;       /* matches @page margin */
    --doc-head: 6mm;      /* header band on each document page */
    --doc-gap: 2mm;       /* spacing under header */
  }

  /* Hide app chrome; only print #print-root */
  #app-shell, header, nav, aside, footer, .sidebar, .navbar, .pc-sidebar, .pc-header, .page-header { display:none !important; }
  @media print {
    body * { visibility: hidden !important; }
    #print-root, #print-root * { visibility: visible !important; }
    #print-root { position: absolute; left:0; top:0; width:100%; }
  }

  /* Legal 8.5x14 portrait */
  @page { size: legal portrait; margin: var(--page-m); }

  /* ---- Print table for All Client Bookings (full width below cards) ---- */
  .print-table { width:100%; border-collapse:collapse; table-layout: fixed; font-size:11px; }
  .print-table th, .print-table td { border:1px solid #dee2e6; padding:5px 6px; vertical-align:top; }
  .print-table thead th { background:#f8f9fa; font-weight:700; }
  .wrap-any { word-break: break-word; }

  /* Column widths tuned for 8.5" width PDFs (sum <= 100%) */
  .col-num      { width: 5%;  }
  .col-client   { width: 34%; }
  .col-type     { width: 15%; }
  .col-datetime { width: 28%; }
  .col-status   { width: 18%; text-transform:uppercase; }

  /* ---- One page per document, maximized printable area ---- */
  .doc-sheet { page-break-before: always; margin: 0; padding: 0; }
  .doc-header { display:flex; justify-content:space-between; align-items:center; gap:.5rem; padding: 0 6mm; min-height: var(--doc-head); margin: 0 0 var(--doc-gap) 0; }
  .doc-fit,
  .doc-embed {
    display:block;
    width: 100%;
    height: calc(100vh - (var(--page-m) * 2) - var(--doc-head) - var(--doc-gap));
    object-fit: contain; /* change to 'cover' if you prefer edge-to-edge even if cropped */
    border: 0;
  }
</style>
</head>
<body class="bg-white">

<div id="print-root" class="container-fluid p-3">

  <!-- PAGE 1: Header -->
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div class="d-flex align-items-center gap-2">
      <img src="<?php echo safe($csnkLogo); ?>" alt="CSNK" style="height:58px;">
      <div class="fw-bold">Applicant &amp; Clients</div>
    </div>
    <div class="text-muted small">Printed on: <?php echo safe(date('M d, Y h:i A')); ?></div>
  </div>

  <!-- PAGE 1: Top row (two cards side by side) -->
  <div class="row g-2">
    <!-- Applicant -->
    <div class="col-6">
      <div class="border rounded p-2">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fw-semibold small d-flex align-items-center gap-2">
            <i class="bi bi-person-badge"></i> Applicant
          </div>
          <span class="badge <?php echo $statusClass; ?> rounded-pill"><?php echo safe(ucfirst(str_replace('_',' ', $status))); ?></span>
        </div>

        <div class="d-flex align-items-center gap-2 mb-2">
          <?php if (!empty($pictureUrl)): ?>
            <img src="<?php echo safe($pictureUrl); ?>" class="rounded-circle" style="width:70px;height:70px;object-fit:cover;" alt="Photo">
          <?php else: ?>
            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width:70px;height:70px;">
              <span class="fw-bold fs-5"><?php echo strtoupper(substr($applicantData['first_name'], 0, 1)); ?></span>
            </div>
          <?php endif; ?>
          <div class="min-w-0">
            <div class="fw-bold text-truncate"><?php echo $fullName; ?></div>
            <div class="text-muted small">Applied: <?php echo safe($appliedDate); ?></div>
          </div>
        </div>

        <div class="row g-2 small">
          <div class="col-6">
            <div class="text-muted">Phone (Primary)</div>
            <div class="fw-semibold text-truncate"><?php echo $primaryPhone !== '' ? safe($primaryPhone) : 'N/A'; ?></div>
          </div>
          <div class="col-6">
            <div class="text-muted">Phone (Alternate)</div>
            <div class="fw-semibold text-truncate"><?php echo $altPhone !== '' ? safe($altPhone) : 'N/A'; ?></div>
          </div>

          <div class="col-6">
            <div class="text-muted">Email</div>
            <div class="fw-semibold text-truncate"><?php echo safe($email); ?></div>
          </div>
          <div class="col-6">
            <div class="text-muted">Date of Birth</div>
            <div class="fw-semibold"><?php echo safe($dob); ?></div>
          </div>

          <div class="col-6">
            <div class="text-muted">Experience</div>
            <div class="fw-semibold"><?php echo $experience . ($experience === 1 ? ' year' : ' years'); ?></div>
          </div>
          <div class="col-6">
            <div class="text-muted">Employment</div>
            <div class="fw-semibold"><?php echo safe($employment); ?></div>
          </div>

          <div class="col-12">
            <div class="text-muted">Address</div>
            <div class="fw-semibold"><?php echo safe(truncate($applicantData['address'] ?? '')); ?></div>
          </div>

          <div class="col-12">
            <div class="text-muted">Preferred Location(s)</div>
            <div class="d-flex flex-wrap gap-1"><?php echo $prefLoc; ?></div>
          </div>

          <div class="col-12">
            <div class="text-muted">Specialization Skills</div>
            <div class="d-flex flex-wrap gap-1"><?php echo $skills; ?></div>
          </div>

          <div class="col-12">
            <div class="text-muted">Languages</div>
            <div class="fw-semibold text-truncate"><?php echo $languages; ?></div>
          </div>

          <!-- Educational Attainment (compact bullets) -->
          <div class="col-12">
            <div class="text-muted">Educational Attainment</div>
            <?php
              $eduArr = json_decode($applicantData['educational_attainment'] ?? '', true);
              if (is_array($eduArr)) {
                $labels = ['elementary'=>'Elementary','highschool'=>'High School','senior_high'=>'Senior High','college'=>'College'];
                echo '<ul class="mb-0 ps-3">';
                foreach ($labels as $k=>$label) {
                  if (!empty($eduArr[$k]) && is_array($eduArr[$k])) {
                    $row=$eduArr[$k]; $parts=[];
                    if (!empty($row['school']))  $parts[]=$row['school'];
                    if (!empty($row['strand']))  $parts[]=$row['strand'];
                    if (!empty($row['course']))  $parts[]=$row['course'];
                    if (!empty($row['year']))    $parts[]=$row['year'];
                    if ($parts) echo '<li class="small">'.safe($label).': '.safe(truncate(implode(' • ', $parts), 88)).'</li>';
                  }
                }
                echo '</ul>';
              } else { echo '<div class="text-muted">N/A</div>'; }
            ?>
          </div>

          <!-- Work History (compact bullets) -->
          <div class="col-12">
            <div class="text-muted">Work History</div>
            <?php
              $workArr = json_decode($applicantData['work_history'] ?? '', true);
              if (is_array($workArr) && $workArr) {
                echo '<ul class="mb-0 ps-3">';
                foreach ($workArr as $w) {
                  if (!is_array($w)) continue;
                  $parts=[];
                  if (!empty($w['company']))  $parts[]=$w['company'];
                  if (!empty($w['role']))     $parts[]=$w['role'];
                  if (!empty($w['years']))    $parts[]=$w['years'];
                  if (!empty($w['location'])) $parts[]=$w['location'];
                  if ($parts) echo '<li class="small">'.safe(truncate(implode(' — ', $parts), 88)).'</li>';
                }
                echo '</ul>';
              } else { echo '<div class="text-muted">N/A</div>'; }
            ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Latest Client Booking -->
    <div class="col-6">
      <div class="border rounded p-2">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fw-semibold small d-flex align-items-center gap-2">
            <i class="bi bi-people"></i> Latest Client Booking
          </div>
          <span class="badge rounded-pill bg-secondary">Total Client Bookings: <?php echo (int)$totalBookings; ?></span>
        </div>

        <?php if (!$latestBooking): ?>
          <div class="text-muted small">No client booking found for this applicant.</div>
        <?php else: ?>
          <div class="row g-2 small">
            <div class="col-12">
              <div class="text-muted">Client</div>
              <div class="fw-semibold text-truncate"><?php echo $clientName !== '' ? safe($clientName) : '—'; ?></div>
            </div>
            <div class="col-6">
              <div class="text-muted">Client Email</div>
              <div class="fw-semibold text-truncate"><?php echo safe($latestBooking['client_email'] ?? '—'); ?></div>
            </div>
            <div class="col-6">
              <div class="text-muted">Client Phone</div>
              <div class="fw-semibold text-truncate"><?php echo safe($latestBooking['client_phone'] ?? '—'); ?></div>
            </div>
            <div class="col-12">
              <div class="text-muted">Client Address</div>
              <div class="fw-semibold"><?php echo safe(truncate($latestBooking['client_address'] ?? '—')); ?></div>
            </div>
            <div class="col-6">
              <div class="text-muted">Appointment Type</div>
              <div class="fw-semibold"><?php echo safe($latestBooking['appointment_type'] ?? '—'); ?></div>
            </div>
            <div class="col-6">
              <div class="text-muted">Date &amp; Time</div>
              <div class="fw-semibold"><?php echo safe($apptDateTime); ?></div>
            </div>
            <div class="col-12">
              <div class="text-muted">Services</div>
              <div class="d-flex flex-wrap gap-1"><?php echo $servicesBadges; ?></div>
            </div>
            <div class="col-6">
              <div class="text-muted">Created</div>
              <div class="fw-semibold"><?php echo !empty($latestBooking['created_at']) ? safe(formatDate($latestBooking['created_at'])) : '—'; ?></div>
            </div>
            <div class="col-6">
              <div class="text-muted">Updated</div>
              <div class="fw-semibold"><?php echo !empty($latestBooking['updated_at']) ? safe(formatDate($latestBooking['updated_at'])) : '—'; ?></div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div><!-- /row top cards -->

  <!-- PAGE 1: All Client Bookings (Full width below cards) -->
  <div class="border rounded p-2 mt-2">
    <div class="fw-semibold small d-flex align-items-center gap-2 mb-1">
      <i class="bi bi-list-ul"></i> All Client Bookings (Complete)
    </div>

    <?php if (empty($allBookings)): ?>
      <div class="text-muted small">No bookings yet.</div>
    <?php else: ?>
      <table class="print-table">
        <colgroup>
          <col class="col-num" />
          <col class="col-client" />
          <col class="col-type" />
          <col class="col-datetime" />
          <col class="col-status" />
        </colgroup>
        <thead>
          <tr>
            <th>#</th>
            <th>Client</th>
            <th>Type</th>
            <th>Date &amp; Time</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allBookings as $i => $b): ?>
            <?php
              $cName = trim(($b['client_first_name'] ?? '').' '.($b['client_middle_name'] ?? '').' '.($b['client_last_name'] ?? ''));
              if ($cName==='') $cName='—';
              $dt  = (!empty($b['appointment_date']) ? formatDate($b['appointment_date']) : '—');
              $tm  = (!empty($b['appointment_time']) ? $b['appointment_time'] : '');
            ?>
            <tr>
              <td class="text-center"><?php echo $i+1; ?></td>
              <td class="wrap-any">
                <div class="fw-semibold"><?php echo safe($cName); ?></div>
                <div class="text-muted"><?php echo safe($b['client_email'] ?? '—'); ?></div>
                <div class="text-muted"><?php echo safe($b['client_phone'] ?? '—'); ?></div>
              </td>
              <td class="wrap-any"><?php echo safe($b['appointment_type'] ?? '—'); ?></td>
              <td class="wrap-any"><?php echo safe(trim($dt.' '.$tm)); ?></td>
              <td class="text-center wrap-any"><?php echo safe(strtoupper((string)($b['status'] ?? ''))); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

<!-- PAGES 2..N (up to 8): One document per page, full legal -->
<?php foreach ($documents as $doc): ?>
  <?php
    $type = (string)($doc['document_type'] ?? 'document');
    $path = (string)($doc['file_path'] ?? '');
    $url  = $path !== '' ? getFileUrl($path) : '';
  ?>
  <section class="doc-sheet">

    <!-- HEADER: Logo on top, Document label below -->
    <div class="doc-header small">
      <div class="d-flex flex-column align-items-start">
        <img src="<?php echo safe($csnkLogo); ?>" alt="CSNK" style="height:58px;">
        <div class="fw-semibold mt-1">
          Document: <?php echo safe(ucfirst(str_replace('_',' ', $type))); ?>
        </div>
      </div>

      <div class="text-muted">
        Applicant Name: <?php echo $fullName; ?>
      </div>
    </div>

    <?php if ($url === ''): ?>
      <div class="p-3">Document file missing.</div>
    <?php else: ?>
      <?php if (isImagePath($path)): ?>
        <img src="<?php echo safe($url); ?>" alt="Document" class="doc-fit">
      <?php elseif (isPdfPath($path)): ?>
        <object data="<?php echo safe($url); ?>" type="application/pdf" class="doc-embed">
          <div class="p-3 small">
            PDF preview not available.
            <a href="<?php echo safe($url); ?>" target="_blank">
              <?php echo safe(basename($path)); ?>
            </a>
          </div>
        </object>
      <?php else: ?>
        <div class="p-3">
          <div class="mb-2">File type not previewable. Open or download:</div>
          <a href="<?php echo safe($url); ?>" target="_blank">
            <?php echo safe(basename($path)); ?>
          </a>
        </div>
      <?php endif; ?>
    <?php endif; ?>

  </section>
<?php endforeach; ?>

</div><!-- /print-root -->

<script>
  // Auto-open print dialog on load (Save as PDF)
  window.addEventListener('load', function(){ try { window.print(); } catch(e){} });
</script>

</body>
</html>