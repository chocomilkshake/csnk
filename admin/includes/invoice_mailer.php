<?php
/**
 * ==========================================================
 * Invoice Mailer
 * ----------------------------------------------------------
 * Handles professional branded invoice delivery with
 * step-by-step payment instructions for clients.
 * ==========================================================
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/config.php';

/* ==========================================================
   PHPMailer Loader
========================================================== */
$composerAutoload = __DIR__ . '/../../vendor/autoload.php';
if (is_readable($composerAutoload)) {
    require_once $composerAutoload;
} else {
    require_once __DIR__ . '/../../lib/phpmailer/src/Exception.php';
    require_once __DIR__ . '/../../lib/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../../lib/phpmailer/src/SMTP.php';
}

/* ==========================================================
   LOGGING UTILITIES
========================================================== */
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

function resolveInvoiceMailerAssetPath(array $relativePaths): ?string
{
    $projectRoot = realpath(__DIR__ . '/../../');

    if ($projectRoot === false) {
        return null;
    }

    foreach ($relativePaths as $relativePath) {
        $fullPath = $projectRoot . '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
        if (is_readable($fullPath)) {
            return $fullPath;
        }
    }

    return null;
}

function embedInvoiceMailerImage(
    PHPMailer $mail,
    array $relativePaths,
    string $contentId,
    string $name
): ?string {
    $assetPath = resolveInvoiceMailerAssetPath($relativePaths);

    if ($assetPath === null) {
        return null;
    }

    try {
        $mail->addEmbeddedImage($assetPath, $contentId, $name);
        return 'cid:' . $contentId;
    } catch (Throwable $e) {
        logInvoiceMailerEvent('IMAGE EMBED FAIL ' . $name . ' -> ' . $e->getMessage());
        return null;
    }
}

/* ==========================================================
   SMTP CONFIGURATION
========================================================== */
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

