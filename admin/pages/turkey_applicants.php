<?php
// admin/turkey_applicants.php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/functions.php';
require_once '../includes/Applicant.php';

// -------- Helper: get Turkey Business Unit ID --------
// Strategy: Prefer the BU with code 'SMC-TR'. If not found, fallback to BU where country_id points to Turkey (countries.iso3='TUR').
function getTurkeyBusinessUnitId(mysqli $db): ?int {
    // 1) Try by BU code
    $sql1 = "SELECT id FROM business_units WHERE code = ? AND active = 1 LIMIT 1";
    if ($stmt = $db->prepare($sql1)) {
        $code = 'SMC-TR';
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $stmt->bind_result($id);
        if ($stmt->fetch()) {
            $stmt->close();
            return (int)$id;
        }
        $stmt->close();
    }

    // 2) Fallback: join by countries.iso3='TUR'
    $sql2 = "
        SELECT bu.id
        FROM business_units bu
        JOIN countries c ON c.id = bu.country_id
        WHERE c.iso3 = 'TUR' AND bu.active = 1
        ORDER BY bu.id ASC
        LIMIT 1
    ";
    if ($res = $db->query($sql2)) {
        $row = $res->fetch_assoc();
        $res->free();
        if ($row) return (int)$row['id'];
    }
    return null;
}

$turkeyBuId = getTurkeyBusinessUnitId($mysqli);
if ($turkeyBuId === null) {
    http_response_code(404);
    die("Turkey business unit not found. Please ensure business_units has code 'SMC-TR' or a row linked to countries.iso3='TUR'.");
}

// -------- Read filter/sort/pagination inputs (sanitized) --------
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort   = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$dir    = isset($_GET['dir']) ? strtoupper($_GET['dir']) : 'DESC';
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per    = isset($_GET['per']) ? min(100, max(10, (int)$_GET['per'])) : 25;

$allowedSort = [
    'created_at' => 'a.created_at',
    'last_name'  => 'a.last_name',
    'first_name' => 'a.first_name',
    'dob'        => 'a.date_of_birth',
    'email'      => 'a.email'
];
$orderBy = $allowedSort[$sort] ?? 'a.created_at';
$dir = ($dir === 'ASC') ? 'ASC' : 'DESC';

$offset = ($page - 1) * $per;

// -------- Count total --------
$where = "a.business_unit_id = ?";

$params = [];
$types = "i";
$params[] = $turkeyBuId;

if ($search !== '') {
    // Search in first_name, middle_name, last_name, email, phone_number
    $where .= " AND (
        a.first_name LIKE ? OR a.middle_name LIKE ? OR a.last_name LIKE ?
        OR a.email LIKE ? OR a.phone_number LIKE ?
    )";
    $like = '%' . $search . '%';
    $types .= "sssss";
    array_push($params, $like, $like, $like, $like, $like);
}

// Count query
$sqlCount = "SELECT COUNT(*) AS total FROM applicants a WHERE $where";
$stmt = $mysqli->prepare($sqlCount);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$total = (int)($res->fetch_assoc()['total'] ?? 0);
$stmt->close();

$totalPages = max(1, (int)ceil($total / $per));

// -------- Fetch page --------
$sql = "
SELECT
    a.id,
    a.first_name,
    a.middle_name,
    a.last_name,
    a.suffix,
    a.phone_number,
    a.alt_phone_number,
    a.email,
    a.date_of_birth,
    a.address,
    a.educational_attainment,
    a.work_history,
    a.created_at,
    a.updated_at
FROM applicants a
WHERE $where
ORDER BY $orderBy $dir
LIMIT ? OFFSET ?
";
$typesPage = $types . "ii";
$paramsPage = array_merge($params, [$per, $offset]);

$stmt = $mysqli->prepare($sql);
$stmt->bind_param($typesPage, ...$paramsPage);
$stmt->execute();
$result = $stmt->get_result();

