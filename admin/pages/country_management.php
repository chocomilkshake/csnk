<?php
$pageTitle = 'Country Management';
require_once '../includes/header.php';

/* ============================================================================
   RBAC: Admins and Super Admins only (defense in depth)
============================================================================ */
if (!$isAdmin && !$isSuperAdmin) {
    setFlashMessage('error', 'You do not have permission to access this page.');
    redirect('dashboard.php');
}

/* ============================================================================
   Schema guard: ensure countries.id is AUTO_INCREMENT primary key (+ fix id=0)
============================================================================ */
if ($conn instanceof mysqli) {
    // Ensure PK exists on id
    if ($res = $conn->query("SHOW INDEX FROM countries WHERE Key_name='PRIMARY'")) {
        if ($res->num_rows === 0) {
            @$conn->query("ALTER TABLE countries ADD PRIMARY KEY (id)");
        }
        $res->close();
    }
    // Ensure AUTO_INCREMENT on id
    if ($schema = $conn->query("SHOW CREATE TABLE countries")) {
        $row = $schema->fetch_assoc();
        if (strpos($row['Create Table'] ?? '', 'AUTO_INCREMENT') === false) {
            @$conn->query("ALTER TABLE countries MODIFY id SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT");
        }
        $schema->close();
    }
    // Fix any id=0 rows
    if ($fix = $conn->query("SELECT COUNT(*) AS cnt FROM countries WHERE id = 0")) {
        $cnt = (int)($fix->fetch_assoc()['cnt'] ?? 0);
        $fix->close();
        if ($cnt > 0) {
            $nr = $conn->query("SELECT IFNULL(MAX(id),0)+1 AS nxt FROM countries");
            $nxt = (int)($nr->fetch_assoc()['nxt'] ?? 1);
            $nr && $nr->close();
            while ($cnt--) { @$conn->query("UPDATE countries SET id={$nxt} WHERE id=0 LIMIT 1"); $nxt++; }
        }
    }
}

/* ============================================================================
   Helpers
============================================================================ */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function ensure_upper(?string $s, int $max = null): string {
    $s = strtoupper(trim((string)$s));
    if ($max !== null) $s = substr($s, 0, $max);
    return $s;
}
function flag_emoji_from_iso2(?string $iso2): string {
    $iso2 = strtoupper(trim((string)$iso2));
    if (strlen($iso2) !== 2) return '';
    $cp1 = 0x1F1E6 + (ord($iso2[0]) - ord('A'));
    $cp2 = 0x1F1E6 + (ord($iso2[1]) - ord('A'));
    return mb_convert_encoding('&#' . $cp1 . ';', 'UTF-8', 'HTML-ENTITIES')
         . mb_convert_encoding('&#' . $cp2 . ';', 'UTF-8', 'HTML-ENTITIES');
}
/**
 * Build a small flag icon (24x18) using flagcdn.com with emoji fallback
 * Usage: echo flag_icon('PH', 'Philippines');
 */
function flag_icon(?string $iso2, string $nameForAlt = ''): string {
    $code = strtolower(trim((string)$iso2));
    if (strlen($code) !== 2) {
        return '<span class="flag-emoji">'.flag_emoji_from_iso2($iso2).'</span>';
    }
    $emoji = flag_emoji_from_iso2($iso2);
    $alt = h($nameForAlt ?: strtoupper($code).' flag');
    // stack: image first, reveal emoji if image fails
    return '
    <span class="flag-stack" aria-hidden="true">
      <img class="flag-img" src="https://flagcdn.com/24x18/'.h($code).'.png"
           width="24" height="18" loading="lazy" alt="'.$alt.'"
           onerror="this.style.display=\'none\'; const e=this.nextElementSibling; if(e) e.style.display=\'inline-block\';">
      <span class="flag-emoji" style="display:none">'.$emoji.'</span>
    </span>';
}

/* ============================================================================
   Row actions (toggle active / delete)
============================================================================ */
if (isset($_GET['action'], $_GET['id'])) {
    $targetId = (int)$_GET['id'];

    if ($_GET['action'] === 'toggle_active') {
        $stmt = $conn->prepare("UPDATE countries SET active = 1 - active WHERE id = ?");
        $stmt->bind_param('i', $targetId);
        if ($stmt->execute()) setFlashMessage('success', 'Country status updated.');
        else setFlashMessage('error', 'Failed to update country status.');
        $stmt->close();
        redirect('country_management.php');
    }

    if ($_GET['action'] === 'delete') {
        // Only allow delete when there is no BU referencing it
        $stmt = $conn->prepare("SELECT COUNT(*) FROM business_units WHERE country_id = ?");
        $stmt->bind_param('i', $targetId);
        $stmt->execute();
        $buCount = (int)($stmt->get_result()->fetch_row()[0] ?? 0);
        $stmt->close();

        if ($buCount > 0) {
            setFlashMessage('error', 'Cannot delete: linked Business Units exist. Deactivate instead.');
        } else {
            $stmt = $conn->prepare("DELETE FROM countries WHERE id = ?");
            $stmt->bind_param('i', $targetId);
            if ($stmt->execute()) setFlashMessage('success', 'Country deleted.');
            else setFlashMessage('error', 'Failed to delete country.');
            $stmt->close();
        }
        redirect('country_management.php');
    }
}

