<?php
$pageTitle = 'View Applicant';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

$applicant = new Applicant($database);

if (!isset($_GET['id'])) {
    redirect('applicants.php');
}

$id = (int)$_GET['id'];
$applicantData = $applicant->getById($id);

if (!$applicantData) {
    setFlashMessage('error', 'Applicant not found.');
    redirect('applicants.php');
}

$documents = $applicant->getDocuments($id);

/**
 * ------- Helpers to render JSON fields nicely -------
 */

/**
 * Preferred location: JSON array -> clean string.
 * Shows first city if long (with full list as title tooltip).
 */
function renderPreferredLocationText(?string $json, int $maxLen = 40): array {
    // returns [displayText, titleText]
    if (empty($json)) return ['N/A', ''];
    $arr = json_decode($json, true);
    if (!is_array($arr)) {
        $fallback = trim($json);
        $fallback = trim($fallback, " \t\n\r\0\x0B[]\"");
        return [$fallback !== '' ? $fallback : 'N/A', $fallback];
    }
    $cities = array_values(array_filter(array_map('trim', $arr), fn($v)=>is_string($v) && $v!==''));
    if (!$cities) return ['N/A', ''];
    $full = implode(', ', $cities);
    if (mb_strlen($full) > $maxLen) {
        return [$cities[0], $full]; // show first city, keep full in title
    }
    return [$full, $full];
}

/**
 * Educational attainment JSON -> clean multiline HTML
 */
function renderEducationHtml(?string $json): string {
    if (empty($json)) return '<span class="text-muted">N/A</span>';
    $edu = json_decode($json, true);
    if (!is_array($edu)) {
        return htmlspecialchars($json, ENT_QUOTES, 'UTF-8'); // fallback raw
    }

    $parts = [];

    // Elementary
    $elem = $edu['elementary'] ?? [];
    $elemSchool = trim((string)($elem['school'] ?? ''));
    $elemYear   = trim((string)($elem['year'] ?? ''));
    if ($elemSchool || $elemYear) {
        $line = 'Elementary: ' . htmlspecialchars($elemSchool, ENT_QUOTES, 'UTF-8');
        if ($elemYear !== '') $line .= ' (' . htmlspecialchars($elemYear, ENT_QUOTES, 'UTF-8') . ')';
        $parts[] = $line;
    }

    // Highschool
    $hs = $edu['highschool'] ?? [];
    $hsSchool = trim((string)($hs['school'] ?? ''));
    $hsYear   = trim((string)($hs['year'] ?? ''));
    if ($hsSchool || $hsYear) {
        $line = 'High School: ' . htmlspecialchars($hsSchool, ENT_QUOTES, 'UTF-8');
        if ($hsYear !== '') $line .= ' (' . htmlspecialchars($hsYear, ENT_QUOTES, 'UTF-8') . ')';
        $parts[] = $line;
    }

    // Senior High
    $sh = $edu['senior_high'] ?? [];
    $shSchool = trim((string)($sh['school'] ?? ''));
    $shStrand = trim((string)($sh['strand'] ?? ''));
    $shYear   = trim((string)($sh['year'] ?? ''));
    if ($shSchool || $shStrand || $shYear) {
        $line = 'Senior High: ' . htmlspecialchars($shSchool, ENT_QUOTES, 'UTF-8');
        $details = [];
        if ($shStrand !== '') $details[] = htmlspecialchars($shStrand, ENT_QUOTES, 'UTF-8');
        if ($shYear   !== '') $details[] = htmlspecialchars($shYear,   ENT_QUOTES, 'UTF-8');
        if ($details) $line .= ' (' . implode(' • ', $details) . ')';
        $parts[] = $line;
    }

    // College
    $col = $edu['college'] ?? [];
    $colSchool = trim((string)($col['school'] ?? ''));
    $colCourse = trim((string)($col['course'] ?? ''));
    $colYear   = trim((string)($col['year'] ?? ''));
    if ($colSchool || $colCourse || $colYear) {
        $line = 'College: ' . htmlspecialchars($colSchool, ENT_QUOTES, 'UTF-8');
        $details = [];
        if ($colCourse !== '') $details[] = htmlspecialchars($colCourse, ENT_QUOTES, 'UTF-8');
        if ($colYear   !== '') $details[] = htmlspecialchars($colYear,   ENT_QUOTES, 'UTF-8');
        if ($details) $line .= ' (' . implode(' • ', $details) . ')';
        $parts[] = $line;
    }

    if (!$parts) return '<span class="text-muted">N/A</span>';
    return htmlspecialchars(implode("\n", $parts), ENT_QUOTES, 'UTF-8');
}

/**
 * Work history JSON array -> multiline HTML
 * Each item: { company, years, role, location }
 * Renders: "Company — Role — Years — Location"
 */
