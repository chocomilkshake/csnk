<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/config.php';

// ========== PHPMailer Loader ==========
$composerAutoload = __DIR__ . '/../../vendor/autoload.php';
if (is_readable($composerAutoload)) {
    require_once $composerAutoload;
} else {
    require_once __DIR__ . '/../../lib/phpmailer/src/Exception.php';
    require_once __DIR__ . '/../../lib/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../../lib/phpmailer/src/SMTP.php';
}

/* ======================================================
   LOGGER
====================================================== */
function logInvoiceMailerEvent(string $message): void
{
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    file_put_contents(
        $logDir . '/invoice_mailer.log',
        '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
        FILE_APPEND
    );
}

function setLastInvoiceMailerError(string $message): void
{
    $GLOBALS['last_invoice_mailer_error'] = $message;
}

function getLastInvoiceMailerError(): string
{
    return trim((string) ($GLOBALS['last_invoice_mailer_error'] ?? ''));
}

/* ======================================================
   SMTP CONFIG
====================================================== */
function getSmtpConfig(string $companyType): array
{
    if (strtoupper($companyType) === 'SMC') {
        return [
            'host'     => SMTP_HOST,
            'port'     => SMTP_PORT,
            'username' => SMC_SMTP_USER,
            'password' => SMC_SMTP_PASS,
            'from'     => SMC_FROM_EMAIL,
            'fromName' => 'SMC Manpower Agency Billing',
        ];
    }

    return [
        'host'     => SMTP_HOST,
        'port'     => SMTP_PORT,
        'username' => SMTP_USER,
        'password' => SMTP_PASS,
        'from'     => SMTP_FROM_EMAIL,
        'fromName' => 'CSNK Manpower Agency Billing',
    ];
}

/* ======================================================
   SEND INVOICE EMAIL
====================================================== */
function sendInvoiceEmail(
    string $toEmail,
    string $clientName,
    string $invoiceNumber,
    string $pdfPath,
    string $companyType,
    ?string $paymentLink = null
): bool {

    $smtp = getSmtpConfig($companyType);
    $mail = new PHPMailer(true);
    setLastInvoiceMailerError('');

    try {
        /* SMTP */
        $mail->isSMTP();
        $mail->Host       = $smtp['host'];
        $mail->Port       = SMTP_PORT;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['username'];
        $mail->Password   = $smtp['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ];

        $mail->setFrom($smtp['from'], $smtp['fromName']);
        $mail->addAddress($toEmail, $clientName);
        $mail->addReplyTo($smtp['from'], $smtp['fromName']);

        if (is_readable($pdfPath)) {
            $mail->addAttachment($pdfPath, "Invoice-{$invoiceNumber}.pdf");
        }

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = "Invoice {$invoiceNumber} | {$companyType} Agency";

        /* THEME + LOGO */
        $year = date('Y');

        $siteBaseUrl = preg_replace('#/admin/?$#', '', APP_URL);

        if (strtoupper($companyType) === 'SMC') {
            $headerBg = '#0a1f44'; // Navy
            $accent   = '#d4af37'; // Gold
            $title    = 'SMC Manpower Agency Philippines Co.';
            $logoTag = "<img src='" . $siteBaseUrl . "/resources/img/smcbrandname.png'
                alt='SMC Logo'
                style='max-width:95px;height:auto;display:block;'>";
        } else {
            $headerBg = '#8b0000'; // CSNK Red
            $accent   = '#c4161c';
            $title    = 'CSNK Manpower Agency';
            $logoTag = "<img src='" . $siteBaseUrl . "/resources/img/csnklogo.png'
                alt='CSNK Logo'
                style='max-width:95px;height:auto;display:block;'>";
        }

        $payBtn = '';
        if ($paymentLink) {
            $payBtn = "
                <div style='text-align:center;margin:26px 0;'>
                    <a href='{$paymentLink}' target='_blank'
                       style='background:{$accent};
                              color:#ffffff;
                              padding:14px 32px;
                              text-decoration:none;
                              border-radius:6px;
                              font-weight:bold;
                              display:inline-block;'>
                        💳 Pay Invoice Securely
                    </a>
                </div>
            ";
        }

        /* EMAIL BODY */
        $mail->Body = "
<!DOCTYPE html>
<html>
<body style='margin:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;'>
<table width='100%' style='padding:40px 0;'>
<tr><td align='center'>

<table width='620' style='background:#ffffff;border-radius:12px;
box-shadow:0 10px 30px rgba(0,0,0,.15);overflow:hidden;'>

<tr>
<td style='background:{$headerBg};padding:22px;color:#ffffff;'>
<table width='100%'>
<tr>
<td width='110' valign='middle'>{$logoTag}</td>
<td valign='middle'>
    <h2 style='margin:0;font-size:20px;'>{$title}</h2>
    <p style='margin:6px 0 0;font-size:13px;'>Billing & Accounts Department</p>
</td>
</tr>
</table>
</td>
</tr>

<tr>
<td style='padding:32px;color:#333;font-size:15px;'>
<p>Good day <strong>{$clientName}</strong>,</p>

<p>Please find attached your <strong>official invoice</strong>
from <strong>{$companyType} Agency</strong>.</p>

<table width='100%' style='background:#fafafa;border-radius:8px;
border:1px solid #e6e6e6;margin:20px 0;'>
<tr>
<td style='padding:16px;font-size:14px;'>
<strong>Invoice Number:</strong> {$invoiceNumber}<br>
<strong>Agency:</strong> {$companyType} Agency<br>
<strong>Attachment:</strong> PDF Invoice
</td>
</tr>
</table>

{$payBtn}

<div style='background:#fff5eb;border-left:4px solid {$accent};
padding:14px;margin-top:20px;font-size:14px;'>
<strong>Payment Confirmation:</strong><br>
After payment, kindly reply to this email with your receipt or screenshot.
</div>

<p style='margin-top:26px;'>Sincerely,<br>
<strong>{$title}</strong><br>Billing Department</p>
</td>
</tr>

<tr>
<td style='background:#f1f1f1;text-align:center;font-size:12px;color:#666;padding:16px;'>
© {$year} {$title}. All rights reserved.
</td>
</tr>

</table>
</td></tr>
</table>
</body>
</html>
";

        $mail->AltBody =
            "Invoice {$invoiceNumber}\n\n" .
            "Please see attached invoice.\n" .
            ($paymentLink ? "Payment link: {$paymentLink}\n\n" : "") .
            "Reply with receipt after payment.\n\n{$title}";

        $mail->send();
        logInvoiceMailerEvent("SUCCESS {$companyType} {$invoiceNumber} → {$toEmail}");
        return true;

    } catch (Throwable $e) {
        setLastInvoiceMailerError($e->getMessage());
        logInvoiceMailerEvent("FAIL {$companyType} {$invoiceNumber} → {$e->getMessage()}");
        return false;
    }
}