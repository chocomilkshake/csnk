<?php
$pageTitle = 'Edit Applicant';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

$applicant = new Applicant($database);
$errors = [];

if (!isset($_GET['id'])) {
    redirect('applicants.php');
}

$id = (int)$_GET['id'];
$applicantData = $applicant->getById($id);
if (!$applicantData) {
    setFlashMessage('error', 'Applicant not found.');
    redirect('applicants.php');
}

/**
 * ---- Helpers (same logic as add) ----
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
function getSpecializationOptions() {
    return [
        'Cleaning & Housekeeping (General)',
        'Laundry & Clothing Care',
        'Cooking & Food Service',
        'Childcare & Maternity (Yaya)',
        'Elderly & Special Care (Caregiver)',
        'Pet & Outdoor Maintenance',
    ];
}

/**
 * Decode existing JSONs for prefills
 */
$eduArr    = json_decode($applicantData['educational_attainment'] ?? '[]', true) ?: [];
$workArr   = json_decode($applicantData['work_history']           ?? '[]', true) ?: [];
$citiesArr = json_decode($applicantData['preferred_location']     ?? '[]', true) ?: [];
$langsArr  = json_decode($applicantData['languages']              ?? '[]', true) ?: [];
$skillsArr = json_decode($applicantData['specialization_skills']  ?? '[]', true) ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ------- Collect & sanitize -------
    $firstName       = sanitizeInput($_POST['first_name'] ?? '');
    $middleName      = sanitizeInput($_POST['middle_name'] ?? '');
    $lastName        = sanitizeInput($_POST['last_name'] ?? '');
    $suffix          = sanitizeInput($_POST['suffix'] ?? '');
    $phoneNumber     = sanitizeInput($_POST['phone_number'] ?? '');      // primary (required)
    $altPhoneNumber  = sanitizeInput($_POST['alt_phone_number'] ?? '');  // alternate (optional)
    $email           = sanitizeInput($_POST['email'] ?? '');
    $dateOfBirth     = sanitizeInput($_POST['date_of_birth'] ?? '');
    $address         = sanitizeInput($_POST['address'] ?? '');

    $employmentType  = sanitizeInput($_POST['employment_type'] ?? ''); // Full Time | Part Time
    $status          = sanitizeInput($_POST['status'] ?? $applicantData['status']); // keep existing if not posted

    // Highest Educational Level (required)
    $educationLevel  = sanitizeInput($_POST['education_level'] ?? '');

    // Educational attainment structured
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

    // Specialization skills (checkbox list)
    $specializations = $_POST['specialization_skills'] ?? [];
    if (!is_array($specializations)) $specializations = [];
    // keep only known options
    $known = getSpecializationOptions();
    $specializations = array_values(array_intersect($specializations, $known));
    $specializationJson = json_encode($specializations);

    // ------- Validation -------
    if (empty($firstName))   $errors[] = 'First name is required.';
    if (empty($lastName))    $errors[] = 'Last name is required.';
    if (empty($dateOfBirth)) $errors[] = 'Date of birth is required.';
    if (empty($address))     $errors[] = 'Address is required.';

    // Primary phone: required, PH mobile format
    if (empty($phoneNumber)) {
        $errors[] = 'Primary phone number is required.';
    } elseif (!preg_match('/^09\d{9}$/', $phoneNumber)) {
        $errors[] = 'Primary phone must be 11 digits and start with 09 (e.g., 09123456789).';
    }

    // Alternate phone: optional but must pass if provided
    if ($altPhoneNumber !== '' && !preg_match('/^09\d{9}$/', $altPhoneNumber)) {
        $errors[] = 'Alternate phone must be 11 digits and start with 09 (e.g., 09123456789).';
    }

    if (!in_array($employmentType, ['Full Time','Part Time'], true)) {
        $errors[] = 'Please choose an employment type.';
    }
    if (!in_array($educationLevel, getEducationLevelOptions(), true)) {
        $errors[] = 'Please select a valid highest educational level.';
    }

    // ------- Picture handling -------
    $picturePath = $applicantData['picture'];
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
        $newPicturePath = uploadFile($_FILES['picture'], 'applicants');
        if ($newPicturePath) {
            if ($picturePath) {
                deleteFile($picturePath);
            }
            $picturePath = $newPicturePath;
        } else {
            $errors[] = 'Failed to upload picture.';
        }
    }

    // ------- Auto-compute years of experience from work history -------
    $yearsExperience = computeTotalYears($workHistoryArr);

    if (empty($errors)) {
        $data = [
            'first_name'              => $firstName,
            'middle_name'             => $middleName,
            'last_name'               => $lastName,
            'suffix'                  => $suffix,
            'phone_number'            => $phoneNumber,
            'alt_phone_number'        => $altPhoneNumber,
            'email'                   => $email,
            'date_of_birth'           => $dateOfBirth,
            'address'                 => $address,

            'educational_attainment'  => $educationalAttainmentJson,
            'work_history'            => $workHistoryJson,
            'preferred_location'      => $preferredLocationJson,
            'languages'               => $languagesJson,
            'specialization_skills'   => $specializationJson,

            'picture'                 => $picturePath,
            'status'                  => $status,
            'employment_type'         => $employmentType,
            'education_level'         => $educationLevel,
            'years_experience'        => $yearsExperience,
        ];

        // Update main applicant
        $ok = $applicant->update($id, $data);

        if ($ok) {
            // Optional: upload/append new/updated documents (adds as new rows)
            $documentTypes = [
                'brgy_clearance', 'birth_certificate', 'sss',
                'pagibig', 'nbi', 'police_clearance',
                'tin_id', 'passport'
            ];
            foreach ($documentTypes as $docType) {
                if (isset($_FILES[$docType]) && $_FILES[$docType]['error'] === UPLOAD_ERR_OK) {
                    $docPath = uploadFile($_FILES[$docType], 'documents');
                    if ($docPath) {
                        $applicant->addDocument($id, $docType, $docPath);
                    }
                }
            }

            $auth->logActivity($_SESSION['admin_id'], 'Update Applicant', "Updated applicant ID: $id");
            setFlashMessage('success', 'Applicant updated successfully!');
            redirect('applicants.php');
        } else {
            $errors[] = 'Failed to update applicant.';
        }
    }

    // For re-render after validation error
    $eduArr    = $educationalAttainment;
    $workArr   = $workHistoryArr;
    $citiesArr = $preferredCities;
    $langsArr  = $languages;
    $skillsArr = $specializations;

} else {
    // Prefill basic fields into $_POST for convenience
    $_POST['first_name']        = $applicantData['first_name'];
    $_POST['middle_name']       = $applicantData['middle_name'];
    $_POST['last_name']         = $applicantData['last_name'];
    $_POST['suffix']            = $applicantData['suffix'];
    $_POST['phone_number']      = $applicantData['phone_number'];
    $_POST['alt_phone_number']  = $applicantData['alt_phone_number'] ?? '';
    $_POST['email']             = $applicantData['email'];
    $_POST['date_of_birth']     = $applicantData['date_of_birth'];
    $_POST['address']           = $applicantData['address'];
    $_POST['status']            = $applicantData['status'];
    $_POST['employment_type']   = $applicantData['employment_type'];
    $_POST['education_level']   = $applicantData['education_level'] ?? '';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-semibold">Edit Applicant</h4>
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

<form method="POST" enctype="multipart/form-data" id="editApplicantForm">
    <!-- ==================== Personal Information ==================== -->
    <div class="card mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold">Personal Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Profile Picture</label>
                    <div class="border rounded p-2 text-center">
                        <?php if (!empty($applicantData['picture'])): ?>
                            <img src="<?= htmlspecialchars(getFileUrl($applicantData['picture']), ENT_QUOTES, 'UTF-8') ?>"
                                 alt="Current"
                                 class="img-fluid rounded mb-2"
                                 style="max-height:220px; object-fit:cover;">
                        <?php else: ?>
                            <div class="text-muted small" style="min-height: 220px; display:flex; align-items:center; justify-content:center;">
                                No image uploaded
                            </div>
                        <?php endif; ?>
                    </div>
                    <input type="file" class="form-control mt-2" name="picture" accept="image/*">
                    <small class="text-muted">Leave empty to keep current picture</small>
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
                            <input type="text" class="form-control" name="suffix"
                                   value="<?= htmlspecialchars($_POST['suffix'] ?? '') ?>">
                        </div>

                        <!-- PHONE NUMBERS -->
                        <div class="col-md-6">
                            <label class="form-label">Primary Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" name="phone_number"
                                   placeholder="sample (09123456789)"
                                   minlength="11" maxlength="11" pattern="^09\d{9}$" required
                                   value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>">
                            <div class="form-text">Must be 11 digits and start with 09.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Alternate Phone Number</label>
                            <input type="tel" class="form-control" name="alt_phone_number"
                                   placeholder="sample (09123456789)"
                                   minlength="11" maxlength="11" pattern="^09\d{9}$"
                                   value="<?= htmlspecialchars($_POST['alt_phone_number'] ?? '') ?>">
                            <div class="form-text">Optional. If provided, must be 11 digits and start with 09.</div>
                        </div>
                        <!-- END PHONE NUMBERS -->

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

                        <div class="col-md-6">
                            <label class="form-label">Employment Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="employment_type" required>
                                <option value="">Select...</option>
                                <option value="Full Time"  <?= (($_POST['employment_type'] ?? '') === 'Full Time') ? 'selected' : '' ?>>Full Time</option>
                                <option value="Part Time"  <?= (($_POST['employment_type'] ?? '') === 'Part Time') ? 'selected' : '' ?>>Part Time</option>
                            </select>
                        </div>

                        <!-- Languages (tags) -->
                        <div class="col-md-12">
                            <label class="form-label">Languages <small class="text-muted">(press Enter to add each)</small></label>
                            <div class="d-flex gap-2">
                                <input type="text" id="langInput" class="form-control" placeholder="e.g., English, Filipino, Arabic">
                                <button type="button" id="addLangBtn" class="btn btn-outline-primary" type="button">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                            <div id="langTags" class="d-flex flex-wrap gap-2 mt-2"></div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="address" rows="2" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== Educational Attainment ==================== -->
    <div class="card mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold">Educational Attainment</h5>
        </div>
        <div class="card-body">
            <!-- Highest Educational Level -->
            <div class="row g-3 mb-2">
                <div class="col-md-6">
                    <label class="form-label">Highest Educational Level <span class="text-danger">*</span></label>
                    <select class="form-select" name="education_level" required>
                        <option value="">Select...</option>
                        <?php
                        $opts = getEducationLevelOptions();
                        $sel  = $_POST['education_level'] ?? '';
                        foreach ($opts as $o):
                        ?>
                        <option value="<?= htmlspecialchars($o) ?>" <?= ($sel === $o ? 'selected' : '') ?>><?= htmlspecialchars($o) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3">
                <!-- Elementary -->
                <div class="col-12"><h6 class="fw-semibold mb-2">Elementary</h6></div>
                <div class="col-md-8">
                    <label class="form-label">School Name</label>
                    <input type="text" class="form-control" name="edu[elementary][school]"
                           value="<?= htmlspecialchars($_POST['edu']['elementary']['school'] ?? ($eduArr['elementary']['school'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Year</label>
                    <input type="text" class="form-control" name="edu[elementary][year]" placeholder="e.g., 2008"
                           value="<?= htmlspecialchars($_POST['edu']['elementary']['year'] ?? ($eduArr['elementary']['year'] ?? '')) ?>">
                </div>

                <!-- High School -->
                <div class="col-12 mt-2"><h6 class="fw-semibold mb-2">High School</h6></div>
                <div class="col-md-8">
                    <label class="form-label">School Name</label>
                    <input type="text" class="form-control" name="edu[highschool][school]"
                           value="<?= htmlspecialchars($_POST['edu']['highschool']['school'] ?? ($eduArr['highschool']['school'] ?? '')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Year</label>
                    <input type="text" class="form-control" name="edu[highschool][year]" placeholder="e.g., 2012"
                           value="<?= htmlspecialchars($_POST['edu']['highschool']['year'] ?? ($eduArr['highschool']['year'] ?? '')) ?>">
                </div>

                <!-- Senior High -->
                <div class="col-12 mt-2"><h6 class="fw-semibold mb-2">Senior High School</h6></div>
                <div class="col-md-6">
                    <label class="form-label">School Name</label>
                    <input type="text" class="form-control" name="edu[senior_high][school]"
                           value="<?= htmlspecialchars($_POST['edu']['senior_high']['school'] ?? ($eduArr['senior_high']['school'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Strand</label>
                    <input type="text" class="form-control" name="edu[senior_high][strand]" placeholder="e.g., STEM, HUMSS, ABM"
                           value="<?= htmlspecialchars($_POST['edu']['senior_high']['strand'] ?? ($eduArr['senior_high']['strand'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Year</label>
                    <input type="text" class="form-control" name="edu[senior_high][year]" placeholder="e.g., 2015"
                           value="<?= htmlspecialchars($_POST['edu']['senior_high']['year'] ?? ($eduArr['senior_high']['year'] ?? '')) ?>">
                </div>

                <!-- College -->
                <div class="col-12 mt-2"><h6 class="fw-semibold mb-2">College</h6></div>
                <div class="col-md-6">
                    <label class="form-label">School Name</label>
                    <input type="text" class="form-control" name="edu[college][school]"
                           value="<?= htmlspecialchars($_POST['edu']['college']['school'] ?? ($eduArr['college']['school'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Course</label>
                    <input type="text" class="form-control" name="edu[college][course]"
                           value="<?= htmlspecialchars($_POST['edu']['college']['course'] ?? ($eduArr['college']['course'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Year</label>
                    <input type="text" class="form-control" name="edu[college][year]" placeholder="e.g., 2019"
                           value="<?= htmlspecialchars($_POST['edu']['college']['year'] ?? ($eduArr['college']['year'] ?? '')) ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== Work History & Specialization ==================== -->
    <div class="card mb-4">
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

            <h6 class="fw-semibold mb-2">Specialization Skills</h6>
            <div class="row">
                <?php
                $opts = getSpecializationOptions();
                foreach ($opts as $i => $opt):
                    $checked = in_array($opt, $skillsArr, true) ? 'checked' : '';
                ?>
                <div class="col-md-6 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="skill<?= $i ?>" name="specialization_skills[]" value="<?= htmlspecialchars($opt) ?>" <?= $checked ?>>
                        <label class="form-check-label" for="skill<?= $i ?>"><?= htmlspecialchars($opt) ?></label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="form-text mt-2">Tick all that apply.</div>
        </div>
    </div>

    <!-- ==================== Preferences & Status ==================== -->
    <div class="card mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold">Preferences & Status</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <!-- Preferred Cities (tags) -->
                <div class="col-md-8">
                    <label class="form-label">Preferred Cities <small class="text-muted">(press Enter to add each)</small></label>
                    <div class="d-flex gap-2 mb-2">
                        <input type="text" id="cityInput" class="form-control" placeholder="Type a city and press Enter">
                        <button type="button" id="addCityBtn" class="btn btn-outline-primary">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                    <div id="cityTags" class="d-flex flex-wrap gap-2"></div>
                </div>

                <!-- Status -->
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <?php
                        $st = $_POST['status'] ?? $applicantData['status'];
                        $statuses = ['pending' => 'Pending', 'on_process' => 'On Process', 'approved' => 'Approved'];
                        foreach ($statuses as $k => $v):
                        ?>
                            <option value="<?= $k ?>" <?= ($st === $k ? 'selected' : '') ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== Documents (edit/append) ==================== -->
    <div class="card mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold">Documents</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Upload a file to replace/append a document. New uploads will be added as records for this applicant.</p>
            <div class="row g-3">
                <?php
                $docTypes = [
                    'brgy_clearance'   => 'Barangay Clearance',
                    'birth_certificate'=> 'Birth Certificate',
                    'sss'              => 'SSS',
                    'pagibig'          => 'Pag-IBIG',
                    'nbi'              => 'NBI Clearance',
                    'police_clearance' => 'Police Clearance',
                    'tin_id'           => 'TIN ID',
                    'passport'         => 'Passport',
                ];
                foreach ($docTypes as $key => $label):
                ?>
                <div class="col-md-6">
                    <label class="form-label"><?= htmlspecialchars($label) ?></label>
                    <input type="file" class="form-control" name="<?= $key ?>" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-2"></i>Update Applicant
        </button>
        <a href="applicants.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<script>
(function(){
  // ---------- Utility ----------
  function escapeHtml(str){
    return String(str || '').replace(/[&<>"']/g, s => (
      { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[s]
    ));
  }

  // Prevent Enter key in tag inputs from submitting the form
  const form = document.getElementById('editApplicantForm');
  form.addEventListener('keydown', (e) => {
    const target = e.target;
    if ((target && (target.id === 'cityInput' || target.id === 'langInput')) && e.key === 'Enter') {
      e.preventDefault();
    }
  });

  // ---------- Work History dynamic rows ----------
  const container = document.getElementById('workHistoryRows');
  const addBtn = document.getElementById('addWorkRow');

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
      e.preventDefault();
      const row = e.target.closest('.row');
      row?.remove();
      updateYearsTotal();
    }
  });

  addBtn.addEventListener('click', (e) => {
    e.preventDefault();
    createRow(getNextIndex());
    attachYearsListener();
  });

  // Prefill work history (from POST or DB)
  const initialWork = <?= json_encode(
        (isset($_POST['work_history']) ? $_POST['work_history'] : $workArr),
        JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP
    ); ?> || [];
  if (Array.isArray(initialWork) || typeof initialWork === 'object') {
    const keys = Object.keys(initialWork);
    if (keys.length) {
      keys.forEach((k,i) => createRow(i, initialWork[k]));
    } else {
      createRow(0, {});
    }
  } else {
    createRow(0, {});
  }

  // Years auto-total
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
    document.querySelectorAll('.years-input').forEach(inp => {
      total += parseYears(inp.value);
    });
    const out = document.getElementById('yearsTotal');
    if (out) out.value = total + (total === 1 ? ' year' : ' years');
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
    const value = String(name || '').trim();
    if (!value) return;
    const exists = Array.from(cityTags.querySelectorAll('input[name="preferred_cities[]"]'))
      .some(inp => inp.value.toLowerCase() === value.toLowerCase());
    if (exists) { cityInput.value = ''; return; }
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
  cityInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); addCityTag(cityInput.value); }
  });
  addCityBtn?.addEventListener('click', (e) => { e.preventDefault(); addCityTag(cityInput.value); });
  cityTags?.addEventListener('click', (e) => {
    if (e.target.classList.contains('btn-close')) e.target.closest('.badge')?.remove();
  });
  // Prefill cities
  const initialCities = <?= json_encode($citiesArr, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?> || [];
  if (Array.isArray(initialCities)) { initialCities.forEach(c => addCityTag(c)); }

  // ---------- Languages tags ----------
  const langInput = document.getElementById('langInput');
  const langTags  = document.getElementById('langTags');
  const addLangBtn= document.getElementById('addLangBtn');
  function addLangTag(name) {
    const value = String(name || '').trim();
    if (!value) return;
    const exists = Array.from(langTags.querySelectorAll('input[name="languages[]"]'))
      .some(inp => inp.value.toLowerCase() === value.toLowerCase());
    if (exists) { langInput.value = ''; return; }
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
  langInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); addLangTag(langInput.value); }
  });
  addLangBtn?.addEventListener('click', (e) => { e.preventDefault(); addLangTag(langInput.value); });
  langTags?.addEventListener('click', (e) => {
    if (e.target.classList.contains('btn-close')) e.target.closest('.badge')?.remove();
  });
  // Prefill languages
  const initialLangs = <?= json_encode($langsArr, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?> || [];
  if (Array.isArray(initialLangs)) { initialLangs.forEach(l => addLangTag(l)); }
})();
</script>

<?php require_once '../includes/footer.php'; ?>