function renderWorkHistoryHtml(?string $json): string {
    if (empty($json)) return '<span class="text-muted">N/A</span>';
    $arr = json_decode($json, true);
    if (!is_array($arr)) {
        return htmlspecialchars($json, ENT_QUOTES, 'UTF-8'); // fallback raw
    }
    $lines = [];
    foreach ($arr as $row) {
        if (!is_array($row)) continue;
        $company  = trim((string)($row['company']  ?? ''));
        $role     = trim((string)($row['role']     ?? ''));
        $years    = trim((string)($row['years']    ?? ''));
        $location = trim((string)($row['location'] ?? ''));
        if ($company === '' && $role === '' && $years === '' && $location === '') continue;

        $bits = [];
        if ($company  !== '') $bits[] = $company;
        if ($role     !== '') $bits[] = $role;
        if ($years    !== '') $bits[] = $years;
        if ($location !== '') $bits[] = $location;

        $lines[] = implode(' — ', array_map(fn($s)=>htmlspecialchars($s, ENT_QUOTES, 'UTF-8'), $bits));
    }
    if (!$lines) return '<span class="text-muted">N/A</span>';
    return implode("\n", $lines);
}

/**
 * Render languages JSON array -> string (comma-separated)
 */
function renderLanguages(?string $json): string {
    if (empty($json)) return 'N/A';
    $arr = json_decode($json, true);
    if (!is_array($arr) || empty($arr)) return 'N/A';
    $clean = array_values(array_filter(array_map('trim', $arr)));
    return $clean ? htmlspecialchars(implode(', ', $clean), ENT_QUOTES, 'UTF-8') : 'N/A';
}

/**
 * Render specialization skills JSON array -> HTML pills (red-ish ovals)
 * IMPORTANT: We escape each label ONCE and return HTML (no further escaping when echoing).
 */
function renderSkillsPills(?string $json): string {
    if (empty($json)) return '<span class="text-muted">N/A</span>';
    $arr = json_decode($json, true);
    if (!is_array($arr) || empty($arr)) return '<span class="text-muted">N/A</span>';

    $clean = array_values(array_filter(array_map('trim', $arr)));
    if (!$clean) return '<span class="text-muted">N/A</span>';

    $htmlParts = [];
    foreach ($clean as $label) {
        $safe = htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); // escape once
        // Using Bootstrap badge + custom class for red pill
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

// Prepare rendered fields
[$locationDisplay, $locationTitle] = renderPreferredLocationText($applicantData['preferred_location'] ?? '');
$educationHtml  = renderEducationHtml($applicantData['educational_attainment'] ?? '');
$workHtml       = renderWorkHistoryHtml($applicantData['work_history'] ?? '');

// Picture URL
$pictureUrl = !empty($applicantData['picture']) ? getFileUrl($applicantData['picture']) : null;

// Phones
$primaryPhone   = trim((string)($applicantData['phone_number'] ?? ''));
$alternatePhone = trim((string)($applicantData['alt_phone_number'] ?? ''));
$alternatePhoneDisplay = ($alternatePhone !== '') ? $alternatePhone : 'N/A';

// Languages & Specializations
$languagesDisplay = renderLanguages($applicantData['languages'] ?? '');
$skillsPillsHtml  = renderSkillsPills($applicantData['specialization_skills'] ?? '');
?>
<style>
/* Red-ish pill style for specialization badges */
.skill-pill{
  background-color:#ffe5e5;   /* light red background */
  color:#9b1c1c;               /* dark red text */
  border:1px solid #ffc9c9;    /* soft red border */
  padding:.45rem .65rem;
  font-weight:600;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-semibold">Applicant Details</h4>
    <div>
        <a href="edit-applicant.php?id=<?php echo $id; ?>" class="btn btn-warning me-2">
            <i class="bi bi-pencil me-2"></i>Edit
        </a>
        <a href="applicants.php" class="btn btn-outline-secondary">
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
                    <div class="bg-secondary text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 150px; height: 150px; font-size: 3rem;">
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
                        <div class="fw-semibold"><?php echo $primaryPhone !== '' ? htmlspecialchars($primaryPhone) : 'N/A'; ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Phone (Alternate)</small>
                        <div class="fw-semibold"><?php echo htmlspecialchars($alternatePhoneDisplay); ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Email</small>
                        <div class="fw-semibold"><?php echo htmlspecialchars($applicantData['email'] ?? 'N/A'); ?></div>
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
                        <small class="text-muted">Preferred Location</small>
                        <div class="fw-semibold" title="<?php echo htmlspecialchars($locationTitle, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($locationDisplay, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <small class="text-muted">Address</small>
                        <div class="fw-semibold"><?php echo htmlspecialchars($applicantData['address']); ?></div>
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
                        <div class="fw-semibold"><?php echo !empty($applicantData['employment_type']) ? htmlspecialchars($applicantData['employment_type']) : 'N/A'; ?></div>
                    </div>

                    <div class="col-md-12">
                        <small class="text-muted">Specialization Skills</small>
                        <!-- IMPORTANT: skills pills are HTML, DO NOT escape again -->
                        <div class="d-flex flex-wrap gap-2"><?php echo $skillsPillsHtml; ?></div>
                    </div>

                    <div class="col-md-12">
                        <small class="text-muted">Languages</small>
                        <div class="fw-semibold"><?php echo $languagesDisplay; ?></div>
                    </div>

                    <div class="col-md-12">
                        <small class="text-muted">Educational Attainment</small>
                        <div class="fw-semibold" style="white-space: pre-line;"><?php echo $educationHtml; ?></div>
                    </div>
                    <div class="col-md-12">
                        <small class="text-muted">Work History</small>
                        <div class="fw-semibold" style="white-space: pre-line;"><?php echo $workHtml; ?></div>
                    </div>
                </div>
            </div>
        </div>

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
                                <a href="<?php echo htmlspecialchars(getFileUrl($doc['file_path']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
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

<?php