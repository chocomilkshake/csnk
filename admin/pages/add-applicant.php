<?php
// add-applicant.php

$pageTitle = 'Add New Applicant';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

$applicant = new Applicant($database);
$errors = [];

/**
 * ---- Helpers to compute Years of Experience from Work History ----
 * Accepts either a range "2019-2021" / "2019–2021" / "2019 — 2021"
 * or a numeric "2" / "2 years" etc. Returns non-negative int years.
 */
function computeYearsFromString($text) {
    $t = trim((string)$text);
    if ($t === '') return 0;
    $norm = str_replace(['–', '—', 'to'], ['-', '-', '-'], strtolower($t));
    if (preg_match_all('/\d{1,4}/', $norm, $m) && count($m[0]) >= 2) {
        $a = (int)$m[0][0];
        $b = (int)$m[0][1];
        if ($a > 0 && $b > 0) {
            $diff = $b - $a;
            return max(0, $diff);
        }
    }
    if (preg_match('/\d+/', $norm, $n)) {
        return max(0, (int)$n[0]);
    }
    return 0;
}
function computeTotalYears($workHistoryArr) {
    $total = 0;
    if (!is_array($workHistoryArr)) return 0;
    foreach ($workHistoryArr as $row) {
        $yearsText = $row['years'] ?? '';
        $total += computeYearsFromString($yearsText);
    }
    return $total;
}

// Highest Educational Level options
function getEducationLevelOptions() {
    return [
        'Elementary Graduate',
        'Secondary Level (Attended High School)',
        'Secondary Graduate (Junior High School / Old Curriculum)',
        'Senior High School Graduate (K-12 Curriculum)',
        'Technical-Vocational / TESDA Graduate',
        'Tertiary Level (College Undergraduate)',
        'Tertiary Graduate (Bachelor’s Degree)',
    ];
}

