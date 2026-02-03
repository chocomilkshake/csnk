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
      <!-- Keep the form inside your existing .container (from <main class="container py-4">) -->
      <form id="searchForm" role="search">
        <!-- The pill itself stretches only within the current container width -->
        <div class="search-pill d-flex align-items-stretch bg-white border border-2 rounded-pill shadow-sm p-2 p-sm-3 w-100">

          <!-- Keyword -->
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

          <!-- Divider (desktop only) -->
          <div class="d-none d-lg-block align-self-stretch border-start mx-2"></div>

          <!-- Location -->
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

          <!-- Divider (desktop only) -->
          <div class="d-none d-lg-block align-self-stretch border-start mx-2"></div>

          <!-- Date -->
          <div class="flex-grow-1 d-flex align-items-center px-2 px-sm-3 min-w-0">
            <i class="bi bi-calendar2-event-fill text-danger me-2 fs-5"></i>
            <input
              type="date"
              class="form-control border-0 bg-transparent ps-3 min-w-0"
              id="available_by"
              name="available_by"
              aria-label="Date"
            >
          </div>

          <!-- Search button -->
          <div class="d-flex align-items-center ps-2 ps-sm-3">
            <button class="btn btn-danger rounded-pill px-3 px-sm-4 view-profile-btn" type="submit">
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
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="specializations[]" value="Elderly and Special Care (Caregiver)" id="spec-all"><label class="form-check-label" for="spec-all">Elderly nad Special Care (Caregiver)</label></div>
                  <div class="form-check"><input class="form-check-input" type="checkbox" name="specializations[]" value="Pet and Outdoor Maintenance" id="spec-driver"><label class="form-check-label" for="spec-driver">Pet and Outdoor Maintenance</div>
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

  <script>
    document.getElementById('year').textContent = new Date().getFullYear();
  </script>

  <!-- ✅ Applicant Profile Modal -->
  <div class="modal fade" id="applicantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <!-- header -->
        <div class="modal-header">
          <h1 class="modal-title fs-5">Applicant Profile</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- body IDs must match app.js -->
        <div class="modal-body">
          <div class="p-3 mb-3 d-flex gap-3 align-items-center" style="border:1px solid #e5e7eb;border-radius:12px;background:#fff;">
            <div id="avatar" style="width:64px;height:64px;border-radius:50%;display:grid;place-items:center;background:#f3f4f6;border:1px solid #ffffff;color:#991b1b;font-weight:900;"></div>
            <div>
              <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="fw-bold" id="name">Applicant Name</span>
                <span class="badge rounded-pill" id="primaryRole" style="background:#ffffff;color:#991b1b;border:1px solid #000000;">Role</span>
                <span class="badge rounded-pill" id="yoeBadge" style="background:#ffffff;color:#991b1b;border:1px solid #000000;">0 yrs</span>
              </div>
              <div class="mt-1" id="availabilityLine" style="color:#000000;">City, Region • Available from: <strong class="text-danger-emphasis">TBD</strong></div>
            </div>
          </div>

          <div class="p-3 mb-3" style="border:1px solid #e5e7eb;border-radius:12px;background:#fff;">
            <h6 class="mb-2" style="color:#991b1b;text-transform:uppercase;letter-spacing:.6px;font-weight:800;font-size:.82rem;">Specialization</h6>
            <div class="d-flex flex-wrap gap-2" id="chipsContainer"></div>
          </div>

          <div class="p-3" style="border:1px solid #e5e7eb;border-radius:12px;background:#fff;">
            <h6 class="mb-3" style="color:#991b1b;text-transform:uppercase;letter-spacing:.6px;font-weight:800;font-size:.82rem;">Basic Information</h6>
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <div class="p-2" style="border:1px solid #e5e7eb;border-radius:10px;">
                  <div class="text-uppercase small fw-bold" style="letter-spacing:.5px;color:#b91c1c;">Location — City</div>
                  <div class="fw-semibold" id="cityValue">—</div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="p-2" style="border:1px solid #e5e7eb;border-radius:10px;">
                  <div class="text-uppercase small fw-bold" style="letter-spacing:.5px;color:#b91c1c;">Years of Experience</div>
                  <div class="fw-semibold" id="yoeValue">—</div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="p-2" style="border:1px solid #e5e7eb;border-radius:10px;">
                  <div class="text-uppercase small fw-bold" style="letter-spacing:.5px;color:#b91c1c;">Employment Type</div>
                  <div class="fw-semibold" id="employmentValue">—</div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="p-2" style="border:1px solid #e5e7eb;border-radius:10px;">
                  <div class="text-uppercase small fw-bold" style="letter-spacing:.5px;color:#b91c1c;">Languages</div>
                  <div class="fw-semibold" id="langValue">—</div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>



  

  <style>
    /* chips */
    #chipsContainer .chip{
      user-select:none; pointer-events:none;
      display:inline-block; padding:.4rem .7rem; border-radius:999px;
      font-weight:700; font-size:.82rem; color:#000000;
      background:#fafafa; border:1px solid #f0f0f0; position:relative;
    }
    #chipsContainer .chip::after{content:""; position:absolute; inset:-2px; border-radius:999px; box-shadow:0 0 0 2px #000000;}
  </style>

  <!-- Booking Modal (5-step wizard) -->
  <div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content shadow">

        <div class="modal-header px-4 pt-4">
          <h5 class="modal-title fw-bold">Booking Appointment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <!-- Stepper -->
        <div class="px-4">
          <div class="d-flex gap-2 pb-3 stepper">
            <div class="step active" data-step="1"><span class="dot">1</span><div>Select Service</div></div>
            <div class="step" data-step="2"><span class="dot">2</span><div>Appointment Type</div></div>
            <div class="step" data-step="3"><span class="dot">3</span><div>Date &amp; Time</div></div>
            <div class="step" data-step="4"><span class="dot">4</span><div>Basic Information</div></div>
            <div class="step" data-step="5"><span class="dot">5</span><div>Confirmation</div></div>
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

          <!-- STEP 1: Services -->
          <div class="step-pane" data-step-pane="1">
            <div class="panel mb-3">
              <h6 class="mb-3">Services (check all that apply)</h6>
              <div class="row g-2">
                <div class="col-6 col-md-4"><button type="button" class="btn w-100 oval-tag" data-service="Child Care">Child Care</button></div>
                <div class="col-6 col-md-4"><button type="button" class="btn w-100 oval-tag" data-service="Senior Care">Senior Care</button></div>
                <div class="col-6 col-md-4"><button type="button" class="btn w-100 oval-tag" data-service="Pet Care">Pet Care</button></div>
                <div class="col-6 col-md-4"><button type="button" class="btn w-100 oval-tag" data-service="House Cleaning">House Cleaning</button></div>
                <div class="col-6 col-md-4"><button type="button" class="btn w-100 oval-tag" data-service="Cooking">Cooking</button></div>
                <div class="col-6 col-md-4"><button type="button" class="btn w-100 oval-tag" data-service="Laundry">Laundry</button></div>
              </div>
            </div>
          </div>

          <!-- STEP 2: Appointment Type -->
          <div class="step-pane d-none" data-step-pane="2">
            <div class="panel mb-3">
              <h6 class="mb-3">Select Appointment Type</h6>
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
                  <input type="radio" class="btn-check" name="apptType" id="typeVisit" value="House Visit" autocomplete="off">
                  <label class="btn btn-outline-dark w-100 py-3" for="typeVisit"><i class="bi bi-house-door me-1"></i> House Visit</label>
                </div>
              </div>
            </div>
          </div>

          <!-- STEP 3: Date & Time -->
          <div class="step-pane d-none" data-step-pane="3">
            <div class="panel mb-3">
              <h6 class="mb-3">Choose Date &amp; Time</h6>
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <label class="form-label">Date
                  <input type="date" class="form-control" id="bkDate">
                  </label>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Time
                  <input type="time" class="form-control" id="bkTime">
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- STEP 4: Basic Info -->
          <div class="step-pane d-none" data-step-pane="4">
            <div class="panel mb-3">
              <h6 class="mb-3">Basic Information</h6>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">First Name
                  <input type="text" class="form-control" id="bkFirstName">
                  </label>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Last Name
                  <input type="text" class="form-control" id="bkLastName">
                  </label>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Phone Number
                  <input type="tel" class="form-control" id="bkPhone">
                  </label>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Email Address
                  <input type="email" class="form-control" id="bkEmail">
                  </label>
                </div>
                <div class="col-12">
                  <label class="form-label">Address
                  <input type="text" class="form-control" id="bkAddress">
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- STEP 5: Confirmation -->
          <div class="step-pane d-none" data-step-pane="5">
            <div class="panel mb-3">
              <h6 class="mb-3">Booking Confirmed</h6>
              <div class="row g-3">
                <div class="col-lg-8">
                  <div class="border rounded p-3">
                    <h6 class="text-success mb-2"><i class="bi bi-check-circle me-1"></i> Success!</h6>
                    <div id="bkSummary" class="small"></div>
                  </div>
                </div>
                <div class="col-lg-4">
                  <div class="border rounded p-3 text-center">
                    <div class="fw-semibold mb-2">Booking QR</div>
                    <!-- Placeholder QR block (replace with real QR later) -->
                    <div id="bkQR" class="d-inline-block border rounded" style="width:140px;height:140px;background:#f8f9fa"></div>
                    <button class="btn btn-darkbrand w-100 mt-3 text-white" id="bkDownload">Download Receipt</button>
                  </div>
                </div>
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