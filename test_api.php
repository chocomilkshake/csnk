<?php
// Simple test to check get_applicants.php
echo "Testing get_applicants.php API...\n\n";

$url = 'http://localhost/csnk-1/includes/get_applicants.php';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$result = file_get_contents($url, false, $context);

if ($result === false) {asdasdasdas
    echo "ERROR: Could not fetch from API\n";
    echo "Check if Apache/XAMPP is running and the path is correct\n";
} else {
    $data = json_decode($result, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "ERROR: Invalid JSON response\n";
        echo "Raw response: " . substr($result, 0, 500) . "...\n";
    } else {
        echo "SUCCESS: API returned " . count($data) . " applicants\n\n";
        if (count($data) > 0) {
            echo "First applicant sample:\n";
            print_r($data[0]);
        } else {
            echo "No applicants found in database\n";
        }
    }
}
?>