// Step-3 Specialization Skills options
function getSpecializationOptions() {
    return [
        'Cleaning and Housekeeping (General)',
        'Laundry and Clothing Care',
        'Cooking and Food Service',
        'Childcare and Maternity (Yaya)',
        'Elderly nad Special Care (Caregiver)',
        'Pet and Outdoor Maintenance',
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ===================== Get & sanitize POST =====================
    // Basic strings
    $firstName   = sanitizeInput($_POST['first_name'] ?? '');
    $middleName  = sanitizeInput($_POST['middle_name'] ?? '');
    $lastName    = sanitizeInput($_POST['last_name'] ?? '');
    $suffix      = sanitizeInput($_POST['suffix'] ?? '');
    $email       = sanitizeInput($_POST['email'] ?? '');
    $dateOfBirth = sanitizeInput($_POST['date_of_birth'] ?? '');
    $address     = sanitizeInput($_POST['address'] ?? '');

    // Phones
    $phonePrimary   = sanitizeInput($_POST['phone_number'] ?? '');
    $phoneAlternate = sanitizeInput($_POST['alt_phone_number'] ?? '');
    // Final phone (if admin provided only alternate, we still store primary column with a number)
    $phoneNumber = $phonePrimary !== '' ? $phonePrimary : ($phoneAlternate !== '' ? $phoneAlternate : '');

    // Employment type moved to Step 1
    $employmentType  = sanitizeInput($_POST['employment_type'] ?? ''); // Full Time | Part Time

    // Education level (Step 2)
    $educationLevel  = sanitizeInput($_POST['education_level'] ?? '');

    // Educational attainment (structured incl. Senior High)
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
    $educationalAttainmentJson = json_encode($educationalAttainment);

    // Work history (array of rows) — includes 'location'
    $workHistoryRaw = $_POST['work_history'] ?? [];
    $workHistoryArr = [];
    if (is_array($workHistoryRaw)) {
        foreach ($workHistoryRaw as $row) {
            $company  = sanitizeInput($row['company'] ?? '');
            $years    = sanitizeInput($row['years'] ?? '');
            $role     = sanitizeInput($row['role'] ?? '');
            $location = sanitizeInput($row['location'] ?? '');
            if ($company || $years || $role || $location) {
                $workHistoryArr[] = [
                    'company'  => $company,
                    'years'    => $years,
                    'role'     => $role,
                    'location' => $location,
                ];
            }
        }
    }
    $workHistoryJson = json_encode($workHistoryArr);

    // Specialization skills (checkboxes)
    $specializations = $_POST['specialization_skills'] ?? [];
    if (!is_array($specializations)) $specializations = [];
    $specializations = array_values(array_filter(array_map('sanitizeInput', $specializations)));
    $specializationSkillsJson = json_encode($specializations);

    // Preferred cities (tags)
    $preferredCities = $_POST['preferred_cities'] ?? [];
    if (!is_array($preferredCities)) $preferredCities = [];
    $preferredCities = array_values(array_filter(array_map('sanitizeInput', $preferredCities)));
    $preferredLocationJson = json_encode($preferredCities);

    // Languages (tags)
    $languages = $_POST['languages'] ?? [];
    if (!is_array($languages)) $languages = [];
    $languages = array_values(array_filter(array_map('sanitizeInput', $languages)));
    $languagesJson = json_encode($languages);

    // Keep server-side default status
    $status = 'pending';

    // ===================== Validation =====================
    if (empty($firstName))   $errors[] = 'First name is required.';
    if (empty($lastName))    $errors[] = 'Last name is required.';
    if (empty($dateOfBirth)) $errors[] = 'Date of birth is required.';
    if (empty($address))     $errors[] = 'Address is required.';

    if (!in_array($employmentType, ['Full Time','Part Time'], true)) {
        $errors[] = 'Please choose an employment type.';
    }
    if (!in_array($educationLevel, getEducationLevelOptions(), true)) {
        $errors[] = 'Please select a valid highest educational level.';
    }

    // Phones: both optional; validate format if provided
    $phoneRegex = '/^09\d{9}$/'; // PH mobile pattern
    if ($phonePrimary !== '' && !preg_match($phoneRegex, $phonePrimary)) {
        $errors[] = 'Primary phone must be 11 digits and start with 09 (e.g., 09123456789).';
    }
    if ($phoneAlternate !== '' && !preg_match($phoneRegex, $phoneAlternate)) {
        $errors[] = 'Alternate phone must be 11 digits and start with 09 (e.g., 09123456789).';
    }

    // ===================== Picture upload =====================
    $picturePath = null;
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
        $picturePath = uploadFile($_FILES['picture'], 'applicants');
        if (!$picturePath) {
            $errors[] = 'Failed to upload picture.';
        }
    }

    // Auto-compute years of experience from work history
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
            'specialization_skills'  => $specializationSkillsJson, // NEW

            'employment_type'        => $employmentType,
            'education_level'        => $educationLevel,
            'years_experience'       => $yearsExperience,

            'picture'                => $picturePath,
            'status'                 => $status,
            'created_by'             => $_SESSION['admin_id']
        ];

        $applicantId = $applicant->create($data);

        if ($applicantId) {
            // Document uploads
            $documentTypes = [
                'brgy_clearance', 'birth_certificate', 'sss',
                'pagibig', 'nbi', 'police_clearance',
                'tin_id', 'passport'
            ];

            foreach ($documentTypes as $docType) {
                if (isset($_FILES[$docType]) && $_FILES[$docType]['error'] === UPLOAD_ERR_OK) {
                    $docPath = uploadFile($_FILES[$docType], 'documents');
                    if ($docPath) {
                        $applicant->addDocument($applicantId, $docType, $docPath);
                    }
                }
            }

            $auth->logActivity($_SESSION['admin_id'], 'Add Applicant', "Added new applicant: $firstName $lastName");
            setFlashMessage('success', 'Applicant added successfully!');
            redirect('applicants.php');
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
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- ===== Step indicator with numbers ===== -->
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
    <!-- ==================== STEP 1: Personal Info (with Employment Type) ==================== -->
    <div class="card mb-4 wizard-step" data-step="1">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold">Personal Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <!-- Picture with live preview -->
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
                        <div class="col-md-6">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name" required
                                   value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="middle_name"
                                   value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="last_name" required
                                   value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Suffix</label>
                            <input type="text" class="form-control" name="suffix" placeholder="Jr., Sr., III, etc."
                                   value="<?= htmlspecialchars($_POST['suffix'] ?? '') ?>">
                        </div>

                        <!-- Employment Type moved here -->
                        <div class="col-md-6">
                            <label class="form-label">Employment Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="employment_type" required>
                                <option value="">Select...</option>
                                <option value="Full Time"  <?= (($_POST['employment_type'] ?? '') === 'Full Time') ? 'selected' : '' ?>>Full Time</option>
                                <option value="Part Time"  <?= (($_POST['employment_type'] ?? '') === 'Part Time') ? 'selected' : '' ?>>Part Time</option>
                            </select>
                        </div>

                        <!-- Phones -->
                        <div class="col-md-6">
                            <label class="form-label">Phone Number (Primary)</label>
                            <input type="tel" class="form-control" name="phone_number"
                                   placeholder="sample (09123456789)"
                                   minlength="11" maxlength="11"
                                   pattern="^09\d{9}$"
                                   value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>">
                            <div class="form-text">Optional. If provided, must be 11 digits and start with 09.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone Number (Alternate)</label>
                            <input type="tel" class="form-control" name="alt_phone_number"
                                   placeholder="sample (09123456789)"
                                   minlength="11" maxlength="11"
                                   pattern="^09\d{9}$"
                                   value="<?= htmlspecialchars($_POST['alt_phone_number'] ?? '') ?>">
                            <div class="form-text">Optional backup number.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="date_of_birth" required
                                   value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="address" rows="2" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-primary next-step">Next</button>
        </div>
    </div>

    <!-- ==================== STEP 2: Education ==================== -->
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
                        foreach ($opts as $o):
                        ?>
                        <option value="<?= htmlspecialchars($o) ?>" <?= ($sel === $o ? 'selected' : '') ?>><?= htmlspecialchars($o) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12"><h6 class="fw-semibold mb-2">Elementary</h6></div>
                <div class="col-md-8">
                    <label class="form-label">School Name</label>
                    <input type="text" class="form-control" name="edu[elementary][school]"
                           value="<?= htmlspecialchars($_POST['edu']['elementary']['school'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Year</label>
                    <input type="text" class="form-control" name="edu[elementary][year]" placeholder="e.g., 2008"
                           value="<?= htmlspecialchars($_POST['edu']['elementary']['year'] ?? '') ?>">
                </div>

                <div class="col-12 mt-2"><h6 class="fw-semibold mb-2">High School</h6></div>
                <div class="col-md-8">
                    <label class="form-label">School Name</label>
                    <input type="text" class="form-control" name="edu[highschool][school]"
                           value="<?= htmlspecialchars($_POST['edu']['highschool']['school'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Year</label>
                    <input type="text" class="form-control" name="edu[highschool][year]" placeholder="e.g., 2012"
                           value="<?= htmlspecialchars($_POST['edu']['highschool']['year'] ?? '') ?>">
                </div>

                <div class="col-12 mt-2"><h6 class="fw-semibold mb-2">Senior High School</h6></div>
                <div class="col-md-6">
                    <label class="form-label">School Name</label>
                    <input type="text" class="form-control" name="edu[senior_high][school]"
                           value="<?= htmlspecialchars($_POST['edu']['senior_high']['school'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Strand</label>
                    <input type="text" class="form-control" name="edu[senior_high][strand]" placeholder="e.g., STEM, HUMSS, ABM"
                           value="<?= htmlspecialchars($_POST['edu']['senior_high']['strand'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Year</label>
                    <input type="text" class="form-control" name="edu[senior_high][year]" placeholder="e.g., 2015"
                           value="<?= htmlspecialchars($_POST['edu']['senior_high']['year'] ?? '') ?>">
                </div>

                <div class="col-12 mt-2"><h6 class="fw-semibold mb-2">College</h6></div>
                <div class="col-md-6">
                    <label class="form-label">School Name</label>
                    <input type="text" class="form-control" name="edu[college][school]"
                           value="<?= htmlspecialchars($_POST['edu']['college']['school'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Course</label>
                    <input type="text" class="form-control" name="edu[college][course]"
                           value="<?= htmlspecialchars($_POST['edu']['college']['course'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Year</label>
                    <input type="text" class="form-control" name="edu[college][year]" placeholder="e.g., 2019"
                           value="<?= htmlspecialchars($_POST['edu']['college']['year'] ?? '') ?>">
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between">
            <button type="button" class="btn btn-secondary prev-step">Previous</button>
            <button type="button" class="btn btn-primary next-step">Next</button>
        </div>
    </div>

    <!-- ==================== STEP 3: Work History & Specialization ==================== -->
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
                    <div class="form-text">Computed from the “Years” column (e.g., 2019–2021 = 2; “3 years” = 3).</div>
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
                            <input class="form-check-input" type="checkbox" id="<?= $id ?>" name="specialization_skills[]" value="<?= htmlspecialchars($label) ?>" <?= $checked ?>>
                            <label class="form-check-label" for="<?= $id ?>"><?= htmlspecialchars($label) ?></label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-text mt-2">Check all that apply.</div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between">
            <button type="button" class="btn btn-secondary prev-step">Previous</button>
            <button type="button" class="btn btn-primary next-step">Next</button>
        </div>
    </div>

    <!-- ==================== STEP 4: Preferences (Cities & Languages) ==================== -->
    <div class="card mb-4 wizard-step d-none" data-step="4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold">Preferences</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <!-- Preferred Cities (tags) -->
                <div class="col-md-7">
                    <label class="form-label">Preferred Cities <small class="text-muted">(press Enter to add each)</small></label>
                    <div class="d-flex gap-2 mb-2">
                        <input type="text" id="cityInput" class="form-control" placeholder="Type a city and press Enter">
                        <button type="button" id="addCityBtn" class="btn btn-outline-primary">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                    <div id="cityTags" class="d-flex flex-wrap gap-2"></div>
                    <div class="form-text">Press Enter or click + to add. Click × on a tag to remove.</div>
                </div>

                <!-- Languages (tags) -->
                <div class="col-md-5">
                    <label class="form-label">Languages <small class="text-muted">(press Enter to add each)</small></label>
                    <div class="d-flex gap-2">
                        <input type="text" id="langInput" class="form-control" placeholder="e.g., English, Filipino, Arabic">
                        <button type="button" id="addLangBtn" class="btn btn-outline-primary">
                            <i class="bi bi-plus-lg"></i>
                        </button>
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

    <!-- ==================== STEP 5: Documents & Submit ==================== -->
    <div class="card mb-4 wizard-step d-none" data-step="5">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold">Required Documents</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Barangay Clearance</label>
                    <input type="file" class="form-control" name="brgy_clearance" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Birth Certificate</label>
                    <input type="file" class="form-control" name="birth_certificate" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="col-md-6">
                    <label class="form-label">SSS</label>
                    <input type="file" class="form-control" name="sss" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Pag-IBIG</label>
                    <input type="file" class="form-control" name="pagibig" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="col-md-6">
                    <label class="form-label">NBI Clearance</label>
                    <input type="file" class="form-control" name="nbi" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Police Clearance</label>
                    <input type="file" class="form-control" name="police_clearance" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="col-md-6">
                    <label class="form-label">TIN ID</label>
                    <input type="file" class="form-control" name="tin_id" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Passport</label>
                    <input type="file" class="form-control" name="passport" accept=".pdf,.jpg,.jpeg,.png">
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between">
            <button type="button" class="btn btn-secondary prev-step">Previous</button>
            <div class="d-flex gap-2">
                <a href="applicants.php" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Save Applicant
                </button>
            </div>
        </div>
    </div>
</form>

<!-- ===== Minimal JS: Wizard, dynamic rows, tags, preview ===== -->
<script>
(function(){
  // Prevent ENTER from submitting the whole form (especially in tag inputs)
  const form = document.getElementById('applicantForm');
  form.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      const id = (e.target && e.target.id) ? e.target.id : '';
      if (id === 'cityInput' || id === 'langInput') {
        e.preventDefault(); // handled below to add tag
      } else {
        e.preventDefault(); // block submit on Enter anywhere in wizard
      }
    }
  });

  // ---------- Wizard ----------
  const steps = Array.from(document.querySelectorAll('.wizard-step'));
  const nextBtns = document.querySelectorAll('.next-step');
  const prevBtns = document.querySelectorAll('.prev-step');
  const progress = document.getElementById('stepProgress');
  const badge = document.getElementById('stepBadge');
  const title = document.getElementById('stepTitle');
  const stepPills = document.querySelectorAll('.badge.rounded-pill'); // top numeric badges
  const titles = [
    'Personal Information',
    'Education',
    'Work History',
    'Preferences',
    'Documents'
  ];
  let current = 0; // 0..4

  function updateTopPills(){
    stepPills.forEach((pill, idx) => {
      pill.classList.toggle('bg-primary', idx === current);
      pill.classList.toggle('bg-secondary', idx !== current);
    });
  }

  function updateProgressUI(){
    const stepNum = current + 1;
    badge.textContent = `Step ${stepNum} of ${steps.length}`;
    title.textContent = titles[current] || `Step ${stepNum}`;
    const pct = Math.round((stepNum-1)/(steps.length-1) * 100);
    progress.style.width = pct + '%';
    progress.setAttribute('aria-valuenow', pct);
    updateTopPills();
  }

  function showStep(idx){
    steps.forEach((el,i)=> el.classList.toggle('d-none', i !== idx));
    current = idx;
    updateProgressUI();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function validateCurrentStep(){
    const stepEl = steps[current];
    stepEl.classList.add('was-validated');
    const inputs = stepEl.querySelectorAll('input, select, textarea');
    for (const inp of inputs) {
      if (!inp.checkValidity()) return false;
    }
    return true;
  }

  nextBtns.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      if (!validateCurrentStep()) return;
      if (current < steps.length - 1) showStep(current + 1);
    });
  });

  prevBtns.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      if (current > 0) showStep(current - 1);
    });
  });

  showStep(0);

  // ---------- Profile Picture live preview ----------
  const pictureInput = document.getElementById('pictureInput');
  const picturePreview = document.getElementById('picturePreview');
  const picturePlaceholder = document.getElementById('picturePlaceholder');

  pictureInput.addEventListener('change', (e)=>{
    const file = e.target.files && e.target.files[0];
    if(!file){
      picturePreview.src = '';
      picturePreview.style.display = 'none';
      picturePlaceholder.style.display = 'flex';
      return;
    }
    const reader = new FileReader();
    reader.onload = (evt)=>{
      picturePreview.src = evt.target.result;
      picturePreview.style.display = 'block';
      picturePlaceholder.style.display = 'none';
    };
    reader.readAsDataURL(file);
  });

  // ---------- Work History dynamic rows ----------
  const container = document.getElementById('workHistoryRows');
  const addBtn = document.getElementById('addWorkRow');

  function escapeHtml(str){
    return String(str || '').replace(/[&<>"']/g, s => (
      { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[s]
    ));
  }

  function createRow(idx, values={company:'', years:'', role:'', location:''}) {
    const row = document.createElement('div');
    row.className = 'row g-2 align-items-end';
    row.innerHTML = `
      <div class="col-md-4">
        <label class="form-label">Company Name</label>
        <input type="text" class="form-control" name="work_history[${idx}][company]" value="${escapeHtml(values.company || '')}">
      </div>
      <div class="col-md-2">
        <label class="form-label">Years</label>
        <input type="text" class="form-control years-input" name="work_history[${idx}][years]" placeholder="e.g., 2019–2021 or 2 years" value="${escapeHtml(values.years || '')}">
      </div>
      <div class="col-md-3">
        <label class="form-label">Role</label>
        <input type="text" class="form-control" name="work_history[${idx}][role]" value="${escapeHtml(values.role || '')}">
      </div>
      <div class="col-md-2">
        <label class="form-label">Location</label>
        <input type="text" class="form-control" name="work_history[${idx}][location]" placeholder="e.g., Riyadh, KSA" value="${escapeHtml(values.location || '')}">
      </div>
      <div class="col-md-1 d-grid">
        <button type="button" class="btn btn-outline-danger removeRow" title="Remove">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
    `;
    container.appendChild(row);
  }

  function getNextIndex(){ return container.querySelectorAll('.row').length; }

  container.addEventListener('click', (e) => {
    if (e.target.closest('.removeRow')) {
      const row = e.target.closest('.row');
      row?.remove();
      updateYearsTotal();
    }
  });

  addBtn.addEventListener('click', () => {
    createRow(getNextIndex());
    attachYearsListener();
  });

  // Prefill from POST (if validation failed)
  const postedWork = <?= json_encode($_POST['work_history'] ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
  if (postedWork && Object.keys(postedWork).length) {
    Object.keys(postedWork).forEach((k,i)=>{
      createRow(i, postedWork[k]);
    });
  } else {
    createRow(0, {});
  }

  function parseYears(text){
    if(!text) return 0;
    const norm = String(text).toLowerCase().replace(/–|—|to/g, '-');
    const nums = norm.match(/\d{1,4}/g) || [];
    if(nums.length >= 2){
      const a = parseInt(nums[0], 10);
      const b = parseInt(nums[1], 10);
      const diff = b - a;
      return Math.max(0, isFinite(diff) ? diff : 0);
    }
    const one = norm.match(/\d+/);
    if(one){ return Math.max(0, parseInt(one[0], 10)); }
    return 0;
  }

  function updateYearsTotal(){
    let total = 0;
    document.querySelectorAll('.years-input').forEach(inp=>{
      total += parseYears(inp.value);
    });
    const out = document.getElementById('yearsTotal');
    out.value = total + (total === 1 ? ' year' : ' years');
  }

  function attachYearsListener(){
    document.querySelectorAll('.years-input').forEach(inp=>{
      inp.removeEventListener('input', onYearsChange);
      inp.addEventListener('input', onYearsChange);
    });
  }
  function onYearsChange(){ updateYearsTotal(); }

  attachYearsListener();
  updateYearsTotal();

  // ---------- Preferred Cities tags ----------
  const cityInput = document.getElementById('cityInput');
  const cityTags  = document.getElementById('cityTags');
  const addCityBtn= document.getElementById('addCityBtn');

  function addCityTag(name) {
    const value = name.trim();
    if (!value) return;
    const exists = Array.from(cityTags.querySelectorAll('input[name="preferred_cities[]"]'))
      .some(inp => inp.value.toLowerCase() === value.toLowerCase());
    if (exists) {
      cityInput.value = '';
      return;
    }
    const tag = document.createElement('span');
    tag.className = 'badge text-bg-primary d-inline-flex align-items-center';
    tag.innerHTML = `
      <span class="me-1">${escapeHtml(value)}</span>
      <button type="button" class="btn btn-sm btn-light btn-close p-2 ms-1" aria-label="Remove"></button>
      <input type="hidden" name="preferred_cities[]" value="${escapeHtml(value)}">
    `;
    cityTags.appendChild(tag);
    cityInput.value = '';
  }

  cityInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      addCityTag(cityInput.value);
    }
  });

  addCityBtn.addEventListener('click', () => {
    addCityTag(cityInput.value);
  });

  cityTags.addEventListener('click', (e) => {
    if (e.target.classList.contains('btn-close')) {
      e.target.closest('.badge')?.remove();
    }
  });

  // Prefill preferred cities from POST
  const postedCities = <?= json_encode($_POST['preferred_cities'] ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
  if (Array.isArray(postedCities)) {
    postedCities.forEach(c => addCityTag(c));
  }

  // ---------- Languages tags ----------
  const langInput = document.getElementById('langInput');
  const langTags  = document.getElementById('langTags');
  const addLangBtn= document.getElementById('addLangBtn');

  function addLangTag(name) {
    const value = name.trim();
    if (!value) return;
    const exists = Array.from(langTags.querySelectorAll('input[name="languages[]"]'))
      .some(inp => inp.value.toLowerCase() === value.toLowerCase());
    if (exists) {
      langInput.value = '';
      return;
    }
    const tag = document.createElement('span');
    tag.className = 'badge text-bg-secondary d-inline-flex align-items-center';
    tag.innerHTML = `
      <span class="me-1">${escapeHtml(value)}</span>
      <button type="button" class="btn btn-sm btn-light btn-close p-2 ms-1" aria-label="Remove"></button>
      <input type="hidden" name="languages[]" value="${escapeHtml(value)}">
    `;
    langTags.appendChild(tag);
    langInput.value = '';
  }

  langInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      addLangTag(langInput.value);
    }
  });

  addLangBtn.addEventListener('click', () => {
    addLangTag(langInput.value);
  });

  langTags.addEventListener('click', (e) => {
    if (e.target.classList.contains('btn-close')) {
      e.target.closest('.badge')?.remove();
    }
  });

  // Prefill languages from POST
  const postedLangs = <?= json_encode($_POST['languages'] ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
  if (Array.isArray(postedLangs)) {
    postedLangs.forEach(l => addLangTag(l));
  }
})();
</script>

<?php require_once '../includes/footer.php'; ?>