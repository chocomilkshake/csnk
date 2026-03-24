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
   SEND INVOICE EMAIL
====================================================== */
/**
 * @param string      $toEmail
 * @param string      $clientName
 * @param string      $invoiceNum
 * @param string      $pdfPath
 * @param string      $companyType  (CSNK | SMC)
 * @param string|null $paymentLink  (Xendit payment link)
 *
 * @return bool
 */
function sendInvoiceEmail(
    $toEmail,
    $clientName,
    $invoiceNum,
    $pdfPath,
    $companyType,
    $paymentLink = null
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

        /* ================= HEADERS ================= */
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $clientName);

        /* ================= ATTACHMENT ================= */
        if (!empty($pdfPath) && is_readable($pdfPath)) {
            $mail->addAttachment($pdfPath);
        }

        /* ================= EMAIL CONTENT ================= */
        $mail->isHTML(true);
        $mail->Subject = "Invoice {$invoiceNum} from {$companyType} Agency";

        $year = date('Y');

        /* ================= PAY NOW BUTTON ================= */
        $payButtonHtml = '';
        if (!empty($paymentLink)) {
            $payButtonHtml = '
                <div style="margin:30px 0;text-align:center;">
                    <a href="' . htmlspecialchars($paymentLink) . '" target="_blank"
                       style="
                            display:inline-block;
                            padding:14px 30px;
                            background:#0d6efd;
                            color:#ffffff;
                            text-decoration:none;
                            border-radius:6px;
                            font-size:16px;
                            font-weight:bold;">
                        💳 Pay Invoice Now
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
    <title>Invoice {$invoiceNum}</title>
</head>
<body style='margin:0;padding:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;'>

<table width='100%' cellpadding='0' cellspacing='0' style='padding:30px 0;'>
<tr>
<td align='center'>

<table width='600' cellpadding='0' cellspacing='0'
       style='background:#ffffff;border-radius:12px;
              box-shadow:0 8px 24px rgba(0,0,0,0.08);overflow:hidden;'>

<tr>
<td style='background:#0d6efd;padding:24px;text-align:center;color:#ffffff;'>
    <h1 style='margin:0;font-size:22px;'>📄 INVOICE NOTICE</h1>
    <p style='margin-top:6px;font-size:14px;'>{$companyType} Agency</p>
</td>
</tr>

<tr>
<td style='padding:30px;color:#333;'>

<p>Good day <strong>{$clientName}</strong>,</p>

<p>
Please find attached your official invoice from
<strong>{$companyType} Agency</strong>.
</p>

<table width='100%' style='background:#f8f9fa;border-radius:10px;margin:20px 0;'>
<tr>
<td style='padding:16px;font-size:14px;'>
<strong>Invoice Number:</strong> {$invoiceNum}<br>
<strong>Agency:</strong> {$companyType} Agency<br>
<strong>Attachment:</strong> PDF Invoice
</td>
</tr>
</table>

{$payButtonHtml}

<p>
If you have any questions, please contact our billing department.
</p>

<p style='margin-top:30px;'>
Warm regards,<br>
<strong>{$companyType} Agency</strong><br>
Billing Department
</p>

</td>
</tr>

<tr>
<td style='background:#f1f3f5;padding:16px;text-align:center;font-size:12px;color:#777;'>
This is an automated message.<br>
© {$year} {$companyType} Agency
</td>
</tr>

</table>

</td>
</tr>
</table>

</body>
</html>
";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('Invoice email error: ' . $e->getMessage());
        return false;
    }
}