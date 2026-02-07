<?php
// FILE: pages/view-onprocess.php
$pageTitle = 'View On-Process (Applicant + Client)';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

// We assume $database (mysqli), and helpers: redirect, formatDate, getFileUrl, getFullName, setFlashMessage
$applicant = new Applicant($database);

/**
 * Preserve list search (if user came from on-process.php with ?q=)
 */
$q = '';
if (isset($_GET['q'])) {
    $q = trim((string)$_GET['q']);
    if (mb_strlen($q) > 200) {
        $q = mb_substr($q, 0, 200);
    }
}

if (!isset($_GET['id'])) {
    $dest = 'on-process.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
    redirect($dest);
    exit;
}

$id = (int)$_GET['id'];

/** Print mode toggle */
$isPrint = (isset($_GET['print']) && $_GET['print'] === '1');

/**
 * Load Applicant (ensure not deleted)
 */
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
    setFlashMessage('error', 'Applicant not found.');
    $dest = 'on-process.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
    redirect($dest);
    exit;
}

/**
 * Load documents (if Applicant class has it, use it; else fallback to empty)
 */
$documents = [];
if (method_exists($applicant, 'getDocuments')) {
    $documents = $applicant->getDocuments($id);
}

/**
 * Load latest client booking for this applicant
 */
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

/**
 * Load all bookings (for table + modals)
 */
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

/* ============================================================
   Helpers (renderers + utilities)
   ============================================================ */

