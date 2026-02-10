<?php
/**
 * Public API: Get Applicants (client-facing)
 * - CORS enabled
 * - Returns normalized, client-friendly fields
 * - Robust photo URLs + safe fallbacks
 * - FIXES:
 *    * Availability filtering (Full-time / Part-time)
 *    * Specialization filtering tolerant of "&" vs "and" variants
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

/**
 * Generate tolerant variants for specialization LIKEs.
 * e.g., "Laundry & Clothing Care" => ["Laundry & Clothing Care", "Laundry and Clothing Care"]
 *       "Cleaning and Housekeeping (General)" => ["Cleaning and Housekeeping (General)", "Cleaning & Housekeeping (General)"]
 */
function andAmpVariants(string $label): array {
    $a = trim(htmlspecialchars_decode($label, ENT_QUOTES));
    // Collapse spaces
    $a = preg_replace('/\s+/', ' ', $a);

    // Build variants
    $withAmp = preg_replace('/\s*(?:&|and)\s*/i', ' & ', $a);
    $withAnd = preg_replace('/\s*(?:&|and)\s*/i', ' and ', $a);

    // Unique, preserve order preference: as-provided first, then the alternate
    $out = [];
    foreach ([$a, $withAmp, $withAnd] as $v) {
        if ($v !== '' && !in_array($v, $out, true)) $out[] = $v;
    }
    return $out;
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

    // Pagination / filters (from GET)
    $page       = max(1, (int)($_GET['page'] ?? 1));
    $per_page   = min(100, max(1, (int)($_GET['per_page'] ?? 12)));
    $offset     = ($page - 1) * $per_page;

    $q          = trim((string)($_GET['q'] ?? ''));
    $location   = trim((string)($_GET['location'] ?? ''));
    $minExp     = (int)($_GET['min_experience'] ?? 0);
    $availableBy = trim((string)($_GET['available_by'] ?? ''));
    $sort       = trim((string)($_GET['sort'] ?? ''));

    // Multi-value filters (arrays)
    $selectedSpecs = $_GET['specializations'] ?? [];
    if (!is_array($selectedSpecs) && $selectedSpecs !== '') {
        $selectedSpecs = array_filter(array_map('trim', explode(',', (string)$selectedSpecs)));
    }
    $selectedLangs = $_GET['languages'] ?? [];
    if (!is_array($selectedLangs) && $selectedLangs !== '') {
        $selectedLangs = array_filter(array_map('trim', explode(',', (string)$selectedLangs)));
    }

    // NEW: Availability from UI (Full-time / Part-time)
    $availability = $_GET['availability'] ?? [];
    if (!is_array($availability) && $availability !== '') {
        $availability = array_filter(array_map('trim', explode(',', (string)$availability)));
    }

    // Build WHERE clauses and bind params dynamically
    $where = ["deleted_at IS NULL"];

    // Public API: exclude applicants that are already approved — they should not be shown to clients
    $where[] = "LOWER(TRIM(status)) != 'approved'";

    $types = '';
    $values = [];

    if ($q !== '') {
        $where[] = "(CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ? OR specialization_skills LIKE ? OR email LIKE ? OR preferred_location LIKE ? )";
        $like = "%{$q}%";
        $types .= 'ssss';
        $values[] = $like; $values[] = $like; $values[] = $like; $values[] = $like;
    }

    if ($location !== '') {
        $where[] = "(location_city LIKE ? OR location_region LIKE ? OR preferred_location LIKE ? )";
        $likeLoc = "%{$location}%";
        $types .= 'sss';
        $values[] = $likeLoc; $values[] = $likeLoc; $values[] = $likeLoc;
    }

    // Specializations: tolerant of "&" vs "and"
    if (!empty($selectedSpecs)) {
        $specParts = [];
        foreach ($selectedSpecs as $spec) {
            $variants = andAmpVariants($spec);
            foreach ($variants as $v) {
                $specParts[] = "specialization_skills LIKE ?";
                $types .= 's';
                $values[] = '%' . $v . '%';
            }
        }
        if (!empty($specParts)) {
            $where[] = '(' . implode(' OR ', $specParts) . ')';
        }
    }

    // Languages
    if (!empty($selectedLangs)) {
        $parts = [];
        foreach ($selectedLangs as $lang) {
            $parts[] = "languages LIKE ?";
            $types .= 's'; $values[] = "%{$lang}%";
        }
        $where[] = '(' . implode(' OR ', $parts) . ')';
    }

    // Experience (min years)
    if ($minExp > 0) {
        $where[] = 'years_experience >= ?';
        $types .= 'i'; $values[] = $minExp;
    }

    // NEW: Availability => employment_type tolerance (Full Time / Part Time)
    if (!empty($availability)) {
        // Normalize incoming values to tokens: 'fulltime' or 'parttime'
        $want = [];
        foreach ($availability as $a) {
            $t = strtolower(preg_replace('/[\s\-]/', '', $a));
            if (in_array($t, ['fulltime', 'parttime'], true)) $want[$t] = true;
        }
        if (!empty($want)) {
            $in = [];
            // We will match by REPLACE(LOWER(employment_type), ' ', '') IN (...)
            // This covers 'Full Time', 'fulltime', 'Full-time' etc.
            $where[] = "("
                . "REPLACE(LOWER(employment_type), ' ', '') IN ("
                . implode(',', array_fill(0, count($want), '?'))
                . ")"
                . ")";
            foreach (array_keys($want) as $k) {
                $types .= 's'; $values[] = $k; // 'fulltime' or 'parttime'
            }
        }
    }

    if ($availableBy !== '') {
        // Compare computed availability = created_at + 30 days <= availableBy
        $where[] = "DATE_ADD(created_at, INTERVAL 30 DAY) <= ?";
        $types .= 's'; $values[] = $availableBy;
    }

    // Sorting
    switch ($sort) {
        case 'availability_asc':
            $orderBy = 'DATE_ADD(created_at, INTERVAL 30 DAY) ASC';
            break;
        case 'experience_desc':
            $orderBy = 'years_experience DESC';
            break;
        case 'newest':
        default:
            $orderBy = 'created_at DESC';
    }

    $whereSql = implode(' AND ', $where);

    // Total count
    $countSql = "SELECT COUNT(*) AS cnt FROM applicants WHERE {$whereSql}";
    $mysqli = $database->getConnection();
    $countStmt = $mysqli->prepare($countSql);
    if ($types !== '') {
        // bind params to count statement
        $tmp = array_merge([$types], $values);
        $refs = [];
        foreach ($tmp as $k => $v) $refs[$k] = &$tmp[$k];
        call_user_func_array([$countStmt, 'bind_param'], $refs);
    }
    $countStmt->execute();
    $countRes = $countStmt->get_result();
    $totalRow = $countRes->fetch_assoc();
    $total = (int)($totalRow['cnt'] ?? 0);

    // Determine rotation seed (daily by default) and whether rotation is disabled via ?rotate=0
    $rotateEnabled = !isset($_GET['rotate']) || $_GET['rotate'] !== '0';
    $seed = date('Ymd'); // daily seed; change to date('YmdH') for hourly, etc.

    // Data query with deterministic pseudo-random ordering using CRC32(id + seed)
    if ($rotateEnabled) {
        $sql = "SELECT * FROM applicants WHERE {$whereSql} ORDER BY CRC32(CONCAT(id, ?)) ASC, {$orderBy} LIMIT ?, ?";
    } else {
        $sql = "SELECT * FROM applicants WHERE {$whereSql} ORDER BY {$orderBy} LIMIT ?, ?";
    }

    $stmt = $mysqli->prepare($sql);

    // bind params + offset, per_page (offset/per_page are integers)
    if ($rotateEnabled) {
        // add seed (s) before offset/per_page (ii)
        $bindTypes = $types . 'sii';
        $bindValues = array_merge($values, [$seed, $offset, $per_page]);
    } else {
        $bindTypes = $types . 'ii';
        $bindValues = array_merge($values, [$offset, $per_page]);
    }

    $tmp = array_merge([$bindTypes], $bindValues);
    $refs = [];
    foreach ($tmp as $k => $v) $refs[$k] = &$tmp[$k];
    call_user_func_array([$stmt, 'bind_param'], $refs);

    $stmt->execute();
    $result = $stmt->get_result();
    $apps = $result->fetch_all(MYSQLI_ASSOC);

    // Base URLs (auto-detect http/https + host)
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Adjust this path to your project root if needed:
    $appBase = $scheme . '://' . $host . '/csnk';

    $uploadsBase    = rtrim($appBase, '/') . '/admin/uploads/';
    $placeholderUrl = rtrim($appBase, '/') . '/resources/img/placeholder-user.svg';

    $rows = [];

    foreach ($apps as $app) {
        // Parse JSONs safely
        $skills      = json_decode($app['specialization_skills'] ?? '[]', true) ?: [];
        $prefLoc     = json_decode($app['preferred_location']     ?? '[]', true) ?: [];
        $langsArr    = json_decode($app['languages']              ?? '[]', true) ?: [];
        $educAttain  = json_decode($app['educational_attainment'] ?? '[]', true) ?: [];

        // Normalize skills (avoid excessive HTML entities)
        $skills = array_values(array_filter(array_map('normalizeSkillLabel', $skills)));

        // Primary specialization (mapped role) – tolerant of &/and
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
        $educationLevelEnum = $app['education_level'] ?? null;
        $educationDisplay = $educationLevelEnum
            ?: ((is_array($educAttain) && !empty($educAttain)) ? implode(', ', $educAttain) : '—');

        // Video URL
        $videoUrl = '';
        if (!empty($app['video_url'])) {
            $normalized = str_replace('\\', '/', (string)$app['video_url']);
            $videoRelative = ltrim($normalized, '/');
            if (preg_match('~^https?://~i', $videoRelative)) {
                $videoUrl = $videoRelative; // YouTube/Vimeo
            } elseif (preg_match('~^admin/uploads/~i', $videoRelative)) {
                $videoUrl = $appBase . '/' . $videoRelative;
            } else {
                $videoUrl = $uploadsBase . $videoRelative;
            }
        }

        // Video type
        $videoTypeRaw = strtolower($app['video_type'] ?? 'iframe');
        $videoType = ($videoTypeRaw === 'file') ? 'file' : 'iframe';

        $rows[] = [
            'id'                      => (int)$app['id'],
            'full_name'               => trim(($app['first_name'] ?? '') . ' ' . (($app['middle_name'] ?? '') ? $app['middle_name'] . ' ' : '') . ($app['last_name'] ?? '')),
            'initials'                => initialsFromName($app['first_name'] ?? '', $app['last_name'] ?? ''),
            'age'                     => computeAge($app['date_of_birth'] ?? null),

            // STATUS (important for client-side filtering)
            'status'                  => strtolower(trim((string)($app['status'] ?? ''))),

            'specialization'          => $primaryRole,
            'specializations'         => $skills,

            'location_city'           => $locationCity,
            'location_region'         => $locationRegion,
            'preferred_locations'     => $prefLoc,

            'years_experience'        => (int)($app['years_experience'] ?? 0),

            'employment_type'         => $employmentLabel,  // Full-time | Part-time
            'employment_type_raw'     => $employmentRaw,    // Full Time | Part Time (DB)

            'availability_date'       => $availDate,

            'languages'               => $languagesStr,     // "English,Filipino"
            'languages_array'         => $langsArr,         // ["English","Filipino"]

            'education_level'         => $educationLevelEnum,
            'educational_attainment'  => $educAttain,
            'education_display'       => $educationDisplay,

            'photo_url'               => $photoUrl,
            'photo_placeholder'       => $placeholderUrl,

            'video_url'               => $videoUrl,
            'video_type'              => $videoType,

            'created_at'              => $createdAt
        ];
    }

    // Return paginated response with metadata
    echo json_encode([
        'data' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $per_page
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}