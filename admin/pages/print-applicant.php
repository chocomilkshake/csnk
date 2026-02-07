<?php
// FILE: pages/print-applicant.php
$pageTitle = 'Print — Applicant + Client';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

// Assumptions: header.php sets up $database (mysqli) and helpers:
// redirect($url), formatDate($dateStr), getFileUrl($path), getFullName(...), setFlashMessage(...)

$applicant = new Applicant($database);

// Preserve optional search query for navigation (not required for print)
$q = '';
if (isset($_GET['q'])) {
    $q = trim((string)$_GET['q']);
    if (mb_strlen($q) > 200) $q = mb_substr($q, 0, 200);
}

// Require applicant id
if (!isset($_GET['id'])) {
    $dest = 'on-process.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
    redirect($dest);
    exit;
}
$id = (int)$_GET['id'];

/** =======================
 *  Data loading (mysqli)
 *  ======================= */
function safe(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Applicant (ensure not deleted)
try {
    $stmt = $database->prepare("SELECT * FROM applicants WHERE id = ? AND (status <> 'deleted' OR status IS NULL) LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $applicantData = $res ? $res->fetch_assoc() : false;
} catch (Throwable $e) { $applicantData = false; }

if (!$applicantData) {
    setFlashMessage('error', 'Applicant not found.');
    $dest = 'on-process.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
    redirect($dest);
    exit;
}

// Documents (via Applicant class if available)
$documents = [];
if (method_exists($applicant, 'getDocuments')) {
    $documents = $applicant->getDocuments($id);
}

// Latest booking
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
} catch (Throwable $e) { $latestBooking = null; }

// All bookings
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
} catch (Throwable $e) { $allBookings = []; }

/** =======================
 *  Render helpers
 *  ======================= */