// -------- Simple HTML --------
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Turkey Applicants</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 16px; }
    h1 { margin: 0 0 8px; }
    form.toolbar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin: 12px 0; }
    input[type="text"] { padding: 6px 8px; min-width: 240px; }
    select { padding: 6px 8px; }
    table { border-collapse: collapse; width: 100%; margin-top: 8px; }
    th, td { border: 1px solid #ddd; padding: 8px; vertical-align: top; }
    th { background: #f8f8f8; text-align: left; white-space: nowrap; }
    .muted { color: #666; font-size: 12px; }
    .nowrap { white-space: nowrap; }
    .pagination { margin-top: 12px; display: flex; gap: 6px; align-items: center; }
    .pagination a, .pagination span { padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; }
    .pagination .current { background: #333; color: #fff; border-color: #333; }
    .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
</style>
</head>
<body>
    <h1>Turkey Applicants</h1>
    <div class="muted">
        Business Unit ID: <span class="mono"><?= htmlspecialchars($turkeyBuId) ?></span> |
        Total: <strong><?= number_format($total) ?></strong>
    </div>

    <form method="get" class="toolbar">
        <input type="text" name="q" placeholder="Search name, email, phone…" value="<?= htmlspecialchars($search) ?>">
        <label>Sort
            <select name="sort">
                <option value="created_at" <?= $sort==='created_at'?'selected':'' ?>>Created</option>
                <option value="last_name"  <?= $sort==='last_name'?'selected':'' ?>>Last name</option>
                <option value="first_name" <?= $sort==='first_name'?'selected':'' ?>>First name</option>
                <option value="dob"        <?= $sort==='dob'?'selected':'' ?>>Date of birth</option>
                <option value="email"      <?= $sort==='email'?'selected':'' ?>>Email</option>
            </select>
        </label>
        <label>Dir
            <select name="dir">
                <option value="DESC" <?= $dir==='DESC'?'selected':'' ?>>DESC</option>
                <option value="ASC"  <?= $dir==='ASC'?'selected':'' ?>>ASC</option>
            </select>
        </label>
        <label>Rows
            <select name="per">
                <?php foreach ([10,25,50,100] as $opt): ?>
                    <option value="<?= $opt ?>" <?= $per===$opt?'selected':'' ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Apply</button>
    </form>

    <table>
        <thead>
            <tr>
                <th class="nowrap">ID</th>
                <th>Name</th>
                <th class="nowrap">Phone(s)</th>
                <th>Email</th>
                <th>DOB</th>
                <th>Address</th>
                <th>Education</th>
                <th>Work History</th>
                <th class="nowrap">Created</th>
                <th class="nowrap">Updated</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows === 0): ?>
                <tr><td colspan="10">No applicants found.</td></tr>
            <?php else: ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="nowrap"><?= (int)$row['id'] ?></td>
                        <td>
                            <?php
                            $full = trim(
                                ($row['last_name'] ?? '') . ', ' .
                                ($row['first_name'] ?? '') . ' ' .
                                ($row['middle_name'] ?? '') . ' ' .
                                ($row['suffix'] ?? '')
                            );
                            echo htmlspecialchars(preg_replace('/\s+/', ' ', $full));
                            ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($row['phone_number'] ?? '') ?>
                            <?php if (!empty($row['alt_phone_number'])): ?>
                                <div class="muted"><?= htmlspecialchars($row['alt_phone_number']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['email'] ?? '') ?></td>
                        <td class="nowrap"><?= htmlspecialchars($row['date_of_birth'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['address'] ?? '') ?></td>
                        <td><div style="max-width:260px"><?= htmlspecialchars($row['educational_attainment'] ?? '') ?></div></td>
                        <td><div style="max-width:260px"><?= htmlspecialchars($row['work_history'] ?? '') ?></div></td>
                        <td class="nowrap"><?= htmlspecialchars($row['created_at'] ?? '') ?></td>
                        <td class="nowrap"><?= htmlspecialchars($row['updated_at'] ?? '') ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php
    // Pagination controls
    $baseParams = $_GET;
    ?>
    <div class="pagination">
        <?php
        $baseParams['page'] = max(1, $page - 1);
        $prevUrl = '?' . http_build_query($baseParams);
        ?>
        <a href="<?= $prevUrl ?>">&laquo; Prev</a>

        <?php
        // Compact pagination: show first, current-2..current+2, last
        $toShow = [];
        $toShow[] = 1;
        for ($i = $page - 2; $i <= $page + 2; $i++) {
            if ($i >= 1 && $i <= $totalPages) $toShow[] = $i;
        }
        $toShow[] = $totalPages;
        $toShow = array_values(array_unique($toShow));
        $lastPrinted = 0;
        foreach ($toShow as $p) {
            if ($lastPrinted && $p !== $lastPrinted + 1) echo '<span>…</span>';
            $baseParams['page'] = $p;
            $url = '?' . http_build_query($baseParams);
            if ($p == $page) {
                echo '<span class="current">'. $p .'</span>';
            } else {
                echo '<a href="'. htmlspecialchars($url) .'">'. $p .'</a>';
            }
            $lastPrinted = $p;
        }
        $baseParams['page'] = min($totalPages, $page + 1);
        $nextUrl = '?' . http_build_query($baseParams);
        ?>
        <a href="<?= $nextUrl ?>">Next &raquo;</a>
    </div>
</body>
</html>
<?php
// cleanup
$result->free();
$stmt->close();
$mysqli->close();