/* ==========================================================
   MAIN EMAIL FUNCTION
========================================================== */
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
    $companyCode = strtoupper($companyType);

    try {

        /* ================= SMTP SETUP ================= */
        $mail->isSMTP();
        $mail->Host       = $smtp['host'];
        $mail->Port       = $smtp['port'];
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

        /* ================= HEADERS ================= */
        $mail->setFrom($smtp['from'], $smtp['fromName']);
        $mail->addAddress($toEmail, $clientName);
        $mail->addReplyTo($smtp['from'], $smtp['fromName']);

        if (is_readable($pdfPath)) {
            $mail->addAttachment($pdfPath, "Invoice-{$invoiceNumber}.pdf");
        }

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = "Invoice {$invoiceNumber} | {$companyType} Manpower Agency";

        /* ================= BRANDING ================= */
        $year = date('Y');
        $siteBaseUrl = preg_replace('#/admin/?$#', '', APP_URL);

        if ($companyCode === 'SMC') {
            $headerBg     = '#0f274b';
            $accent       = '#c8a85d';
            $accentSoft   = '#f6efe2';
            $panelBg      = '#f7f9fc';
            $title        = 'SMC Manpower Agency Philippines Co.';
            $supportEmail = SMC_FROM_EMAIL;
            $logoUrl      = "{$siteBaseUrl}/resources/img/smcbrandname.png";
            $logoSrc      = embedInvoiceMailerImage(
                $mail,
                ['resources/img/smcbrandname.png', 'admin/resources/img/smcbrandname.png'],
                'invoice_brand_logo',
                'smcbrandname.png'
            ) ?? $logoUrl;
        } else {
            $headerBg     = '#8b1e24';
            $accent       = '#d14b52';
            $accentSoft   = '#fff1f2';
            $panelBg      = '#faf7f7';
            $title        = 'CSNK Manpower Agency';
            $supportEmail = SMTP_FROM_EMAIL;
            $logoUrl      = "{$siteBaseUrl}/resources/img/csnklogo.png";
            $logoSrc      = embedInvoiceMailerImage(
                $mail,
                ['resources/img/csnklogo.png', 'admin/resources/img/csnklogo.png'],
                'invoice_brand_logo',
                'csnklogo.png'
            ) ?? $logoUrl;
        }

        $safeClientName = htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8');
        $safeInvoiceNumber = htmlspecialchars($invoiceNumber, ENT_QUOTES, 'UTF-8');
        $safeCompanyType = htmlspecialchars($companyType, ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeSupportEmail = htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8');

        /* ================= PAYMENT BUTTON ================= */
        $payButton = '';
        if ($paymentLink) {
            $safePaymentLink = htmlspecialchars($paymentLink, ENT_QUOTES, 'UTF-8');
            $payButton = "
<table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='margin:28px 0 10px;'>
    <tr>
        <td align='center'>
            <a href='{$safePaymentLink}' target='_blank'
               style='background:{$accent};color:#ffffff;padding:15px 28px;border-radius:999px;text-decoration:none;font-size:15px;font-weight:700;display:inline-block;'>
                View Secure Payment Page
            </a>
            <p style='margin:10px 0 0;font-size:12px;line-height:1.5;color:#6b7280;'>
                Use the secure payment page above or your approved payment channel.
            </p>
        </td>
    </tr>
</table>";
        }

        /* ================= EMAIL BODY ================= */
        $mail->Body = "
<!DOCTYPE html>
<html>
<body style='margin:0;padding:0;background-color:#eef2f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;'>
<table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background-color:#eef2f7;margin:0;padding:24px 12px;'>
    <tr>
        <td align='center'>
            <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='max-width:680px;background:#ffffff;border-radius:24px;overflow:hidden;'>
                <tr>
                    <td style='background:linear-gradient(135deg, {$headerBg} 0%, {$accent} 100%);padding:30px 32px 22px;color:#ffffff;'>
                        <table role='presentation' width='100%' cellpadding='0' cellspacing='0'>
                            <tr>
                                <td align='left' style='vertical-align:top;'>
                                    <table role='presentation' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td style='background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);border-radius:18px;padding:12px 16px;'>
                                                <img src='{$logoSrc}' alt='{$safeTitle} logo' style='display:block;max-width:160px;width:100%;height:auto;'>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td align='right' style='vertical-align:top;'>
                                    <div style='display:inline-block;background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);border-radius:999px;padding:8px 14px;font-size:12px;font-weight:700;letter-spacing:0.6px;text-transform:uppercase;'>
                                        Billing Notice
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <div style='padding-top:24px;'>
                            <p style='margin:0 0 10px;font-size:13px;letter-spacing:1px;text-transform:uppercase;opacity:0.85;'>
                                {$safeTitle}
                            </p>
                            <h1 style='margin:0;font-size:30px;line-height:1.2;font-weight:700;color:#ffffff;'>
                                Invoice {$safeInvoiceNumber}
                            </h1>
                            <p style='margin:12px 0 0;font-size:15px;line-height:1.7;color:rgba(255,255,255,0.92);max-width:480px;'>
                                Your invoice is attached and ready for review. We also included a simple payment guide below so the process stays smooth from start to confirmation.
                            </p>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style='padding:0 32px;'>
                        <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='margin-top:-22px;'>
                            <tr>
                                <td style='background:#ffffff;border:1px solid #e5e7eb;border-radius:20px;padding:18px 20px;box-shadow:0 12px 30px rgba(15,23,42,0.08);'>
                                    <table role='presentation' width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td style='font-size:13px;color:#6b7280;padding-bottom:8px;'>Recipient</td>
                                            <td style='font-size:13px;color:#6b7280;padding-bottom:8px;'>Agency</td>
                                            <td style='font-size:13px;color:#6b7280;padding-bottom:8px;'>Attachment</td>
                                        </tr>
                                        <tr>
                                            <td style='font-size:16px;font-weight:700;color:#111827;'>{$safeClientName}</td>
                                            <td style='font-size:16px;font-weight:700;color:#111827;'>{$safeCompanyType} Manpower Agency</td>
                                            <td style='font-size:16px;font-weight:700;color:#111827;'>PDF Invoice Included</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style='padding:30px 32px 18px;'>
                        <p style='margin:0 0 14px;font-size:16px;line-height:1.8;color:#374151;'>
                            Good day <strong>{$safeClientName}</strong>,
                        </p>
                        <p style='margin:0 0 14px;font-size:15px;line-height:1.8;color:#4b5563;'>
                            Attached to this email is your official invoice from <strong>{$safeTitle}</strong>. Please review the file and follow the payment steps below for faster posting and confirmation.
                        </p>

                        <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='margin:26px 0;background:{$panelBg};border:1px solid #e5e7eb;border-radius:20px;'>
                            <tr>
                                <td style='padding:24px;'>
                                    <p style='margin:0 0 18px;font-size:18px;font-weight:700;color:#111827;'>Invoice snapshot</p>
                                    <table role='presentation' width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td width='50%' style='padding:0 10px 14px 0;vertical-align:top;'>
                                                <div style='background:#ffffff;border-radius:16px;border:1px solid #eadede;padding:16px;'>
                                                    <div style='width:36px;height:36px;line-height:36px;text-align:center;background:{$accentSoft};border-radius:50%;font-size:18px;color:{$accent};font-weight:700;'>#</div>
                                                    <p style='margin:12px 0 4px;font-size:12px;letter-spacing:0.8px;text-transform:uppercase;color:#6b7280;'>Invoice Number</p>
                                                    <p style='margin:0;font-size:17px;font-weight:700;color:#111827;'>{$safeInvoiceNumber}</p>
                                                </div>
                                            </td>
                                            <td width='50%' style='padding:0 0 14px 10px;vertical-align:top;'>
                                                <div style='background:#ffffff;border-radius:16px;border:1px solid #eadede;padding:16px;'>
                                                    <div style='width:36px;height:36px;line-height:36px;text-align:center;background:{$accentSoft};border-radius:50%;font-size:18px;color:{$accent};font-weight:700;'>@</div>
                                                    <p style='margin:12px 0 4px;font-size:12px;letter-spacing:0.8px;text-transform:uppercase;color:#6b7280;'>Reply To</p>
                                                    <p style='margin:0;font-size:15px;font-weight:700;color:#111827;'>{$safeSupportEmail}</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td width='50%' style='padding:0 10px 0 0;vertical-align:top;'>
                                                <div style='background:#ffffff;border-radius:16px;border:1px solid #eadede;padding:16px;'>
                                                    <div style='width:36px;height:36px;line-height:36px;text-align:center;background:{$accentSoft};border-radius:50%;font-size:18px;color:{$accent};font-weight:700;'>P</div>
                                                    <p style='margin:12px 0 4px;font-size:12px;letter-spacing:0.8px;text-transform:uppercase;color:#6b7280;'>Payment</p>
                                                    <p style='margin:0;font-size:15px;font-weight:700;color:#111827;'>Use approved payment channel</p>
                                                </div>
                                            </td>
                                            <td width='50%' style='padding:0 0 0 10px;vertical-align:top;'>
                                                <div style='background:#ffffff;border-radius:16px;border:1px solid #eadede;padding:16px;'>
                                                    <div style='width:36px;height:36px;line-height:36px;text-align:center;bac
                            <strong>{$safeTitle}</strong><br>
                            Billing &amp; Accounts Department
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style='padding:24px 32px 30px;background:#f8fafc;border-top:1px solid #e5e7eb;'>
                        <table role='presentation' width='100%' cellpadding='0' cellspacing='0'>
                            <tr>
                                <td align='left' style='font-size:12px;line-height:1.7;color:#6b7280;'>
                                    {$safeTitle}<br>
                                    Reply to: {$safeSupportEmail}
                                </td>
                                <td align='right' style='font-size:12px;line-height:1.7;color:#9ca3af;'>
                                    &copy; {$year} {$safeTitle}<br>
                                    All rights reserved.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
";

        /* ================= TEXT FALLBACK ================= */
        $mail->AltBody =
            "Invoice {$invoiceNumber}\n\n" .
            "Hello {$clientName},\n\n" .
            "Your invoice PDF is attached to this email.\n\n" .
            "Payment Steps:\n" .
            "1. Review attached invoice\n" .
            "2. Make your payment\n" .
            "3. Complete transaction\n" .
            "4. Reply with proof of payment\n\n" .
            ($paymentLink ? "Payment link: {$paymentLink}\n\n" : '') .
            "Reply to: {$supportEmail}\n\n" .
            "{$title} Billing Department";

        /* ================= SEND ================= */
        $mail->send();
        logInvoiceMailerEvent("SUCCESS {$companyType} {$invoiceNumber} -> {$toEmail}");
        return true;

    } catch (Throwable $e) {
        setLastInvoiceMailerError($e->getMessage());
        logInvoiceMailerEvent("FAIL {$companyType} {$invoiceNumber} -> {$e->getMessage()}");
        return false;
    }
}