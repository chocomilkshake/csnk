<?php
// FILE: admin/pages/replace-search.php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/functions.php';
require_once '../includes/Applicant.php';

$database = new Database();
$auth     = new Auth($database);
$auth->requireLogin();

header('Content-Type: application/json; charset=UTF-8');

$replacementId = isset($_GET['replacement_id']) ? (int)$_GET['replacement_id'] : 0;
$originalId    = isset($_GET['original_applicant_id']) ? (int)$_GET['original_applicant_id'] : 0;
$limit         = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
if ($limit <= 0 || $limit > 1000) $limit = 50;

$applicant = new Applicant($database);

// Resolve original applicant via replacement if not provided
if ($originalId <= 0) {
    if ($replacementId <= 0) {
        echo json_encode(['ok' => false, 'message' => 'Missing replacement_id or original_applicant_id.']); exit;
    }
    $rep = $applicant->getReplacementById($replacementId);
    if (!$rep) { echo json_encode(['ok' => false, 'message' => 'Replacement not found.']); exit; }
    $originalId = (int)$rep['original_applicant_id'];
}

$candidates = $applicant->searchPendingCandidatesForReplacement($originalId, $limit);

// Build response
$out = [];
foreach ($candidates as $row) {
    $skillsArr = json_to_array_safe($row['specialization_skills'] ?? '[]');
    $citiesArr = json_to_array_safe($row['preferred_location'] ?? '[]');

    $out[] = [
        'id'                 => (int)$row['id'],
        'first_name'         => (string)($row['first_name'] ?? ''),
        'middle_name'        => (string)($row['middle_name'] ?? ''),
        'last_name'          => (string)($row['last_name'] ?? ''),
        'suffix'             => (string)($row['suffix'] ?? ''),
        'full_name'          => getFullName($row['first_name'] ?? '', $row['middle_name'] ?? '', $row['last_name'] ?? '', $row['suffix'] ?? ''),
        'email'              => (string)($row['email'] ?? ''),
        'phone_number'       => (string)($row['phone_number'] ?? ''),
        'years_experience'   => (int)($row['years_experience'] ?? 0),
        'employment_type'    => (string)($row['employment_type'] ?? ''),
        'education_level'    => (string)($row['education_level'] ?? ''),
        'picture_url'        => !empty($row['picture']) ? getFileUrl($row['picture']) : null,
        '_score'             => (int)($row['_score'] ?? 0),
        'specialization_skills' => $skillsArr,
        'preferred_location'    => $citiesArr,
        'created_at'         => (string)($row['created_at'] ?? ''),
    ];
}

echo json_encode(['ok' => true, 'original_applicant_id' => $originalId, 'count' => count($out), 'data' => $out], JSON_UNESCAPED_UNICODE);