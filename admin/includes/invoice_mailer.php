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

                <tr>apshot</p>
                                    <table role='presentation' width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td width='50%' style='padding:0 10px 14px 0;vertical-align:top;'>
                                            </td>
                                            <td width='50%' style='padding:0 0 14px 10px;vertical-align:top;'>
                                                <div style='background:#ffffff;border-radius:16px;border:1px solid #eadede;padding:16px;'>
                                                    <div style='width:36px;height:36px;line-height:36px;text-align:center;background:{$accentSoft};border-radius:50%;font-size:18px;color:{$accent};font-weight:700;'>@</div>
                                                    <p style='margin:12px 0 4px;font-size:12px;letter-spacing:0.8px;text-transform:uppercase;color:#6b7280;'>Reply To</p>
                                                    <p style='margin:0;font-size:15px;font-weight:700;color:#111827;'>{$safeSupportEmail}</p>
                                                </div>
                                            </td
        logInvoiceMailerEvent("SUCCESS {$companyType} {$invoiceNumber} -> {$toEmail}");
        return true;

    } catch (Throwable $e) {
        setLastInvoiceMailerError($e->getMessage());
        logInvoiceMailerEvent("FAIL {$companyType} {$invoiceNumber} -> {$e->getMessage()}");
        return false;
    }
}