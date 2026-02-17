<?php
// Expect $page from parent file
if (!isset($page)) { $page = ''; }
?>
<header id="site-navbar">
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container py-2">

      <!-- Brand -->
      <a href="./index.php" class="navbar-brand d-flex align-items-center gap-2">
        <img src="../resources/img/smc.png" alt="SMC Logo" style="height:48px; width:auto;">
        <span class="brand-title fw-bold d-none d-sm-inline">
          SMC Manpower Agency Philippines Co.
        </span>
      </a>

      <!-- Toggler -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
              aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Nav -->
      <div class="collapse navbar-collapse justify-content-end" id="mainNav">
        <ul class="navbar-nav align-items-lg-center fw-semibold gap-2">
          <li class="nav-item">
            <a class="nav-link px-3 <?= $page==='home' ? 'active' : '' ?>" href="./index.php">Home</a>
          </li>
          <li class="nav-item position-relative">
            <a class="nav-link px-3 <?= $page==='applicants' ? 'active' : '' ?>" href="./applicant.php">
              Applicants
              <span class="position-absolute top-0 translate-middle badge rounded-pill text-dark bg-warning"
                    style="right:-10px">New</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link px-3 <?= $page==='about' ? 'active' : '' ?>" href="./about.php">About</a>
          </li>
          <li class="nav-item">
            <a class="nav-link px-3 <?= $page==='contact' ? 'active' : '' ?>" href="./contactUs.php">Contact</a>
          </li>
        </ul>
      </div>

    </div>
  </nav>
</header>

<!-- Minimal, scoped styles so other pages cannot override -->
<style>
  :root{ --smc-navy:#0B1F3A; --smc-navy-2:#132A4A; --smc-gold:#FFD84D; }

  /* Scope everything to this header to avoid page CSS collisions */
  #site-navbar .navbar {
    background: linear-gradient(135deg, var(--smc-navy), var(--smc-navy-2));
  }
  #site-navbar .brand-title {
    color: var(--smc-gold) !important; /* stays gold regardless of page CSS */
    letter-spacing:.2px;
    font-size:1.03rem;
  }
  #site-navbar .nav-link {
    color: rgba(255,255,255,.9);
    position:relative;
    transition:color .2s ease;
  }
  #site-navbar .nav-link:hover,
  #site-navbar .nav-link.active {
    color: var(--smc-gold) !important;
  }
  #site-navbar .navbar-toggler {
    border-color: var(--smc-gold);
  }
  /* Keep Bootstrap's dark toggler icon; make it brighter for contrast */
  #site-navbar .navbar-dark .navbar-toggler-icon {
    filter: brightness(1.4);
  }
  @media (max-width: 991.98px){
    #site-navbar #mainNav { background: var(--smc-navy); border-radius:.5rem; padding: .5rem; margin-top:.5rem; }
  }
</style>