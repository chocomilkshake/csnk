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

// Always JSON + no-cache (avoid stale results)
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

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

// --- Pass 1: ask the scorer for more items up-front so UI has enough choices ---
$primaryFetchSize = max(200, $limit * 4); // pull more; scorer will sort & slice
$candidates = $applicant->searchPendingCandidatesForReplacement($originalId, $primaryFetchSize);

// If still very few (e.g., only 0–1), do a defensive open-net fallback (CSNK + pending, excluding original)
if (count($candidates) < $limit) {
    try {
        $conn = $database->getConnection();
        if ($conn instanceof mysqli) {
            // Find the original BU & country to keep scoring meaningful
            $origBuId = null;
            $origCountryId = null;
            if ($stmt = $conn->prepare("
                SELECT bu.id AS bu_id, bu.country_id AS country_id
                FROM applicants a
                JOIN business_units bu ON bu.id = a.business_unit_id
                WHERE a.id = ?
                LIMIT 1
            ")) {
                $stmt->bind_param('i', $originalId);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && ($row = $res->fetch_assoc())) {
                    $origBuId = (int)$row['bu_id'];
                    $origCountryId = (int)$row['country_id'];
                }
                $stmt->close();
            }

            // Pull a wider pool: all pending CSNK (excluding original & blacklisted)
            $widerLimit = max(300, $limit * 6);
            $sql = "
                SELECT a.*
                FROM applicants a
                JOIN business_units bu ON bu.id = a.business_unit_id
                JOIN agencies ag ON ag.id = bu.agency_id
                WHERE a.id <> ?
                  AND a.status = 'pending'
                  AND a.deleted_at IS NULL
                  AND ag.code = 'csnk'
                  AND NOT EXISTS (
                      SELECT 1 FROM blacklisted_applicants b
                      WHERE b.applicant_id = a.id AND b.is_active = 1
                  )
                ORDER BY a.created_at DESC
                LIMIT ?
            ";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param('ii', $originalId, $widerLimit);
                $stmt->execute();
                $res = $stmt->get_result();
                $wideRows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
                $stmt->close();

                // Merge unique by id, preserving items we already had from the scorer
                $byId = [];
                foreach ($candidates as $r) { $byId[(int)$r['id']] = $r; }
                foreach ($wideRows as $r) {
                    $id = (int)$r['id'];
                    if (!isset($byId[$id])) $byId[$id] = $r;
                }
                $candidates = array_values($byId);
            }
        }
    } catch (Throwable $e) {
        error_log('replace-search fallback error: ' . $e->getMessage());
        // ignore; we still return what we have
    }
}

// Final slice to requested limit (UI will show up to this many)
if ($limit > 0 && count($candidates) > $limit) {
    $candidates = array_slice($candidates, 0, $limit);
}

// Build response
$out = [];
foreach ($candidates as $row) {
    $skillsArr = json_to_array_safe($row['specialization_skills'] ?? '[]');
    $citiesArr = json_to_array_safe($row['preferred_location'] ?? '[]');

    $out[] = [
        'id'                   => (int)$row['id'],
        'first_name'           => (string)($row['first_name'] ?? ''),
        'middle_name'          => (string)($row['middle_name'] ?? ''),
        'last_name'            => (string)($row['last_name'] ?? ''),
        'suffix'               => (string)($row['suffix'] ?? ''),
        'full_name'            => getFullName($row['first_name'] ?? '', $row['middle_name'] ?? '', $row['last_name'] ?? '', $row['suffix'] ?? ''),
        'email'                => (string)($row['email'] ?? ''),
        'phone_number'         => (string)($row['phone_number'] ?? ''),
        'years_experience'     => (int)($row['years_experience'] ?? 0),
        'employment_type'      => (string)($row['employment_type'] ?? ''),
        'education_level'      => (string)($row['education_level'] ?? ''),
        'picture_url'          => !empty($row['picture']) ? getFileUrl($row['picture']) : null,
        '_score'               => (int)($row['_score'] ?? 0),
        // Include docs_completed if present (helpful for UI badges / debugging)
        'docs_completed'       => isset($row['docs_completed']) ? (int)$row['docs_completed'] : null,
        'specialization_skills'=> $skillsArr,
        'preferred_location'   => $citiesArr,
        'created_at'           => (string)($row['created_at'] ?? ''),
    ];
}

echo json_encode([
    'ok'                     => true,
    'original_applicant_id'  => $originalId,
    'count'                  => count($out),
    'data'                   => $out
], JSON_UNESCAPED_UNICODE);