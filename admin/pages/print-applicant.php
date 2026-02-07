<?php
// FILE: pages/print-applicant.php
$pageTitle = 'Print — Applicant + Client';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

// From header.php: $database (mysqli), helpers: redirect(), formatDate(), getFileUrl(), getFullName(), setFlashMessage()
$applicant = new Applicant($database);

/* Optional filter */
$q = '';
if (isset($_GET['q'])) {
    $q = trim((string)$_GET['q']);
    if (mb_strlen($q) > 200) $q = mb_substr($q, 0, 200);
}

/* Require applicant id */
if (!isset($_GET['id'])) {
    $dest = 'on-process.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
    redirect($dest); exit;
}
$id = (int)$_GET['id'];

/* Helpers */
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
    $stmt->bind_param("i", $id); $stmt->execute();
    $res = $stmt->get_result(); $applicantData = $res ? $res->fetch_assoc() : false;
} catch (Throwable $e) { $applicantData = false; }
if (!$applicantData) {
    setFlashMessage('error', 'Applicant not found.');
    redirect('on-process.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '')); exit;
}

/* Latest Booking */
$latestBooking = null;
try {
    $stmt = $database->prepare("SELECT * FROM client_bookings WHERE applicant_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $id); $stmt->execute();
    $res = $stmt->get_result(); $latestBooking = $res ? $res->fetch_assoc() : null;
} catch (Throwable $e) { $latestBooking = null; }

/* All Bookings (complete list) */
$allBookings = [];
try {
    $stmt = $database->prepare("SELECT * FROM client_bookings WHERE applicant_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $id); $stmt->execute();
    $res = $stmt->get_result(); $allBookings = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
} catch (Throwable $e) { $allBookings = []; }
$totalBookings = count($allBookings);

/* Documents */
$documents = [];
if (method_exists($applicant, 'getDocuments')) {
    $documents = $applicant->getDocuments($id);
}

/* Badge/line renderers (Bootstrap utilities only) */
function renderBadgesFromJson(?string $json, string $badgeClass = 'bg-light text-primary border', int $max = 0): string {
    if ($json === null || trim($json) === '') return '<span class="text-muted">N/A</span>';
    $arr = json_decode($json, true); $items = [];
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
    $data = json_decode($json, true); if (json_last_error() !== JSON_ERROR_NONE) return '<span class="text-muted">'.safe(truncate($json, 60)).'</span>';
    $labels = [];
    if (is_array($data)) {
        foreach ($data as $item) {
            if (is_string($item) && trim($item) !== '') $labels[] = trim($item);
            elseif (is_array($item)) foreach (['name','label','service','title'] as $k) if (!empty($item[$k]) && is_string($item[$k])) { $labels[] = trim($item[$k]); break; }
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

<!-- Print isolation + page sizing (tailwind/bs utilities for the rest) -->
<style>
  /* Hide app chrome; only print #print-root */
  #app-shell, header, nav, aside, footer, .sidebar, .navbar, .pc-sidebar, .pc-header, .page-header { display:none !important; }
  @media print {
    body * { visibility: hidden !important; }
    #print-root, #print-root * { visibility: visible !important; }
    #print-root { position: absolute; left:0; top:0; width:100%; }
  }
  /* Legal 8.5x14 portrait, modest margins */
  @page { size: legal portrait; margin: 10mm; }
  /* Page breaks for documents */
  .page-break { page-break-before: always; }
  .doc-page { height: 100vh; }
  .doc-fit { width:100%; height: calc(100vh - 20mm); object-fit: contain; }
</style>
</head>
<body class="bg-white">

<div id="print-root" class="container-fluid p-3">

  <!-- Top bar -->
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div class="d-flex align-items-center gap-2">
      <img src="<?php echo safe($csnkLogo); ?>" alt="CSNK" style="height:28px;">
      <div class="fw-bold">Applicant + Client (On Process)</div>
    </div>
    <div class="text-muted small">Printed on: <?php echo safe(date('M d, Y h:i A')); ?></div>
  </div>

  <!-- PAGE 1: Applicant + Latest Client Booking + All Client Bookings (complete) -->
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
              } else {
                echo '<div class="text-muted">N/A</div>';
              }
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
              } else {
                echo '<div class="text-muted">N/A</div>';
              }
            ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Latest Client Booking + All Client Bookings -->
    <div class="col-6">
      <div class="border rounded p-2 mb-2">
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
              <div class="fw-semibold text-truncate">
                <?php echo $clientName !== '' ? safe($clientName) : '—'; ?>
              </div>
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

      <!-- All Client Bookings (complete list, compact) -->
      <div class="border rounded p-2">
        <div class="fw-semibold small d-flex align-items-center gap-2 mb-1">
          <i class="bi bi-list-ul"></i> All Client Bookings (Complete)
        </div>
        <?php if (empty($allBookings)): ?>
          <div class="text-muted small">No bookings yet.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 small align-middle">
              <thead class="table-light">
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
                    $cName = trim(($b['client_first_name'] ?? '').' '.($b['client_middle_name'] ?? '').' '.($b['client_last_name'] ?? '')); if ($cName==='') $cName='—';
                    $dt = ( !empty($b['appointment_date']) ? formatDate($b['appointment_date']) : '—' ) . ( !empty($b['appointment_time']) ? (' '.$b['appointment_time']) : '' );
                  ?>
                  <tr>
                    <td><?php echo $i+1; ?></td>
                    <td class="text-truncate" style="max-width:120px"><?php echo safe($cName); ?></td>
                    <td><?php echo safe($b['appointment_type'] ?? '—'); ?></td>
                    <td class="text-truncate" style="max-width:120px"><?php echo safe($dt); ?></td>
                    <td class="text-uppercase"><?php echo safe($b['status'] ?? ''); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- PAGES 2..N: One page per document (full page) -->
  <?php if (!empty($documents)): ?>
    <?php foreach ($documents as $index => $doc): ?>
      <?php
        $type = (string)($doc['document_type'] ?? 'document');
        $path = (string)($doc['file_path'] ?? '');
        $url  = $path !== '' ? getFileUrl($path) : '';
      ?>
      <div class="page-break"></div>
      <div class="p-3 doc-page">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="d-flex align-items-center gap-2">
            <img src="<?php echo safe($csnkLogo); ?>" alt="CSNK" style="height:22px;">
            <div class="fw-semibold">Document: <?php echo safe(ucfirst(str_replace('_',' ', $type))); ?></div>
          </div>
          <div class="text-muted small">Applicant: <?php echo $fullName; ?></div>
        </div>

        <?php if ($url === ''): ?>
          <div class="alert alert-warning p-2 small">Document file missing.</div>
        <?php else: ?>
          <?php if (isImagePath($path)): ?>
            <img src="<?php echo safe($url); ?>" alt="Document" class="doc-fit d-block mx-auto">
          <?php elseif (isPdfPath($path)): ?>
            <object data="<?php echo safe($url); ?>" type="application/pdf" class="w-100" style="height: calc(100vh - 20mm);">
              <div class="small">
                PDF preview not available. Open:
                <a href="<?php echo safe($url); ?>" target="_blank"><?php echo safe(basename($path)); ?></a>
              </div>
            </object>
          <?php else: ?>
            <div class="border rounded p-3">
              <div class="mb-2">File type not previewable. Open or download the file:</div>
              <a href="<?php echo safe($url); ?>" target="_blank" class="text-decoration-none">
                <?php echo safe(basename($path)); ?>
              </a>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div><!-- /print-root -->

<script>window.addEventListener('load', function(){ try{ window.print(); }catch(e){} });</script>
</body>
</html>