/* ============================================================================
   Add/Edit submit
   - Business Unit creation is AUTO (hidden) for ADD only:
     Agency ID=2 (default), BU Code=SMC-{ISO2}, BU Name=SMC {Country Name}, Active=1
     If BU code exists already, skip creating duplicate silently.
============================================================================ */
$errors = [];
$reopenModal = ''; // 'add' or 'edit' (to reopen on error)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_country'])) {
    $id                 = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $iso2               = ensure_upper($_POST['iso2'] ?? '', 2);
    $iso3               = ensure_upper($_POST['iso3'] ?? '', 3);
    $name               = trim((string)($_POST['name'] ?? ''));
    $default_tz         = trim((string)($_POST['default_tz'] ?? ''));
    $phone_country_code = trim((string)($_POST['phone_country_code'] ?? ''));
    $currency_code      = ensure_upper($_POST['currency_code'] ?? '', 3);
    $locale             = trim((string)($_POST['locale'] ?? ''));
    $date_format        = trim((string)($_POST['date_format'] ?? 'Y-m-d'));
    $active             = isset($_POST['active']) ? 1 : 0;

    // Hidden BU inputs (kept for forward compatibility; may be empty)
    $bu_agency_id = (int)($_POST['bu_agency_id'] ?? 0);
    $bu_code      = trim((string)($_POST['bu_code'] ?? ''));
    $bu_name      = trim((string)($_POST['bu_name'] ?? ''));
    $bu_active    = isset($_POST['bu_active']) ? 1 : 0;

    // Validation
    if (strlen($iso2) !== 2) $errors[] = "ISO2 must be exactly 2 characters.";
    if (strlen($iso3) !== 3) $errors[] = "ISO3 must be exactly 3 characters.";
    if ($name === '')        $errors[] = "Country Name is required.";

    // Uniqueness checks
    if ($id > 0) {
        $chk = $conn->prepare("SELECT COUNT(*) FROM countries WHERE id <> ? AND (iso2 = ? OR iso3 = ? OR name = ?)");
        $chk->bind_param('isss', $id, $iso2, $iso3, $name);
    } else {
        $chk = $conn->prepare("SELECT COUNT(*) FROM countries WHERE (iso2 = ? OR iso3 = ? OR name = ?)");
        $chk->bind_param('sss', $iso2, $iso3, $name);
    }
    $chk->execute();
    $dups = (int)($chk->get_result()->fetch_row()[0] ?? 0);
    $chk->close();
    if ($dups > 0) $errors[] = "Another country with the same ISO2, ISO3 or Name already exists.";

    if (empty($errors)) {
        $ok = false;
        $countryId = $id;

        if ($id > 0) {
            // UPDATE
            $sql = "UPDATE countries
                       SET iso2=?, iso3=?, name=?, default_tz=?, phone_country_code=?, currency_code=?, locale=?, date_format=?, active=?
                     WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssssssii',
                $iso2, $iso3, $name, $default_tz, $phone_country_code, $currency_code, $locale, $date_format, $active, $id
            );
            if ($stmt->execute()) $ok = true;
            else $errors[] = "Database error (update): " . $conn->error;
            $stmt->close();
        } else {
            // INSERT
            $sql = "INSERT INTO countries (iso2, iso3, name, default_tz, phone_country_code, currency_code, locale, date_format, active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssssssi',
                $iso2, $iso3, $name, $default_tz, $phone_country_code, $currency_code, $locale, $date_format, $active
            );
            if ($stmt->execute()) { $ok = true; $countryId = $conn->insert_id; }
            else $errors[] = "Database error (insert): " . $conn->error;
            $stmt->close();

            // AUTO-CREATE BU (hidden logic) — only on ADD
            if ($ok) {
                // Build defaults if not provided (hidden fields)
                if ($bu_agency_id <= 0) $bu_agency_id = 2;
                if ($bu_active !== 0 && $bu_active !== 1) $bu_active = 1;
                if ($bu_code === '') $bu_code = 'SMC-' . $iso2;
                if ($bu_name === '') $bu_name = 'SMC ' . $name;

                // Avoid duplicate BU code
                $chk = $conn->prepare("SELECT COUNT(*) FROM business_units WHERE code = ?");
                $chk->bind_param('s', $bu_code);
                $chk->execute();
                $exists = (int)($chk->get_result()->fetch_row()[0] ?? 0);
                $chk->close();

                if ($exists === 0) {
                    $sqlBU = "INSERT INTO business_units (agency_id, country_id, code, name, active, created_at, updated_at)
                              VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
                    $stmtBU = $conn->prepare($sqlBU);
                    $stmtBU->bind_param('iissi', $bu_agency_id, $countryId, $bu_code, $bu_name, $bu_active);
                    if (!$stmtBU->execute()) {
                        // Non-fatal: keep country saved even if BU creation fails silently
                    }
                    $stmtBU->close();
                }
            }
        }

        if (empty($errors) && $ok) {
            setFlashMessage('success', ($id > 0) ? 'Country saved successfully.' : 'Country and default Business Unit created.');
            redirect('country_management.php');
        }
    }

    // If errors: reopen the right modal and pass the posted data back via JS
    $reopenModal = ($id > 0) ? 'edit' : 'add';
    $postedData = [
        'id' => $id, 'iso2' => $iso2, 'iso3' => $iso3, 'name' => $name,
        'default_tz' => $default_tz, 'phone_country_code' => $phone_country_code,
        'currency_code' => $currency_code, 'locale' => $locale, 'date_format' => $date_format, 'active' => $active,
        'bu_agency_id' => $bu_agency_id, 'bu_code' => $bu_code, 'bu_name' => $bu_name, 'bu_active' => $bu_active
    ];
} else {
    $postedData = null;
}

/* ============================================================================
   Load countries with BU counts
============================================================================ */
$countries = [];
$sql = "SELECT c.*, (SELECT COUNT(*) FROM business_units WHERE country_id = c.id) AS bu_count
        FROM countries c
        ORDER BY c.name ASC";
$res = $conn->query($sql);
if ($res) {
    $countries = $res->fetch_all(MYSQLI_ASSOC);
    $res->close();
}

$totalCount  = count($countries);
$activeCount = array_reduce($countries, fn($a,$c)=>$a + ((int)($c['active'] ?? 0) === 1 ? 1 : 0), 0);
$inactiveCount = $totalCount - $activeCount;
?>
<style>
:root{
  --ink:#0f172a; --muted:#64748b; --ring:rgba(37,99,235,.14);
  --bg:#f8fafc; --card:#ffffff; --card-2:#ffffff; --line:#e2e8f0;
  --ok:#16a34a; --warn:#f59e0b; --err:#ef4444; --pri:#2563eb; --pri-2:#60a5fa;
}

