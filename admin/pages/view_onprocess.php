<?php
// FILE: pages/view-onprocess.php
$pageTitle = 'View On-Process (Applicant + Client)';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

// We assume $database (mysqli connection wrapper) and helpers (redirect, formatDate, getFileUrl, getFullName, setFlashMessage)
// are available from header.php as they are in your existing pages.

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
    // Go back to On Process list (preserve search)
    $dest = 'on-process.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
    redirect($dest);
    exit;
}

$id = (int)$_GET['id'];

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
 * Load all bookings (optional section below)
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
   Helpers (renderers + utilities) - same design as reference
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

/**
 * Educational Attainment Renderer (timeline style)
 * Returns HTML (do not escape again).
 */
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

/**
 * Work history JSON array -> structured HTML list.
 * Each item: { company, role, years, location }
 * Returns HTML (do not escape again).
 */
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

        $line = '<li class="mb-2">';
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

/**
 * Render languages JSON array -> string
 */
function renderLanguages(?string $json): string {
    if ($json === null || trim($json) === '') return 'N/A';
    $arr = json_decode($json, true);
    if (!is_array($arr) || empty($arr)) return 'N/A';
    $clean = array_values(array_filter(array_map('trim', $arr)));
    return $clean ? safe(implode(', ', $clean)) : 'N/A';
}

/**
 * Render specialization skills JSON array -> HTML pills
 * Returns HTML (do not escape again).
 */
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

/**
 * Status badge color map (applicant)
 */
function statusBadgeColor(string $status): string {
    $map = [
        'pending'    => 'warning',
        'on_process' => 'info',
        'approved'   => 'success',
        'deleted'    => 'secondary',
    ];
    return $map[$status] ?? 'secondary';
}

/**
 * Booking status badge color map
 */
function bookingStatusBadgeColor(string $status): string {
    $map = [
        'submitted' => 'secondary',
        'confirmed' => 'success',
        'cancelled' => 'danger',
    ];
    return $map[$status] ?? 'secondary';
}

/**
 * Render services_json from client_bookings
 * - Accepts: array of strings OR array of objects with common name keys
 * - Returns HTML (do not escape again)
 */
function renderServicesHtml(?string $json): string {
    if ($json === null || trim($json) === '') return '<span class="text-muted">N/A</span>';
    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        // Show raw if invalid JSON
        return '<div>'.safe($json).'</div>';
    }

    if (is_array($data)) {
        $labels = [];
        foreach ($data as $item) {
            if (is_string($item)) {
                $label = trim($item);
                if ($label !== '') $labels[] = $label;
            } elseif (is_array($item)) {
                // Try common keys
                $keyOrder = ['name', 'label', 'service', 'title'];
                $found = null;
                foreach ($keyOrder as $k) {
                    if (isset($item[$k]) && is_string($item[$k]) && trim($item[$k]) !== '') {
                        $found = trim($item[$k]);
                        break;
                    }
                }
                if ($found !== null) $labels[] = $found;
                else {
                    // Last resort: stringify compact form
                    $labels[] = trim((string)json_encode($item, JSON_UNESCAPED_UNICODE));
                }
            }
        }

        if (!empty($labels)) {
            $html = [];
            foreach ($labels as $lbl) {
                $html[] = '<span class="badge rounded-pill bg-light text-dark border" style="padding:.4rem .6rem; font-weight:600;">'.safe($lbl).'</span>';
            }
            return '<div class="d-flex flex-wrap gap-2">'.implode(' ', $html).'</div>';
        }

        // Empty array structure
        return '<span class="text-muted">N/A</span>';
    }

    // Non-array JSON (object, scalar)
    return '<div>'.safe(json_encode($data, JSON_UNESCAPED_UNICODE)).'</div>';
}

/* ============================================================
   Prepare data for view
   ============================================================ */

$educationHtml = renderEducationListHtml($applicantData['educational_attainment'] ?? '');
$workHtml      = renderWorkHistoryListHtml($applicantData['work_history'] ?? '');
$locBadgesHtml = renderPreferredLocationBadges($applicantData['preferred_location'] ?? '');

// Picture URL
$pictureUrl = !empty($applicantData['picture']) ? getFileUrl($applicantData['picture']) : null;

// Phones
$primaryPhone   = trim((string)($applicantData['phone_number'] ?? ''));
$alternatePhone = trim((string)($applicantData['alt_phone_number'] ?? ''));
$alternatePhoneDisplay = ($alternatePhone !== '') ? $alternatePhone : 'N/A';

