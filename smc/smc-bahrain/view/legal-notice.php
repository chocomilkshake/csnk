<?php
session_start();
$page = 'legal';
?>
<!doctype html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Legal Notice — SMC / CSNK Manpower Agency | Bahrain Program</title>

  <!-- SEO -->
  <meta name="description" content="Legal Notice for CSNK Manpower Agency / SMC Manpower Agency. Compliance-first, ethical overseas recruitment for Bahrain. Fully licensed, transparent, responsible manpower services.">
  <meta name="theme-color" content="#0B1F3A">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- Styles -->
  <style>

    .page-header h1 {
      font-weight: 800;
      font-size: clamp(1.8rem, 5vw, 2.8rem);
    }

    .page-header .sub {
      opacity: .85;
      font-size: 1.05rem;
    }

    /* Legal Content Card */
    .legal-card {
      background: var(--card-bg);
      border
    .cta-legal strong {
      color: var(--navy);
    }

    /* Contact list */
    .contact-list li strong {
      color: var(--navy);
    }

  </style>
</head>

<body>

  <!-- NAVBAR -->
  <?php include __DIR__ . '/navbar.php'; ?>

  <!-- PAGE HEADER -->
  <section class="page-header">
    <h1>Legal Notice</h1>
    <div class="sub">CSNK / SMC Manpower Agency — Bahrain Program</div>
  </section>

  <!-- MAIN CONTENT -->
  <main class="container pb-5">
    <div class="legal-card">

      <h2><i class="fa-solid fa-circle-exclamation"></i> 4. Disclaimer</h2>
      <p>
        All materials are provided “as is.” CSNK / SMC Manpower Agency makes no warranties regarding accuracy, completeness, or reliability of any information displayed on the website.
      </p>

      <h2><i class="fa-solid fa-ban"></i> 5. Limitations</h2>
      <p>
        The agency shall not be liable for damages arising from website use, including loss of data, business interruptions, or consequential damages—even if notified of potential risks.
      </p>

      <h2><i class="fa-solid fa-clipboard-question"></i> 6. Accuracy of Information</h2>
      <p>
        Website materials may contain typographical or technical errors. We reserve the right to modify content at any time without prior notice.
      </p>

      <h2><i class="fa-solid fa-link"></i> 7. External Links</h2>
      <p>
        Linked sites are not operated by CSNK / SMC. We are not responsible for external content. Visiting third‑party sites is done at the user’s own risk.
      </p>

      <h2><i class="fa-solid fa-gear"></i> 8. Modifications to Terms</h2>
      <p>
        Terms of service may be updated at any time. Continued use of the website signifies acceptance of the latest version.
      </p>

      <h2><i class="fa-solid fa-gavel"></i> 9. Governing Law</h2>
      <p>
        This Legal Notice is governed by the laws of the Republic of the Philippines. Users agree to the exclusive jurisdiction of Philippine courts.
      </p>

      <h2><i class="fa-solid fa-briefcase"></i> 10. Employment Services Disclaimer</h2>
      <p>e-circle-check"></i> 13. Contact Information</h2>
      <p>If you have questions about this Legal Notice, contact us:</p>
      <ul class="contact-list">
        <li><strong>Email:</strong> <a href="mailto:csnkmanila06@gmail.com">csnkmanila06@gmail.com</a></li>
        <li><strong>Phone:</strong> 0945 657 0878</li>
        <li><strong>Address:</strong> Ground Floor Unit 1 Eden Townhouse, 2001 Eden St. Cor Pedro Gil, Sta. Ana, Manila</li>
      </ul>

      <div class="cta-legal mt-4">
        <strong>Need assistance?</strong><br>
        Our Bahrain Program support team is ready to help with compliance, documentation, or employment inquiries.
      </div>

    </div><!-- /legal-card -->
  </main>

  <!-- FOOTER -->
  <?php include __DIR__ . '/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>