<?php
// This script updates approved.php to add agency filtering
$file = 'admin/pages/approved.php';
$content = file_get_contents($file);

// Find and replace the getAll call
$oldCode = '/** Load approved applicants */
$applicants = $applicant->getAll(\'approved\');';

$newCode = '/** Load approved applicants */
// Determine user\'s agency for filtering
$userAgency = $auth->getAgency(); // \'csnk\', \'smc\', or null for admin/super_admin

// For employees, restrict to their agency
$filterAgency = null;
if ($currentRole === \'employee\' && $userAgency !== null) {
    $filterAgency = $userAgency;
}

$applicants = $applicant->getAll(\'approved\', null, $filterAgency);';

$content = str_replace($oldCode, $newCode, $content);
file_put_contents($file, $content);
echo "approved.php updated successfully\n";
?>