function safe(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/**
 * Preferred locations (JSON) -> HTML badges (show ALL cities)
 * Returns HTML (do not escape again).
 */
function renderPreferredLocationBadges(?string $json): string {
    if ($json === null || trim($json) === '') {
        return '<span class="text-muted">N/A</span>';
    }

    $cities = [];
    $decoded = json_decode($json, true);

    if (is_array($decoded)) {
        foreach ($decoded as $c) {
            if (is_string($c)) {
                $t = trim($c);
                if ($t !== '') $cities[] = $t;
            }
        }
    } else {
        $fallback = trim($json);
        $fallback = trim($fallback, " \t\n\r\0\x0B[]\"");
        if ($fallback !== '') {
            $parts = array_map('trim', explode(',', $fallback));
            foreach ($parts as $p) {
                if ($p !== '') $cities[] = $p;
            }
        }
    }

    if (empty($cities)) {
        return '<span class="text-muted">N/A</span>';
    }

    $html = [];
    foreach ($cities as $city) {
        $html[] = '<span class="badge rounded-pill loc-pill">'.safe($city).'</span>';
    }
    return implode(' ', $html);
}

/** Educational Attainment Renderer (timeline style) */
function renderEducationListHtml(?string $json): string {
    if (empty($json)) {
        return '<span class="text-muted">N/A</span>';
    }

    $edu = json_decode($json, true);
    if (!is_array($edu)) {
        return '<div>'.safe($json).'</div>';
    }

    $levels = [
        'elementary'  => ['label' => 'Elementary',  'icon' => '📘'],
        'highschool'  => ['label' => 'High School', 'icon' => '📗'],
        'senior_high' => ['label' => 'Senior High', 'icon' => '📙'],
        'college'     => ['label' => 'College',     'icon' => '🎓'],
    ];

    $html = '<div class="edu-timeline">';

    foreach ($levels as $key => $meta) {
        if (empty($edu[$key]) || !is_array($edu[$key])) {
            continue;
        }

        $row = $edu[$key];

        $school = trim((string)($row['school'] ?? ''));
        $year   = trim((string)($row['year'] ?? ''));
        $strand = trim((string)($row['strand'] ?? ''));
        $course = trim((string)($row['course'] ?? ''));

        if ($school === '' && $year === '' && $strand === '' && $course === '') {
            continue;
        }

        $html .= '<div class="edu-item">';
        $html .=   '<div class="edu-header">';
        $html .=     '<span class="edu-icon">'.$meta['icon'].'</span>';
        $html .=     '<span class="edu-level">'.safe($meta['label']).'</span>';
        if ($year !== '') {
            $html .=   '<span class="edu-year">'.safe($year).'</span>';
        }
        $html .=   '</div>';

        if ($school !== '') {
            $html .= '<div class="edu-school">'.safe($school).'</div>';
        }

        if ($strand !== '' || $course !== '') {
            $detail = $strand !== '' ? $strand : $course;
            $html .= '<div class="edu-detail">'.safe($detail).'</div>';
        }

        $html .= '</div>';
    }

    $html .= '</div>';

    return $html !== '<div class="edu-timeline"></div>' ? $html : '<span class="text-muted">N/A</span>';
}

/** Work history JSON array -> structured HTML list (do not escape again). */
function renderWorkHistoryListHtml(?string $json): string {
    if ($json === null || trim($json) === '') {
        return '<span class="text-muted">N/A</span>';
    }
    $arr = json_decode($json, true);
    if (!is_array($arr)) {
        return '<div>'.safe($json).'</div>';
    }

    $items = [];
    foreach ($arr as $row) {
        if (!is_array($row)) continue;

        $company  = trim((string)($row['company']  ?? ''));
        $role     = trim((string)($row['role']     ?? ''));
        $years    = trim((string)($row['years']    ?? ''));
        $location = trim((string)($row['location'] ?? ''));

        if ($company === '' && $role === '' && $years === '' && $location === '') {
            continue;
        }

        $top = [];
        if ($company !== '') $top[] = '<span class="fw-semibold">'.safe($company).'</span>';
        if ($role    !== '') $top[] = safe($role);

        $meta = [];
        if ($years   !== '') $meta[] = safe($years);
        if ($location!== '') $meta[] = safe($location);

        $line = '<li class="mb-1">';
        if (!empty($top)) {
            $line .= implode(' — ', $top);
        }
        if (!empty($meta)) {
            $line .= '<div class="text-muted small">'.implode(' • ', $meta).'</div>';
        }
        $line .= '</li>';

        $items[] = $line;
    }

    if (empty($items)) {
        return '<span class="text-muted">N/A</span>';
    }

    return '<ul class="list-unstyled mb-0">'.implode('', $items).'</ul>';
}

/** Languages JSON -> string */
function renderLanguages(?string $json): string {
    if ($json === null || trim($json) === '') return 'N/A';
    $arr = json_decode($json, true);
    if (!is_array($arr) || empty($arr)) return 'N/A';
    $clean = array_values(array_filter(array_map('trim', $arr)));
    return $clean ? safe(implode(', ', $clean)) : 'N/A';
}

/** Skills JSON -> HTML pills */
function renderSkillsPills(?string $json): string {
    if ($json === null || trim($json) === '') return '<span class="text-muted">N/A</span>';
    $arr = json_decode($json, true);
    if (!is_array($arr) || empty($arr)) return '<span class="text-muted">N/A</span>';

    $clean = array_values(array_filter(array_map('trim', $arr)));
    if (!$clean) return '<span class="text-muted">N/A</span>';

    $htmlParts = [];
    foreach ($clean as $label) {
        $htmlParts[] = '<span class="badge rounded-pill skill-pill">'.safe($label).'</span>';
    }
    return implode(' ', $htmlParts);
}

/** Applicant status -> badge color */
function statusBadgeColor(string $status): string {
    $map = [
        'pending'    => 'warning',
        'on_process' => 'info',
        'approved'   => 'success',
        'deleted'    => 'secondary',
    ];
    return $map[$status] ?? 'secondary';
}

/** Booking status -> badge color */
function bookingStatusBadgeColor(string $status): string {
    $map = [
        'submitted' => 'secondary',
        'confirmed' => 'success',
        'cancelled' => 'danger',
    ];
    return $map[$status] ?? 'secondary';
}

/** Services JSON from client_bookings -> HTML pills */
function renderServicesHtml(?string $json): string {
    if ($json === null || trim($json) === '') return '<span class="text-muted">N/A</span>';
    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return '<div>'.safe($json).'</div>';
    }

    if (is_array($data)) {
        $labels = [];
        foreach ($data as $item) {
            if (is_string($item)) {
                $label = trim($item);
                if ($label !== '') $labels[] = $label;
            } elseif (is_array($item)) {
                $keyOrder = ['name', 'label', 'service', 'title'];
                $found = null;
                foreach ($keyOrder as $k) {
                    if (isset($item[$k]) && is_string($item[$k]) && trim($item[$k]) !== '') {
                        $found = trim($item[$k]);
                        break;
                    }
                }
                if ($found !== null) $labels[] = $found;
                else $labels[] = trim((string)json_encode($item, JSON_UNESCAPED_UNICODE));
            }
        }

        if (!empty($labels)) {
            $html = [];
            foreach ($labels as $lbl) {
                $html[] = '<span class="badge rounded-pill bg-light text-dark border" style="padding:.3rem .55rem; font-weight:600;">'.safe($lbl).'</span>';
            }
            return '<div class="d-flex flex-wrap gap-1">'.implode(' ', $html).'</div>';
        }
        return '<span class="text-muted">N/A</span>';
    }

    return '<div>'.safe(json_encode($data, JSON_UNESCAPED_UNICODE)).'</div>';
}

