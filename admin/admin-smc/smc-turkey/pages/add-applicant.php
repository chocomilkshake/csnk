<?php
// FILE: admin/admin-smc/smc-turkey/pages/add-applicant.php (SMC - Multi-BU)
$pageTitle = 'Add New Applicant (SMC)';

// SMC header (auth + SMC access + BU guard)
require_once __DIR__ . '/../includes/header.php';

// Shared model (SMC-ready, BU-aware) - use local Applicant class with getBusinessUnitsByAgency method
require_once __DIR__ . '/../includes/Applicant.php';

$applicant   = new Applicant($database);
$errors      = [];

// Session-provided scoping
$currentBuId  = (int)($_SESSION['current_bu_id'] ?? 0);

// Step 2: ensure both Turkey(2) & Bahrain(3) are allowed by default when session is empty or invalid
// FIX: Check if allowed_bu_ids is set AND is a non-empty array
$sessionBuIds = $_SESSION['allowed_bu_ids'] ?? null;
if (is_array($sessionBuIds) && count($sessionBuIds) > 0) {
    $allowedBuIds = array_map('intval', $sessionBuIds);
    // Additional validation: filter out any non-positive values
    $allowedBuIds = array_values(array_filter($allowedBuIds, function($v) { return $v > 0; }));
} else {
    // Default SMC BU IDs: Turkey(2) and Bahrain(3)
    $allowedBuIds = [2, 3];
}

// ----------------------------------------------------------------------
// Load SMC business units visible to this user (guard by allowedBuIds).
// This returns: id, code, bu_name, country_id, country_name, label, agency_code
// ----------------------------------------------------------------------
$smcBusinessUnitsAll = $applicant->getBusinessUnitsByAgency('smc', true, !empty($allowedBuIds) ? $allowedBuIds : null);
$smcBusinessUnits    = array_values($smcBusinessUnitsAll);

// Compute selected BU (POST wins; else default to current; else first allowed)
$selectedBuId = $currentBuId;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedBuId = (int)($_POST['business_unit_id'] ?? $currentBuId);
}
$validBuIds = array_map(fn($r) => (int)$r['id'], $smcBusinessUnits);
if (!in_array($selectedBuId, $validBuIds, true)) {
    $selectedBuId = $currentBuId;
}
if (!in_array($selectedBuId, $validBuIds, true)) {
    // final fallback: auto-select first available BU to keep UI usable
    $selectedBuId = $validBuIds[0] ?? 0;
}

$businessUnitsForDropdown = $smcBusinessUnits;

/**
 * -----------------------------------------
 * Quick-select country helpers (SMC)
 * -----------------------------------------
 * We try to locate one BU per country (Turkey/Bahrain).
 * If multiple BUs per country exist, we pick the first.
 * Buttons will be disabled if the country BU is missing.
 */
$turkeyBuId  = null;
$bahrainBuId = null;
foreach ($businessUnitsForDropdown as $bu) {
    $cname = strtolower(trim($bu['country_name'] ?? ''));
    if ($turkeyBuId === null && $cname === 'turkey') {
        $turkeyBuId = (int)$bu['id'];
    }
    if ($bahrainBuId === null && $cname === 'bahrain') {
        $bahrainBuId = (int)$bu['id'];
    }
}

// ========================== Helpers ==========================
function computeYearsFromString($text) {
    $t = trim((string)$text);
    if ($t === '') return 0;
    $norm = str_replace(['–','—','to'], ['-','-','-'], strtolower($t));
    if (preg_match_all('/\d{1,4}/', $norm, $m) && count($m[0]) >= 2) {
        $a = (int)$m[0][0]; $b = (int)$m[0][1];
        if ($a > 0 && $b > 0) return max(0, $b - $a);
    }
    if (preg_match('/\d+/', $norm, $n)) return max(0, (int)$n[0]);
    return 0;
}

