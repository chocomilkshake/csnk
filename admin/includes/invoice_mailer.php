<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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