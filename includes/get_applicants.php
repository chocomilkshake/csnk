<?php
/**
 * Public API: Get Applicants (client-facing)
 * - CORS enabled
 * - Returns normalized, client-friendly fields
 * - Robust photo URLs + safe fallbacks
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Bootstrap admin side
require_once __DIR__ . '/../admin/includes/config.php';
require_once __DIR__ . '/../admin/includes/Database.php';
require_once __DIR__ . '/../admin/includes/Applicant.php';

function normalizeSkillLabel(string $label): string {
    // Normalize dashes/ampersands/spacing from stored data
    $x = trim(htmlspecialchars_decode($label, ENT_QUOTES));
    $x = preg_replace('/\s+/', ' ', $x);
    return $x;
}

function mapPrimarySpecialization(array $skills): string {
    // Exact option set from your schema/UI
    // 'Cleaning & Housekeeping (General)'
    // 'Laundry & Clothing Care'
    // 'Cooking & Food Service'
    // 'Childcare & Maternity (Yaya)'
    // 'Elderly & Special Care (Caregiver)'
    // 'Pet & Outdoor Maintenance'

    $roleMap = [
        'Cleaning & Housekeeping (General)' => 'Kasambahay',
        'Laundry & Clothing Care'           => 'Kasambahay',
        'Cooking & Food Service'            => 'Cook',
        'Childcare & Maternity (Yaya)'      => 'Nanny',
        'Elderly & Special Care (Caregiver)'=> 'Elderly Care',
        'Pet & Outdoor Maintenance'         => 'All-around Helper',
    ];

    foreach ($skills as $raw) {
        $skill = normalizeSkillLabel($raw);
        if (isset($roleMap[$skill])) {
            return $roleMap[$skill];
        }
    }
    return '';
}

function computeAge(?string $dob): ?int {
    if (empty($dob)) return null;
    $d = DateTime::createFromFormat('Y-m-d', $dob);
    if (!$d) return null;
    $now = new DateTime('now');
    return (int)$d->diff($now)->y;
}

function initialsFromName(string $first, ?string $last): string {
    $a = mb_substr(trim($first), 0, 1);
    $b = mb_substr(trim((string)$last), 0, 1);
    return mb_strtoupper($a . $b);
}

try {
    // Debug: Log that we're starting
    error_log("get_applicants.php: Starting execution");

    $database  = new Database();
    $applicant = new Applicant($database);

    // Active (not deleted)
    $apps = $applicant->getAll();

    // Debug: Log how many applicants we found
    error_log("get_applicants.php: Found " . count($apps) . " applicants");

    // Base URLs (auto-detect http/https + host)
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Adjust this path to your project root if needed:
    $appBase = $scheme . '://' . $host . '/csnk';

    $uploadsBase    = $appBase . '/admin/uploads/';
    $placeholderUrl = $appBase . '/resources/img/placeholder-user.svg';

    $rows = [];

    foreach ($apps as $app) {
        // Parse JSONs safely
        $skills      = json_decode($app['specialization_skills'] ?? '[]', true) ?: [];
        $prefLoc     = json_decode($app['preferred_location']     ?? '[]', true) ?: [];
        $langsArr    = json_decode($app['languages']              ?? '[]', true) ?: [];

        // Normalize skills (avoid &amp;)
        $skills = array_values(array_filter(array_map('normalizeSkillLabel', $skills)));

        // Primary specialization (mapped role)
        $primaryRole = mapPrimarySpecialization($skills);

        // Location city (first preferred city)
        $locationCity   = !empty($prefLoc) ? (string)$prefLoc[0] : 'N/A';
        // Region: set a sane default, you can enhance later with geo mapping per city
        $locationRegion = 'NCR';

        // Languages
        $languagesStr = implode(',', $langsArr);

        // Photo URL
        $photoUrl = $placeholderUrl;
        if (!empty($app['picture'])) {
            $relative = ltrim($app['picture'], '/');
            $photoUrl = $uploadsBase . $relative;
        }

        // Employment type label for client
        $employmentRaw   = (string)($app['employment_type'] ?? '');
        $employmentLabel = ($employmentRaw === 'Full Time') ? 'Full-time' : (($employmentRaw === 'Part Time') ? 'Part-time' : 'Full-time');

        // Availability: default created_at + 30 days
        $createdAt   = $app['created_at'] ?? date('Y-m-d H:i:s');
        $availDate   = date('Y-m-d', strtotime($createdAt . ' +30 days'));

        $rows[] = [
            'id'                 => (int)$app['id'],
            'full_name'          => trim($app['first_name'] . ' ' . (($app['middle_name'] ?? '') ? $app['middle_name'] . ' ' : '') . $app['last_name']),
            'initials'           => initialsFromName($app['first_name'] ?? '', $app['last_name'] ?? ''),
            'age'                => computeAge($app['date_of_birth'] ?? null),

            'specialization'     => $primaryRole,   // main role label
            'specializations'    => $skills,        // full list (as array)

            'location_city'      => $locationCity,
            'location_region'    => $locationRegion,

            'years_experience'   => (int)($app['years_experience'] ?? 0),

            'employment_type'        => $employmentLabel,   // Full-time | Part-time
            'employment_type_raw'    => $employmentRaw,     // Full Time | Part Time (DB)

            'availability_date'  => $availDate,

            'languages'          => $languagesStr, // "English,Filipino"
            'languages_array'    => $langsArr,     // ["English", "Filipino"]

            'photo_url'          => $photoUrl,
            'photo_placeholder'  => $placeholderUrl,

            'created_at'         => $createdAt
        ];
    }

    echo json_encode($rows, JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}