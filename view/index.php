<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CSNK Manpower Agency</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

  <!-- Navbar -->
  <header class="bg-white border-bottom sticky-top">
    <div class="container py-3">
      <div class="text-center mb-3">
        <img src="../resources/img/csnklogo.png" alt="CSNK Manpower Agency" style="height:95px;">
      </div>

      <nav class="d-flex justify-content-center">
        <div class="bg-danger rounded-pill px-2 py-2 d-inline-flex gap-1 shadow-sm">
          <a class="btn btn-danger rounded-pill active" href="#home">Home</a>
          <a class="btn btn-danger rounded-pill" href="applicant.html">Applicants</a>
          <a class="btn btn-danger rounded-pill" href="#about">About</a>
          <a class="btn btn-danger rounded-pill" href="#contact">Contact</a>
        </div>
      </nav>
    </div>
  </header>

  <!-- Hero -->
  <section id="home" class="py-5">
    <div class="container">
      <div class="p-4 p-lg-5 rounded-4 text-white shadow"
          style="background: linear-gradient(90deg, #dc3545, #111);">
        <div class="row align-items-center g-4">
          <div class="col-lg-6">
            <h1 class="display-6 fw-bold">Welcome to CSNK Manpower Agency</h1>
            <p class="lead mb-4">Your trusted partner in global workforce solutions. We connect talented professionals with premier opportunities worldwide.</p>

            <div class="d-flex gap-3 mb-3">
              <i class="fa-solid fa-circle-check fa-2x text-warning"></i>
              <div>
                <div class="fw-semibold">Professional Recruitment</div>
                <div class="small opacity-75">Expert matching of skills and opportunities</div>
              </div>
            </div>

            <div class="d-flex gap-3 mb-4">
              <i class="fa-solid fa-globe fa-2x text-warning"></i>
              <div>
                <div class="fw-semibold">Global Network</div>
                <div class="small opacity-75">Connections across multiple countries</div>
              </div>
            </div>

            <a href="#about" class="btn btn-light btn-lg fw-semibold">Learn More</a>
          </div>

          <div class="col-lg-6">
            <img class="img-fluid rounded-4 shadow"
                 src="https://images.pexels.com/photos/3184292/pexels-photo-3184292.jpeg?auto=compress&cs=tinysrgb&w=900"
                 alt="Office">
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Applicants Placeholder
  <section id="applicants" class="py-5 bg-white border-top border-bottom">
    <div class="container text-center">
      <h2 class="fw-bold mb-2">Applicants</h2>
      <p class="text-muted mb-0">Add your applicants page content or link here.</p>
    </div>
  </section> -->

  <!-- Why Choose -->
  <section id="about" class="py-5">
    <div class="container">
      <div class="text-center mb-5">
        <div class="text-muted">Why Choose</div>
        <h2 class="fw-bold"><span class="text-danger">CSNK</span></h2>
      </div>

      <div class="row g-4">
        <div class="col-md-4">
          <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center p-4">
              <i class="fa-solid fa-award fa-3x text-primary mb-3"></i>
              <div class="display-5 fw-bold text-danger mb-2">20,000+</div>
              <div class="text-danger fw-semibold small mb-2">ACCOMPLISHMENT</div>
              <h5 class="fw-bold">Efficient and Quality Service</h5>
              <p class="text-muted small mb-0">
                We provide dependable recruitment support with clear guidance, fast processing, and client focused service.
              </p>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center p-4">
              <i class="fa-solid fa-chart-line fa-3x text-success mb-3"></i>
              <div class="display-5 fw-bold text-danger mb-2">20,000+</div>
              <div class="text-danger fw-semibold small mb-2">SINCE 2020</div>
              <h5 class="fw-bold">Successful Deployments</h5>
              <p class="text-muted small mb-0">
                Thousands of placements across industries with proper documentation, step by step support, and real results.
              </p>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card h-100 shadow-sm border-0">
            <div class="card-body text-center p-4">
              <i class="fa-solid fa-shield-halved fa-3x text-danger mb-3"></i>
              <div class="display-5 fw-bold text-danger mb-2">100%</div>
              <div class="text-danger fw-semibold small mb-2">VERIFIED</div>
              <h5 class="fw-bold">Recruitment Safety</h5>
              <p class="text-muted small mb-0">
                Screening and verification are done to help ensure compliant processing, safety, and peace of mind.
              </p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- Testimonials -->
  <section class="py-5" style="background: #ffe3e6;">
    <div class="container">
      <div class="text-center mb-4">
        <div class="text-danger fw-semibold small">CLIENT TESTIMONIALS</div>
        <h2 class="fw-bold">Here is what our clients say</h2>
      </div>

      <div class="row justify-content-center">
        <div class="col-lg-8">

          <div id="testimonials" class="carousel slide">
            <div class="carousel-inner bg-white rounded-4 shadow p-4 p-md-5">

              <div class="carousel-item active">
                <div class="row align-items-center g-4">
                  <div class="col-md-4 text-center">
                    <img class="img-fluid rounded-3 shadow-sm"
                         src="../resources/img/testimonial1.jpg"
                         alt="Derek Ross">
                  </div>
                  <div class="col-md-8">
                    <h5 class="fw-bold mb-1">Derek Ross</h5>
                    <p class="text-muted mb-0">
                      I had a smooth experience. They were professional, responsive, and matched me with an opportunity that fit my goals.
                    </p>
                  </div>
                </div>
              </div>

              <div class="carousel-item">
                <div class="row align-items-center g-4">
                  <div class="col-md-4 text-center">
                    <img class="img-fluid rounded-3 shadow-sm"
                         src="https://images.pexels.com/photos/774909/pexels-photo-774909.jpeg?auto=compress&cs=tinysrgb&w=400"
                         alt="Maria Santos">
                  </div>
                  <div class="col-md-8">
                    <h5 class="fw-bold mb-1">Maria Santos</h5>
                    <p class="text-muted mb-0">
                      They guided me step by step and helped me complete my requirements. I recommend CSNK for overseas processing.
                    </p>
                  </div>
                </div>
              </div>

              <div class="carousel-item">
                <div class="row align-items-center g-4">
                  <div class="col-md-4 text-center">
                    <img class="img-fluid rounded-3 shadow-sm"
                         src="https://images.pexels.com/photos/1222271/pexels-photo-1222271.jpeg?auto=compress&cs=tinysrgb&w=400"
                         alt="John Smith">
                  </div>
                  <div class="col-md-8">
                    <h5 class="fw-bold mb-1">John Smith</h5>
                    <p class="text-muted mb-0">
                      Great support and clear communication. The process felt organized and safe. Thank you CSNK.
                    </p>
                  </div>
                </div>
              </div>

            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#testimonials" data-bs-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#testimonials" data-bs-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Next</span>
            </button>
          </div>

        </div>
      </div>
    </div>
  </section>

