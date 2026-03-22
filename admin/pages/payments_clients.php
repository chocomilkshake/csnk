<?php
session_start();

/* ======================================================
   AJAX: Get Invoice Applicants (MUST BE FIRST)
====================================================== */
if (isset($_GET['get_invoice_applicants']) && isset($_GET['id'])) {
    header('Content-Type: application/json');

    require_once '../includes/config.php';
    require_once '../includes/Database.php';
    require_once '../includes/functions.php';

    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        echo json_encode([]);
        exit;
    }

    $invoice_id = (int) $_GET['id'];

    $stmt = $conn->prepare("
        SELECT applicants_data
        FROM invoice_history
        WHERE id = ?
    ");
    $stmt->bind_param('i', $invoice_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        echo json_encode([]);
        exit;
    }

    $decoded = json_decode($row['applicants_data'] ?? '[]', true);
    if (!is_array($decoded)) {
        echo json_encode([]);
        exit;
    }

    $enriched_apps = [];

    foreach ($decoded as $app) {
        $item = [
            'name'        => $app['name'] ?? 'Unknown Applicant',
            'start_date'  => $app['start_date'] ?? '',
            'end_date'    => $app['end_date'] ?? '',
            'days'        => isset($app['days']) ? (int)$app['days'] : 0,
            'amount'      => isset($app['amount']) ? (float)$app['amount'] : 0
        ];


        // Optional: enrich name from applicants table
        if (!empty($app['applicant_id'])) {
            $app_stmt = $conn->prepare("
                SELECT first_name, middle_name, last_name, suffix
                FROM applicants
                WHERE id = ?
            ");
            $app_stmt->bind_param('i', $app['applicant_id']);
            $app_stmt->execute();
            $a = $app_stmt->get_result()->fetch_assoc();

            if ($a) {
                $item['name'] = getFullName(
                    $a['first_name'],
                    $a['middle_name'],
                    $a['last_name'],
                    $a['suffix']
                );
            }
        }

        $enriched_apps[] = $item;
    }

    echo json_encode($enriched_apps);
    exit;
}

/* ======================================================
   NORMAL PAGE LOAD CONTINUES BELOW
====================================================== */

$pageTitle = 'Clients Invoices';

require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die('Database connection failed.');
}


/* ================= TAB FILTER ================= */
$activeTab = 'CSNK'; // default

if (isset($_GET['tab']) && $_GET['tab'] === 'SMC') {
    $activeTab = 'SMC';
}

/* ================= SEARCH ================= */
$q = '';
if (isset($_GET['q'])) {
    $q = trim((string) $_GET['q']);
    $_SESSION['invoices_q'] = $q;
} elseif (!empty($_SESSION['invoices_q'])) {
    $q = $_SESSION['invoices_q'];
}

if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    unset($_SESSION['invoices_q']);
    redirect('payments_clients.php');
    exit;
}

/* ================= FETCH INVOICES ================= */
$sql = "
    SELECT ih.*, NULL AS picture
    FROM invoice_history ih
    WHERE ih.client_name LIKE ?
      AND ih.invoice_num LIKE ?
    ORDER BY ih.id DESC
";

$stmt = $conn->prepare($sql);

$like   = '%' . $q . '%';
$prefix = $activeTab . '-%'; // CSNK-% or SMC-%

$stmt->bind_param('ss', $like, $prefix);
$stmt->execute()-no').textContent = d.ref;
        document.getElementById('pv-total').textContent = 
            parseFloat(d.total).toLocaleString('en-PH', { minimumFractionDigits: 2 });

        const tbody = document.getElementById('pv-items');
        tbody.innerHTML = '<tr><td colspan="4" class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Loading...</td></tr>';

        try {
            // Fetch enriched ="text-end fw-semibold">
                                ₱${parseFloat(app.amount || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}
                            </td>
                        </tr>
                    `);
                });
            }
        } catch (error) {
            console.error('Failed to load applicants:', error);
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-3">Failed to load applicants</td></tr>';
        }

        // Setup PDF download
        document.getElementById('downloadPdfBtn').onclick = () => {
            window.open('../../uploads/invoices/' + currentInvoiceData.pdf, '_blank');
        };
    });
});
</script>

<?php include '../includes/footer.php'; ?>