
<?php
  // Set the active page for navbar highlighting
  $page = 'about';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CSNK Manpower Agency</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- ✅ Reusable Navbar -->
<?php include __DIR__ . '/navbar.php'; ?>

<!-- ===================== -->
<!-- Page Content Starts   -->
<!-- ===================== -->

<style>
  /* ---------- Base / utilities applicable to this page ---------- */
  img, svg { max-width: 100%; height: auto; }

  /* ---------- HERO SECTION ---------- */
  .hero-section {
    background-color: #f8f9fb;
    position: relative;
    isolation: isolate; /* keep background layers behind content */
    padding: clamp(2rem, 6vw, 5rem) 0;
  }

  /* Background layers */
  .hero-grid,
  .hero-gradient {
    position: absolute; inset: 0;
    z-index: 0;
    pointer-events: none;
  }
  .hero-grid {
    opacity: .22;
    background-image:
      linear-gradient(to right, rgba(0,0,0,.06) 1px, transparent 1px),
      linear-gradient(to bottom, rgba(0,0,0,.06) 1px, transparent 1px);
    background-size: 32px 32px, 32px 32px;
    mask-image: linear-gradient(to bottom, rgba(0,0,0,1) 10%, rgba(0,0,0,.85) 40%, rgba(0,0,0,.6) 70%, rgba(0,0,0,0) 100%);
  }
  .hero-gradient {
    background:
      radial-gradient(900px 400px at 15% 35%, rgba(255, 159, 169, 0.88), rgba(220, 53, 69, 0) 60%),
      radial-gradient(700px 350px at 80% 45%, rgba(17, 17, 17, .12), rgba(17,17,17,0) 60%),
      linear-gradient(180deg, rgba(255,255,255,0) 0%, rgba(255,255,255,.25) 60%, rgba(255, 84, 84, 0) 100%);
    mask-image: linear-gradient(to bottom, rgba(0,0,0,0) 0%, rgba(0,0,0,.9) 10%, rgba(0,0,0,.95) 85%, rgba(0,0,0,0) 100%);
  }

  /* Keep actual content above background layers */
  .hero-section .container { position: relative; z-index: 1; }

  /* Headings (desktop) */
  @media (min-width: 992px) {
    .hero-section .display-4 { font-size: 3rem; line-height: 1.1; }
  }

  /* Smooth swap animation */
  .fade-swap { transition: opacity .22s ease, transform .22s ease; }
  .is-swapping { opacity: 0; transform: translateY(6px); }

  /* ---------- PILL BAR (shrink-to-content & scrollable) ---------- */

  /* Wrapper: only scrolls when needed */
  .hero-pills-abs-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  /* Pill bar: fit to content, not full width */
  #heroPills {
    display: inline-flex;        /* makes white capsule hug its content */
    width: max-content;          /* shrink-wrap */
    max-width: 100%;             /* never overflow the container */
    gap: .5rem;
    align-items: center;
    padding: .5rem .6rem;        /* capsule padding */
    border-radius: 999px;
    box-shadow: 0 4px 15px rgba(0,0,0,.08);
    overflow: visible;           /* keep nice shadows visible */
    flex-wrap: nowrap;           /* one row; wrapper handles scroll on small screens */
    scroll-snap-type: x proximity;
    background: #fff;            /* ensure white capsule */
  }

  #heroPills .btn {
    flex: 0 0 auto;              /* no shrinking */
    white-space: nowrap;         /* keep labels on one line */
    scroll-snap-align: start;
  }

  .hero-section .btn-light.active { background: #111; color: #fff; }

  /* Center the capsule on md+ (optional) */
  @media (min-width: 768px) {
    .hero-pills-abs-wrapper {
      display: flex;
      justify-content: flex-start; /* change to center if you want centered pills */
    }
  }

  /* ---------- Hero visual (image) ---------- */
  .hero-visual { display: flex; justify-content: center; }
  .hero-image-wrap {
    background: transparent;
    border-radius: 1rem;
    filter: drop-shadow(0 12px 22px rgba(0,0,0,.18));
    width: clamp(260px, 40vw, 520px);  /* desktop-preferred width */
  }
  .hero-image-wrap img {
    display: block;
    width: 100%;
    height: auto;
    object-fit: contain;
  }

  /* ---------- Responsive fixes (small → medium) ---------- */

  /* Let hero content breathe on xs; avoid clipping */
  @media (max-width: 575.98px) {
    .hero-section { overflow: visible !important; }
    .hero-title-wrap .display-4 { font-size: 2rem; line-height: 1.2; }
    .hero-lead-wrap .lead { font-size: 1rem; }
  }

  /* On < lg, center the image and remove desktop offsets entirely */
  @media (max-width: 991.98px) {
    .hero-image-wrap {
      margin: 0 auto !important;
      transform: none !important;
      width: min(85vw, 420px);
    }
  }

  /* ---------- Carousel image heights by breakpoint (if you later add slide images) ---------- */
  .carousel-img {
    width: 100%;
    height: 340px;
    object-fit: cover;
  }
  @media (max-width: 575.98px) { .carousel-img { height: 220px; } }
  @media (min-width: 576px) and (max-width: 991.98px) { .carousel-img { height: 280px; } }

  /* Extra room around pills on phones */
  @media (max-width: 575.98px) {
    .hero-pills-abs-wrapper { margin-bottom: .5rem; }
    .hero-pills-spacer { height: 0; }
  }

  /* ===================== */
  /* TRAINING GALLERY CSS  */
  /* ===================== */
  .training-gallery .btn-icon {
    width: 40px; height: 40px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
  }

  .training-gallery .gallery-grid {
    display: grid;
    gap: 1rem;
    /* Mobile: simple one-column grid */
    grid-template-columns: 1fr;
  }

  /* Tablet */
  @media (min-width: 576px) {
    .training-gallery .gallery-grid { grid-template-columns: repeat(2, 1fr); }
  }

  /* Desktop – collage layout:
       a b d
       a c d
  */
  @media (min-width: 992px) {
    .training-gallery .gallery-grid {
      grid-template-columns: 3fr 2fr 3fr;
      grid-template-rows: auto auto;
      grid-template-areas: "a b d" "a c d";
    }
    .training-gallery .gallery-item[data-area="a"] { grid-area: a; }
    .training-gallery .gallery-item[data-area="b"] { grid-area: b; }
    .training-gallery .gallery-item[data-area="c"] { grid-area: c; }
    .training-gallery .gallery-item[data-area="d"] { grid-area: d; }
  }

  .training-gallery .gallery-item {
    position: relative; border-radius: 1rem; overflow: hidden; background: #f8f9fa;
    transition: transform .25s ease, box-shadow .25s ease;
    cursor: zoom-in;
  }
  .training-gallery .gallery-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(0,0,0,.12);
  }

  /* Aspect ratios to echo the reference */
  .training-gallery .gallery-item[data-area="a"],
  .training-gallery .gallery-item[data-area="d"] { aspect-ratio: 3 / 4; }   /* tall */
  .training-gallery .gallery-item[data-area="b"],
  .training-gallery .gallery-item[data-area="c"] { aspect-ratio: 4 / 3; }   /* wide-ish */

  .training-gallery .gallery-item img {
    width: 100%; height: 100%; object-fit: cover; display: block;
  }

  /* Dots styling */
  .training-gallery .gallery-dots button {
    width: 28px; height: 4px; border-radius: 999px;
    background-color: #d9dbe1; border: 0; margin: 0 .18rem;
  }
  .training-gallery .gallery-dots button.active { background-color: #2f315a; }

  /* Modal image fits nicely on dark backdrop */
  #galleryModal .modal-content { background: #000; }
  #galleryModalImg { max-height: 82vh; object-fit: contain; }

  /* ====================== */
/* FINAL CTA (Hire Now!)  */
/* ====================== */
.cta-hire {
  /* Soft white card with subtle, directionally-lit gradient like your ref */
  background:
    /* top-left warm highlight */
    radial-gradient(800px 260px at 8% 5%, rgba(255,170,120,.18), rgba(255,170,120,0) 60%),
    /* bottom-right cool fade */
    radial-gradient(1000px 320px at 92% 110%, rgba(12,32,76,.08), rgba(12,32,76,0) 60%),
    /* gentle vertical wash */
    linear-gradient(180deg, #ffffff 0%, #fbfcff 60%, #f7f9fc 100%);
  border-radius: 1.25rem;
  padding: clamp(1rem, 3vw, 2rem) clamp(1rem, 3.5vw, 2rem);
  box-shadow:
    0 20px 40px rgba(13, 29, 54, 0.06),
    0 1px 0 rgba(255,255,255,0.6) inset;
}

.cta-row {
  display: grid;
  /* Title left, button right on md+; single column on mobile */
  grid-template-columns: 1fr;
  align-items: center;
  gap: clamp(.75rem, 2vw, 1rem);
}
@media (min-width: 768px) {
  .cta-row { grid-template-columns: 1fr auto; }
}

.cta-title {
  font-weight: 800;
  font-size: clamp(1.05rem, 2.1vw, 1.35rem);
  color: #1b1d22;
  margin: 0;
  line-height: 1.35;
}

.cta-actions {
  display: flex;
  justify-content: flex-start;   /* mobile */
}
@media (min-width: 768px) {
  .cta-actions { justify-content: flex-end; } /* button to the far right on md+ */
}

/* Button */
.cta-btn {
  --grad-a: #ff7a3d;   /* warm orange */
  --grad-b: #ffb04a;   /* soft orange-yellow */
  background: linear-gradient(90deg, var(--grad-a), var(--grad-b));
  color: #fff;
  border: 0;
  border-radius: 999px;
  padding: .85rem 1.5rem;
  font-weight: 700;
  letter-spacing: .2px;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: .6rem;
  box-shadow: 0 12px 26px rgba(255, 122, 61, .28);
  transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
  position: relative;              /* for decorative ticks */
  isolation: isolate;              /* keep inner effects confined */
  white-space: nowrap;
}
.cta-btn:hover,
.cta-btn:focus {
  transform: translateY(-1px);
  box-shadow: 0 16px 34px rgba(255, 122, 61, .34);
  filter: brightness(1.03);
  color: #fff;
}

/* Decorative side marks to echo the ref’s “spark” shape */
.cta-btn::after {
  content: "✦ ✦ ✦";
  font-size: .85rem;
  color: #ffa95a;
  position: absolute;
  right: -2rem;                    /* sits outside the button’s right edge */
  top: 50%;
  transform: translateY(-50%);
  opacity: .95;
  pointer-events: none;
}
@media (max-width: 575.98px) {
  .cta-btn::after { right: -1.6rem; font-size: .8rem; }
}

/* Respect reduced motion */
@media (prefers-reduced-motion: reduce) {
  .cta-btn { transition: none !important; }
}
</style>

<!-- HERO -->
<section class="hero-section">
  <!-- background layers -->
  <div class="hero-grid"></div>
  <div class="hero-gradient"></div>

  <div class="container">
    <div class="row align-items-center g-4 g-lg-5">

      <!-- LEFT: Text + pills -->
      <div class="col-12 col-lg-6">
        <div class="hero-title-wrap mb-2">
          <h1 id="heroTitle" class="display-4 fw-bold mb-0 fade-swap">
            Get to know CSNK Manpower Agency
          </h1>
        </div>

        <div class="hero-lead-wrap mb-4">
          <p id="heroLead" class="lead text-black-50 mb-0 fade-swap">
            Clear, honest and customer‑first guidance. We connect families with properly
            screened domestic workers through safe and compliant processes.
          </p>
        </div>

        <!-- Pills -->
        <div class="hero-pills-abs-wrapper">
          <div id="heroPills"
               class="rounded-pill px-3 py-2 d-inline-flex align-items-center"
               role="tablist" aria-label="Hero options">

            <button type="button" class="btn btn-light rounded-pill px-3 py-2 active"
                    role="tab" aria-selected="true"
                    data-title="Get to know CSNK"
                    data-lead="CSNK Manpower Agency is dedicated to providing families with reliable 
                    and compassionate household assistance. Beyond offering quality domestic help, we 
                    are a full‑service manpower agency committed to supporting and empowering Filipino 
                    women by connecting them with safe, legitimate, and rewarding employment opportunities. 
                    Through proper screening, guidance, and documentation, we ensure that every home receives 
                    trustworthy service, while every applicant receives a fair chance to build a better future."
                    data-img="../resources/img/overview.png"
                    data-img-alt="Overview image">
              Overview
            </button>

            <button type="button" class="btn btn-light rounded-pill px-3 py-2"
                    role="tab" aria-selected="false"
                    data-title="Meet Founder of CSNK"
                    data-lead="CSNK was founded by Mr. Rogelio M. Lansang year 2010, because of his 
                    passion to help people and give a job for those in need. He is formerly an Overseas 
                    Filipino Worker in the Middle East for Ten (10) years from 1989 to 2004. His goal 
                    is to provide an opportunity that will help Filipino women build a better future, not
                    only for themselves, but for their families. He was successfully managing group of 
                    companies under the SMC GROUP OF COMPANY in 2006. Since then, Mr. Lansang has remained 
                    committed to ensuring that CSNK carries out its mission with integrity."
                    data-img="../resources/img/MrRog.png"
                    data-img-alt="Founder image">
              Founder
            </button>
          </div>
        </div>

        <!-- Spacer (kept for layout compatibility) -->
        <div class="hero-pills-spacer"></div>
      </div>

      <!-- RIGHT: Image -->
      <div class="col-12 col-lg-6 hero-visual">
        <div class="hero-image-wrap rounded-4">
          <img id="heroImg"
               src="/resources/img/hero1.jpg"
               alt="Hero visual"
               class="img-fluid fade-swap">
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ===================== -->
<!-- Training Gallery -->
<!-- ===================== -->
<section id="training-gallery" class="training-gallery py-5 bg-white">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h2 class="h1 fw-bold mb-0">Gallery</h2>

      <!-- Desktop/Tablet arrows -->
      <div class="d-none d-sm-flex align-items-center gap-2">
        <button class="btn btn-outline-secondary btn-icon" type="button"
                data-bs-target="#galleryCarousel" data-bs-slide="prev" aria-label="Previous">
          <i class="fa-solid fa-arrow-left"></i>
        </button>
        <button class="btn btn-outline-secondary btn-icon" type="button"
                data-bs-target="#galleryCarousel" data-bs-slide="next" aria-label="Next">
          <i class="fa-solid fa-arrow-right"></i>
        </button>
      </div>
    </div>

    <div id="galleryCarousel" class="carousel slide" data-bs-ride="false" data-bs-interval="false" data-bs-touch="true">
      <!-- Slides -->
      <div class="carousel-inner">

        <!-- Slide 1 -->
        <div class="carousel-item active">
          <div class="gallery-grid">
            <a href="https://tinyurl.com/3ekp6msy" class="gallery-item" data-area="a" data-full="https://tinyurl.com/3ekp6msy">
              <img src="https://tinyurl.com/3ekp6msy" alt="Training photo 1">
            </a>
            <a href="https://tinyurl.com/45bw8zv3" class="gallery-item" data-area="b" data-full="https://tinyurl.com/45bw8zv3">
              <img src="https://tinyurl.com/45bw8zv3" alt="Training photo 2">
            </a>
            <a href="https://tinyurl.com/55nwhff7" class="gallery-item" data-area="c" data-full="https://tinyurl.com/55nwhff7">
              <img src="https://tinyurl.com/55nwhff7" alt="Training photo 3">
            </a>
            <a href="https://tinyurl.com/47wt7tb2" class="gallery-item" data-area="d" data-full="https://tinyurl.com/47wt7tb2">
              <img src="https://tinyurl.com/47wt7tb2" alt="Training photo 4">
            </a>
          </div>
        </div>

        <!-- Slide 2 -->
        <div class="carousel-item">
          <div class="gallery-grid">
            <a href="https://tinyurl.com/4bkcuuwn" class="gallery-item" data-area="a" data-full="https://tinyurl.com/4bkcuuwn">
              <img src="https://tinyurl.com/4bkcuuwn" alt="Training photo 5">
            </a>
            <a href="https://tinyurl.com/ye7xubh2" class="gallery-item" data-area="b" data-full="https://tinyurl.com/ye7xubh2">
              <img src="https://tinyurl.com/ye7xubh2" alt="Training photo 6">
            </a>
            <a href="https://tinyurl.com/4fty8ztf" class="gallery-item" data-area="c" data-full="https://tinyurl.com/4fty8ztf">
              <img src="https://tinyurl.com/4fty8ztf" alt="Training photo 7">
            </a>
            <a href="https://tinyurl.com/2mky2xyz" class="gallery-item" data-area="d" data-full="https://tinyurl.com/2mky2xyz">
              <img src="https://tinyurl.com/2mky2xyz" alt="Training photo 8">
            </a>
          </div>
        </div>

        <!-- Slide 3 -->
        <div class="carousel-item">
          <div class="gallery-grid">
            <a href="https://tinyurl.com/3ekp6msy" class="gallery-item" data-area="a" data-full="https://tinyurl.com/3ekp6msy">
              <img src="https://tinyurl.com/3ekp6msy" alt="Training photo 9">
            </a>
            <a href="https://tinyurl.com/45bw8zv3" class="gallery-item" data-area="b" data-full="https://tinyurl.com/45bw8zv3">
              <img src="https://tinyurl.com/45bw8zv3" alt="Training photo 10">
            </a>
            <a href="https://tinyurl.com/55nwhff7" class="gallery-item" data-area="c" data-full="https://tinyurl.com/55nwhff7">
              <img src="https://tinyurl.com/55nwhff7" alt="Training photo 11">
            </a>
            <a href="https://tinyurl.com/47wt7tb2" class="gallery-item" data-area="d" data-full="https://tinyurl.com/47wt7tb2">
              <img src="https://tinyurl.com/47wt7tb2" alt="Training photo 12">
            </a>
          </div>
        </div>

      </div>

      <!-- Mobile arrows -->
      <button class="carousel-control-prev d-sm-none" type="button" data-bs-target="#galleryCarousel" data-bs-slide="prev" aria-label="Previous">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      </button>
      <button class="carousel-control-next d-sm-none" type="button" data-bs-target="#galleryCarousel" data-bs-slide="next" aria-label="Next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
      </button>

      <!-- Dots -->
      <div class="carousel-indicators gallery-dots mt-4">
        <button type="button" data-bs-target="#galleryCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#galleryCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#galleryCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
      </div>
    </div>
  </div>

  <!-- Lightbox Modal -->
  <div class="modal fade" id="galleryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
      <div class="modal-content bg-black border-0">
        <button type="button" class="btn-close btn-close-white ms-auto me-2 mt-2" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="p-2 p-sm-3">
          <img id="galleryModalImg" src="" alt="Training photo" class="img-fluid w-100 rounded-3">
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ====================== -->
<!-- FINAL CTA: Hire Now!  -->
<!-- ====================== -->
<section class="py-4 py-md-5">
  <div class="container">
    <div class="cta-hire">
      <div class="cta-row">
        <p class="cta-title">
          Hire reliable, properly screened Household Service Workers (HSWs)
          for your home.
        </p>

        <div class="cta-actions">
          <a class="cta-btn" href="./applicant.php" aria-label="Hire Now">
            Hire Now! <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>
      </div>
    </div><!-- /.cta-hire -->
  </div>
</section>

<!-- ===================== -->
<!-- Page Content Ends     -->
<!-- ===================== -->

<!-- ✅ Reusable Footer -->
<?php include __DIR__ . '/footer.php'; ?>

<!-- Bootstrap JS (bundle includes Popper + Carousel) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Policy Modals Handler -->
<script src="../resources/js/policy-modals.js"></script>

<!-- Page‑local: Hero pill swapper -->
<script>
(function(){
  const container = document.getElementById('heroPills');
  const titleEl   = document.getElementById('heroTitle');
  const leadEl    = document.getElementById('heroLead');
  const imgEl     = document.getElementById('heroImg');

  if (!container || !titleEl || !leadEl || !imgEl) return;

  const pills   = container.querySelectorAll('.btn');
  const swapEls = [titleEl, leadEl, imgEl];

  function setActive(btn){
    pills.forEach(b => { b.classList.remove('active'); b.setAttribute('aria-selected','false'); });
    btn.classList.add('active'); btn.setAttribute('aria-selected','true');
  }

  function applyFrom(btn){
    if (btn.dataset.title) titleEl.textContent = btn.dataset.title;
    if (btn.dataset.lead)  leadEl.textContent  = btn.dataset.lead;
    if (btn.dataset.img) {
      imgEl.src = btn.dataset.img;
      imgEl.alt = btn.dataset.imgAlt || btn.dataset.title || 'Hero image';
    }
  }

  function swap(btn){
    setActive(btn);
    swapEls.forEach(el => el.classList.add('is-swapping'));
    setTimeout(() => {
      applyFrom(btn);
      swapEls.forEach(el => el.classList.remove('is-swapping'));
    }, 150);
  }

  pills.forEach(btn => {
    btn.addEventListener('click', () => swap(btn));
    btn.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); swap(btn); }
    });
  });

  const init = container.querySelector('.btn.active') || pills[0];
  if (init) applyFrom(init);
})();
</script>

<!-- Page‑local: Training Gallery lightbox & keys -->
<script>
(function(){
  const modalEl = document.getElementById('galleryModal');
  const modalImg = document.getElementById('galleryModalImg');
  const gallerySection = document.getElementById('training-gallery');

  if (!modalEl || !modalImg || !gallerySection) return;

  // Open modal with clicked image
  gallerySection.querySelectorAll('.gallery-item').forEach(item => {
    item.addEventListener('click', function(e){
      e.preventDefault();
      const full = this.getAttribute('data-full') || this.querySelector('img')?.src;
      if (!full) return;
      modalImg.src = full;
      const m = bootstrap.Modal.getOrCreateInstance(modalEl);
      m.show();
    });
  });

  // Optional: arrow keys go to next/prev slide when modal is open
  document.addEventListener('keydown', function(e){
    const isOpen = modalEl.classList.contains('show');
    if (!isOpen) return;
    if (e.key === 'ArrowRight') {
      document.querySelector('#galleryCarousel .carousel-control-next')?.click();
    } else if (e.key === 'ArrowLeft') {
      document.querySelector('#galleryCarousel .carousel-control-prev')?.click();
    }
  }, false);
})();
</script>

</body>
</html>