function computeTotalYears($workHistoryArr) {
    $total = 0;
    if (!is_array($workHistoryArr)) return 0;
    foreach ($workHistoryArr as $row) $total += computeYearsFromString($row['years'] ?? '');
    return $total;
}

function getEducationLevelOptions() {
    return [
        'Elementary Graduate',
        'Secondary Level (Attended High School)',
        'Secondary Graduate (Junior High School / Old Curriculum)',
        'Senior High School Graduate (K-12 Curriculum)',
        'Technical-Vocational / TESDA Graduate',
        'Tertiary Level (College Undergraduate)',
        'Tertiary Graduate (Bachelors Degree)',
    ];
}

function getSpecializationOptions() {
    return [
        'Cleaning and Housekeeping (General)',
        'Laundry and Clothing Care',
        'Cooking and Food Service',
        'Childcare and Maternity (Yaya)',
        'Elderly and Special Care (Caregiver)',
        'Pet and Outdoor Maintenance',
    ];
}

// ===================== If POST, handle submission =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName   = sanitizeInput($_POST['first_name'] ?? '');
    $middleName  = sanitizeInput($_POST['middle_name'] ?? '');
    $lastName    = sanitizeInput($_POST['last_name'] ?? '');
    $suffix      = sanitizeInput($_POST['suffix'] ?? '');
    $email       = sanitizeInput($_POST['email'] ?? '');
    $dateOfBirth = sanitizeInput($_POST['date_of_birth'] ?? '');
    $address     = sanitizeInput($_POST['address'] ?? '');

    $phonePrimary   = sanitizeInput($_POST['phone_number'] ?? '');
    $phoneAlternate = sanitizeInput($_POST['alt_phone_number'] ?? '');
    $phoneNumber    = $phonePrimary !== '' ? $phonePrimary : ($phoneAlternate !== '' ? $phoneAlternate : '');

    $employmentType = sanitizeInput($_POST['employment_type'] ?? '');
    $educationLevel = sanitizeInput($_POST['education_level'] ?? '');

    $postedBuId  = (int)($_POST['business_unit_id'] ?? 0);
    $selectedBuId = in_array($postedBuId, $validBuIds, true) ? $postedBuId : $selectedBuId;

    $eduRaw = $_POST['edu'] ?? [];
    $educationalAttainment = [
        'elementary' => [
            'school' => sanitizeInput($eduRaw['elementary']['school'] ?? ''),
            'year'   => sanitizeInput($eduRaw['elementary']['year'] ?? '')
        ],
        'highschool' => [
            'school' => sanitizeInput($eduRaw['highschool']['school'] ?? ''),
            'year'   => sanitizeInput($eduRaw['highschool']['year'] ?? '')
        ],
        'senior_high' => [
            'school' => sanitizeInput($eduRaw['senior_high']['school'] ?? ''),
            'strand' => sanitizeInput($eduRaw['senior_high']['strand'] ?? ''),
            'year'   => sanitizeInput($eduRaw['senior_high']['year'] ?? '')
        ],
        'college' => [
            'school' => sanitizeInput($eduRaw['college']['school'] ?? ''),
            'course' => sanitizeInput($eduRaw['college']['course'] ?? ''),
            'year'   => sanitizeInput($eduRaw['college']['year'] ?? '')
        ],
    ];
    $educationalAttainmentJson = json_encode($educationalAttainment, JSON_UNESCAPED_UNICODE);

    $workHistoryRaw = $_POST['work_history'] ?? [];
    $workHistoryArr = [];
    if (is_array($workHistoryRaw)) {
        foreach ($workHistoryRaw as $row) {
            $company  = sanitizeInput($row['company']  ?? '');
            $years    = sanitizeInput($row['years']    ?? '');
            $role     = sanitizeInput($row['role']     ?? '');
            $location = sanitizeInput($row['location'] ?? '');
            if ($company || $years || $role || $location) {
                $workHistoryArr[] = compact('company','years','role','location');
            }
        }
    }
    $workHistoryJson = json_encode($workHistoryArr, JSON_UNESCAPED_UNICODE);

    $specializations = $_POST['specialization_skills'] ?? [];
    if (!is_array($specializations)) $specializations = [];
    $specializations = array_values(array_filter(array_map('sanitizeInput', $specializations)));
    $specializationSkillsJson = json_encode($specializations, JSON_UNESCAPED_UNICODE);

    $preferredCities = $_POST['preferred_cities'] ?? [];
    if (!is_array($preferredCities)) $preferredCities = [];
    $preferredCities = array_values(array_filter(array_map('sanitizeInput', $preferredCities)));
    $preferredLocationJson = json_encode($preferredCities, JSON_UNESCAPED_UNICODE);

    $languages = $_POST['languages'] ?? [];
    if (!is_array($languages)) $languages = [];
    $languages = array_values(array_filter(array_map('sanitizeInput', $languages)));
    $languagesJson = json_encode($languages, JSON_UNESCAPED_UNICODE);

    $status = 'pending';

    if ($firstName === '')   $errors[] = 'First name is required.';
    if ($lastName === '')    $errors[] = 'Last name is required.';
    if ($dateOfBirth === '') $errors[] = 'Date of birth is required.';
    if ($address === '')     $errors[] = 'Address is required.';

    if (!in_array($employmentType, ['Full Time','Part Time'], true)) {
        $errors[] = 'Please choose an employment type.';
    }
    if (!in_array($educationLevel, getEducationLevelOptions(), true)) {
        $errors[] = 'Please select a valid highest educational level.';
    }

    if (!in_array($selectedBuId, $validBuIds, true)) {
        $errors[] = 'Invalid or unauthorized business unit.';
    }

    $phoneRegex = '/^09\d{9}$/';
    if ($phonePrimary !== '' && !preg_match($phoneRegex, $phonePrimary)) {
        $errors[] = 'Primary phone must be 11 digits and start with 09.';
    }
    if ($phoneAlternate !== '' && !preg_match($phoneRegex, $phoneAlternate)) {
        $errors[] = 'Alternate phone must be 11 digits and start with 09.';
    }

    $picturePath = null;
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
        $picturePath = uploadFile($_FILES['picture'], 'applicants');
        if (!$picturePath) $errors[] = 'Failed to upload picture.';
    }

    $yearsExperience = computeTotalYears($workHistoryArr);

    if (empty($errors)) {
        $data = [
            'first_name'             => $firstName,
            'middle_name'            => $middleName,
            'last_name'              => $lastName,
            'suffix'                 => $suffix,
            'phone_number'           => $phoneNumber,
            'alt_phone_number'       => $phoneAlternate,
            'email'                  => $email,
            'date_of_birth'          => $dateOfBirth,
            'address'                => $address,
            'educational_attainment' => $educationalAttainmentJson,
            'work_history'           => $workHistoryJson,
            'preferred_location'     => $preferredLocationJson,
            'languages'              => $languagesJson,
            'specialization_skills'  => $specializationSkillsJson,
            'employment_type'        => $employmentType,
            'education_level'        => $educationLevel,
            'years_experience'       => $yearsExperience,
            'picture'                => $picturePath,
            'status'                 => $status,
            'created_by'             => (int)($_SESSION['admin_id'] ?? 0),
            'business_unit_id'       => $selectedBuId
        ];

        $applicantId = $applicant->create($data);

        if ($applicantId) {
            $docTypes = $applicant->getDocumentTypesForBu($selectedBuId);

            if (empty($docTypes)) {
                $docTypes = [
                    ['code' => 'brgy_clearance',   'label' => 'Barangay Clearance',  'id' => null],
                    ['code' => 'birth_certificate','label' => 'Birth Certificate',   'id' => null],
                    ['code' => 'sss',              'label' => 'SSS',                 'id' => null],
                    ['code' => 'pagibig',          'label' => 'PAG-IBIG',            'id' => null],
                    ['code' => 'nbi',              'label' => 'NBI Clearance',       'id' => null],
                    ['code' => 'police_clearance', 'label' => 'Police Clearance',    'id' => null],
                    ['code' => 'tin_id',           'label' => 'TIN ID',              'id' => null],
                    ['code' => 'passport',         'label' => 'Passport',            'id' => null],
                ];
            }

            foreach ($docTypes as $dt) {
                $code = (string)$dt['code'];
                $docTypeId = isset($dt['id']) ? (int)$dt['id'] : null;
                if (isset($_FILES[$code]) && $_FILES[$code]['error'] === UPLOAD_ERR_OK) {
                    $docPath = uploadFile($_FILES[$code], 'documents');
                    if ($docPath) {
                        $applicant->addDocument($applicantId, $selectedBuId, $code, $docPath, $docTypeId);
                    }
                }
            }

            if (isset($_FILES['videos']) && is_array($_FILES['videos']['name']) && count($_FILES['videos']['name']) > 0) {
                $videoFile = [
                    'name'     => $_FILES['videos']['name'][0],
                    'type'     => $_FILES['videos']['type'][0],
                    'tmp_name' => $_FILES['videos']['tmp_name'][0],
                    'error'    => $_FILES['videos']['error'][0],
                    'size'     => $_FILES['videos']['size'][0],
                ];
                if ($videoFile['error'] === UPLOAD_ERR_OK) {
                    $vidPath = uploadFile($videoFile, 'video');
                    if ($vidPath) {
                        $videoData = [
                            'video_url'             => $vidPath,
                            'video_provider'        => 'file',
                            'video_type'            => 'file',
                            'video_title'           => isset($_POST['video_title']) ? sanitizeInput($_POST['video_title']) : pathinfo($videoFile['name'], PATHINFO_FILENAME),
                            'video_thumbnail_url'   => null,
                            'video_duration_seconds'=> null,
                        ];
                        $applicant->updateVideoFields($applicantId, $videoData, $selectedBuId);
                    }
                }
            }

            if (isset($auth) && method_exists($auth, 'logActivity')) {
                $auth->logActivity((int)$_SESSION['admin_id'], 'Add Applicant', "Added new applicant: {$firstName} {$lastName} (BU ID {$selectedBuId})");
            }
            setFlashMessage('success', 'Applicant added successfully!');
            redirect('applicants.php');
            exit;
        } else {
            $errors[] = 'Failed to create applicant.';
        }
    }
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-semibold">Add New Applicant</h4>
    <a href="applicants.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to List
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Step indicator -->
<div class="mb-3">
    <div class="d-flex align-items-center gap-3 mb-2">
        <div class="d-flex align-items-center gap-3">
            <span class="badge rounded-pill bg-primary px-3 py-2">1</span>
            <span class="badge rounded-pill bg-secondary px-3 py-2">2</span>
            <span class="badge rounded-pill bg-secondary px-3 py-2">3</span>
            <span class="badge rounded-pill bg-secondary px-3 py-2">4</span>
            <span class="badge rounded-pill bg-secondary px-3 py-2">5</span>
        </div>
        <div class="vr"></div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary" id="stepBadge">Step 1 of 5</span>
            <strong id="stepTitle">Personal Information</strong>
        </div>
    </div>
    <div class="progress" style="height: 8px;">
        <div class="progress-bar" id="stepProgress" role="progressbar" style="width: 0%;" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
