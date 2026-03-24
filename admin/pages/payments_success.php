<?php
require_once __DIR__ . '/includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Successful</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap (already used in your system) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #e8f5e9, #f1f8ff);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Segoe UI", Arial, sans-serif;
        }
        .success-card {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.12);
            padding: 50px 40px;
            max-width: 520px;
            width: 100%;
            text-align: center;
        }
        .success-icon {
            font-size: 64px;
            color: #198754;
            margin-bottom: 15px;
        }
        .success-title {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .success-text {
            color: #555;
            font-size: 15px;
            margin-bottom: 30px;
        }
        .btn-back {
            padding: 12px 26px;
            font-weight: 600;
            border-radius: 10px;
        }
        .small-note {
            margin-top: 25px;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>

<div class="success-card">
    <div class="success-icon">✅</div>

    <div class="success-title">
        Payment Successful
    </div>

    <p class="success-text">
        Thank you! Your payment has been received and processed successfully.<br>
        You may now close this page.
    </p>

    <div class="d-grid gap-2">
        <a href="<?= APP_URL ?>/pages/payments_clients.php"
           class="btn btn-success btn-back">
            Back to Admin Dashboard
        </a>
    </div>

    <div class="small-note">
        This page is shown after successful payment confirmation.
    </div>
</div>

</body>
</html>