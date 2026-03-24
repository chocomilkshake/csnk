<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== CLIENT DEBUG ===\n";

echo "1. Raw client_bookings:\n";
$q1 = "SELECT id, client_email, client_first_name, client_last_name, status, business_unit_id FROM client_bookings WHERE deleted_at IS NULL";
$r1 = $conn->query($q1);
if ($r1) {
    while ($row = $r1->fetch_assoc()) {
        echo sprintf("  ID:%d | %s %s | %s | status:'%s' | bu:%d\n", 
            $row['id'], $row['client_first_name'], $row['client_last_name'], 
            $row['client_email'], $row['status'], $row['business_unit_id']);
    }
} else echo "Query1 failed\n";

echo "\n2. Main client query result (payment_invoice_gen):\n";
$q2 = "
    SELECT cb.client_email,
           CONCAT(cb.client_first_name,' ',cb.client_last_name) AS client_name,
           cb.business_unit_id, bu.name as bu_name
    FROM client_bookings cb
    JOIN business_units bu ON bu.id = cb.business_unit_id
    JOIN agencies ag ON ag.id = bu.agency_id
    WHERE cb.status IN ('submitted','confirmed','on_process','approved')
    GROUP BY cb.client_email, cb.client_first_name, cb.client_last_name, cb.business_unit_id
";
$r2 = $conn->query($q2);
$count2 = $r2 ? $r2->num_rows : 0;
echo "FOUND: $count2 clients\n";
if ($r2) {
    while ($row = $r2->fetch_assoc()) {
        echo sprintf("  %s | BU:%d (%s)\n", $row['client_name'], $row['business_unit_id'], $row['bu_name']);
        $apps = get_client_applicants($conn, $row['client_email']);
        echo sprintf("    Applicants: %d\n", count($apps));
    }
} else echo "Query2 failed: " . $conn->error . "\n";

echo "\n3. business_units check:\n";
$q3 = "SELECT id, name, agency_id FROM business_units";
$r3 = $conn->query($q3);
if ($r3) {
    while ($row = $r3->fetch_assoc()) {
        echo sprintf("  BU%d: %s (agency %d)\n", $row['id'], $row['name'], $row['agency_id']);
    }
} else echo "Query3 failed\n";

echo "\n4. agencies check:\n";
$q4 = "SELECT * FROM agencies";
$r4 = $conn->query($q4);
if ($r4) {
    while ($row = $r4->fetch_assoc()) {
        echo sprintf("  Agency %d: %s (%s)\n", $row['id'], $row['name'], $row['code']);
    }
} else echo "Query4 failed\n";

echo "\n=== END DEBUG ===\n";
?>

