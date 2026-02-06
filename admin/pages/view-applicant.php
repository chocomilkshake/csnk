<?php
// FILE: pages/view-applicant.php
$pageTitle = 'View Applicant';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

$applicant = new Applicant($database);

// Preserve list search (if user came from a searched list)
$q = '';
if (isset($_GET['q'])) {
    $q = trim((string)$_GET['q']);
    if (mb_strlen($q) > 200) {
        $q = mb_substr($q, 0, 200);
    }
}

if (!isset($_GET['id'])) {
    $dest = 'applicants.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
    redirect($dest);
    exit;
}

$id = (int)$_GET['id'];
$applicantData = $applicant->getById($id);

if (!$applicantData) {
    setFlashMessage('error', 'Applicant not found.');
    $dest = 'applicants.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
    redirect($dest);
    exit;
}

$documents = $applicant->getDocuments($id);

/* ============================================================
   Helpers (renderers + utilities)
   ============================================================ */

/**
 * Preferred locations (JSON) -> HTML badges (show ALL cities)
 * Accepts JSON array or comma-separated string fallback.
 * Returns HTML (do not escape again on echo).
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
        // Fallback: support comma-separated value or raw single string
        $fallback = trim($json);
        // Strip common wrappers like ["..."]
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

    // Build badges
    $html = [];
    foreach ($cities as $city) {
        $safe = htmlspecialchars($city, ENT_QUOTES, 'UTF-8');
        $html[] = '<span class="badge rounded-pill loc-pill">'.$safe.'</span>';
    }
    return implode(' ', $html);
}

/**
 * Improved Educational Attainment Renderer
 * - Clean card/timeline style (very readable)
 * - Handles missing levels safely
 * RETURNS HTML (do NOT escape again when echoing)
 */
function renderEducationListHtml(?string $json): string {
    if (empty($json)) {
        return '<span class="text-muted">N/A</span>';
    }

    $edu = json_decode($json, true);
    if (!is_array($edu)) {
        return '<div>'.htmlspecialchars($json, ENT_QUOTES, 'UTF-8').'</div>';
    }

    // Config per level (order + icon)
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
        $html .=     '<span class="edu-level">'.htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8').'</span>';
        if ($year !== '') {
            $html .=   '<span class="edu-year">'.htmlspecialchars($year, ENT_QUOTES, 'UTF-8').'</span>';
        }
        $html .=   '</div>';

        if ($school !== '') {
            $html .= '<div class="edu-school">'.htmlspecialchars($school, ENT_QUOTES, 'UTF-8').'</div>';
        }

        if ($strand !== '' || $course !== '') {
            $detail = $strand !== '' ? $strand : $course;
            $html .= '<div class="edu-detail">'.htmlspecialchars($detail, ENT_QUOTES, 'UTF-8').'</div>';
        }

        $html .= '</div>';
    }

    $html .= '</div>';

    return $html !== '<div class="edu-timeline"></div>' ? $html : '<span class="text-muted">N/A</span>';
}

/**
 * Work history JSON array -> structured HTML list.
 * Each item: { company, role, years, location }
 * Returns HTML (do not escape again on echo).
 */
