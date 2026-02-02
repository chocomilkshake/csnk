<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/Applicant.php';
require_once '../includes/functions.php';

$database = new Database();
$auth = new Auth($database);
$auth->requireLogin();

$applicant = new Applicant($database);

$type = $_GET['type'] ?? 'all';

switch ($type) {
    case 'pending':
        $applicants = $applicant->getAll('pending');
        $filename = 'Pending_Applicants';
        break;
    case 'on_process':
        $applicants = $applicant->getAll('on_process');
        $filename = 'On_Process_Applicants';
        break;
    case 'all':
    default:
        $applicants = $applicant->getAll();
        $filename = 'All_Applicants';
        break;
}

$filename .= '_' . date('Y-m-d_His') . '.xls';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo '<?xml version="1.0"?>';
echo '<?mso-application progid="Excel.Sheet"?>';
echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:x="urn:schemas-microsoft-com:office:excel"
    xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:html="http://www.w3.org/TR/REC-html40">';

echo '<Worksheet ss:Name="Applicants">';
echo '<Table>';

echo '<Row>';
echo '<Cell><Data ss:Type="String">ID</Data></Cell>';
echo '<Cell><Data ss:Type="String">First Name</Data></Cell>';
echo '<Cell><Data ss:Type="String">Middle Name</Data></Cell>';
echo '<Cell><Data ss:Type="String">Last Name</Data></Cell>';
echo '<Cell><Data ss:Type="String">Suffix</Data></Cell>';
echo '<Cell><Data ss:Type="String">Phone Number</Data></Cell>';
echo '<Cell><Data ss:Type="String">Email</Data></Cell>';
echo '<Cell><Data ss:Type="String">Date of Birth</Data></Cell>';
echo '<Cell><Data ss:Type="String">Address</Data></Cell>';
echo '<Cell><Data ss:Type="String">Educational Attainment</Data></Cell>';
echo '<Cell><Data ss:Type="String">Work History</Data></Cell>';
echo '<Cell><Data ss:Type="String">Preferred Location</Data></Cell>';
echo '<Cell><Data ss:Type="String">Status</Data></Cell>';
echo '<Cell><Data ss:Type="String">Date Applied</Data></Cell>';
echo '</Row>';

foreach ($applicants as $app) {
    echo '<Row>';
    echo '<Cell><Data ss:Type="Number">' . htmlspecialchars($app['id']) . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($app['first_name']) . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($app['middle_name']) . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($app['last_name']) . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($app['suffix']) . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($app['phone_number']) . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($app['email']) . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($app['date_of_birth']) . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($app['address']) . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($app['educational_attainment']) . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($app['work_history']) . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($app['preferred_location']) . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $app['status']))) . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars(formatDate($app['created_at'])) . '</Data></Cell>';
    echo '</Row>';
}

echo '</Table>';
echo '</Worksheet>';
echo '</Workbook>';

$auth->logActivity($_SESSION['admin_id'], 'Export Excel', "Exported $type applicants list");
exit();