// Languages & Specializations
$languagesDisplay = renderLanguages($applicantData['languages'] ?? '');
$skillsPillsHtml  = renderSkillsPills($applicantData['specialization_skills'] ?? '');

// Back & Edit URLs preserving search (if any)
$backUrl = 'on-process.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
$editUrl = 'edit-applicant.php?id=' . $id . ($q !== '' ? ('&q=' . urlencode($q)) : '');
?>
<style>
/* Red-ish pill style for specialization badges */
.skill-pill{
  background-color:#ffe5e5;
  color:#9b1c1c;
  border:1px solid #ffc9c9;
  padding:.45rem .65rem;
  font-weight:600;
}

/* Soft blue pill style for preferred locations */
.loc-pill{
  background-color:#e7f1ff;
  color:#0b5ed7;
  border:1px solid #cfe2ff;
  padding:.35rem .6rem;
  font-weight:600;
}

/* Educational Attainment Timeline */
.edu-timeline {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.edu-item {
  padding-left: 0.75rem;
  border-left: 3px solid #e9ecef;
}

.edu-header {
  display: flex;
  align-items: center;
  gap: .5rem;
  font-weight: 600;
}

.edu-icon {
  font-size: 1.1rem;
}

.edu-level {
  color: #212529;
}

.edu-year {
  margin-left: auto;
  font-size: .8rem;
  background: #f1f3f5;
  padding: .15rem .5rem;
  border-radius: .5rem;
  color: #495057;
}

.edu-school {
  margin-left: 1.6rem;
  font-weight: 600;
  color: #0d6efd;
}

.edu-detail {
  margin-left: 1.6rem;
  font-size: .9rem;
  color: #6c757d;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-semibold">Applicant + Client (On Process)</h4>
    <div>
        <a href="<?php echo safe($editUrl); ?>" class="btn btn-warning me-2">
            <i class="bi bi-pencil me-2"></i>Edit Applicant
        </a>
        <a href="<?php echo safe($backUrl); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to On Process
        </a>
    </div>
</div>

<div class="row">
    <!-- Left column: Applicant Summary -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <?php if ($pictureUrl): ?>
                    <img src="<?php echo safe($pictureUrl); ?>"
                         alt="Profile" class="rounded-circle mb-3" width="150" height="150" style="object-fit: cover;">
                <?php else: ?>
                    <div class="bg-secondary text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center"
                         style="width: 150px; height: 150px; font-size: 3rem;">
                        <?php echo strtoupper(substr($applicantData['first_name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>

                <h5 class="fw-bold mb-1">
                    <?php echo getFullName($applicantData['first_name'], $applicantData['middle_name'], $applicantData['last_name'], $applicantData['suffix']); ?>
                </h5>

                <?php $badgeColor = statusBadgeColor($applicantData['status']); ?>
                <span class="badge bg-<?php echo $badgeColor; ?> mb-3">
                    <?php echo ucfirst(str_replace('_', ' ', (string)$applicantData['status'])); ?>
                </span>

                <div class="text-start mt-4">
                    <div class="mb-2">
                        <small class="text-muted">Phone (Primary)</small>
                        <div class="fw-semibold"><?php echo $primaryPhone !== '' ? safe($primaryPhone) : 'N/A'; ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Phone (Alternate)</small>
                        <div class="fw-semibold"><?php echo safe($alternatePhoneDisplay); ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Email</small>
                        <div class="fw-semibold"><?php echo safe($applicantData['email'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Date Applied</small>
                        <div class="fw-semibold"><?php echo formatDate($applicantData['created_at']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($applicantData['video_url'])): ?>
        <div class="card mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-semibold">
                    <i class="bi bi-film me-2"></i>
                    <?php echo !empty($applicantData['video_title']) ? safe($applicantData['video_title']) : 'Video'; ?>
                </h5>
            </div>
            <div class="card-body">
                <?php
                    // If your system expects file-based videos here, you can keep it as <video>.
                    // Otherwise handle iframe providers if needed (youtube/vimeo) based on your schema.
                ?>
                <video controls preload="metadata" style="width:100%; max-height:500px; background:#000;">
                    <source src="<?php echo safe(getFileUrl($applicantData['video_url'])); ?>" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right column: Applicant Details + Client Booking -->
    <div class="col-md-8">
        <!-- Applicant Personal Info -->
        <div class="card mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-semibold">Applicant Information</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted">Date of Birth</small>
                        <div class="fw-semibold"><?php echo formatDate($applicantData['date_of_birth']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Preferred Location(s)</small>
                        <div class="fw-semibold d-flex flex-wrap gap-2"><?php echo $locBadgesHtml; ?></div>
                    </div>

                    <div class="col-md-12">
                        <small class="text-muted">Address</small>
                        <div class="fw-semibold"><?php echo safe($applicantData['address']); ?></div>
                    </div>

                    <div class="col-md-6">
                        <small class="text-muted">Years of Experience</small>
                        <div class="fw-semibold">
                            <?php
                                $yrs = isset($applicantData['years_experience']) ? (int)$applicantData['years_experience'] : 0;
                                echo $yrs . ($yrs === 1 ? ' year' : ' years');
                            ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Employment Type</small>
                        <div class="fw-semibold"><?php echo !empty($applicantData['employment_type']) ? safe($applicantData['employment_type']) : 'N/A'; ?></div>
                    </div>

                    <div class="col-md-12">
                        <small class="text-muted">Specialization Skills</small>
                        <div class="d-flex flex-wrap gap-2"><?php echo $skillsPillsHtml; ?></div>
                    </div>

                    <div class="col-md-12">
                        <small class="text-muted">Languages</small>
                        <div class="fw-semibold"><?php echo $languagesDisplay; ?></div>
                    </div>

                    <div class="col-md-12">
                        <small class="text-muted">Educational Attainment</small>
                        <div class="mt-1"><?php echo $educationHtml; ?></div>
                    </div>

                    <div class="col-md-12">
                        <small class="text-muted">Work History</small>
                        <div class="mt-1"><?php echo $workHtml; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Client Booking (Latest) -->
        <div class="card mb-4">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-semibold d-flex align-items-center">
                    <i class="bi bi-people me-2"></i> Latest Client Booking
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
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted">Client Name</small>
                            <?php
                                $clientName = trim(($latestBooking['client_first_name'] ?? '') . ' ' . ($latestBooking['client_middle_name'] ?? '') . ' ' . ($latestBooking['client_last_name'] ?? ''));
                                $clientName = $clientName !== '' ? $clientName : '—';
                            ?>
                            <div class="fw-semibold"><?php echo safe($clientName); ?></div>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Client Address</small>
                            <div class="fw-semibold"><?php echo safe($latestBooking['client_address'] ?? '—'); ?></div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted">Client Email</small>
                            <div class="fw-semibold"><?php echo safe($latestBooking['client_email'] ?? '—'); ?></div>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Client Phone</small>
                            <div class="fw-semibold"><?php echo safe($latestBooking['client_phone'] ?? '—'); ?></div>
                        </div>

                        <div class="col-md-4">
                            <small class="text-muted">Appointment Type</small>
                            <div class="fw-semibold"><?php echo safe($latestBooking['appointment_type'] ?? '—'); ?></div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Appointment Date</small>
                            <div class="fw-semibold"><?php echo !empty($latestBooking['appointment_date']) ? formatDate($latestBooking['appointment_date']) : '—'; ?></div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Appointment Time</small>
                            <div class="fw-semibold"><?php echo !empty($latestBooking['appointment_time']) ? safe($latestBooking['appointment_time']) : '—'; ?></div>
                        </div>

                        <div class="col-md-12">
                            <small class="text-muted">Services</small>
                            <div class="mt-1"><?php echo renderServicesHtml($latestBooking['services_json'] ?? null); ?></div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted">Created</small>
                            <div class="fw-semibold"><?php echo !empty($latestBooking['created_at']) ? formatDate($latestBooking['created_at']) : '—'; ?></div>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Updated</small>
                            <div class="fw-semibold"><?php echo !empty($latestBooking['updated_at']) ? formatDate($latestBooking['updated_at']) : '—'; ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- All Bookings (Optional overview) -->
        <div class="card mb-4">
            <div class="card-header bg-white py-3">
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
                                    <th>Date & Time</th>
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
                                        $dt = trim(($b['appointment_date'] ?? '').' '.($b['appointment_time'] ?? ''));
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

        <!-- Documents -->
        <div class="card">
            <div class="card-header bg-white py-3">
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
                                <a href="<?php echo safe(getFileUrl($doc['file_path'])); ?>"
                                   target="_blank" class="btn btn-sm btn-outline-primary">
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

<?php require_once '../includes/footer.php'; ?>