/* ============================================================
   Prepare data for view
   ============================================================ */

$educationHtml = renderEducationListHtml($applicantData['educational_attainment'] ?? '');
$workHtml      = renderWorkHistoryListHtml($applicantData['work_history'] ?? '');
$locBadgesHtml = renderPreferredLocationBadges($applicantData['preferred_location'] ?? '');

$pictureUrl = !empty($applicantData['picture']) ? getFileUrl($applicantData['picture']) : null;

$primaryPhone   = trim((string)($applicantData['phone_number'] ?? ''));
$alternatePhone = trim((string)($applicantData['alt_phone_number'] ?? ''));
$alternatePhoneDisplay = ($alternatePhone !== '') ? $alternatePhone : 'N/A';

$languagesDisplay = renderLanguages($applicantData['languages'] ?? '');
$skillsPillsHtml  = renderSkillsPills($applicantData['specialization_skills'] ?? '');

$backUrl  = 'on-process.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
$editUrl  = 'edit-applicant.php?id=' . $id . ($q !== '' ? ('&q=' . urlencode($q)) : '');
$printUrl = 'view-onprocess.php?id=' . $id . ($q !== '' ? ('&q=' . urlencode($q)) : '') . '&print=1';
?>
<style>
/* Compact visual tweaks to fit both columns above the fold */
:root{
  --card-pad-y: .75rem;
  --card-pad-x: .9rem;
}
.card.compact > .card-header,
.card.compact > .card-body{
  padding: var(--card-pad-y) var(--card-pad-x);
}
.card-header h5{
  font-size: 1rem;
}
.small-label{
  font-size: .75rem;
  color:#6c757d;
  display:block;
  margin-bottom: .15rem;
}
.value{
  font-weight:600;
  line-height: 1.1;
}
.value-sm{
  font-weight:600;
  line-height: 1.1;
  font-size:.95rem;
}

/* Red-ish pill style for specialization badges */
.skill-pill{
  background-color:#ffe5e5;
  color:#9b1c1c;
  border:1px solid #ffc9c9;
  padding:.25rem .5rem;
  font-weight:600;
}

/* Soft blue pill style for preferred locations */
.loc-pill{
  background-color:#e7f1ff;
  color:#0b5ed7;
  border:1px solid #cfe2ff;
  padding:.25rem .5rem;
  font-weight:600;
}