function renderWorkHistoryListHtml(?string $json): string {
    if ($json === null || trim($json) === '') {
        return '<span class="text-muted">N/A</span>';
    }
    $arr = json_decode($json, true);
    if (!is_array($arr)) {
        return '<div>'.htmlspecialchars($json, ENT_QUOTES, 'UTF-8').'</div>';
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
        if ($company !== '') $top[] = '<span class="fw-semibold">'.htmlspecialchars($company, ENT_QUOTES, 'UTF-8').'</span>';
        if ($role    !== '') $top[] = htmlspecialchars($role, ENT_QUOTES, 'UTF-8');

        $meta = [];
        if ($years   !== '') $meta[] = htmlspecialchars($years, ENT_QUOTES, 'UTF-8');
        if ($location!== '') $meta[] = htmlspecialchars($location, ENT_QUOTES, 'UTF-8');

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
 * Render languages JSON array -> string (comma-separated)
 */
function renderLanguages(?string $json): string {
    if ($json === null || trim($json) === '') return 'N/A';
    $arr = json_decode($json, true);
    if (!is_array($arr) || empty($arr)) return 'N/A';
    $clean = array_values(array_filter(array_map('trim', $arr)));
    return $clean ? htmlspecialchars(implode(', ', $clean), ENT_QUOTES, 'UTF-8') : 'N/A';
}

/**
 * Render specialization skills JSON array -> HTML pills
 * IMPORTANT: Returns HTML (do not escape again).
 */
function renderSkillsPills(?string $json): string {
    if ($json === null || trim($json) === '') return '<span class="text-muted">N/A</span>';
    $arr = json_decode($json, true);
    if (!is_array($arr) || empty($arr)) return '<span class="text-muted">N/A</span>';

    $clean = array_values(array_filter(array_map('trim', $arr)));
    if (!$clean) return '<span class="text-muted">N/A</span>';

    $htmlParts = [];
    foreach ($clean as $label) {
        $safe = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $htmlParts[] = '<span class="badge rounded-pill skill-pill">'.$safe.'</span>';
    }
    return implode(' ', $htmlParts);
}

/**
 * Status badge color map
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
$backUrl = 'applicants.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
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
    <h4 class="mb-0 fw-semibold">Applicant Details</h4>
    <div>
        <a href="<?php echo htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-warning me-2">
            <i class="bi bi-pencil me-2"></i>Edit
        </a>
        <a href="<?php echo htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to List
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <?php if ($pictureUrl): ?>
                    <img src="<?php echo htmlspecialchars($pictureUrl, ENT_QUOTES, 'UTF-8'); ?>"
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
                    <?php echo ucfirst(str_replace('_', ' ', $applicantData['status'])); ?>
                </span>

                <div class="text-start mt-4">
                    <div class="mb-2">
                        <small class="text-muted">Phone (Primary)</small>
                        <div class="fw-semibold"><?php echo $primaryPhone !== '' ? htmlspecialchars($primaryPhone, ENT_QUOTES, 'UTF-8') : 'N/A'; ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Phone (Alternate)</small>
                        <div class="fw-semibold"><?php echo htmlspecialchars($alternatePhoneDisplay, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Email</small>
                        <div class="fw-semibold"><?php echo htmlspecialchars($applicantData['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Date Applied</small>
                        <div class="fw-semibold"><?php echo formatDate($applicantData['created_at']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-semibold">Personal Information</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted">Date of Birth</small>
                        <div class="fw-semibold"><?php echo formatDate($applicantData['date_of_birth']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Preferred Location(s)</small>
                        <!-- HTML pills; do not escape again -->
                        <div class="fw-semibold d-flex flex-wrap gap-2"><?php echo $locBadgesHtml; ?></div>
                    </div>

                    <div class="col-md-12">
                        <small class="text-muted">Address</small>
                        <div class="fw-semibold"><?php echo htmlspecialchars($applicantData['address'], ENT_QUOTES, 'UTF-8'); ?></div>
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
                        <div class="fw-semibold"><?php echo !empty($applicantData['employment_type']) ? htmlspecialchars($applicantData['employment_type'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?></div>
                    </div>

                    <div class="col-md-12">
                        <small class="text-muted">Specialization Skills</small>
                        <!-- HTML pills; do not escape again -->
                        <div class="d-flex flex-wrap gap-2"><?php echo $skillsPillsHtml; ?></div>
                    </div>

                    <div class="col-md-12">
                        <small class="text-muted">Languages</small>
                        <div class="fw-semibold"><?php echo $languagesDisplay; ?></div>
                    </div>

                    <div class="col-md-12">
                        <small class="text-muted">Educational Attainment</small>
                        <!-- Structured timeline HTML; do not escape again -->
                        <div class="mt-1"><?php echo $educationHtml; ?></div>
                    </div>

                    <div class="col-md-12">
                        <small class="text-muted">Work History</small>
                        <!-- Structured list HTML; do not escape again -->
                        <div class="mt-1"><?php echo $workHtml; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($applicantData['video_url'])): ?>
        <div class="card mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-semibold">
                    <i class="bi bi-film me-2"></i>
                    <?php echo !empty($applicantData['video_title']) ? htmlspecialchars($applicantData['video_title'], ENT_QUOTES, 'UTF-8') : 'Video'; ?>
                </h5>
            </div>
            <div class="card-body">
                <video controls preload="metadata" style="width:100%; max-height:500px; background:#000;">
                    <source src="<?php echo htmlspecialchars(getFileUrl($applicantData['video_url']), ENT_QUOTES, 'UTF-8'); ?>" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-semibold">Documents</h5>
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
                                    <?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?>
                                </span>
                                <a href="<?php echo htmlspecialchars(getFileUrl($doc['file_path']), ENT_QUOTES, 'UTF-8'); ?>"
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