/* Light background */
body { background: linear-gradient(180deg, #f1f5f9, #f8fafc) fixed; }

/* Page title */
.page-title h4{ font-weight:800; letter-spacing:.3px; color:#1e293b; }
.page-title p{ color:#64748b; }

/* Stat chips */
.stats-chip{
  display:flex; align-items:center; gap:.65rem; padding:.7rem 1rem; border:1px solid var(--line);
  border-radius:1rem; background:#ffffff;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  color:#334155;
}
.stats-chip .icon{ width:30px; height:30px; display:grid; place-items:center; border-radius:10px; background:rgba(37,99,235,.1); color:#2563eb; }
.stats-chip .count{ font-weight:800; color:#0f172a; font-size:1.05rem; }
.stats-chip small{ color:#64748b; display:block; margin-top:-2px; }

/* Card */
.card {
  border:1px solid var(--line); border-radius:1rem; overflow:hidden; background:#ffffff;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.card-header{
  background: #f8fafc;
  border-bottom:1px solid var(--line);
  padding: .85rem 1rem;
  position: sticky; top: 0; z-index: 3;
  color:#334155;
}
.card-header .title{ font-weight:700; }
.card-body{ background:transparent; }

/* Table */
.table-wrap{ max-height: 64vh; overflow:auto; }
table.table{ margin:0; color:#334155; }
table thead th{
  position: sticky; top: 0; background:#f1f5f9;
  z-index:2; border-bottom:1px solid var(--line); color:#1e293b; font-weight:700; letter-spacing:.2px;
}
.table td, .table th{ vertical-align:middle; border-color: var(--line); }
.table tbody tr:hover{ background:rgba(37,99,235,.04); }

/* Badges */
.badge-soft{ border:1px solid var(--line); background:#f8fafc; color:#334155; }
.badge-iso2{ background:rgba(37,99,235,.1); color:#1d4ed8; cursor:pointer; }
.badge-iso3{ background:rgba(14,165,233,.12); color:#0369a1; cursor:pointer; }
.badge-cur{ background:rgba(16,185,129,.1); color:#15803d; cursor:pointer; }
.badge-ph{ background:rgba(245,158,11,.1); color:#b45309; cursor:pointer; }
.badge-copy:active{ transform:scale(.97); }

/* Flags */
.flag-stack { display:inline-flex; align-items:center; justify-content:center; width:24px; height:18px; margin-right:.35rem; border-radius:3px; overflow:hidden; box-shadow:0 0 0 1px rgba(0,0,0,.1); }
.flag-img { display:block; width:24px; height:18px; object-fit:cover; }
.flag-emoji { font-size:1rem; line-height:1; }

/* Mono for IDs */
.mono{ font-variant-numeric:tabular-nums; font-family: ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace; }

/* Buttons */
.btn-action{ min-width:36px; border-radius:10rem; position:relative; z-index:1; }
.btn-toggle{ border-radius:999px; }
.btn, .form-control, .form-select{ border-radius:.75rem; }

/* Inputs + focus */
.form-control, .form-select{ background:#ffffff; border-color:var(--line); color:#1e293b; }
.form-control:focus, .form-select:focus{ box-shadow:0 0 0 .25rem var(--ring); border-color:#93c5fd; outline:none; color:#0f172a; }
.input-icon{ position:relative; }
.input-icon > .bi{ position:absolute; left:.75rem; top:50%; transform:translateY(-50%); color:#64748b; }
.input-icon > input{ padding-left:2.25rem; }
.form-label{ font-weight:700; color:#1e293b; }
.small-hint{ color:#64748b; }
.bg-soft{ background:#f8fafc; }

/* Divider */
hr.hr-soft{ border:0; height:1px; background:linear-gradient(90deg,rgba(0,0,0,.04),rgba(0,0,0,.1),rgba(0,0,0,.04)); }

/* Footer (sticky controls) */
.card-footer{
  border-top:1px solid var(--line);
  background:#f8fafc;
  position: sticky; bottom: 0; z-index: 2;
  color:#64748b;
}

/* Tooltip width */
.tooltip-inner{ max-width:260px; }

/* Status pills */
.badge-status {
  display:inline-flex; align-items:center; gap:.35rem; padding:.25rem .6rem; border-radius:999px;
  font-weight:700;
}
.badge-status.ok { background:rgba(22,163,74,.1); color:#15803d; border:1px solid rgba(22,163,74,.2); }
.badge-status.no { background:rgba(239,68,68,.1); color:#dc2626; border:1px solid rgba(239,68,68,.2); }

/* Responsive */
@media (max-width: 575.98px){
  .page-title h4 { font-size:1.05rem; }
}
</style>

<div class="row align-items-center justify-content-between mb-3 page-title">
  <div class="col">
    <h4 class="mb-1">Country Management</h4>
    <p class="mb-0">Manage global countries and their regional settings.</p>
  </div>

  <div class="col-auto d-flex flex-wrap gap-2">
    <div class="stats-chip" title="All countries in the system" data-bs-toggle="tooltip">
      <div class="icon"><i class="bi bi-globe2"></i></div>
      <div>
        <div class="count"><?= (int)$totalCount ?></div>
        <small>Total</small>
      </div>
    </div>
    <div class="stats-chip" title="Active &amp; available for selection" data-bs-toggle="tooltip">
      <div class="icon"><i class="bi bi-check2-circle"></i></div>
      <div>
        <div class="count"><?= (int)$activeCount ?></div>
        <small>Active</small>
      </div>
    </div>
    <div class="stats-chip" title="Inactive &amp; hidden from new records" data-bs-toggle="tooltip">
      <div class="icon"><i class="bi bi-slash-circle"></i></div>
      <div>
        <div class="count"><?= (int)$inactiveCount ?></div>
        <small>Inactive</small>
      </div>
    </div>
  </div>
</div>

<!-- Toolbar (simplified: removed density, rows, export) -->
<div class="row g-2 align-items-center toolbar mb-3">
  <div class="col-sm-6">
    <div class="input-icon">
      <i class="bi bi-search"></i>
      <input id="tblFilter" type="search" class="form-control" placeholder="Search by name or ISO2 (e.g., Philippines or PH)" aria-label="Search countries">
    </div>
  </div>
  <div class="col-sm-auto">
    <select id="statusFilter" class="form-select" aria-label="Filter by status">
      <option value="">All statuses</option>
      <option value="1">Active</option>
      <option value="0">Inactive</option>
    </select>
  </div>
  <div class="col-sm-auto">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal" title="Add new country">
      <i class="bi bi-plus-lg me-1"></i><span class="label">Add Country</span>
    </button>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <div class="d-flex align-items-start">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      <div>
        <strong>We found some issues:</strong>
        <ul class="mb-0 mt-1">
          <?php foreach ($errors as $e): ?><li><?php echo h($e); ?></li><?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Countries Table -->
<div class="card shadow-sm">
  <div class="card-header d-flex align-items-center justify-content-between">
    <div class="title"><i class="bi bi-flag me-2 text-primary"></i>Countries</div>
    <div class="text-muted small" id="tableSummary">Showing 0–0 of 0</div>
  </div>
  <div class="card-body p-0 table-wrap">
    <table class="table table-hover align-middle mb-0" id="countriesTable">
      <thead>
        <tr>
          <th class="ps-3">ID</th>
          <th>Name</th>
          <th>Codes</th>
          <th>Phone</th>
          <th>Currency</th>
          <th>Timezone</th>
          <th>Locale</th>
          <th class="text-center"># BUs</th>
          <th class="text-center">Status</th>
          <th class="text-end pe-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($countries)): ?>
          <tr><td colspan="10" class="text-center py-5 text-muted">
            <div class="mb-2"><i class="bi bi-emoji-frown" style="font-size:1.25rem;"></i></div>
            No countries found. Click <strong>Add Country</strong> to create your first one.
          </td></tr>
        <?php else: foreach ($countries as $c): ?>
          <?php
            $isActive = (int)($c['active'] ?? 0) === 1;
            $canDelete = ((int)$c['bu_count'] === 0);
            $flag = flag_icon($c['iso2'] ?? '', $c['name'] ?? '');
          ?>
          <tr data-name="<?php echo strtolower(h($c['name'])); ?>"
              data-iso2="<?php echo strtolower(h($c['iso2'])); ?>"
              data-active="<?php echo $isActive ? '1' : '0'; ?>">
            <td class="ps-3 mono text-muted small"><?php echo (int)$c['id']; ?></td>
            <td class="text-nowrap">
              <?php echo $flag; ?>
              <strong><?php echo h($c['name']); ?></strong>
            </td>
            <td class="text-nowrap">
              <span class="badge badge-soft badge-iso2 badge-copy me-1" data-copy="<?php echo h($c['iso2']); ?>" data-bs-toggle="tooltip" title="Click to copy ISO2">
                <?php echo h($c['iso2']); ?>
              </span>
              <span class="badge badge-soft badge-iso3 badge-copy" data-copy="<?php echo h($c['iso3']); ?>" data-bs-toggle="tooltip" title="Click to copy ISO3">
                <?php echo h($c['iso3']); ?>
              </span>
            </td>
            <td class="text-nowrap">
              <span class="badge badge-soft badge-ph badge-copy" data-copy="<?php echo h($c['phone_country_code']); ?>" data-bs-toggle="tooltip" title="Click to copy Phone Code">
                <?php echo h($c['phone_country_code']); ?>
              </span>
            </td>
            <td class="mono text-nowrap">
              <span class="badge badge-soft badge-cur badge-copy" data-copy="<?php echo h($c['currency_code']); ?>" data-bs-toggle="tooltip" title="Click to copy Currency Code">
                <?php echo h($c['currency_code']); ?>
              </span>
            </td>
            <td><small class="text-muted"><?php echo h($c['default_tz']); ?></small></td>
            <td><small class="text-muted"><?php echo h($c['locale']); ?></small></td>
            <td class="text-center">
              <span class="badge rounded-pill <?php echo $c['bu_count']>0 ? 'text-bg-primary' : 'text-bg-secondary'; ?>">
                <?php echo (int)$c['bu_count']; ?>
              </span>
            </td>
            <td class="text-center">
              <a href="country_management.php?action=toggle_active&id=<?php echo (int)$c['id']; ?>"
                 class="text-decoration-none btn btn-outline-<?php echo $isActive ? 'success' : 'danger'; ?> btn-sm btn-toggle px-3"
                 onclick="return confirm('Toggle status for <?php echo h($c['name']); ?>?');"
                 title="<?php echo $isActive ? 'Deactivate' : 'Activate'; ?>">
                <?php if ($isActive): ?>
                  <i class="bi bi-check2-circle me-1"></i> Active
                <?php else: ?>
                  <i class="bi bi-x-octagon me-1"></i> Inactive
                <?php endif; ?>
              </a>
            </td>
            <td class="text-end pe-3">
              <div class="btn-group">
                <!-- View -->
                <button type="button" class="btn btn-info btn-sm btn-action text-white"
                  data-bs-toggle="modal" data-bs-target="#viewModal"
                  data-id="<?php echo (int)$c['id']; ?>"
                  data-name="<?php echo h($c['name']); ?>"
                  data-iso2="<?php echo h($c['iso2']); ?>"
                  data-iso3="<?php echo h($c['iso3']); ?>"
                  data-phone="<?php echo h($c['phone_country_code']); ?>"
                  data-currency="<?php echo h($c['currency_code']); ?>"
                  data-tz="<?php echo h($c['default_tz']); ?>"
                  data-locale="<?php echo h($c['locale']); ?>"
                  data-datefmt="<?php echo h($c['date_format']); ?>"
                  data-active="<?php echo $isActive ? '1' : '0'; ?>"
                  title="View details" aria-label="View">
                  <i class="bi bi-eye"></i>
                </button>

                <!-- Edit -->
                <button type="button" class="btn btn-warning btn-sm btn-action"
                  data-bs-toggle="modal" data-bs-target="#editModal"
                  data-id="<?php echo (int)$c['id']; ?>"
                  data-name="<?php echo h($c['name']); ?>"
                  data-iso2="<?php echo h($c['iso2']); ?>"
                  data-iso3="<?php echo h($c['iso3']); ?>"
                  data-phone="<?php echo h($c['phone_country_code']); ?>"
                  data-currency="<?php echo h($c['currency_code']); ?>"
                  data-tz="<?php echo h($c['default_tz']); ?>"
                  data-locale="<?php echo h($c['locale']); ?>"
                  data-datefmt="<?php echo h($c['date_format']); ?>"
                  data-active="<?php echo $isActive ? '1' : '0'; ?>"
                  title="Edit country" aria-label="Edit">
                  <i class="bi bi-pencil-square"></i>
                </button>

                <!-- Delete (fixed: removed duplicate data-bs-toggle; modal opens correctly) -->
                <?php if ($canDelete): ?>
                  <button type="button" class="btn btn-danger btn-sm btn-action"
                          data-bs-toggle="modal" data-bs-target="#confirmDeleteModal"
                          data-delete-url="country_management.php?action=delete&id=<?php echo (int)$c['id']; ?>"
                          data-delete-name="<?php echo h($c['name']); ?>"
                          title="Delete" aria-label="Delete">
                    <i class="bi bi-trash"></i>
                  </button>
                <?php else: ?>
                  <button type="button" class="btn btn-danger btn-sm btn-action" disabled
                          title="Cannot delete while BUs are linked" aria-label="Delete disabled">
                    <i class="bi bi-trash"></i>
                  </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer d-flex align-items-center justify-content-between">
    <div class="small" id="selectionHint">
      <i class="bi bi-info-circle me-1"></i>
      List
    </div>
    <nav>
      <ul class="pagination pagination-sm mb-0" id="pager">
        <li class="page-item"><button class="page-link" id="prevPage" aria-label="Previous">&laquo;</button></li>
        <li class="page-item disabled"><span class="page-link" id="pageInfo">Page 1/1</span></li>
        <li class="page-item"><button class="page-link" id="nextPage" aria-label="Next">&raquo;</button></li>
      </ul>
    </nav>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<!-- ===========================
     ADD MODAL
=========================== -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-soft">
        <h6 class="modal-title" id="addModalLabel"><i class="bi bi-plus-circle me-2 text-primary"></i>Add New Country</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="countryFormAdd" action="country_management.php" method="POST" novalidate>
        <input type="hidden" name="id" value="0">
        <!-- Hidden BU fields (auto-managed) -->
        <input type="hidden" name="bu_agency_id" id="add_bu_agency_id" value="0">
        <input type="hidden" name="bu_code" id="add_bu_code" value="">
        <input type="hidden" name="bu_name" id="add_bu_name" value="">
        <input type="hidden" name="bu_active" id="add_bu_active" value="1">

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-5">
              <label class="form-label">Country Name <span class="text-danger">*</span></label>
              <div class="input-icon">
                <i class="bi bi-flag"></i>
                <input list="countryListAdd" name="name" class="form-control form-control-lg"
                       placeholder="Type or choose a country…" required>
                <datalist id="countryListAdd"></datalist>
              </div>
              <div class="small-hint mt-2">Choosing from the list fills the rest for you.</div>
            </div>

            <div class="col-md-2">
              <label class="form-label">ISO2 <span class="text-danger">*</span></label>
              <div class="input-icon">
                <i class="bi bi-alphabet"></i>
                <input type="text" name="iso2" class="form-control form-control-lg" maxlength="2"
                       placeholder="PH" pattern="[A-Za-z]{2}" required>
              </div>
            </div>

            <div class="col-md-2">
              <label class="form-label">ISO3 <span class="text-danger">*</span></label>
              <div class="input-icon">
                <i class="bi bi-alphabet"></i>
                <input type="text" name="iso3" class="form-control form-control-lg" maxlength="3"
                       placeholder="PHL" pattern="[A-Za-z]{3}" required>
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Default Timezone</label>
              <div class="input-icon">
                <i class="bi bi-clock-history"></i>
                <input type="text" name="default_tz" class="form-control form-control-lg" placeholder="Asia/Manila">
              </div>
              <div class="small-hint">Use IANA names like <code>Asia/Manila</code>.</div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Phone Code</label>
              <div class="input-icon">
                <i class="bi bi-telephone"></i>
                <input type="text" name="phone_country_code" class="form-control form-control-lg"
                       placeholder="+63" pattern="^\+?[0-9\- ]+$">
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Currency Code</label>
              <div class="input-icon">
                <i class="bi bi-cash-coin"></i>
                <input type="text" name="currency_code" class="form-control form-control-lg" maxlength="3" placeholder="PHP" pattern="[A-Za-z]{3}">
              </div>
              <div class="small-hint">Three-letter ISO code (e.g., USD, EUR, PHP).</div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Locale</label>
              <div class="input-icon">
                <i class="bi bi-translate"></i>
                <input type="text" name="locale" class="form-control form-control-lg" placeholder="en_PH">
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Date Format</label>
              <div class="input-icon">
                <i class="bi bi-calendar3"></i>
                <input type="text" name="date_format" class="form-control form-control-lg" value="Y-m-d">
              </div>
              <div class="small-hint">PHP-like format, e.g., <code>Y-m-d</code> or <code>d/m/Y</code>.</div>
            </div>

            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="active" id="activeAdd" checked>
                <label class="form-check-label" for="activeAdd">Country is Active</label>
              </div>
            </div>
          </div>

          <hr class="hr-soft my-3">

          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-lightning-charge text-warning"></i>
            <small class="text-muted">
              A default Business Unit will be created automatically (e.g., <span class="mono">SMC-PH</span> / <span class="mono">SMC Philippines</span>).
            </small>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" name="save_country" class="btn btn-success px-4">
            <i class="bi bi-check-lg me-1"></i> Save Country
          </button>
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===========================
     VIEW MODAL
=========================== -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-soft">
        <h6 class="modal-title" id="viewModalLabel"><i class="bi bi-eye me-2 text-primary"></i>Country Details</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <dl class="row mb-0">
          <dt class="col-sm-4">Name</dt><dd class="col-sm-8" id="v_name"></dd>
          <dt class="col-sm-4">ISO2</dt><dd class="col-sm-8" id="v_iso2"></dd>
          <dt class="col-sm-4">ISO3</dt><dd class="col-sm-8" id="v_iso3"></dd>
          <dt class="col-sm-4">Timezone</dt><dd class="col-sm-8" id="v_tz"></dd>
          <dt class="col-sm-4">Phone Code</dt><dd class="col-sm-8" id="v_phone"></dd>
          <dt class="col-sm-4">Currency</dt><dd class="col-sm-8" id="v_currency"></dd>
          <dt class="col-sm-4">Locale</dt><dd class="col-sm-8" id="v_locale"></dd>
          <dt class="col-sm-4">Date Format</dt><dd class="col-sm-8" id="v_datefmt"></dd>
          <dt class="col-sm-4">Status</dt><dd class="col-sm-8" id="v_status"></dd>
        </dl>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- ===========================
     EDIT MODAL
=========================== -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-soft">
        <h6 class="modal-title" id="editModalLabel"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Country</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="countryFormEdit" action="country_management.php" method="POST" novalidate>
        <input type="hidden" name="id" id="e_id">
        <!-- For EDIT, keep BU hidden fields empty (no new BU auto-create) -->
        <input type="hidden" name="bu_agency_id" id="e_bu_agency_id" value="">
        <input type="hidden" name="bu_code" id="e_bu_code" value="">
        <input type="hidden" name="bu_name" id="e_bu_name" value="">
        <input type="hidden" name="bu_active" id="e_bu_active" value="">

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-5">
              <label class="form-label">Country Name <span class="text-danger">*</span></label>
              <div class="input-icon">
                <i class="bi bi-flag"></i>
                <input list="countryListEdit" name="name" id="e_name" class="form-control form-control-lg" required>
                <datalist id="countryListEdit"></datalist>
              </div>
            </div>

            <div class="col-md-2">
              <label class="form-label">ISO2 <span class="text-danger">*</span></label>
              <div class="input-icon">
                <i class="bi bi-alphabet"></i>
                <input type="text" name="iso2" id="e_iso2" class="form-control form-control-lg" maxlength="2" pattern="[A-Za-z]{2}" required>
              </div>
            </div>

            <div class="col-md-2">
              <label class="form-label">ISO3 <span class="text-danger">*</span></label>
              <div class="input-icon">
                <i class="bi bi-alphabet"></i>
                <input type="text" name="iso3" id="e_iso3" class="form-control form-control-lg" maxlength="3" pattern="[A-Za-z]{3}" required>
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Default Timezone</label>
              <div class="input-icon">
                <i class="bi bi-clock-history"></i>
                <input type="text" name="default_tz" id="e_tz" class="form-control form-control-lg" placeholder="Asia/Manila">
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Phone Code</label>
              <div class="input-icon">
                <i class="bi bi-telephone"></i>
                <input type="text" name="phone_country_code" id="e_phone" class="form-control form-control-lg" pattern="^\+?[0-9\- ]+$">
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Currency Code</label>
              <div class="input-icon">
                <i class="bi bi-cash-coin"></i>
                <input type="text" name="currency_code" id="e_currency" class="form-control form-control-lg" maxlength="3" pattern="[A-Za-z]{3}">
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Locale</label>
              <div class="input-icon">
                <i class="bi bi-translate"></i>
                <input type="text" name="locale" id="e_locale" class="form-control form-control-lg">
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Date Format</label>
              <div class="input-icon">
                <i class="bi bi-calendar3"></i>
                <input type="text" name="date_format" id="e_datefmt" class="form-control form-control-lg">
              </div>
            </div>

            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="active" id="e_active">
                <label class="form-check-label" for="e_active">Country is Active</label>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" name="save_country" class="btn btn-success px-4">
            <i class="bi bi-check-lg me-1"></i> Save Changes
          </button>
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===========================
     CONFIRM DELETE MODAL
=========================== -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-soft">
        <h6 class="modal-title" id="confirmDeleteLabel"><i class="bi bi-trash me-2 text-danger"></i>Delete Country</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Are you sure you want to permanently delete <strong id="delTargetName">this country</strong>? This cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <a id="confirmDeleteBtn" class="btn btn-danger"><i class="bi bi-trash me-1"></i> Delete</a>
      </div>
    </div>
  </div>
</div>

<!-- ===========================
     SMART AUTOFILL & UI BEHAVIOR
=========================== -->
<script src="../js/country_ref.js"></script>
<script>
// QSA helpers
const $  = (sel, root=document) => root.querySelector(sel);
const $$ = (sel, root=document) => [...root.querySelectorAll(sel)];

// ---------- Tooltips (only where data-bs-toggle="tooltip") ----------
document.addEventListener('DOMContentLoaded', () => {
  $$('.badge-copy,[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
});

// ---------- Populate datalists ----------
function populateDatalist(datalistEl) {
  if (!datalistEl || !window.COUNTRY_REF) return;
  [...COUNTRY_REF].sort((a,b)=>a.name.localeCompare(b.name))
    .forEach(c => {
      const opt = document.createElement('option');
      opt.value = c.name;
      datalistEl.appendChild(opt);
    });
}
populateDatalist(document.getElementById('countryListAdd'));
populateDatalist(document.getElementById('countryListEdit'));

// ---------- Helpers ----------
function deriveLocale(code){ return code ? ('en_' + code) : ''; }
function toUpper(el){ el && (el.value = (el.value || '').toUpperCase()); }
function copyText(t){
  if (!t) return;
  navigator.clipboard?.writeText(t).then(()=>{}).catch(()=>{});
}
$$('.badge-copy').forEach(b=>{
  b.addEventListener('click', () => {
    copyText(b.getAttribute('data-copy') || b.textContent.trim());
    const tip = bootstrap.Tooltip.getInstance(b);
    if (tip) {
      tip.hide(); b.setAttribute('data-bs-original-title','Copied!'); tip.show();
      setTimeout(()=>{ tip.hide(); b.setAttribute('data-bs-original-title','Click to copy'); },900);
    }
  });
});

// ---------- Smart autofill binding (Add) ----------
(function bindAdd(){
  const form   = document.getElementById('countryFormAdd');
  if (!form) return;
  const name   = form.querySelector('[name="name"]');
  const iso2   = form.querySelector('[name="iso2"]');
  const iso3   = form.querySelector('[name="iso3"]');
  const phone  = form.querySelector('[name="phone_country_code"]');
  const curr   = form.querySelector('[name="currency_code"]');
  const tz     = form.querySelector('[name="default_tz"]');
  const locale = form.querySelector('[name="locale"]');
  const datef  = form.querySelector('[name="date_format"]');

  // Hidden BU fields (auto-managed)
  const buCode = document.getElementById('add_bu_code');
  const buName = document.getElementById('add_bu_name');
  const buAgency = document.getElementById('add_bu_agency_id');
  const buActive = document.getElementById('add_bu_active');

  const up = (e)=> toUpper(e.target);
  iso2?.addEventListener('input', up);
  iso3?.addEventListener('input', up);
  curr?.addEventListener('input', up);

  function suggestBU() {
    const code = (iso2?.value || '').toUpperCase();
    const nm   = (name?.value || '').trim();
    if (buAgency && !buAgency.value) buAgency.value = '2';
    if (buActive && !buActive.value) buActive.value = '1';
    if (buCode) buCode.value = code ? `SMC-${code}` : '';
    if (buName) buName.value = nm ? `SMC ${nm}` : '';
  }

  function fillFromMatch(m) {
    if (!m) return;
    if (iso2 && !iso2.value) iso2.value = m.iso2;
    if (iso3 && !iso3.value) iso3.value = m.iso3;
    if (phone && !phone.value) phone.value = m.phone;
    if (curr && !curr.value) curr.value = m.currency;
    if (tz && !tz.value) tz.value = m.tz;
    if (locale && !locale.value) locale.value = m.locale || deriveLocale(m.iso2);
    if (datef && !datef.value) datef.value = 'Y-m-d';
    suggestBU();
  }

  name?.addEventListener('change', () => {
    const v = (name.value||'').trim().toLowerCase();
    const m = (window.COUNTRY_REF||[]).find(c => c.name.toLowerCase() === v);
    fillFromMatch(m);
  });
  iso2?.addEventListener('blur', () => {
    const code = (iso2.value||'').toUpperCase();
    const m = (window.COUNTRY_REF||[]).find(c => c.iso2 === code);
    if (m && name && !name.value) name.value = m.name;
    fillFromMatch(m);
  });
  name?.addEventListener('input', suggestBU);
  iso2?.addEventListener('input', suggestBU);

  form.addEventListener('submit', (e) => {
    if (iso2 && iso2.value.length !== 2) { alert('ISO2 must be 2 characters.'); e.preventDefault(); iso2.focus(); return; }
    if (iso3 && iso3.value.length !== 3) { alert('ISO3 must be 3 characters.'); e.preventDefault(); iso3.focus(); return; }
    toUpper(iso2); toUpper(iso3); toUpper(curr);
    suggestBU(); // final sync for hidden BU fields
  });
})();

// ---------- Smart autofill binding (Edit) ----------
(function bindEdit(){
  const form   = document.getElementById('countryFormEdit');
  if (!form) return;
  const name   = form.querySelector('[name="name"]');
  const iso2   = form.querySelector('[name="iso2"]');
  const iso3   = form.querySelector('[name="iso3"]');
  const phone  = form.querySelector('[name="phone_country_code"]');
  const curr   = form.querySelector('[name="currency_code"]');
  const tz     = form.querySelector('[name="default_tz"]');
  const locale = form.querySelector('[name="locale"]');
  const datef  = form.querySelector('[name="date_format"]');
  const up = (e)=> toUpper(e.target);
  iso2?.addEventListener('input', up);
  iso3?.addEventListener('input', up);
  curr?.addEventListener('input', up);

  function fillFromMatch(m) {
    if (!m) return;
    if (iso2 && !iso2.value) iso2.value = m.iso2;
    if (iso3 && !iso3.value) iso3.value = m.iso3;
    if (phone && !phone.value) phone.value = m.phone;
    if (curr && !curr.value) curr.value = m.currency;
    if (tz && !tz.value) tz.value = m.tz;
    if (locale && !locale.value) locale.value = m.locale || ('en_' + m.iso2);
    if (datef && !datef.value) datef.value = 'Y-m-d';
  }

  name?.addEventListener('change', () => {
    const v = (name.value||'').trim().toLowerCase();
    const m = (window.COUNTRY_REF||[]).find(c => c.name.toLowerCase() === v);
    fillFromMatch(m);
  });
  iso2?.addEventListener('blur', () => {
    const code = (iso2.value||'').toUpperCase();
    const m = (window.COUNTRY_REF||[]).find(c => c.iso2 === code);
    if (m && name && !name.value) name.value = m.name;
    fillFromMatch(m);
  });

  form.addEventListener('submit', (e) => {
    if (iso2 && iso2.value.length !== 2) { alert('ISO2 must be 2 characters.'); e.preventDefault(); iso2.focus(); return; }
    if (iso3 && iso3.value.length !== 3) { alert('ISO3 must be 3 characters.'); e.preventDefault(); iso3.focus(); return; }
    toUpper(iso2); toUpper(iso3); toUpper(curr);
  });
})();

// ---------- View modal population ----------
const viewModal = document.getElementById('viewModal');
viewModal?.addEventListener('show.bs.modal', (ev) => {
  const b = ev.relatedTarget;
  const d = (a)=>b.getAttribute(a)||'';
  document.getElementById('v_name').textContent     = d('data-name');
  document.getElementById('v_iso2').textContent     = d('data-iso2');
  document.getElementById('v_iso3').textContent     = d('data-iso3');
  document.getElementById('v_tz').textContent       = d('data-tz');
  document.getElementById('v_phone').textContent    = d('data-phone');
  document.getElementById('v_currency').textContent = d('data-currency');
  document.getElementById('v_locale').textContent   = d('data-locale');
  document.getElementById('v_datefmt').textContent  = d('data-datefmt') || 'Y-m-d';
  document.getElementById('v_status').textContent   = d('data-active')==='1' ? 'Active' : 'Inactive';
});

// ---------- Edit modal population ----------
const editModal = document.getElementById('editModal');
editModal?.addEventListener('show.bs.modal', (ev) => {
  const b = ev.relatedTarget;
  const d = (a)=>b.getAttribute(a)||'';

  document.getElementById('e_id').value       = d('data-id');
  document.getElementById('e_name').value     = d('data-name');
  document.getElementById('e_iso2').value     = d('data-iso2');
  document.getElementById('e_iso3').value     = d('data-iso3');
  document.getElementById('e_tz').value       = d('data-tz');
  document.getElementById('e_phone').value    = d('data-phone');
  document.getElementById('e_currency').value = d('data-currency');
  document.getElementById('e_locale').value   = d('data-locale');
  document.getElementById('e_datefmt').value  = d('data-datefmt') || 'Y-m-d';
  document.getElementById('e_active').checked = (d('data-active')==='1');

  // Keep hidden BU fields empty on edit (prevent auto BU creation)
  document.getElementById('e_bu_agency_id').value = '';
  document.getElementById('e_bu_code').value = '';
  document.getElementById('e_bu_name').value = '';
  document.getElementById('e_bu_active').value = '';
});

// ---------- Delete confirmation modal ----------
const confirmDeleteModal = document.getElementById('confirmDeleteModal');
confirmDeleteModal?.addEventListener('show.bs.modal', (ev) => {
  const b = ev.relatedTarget;
  const url = b.getAttribute('data-delete-url');
  const nm  = b.getAttribute('data-delete-name') || 'this country';
  document.getElementById('delTargetName').textContent = nm;
  const link = document.getElementById('confirmDeleteBtn');
  link.setAttribute('href', url);
});

// ---------- Table quick filter + status + pagination ----------
const tblFilter = document.getElementById('tblFilter');
const statusFilter = document.getElementById('statusFilter');
const table = document.getElementById('countriesTable');
const tbody = table?.querySelector('tbody');
const pageInfo = document.getElementById('pageInfo');
const tableSummary = document.getElementById('tableSummary');
const prevBtn = document.getElementById('prevPage');
const nextBtn = document.getElementById('nextPage');

// Fixed page size (removed UI control)
const PAGE_SIZE = 25;
let page = 1;

function getRows() {
  return [...tbody.querySelectorAll('tr')];
}
function matchesFilter(tr, q, status) {
  const name = tr.getAttribute('data-name') || '';
  const iso2 = tr.getAttribute('data-iso2') || '';
  const act  = tr.getAttribute('data-active') || '';
  const qok  = (!q) || name.includes(q) || iso2.includes(q);
  const sok  = (!status) || (act === status);
  return qok && sok;
}
function applyFilters() {
  const q = (tblFilter.value||'').trim().toLowerCase();
  const st = statusFilter.value || '';
  const rows = getRows();
  rows.forEach(tr => tr.style.display = 'none');
  const filtered = rows.filter(tr => matchesFilter(tr, q, st));
  const size = PAGE_SIZE;
  const total = filtered.length;
  const maxPage = Math.max(1, Math.ceil(total / size));
  page = Math.min(page, maxPage);

  filtered.forEach((tr, idx) => {
    const start = (page - 1) * size;
    const end = start + size;
    if (idx >= start && idx < end) tr.style.display = '';
  });

  const startN = total ? ( (page-1)*size + 1 ) : 0;
  const endN = Math.min(page*size, total);
  if (tableSummary) tableSummary.textContent = `Showing ${startN}–${endN} of ${total}`;
  if (pageInfo) pageInfo.textContent = `Page ${page}/${Math.max(1, maxPage)}`;
  prevBtn?.classList.toggle('disabled', page <= 1);
  nextBtn?.classList.toggle('disabled', page >= maxPage);
}
tblFilter?.addEventListener('input', ()=>{ page=1; applyFilters(); });
statusFilter?.addEventListener('change', ()=>{ page=1; applyFilters(); });
prevBtn?.addEventListener('click', ()=>{ if (!prevBtn.classList.contains('disabled')) { page--; applyFilters(); }});
nextBtn?.addEventListener('click', ()=>{ if (!nextBtn.classList.contains('disabled')) { page++; applyFilters(); }});
document.addEventListener('DOMContentLoaded', applyFilters);

// ---------- Re-open modal with posted data on validation errors ----------
<?php if (!empty($errors) && !empty($reopenModal)): ?>
(function(){
  const mode = <?php echo json_encode($reopenModal); ?>;
  const data = <?php echo json_encode($postedData ?? []); ?>;
  const open = () => {
    if (mode === 'add') {
      const modal = new bootstrap.Modal(document.getElementById('addModal'));
      modal.show();
      const f = document.getElementById('countryFormAdd');
      if (!f) return;
      Object.entries(data).forEach(([k,v])=>{
        const el = f.querySelector(`[name="${k}"]`);
        if (!el) return;
        if (el.type === 'checkbox') el.checked = (v==1);
        else el.value = v ?? '';
      });
    } else if (mode === 'edit') {
      const modal = new bootstrap.Modal(document.getElementById('editModal'));
      modal.show();
      const f = document.getElementById('countryFormEdit');
      if (!f) return;
      const map = { id:'e_id', name:'e_name', iso2:'e_iso2', iso3:'e_iso3', default_tz:'e_tz',
                    phone_country_code:'e_phone', currency_code:'e_currency', locale:'e_locale',
                    date_format:'e_datefmt', active:'e_active' };
      Object.entries(data).forEach(([k,v])=>{
        const sel = map[k] ? `#${map[k]}` : `[name="${k}"]`;
        const el = document.querySelector(sel);
        if (!el) return;
        if (el.type === 'checkbox') el.checked = (v==1);
        else el.value = v ?? '';
      });
    }
  };
  if (document.readyState === 'complete') open();
  else window.addEventListener('load', open);
})();
<?php endif; ?>
</script>