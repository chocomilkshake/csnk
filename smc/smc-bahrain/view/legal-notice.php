<?php
session_start();
$page = 'legal';
?>
<!doctype html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>SMC Manpower Agency Co.</title>

  <!-- SEO -->
  <meta name="description" content="Legal Notice for SMC Manpower Agency Philippines Company. Compliance-first, ethical overseas recruitment and placement for Bahrain employers and Filipino workers. Transparent terms, data handling, and responsibilities." />
  <meta name="theme-color" content="#0B1F3A" />

  <!-- Open Graph -->
  <meta property="og:title" content="Legal Notice — SMC Manpower Agency Philippines Company (Bahrain Program)" />
  <meta property="og:description" content="Compliance-first, ethical overseas recruitment for Bahrain. Read our Legal Notice and terms for using this site." />
  <meta property="og:type" content="website" />
  <meta property="og:image" content="../resources/img/hero.jpeg" />

  <!-- ✅ FAVICONS (root + fallback for /view/) -->
  <link rel="icon" type="image/png" href="/resources/img/smc.png" />
  <link rel="apple-touch-icon" href="/resources/img/smc.png" />
  <link rel="icon" type="image/png" href="../resources/img/smc.png" />
  <link rel="apple-touch-icon" href="../resources/img/smc.png" />

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />

  <style>
    :root{
      --navy: #0B1F3A;
      --navy-2:#0c1a2e;
      --gold: #FFD84D;
      --bh-red: #CE1126;
      --ink: #16243B;
      --muted: #6c7688;
      --soft-bg:#f4f6fb;
      --border:#e6ecf5;
      --radius:1.2rem;
      --shadow:0 10px 24px rgba(11,31,58,.10);
      --shadow-lg:0 18px 40px rgba(11,31,58,.12);
    }

    html,body{ background:#f8f9fb; color:var(--ink); }
    a{ color:var(--navy); text-decoration:none; }
    a:hover{ color:#09203a; text-decoration:underline; }

    /* Header banner */
    .page-header{
      padding: clamp(2.2rem,4vw,3.6rem) 0;
      background:
        radial-gradient(820px 260px at 8% 5%, rgba(255,216,77,.12), rgba(255,216,77,0) 60%),
        radial-gradient(920px 320px at 95% 120%, rgba(206,17,38,.10), rgba(206,17,38,0) 60%),
        linear-gradient(120deg, var(--navy) 20%, var(--navy-2) 80%);
      color:#fff;
      border-bottom-left-radius: 2rem;
      border-bottom-right-radius: 2rem;
      box-shadow: var(--shadow-lg);
      margin-bottom: 2.2rem;
    }
    .page-header h1{ font-weight:800; letter-spacing:-.3px; }
    .page-header p{ opacity:.9; }

    /* Card */
    .legal-card{
      background:#fff; border:1px solid var(--border);
      border-radius: var(--radius); box-shadow: var(--shadow);
      padding: clamp(1.2rem,2.2vw,2rem) clamp(1rem,2.2vw,2.2rem);
    }

    /* Section headings */
    .legal-card h2{
      margin-top:2rem; margin-bottom:.75rem;
      font-size:1.25rem; font-weight:800; color:var(--bh-red);
      display:flex; align-items:center; gap:.5rem;
    }
    .legal-card h2 i{ color:var(--bh-red); }

    .last-updated{ color:var(--muted); font-style:italic; }

    /* TOC */
    .toc{
      background:#fff; border:1px dashed var(--border);
      border-radius: 1rem; padding:1rem 1.25rem; margin:1rem 0 1.25rem;
    }
    .toc a{ text-decoration:none; }
    .toc a:hover{ text-decoration:underline; }

    /* Notice box */
    .notice{
      background:#fff; border-left:4px solid var(--gold);
      border-radius: .85rem; padding: .9rem 1rem; color:#39485d;
      box-shadow: 0 8px 20px rgba(11,31,58,.05);
    }

    /* CTA help box */
    .cta-help{
      background:#fff; border-left:5px solid var(--bh-red);
      border-radius: 1rem; padding: 1rem 1.25rem; margin-top:1.25rem;
      box-shadow: 0 10px 24px rgba(11,31,58,.08);
    }

    /* Lists spacing */
    .legal-card ul{ padding-left:1.1rem; }
    .legal-card li{ margin-bottom:.5rem; }
    .divider{ height:1px; background:var(--border); margin:1.1rem 0; }
  </style>
</head>
<body>

  <!-- Header / Navbar -->
  <?php include __DIR__ . '/navbar.php'; ?>

  <!-- Hero / Title -->
  <section class="page-header">
    <div class="container">
      <h1 class="display-6 mb-2">Legal Notice</h1>
      <p class="mb-0">SMC Manpower Agency Philippines Company — Bahrain Program</p>
    </div>
  </section>

  <!-- Main -->
  <main class="container pb-5">
    <div class="legal-card">
      <p class="last-updated">Last Updated: February 2026</p>

      <!-- Table of contents -->
      <div class="toc">
        <strong><i class="fa-solid fa-list-ul me-2"></i>Contents</strong>
        <div class="mt-2 small">
          <a href="#operator" class="me-3">1. Website Operator</a>
          <a href="#scope" class="me-3">2. Scope & Acceptance</a>
          <a href="#use" class="me-3">3. Website Use</a>
          <a href="#license" class="me-3">4. Limited License</a>
          <a href="#disclaimer" class="me-3">5. Disclaimers</a>
          <a href="#liability" class="me-3">6. Limitations of Liability</a>
          <a href="#bahrain" class="me-3">7. Bahrain‑Related Terms</a>
          <a href="#roles" class="me-3">8. Responsibilities</a>
          <a href="#ip" class="me-3">9. Intellectual Property</a>
          <a href="#links" class="me-3">10. Third‑Party Links</a>
          <a href="#privacy" class="me-3">11. Data & Privacy</a>
          <a href="#conduct" class="me-3">12. Prohibited Conduct</a>
          <a href="#mods" class="me-3">13. Changes</a>
          <a href="#law" class="me-3">14. Governing Law</a>
          <a href="#contact" class="">15. Contact</a>
        </div>
      </div>

      <div class="notice mb-3">
        <strong>Informational Only.</strong> This Legal Notice governs your use of this website and SMC’s online materials.
        It is not legal advice and does not modify any employment contract or agency agreement.
      </div>

      <h2 id="operator"><i class="fa-solid fa-building-shield"></i>1) Website Operator</h2>
      <p class="mb-1"><strong>Operator:</strong> SMC Manpower Agency Philippines Company</p>
      <p class="mb-1"><strong>Address:</strong> Unit 1 Eden Townhomes, 2001 Eden Street corner Pedro Gil Street, Sta. Ana, Barangay 866, City of Manila, NCR, Sixth District</p>
      <p class="mb-1"><strong>Email:</strong> <a href="mailto:smcphilippines.marketing@gmail.com">smcphilippines.marketing@gmail.com</a></p>
      <p class="mb-3"><strong>Phone:</strong> <a href="tel:+639393427412">0939 342 7412</a></p>

      <h2 id="scope"><i class="fa-solid fa-scale-balanced"></i>2) Scope & Acceptance</h2>
      <p>
        By accessing or using this website, you confirm that you have read, understood, and agree to this Legal Notice and any referenced policies.
        If you do not agree, please discontinue use of the website.
      </p>

      <h2 id="use"><i class="fa-solid fa-globe"></i>3) Website Use</h2>
      <p>
        This website provides general information about SMC’s recruitment and placement services. Content may include marketing materials,
        process overviews, and contact channels. Information is presented for convenience and may change without prior notice.
      </p>

      <h2 id="license"><i class="fa-solid fa-file-signature"></i>4) Limited License</h2>
      <p>
        SMC grants a limited, revocable, non‑exclusive license to view and temporarily download materials for personal, non‑commercial use.
        Under this license you may not:
      </p>
      <ul>
        <li>Modify, reproduce, or publicly display materials without permission</li>
        <li>Reverse engineer, decompile, or otherwise tamper with site code</li>
        <li>Remove proprietary notices or mirror content on other servers</li>
        <li>Use robots, scrapers, or automated tools without written consent</li>
      </ul>

      <h2 id="disclaimer"><i class="fa-solid fa-circle-exclamation"></i>5) Disclaimers</h2>
      <ul>
        <li><strong>As‑is Information.</strong> Website content is provided on an “as is” and “as available” basis.</li>
        <li><strong>No Guarantee.</strong> SMC does not guarantee outcomes, approvals, visa issuance, or employment offers.</li>
        <li><strong>No Legal Advice.</strong> Materials do not constitute legal, immigration, or regulatory advice.</li>
      </ul>

      <h2 id="liability"><i class="fa-solid fa-triangle-exclamation"></i>6) Limitations of Liability</h2>
      <p>
        To the maximum extent permitted, SMC shall not be liable for any indirect, incidental, consequential, special, or punitive damages,
        or for lost profits, lost data, or business interruption arising from or related to website use or reliance on website materials.
      </p>

      <h2 id="bahrain"><i class="fa-solid fa-flag"></i>7) Bahrain‑Related Terms (International Placement)</h2>
      <ul>
        <li><strong>Recruitment Intermediary.</strong> SMC acts as a Philippine recruitment and placement agency facilitating connections between applicants and employers, including Bahrain‑based employers.</li>
        <li><strong>Immigration Decisions.</strong> Visa issuance, entry permissions, and residency are solely determined by Bahrain authorities and are outside SMC’s control.</li>
        <li><strong>Local Requirements.</strong> Policies, sponsorship systems, and employment rules applicable in Bahrain may change. Employers and applicants remain responsible for complying with current procedures and lawful documentation.</li>
        <li><strong>Transparency.</strong> Final employment terms are set in the employer‑worker contract. SMC promotes clarity but is not a party to the employer’s payroll, workplace administration, or daily supervision.</li>
      </ul>

      <h2 id="roles"><i class="fa-solid fa-people-group"></i>8) Responsibilities</h2>
      <p class="mb-1"><strong>SMC (Agency):</strong></p>
      <ul>
        <li>Facilitates ethical recruitment and preliminary screening aligned with applicable Philippine processes of the competent authorities</li>
        <li>Coordinates documentation flow between employer and applicant where applicable</li>
        <li>Promotes clear expectations and proper orientation prior to deployment</li>
      </ul>

      <p class="mb-1"><strong>Employer (Bahrain‑based):</strong></p>
      <ul>
        <li>Provides accurate job details, lawful contract terms, and required employer documents</li>
        <li>Observes applicable local regulations, including lawful conditions of work</li>
        <li>Handles payroll, workplace safety, supervision, and any worksite obligations</li>
      </ul>

      <p class="mb-1"><strong>Applicant / Worker:</strong></p>
      <ul>
        <li>Provides truthful information and valid documents</li>
        <li>Completes medicals, clearances, and required orientations, if applicable</li>
        <li>Reviews and understands employment terms prior to acceptance</li>
      </ul>

      <div class="divider"></div>

      <p class="mb-1"><strong>Important Clarifications:</strong></p>
      <ul>
        <li><strong>No Guarantee of Placement.</strong> Shortlisting or interviews do not assure hiring or travel timelines.</li>
        <li><strong>Contract Relationship.</strong> Employment is strictly between employer and worker. SMC is not responsible for salary administration, issuance of benefits, or day‑to‑day management.</li>
        <li><strong>Disputes.</strong> Disputes arising from the employment contract are primarily between employer and worker, subject to the processes of competent authorities.</li>
      </ul>

      <h2 id="ip"><i class="fa-solid fa-copyright"></i>9) Intellectual Property</h2>
      <p>
        Unless otherwise stated, all text, graphics, logos, photographs, and software on this site are owned by or licensed to SMC.
        Any unauthorized reproduction, distribution, or derivative works are prohibited.
      </p>

      <h2 id="links"><i class="fa-solid fa-link"></i>10) Third‑Party Links</h2>
      <p>
        External links are provided for convenience. SMC does not control or endorse linked content and is not responsible for third‑party websites.
        Access to such sites is at your own risk.
      </p>

      <h2 id="privacy"><i class="fa-solid fa-user-shield"></i>11) Data & Privacy</h2>
      <p>
        SMC handles personal information with care consistent with applicable data‑protection principles and for legitimate recruitment purposes.
        For details on collection, use, retention, and your choices, please refer to our Privacy Policy.
      </p>

      <h2 id="conduct"><i class="fa-solid fa-shield-halved"></i>12) Prohibited Conduct</h2>
      <ul>
        <li>Submitting false information or forged documents</li>
        <li>Interfering with site operations, security, or access controls</li>
        <li>Using automated tools to harvest data without consent</li>
        <li>Any unlawful, deceptive, or abusive activity</li>
      </ul>

      <h2 id="mods"><i class="fa-solid fa-pen-to-square"></i>13) Changes to this Legal Notice</h2>
      <p>
        SMC may update this Legal Notice from time to time. Changes take effect upon posting. Continued use of the website constitutes acceptance of the latest version.
      </p>

      <h2 id="law"><i class="fa-solid fa-gavel"></i>14) Governing Law & Venue</h2>
      <p>
        This Legal Notice and your use of the website are governed by the laws of the Republic of the Philippines, without regard to conflict‑of‑law principles.
        You agree to the exclusive jurisdiction of the competent courts in the Philippines foreet, Sta. Ana, Barangay 866, City of Manila, NCR, Sixth District</li>
      </ul>

      <div class="cta-help">
        <strong><i class="fa-solid fa-circle-info me-1"></i>Need assistance?</strong><br/>
        Our Bahrain Program desk can guide you on documentation flows and timelines. Contact us for a clear checklist tailored to your case.
      </div>
    </div>
  </main>

  <!-- Footer -->
  <?php include __DIR__ . '/footer.php'; ?>

  <!-- JS -->
  https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js</script>
</body>
</html>