/* Educational Attainment Timeline */
.edu-timeline {
  display: flex;
  flex-direction: column;
  gap: .6rem;
}
.edu-item { padding-left: 0.5rem; border-left: 3px solid #e9ecef; }
.edu-header { display:flex; align-items:center; gap:.4rem; font-weight:600; }
.edu-icon { font-size: 1rem; }
.edu-level { color:#212529; font-size:.95rem; }
.edu-year { margin-left:auto; font-size:.75rem; background:#f1f3f5; padding:.05rem .4rem; border-radius:.35rem; color:#495057; }
.edu-school { margin-left:1.3rem; font-weight:600; color:#0d6efd; font-size:.95rem;}
.edu-detail { margin-left:1.3rem; font-size:.85rem; color:#6c757d; }

/* Avatar */
.avatar-compact{
  width: 110px; height:110px; object-fit:cover; border-radius:50%;
}

/* Two-column details list */
.dl2{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap:.5rem .75rem;
}

/* Layout: keep both main cards side-by-side */
@media (min-width: 1200px){
  .no-wrap-row{ display:flex; gap:1rem; }
  .no-wrap-row > .col-left,
  .no-wrap-row > .col-right{ flex:1; min-width:0; }
}

/* Collapsible section headers spacing tighter */
.accordion-button{ padding:.5rem .9rem; font-weight:600; }
.accordion-body{ padding:.5rem .9rem; }

/* Utility */
.text-truncate-2{
  display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
}
.text-truncate-1{
  overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}

/* Print view optimizations */
@media print{
  .no-print, .btn, .accordion-button, .modal { display:none !important; }
  .card { border:none; }
  body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .loc-pill, .skill-pill { border:1px solid #ccc; }
}
</style>

<?php if ($isPrint): ?>
<!-- PRINT HEADER -->
<div class="mb-3">
  <h3 class="fw-semibold">Applicant + Client (On Process)</h3>
  <div class="text-muted">Printed on: <?php echo safe(date('F d, Y h:i A')); ?></div>
  <hr>
</div>
<?php else: ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0 fw-semibold">Applicant + Client (On Process)</h4>
  <div class="d-flex gap-2 no-print">
    <a href="<?php echo safe($printUrl); ?>" target="_blank" class="btn btn-outline-dark">
      <i class="bi bi-printer me-1"></i> Print / Save as PDF
    </a>
    <a href="<?php echo safe($editUrl); ?>" class="btn btn-warning">
      <i class="bi bi-pencil me-1"></i> Edit Applicant
    </a>
    <a href="<?php echo safe($backUrl); ?>" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i> Back to On Process
    </a>
  </div>
</div>
<?php endif; ?>

<!-- MAIN ROW: Applicant (left) + Client (right). Designed to fit without page scroll on typical admin screens -->
<div class="<?php echo $isPrint ? '' : 'no-wrap-row'; ?>">
  <!-- Applicant Panel -->
  <div class="<?php echo $isPrint ? 'mb-3' : 'col-left'; ?>">
    <div class="card compact mb-3">
      <div class="card-header bg-white py-2">
        <h5 class="mb-0 fw-semibold d-flex align-items-center">
          <i class="bi bi-person-badge me-2"></i>Applicant
        </h5>
      </div>
      <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-2">
          <?php if (!empty($pictureUrl)): ?>
            <img src="<?php echo safe($pictureUrl); ?>" alt="Profile" class="avatar-compact">
          <?php else: ?>
            <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center avatar-compact" style="font-size:2.2rem;">
              <?php echo strtoupper(substr($applicantData['first_name'], 0, 1)); ?>
            </div>
          <?php endif; ?>

          <div class="flex-grow-1">
            <div class="fw-bold" style="font-size:1.05rem;">
              <?php echo getFullName($applicantData['first_name'], $applicantData['middle_name'], $applicantData['last_name'], $applicantData['suffix']); ?>
            </div>
            <?php $badgeColor = statusBadgeColor((string)$applicantData['status']); ?>
            <span class="badge bg-<?php echo $badgeColor; ?> me-2"><?php echo ucfirst(str_replace('_',' ', (string)$applicantData['status'])); ?></span>
            <small class="text-muted">Applied: <?php echo formatDate($applicantData['created_at']); ?></small>
          </div>
        </div>

        <div class="dl2">
          <div>
            <span class="small-label">Phone (Primary)</span>
            <div class="value-sm"><?php echo $primaryPhone !== '' ? safe($primaryPhone) : 'N/A'; ?></div>
          </div>
          <div>
            <span class="small-label">Phone (Alternate)</span>
            <div class="value-sm"><?php echo safe($alternatePhoneDisplay); ?></div>
          </div>
          <div>
            <span class="small-label">Email</span>
            <div class="value-sm text-truncate-1"><?php echo safe($applicantData['email'] ?? 'N/A'); ?></div>
          </div>
          <div>
            <span class="small-label">DOB</span>
            <div class="value-sm"><?php echo formatDate($applicantData['date_of_birth']); ?></div>
          </div>
          <div>
            <span class="small-label">Experience</span>
            <div class="value-sm">
              <?php $yrs = (int)($applicantData['years_experience'] ?? 0); echo $yrs . ($yrs === 1 ? ' year' : ' years'); ?>
            </div>
          </div>
          <div>
            <span class="small-label">Employment</span>
            <div class="value-sm"><?php echo !empty($applicantData['employment_type']) ? safe($applicantData['employment_type']) : 'N/A'; ?></div>
          </div>
        </div>

        <div class="mt-2">
          <span class="small-label">Address</span>
          <div class="value text-truncate-2"><?php echo safe($applicantData['address']); ?></div>
        </div>

        <div class="mt-2">
          <span class="small-label">Preferred Location(s)</span>
          <div class="d-flex flex-wrap gap-1"><?php echo $locBadgesHtml; ?></div>
        </div>

        <div class="mt-2">
          <span class="small-label">Skills</span>
          <div class="d-flex flex-wrap gap-1"><?php echo $skillsPillsHtml; ?></div>
        </div>

        <div class="mt-2">
          <span class="small-label">Languages</span>
          <div class="value-sm"><?php echo $languagesDisplay; ?></div>
        </div>

        <?php if (!empty($applicantData['video_url']) && !$isPrint): ?>
          <div class="mt-2 no-print">
            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#applicantVideoModal">
              <i class="bi bi-play-circle me-1"></i>Preview Video
            </button>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Client Panel -->
  <div class="<?php echo $isPrint ? '' : 'col-right'; ?>">
    <div class="card compact mb-3">
      <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold d-flex align-items-center">
          <i class="bi bi-people me-2"></i>Latest Client Booking
        </h5>
        <?php if ($latestBooking): ?>
          <?php $bColor = bookingStatusBadgeColor((string)$latestBooking['status']); ?>
          <span class="badge bg-<?php echo $bColor; ?> text-uppercase"><?php echo safe($latestBooking['status']); ?></span>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if (!$latestBooking): ?>
          <p class="text-muted mb-0">No client booking found for this applicant.</p>
        <?php else: ?>
          <?php
            $clientName = trim(($latestBooking['client_first_name'] ?? '') . ' ' . ($latestBooking['client_middle_name'] ?? '') . ' ' . ($latestBooking['client_last_name'] ?? ''));
            $clientName = $clientName !== '' ? $clientName : '—';
          ?>
          <div class="mb-2">
            <span class="small-label">Client</span>
            <div class="value-sm"><?php echo safe($clientName); ?></div>
          </div>

          <div class="dl2">
            <div>
              <span class="small-label">Client Email</span>
              <div class="value-sm text-truncate-1"><?php echo safe($latestBooking['client_email'] ?? '—'); ?></div>
            </div>
            <div>
              <span class="small-label">Client Phone</span>
              <div class="value-sm"><?php echo safe($latestBooking['client_phone'] ?? '—'); ?></div>
            </div>
            <div>
              <span class="small-label">Appointment</span>
              <div class="value-sm"><?php echo safe($latestBooking['appointment_type'] ?? '—'); ?></div>
            </div>
            <div>
              <span class="small-label">Date &amp; Time</span>
              <div class="value-sm">
                <?php
                  $d = !empty($latestBooking['appointment_date']) ? formatDate($latestBooking['appointment_date']) : '—';
                  $t = !empty($latestBooking['appointment_time']) ? safe($latestBooking['appointment_time']) : '';
                  echo trim($d . ' ' . $t);
                ?>
              </div>
            </div>
          </div>

          <div class="mt-2">
            <span class="small-label">Client Address</span>
            <div class="value text-truncate-2"><?php echo safe($latestBooking['client_address'] ?? '—'); ?></div>
          </div>

          <div class="mt-2">
            <span class="small-label">Services</span>
            <div class="mt-1"><?php echo renderServicesHtml($latestBooking['services_json'] ?? null); ?></div>
          </div>

          <div class="dl2 mt-2">
            <div>
              <span class="small-label">Created</span>
              <div class="value-sm"><?php echo !empty($latestBooking['created_at']) ? formatDate($latestBooking['created_at']) : '—'; ?></div>
            </div>
            <div>
              <span class="small-label">Updated</span>
              <div class="value-sm"><?php echo !empty($latestBooking['updated_at']) ? formatDate($latestBooking['updated_at']) : '—'; ?></div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- SECOND ROW: Either printed full or accordions -->
<?php if ($isPrint): ?>
  <!-- PRINT: Show all detail sections expanded -->

  <div class="card compact mb-3">
    <div class="card-header bg-white py-2">
      <h5 class="mb-0 fw-semibold"><i class="bi bi-mortarboard me-2"></i>Educational Attainment</h5>
    </div>
    <div class="card-body"><?php echo $educationHtml; ?></div>
  </div>

  <div class="card compact mb-3">
    <div class="card-header bg-white py-2">
      <h5 class="mb-0 fw-semibold"><i class="bi bi-briefcase me-2"></i>Work History</h5>
    </div>
    <div class="card-body"><?php echo $workHtml; ?></div>
  </div>

  <div class="card compact mb-3">
    <div class="card-header bg-white py-2">
      <h5 class="mb-0 fw-semibold"><i class="bi bi-list-ul me-2"></i>All Client Bookings for Applicant</h5>
    </div>
    <div class="card-body">
      <?php if (empty($allBookings)): ?>
        <p class="text-muted mb-0">No bookings yet.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-bordered table-striped table-hover table-styled mb-0">
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
                  $badge = bookingStatusBadgeColor((string)$b['status']);
                ?>
                <tr>
                  <td><?php echo (int)($i + 1); ?></td>
                  <td><?php echo safe($b['appointment_type'] ?? '—'); ?></td>
                  <td><?php echo (!empty($b['appointment_date']) ? formatDate($b['appointment_date']) : '—') . ' ' . (!empty($b['appointment_time']) ? safe($b['appointment_time']) : ''); ?></td>
                  <td><?php echo safe($cName); ?></td>
                  <td>
                    <div><?php echo safe($b['client_email'] ?? '—'); ?></div>
                    <div class="text-muted small"><?php echo safe($b['client_phone'] ?? '—'); ?></div>
                  </td>
                  <td><?php echo renderServicesHtml($b['services_json'] ?? null); ?></td>
                  <td><span class="badge bg-<?php echo $badge; ?>"><?php echo safe($b['status']); ?></span></td>
                  <td><?php echo !empty($b['created_at']) ? formatDate($b['created_at']) : '—'; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card compact">
    <div class="card-header bg-white py-2">
      <h5 class="mb-0 fw-semibold"><i class="bi bi-folder2-open me-2"></i>Documents</h5>
    </div>
    <div class="card-body">
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
              <a href="<?php echo safe(getFileUrl($doc['file_path'])); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-eye me-1"></i>View
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Auto-trigger browser print in print mode
    window.addEventListener('load', function(){ window.print(); });
  </script>

<?php else: ?>

  <!-- NON-PRINT: Use accordions to keep above the fold -->
  <div class="accordion" id="extraInfoAccordion">
    <div class="accordion-item">
      <h2 class="accordion-header" id="headingOne">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseApplicantDetails" aria-expanded="false" aria-controls="collapseApplicantDetails">
          More Applicant Details (Education & Work)
        </button>
      </h2>
      <div id="collapseApplicantDetails" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#extraInfoAccordion">
        <div class="accordion-body">
          <div class="row g-3">
            <div class="col-lg-6">
              <div class="card compact">
                <div class="card-header bg-white py-2"><h5 class="mb-0 fw-semibold"><i class="bi bi-mortarboard me-2"></i>Educational Attainment</h5></div>
                <div class="card-body"><?php echo $educationHtml; ?></div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="card compact">
                <div class="card-header bg-white py-2"><h5 class="mb-0 fw-semibold"><i class="bi bi-briefcase me-2"></i>Work History</h5></div>
                <div class="card-body"><?php echo $workHtml; ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- All Bookings with Actions -->
    <div class="accordion-item">
      <h2 class="accordion-header" id="headingTwo">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAllBookings" aria-expanded="false" aria-controls="collapseAllBookings">
          All Client Bookings for Applicant
        </button>
      </h2>
      <div id="collapseAllBookings" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#extraInfoAccordion">
        <div class="accordion-body">
          <?php if (empty($allBookings)): ?>
            <p class="text-muted mb-0">No bookings yet.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-bordered table-striped table-hover table-styled mb-0">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Appointment</th>
                    <th>Date &amp; Time</th>
                    <th>Client</th>
                    <th>Contacts</th>
                    <th>Services</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                    <th>Created</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($allBookings as $i => $b): ?>
                    <?php
                      $cName  = trim(($b['client_first_name'] ?? '') . ' ' . ($b['client_middle_name'] ?? '') . ' ' . ($b['client_last_name'] ?? ''));
                      $cName  = $cName !== '' ? $cName : '—';
                      $badge  = bookingStatusBadgeColor((string)$b['status']);
                      $cid    = isset($b['id']) ? (int)$b['id'] : $i;
                      $email  = trim((string)($b['client_email'] ?? ''));
                      $phone  = trim((string)($b['client_phone'] ?? ''));
                      $subject = rawurlencode('Inquiry re: Appointment with '.$cName);
                      $bodyLines = [
                        'Hello '.$cName.',',
                        '',
                        'This is regarding your appointment.',
                        (!empty($b['appointment_date']) ? 'Date: '.formatDate($b['appointment_date']) : ''),
                        (!empty($b['appointment_time']) ? 'Time: '.$b['appointment_time'] : ''),
                        '',
                        'Thank you,'
                      ];
                      $body = rawurlencode(implode("\n", array_filter($bodyLines)));
                      $mailto = 'mailto:'.rawurlencode($email).'?subject='.$subject.'&body='.$body;
                      $modalId = 'contactModal'.$cid;
                    ?>
                    <tr>
                      <td><?php echo (int)($i + 1); ?></td>
                      <td><?php echo safe($b['appointment_type'] ?? '—'); ?></td>
                      <td><?php echo (!empty($b['appointment_date']) ? formatDate($b['appointment_date']) : '—') . ' ' . (!empty($b['appointment_time']) ? safe($b['appointment_time']) : ''); ?></td>
                      <td><?php echo safe($cName); ?></td>
                      <td>
                        <div><?php echo safe($email !== '' ? $email : '—'); ?></div>
                        <div class="text-muted small"><?php echo safe($phone !== '' ? $phone : '—'); ?></div>
                      </td>
                      <td><?php echo renderServicesHtml($b['services_json'] ?? null); ?></td>
                      <td><span class="badge bg-<?php echo $badge; ?>"><?php echo safe($b['status']); ?></span></td>
                      <td class="text-center">
                        <div class="btn-group">
                          <a href="<?php echo safe($mailto); ?>" class="btn btn-sm btn-outline-primary" title="Email Client">
                            <i class="bi bi-envelope"></i>
                          </a>
                          <button type="button" class="btn btn-sm btn-outline-success" title="Show Contact" data-bs-toggle="modal" data-bs-target="#<?php echo safe($modalId); ?>">
                            <i class="bi bi-telephone"></i>
                          </button>
                        </div>
                      </td>
                      <td><?php echo !empty($b['created_at']) ? formatDate($b['created_at']) : '—'; ?></td>
                    </tr>

                    <!-- Contact Modal (per booking) -->
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
                              <div class="small-label">Client</div>
                              <div class="value"><?php echo safe($cName); ?></div>
                            </div>
                            <div class="row g-3">
                              <div class="col-md-6">
                                <div class="small-label">Email</div>
                                <div class="d-flex align-items-center gap-2">
                                  <i class="bi bi-envelope text-muted"></i>
                                  <?php if ($email !== ''): ?>
                                    <a href="mailto:<?php echo safe($email); ?>" class="value-sm text-decoration-none"><?php echo safe($email); ?></a>
                                  <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                  <?php endif; ?>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="small-label">Phone</div>
                                <div class="d-flex align-items-center gap-2">
                                  <i class="bi bi-telephone text-muted"></i>
                                  <?php if ($phone !== ''): ?>
                                    <a href="tel:<?php echo safe($phone); ?>" class="value-sm text-decoration-none"><?php echo safe($phone); ?></a>
                                  <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                  <?php endif; ?>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <?php if ($email !== ''): ?>
                              <a href="<?php echo safe($mailto); ?>" class="btn btn-primary">
                                <i class="bi bi-envelope me-1"></i>Email
                              </a>
                            <?php endif; ?>
                            <?php if ($phone !== ''): ?>
                              <a href="tel:<?php echo safe($phone); ?>" class="btn btn-success">
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
      <h2 class="accordion-header" id="headingThree">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDocuments" aria-expanded="false" aria-controls="collapseDocuments">
          Applicant Documents
        </button>
      </h2>
      <div id="collapseDocuments" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#extraInfoAccordion">
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
                  <a href="<?php echo safe(getFileUrl($doc['file_path'])); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye me-1"></i>View
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

<?php endif; ?>

<!-- Video Modal (non-print only) -->
<?php if (!empty($applicantData['video_url']) && !$isPrint): ?>
<div class="modal fade" id="applicantVideoModal" tabindex="-1" aria-labelledby="applicantVideoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title" id="applicantVideoModalLabel">
          <i class="bi bi-film me-2"></i><?php echo !empty($applicantData['video_title']) ? safe($applicantData['video_title']) : 'Applicant Video'; ?>
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-2">
        <video controls preload="metadata" style="width:100%; max-height:70vh; background:#000;">
          <source src="<?php echo safe(getFileUrl($applicantData['video_url'])); ?>" type="video/mp4">
          Your browser does not support the video tag.
        </video>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>