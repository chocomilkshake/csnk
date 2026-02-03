<?php
  // Active page for navbar highlighting
  $page = 'applicants';
?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CSNK Manpower Agency – Kasambahay Applicants</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../resources/css/app.css" rel="stylesheet">
</head>
<body>

  <!-- Reusable Navbar -->
  <?php include __DIR__ . '/navbar.php'; ?>

  <main class="container py-4">
    <div class="mb-3"><h1 class="h3 mb-0">Kasambahay Applicants</h1></div>

    <!-- Search Bar -->
    <section class="search-wrap mb-4">
      <form id="searchForm" role="search">
        <div class="search-pill d-flex align-items-stretch bg-white border border-2 rounded-pill shadow-sm p-2 p-sm-3 w-100">
          <div class="flex-grow-1 d-flex align-items-center px-2 px-sm-3 min-w-0">
            <i class="bi bi-hospital-fill text-danger me-2 fs-5"></i>
            <input
              type="text"
              class="form-control border-0 bg-transparent ps-3 min-w-0"
              id="q"
              name="q"
              placeholder="Search for Name, Specialization, Experience..."
              aria-label="Search keywords"
            >
          </div>

          <div class="d-none d-lg-block align-self-stretch border-start mx-2"></div>

          <div class="flex-grow-1 d-flex align-items-center px-2 px-sm-3 min-w-0">
            <i class="bi bi-geo-alt-fill text-danger me-2 fs-5"></i>
            <input
              type="text"
              class="form-control border-0 bg-transparent ps-3 min-w-3"
              id="location"
              name="location"
              placeholder="Location"
              aria-label="Location"
            >
          </div>

          <div class="d-flex align-items-center ps-2 ps-sm-3">
            <button class="btn btn-danger rounded-pill px-3 px-sm-4" type="submit">
              <i class="bi bi-search me-1"></i>
              <span class="fw-medium">Search</span>
            </button>
          </div>
        </div>
      </form>
    </section>

    <div class="row">
      <!-- Sidebar Filters -->
      <aside class="col-12 col-lg-3 mb-4">
        <button class="btn btn-outline-secondary d-lg-none mb-2" data-bs-toggle="offcanvas" data-bs-target="#filtersCanvas">
          <i class="bi bi-funnel"></i> Filters
        </button>
        <div class="offcanvas-lg offcanvas-start" tabindex="-1" id="filtersCanvas" aria-labelledby="filtersCanvasLabel">
          <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="filtersCanvasLabel">Filter</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
          </div>
          <div class="offcanvas-body">
            <form id="filtersForm" class="d-grid gap-3">
              <div>
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="fw-semibold">Specialization</span>
                  <a href="#" class="small" id="clearSpecs">Clear</a>
                </div>
                <div class="vstack gap-1">
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="specializations[]" value="Cleaning and Housekeeping (General)" id="spec-kas"><label class="form-check-label" for="spec-kas">Cleaning and Housekeeping (General)</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="specializations[]" value="Laundry and Clothing Care" id="spec-nan"><label class="form-check-label" for="spec-nan">Laundry and Clothing Care</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="specializations[]" value="Cooking and Food Service" id="spec-cook"><label class="form-check-label" for="spec-cook">Cooking and Food Service</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="specializations[]" value="Childcare and Maternity (Yaya)" id="spec-elder"><label class="form-check-label" for="spec-elder">Childcare and Maternity (Yaya)</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="specializations[]" value="Elderly and Special Care (Caregiver)" id="spec-all"><label class="form-check-label" for="spec-all">Elderly and Special Care (Caregiver)</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="specializations[]" value="Pet and Outdoor Maintenance" id="spec-driver"><label class="form-check-label" for="spec-driver">Pet and Outdoor Maintenance</label></div>
                </div>
              </div>

              <div>
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="fw-semibold">Availability</span>
                  <a href="#" class="small" id="clearAvail">Clear</a>
                </div>
                <div class="vstack gap-1">
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="availability[]" value="Full-time" id="avail-ft"><label class="form-check-label" for="avail-ft">Full-time</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="availability[]" value="Part-time" id="avail-pt"><label class="form-check-label" for="avail-pt">Part-time</label></div>
                </div>
              </div>

              <div>
                <label class="fw-semibold mb-1" for="exp-range">Experience (min years)</label>
                <input type="range" class="form-range" id="exp-range" name="min_experience" min="0" max="20" step="1" value="0" oninput="this.nextElementSibling.value=this.value">
                <output class="small">0</output>
              </div>

              <div>
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="fw-semibold">Languages</span>
                  <a href="#" class="small" id="clearLangs">Clear</a>
                </div>
                <div class="vstack gap-1">
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="languages[]" value="Filipino" id="lang-fil"><label class="form-check-label" for="lang-fil">Filipino</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="languages[]" value="English" id="lang-eng"><label class="form-check-label" for="lang-eng">English</label></div>
                </div>
              </div>

              <div>
                <label class="fw-semibold mb-1" for="sort">Sort by</label>
                <select class="form-select" id="sort" name="sort">
                  <option value="availability_asc">Availability: Earliest</option>
                  <option value="experience_desc">Experience: Highest</option>
                  <option value="newest">Newest</option>
                </select>
              </div>

              <div class="d-grid gap-1">
                <button class="btn btn-primary" id="applyFilters">Apply Filters</button>
                <a class="btn btn-outline-secondary" href="#" id="resetFilters">Reset</a>
              </div>
            </form>
          </div>
        </div>
      </aside>

      <!-- Results -->
      <section class="col-12 col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="small text-secondary" id="resultsCount">Showing 0 of 0 applicants</div>
          <div class="d-none d-lg-block"><i class="bi bi-grid"></i></div>
        </div>

        <div id="cardsGrid" class="row g-3" aria-live="polite"></div>

        <!-- Pagination -->
        <nav class="mt-4" aria-label="Applicants pagination">
          <ul class="pagination" id="pagination"></ul>
        </nav>
      </section>
    </div>
  </main>

  <!-- ✅ Applicant Profile Modal (modernized) -->
  <div class="modal fade" id="applicantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content shadow border-0 rounded-4">
        <div class="modal-header border-0">
          <h1 class="modal-title fs-5 fw-bold">Applicant Profile</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- Body IDs must match app.js -->
        <div class="modal-body pt-0">
          <!-- Header strip -->
          <div class="p-3 mb-3 d-flex gap-3 align-items-center rounded-3" style="border:1px solid #e5e7eb;background:#fff;">
            <div id="avatar" style="width:64px;height:64px;border-radius:50%;display:grid;place-items:center;background:#f3f4f6;border:1px solid #ffffff;color:#991b1b;font-weight:900;"></div>
            <div class="flex-grow-1">
              <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="fw-bold fs-6" id="name">Applicant Name</span>
                <span class="badge rounded-pill" id="primaryRole" style="background:#ffffff;color:#991b1b;border:1px solid #000000;">Role</span>
                <span class="badge rounded-pill" id="yoeBadge" style="background:#ffffff;color:#991b1b;border:1px solid #000000;">0 yrs</span>
              </div>
              <!-- City & Region only -->
              <div class="mt-1 text-muted" id="availabilityLine">City, Region</div>
            </div>
          </div>

          <!-- Specialization -->
          <div class="p-3 mb-3 rounded-3" style="border:1px solid #e5e7eb;background:#fff;">
            <h6 class="mb-2 text-uppercase" style="color:#991b1b;letter-spacing:.6px;font-weight:800;font-size:.82rem;">Specialization</h6>
            <div class="d-flex flex-wrap gap-2" id="chipsContainer"></div>
          </div>

          <!-- Basic Information -->
          <div class="p-3 rounded-3" style="border:1px solid #e5e7eb;background:#fff;">
            <h6 class="mb-3 text-uppercase" style="color:#991b1b;letter-spacing:.6px;font-weight:800;font-size:.82rem;">Basic Information</h6>
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <div class="p-3 rounded-3" style="border:1px solid #e5e7eb;">
                  <div class="text-uppercase small fw-bold" style="letter-spacing:.5px;color:#b91c1c;">Location — City</div>
                  <div class="fw-semibold" id="cityValue">—</div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="p-3 rounded-3" style="border:1px solid #e5e7eb;">
                  <div class="text-uppercase small fw-bold" style="letter-spacing:.5px;color:#b91c1c;">Preferred Locations (All)</div>
                  <div class="fw-semibold" id="prefLocValue">—</div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="p-3 rounded-3" style="border:1px solid #e5e7eb;">
                  <div class="text-uppercase small fw-bold" style="letter-spacing:.5px;color:#b91c1c;">Years of Experience</div>
                  <div class="fw-semibold" id="yoeValue">—</div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="p-3 rounded-3" style="border:1px solid #e5e7eb;">
                  <div class="text-uppercase small fw-bold" style="letter-spacing:.5px;color:#b91c1c;">Employment Type</div>
                  <div class="fw-semibold" id="employmentValue">—</div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="p-3 rounded-3" style="border:1px solid #e5e7eb;">
                  <div class="text-uppercase small fw-bold" style="letter-spacing:.5px;color:#b91c1c;">Languages</div>
                  <div class="fw-semibold" id="langValue">—</div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="p-3 rounded-3" style="border:1px solid #e5e7eb;">
                  <div class="text-uppercase small fw-bold" style="letter-spacing:.5px;color:#b91c1c;">Educational Attainment</div>
                  <div class="fw-semibold" id="eduValue">—</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer gets added by app.js (Proceed to Booking) -->
      </div>
    </div>
  </div>

  <style>
    /* chips used inside Profile modal */
    #chipsContainer .chip{
      user-select:none; pointer-events:none;
      display:inline-block; padding:.4rem .7rem; border-radius:999px;
      font-weight:700; font-size:.82rem; color:#000000;
      background:#fafafa; border:1px solid #f0f0f0; position:relative;
    }
    #chipsContainer .chip::after{content:""; position:absolute; inset:-2px; border-radius:999px; box-shadow:0 0 0 2px #000000; opacity:.06;}
  </style>

  <!-- Booking Modal (5-step wizard) -->
  <div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content shadow">

        <div class="modal-header px-4 pt-4 border-0">
          <h5 class="modal-title fw-bold">Booking Appointment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- Stepper -->
        <div class="px-4">
          <div class="d-flex gap-2 pb-3 stepper">
            <div class="step active" data-step="1"><span class="dot">1</span><div>Choose Services</div></div>
            <div class="step" data-step="2"><span class="dot">2</span><div>Interview Method</div></div>
            <div class="step" data-step="3"><span class="dot">3</span><div>Date &amp; Time</div></div>
            <div class="step" data-step="4"><span class="dot">4</span><div>Client Information</div></div>
            <div class="step" data-step="5"><span class="dot">5</span><div>Review &amp; Submit</div></div>
          </div>
        </div>

        <div class="modal-body px-4 pb-4">
          <!-- Applicant header inside modal -->
          <div class="border rounded-3 p-3 mb-3">
            <div class="d-flex align-items-center gap-3">
              <div id="bkAvatar" class="rounded-circle d-grid place-items-center fw-bolder"
                   style="width:56px;height:56px;background:#f3f4f6;color:#991b1b;border:1px solid #fff;display:grid;place-items:center;">
              </div>
              <div class="small">
                <div class="fw-bold" id="bkName">Applicant Name</div>
                <div class="text-muted" id="bkMeta">—</div>
              </div>
            </div>
          </div>

          <!-- STEP 1: Services (rephrased) -->
          <div class="step-pane" data-step-pane="1">
            <div class="panel mb-3">
              <h6 class="mb-2">Tell us what work you need from this applicant</h6>
              <p class="text-muted small mb-3">Select all applicable services. This helps us prepare the interview and match expectations.</p>
              <div class="row g-2">
                <div class="col-12 col-md-6 col-lg-4"><button type="button" class="btn w-100 oval-tag" data-service="Cleaning & Housekeeping (General)">Cleaning & Housekeeping (General)</button></div>
                <div class="col-12 col-md-6 col-lg-4"><button type="button" class="btn w-100 oval-tag" data-service="Laundry & Clothing Care">Laundry & Clothing Care</button></div>
                <div class="col-12 col-md-6 col-lg-4"><button type="button" class="btn w-100 oval-tag" data-service="Cooking & Food Service">Cooking & Food Service</button></div>
                <div class="col-12 col-md-6 col-lg-4"><button type="button" class="btn w-100 oval-tag" data-service="Childcare & Maternity (Yaya)">Childcare & Maternity (Yaya)</button></div>
                <div class="col-12 col-md-6 col-lg-4"><button type="button" class="btn w-100 oval-tag" data-service="Elderly & Special Care (Caregiver)">Elderly & Special Care (Caregiver)</button></div>
                <div class="col-12 col-md-6 col-lg-4"><button type="button" class="btn w-100 oval-tag" data-service="Pet & Outdoor Maintenance">Pet & Outdoor Maintenance</button></div>
              </div>
            </div>
          </div>

          <!-- STEP 2: Appointment Type (House Visit -> Office Visit) -->
          <div class="step-pane d-none" data-step-pane="2">
            <div class="panel mb-3">
              <h6 class="mb-3">How would you like to interview the applicant?</h6>
              <div class="row g-2">
                <div class="col-6 col-md-3">
                  <input type="radio" class="btn-check" name="apptType" id="typeVideo" value="Video Call" autocomplete="off">
                  <label class="btn btn-outline-dark w-100 py-3" for="typeVideo"><i class="bi bi-camera-video me-1"></i> Video Call</label>
                </div>
                <div class="col-6 col-md-3">
                  <input type="radio" class="btn-check" name="apptType" id="typeAudio" value="Audio Call" autocomplete="off">
                  <label class="btn btn-outline-dark w-100 py-3" for="typeAudio"><i class="bi bi-telephone me-1"></i> Audio Call</label>
                </div>
                <div class="col-6 col-md-3">
                  <input type="radio" class="btn-check" name="apptType" id="typeChat" value="Chat" autocomplete="off">
                  <label class="btn btn-outline-dark w-100 py-3" for="typeChat"><i class="bi bi-chat-dots me-1"></i> Chat</label>
                </div>
                <div class="col-6 col-md-3">
                  <input type="radio" class="btn-check" name="apptType" id="typeVisit" value="Office Visit" autocomplete="off">
                  <label class="btn btn-outline-dark w-100 py-3" for="typeVisit"><i class="bi bi-building me-1"></i> Office Visit</label>
                </div>
              </div>
              <div class="small text-muted mt-2">
                Office Visit means an in-person interview at <strong>CREMPCO Main Office</strong>.
              </div>
            </div>
          </div>

          <!-- STEP 3: Date & Time (with Office Visit constraints) -->
          <div class="step-pane d-none" data-step-pane="3">
            <div class="panel mb-3">
              <h6 class="mb-2">Choose Date & Time</h6>
              <p class="text-muted small mb-3">If you select <strong>Office Visit</strong>, available slots are <strong>Mon–Sat, 8:00 AM – 5:00 PM</strong>.</p>
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <label class="form-label">Date</label>
                  <input type="date" class="form-control" id="bkDate">
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Time</label>
                  <input type="time" class="form-control" id="bkTime">
                </div>
              </div>
            </div>
          </div>

          <!-- STEP 4: Client Information (improved labels) -->
          <div class="step-pane d-none" data-step-pane="4">
            <div class="panel mb-3">
              <h6 class="mb-3">Your Details</h6>
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">First Name</label>
                  <input type="text" class="form-control" id="bkFirstName">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Middle Name</label>
                  <input type="text" class="form-control" id="bkMiddleName">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Last Name</label>
                  <input type="text" class="form-control" id="bkLastName">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Active Phone Number</label>
                  <input type="tel" class="form-control" id="bkPhone" placeholder="+63 9XXXXXXXXX">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Active Email</label>
                  <input type="email" class="form-control" id="bkEmail" placeholder="name@email.com">
                </div>
                <div class="col-12">
                  <label class="form-label">Address</label>
                  <input type="text" class="form-control" id="bkAddress" placeholder="House No., Street, Barangay, City, Province/Region">
                </div>
              </div>
            </div>
          </div>

        

          <!-- STEP 5: Review & Submit (no QR) -->
          <div class="step-pane d-none" data-step-pane="5">
            <div class="panel mb-3">
              <h6 class="mb-3">Review Your Request</h6>
              <div class="border rounded p-3 mb-3">
                <div id="bkSummary" class="small"></div>
              </div>
              <div class="d-grid">
                <button class="btn btn-brand text-white" id="bkSubmit">Submit Request</button>
                
              </div>
            </div>
          </div>

          <!-- Navigation -->
          <div class="d-flex justify-content-between pt-2">
            <button type="button" class="btn btn-outline-dark" id="bkBack">Back</button>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-brand text-white" id="bkNext">Next</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Theme (red/black) + small helpers -->
  <style>
    :root{ --brand-red:#c40000; --brand-black:#111; }
    .btn-brand{ background:var(--brand-red); border-color:var(--brand-red); }
    .btn-brand:hover{ filter:brightness(.9); }
    .btn-darkbrand{ background:var(--brand-black); border-color:var(--brand-black); }

    .panel{ border:1px solid #edf0f3; border-radius:.75rem; padding:1rem; background:#fff; }

    .stepper .step{ flex:1; text-align:center; font-size:.875rem; color:#6c757d; }
    .stepper .step .dot{ width:28px;height:28px;line-height:28px;border-radius:50%;
      display:inline-block;background:#dee2e6;color:#495057;font-weight:600;margin-bottom:.35rem;}
    .stepper .step.active .dot,.stepper .step.completed .dot{ background:var(--brand-red); color:#fff; }
    .stepper .step.active,.stepper .step.completed{ color:var(--brand-red); font-weight:600; }

    .oval-tag{ border-radius:999px; border:2px solid var(--brand-red); color:var(--brand-red);
      background:#fff; transition:.15s ease-in-out; }
    .oval-tag.active,.oval-tag:hover{ background:var(--brand-red); color:#fff; }
  </style>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../resources/js/app.js"></script>
  <script src="../resources/js/applicant.js"></script>

  <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>