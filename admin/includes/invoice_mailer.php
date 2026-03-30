<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* ======================================================
   LOAD CONFIG & PHPMailer
====================================================== */
require_once __DIR__ . '/config.php';

// Load PHPMailer (Composer first, fallback second)
$composerAutoload = __DIR__ . '/../../vendor/autoload.php';

if (is_readable($composerAutoload)) {
    require_once $composerAutoload;
} else {
    require_once __DIR__ . '/../../lib/phpmailer/src/Exception.php';
    require_once __DIR__ . '/../../lib/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../../lib/phpmailer/src/SMTP.php';
}

/* ======================================================
   SEND CSNK INVOICE EMAIL
====================================================== */
/**
 * @param string      $toEmail
 * @param string      $clientName
 * @param string      $invoiceNumber
 * @param string      $pdfPath
 * @param string      $companyType
 * @param string|null $paymentLink
 *
 * @return bool
 */
function sendInvoiceEmail(
    string $toEmail,
    string $clientName,
    string $invoiceNumber,
    string $pdfPath,
    string $companyType = 'CSNK',
    ?string $paymentLink = null
): bool {

    $mail = new PHPMailer(true);

    try {

        /* ================= SMTP SETTINGS ================= */
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        /* ================= EMAIL HEADERS ================= */
        $mail->setFrom(SMTP_FROM_EMAIL, 'CSNK Manpower Agency Billing');
        $mail->addAddress($toEmail, $clientName);

        /* ================= ATTACH INVOICE ================= */
        if (!empty($pdfPath) && is_readable($pdfPath)) {
            $mail->addAttachment($pdfPath);
        }

        /* ================= META ================= */
        $mail->isHTML(true);
        $mail->Subject = "Invoice {$invoiceNumber} | CSNK Manpower Agency";

        $year = date('Y');

        /* ================= PAY BUTTON (WORKING) ================= */
        $payButtonHtml = '';

        if (!empty($paymentLink)) {
            $payButtonHtml = '
                <div style="margin:34px 0;text-align:center;">
                    <a href="' . $paymentLink . '" target="_blank"
                       style="
                           display:inline-block;
                           padding:14px 36px;
                           background:#c4161c;
                           color:#ffffff;
                           text-decoration:none;
                           border-radius:8px;
                           font-size:15px;
                           font-weight:bold;
                           box-shadow:0 6px 18px rgba(196,22,28,0.35);
                       ">
                        💳 Pay Invoice Securely
                    </a>
                </div>
            ';
        }

        /* ================= EMAIL BODY ================= */
        $mail->Body = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Invoice {$invoiceNumber}</title>
</head>

<body style='margin:0;padding:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;'>

<table width='100%' cellpadding='0' cellspacing='0' style='padding:48px 0;'>
<tr>
<td align='center'>

    <table width='620' cellpadding='0' cellspacing='0'
           style='background:#ffffff;border-radius:14px;
                  box-shadow:0 12px 36px rgba(0,0,0,0.14);
                  overflow:hidden;'>

        <!-- HEADER -->
        <tr>
            <td style='
                background:linear-gradient(135deg,#c4161c,#6d0f14);
                padding:30px 32px;
                color:#ffffff;
            '>
                <h1 style='margin:0;font-size:24px;font-weight:bold;'>
                    CSNK Manpower Agency
                </h1>
                <p style='margin:6px 0 0;font-size:14px;opacity:0.95;'>
                    Billing & Accounts Department
                </p>
            </td>
        </tr>

        <!-- CONTENT -->
        <tr>
            <td style='padding:36px 32px;color:#333;font-size:15px;line-height:1.7;'>

                <p>
                    Good day <strong>{$clientName}</strong>,
                </p>

                <p>
                    Thank you for your continued trust in
                    <strong>CSNK Manpower Agency</strong>.
                    Please find attached your official invoice for this transaction,
                    provided for your reference and accounting records.
                </p>

                <!-- INVOICE DETAILS -->
                <table width='100%' cellpadding='0' cellspacing='0'
                       style='background:#fafafa;border-radius:10px;
                              border:1px solid #e6e6e6;margin:22px 0;'>

                    <tr>
                        <td style='padding:18px;font-size:14px;'>
                            <strong>Invoice Number:</strong> {$invoiceNumber}<br>
                            <strong>Agency:</strong> CSNK Manpower Agency<br>
                            <strong>Attachment:</strong> PDF Invoice
                        </td>
                    </tr>
    }
}