</div>

<form method="POST" enctype="multipart/form-data" id="applicantForm" novalidate>
    <!-- STEP 1: Personal Info -->
    <div class="card mb-4 wizard-step" data-step="1">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold">Personal Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Profile Picture</label>
                    <div class="border rounded p-2 text-center">
                        <img id="picturePreview" src="" alt="Preview" class="img-fluid rounded" style="max-height:220px; display:none;">
                        <div id="picturePlaceholder" class="text-muted small" style="min-height: 220px; display:flex; align-items:center; justify-content:center;">
                            No image selected
                        </div>
                    </div>
                    <input type="file" class="form-control mt-2" name="picture" id="pictureInput" accept="image/*">
                </div>

                <div class="col-md-9">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">SMC Countries <span class="text-danger">*</span></label>

                            <!-- Select + Quick-select buttons -->
                            <div class="d-flex flex-wrap gap-2">
                                <select class="form-select" name="business_unit_id" id="businessUnitSelect" required style="max-width: 520px;">
                                    <option value="">Select Branch...</option>
                                    <?php foreach ($businessUnitsForDropdown as $bu): ?>
                                        <option value="<?php echo (int)$bu['id']; ?>" <?php echo ((int)$bu['id'] === (int)$selectedBuId ? 'selected' : ''); ?>>
                                            <?php echo htmlspecialchars($bu['label'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <!-- Quick-select buttons -->
                                <div class="btn-group" role="group" aria-label="Quick select countries">
                                    <button
                                        type="button"
                                        id="btnPickTurkey"
                                        class="btn btn-outline-primary"
                                        <?php echo $turkeyBuId ? '' : 'disabled'; ?>
                                        data-bu="<?php echo $turkeyBuId ? (int)$turkeyBuId : ''; ?>">
                                        <i class="bi bi-geo-alt me-1"></i>Turkey
                                    </button>
                                    <button
                                        type="button"
                                        id="btnPickBahrain"
                                        class="btn btn-outline-primary"
                                        <?php echo $bahrainBuId ? '' : 'disabled'; ?>
                                        data-bu="<?php echo $bahrainBuId ? (int)$bahrainBuId : ''; ?>">
                                        <i class="bi bi-geo-alt me-1"></i>Bahrain
                                    </button>
                                </div>
                            </div>

                            <div class="form-text">Select Country</div>
                            <?php if (!$turkeyBuId || !$bahrainBuId): ?>
                                <div class="form-text text-muted">
                                    <?php
                                    $missing = [];
                                    if (!$turkeyBuId)  $missing[] = 'Turkey';
                                    if (!$bahrainBuId) $missing[] = 'Bahrain';
                                    if (!empty($missing)) {
                                        echo 'Note: No BU found for ' . htmlspecialchars(implode(' and ', $missing)) . ' in your SMC setup.';
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="middle_name" value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Suffix</label>
                            <input type="text" class="form-control" name="suffix" placeholder="Jr., Sr., III, etc." value="<?php echo htmlspecialchars($_POST['suffix'] ?? ''); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Employment Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="employment_type" required>
                                <option value="">Select...</option>
                                <option value="Full Time" <?php echo (($_POST['employment_type'] ?? '') === 'Full Time' ? 'selected' : ''); ?>>Full Time</option>
                                <option value="Part Time" <?php echo (($_POST['employment_type'] ?? '') === 'Part Time' ? 'selected' : ''); ?>>Part Time</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone Number (Primary)</label>
                            <input type="tel" class="form-control" name="phone_number" placeholder="09123456789" minlength="11" maxlength="11" pattern="^09\d{9}$" value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
                            <div class="form-text">Optional. Must be 11 digits starting with 09.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone Number (Alternate)</label>
                            <input type="tel" class="form-control" name="alt_phone_number" placeholder="09123456789" minlength="11" maxlength="11" pattern="^09\d{9}$" value="<?php echo htmlspecialchars($_POST['alt_phone_number'] ?? ''); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="date_of_birth" required value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="address" rows="2" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-primary next-step">Next</button>
        </div>
    </div>

    <!-- STEP 2: Education -->
    <div class="card mb-4 wizard-step d-none" data-step="2">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold">Education</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-2">
                <div class="col-md-6">
                    <label class="form-label">Highest Educational Level <span class="text-danger">*</span></label>
                    <select class="form-select" name="education_level" required>
                        <option value="">Select...</option>
                        <?php
                        $opts = getEducationLevelOptions();
                        $sel = $_POST['education_level'] ?? '';
                        foreach ($opts as $o) {
                            echo '<option value="' . htmlspecialchars($o) . '" ' . ($sel === $o ? 'selected' : '') . '>' . htmlspecialchars($o) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12"><h6 class="fw-semibold mb-2">Elementary</h6></div>
                <div class="col-md-8">
                    <label class="form-label">School Name</label>
                    <input type="text" class="form-control" name="edu[elementary][school]" value="<?php echo htmlspecialchars($_POST['edu']['elementary']['school'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Year</label>
                    <input type="text" class="form-control" name="edu[elementary][year]" placeholder="e.g., 2008" value="<?php echo htmlspecialchars($_POST['edu']['elementary']['year'] ?? ''); ?>">
                </div>

                <div class="col-12 mt-2"><h6 class="fw-semibold mb-2">High School</h6></div>
                <div class="col-md-8">
                    <label class="form-label">School Name</label>
                    <input type="text" class="form-control" name="edu[highschool][school]" value="<?php echo htmlspecialchars($_POST['edu']['highschool']['school'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Year</label>
                    <input type="text" class="form-control" name="edu[highschool][year]" placeholder="e.g., 2012" value="<?php echo htmlspecialchars($_POST['edu']['highschool']['year'] ?? ''); ?>">
                </div>

                <div class="col-12 mt-2"><h6 class="fw-semibold mb-2">Senior High School</h6></div>
                <div class="col-md-6">
                    <label class="form-label">School Name</label>
                    <input type="text" class="form-control" name="edu[senior_high][school]" value="<?php echo htmlspecialchars($_POST['edu']['senior_high']['school'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Strand</label>
                    <input type="text" class="form-control" name="edu[senior_high][strand]" placeholder="e.g., STEM" value="<?php echo htmlspecialchars($_POST['edu']['senior_high']['strand'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Year</label>
                    <input type="text" class="form-control" name="edu[senior_high][year]" placeholder="e.g., 2015" value="<?php echo htmlspecialchars($_POST['edu']['senior_high']['year'] ?? ''); ?>">
                </div>

                <div class="col-12 mt-2"><h6 class="fw-semibold mb-2">College</h6></div>
                <div class="col-md-6">
                    <label class="form-label">School Name</label>
                    <input type="text" class="form-control" name="edu[college][school]" value="<?php echo htmlspecialchars($_POST['edu']['college']['school'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Course</label>
                    <input type="text" class="form-control" name="edu[college][course]" value="<?php echo htmlspecialchars($_POST['edu']['college']['course'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Year</label>
                    <input type="text" class="form-control" name="edu[college][year]" placeholder="e.g., 2019" value="<?php echo htmlspecialchars($_POST['edu']['college']['year'] ?? ''); ?>">
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between">
            <button type="button" class="btn btn-secondary prev-step">Previous</button>
            <button type="button" class="btn btn-primary next-step">Next</button>
        </div>
    </div>

    <!-- STEP 3: Work History & Specialization -->
    <div class="card mb-4 wizard-step d-none" data-step="3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold">Work History</h5>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addWorkRow">
                <i class="bi bi-plus-lg me-1"></i>Add
            </button>
        </div>
        <div class="card-body">
            <div id="workHistoryRows" class="vstack gap-3"></div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <label class="form-label">Years of Experience (Auto)</label>
                    <input type="text" id="yearsTotal" class="form-control" value="0 years" readonly>
                </div>
            </div>

            <hr class="my-4">

            <div>
                <label class="form-label fw-semibold">Specialization Skills</label>
                <div class="row">
                    <?php
                    $specOpts = getSpecializationOptions();
                    $postedSpecs = $_POST['specialization_skills'] ?? [];
                    foreach ($specOpts as $i => $label):
                        $id = 'spec_' . $i;
                        $checked = in_array($label, $postedSpecs ?? [], true) ? 'checked' : '';
                    ?>
                        <div class="col-md-6">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="<?php echo $id; ?>" name="specialization_skills[]" value="<?php echo htmlspecialchars($label); ?>" <?php echo $checked; ?>>
                                <label class="form-check-label" for="<?php echo $id; ?>"><?php echo htmlspecialchars($label); ?></label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between">
            <button type="button" class="btn btn-secondary prev-step">Previous</button>
            <button type="button" class="btn btn-primary next-step">Next</button>
        </div>
    </div>

    <!-- STEP 4: Preferences -->
    <div class="card mb-4 wizard-step d-none" data-step="4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold">Preferences</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label">Preferred Cities</label>
                    <div class="d-flex gap-2 mb-2">
                        <input type="text" id="cityInput" class="form-control" placeholder="Type a city and press Enter">
                        <button type="button" id="addCityBtn" class="btn btn-outline-primary"><i class="bi bi-plus-lg"></i></button>
                    </div>
                    <div id="cityTags" class="d-flex flex-wrap gap-2"></div>
                </div>

                <div class="col-md-5">
                    <label class="form-label">Languages</label>
                    <div class="d-flex gap-2">
                        <input type="text" id="langInput" class="form-control" placeholder="e.g., English, Arabic, Turkish">
                        <button type="button" id="addLangBtn" class="btn btn-outline-primary"><i class="bi bi-plus-lg"></i></button>
                    </div>
                    <div id="langTags" class="d-flex flex-wrap gap-2 mt-2"></div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between">
            <button type="button" class="btn btn-secondary prev-step">Previous</button>
            <button type="button" class="btn btn-primary next-step">Next</button>
        </div>
    </div>

    <!-- STEP 5: Documents -->
    <div class="card mb-4 wizard-step d-none" data-step="5">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold">Required Documents</h5>
        </div>
        <div class="card-body">
            <?php
            $docTypesForUi = $applicant->getDocumentTypesForBu($selectedBuId);
            if (empty($docTypesForUi)) {
                $docTypesForUi = [
                    ['code' => 'brgy_clearance',   'label' => 'Barangay Clearance'],
                    ['code' => 'birth_certificate','label' => 'Birth Certificate'],
                    ['code' => 'sss',              'label' => 'SSS'],
                    ['code' => 'pagibig',          'label' => 'PAG-IBIG'],
                    ['code' => 'nbi',              'label' => 'NBI Clearance'],
                    ['code' => 'police_clearance', 'label' => 'Police Clearance'],
                    ['code' => 'tin_id',           'label' => 'TIN ID'],
                    ['code' => 'passport',         'label' => 'Passport'],
                ];
            }
            ?>
            <div class="row g-3">
                <?php foreach ($docTypesForUi as $dt): ?>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo htmlspecialchars($dt['label'] ?? $dt['code']); ?></label>
                        <input type="file" class="form-control" name="<?php echo htmlspecialchars($dt['code']); ?>" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                <?php endforeach; ?>

                <div class="col-12 mt-3">
                    <label class="form-label">Video (optional)</label>
                    <div id="videoDropArea" class="border rounded p-3 text-center" style="min-height:110px; cursor:pointer; background:#f8f9fa;">
                        <div id="videoDropPlaceholder" class="text-muted">
                            <i class="bi bi-cloud-upload me-1"></i>Drag and drop video here or click to browse
                        </div>
                        <video id="videoPreview" class="mt-2 rounded" style="max-width:100%; max-height:300px; display:none;"></video>
                    </div>
                    <input type="file" class="form-control d-none" id="videoInput" name="videos[]" accept="video/*">
                    <div id="videoMetaInfo" class="mt-2" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <span id="videoFileName" class="text-truncate fw-semibold"></span>
                            <button type="button" id="clearVideoBtn" class="btn btn-sm btn-outline-danger">Clear</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between">
            <button type="button" class="btn btn-secondary prev-step">Previous</button>
            <button type="submit" class="btn btn-primary">Save Applicant</button>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const steps = Array.from(document.querySelectorAll('.wizard-step'));
    const nextBtns = document.querySelectorAll('.next-step');
    const prevBtns = document.querySelectorAll('.prev-step');
    const stepBadge = document.getElementById('stepBadge');
    const stepTitle = document.getElementById('stepTitle');
    const stepProgress = document.getElementById('stepProgress');

    const titles = {
        1: 'Personal Information',
        2: 'Education',
        3: 'Work History and Specialization',
        4: 'Preferences',
        5: 'Required Documents'
    };

    let current = 1;
    function renderStep() {
        steps.forEach(function(s) { s.classList.add('d-none'); });
        const active = document.querySelector('.wizard-step[data-step="' + current + '"]');
        if (active) active.classList.remove('d-none');
        if (stepBadge) stepBadge.textContent = 'Step ' + current + ' of 5';
        if (stepTitle) stepTitle.textContent = titles[current] || '';
        if (stepProgress) stepProgress.style.width = ((current - 1) / 4 * 100) + '%';
    }
    nextBtns.forEach(function(btn) { btn.addEventListener('click', function() { if (current < 5) { current++; renderStep(); } }); });
    prevBtns.forEach(function(btn) { btn.addEventListener('click', function() { if (current > 1) { current--; renderStep(); } }); });
    renderStep();

    // Picture preview
    var pictureInput = document.getElementById('pictureInput');
    var picturePreview = document.getElementById('picturePreview');
    var picturePlaceholder = document.getElementById('picturePlaceholder');
    if (pictureInput) {
        pictureInput.addEventListener('change', function() {
            if (pictureInput.files && pictureInput.files[0]) {
                picturePreview.src = URL.createObjectURL(pictureInput.files[0]);
                picturePreview.style.display = 'block';
                picturePlaceholder.style.display = 'none';
            }
        });
    }

    // Video chooser
    var drop = document.getElementById('videoDropArea');
    var input = document.getElementById('videoInput');
    var preview = document.getElementById('videoPreview');
    var meta = document.getElementById('videoMetaInfo');
    var fname = document.getElementById('videoFileName');
    var placeholder = document.getElementById('videoDropPlaceholder');
    var clearBtn = document.getElementById('clearVideoBtn');
    if (drop && input) {
        drop.addEventListener('click', function() { input.click(); });
        input.addEventListener('change', function() {
            if (input.files && input.files[0]) {
                fname.textContent = input.files[0].name;
                meta.style.display = 'block';
                preview.src = URL.createObjectURL(input.files[0]);
                preview.style.display = 'block';
                placeholder.style.display = 'none';
            }
        });
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                input.value = '';
                meta.style.display = 'none';
                preview.src = '';
                preview.style.display = 'none';
                placeholder.style.display = 'block';
            });
        }
        drop.addEventListener('dragover', function(e) { e.preventDefault(); drop.classList.add('bg-light'); });
        drop.addEventListener('dragleave', function(e) { e.preventDefault(); drop.classList.remove('bg-light'); });
        drop.addEventListener('drop', function(e) {
            e.preventDefault();
            drop.classList.remove('bg-light');
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change'));
            }
        });
    }

    // Quick-select handlers (Turkey/Bahrain)
    const buSelect = document.getElementById('businessUnitSelect');

    function quickPick(btnId) {
        const btn = document.getElementById(btnId);
        if (!btn || !buSelect) return;
        btn.addEventListener('click', function() {
            const buId = btn.getAttribute('data-bu');
            if (!buId) return;
            buSelect.value = buId;
            // dispatch change in case future logic depends on it
            buSelect.dispatchEvent(new Event('change'));
            // flash highlight
            buSelect.classList.add('border', 'border-success', 'shadow');
            setTimeout(() => {
                buSelect.classList.remove('border-success', 'shadow');
            }, 900);
        });
    }

    quickPick('btnPickTurkey');
    quickPick('btnPickBahrain');
});
</script>