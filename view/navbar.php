<?php
// Expect $page from the parent file: 'home', 'applicants', 'about', 'contact'
if (!isset($page)) { $page = ''; }
?>
<header class="bg-white border-bottom sticky-top">
  <nav class="navbar navbar-expand-lg navbar-light bg-white">
    <div class="container py-2">

      <!-- Brand / Logo -->
      <a class="navbar-brand d-flex align-items-center" href="/index.php#home">
        <img src="../resources/img/csnklogo.png" alt="CSNK Manpower Agency" class="img-fluid" style="height:72px;">
      </a>

      <!-- Mobile toggler -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#csnkNav"
              aria-controls="csnkNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Right: Nav -->
      <div class="collapse navbar-collapse justify-content-end" id="csnkNav">
        <ul class="navbar-nav align-items-lg-center gap-2 my-2 my-lg-0 fw-medium">

          <li class="nav-item">
            <a class="nav-link px-3 <?= $page==='home' ? 'active' : '' ?>"
               href="./index.php#home"
               aria-current="<?= $page==='home' ? 'page' : 'false' ?>">
              Home
            </a>
          </li>

        <li class="nav-item position-relative">
          <a class="nav-link px-3 <?= $page==='applicants' ? 'active' : '' ?>"
            href="./applicant.php"
            aria-current="<?= $page==='applicants' ? 'page' : 'false' ?>">
            Applicants
          </a>

          <!-- Floating NEW badge -->
          <span class="badge bg-danger small new-badge">NEW</span>
        </li>


          <li class="nav-item">
            <a class="nav-link px-3 <?= $page==='about' ? 'active' : '' ?>"
               href="./index.php#about"
               aria-current="<?= $page==='about' ? 'page' : 'false' ?>">
              About
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link px-3 <?= $page==='contact' ? 'active' : '' ?>"
               href="./contactUs.php"
               aria-current="<?= $page==='contact' ? 'page' : 'false' ?>">
              Contact
            </a>
          </li>

        </ul>
      </div>

    </div>
  </nav>
</header>
<style>
  .navbar .nav-link {
    position: relative;
    border-bottom: 2px solid transparent; /* reserve space to avoid layout shift */
    transition: border-color .2s ease;
  }
  .navbar .nav-link.active,
  .navbar .nav-link[aria-current="page"] {
    border-bottom-color: #D72638; /* your accent red */
  }
  /* Optional: also show on hover/focus (desktop) */
  @media (hover: hover) {
    .navbar .nav-link:hover,
    .navbar .nav-link:focus {
      border-bottom-color: #D72638;
    } 
.new-badge {
  position: absolute;
  top: -2px;
  right: -6px;
  font-size: .55rem;
  padding: .2rem .35rem;
  border-radius: 999px;
  pointer-events: none;
}

  }
</style>