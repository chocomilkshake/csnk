<?php
session_start();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Legal Notice - CSNK Manpower Agency</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    :root {
      --accent-red: #D72638;
      --ink: #111111;
      --muted-ink: #6c757d;
    }
    body {
      background: #f8f9fa;
      color: var(--ink);
    }
    .content-section {
      background: white;
      border-radius: 8px;
      padding: 2rem;
      margin-bottom: 2rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    h1 {
      color: var(--accent-red);
      margin-bottom: 1rem;
    }
    h2 {
      color: var(--accent-red);
      font-size: 1.5rem;
      margin-top: 2rem;
      margin-bottom: 1rem;
    }
    .last-updated {
      color: var(--muted-ink);
      font-style: italic;
      margin-bottom: 2rem;
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header>
    <?php $page = 'legal'; include __DIR__ . '/navbar.php'; ?>
  </header>

  <!-- Main Content -->
  <main class="container py-5">
    <div class="content-section">
      <h1>Legal Notice</h1>
      <p class="last-updated">Last Updated: February 2026</p>

      <h2>1. Website Operator</h2>
      <p>
        <strong>Company Name:</strong> CSNK Manpower Agency<br>
        <strong>Address:</strong> Ground Floor Unit 1 Eden Townhouse, 2001 Eden St. Cor Pedro Gil, Sta Ana, Barangay 866, City of Manila, NCR, Sixth District<br>
        <strong>Email:</strong> csnkmanila06@gmail.com<br>
        <strong>Phone:</strong> 0945 657 0878
      </p>

      <h2>2. Terms of Use</h2>
      <p>
        By accessing and using this website, you accept and agree to be bound by the terms and provision of this agreement. 
        If you do not agree to abide by the above, please do not use this service.
      </p>

      <h2>3. Use License</h2>
      <p>
        Permission is granted to temporarily download one copy of the materials (information or software) on our website 
        for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, 
        and under this license you may not:
      </p>
      <ul>
        <li>Modify or copy the materials</li>
        <li>Use the materials for any commercial purpose or for any public display</li>
        <li>Attempt to reverse engineer, decompile, or disassemble any software contained on the website</li>
        <li>Remove any copyright or other proprietary notations from the materials</li>
        <li>Transfer the materials to another person or "mirror" the materials on any other server</li>
        <li>Use automated tools or scripts to access our website without authorization</li>
      </ul>

      <h2>4. Disclaimer</h2>
      <p>
        The materials on our website are provided on an 'as is' basis. CSNK Manpower Agency makes no warranties, 
        expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, 
        implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.
      </p>

      <h2>5. Limitations</h2>
      <p>
        In no event shall CSNK Manpower Agency or its suppliers be liable for any damages (including, without limitation, 
        damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use 
        the materials on our website, even if we or our authorized representative has been notified orally or in writing 
        of the possibility of such damage.
      </p>

      <h2>6. Accuracy of Materials</h2>
      <p>
        The materials appearing on our website could include technical, typographical, or photographic errors. 
        CSNK Manpower Agency does not warrant that any of the materials on our website are accurate, complete, or current. 
        CSNK Manpower Agency may make changes to the materials contained on our website at any time without notice.
      </p>

      <h2>7. Links</h2>
      <p>
        CSNK Manpower Agency has not reviewed all of the sites linked to its website and is not responsible for the contents 
        of any such linked site. The inclusion of any link does not imply endorsement by CSNK Manpower Agency of the site. 
        Use of any such linked website is at the user's own risk.
      </p>

      <h2>8. Modifications</h2>
      <p>
        CSNK Manpower Agency may revise these terms of service for its website at any time without notice. 
        By using this website, you are agreeing to be bound by the then current version of these terms of service.
      </p>

      <h2>9. Governing Law</h2>
      <p>
        These terms and conditions are governed by and construed in accordance with the laws of the Republic of the Philippines, 
        and you irrevocably submit to the exclusive jurisdiction of the courts in that location.
      </p>

      <h2>10. Employment Services Disclaimer</h2>
      <p>
        CSNK Manpower Agency acts as a placement and recruitment service provider. We connect qualified candidates with 
        potential employers. We do not guarantee employment or any specific outcome. All employment relationships are 
        between the candidate and the employer. We are not responsible for:
      </p>
      <ul>
        <li>Employment disputes or disagreements between candidates and employers</li>
        <li>Wage or salary payment issues</li>
        <li>Workplace safety or compliance matters</li>
        <li>Non-payment or financial issues arising from employment</li>
      </ul>

      <h2>11. Data Protection</h2>
      <p>
        CSNK Manpower Agency is committed to complying with data protection laws. Information provided to us is handled 
        with appropriate security measures. For more details, please refer to our Privacy Policy.
      </p>

      <h2>12. Intellectual Property Rights</h2>
      <p>
        All content, including text, graphics, logos, images, and software, on our website is the property of CSNK Manpower Agency 
        or its content suppliers and is protected by international copyright laws. Unauthorized reproduction or distribution is prohibited.
      </p>

      <h2>13. Contact Information</h2>
      <p>
        If you have any questions regarding this Legal Notice, please contact us at:
      </p>
      <ul>
        <li><strong>Email:</strong> <a href="mailto:csnkmanila06@gmail.com">csnkmanila06@gmail.com</a></li>
        <li><strong>Phone:</strong> 0945 657 0878</li>
        <li><strong>Address:</strong> Ground Floor Unit 1 Eden Townhouse, 2001 Eden St. Cor Pedro Gil, Sta Ana, Barangay 866, City of Manila, NCR, Sixth District</li>
      </ul>
    </div>
  </main>

  <!-- Footer -->
  <footer>
    <?php include __DIR__ . '/footer.php'; ?>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
