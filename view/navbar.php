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
               href="./index.php"
               aria-current="<?= $page==='home' ? 'page' : 'false' ?>">
              Home
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link px-3 <?= $page==='applicants' ? 'active' : '' ?>"
               href="./applicant.php"
               aria-current="<?= $page==='applicants' ? 'page' : 'false' ?>">
              Applicants
              <span class="ms-1 badge text-bg-danger align-text-top">New</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link px-3 <?= $page==='about' ? 'active' : '' ?>"
               href="./about.php"
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

  /* Header icon tab (small image beside brand) */
  .header-icon-tab { display:inline-flex; align-items:center; text-decoration:none; }
  .header-icon-tab .header-preview { height:36px; width:auto; display:block; }
  .header-icon-tab:hover .header-preview { opacity: .9; transform: translateY(-1px); transition: transform .15s ease, opacity .15s ease; }
  @media (max-width: 576px) {
    .header-icon-tab .header-preview { height:30px; }
  }
</style>