function statusColorHex(string $status): string {
    $map = [
        'pending'    => '#f59f00', // warning
        'on_process' => '#0dcaf0', // info
        'approved'   => '#198754', // success
        'deleted'    => '#6c757d', // secondary
    ];
    return $map[$status] ?? '#6c757d';
}
function bookingColorHex(string $status): string {
    $map = [
        'submitted' => '#6c757d',
        'confirmed' => '#198754',
        'cancelled' => '#dc3545',
    ];
    return $map[$status] ?? '#6c757d';
}
function renderPreferredLocationBadges(?string $json): string {
    if ($json === null || trim($json) === '') return '<span class="text-muted">N/A</span>';
    $cities = [];
    $decoded = json_decode($json, true);
    if (is_array($decoded)) {
        foreach ($decoded as $c) { if (is_string($c) && trim($c) !== '') $cities[] = trim($c); }
    } else {
        $fallback = trim($json, " \t\n\r\0\x0B[]\"");
        if ($fallback !== '') {
            foreach (explode(',', $fallback) as $p) {
                $p = trim($p);
                if ($p !== '') $cities[] = $p;
            }
        }
    }
    if (!$cities) return '<span class="text-muted">N/A</span>';
    $html = [];
    foreach ($cities as $city) $html[] = '<span class="chip chip-blue">'.safe($city).'</span>';
    return implode(' ', $html);
}
function renderEducationListHtml(?string $json): string {
    if (empty($json)) return '<span class="text-muted">N/A</span>';
    $edu = json_decode($json, true);
    if (!is_array($edu)) return '<div>'.safe($json).'</div>';
    $levels = [
        'elementary'  => 'Elementary',
        'highschool'  => 'High School',
        'senior_high' => 'Senior High',
        'college'     => 'College',
    ];
    $rows = [];
    foreach ($levels as $key => $label) {
        if (empty($edu[$key]) || !is_array($edu[$key])) continue;
        $row    = $edu[$key];
        $school = trim((string)($row['school'] ?? ''));
        $year   = trim((string)($row['year'] ?? ''));
        $strand = trim((string)($row['strand'] ?? ''));
        $course = trim((string)($row['course'] ?? ''));
        if ($school === '' && $year === '' && $strand === '' && $course === '') continue;
        $detail = $strand !== '' ? $strand : $course;
        $rows[] = '<tr><td>'.safe($label).'</td><td>'.safe($school).'</td><td>'.safe($detail).'</td><td>'.safe($year).'</td></tr>';
    }
    if (!$rows) return '<span class="text-muted">N/A</span>';
    return '<div class="table-wrap"><table class="table table-plain"><thead><tr><th>Level</th><th>School</th><th>Detail</th><th>Year</th></tr></thead><tbody>'.implode('', $rows).'</tbody></table></div>';
}
function renderWorkHistoryListHtml(?string $json): string {
    if ($json === null || trim($json) === '') return '<span class="text-muted">N/A</span>';
    $arr = json_decode($json, true);
    if (!is_array($arr)) return '<div>'.safe($json).'</div>';
    $rows = [];
    foreach ($arr as $row) {
        if (!is_array($row)) continue;
        $company  = trim((string)($row['company']  ?? ''));
        $role     = trim((string)($row['role']     ?? ''));
        $years    = trim((string)($row['years']    ?? ''));
        $location = trim((string)($row['location'] ?? ''));
        if ($company === '' && $role === '' && $years === '' && $location === '') continue;
        $rows[] = '<tr><td>'.safe($company).'</td><td>'.safe($role).'</td><td>'.safe($years).'</td><td>'.safe($location).'</td></tr>';
    }
    if (!$rows) return '<span class="text-muted">N/A</span>';
    return '<div class="table-wrap"><table class="table table-plain"><thead><tr><th>Company</th><th>Role</th><th>Years</th><th>Location</th></tr></thead><tbody>'.implode('', $rows).'</tbody></table></div>';
}
function renderLanguages(?string $json): string {
    if ($json === null || trim($json) === '') return 'N/A';
    $arr = json_decode($json, true);
    if (!is_array($arr) || !$arr) return 'N/A';
    $clean = array_values(array_filter(array_map('trim', $arr)));
    return $clean ? safe(implode(', ', $clean)) : 'N/A';
}
function renderSkillsPills(?string $json): string {
    if ($json === null || trim($json) === '') return '<span class="text-muted">N/A</span>';
    $arr = json_decode($json, true);
    if (!is_array($arr) || !$arr) return '<span class="text-muted">N/A</span>';
    $clean = array_values(array_filter(array_map('trim', $arr)));
    if (!$clean) return '<span class="text-muted">N/A</span>';
    $chips = [];
    foreach ($clean as $label) $chips[] = '<span class="chip chip-red">'.safe($label).'</span>';
    return implode(' ', $chips);
}
function renderServicesHtml(?string $json): string {
    if ($json === null || trim($json) === '') return '<span class="text-muted">N/A</span>';
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) return '<div>'.safe($json).'</div>';
    if (!is_array($data)) return '<div>'.safe(json_encode($data, JSON_UNESCAPED_UNICODE)).'</div>';
    $labels = [];
    foreach ($data as $item) {
        if (is_string($item) && trim($item) !== '') $labels[] = trim($item);
        elseif (is_array($item)) {
            foreach (['name','label','service','title'] as $k) {
                if (isset($item[$k]) && is_string($item[$k]) && trim($item[$k]) !== '') { $labels[] = trim($item[$k]); break; }
            }
        }
    }
    if (!$labels) return '<span class="text-muted">N/A</span>';
    $chips = [];
    foreach ($labels as $lbl) $chips[] = '<span class="chip">'.safe($lbl).'</span>';
    return '<div class="chip-wrap">'.implode(' ', $chips).'</div>';
}

/** =======================
 *  Prepared values
 *  ======================= */
$educationHtml = renderEducationListHtml($applicantData['educational_attainment'] ?? '');
$workHtml      = renderWorkHistoryListHtml($applicantData['work_history'] ?? '');
$locBadgesHtml = renderPreferredLocationBadges($applicantData['preferred_location'] ?? '');

$pictureUrl = !empty($applicantData['picture']) ? getFileUrl($applicantData['picture']) : null;

$primaryPhone   = trim((string)($applicantData['phone_number'] ?? ''));
$alternatePhone = trim((string)($applicantData['alt_phone_number'] ?? ''));
$alternatePhoneDisplay = ($alternatePhone !== '') ? $alternatePhone : 'N/A';

$languagesDisplay = renderLanguages($applicantData['languages'] ?? '');
$skillsPillsHtml  = renderSkillsPills($applicantData['specialization_skills'] ?? '');

?>
<style>
/* ==========================
 * Print sheet / base styles
 * ========================== */
