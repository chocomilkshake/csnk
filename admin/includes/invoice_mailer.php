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
                        </td>
                    </tr>
    }
}