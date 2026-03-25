<?php
// payments_success.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Successful</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- ✅ CORRECT BOOTSTRAP LOADING -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
:root {
  --success: #22c55e;
  --bg: #f8fafc;
  --text: #0f172a;
  --muted: #64748b;
}

body {
  background: radial-gradient(circle at top, #eef2ff, var(--bg));
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: Inter, system-ui, -apple-system, Segoe UI, sans-serif;
}

.payment-card {
  background: #ffffff;
  border-radius: 24px;
  padding: 48px 42px;
  width: 100%;
  max-width: 440px;
  box-shadow: 0 30px 80px rgba(0,0,0,.12);
  text-align: center;
}

.status-icon {
  width: 84px;
  height: 84px;
  background: var(--success);
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 40px;
  margin: 0 auto 24px;
}

h1 {
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--text);
}

p {
  color: var(--muted);
  font-size: .95rem;
}

.footer-note {
  margin-top: 20px;
  font-size: .85rem;
  color: var(--muted);
}

.close-btn {
  margin-top: 28px;
  padding: 10px 28px;
  border-radius: 999px;
  border: 1px solid #cbd5e1;
  background: transparent;
  font-weight: 600;
}
</style>
</head>

<body>

<div class="payment-card">
  <div class="status-icon">✓</div>

  <h1>Payment Successful</h1>
  <p class="mt-2">
    Thank you. Your payment has been received successfully.
  </p>

  <div class="footer-note">
    No further action is required.<br>
    You may safely close this page.
  </div>

  <button class="close-btn" onclick="window.close()">
    Close this page
  </button>
</div>

</body>
</html>