@page { size: A4; margin: 12mm; }
@media print {
  .no-print { display: none !important; }
  body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji","Segoe UI Emoji"; color:#212529; }

/* Simple grid (no Bootstrap dependency) */
.row{ display:flex; flex-wrap:wrap; margin:-.5rem; }
.col-12{ width:100%; padding:.5rem; }
.col-6{ width:50%; padding:.5rem; }

/* Cards / headers */
.card{ border:1px solid #dee2e6; border-radius:.5rem; margin-bottom:10px; }
.card-header{ padding:.6rem .8rem; background:#fff; border-bottom:1px solid #edf0f2; }
.card-body{ padding:.8rem; }
h1,h2,h3,h4,h5{ margin:0; }

/* Pills / chips */
.chip{ display:inline-block; border:1px solid #dee2e6; background:#f8f9fa; border-radius:999px; padding:.22rem .55rem; font-size:.85rem; font-weight:600; }
.chip-red{ background:#ffe5e5; color:#9b1c1c; border-color:#ffc9c9; }
.chip-blue{ background:#e7f1ff; color:#0b5ed7; border-color:#cfe2ff; }
.chip-wrap{ display:flex; gap:6px; flex-wrap:wrap; }

/* Utility */
.small-label{ font-size:.8rem; color:#6c757d; display:block; margin-bottom:.2rem; }
.value{ font-weight:600; }
.value-sm{ font-weight:600; font-size:.95rem; }
.text-muted{ color:#6c757d; }
.table-wrap{ width:100%; overflow:hidden; }
.table{ width:100%; border-collapse:collapse; }
.table th, .table td{ border:1px solid #dee2e6; padding:.45rem .5rem; vertical-align:top; }
.table thead th{ background:#f8f9fa; }

/* Avatar */
.avatar{ width:110px; height:110px; object-fit:cover; border-radius:50%; background:#f1f3f5; }

/* Header controls */
.header-bar{ display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.header-meta{ color:#6c757d; font-size:.9rem; }
.header-actions{ display:flex; gap:.5rem; }
.btn{ display:inline-flex; align-items:center; gap:.35rem; padding:.35rem .6rem; border-radius:.35rem; border:1px solid #ced4da; background:#fff; color:#212529; text-decoration:none; font-size:.9rem; }
.btn-primary{ background:#0d6efd; border-color:#0d6efd; color:#fff; }
</style>

<div class="header-bar no-print">
  <div>
    <h3 class="fw-semibold" style="margin-bottom:.15rem;">Applicant + Client (On Process)</h3>
    <div class="header-meta">Printed on: <?php echo safe(date('F d, Y h:i A')); ?></div>
  </div>
  <div class="header-actions">
    <button onclick="window.print()" class="btn btn-primary"><span class="bi bi-printer"></span> Print</button>
    <button onclick="window.close()" class="btn">Close</button>
  </div>
</div>

<!-- SUMMARY: Applicant (left) + Latest Booking (right) -->
<div class="row">
  <div class="col-6">
    <div class="card">
      <div class="card-header"><h5><span class="bi bi-person-badge"></span> Applicant</h5></div>
      <div class="card-body">
        <div style="display:flex; gap:12px; align-items:center; margin-bottom:8px;">
          <?php if (!empty($pictureUrl)): ?>
            <img src="<?php echo safe($pictureUrl); ?>" class="avatar" alt="Applicant Photo">
          <?php else: ?>
            <div class="avatar d-flex align-items-center justify-content-center text-muted" style="display:flex;align-items:center;justify-content:center;font-size:2rem;">
              <?php echo strtoupper(substr($applicantData['first_name'], 0, 1)); ?>
            </div>
          <?php endif; ?>
          <div>
            <div class="value" style="font-size:1.05rem;">
              <?php echo getFullName($applicantData['first_name'], $applicantData['middle_name'], $applicantData['last_name'], $applicantData['suffix']); ?>
            </div>
            <?php $statusColor = statusColorHex((string)($applicantData['status'] ?? '')); ?>
            <span class="chip" style="background:<?php echo $statusColor; ?>; color:#fff; border-color:<?php echo $statusColor; ?>; font-weight:700;">
              <?php echo ucfirst(str_replace('_',' ', (string)$applicantData['status'])); ?>
            </span>
            <div class="text-muted" style="font-size:.9rem;">Applied: <?php echo formatDate($applicantData['created_at']); ?></div>
          </div>
        </div>

        <div class="row">
          <div class="col-6">
            <span class="small-label">Phone (Primary)</span>
            <div class="value-sm"><?php echo $primaryPhone !== '' ? safe($primaryPhone) : 'N/A'; ?></div>
          </div>
          <div class="col-6">
            <span class="small-label">Phone (Alternate)</span>
            <div class="value-sm"><?php echo safe($alternatePhoneDisplay); ?></div>
          </div>
          <div class="col-6">
            <span class="small-label">Email</span>
            <div class="value-sm"><?php echo safe($applicantData['email'] ?? 'N/A'); ?></div>
          </div>
          <div class="col-6">
            <span class="small-label">Date of Birth</span>
            <div class="value-sm"><?php echo formatDate($applicantData['date_of_birth']); ?></div>
          </div>
          <div class="col-6">
            <span class="small-label">Experience</span>
            <div class="value-sm">
              <?php $yrs = (int)($applicantData['years_experience'] ?? 0); echo $yrs . ($yrs === 1 ? ' year' : ' years'); ?>
            </div>
          </div>
          <div class="col-6">
            <span class="small-label">Employment Type</span>
            <div class="value-sm"><?php echo !empty($applicantData['employment_type']) ? safe($applicantData['employment_type']) : 'N/A'; ?></div>
          </div>
          <div class="col-12">
            <span class="small-label">Address</span>
            <div class="value-sm"><?php echo safe($applicantData['address']); ?></div>
          </div>
          <div class="col-12">
            <span class="small-label">Preferred Location(s)</span>
            <div><?php echo $locBadgesHtml; ?></div>
          </div>
          <div class="col-12">
            <span class="small-label">Specialization Skills</span>
            <div class="chip-wrap"><?php echo $skillsPillsHtml; ?></div>
          </div>
          <div class="col-12">
            <span class="small-label">Languages</span>
            <div class="value-sm"><?php echo $languagesDisplay; ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Latest Client Booking -->
  <div class="col-6">
    <div class="card">
      <div class="card-header" style="display:flex; align-items:center; justify-content:space-between;">
        <h5><span class="bi bi-people"></span> Latest Client Booking</h5>
        <?php if ($latestBooking): ?>
          <?php $bColor = bookingColorHex((string)($latestBooking['status'] ?? '')); ?>
          <span class="chip" style="background:<?php echo $bColor; ?>; color:#fff; border-color:<?php echo $bColor; ?>; font-weight:700;">
            <?php echo safe($latestBooking['status']); ?>
          </span>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if (!$latestBooking): ?>
          <div class="text-muted">No client booking found for this applicant.</div>
        <?php else: ?>
          <?php
            $clientName = trim(($latestBooking['client_first_name'] ?? '') . ' ' . ($latestBooking['client_middle_name'] ?? '') . ' ' . ($latestBooking['client_last_name'] ?? ''));
            $clientName = $clientName !== '' ? $clientName : '—';
          ?>
          <div class="row">
            <div class="col-12">
              <span class="small-label">Client</span>
              <div class="value-sm"><?php echo safe($clientName); ?></div>
            </div>
            <div class="col-6">
              <span class="small-label">Email</span>
              <div class="value-sm"><?php echo safe($latestBooking['client_email'] ?? '—'); ?></div>
            </div>
            <div class="col-6">
              <span class="small-label">Phone</span>
              <div class="value-sm"><?php echo safe($latestBooking['client_phone'] ?? '—'); ?></div>
            </div>
            <div class="col-12">
              <span class="small-label">Client Address</span>
              <div class="value-sm"><?php echo safe($latestBooking['client_address'] ?? '—'); ?></div>
            </div>
            <div class="col-6">
              <span class="small-label">Appointment Type</span>
              <div class="value-sm"><?php echo safe($latestBooking['appointment_type'] ?? '—'); ?></div>
            </div>
            <div class="col-6">
              <span class="small-label">Date &amp; Time</span>
              <div class="value-sm">
                <?php
                  $d = !empty($latestBooking['appointment_date']) ? formatDate($latestBooking['appointment_date']) : '—';
                  $t = !empty($latestBooking['appointment_time']) ? safe($latestBooking['appointment_time']) : '';
                  echo trim($d . ' ' . $t);
                ?>
              </div>
            </div>
            <div class="col-12">
              <span class="small-label">Services</span>
              <div class="value-sm"><?php echo renderServicesHtml($latestBooking['services_json'] ?? null); ?></div>
            </div>
            <div class="col-6">
              <span class="small-label">Created</span>
              <div class="value-sm"><?php echo !empty($latestBooking['created_at']) ? formatDate($latestBooking['created_at']) : '—'; ?></div>
            </div>
            <div class="col-6">
              <span class="small-label">Updated</span>
              <div class="value-sm"><?php echo !empty($latestBooking['updated_at']) ? formatDate($latestBooking['updated_at']) : '—'; ?></div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Education & Work -->
<div class="row">
  <div class="col-6">
    <div class="card">
      <div class="card-header"><h5><span class="bi bi-mortarboard"></span> Educational Attainment</h5></div>
      <div class="card-body"><?php echo $educationHtml; ?></div>
    </div>
  </div>
  <div class="col-6">
    <div class="card">
      <div class="card-header"><h5><span class="bi bi-briefcase"></span> Work History</h5></div>
      <div class="card-body"><?php echo $workHtml; ?></div>
    </div>
  </div>
</div>

<!-- All Client Bookings -->
<div class="card">
  <div class="card-header"><h5><span class="bi bi-list-ul"></span> All Client Bookings for Applicant</h5></div>
  <div class="card-body">
    <?php if (empty($allBookings)): ?>
      <div class="text-muted">No bookings yet.</div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Appointment</th>
              <th>Date &amp; Time</th>
              <th>Client</th>
              <th>Contacts</th>
              <th>Services</th>
              <th>Status</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($allBookings as $i => $b): ?>
              <?php
                $cName = trim(($b['client_first_name'] ?? '') . ' ' . ($b['client_middle_name'] ?? '') . ' ' . ($b['client_last_name'] ?? ''));
                $cName = $cName !== '' ? $cName : '—';
                $badge = bookingColorHex((string)($b['status'] ?? ''));
              ?>
              <tr>
                <td><?php echo (int)($i + 1); ?></td>
                <td><?php echo safe($b['appointment_type'] ?? '—'); ?></td>
                <td><?php echo (!empty($b['appointment_date']) ? formatDate($b['appointment_date']) : '—') . ' ' . (!empty($b['appointment_time']) ? safe($b['appointment_time']) : ''); ?></td>
                <td><?php echo safe($cName); ?></td>
                <td>
                  <div><?php echo safe($b['client_email'] ?? '—'); ?></div>
                  <div class="text-muted"><?php echo safe($b['client_phone'] ?? '—'); ?></div>
                </td>
                <td><?php echo renderServicesHtml($b['services_json'] ?? null); ?></td>
                <td>
                  <span class="chip" style="background:<?php echo $badge; ?>; color:#fff; border-color:<?php echo $badge; ?>; font-weight:700;">
                    <?php echo safe($b['status']); ?>
                  </span>
                </td>
                <td><?php echo !empty($b['created_at']) ? formatDate($b['created_at']) : '—'; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Documents -->
<div class="card">
  <div class="card-header"><h5><span class="bi bi-folder2-open"></span> Documents</h5></div>
  <div class="card-body">
    <?php if (empty($documents)): ?>
      <div class="text-muted">No documents uploaded yet.</div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Document</th>
              <th>Link</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($documents as $i => $doc): ?>
              <tr>
                <td><?php echo (int)($i + 1); ?></td>
                <td><?php echo safe(ucfirst(str_replace('_', ' ', (string)$doc['document_type']))); ?></td>
                <td>
                  <?php if (!empty($doc['file_path'])): ?>
                    <a href="<?php echo safe(getFileUrl($doc['file_path'])); ?>" target="_blank">
                      <?php echo safe(basename((string)$doc['file_path'])); ?>
                    </a>
                  <?php else: ?>
                    <span class="text-muted">N/A</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  // Auto-open print dialog on load for smooth "Save as PDF"
  window.addEventListener('load', function(){ try{ window.print(); }catch(e){} });
</script>

<?php
// Include footer if your layout requires it (safe to keep for closing tags)
require_once '../includes/footer.php';
?>