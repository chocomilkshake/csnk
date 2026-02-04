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

/**
 * Canonicalize for role mapping only.
 * Unifies "and" and "&" to a single " & " form so we can match either variant
 * from the DB/UI (covers both old and new option sets).
 */
function canonicalizeForRoleMap(string $label): string {
    $x = normalizeSkillLabel($label);
    // Replace any " and " or "&" (with flexible spacing) to " & "
    $x = preg_replace('/\s*(?:&|and)\s*/i', ' & ', $x);
    // Collapse extra spaces again to be safe
    $x = preg_replace('/\s+/', ' ', $x);
    return $x;
}

function mapPrimarySpecialization(array $skills): string {
    // Canonical map (expects " & " form)
    $roleMap = [
        'Cleaning & Housekeeping (General)'   => 'Housekeeping',
        'Laundry & Clothing Care'             => 'Laundry Specialist',
        'Cooking & Food Service'              => 'Cook',
        'Childcare & Maternity (Yaya)'        => 'Nanny',
        'Elderly & Special Care (Caregiver)'  => 'Elderly Care',
        'Pet & Outdoor Maintenance'           => 'Pet Care Specialist',
    ];

    foreach ($skills as $raw) {
        $canon = canonicalizeForRoleMap($raw);
        if (isset($roleMap[$canon])) {
            return $roleMap[$canon];
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
    $database  = new Database();
    $applicant = new Applicant($database);

    // Active (not deleted, not approved)
    $apps = $applicant->getAllForPublic();

    // Base URLs (auto-detect http/https + host)
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Adjust this path to your project root if needed:
    $appBase = $scheme . '://' . $host . '/csnk-1';

    $uploadsBase    = rtrim($appBase, '/') . '/admin/uploads/';
    $placeholderUrl = rtrim($appBase, '/') . '/resources/img/placeholder-user.svg';

    $rows = [];

    foreach ($apps as $app) {
        // Parse JSONs safely
        $skills      = json_decode($app['specialization_skills'] ?? '[]', true) ?: [];
        $prefLoc     = json_decode($app['preferred_location']     ?? '[]', true) ?: [];
        $langsArr    = json_decode($app['languages']              ?? '[]', true) ?: [];
        $educAttain  = json_decode($app['educational_attainment'] ?? '[]', true);

        // Normalize skills (avoid &amp;amp;amp;)
        $skills = array_values(array_filter(array_map('normalizeSkillLabel', $skills)));

        // Primary specialization (mapped role) – handles both "&" and "and" variants
        $primaryRole = mapPrimarySpecialization($skills);

        // Location city (first preferred city shown on card)
        $locationCity   = !empty($prefLoc) ? (string)$prefLoc[0] : 'N/A';
        // Region: basic default
        $locationRegion = 'NCR';

        // Languages (string for quick filter + array for detail)
        $languagesStr = implode(',', $langsArr);

        // Photo URL (robust: absolute URL respected, else join with uploads base)
        $photoUrl = $placeholderUrl;
        if (!empty($app['picture'])) {
            $relative = ltrim((string)$app['picture'], '/');
            if (preg_match('~^https?://~i', $relative)) {
                $photoUrl = $relative; // already absolute
            } else {
                $photoUrl = $uploadsBase . $relative;
            }
        }

        // Employment type label for client
        $employmentRaw   = (string)($app['employment_type'] ?? '');
        $employmentLabel = ($employmentRaw === 'Full Time')
            ? 'Full-time'
            : (($employmentRaw === 'Part Time') ? 'Part-time' : 'Full-time');

        // Availability: default created_at + 30 days (kept for sorting/filter logic only)
        $createdAt   = $app['created_at'] ?? date('Y-m-d H:i:s');
        $availDate   = date('Y-m-d', strtotime($createdAt . ' +30 days'));

        // Educational attainment display
        // Prefer enum field if set; otherwise fall back to JSON array (join)
        $educationLevelEnum = $app['education_level'] ?? null;
        $educationDisplay = $educationLevelEnum
            ?: ((is_array($educAttain) && !empty($educAttain)) ? implode(', ', $educAttain) : '—');

        $rows[] = [
            'id'                      => (int)$app['id'],
            'full_name'               => trim(($app['first_name'] ?? '') . ' ' . (($app['middle_name'] ?? '') ? $app['middle_name'] . ' ' : '') . ($app['last_name'] ?? '')),
            'initials'                => initialsFromName($app['first_name'] ?? '', $app['last_name'] ?? ''),
            'age'                     => computeAge($app['date_of_birth'] ?? null),

            'specialization'          => $primaryRole,
            'specializations'         => $skills,

            'location_city'           => $locationCity,
            'location_region'         => $locationRegion,
            'preferred_locations'     => $prefLoc,   // return ALL preferred locations

            'years_experience'        => (int)($app['years_experience'] ?? 0),

            'employment_type'         => $employmentLabel,  // Full-time | Part-time
            'employment_type_raw'     => $employmentRaw,    // Full Time | Part Time (DB)

            'availability_date'       => $availDate,

            'languages'               => $languagesStr,     // "English,Filipino"
            'languages_array'         => $langsArr,         // ["English","Filipino"]

            'education_level'         => $educationLevelEnum,
            'educational_attainment'  => $educAttain,       // raw JSON array if exists
            'education_display'       => $educationDisplay, // computed display

            'photo_url'               => $photoUrl,
            'photo_placeholder'       => $placeholderUrl,

            'created_at'              => $createdAt
        ];
    }

    echo json_encode($rows, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}