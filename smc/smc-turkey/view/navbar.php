<?php
// Expect $page from parent file
if (!isset($page)) { $page = ''; }
?>
<header class="navbar-header shadow-lg">
  <nav class="navbar navbar-expand-lg navbar-dark bg-navy-premium">
    <div class="container py-2">

      <!-- Brand Logo -->
      <a href="./index.php" class="navbar-brand d-flex align-items-center gap-2">
        <div class="brand-wrap d-flex align-items-center gap-2">
          <img src="../resources/img/smc.png" alt="SMC Logo" class="img-fluid" style="height:52px;">
          <span class="brand-title fw-bold text-gold d-none d-sm-inline">
            SMC Manpower Agency Philippines Co.
          </span>
        </div>
      </a>

      <!-- Mobile Toggler -->
      <button class="navbar-toggler border-gold" 
              type="button" 
              data-bs-toggle="collapse" 
              data-bs-target="#csnkNav"
              aria-controls="csnkNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- NAVIGATION -->
      <div class="collapse navbar-collapse justify-content-end" id="csnkNav">
        <ul class="navbar-nav align-items-lg-center gap-3 fw-semibold">

          <li class="nav-item">
            <a href="./index.php"
              class="nav-link px-3 <?= $page==='home' ? 'active-link' : '' ?>">
              Home
            </a>
          </li>

          <li class="nav-item position-relative">
            <a href="./applicant.php"
              class="nav-link px-3 <?= $page==='applicants' ? 'active-link' : '' ?>">
              Applicants
              <span class="badge badge-gold new-badge">New</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="./about.php"
              class="nav-link px-3 <?= $page==='about' ? 'active-link' : '' ?>">
              About
            </a>
          </li>

          <li class="nav-item">
            <a href="./contactUs.php"
              class="nav-link px-3 <?= $page==='contact' ? 'active-link' : '' ?>">
              Contact
            </a>
          </li>

        </ul>
      </div>

    </div>
  </nav>
</header>

<style>
/* ============================
   COLOR SYSTEM
============================ */
:root{
  --smc-navy-dark: #04101F;   /* NEW deeper navy */
  --smc-navy: #0A1E36;       
  --smc-navy-light: #15345A;
  --smc-gold: #FFD84D;
}

/* ============================
   NAVBAR BACKGROUND
============================ */
.bg-navy-premium {
  background: linear-gradient(135deg, var(--smc-navy-dark), var(--smc-navy-light));
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
}

/* ============================
   BRAND
============================ */
.brand-title {
  font-size: 1.05rem;
  letter-spacing: 0.3px;
}

/* ============================
   NAV LINKS
============================ */
.navbar .nav-link {
  position: relative;
  color: #ffffffd9;
  padding-bottom: 6px;
  font-size: 1rem;
  transition: .25s ease;
}

/* Gold underline animation */
.navbar .nav-link::after {
  content:"";
  position:absolute;
  left:0; bottom:0;
  width:0%;
  height:2px;
  background: var(--smc-gold);
  transition: .25s ease;
  border-radius: 999px;
}

.navbar .nav-link:hover {
  color: var(--smc-gold);
}
.navbar .nav-link:hover::after {
  width:100%;
}

/* Active state */
.active-link {
  color: var(--smc-gold) !important;
  font-weight: 700;
}
.active-link::after {
  width:100% !important;
}

/* ============================
   NEW BADGE
============================ */
.badge-gold {
  background: var(--smc-gold);
  color: var(--smc-navy-dark);
  font-weight: 800;
  padding: 0.25rem 0.55rem;
  border-radius: 999px;
  font-size: 0.65rem;
}
.new-badge {
  position: absolute;
  top: -4px;
  right: -18px;
  box-shadow: 0 0 10px rgba(255,215,77,0.5);
  animation: pulseGold 2s infinite ease-in-out;
}

@keyframes pulseGold {
  0% { transform: scale(1); opacity: 0.9; }
  50% { transform: scale(1.15); opacity: 1; }
  100% { transform: scale(1); opacity: 0.9; }
}

/* ============================
   TOGGLER (MOBILE)
============================ */
.border-gold {
  border: 1px solid var(--smc-gold) !important;
  border-radius: 6px;
}

.navbar-toggler-icon {
  filter: invert(90%) sepia(80%) saturate(800%) hue-rotate(360deg)
          brightness(105%) contrast(105%);
}

/* Mobile dropdown style */
@media (max-width: 991px) {
  #csnkNav {
    background: var(--smc-navy-dark);
    border-radius: 12px;
    padding: 1rem;
    margin-top: 0.5rem;
  }

  .navbar-nav .nav-link {
    padding-left: 0.5rem;
  }
}
</style>