<!-- Contact / Map (Map Left, Info Right) -->
<section id="contact" class="py-5 bg-light">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="fw-bold mb-1">Contact and Location</h2>
      <p class="text-muted mb-0">Visit our office or reach us using the details below</p>
    </div>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
      <div class="row g-0">

        <!-- Map (Left) -->
        <div class="col-lg-7">
          <iframe
            style="width:100%; height:100%; min-height:420px; border:0;"
            src="https://www.google.com/maps?q=2F%20UNIT%201%20EDEN%20TOWNHOUSE%202001%20EDEN%20ST.%20COR%20PEDRO%20GIL%20STA%20ANA%2C%20BARANGAY%20784%2C%20CITY%20OF%20MANILA%2C%20NCR%2C%20FIRST%20DISTRICT&output=embed"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            allowfullscreen>
          </iframe>
        </div>

        <!-- Info (Right) -->
        <div class="col-lg-5 bg-white">
          <div class="p-4 p-md-5 h-100 d-flex flex-column justify-content-center">

            <div class="d-flex align-items-center gap-2 mb-3">
              <span class="badge bg-danger rounded-pill px-3 py-2">CSNK Manpower Agency</span>
            </div>

            <h5 class="fw-bold mb-3">Office Information</h5>

            <div class="d-flex gap-3 mb-3">
              <div class="text-danger fs-5"><i class="fa-solid fa-location-dot"></i></div>
              <div>
                <div class="fw-semibold">Address</div>
                <div class="text-muted small">
                  2F Unit 1 Eden Townhouse<br>
                  2001 Eden St. Cor Pedro Gil, Sta Ana<br>
                  Barangay 784, City of Manila, NCR, First District
                </div>
              </div>
            </div>

            <div class="d-flex gap-3 mb-3">
              <div class="text-danger fs-5"><i class="fa-solid fa-phone"></i></div>
              <div>
                <div class="fw-semibold">Phone</div>
                <div class="text-muted small">+63 (02) 1234-5678</div>
              </div>
            </div>

            <div class="d-flex gap-3 mb-3">
              <div class="text-danger fs-5"><i class="fa-solid fa-envelope"></i></div>
              <div>
                <div class="fw-semibold">Email</div>
                <div class="text-muted small">info@csnkmanpower.com</div>
              </div>
            </div>

            <div class="d-flex gap-3 mb-4">
              <div class="text-danger fs-5"><i class="fa-solid fa-clock"></i></div>
              <div>
                <div class="fw-semibold">Office Hours</div>
                <div class="text-muted small">Mon to Sat, 8:00 AM to 5:00 PM</div>
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
              <a class="btn btn-danger rounded-pill px-4"
                 target="_blank" rel="noopener"
                 href="https://www.google.com/maps?q=2F%20UNIT%201%20EDEN%20TOWNHOUSE%202001%20EDEN%20ST.%20COR%20PEDRO%20GIL%20STA%20ANA%2C%20BARANGAY%20784%2C%20CITY%20OF%20MANILA%2C%20NCR%2C%20FIRST%20DISTRICT">
                <i class="fa-solid fa-location-arrow me-2"></i>Get Directions
              </a>

              <a class="btn btn-outline-secondary rounded-pill px-4" href="#home">
                <i class="fa-solid fa-arrow-up me-2"></i>Back to Top
              </a>
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>
</section>



  <!-- Footer -->
  <footer class="bg-white border-top">
    <div class="container py-4">
      <div class="row g-4 align-items-start">
        <div class="col-md-6">
          <img src="../resources/img/csnklogo.png" alt="CSNK Logo" style="height:75px;">
        </div>

        <div class="col-md-6">
          <ul class="list-unstyled text-muted mb-0">
            <li class="mb-2"><i class="fa-solid fa-location-dot text-danger me-2"></i>2F Unit 1 Eden Townhouse 2001 Eden St. Cor Pedro Gil, Sta Ana Barangay 784, City of Manila, NCR, First District</li>
            <li class="mb-2"><i class="fa-solid fa-phone text-danger me-2"></i>+63 (02) 1234-5678</li>
            <li class="mb-2"><i class="fa-solid fa-envelope text-danger me-2"></i>info@csnkmanpower.com</li>
            <li class="mb-0"><i class="fa-solid fa-clock text-danger me-2"></i>Mon - Sat: 8:00 AM - 5:00 PM</li>
          </ul>
        </div>
      </div>

      <hr class="my-4">

      <div class="text-center small text-muted">
        <div>Copyright © <?php echo date('Y'); ?> CSNK Manpower Agency. All Rights Reserved.</div>
        <div class="mt-2">
          <a class="text-muted text-decoration-none me-3" href="#">Legal Notice</a>
          <a class="text-muted text-decoration-none me-3" href="#">Privacy Policy</a>
          <a class="text-muted text-decoration-none" href="#">Refund Policy</a>
        </div>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Smooth scroll + active nav
    const navLinks = document.querySelectorAll('a[href^="#"]');
    navLinks.forEach(a => {
      a.addEventListener('click', (e) => {
        const target = document.querySelector(a.getAttribute('href'));
        if (!target) return;
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth' });
      });
    });

    const sections = document.querySelectorAll('section[id]');
    window.addEventListener('scroll', () => {
      const y = window.scrollY + 120;
      sections.forEach(s => {
        const top = s.offsetTop;
        const bottom = top + s.offsetHeight;
        if (y >= top && y < bottom) {
          navLinks.forEach(l => l.classList.remove('active'));
          const active = document.querySelector(`a[href="#${s.id}"]`);
          if (active) active.classList.add('active');
        }
      });
    });
  </script>
</body>
</html>