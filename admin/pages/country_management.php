<?php
$pageTitle = 'Country Management';
require_once '../includes/header.php';

/* =========================================================================================
   RBAC (defense in depth): Only Admins and Super Admins can access
========================================================================================= */
if (!$isAdmin && !$isSuperAdmin) {
    setFlashMessage('error', 'You do not have permission to access this page.');
    redirect('dashboard.php');
}

/* =========================================================================================
   Schema guard: ensure countries.id is AUTO_INCREMENT primary key (+ fix id=0 rows)
========================================================================================= */
if ($conn instanceof mysqli) {
    // Ensure PK exists
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
    // Fix any id=0
    if ($fix = $conn->query("SELECT COUNT(*) AS cnt FROM countries WHERE id = 0")) {
        $cnt = (int)($fix->fetch_assoc()['cnt'] ?? 0);
        $fix->close();
        if ($cnt > 0) {
            $nRes = $conn->query("SELECT IFNULL(MAX(id),0)+1 AS nxt FROM countries");
            $nxt = (int)($nRes->fetch_assoc()['nxt'] ?? 1);
            $nRes && $nRes->close();
            while ($cnt--) { @$conn->query("UPDATE countries SET id={$nxt} WHERE id=0 LIMIT 1"); $nxt++; }
        }
    }
}

/* =========================================================================================
   Helpers
========================================================================================= */
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

/* =========================================================================================
   Actions: toggle active / delete
========================================================================================= */
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
        // Allow delete only when no BUs linked
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

/* =========================================================================================
   Create / Update (with optional BU creation) — BU fields always enabled (no toggle)
========================================================================================= */
$errors = [];
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

    // BU (always enabled; creation is optional -> inferred if code or name provided)
    $bu_agency_id = (int)($_POST['bu_agency_id'] ?? 0);
    $bu_code      = trim((string)($_POST['bu_code'] ?? ''));
    $bu_name      = trim((string)($_POST['bu_name'] ?? ''));
    $bu_active    = isset($_POST['bu_active']) ? 1 : 0;

    $wantsLinkedBU = ($bu_code !== '' || $bu_name !== '' || $bu_agency_id > 0);

    // Validation
    if (strlen($iso2) !== 2) $errors[] = "ISO2 must be exactly 2 characters.";
    if (strlen($iso3) !== 3) $errors[] = "ISO3 must be exactly 3 characters.";
    if ($name === '')        $errors[] = "Country Name is required.";

    // Uniqueness on iso2/iso3/name
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

    // If admin entered any BU fields, require code+name
    if ($wantsLinkedBU) {
        if ($bu_code === '') $errors[] = "BU Code is required when creating a BU.";
        if ($bu_name === '') $errors[] = "BU Name is required when creating a BU.";
    }

    if (empty($errors)) {
        $saved = false;
        $countryId = $id;

        if ($id > 0) {
            $sql = "UPDATE countries
                       SET iso2=?, iso3=?, name=?, default_tz=?, phone_country_code=?, currency_code=?, locale=?, date_format=?, active=?
                     WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssssssii',
                $iso2, $iso3, $name, $default_tz, $phone_country_code, $currency_code, $locale, $date_format, $active, $id
            );
            if ($stmt->execute()) $saved = true;
            else $errors[] = "Database error (update): " . $conn->error;
            $stmt->close();
        } else {
            $sql = "INSERT INTO countries (iso2, iso3, name, default_tz, phone_country_code, currency_code, locale, date_format, active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssssssi',
                $iso2, $iso3, $name, $default_tz, $phone_country_code, $currency_code, $locale, $date_format, $active
            );
            if ($stmt->execute()) { $saved = true; $countryId = $conn->insert_id; }
            else $errors[] = "Database error (insert): " . $conn->error;
            $stmt->close();
        }

        // Linked BU
        if ($saved && $wantsLinkedBU) {
            // unique code
            $chk = $conn->prepare("SELECT COUNT(*) FROM business_units WHERE code = ?");
            $chk->bind_param('s', $bu_code);
            $chk->execute();
            $exists = (int)($chk->get_result()->fetch_row()[0] ?? 0);
            $chk->close();

            if ($exists > 0) {
                $errors[] = "A Business Unit with code '{$bu_code}' already exists.";
            } else {
                $sqlBU = "INSERT INTO business_units (agency_id, country_id, code, name, active, created_at, updated_at)
                          VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
                $stmtBU = $conn->prepare($sqlBU);
                $stmtBU->bind_param('iissi', $bu_agency_id, $countryId, $bu_code, $bu_name, $bu_active);
                if (!$stmtBU->execute()) $errors[] = "Failed to create linked Business Unit: " . $conn->error;
                $stmtBU->close();
            }
        }

        if (empty($errors)) {
            setFlashMessage('success', $wantsLinkedBU ? 'Country and linked BU saved successfully.' : 'Country saved successfully.');
            redirect('country_management.php');
        }
    }
}

/* =========================================================================================
   Load countries with BU counts
========================================================================================= */
$countries = [];
$sql = "SELECT c.*, (SELECT COUNT(*) FROM business_units WHERE country_id = c.id) AS bu_count
        FROM countries c
        ORDER BY c.name ASC";
$res = $conn->query($sql);
if ($res) {
    $countries = $res->fetch_all(MYSQLI_ASSOC);
    $res->close();
}

/* =========================================================================================
   (Optional) Prepare one record for editing via modal (if requested)
   We don’t preload form; the edit modal gets data attributes from row button.
========================================================================================= */
?>
<style>
/* Tiny visual polish to match your screenshots */
.badge-soft { border:1px solid rgba(0,0,0,.08); }
.badge-code { background:#eef2ff; color:#1d4ed8; }
.badge-iso2 { background:#f1f5f9; color:#0f172a; }
.badge-iso3 { background:#e0f2fe; color:#075985; }
.mono { font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
.btn-pill { border-radius: 10rem; }
.btn-action { min-width: 36px; }
.table td, .table th { vertical-align: middle; }
.row-muted { color:#64748b; }
.country-flag { font-size: 1.15rem; margin-right:.35rem; }
</style>

<div class="row mb-4">
    <div class="col">
        <h4 class="mb-0">Country Management</h4>
        <p class="text-muted small">Manage global countries and their regional settings.</p>
    </div>
    <div class="col-auto d-flex align-items-center gap-2">
        <input id="tblFilter" type="search" class="form-control form-control-sm" placeholder="Search countries...">
        <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#countryFormSection">
            <i class="bi bi-plus-lg me-1"></i> Add New Country
        </button>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?><li><?php echo h($err); ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- =========================================================
     ADD NEW COUNTRY (with always-enabled BU section)
========================================================= -->
<div class="collapse <?php echo (!empty($errors)) ? 'show' : ''; ?> mb-4" id="countryFormSection">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 pt-3">
            <h6 class="mb-0">Add New Country</h6>
        </div>
        <div class="card-body">
            <form action="country_management.php" method="POST" id="countryFormAdd">
                <input type="hidden" name="id" value="0">

                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">Country Name <span class="text-danger">*</span></label>
                        <input list="countryListAdd" name="name" class="form-control" placeholder="e.g. Philippines" required>
                        <datalist id="countryListAdd"></datalist>
                        <div class="form-text">Pick from the list to auto-fill ISO, phone, currency, timezone and locale.</div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">ISO2 <span class="text-danger">*</span></label>
                        <input type="text" name="iso2" class="form-control" maxlength="2" placeholder="PH" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">ISO3 <span class="text-danger">*</span></label>
                        <input type="text" name="iso3" class="form-control" maxlength="3" placeholder="PHL" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Default Timezone</label>
                        <input type="text" name="default_tz" class="form-control" placeholder="Asia/Manila">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Phone Code</label>
                        <input type="text" name="phone_country_code" class="form-control" placeholder="+63">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Currency Code</label>
                        <input type="text" name="currency_code" class="form-control" maxlength="3" placeholder="PHP">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Locale</label>
                        <input type="text" name="locale" class="form-control" placeholder="en_PH">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Date Format</label>
                        <input type="text" name="date_format" class="form-control" value="Y-m-d">
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="active" id="activeAdd" checked>
                            <label class="form-check-label" for="activeAdd">Country is Active</label>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0">Linked Business Unit <span class="text-muted small">(optional)</span></h6>
                    <span class="badge rounded-pill text-bg-light">Enabled</span>
                </div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Agency ID</label>
                        <input type="number" min="0" name="bu_agency_id" class="form-control" placeholder="e.g. 1">
                        <div class="form-text">Leave 0 if you don't use agencies.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">BU Code</label>
                        <input type="text" name="bu_code" class="form-control" placeholder="e.g. SMC-PH">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">BU Name</label>
                        <input type="text" name="bu_name" class="form-control" placeholder="e.g. SMC Philippines">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold d-block">BU Active</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="bu_active" id="buActiveAdd" checked>
                            <label class="form-check-label" for="buActiveAdd">Active</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top d-flex gap-2">
                    <button type="submit" name="save_country" class="btn btn-success px-4">
                        <i class="bi bi-check-lg me-1"></i> Save Country
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#countryFormSection">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =========================================================
     COUNTRIES TABLE
========================================================= -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="countriesTable">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Name</th>
                        <th>Codes</th>
                        <th>Phone</th>
                        <th>Currency</th>
                        <th>Locale</th>
                        <th class="text-center"># BUs</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($countries)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">No countries found.</td></tr>
                    <?php else: foreach ($countries as $c): ?>
                        <?php
                            $flag  = flag_emoji_from_iso2($c['iso2'] ?? '');
                            $isActive = (int)($c['active'] ?? 0) === 1;
                            $canDelete = ((int)$c['bu_count'] === 0);
                        ?>
                        <tr data-name="<?php echo strtolower(h($c['name'])); ?>" data-iso2="<?php echo strtolower(h($c['iso2'])); ?>">
                            <td class="ps-3 mono text-muted small"><?php echo (int)$c['id']; ?></td>
                            <td>
                                <span class="country-flag"><?php echo $flag; ?></span>
                                <strong><?php echo h($c['name']); ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-soft badge-iso2"><?php echo h($c['iso2']); ?></span>
                                <span class="badge badge-soft badge-iso3"><?php echo h($c['iso3']); ?></span>
                            </td>
                            <td><code class="text-danger"><?php echo h($c['phone_country_code']); ?></code></td>
                            <td class="mono"><?php echo h($c['currency_code']); ?></td>
                            <td><small class="text-muted"><?php echo h($c['locale']); ?></small></td>
                            <td class="text-center">
                                <span class="badge rounded-pill <?php echo $c['bu_count'] > 0 ? 'text-bg-primary' : 'text-bg-light'; ?>">
                                    <?php echo (int)$c['bu_count']; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="country_management.php?action=toggle_active&id=<?php echo (int)$c['id']; ?>"
                                   class="text-decoration-none"
                                   onclick="return confirm('Toggle status for <?php echo h($c['name']); ?>?');"
                                   title="Click to <?php echo $isActive ? 'Deactivate' : 'Activate'; ?>">
                                   <?php if ($isActive): ?>
                                       <span class="badge bg-success-subtle text-success border-success-subtle border px-3">Active</span>
                                   <?php else: ?>
                                       <span class="badge bg-danger-subtle text-danger border-danger-subtle border px-3">Inactive</span>
                                   <?php endif; ?>
                                </a>
                            </td>
                            <td class="text-end pe-3">
                                <div class="btn-group">
                                    <!-- View modal -->
                                    <button type="button" class="btn btn-info btn-sm btn-pill btn-action text-white"
                                        data-bs-toggle="modal" data-bs-target="#viewModal"
                                        data-id="<?php echo (int)$c['id']; ?>"
                                        data-name="<?php echo h($c['name']); ?>"
                                        data-iso2="<?php echo h($c['iso2']); ?>"
                                        data-iso3="<?php echo h($c['iso3']); ?>"
                                        data-phone="<?php echo h($c['phone_country_code']); ?>"
                                        data-currency="<?php echo h($c['currency_code']); ?>"
                                        data-locale="<?php echo h($c['locale']); ?>"
                                        data-tz="<?php echo h($c['default_tz']); ?>"
                                        data-datefmt="<?php echo h($c['date_format']); ?>"
                                        data-active="<?php echo $isActive ? '1' : '0'; ?>">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <!-- Edit modal -->
                                    <button type="button" class="btn btn-warning btn-sm btn-pill btn-action"
                                        data-bs-toggle="modal" data-bs-target="#editModal"
                                        data-id="<?php echo (int)$c['id']; ?>"
                                        data-name="<?php echo h($c['name']); ?>"
                                        data-iso2="<?php echo h($c['iso2']); ?>"
                                        data-iso3="<?php echo h($c['iso3']); ?>"
                                        data-phone="<?php echo h($c['phone_country_code']); ?>"
                                        data-currency="<?php echo h($c['currency_code']); ?>"
                                        data-locale="<?php echo h($c['locale']); ?>"
                                        data-tz="<?php echo h($c['default_tz']); ?>"
                                        data-datefmt="<?php echo h($c['date_format']); ?>"
                                        data-active="<?php echo $isActive ? '1' : '0'; ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <!-- Delete -->
                                    <?php if ($canDelete): ?>
                                        <a href="country_management.php?action=delete&id=<?php echo (int)$c['id']; ?>"
                                           class="btn btn-danger btn-sm btn-pill btn-action"
                                           onclick="return confirm('Permanently delete <?php echo h($c['name']); ?>? This cannot be undone.');"
                                           title="Delete">
                                           <i class="bi bi-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-danger btn-sm btn-pill btn-action" disabled
                                                title="Cannot delete while BUs are linked">
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
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<!-- =========================================================
     View Modal
========================================================= -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title" id="viewModalLabel">Country Details</h6>
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

<!-- =========================================================
     Edit Modal (posts back to this page)
========================================================= -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title" id="editModalLabel">Edit Country</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="country_management.php" method="POST" id="countryFormEdit">
        <div class="modal-body">
          <input type="hidden" name="id" id="e_id">
          <div class="row g-3 align-items-end">
            <div class="col-md-5">
              <label class="form-label small fw-bold">Country Name <span class="text-danger">*</span></label>
              <input list="countryListEdit" name="name" id="e_name" class="form-control" required>
              <datalist id="countryListEdit"></datalist>
            </div>
            <div class="col-md-2">
              <label class="form-label small fw-bold">ISO2 <span class="text-danger">*</span></label>
              <input type="text" name="iso2" id="e_iso2" class="form-control" maxlength="2" required>
            </div>
            <div class="col-md-2">
              <label class="form-label small fw-bold">ISO3 <span class="text-danger">*</span></label>
              <input type="text" name="iso3" id="e_iso3" class="form-control" maxlength="3" required>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold">Default Timezone</label>
              <input type="text" name="default_tz" id="e_tz" class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label small fw-bold">Phone Code</label>
              <input type="text" name="phone_country_code" id="e_phone" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold">Currency Code</label>
              <input type="text" name="currency_code" id="e_currency" class="form-control" maxlength="3">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold">Locale</label>
              <input type="text" name="locale" id="e_locale" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold">Date Format</label>
              <input type="text" name="date_format" id="e_datefmt" class="form-control">
            </div>

            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="active" id="e_active">
                <label class="form-check-label" for="e_active">Country is Active</label>
              </div>
            </div>
          </div>

          <hr class="my-4">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0">Linked Business Unit <span class="text-muted small">(optional)</span></h6>
            <span class="badge rounded-pill text-bg-light">Enabled</span>
          </div>

          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label small fw-bold">Agency ID</label>
              <input type="number" min="0" name="bu_agency_id" id="e_bu_agency_id" class="form-control" placeholder="e.g. 1">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-bold">BU Code</label>
              <input type="text" name="bu_code" id="e_bu_code" class="form-control" placeholder="e.g. SMC-PH">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">BU Name</label>
              <input type="text" name="bu_name" id="e_bu_name" class="form-control" placeholder="e.g. SMC Philippines">
            </div>
            <div class="col-md-2">
              <label class="form-label small fw-bold d-block">BU Active</label>
              <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="bu_active" id="e_bu_active" checked>
                <label class="form-check-label" for="e_bu_active">Active</label>
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

<!-- =========================================================
     SMART AUTOFILL + UI BEHAVIOR
========================================================= -->
<script>
/* ==== Reference dataset (extend as needed) ==== */
const COUNTRY_REF = [
  {name:'Bahrain',iso2:'BH',iso3:'BHR',phone:'+973',currency:'BHD',tz:'Asia/Bahrain',locale:'ar_BH'},
  {name:'Cambodia',iso2:'KH',iso3:'KHM',phone:'+855',currency:'KHR',tz:'Asia/Phnom_Penh',locale:'km_KH'},
  {name:'China',iso2:'CN',iso3:'CHN',phone:'+86',currency:'CNY',tz:'Asia/Shanghai',locale:'zh_CN'},
  {name:'Japan',iso2:'JP',iso3:'JPN',phone:'+81',currency:'JPY',tz:'Asia/Tokyo',locale:'ja_JP'},
  {name:'Philippines',iso2:'PH',iso3:'PHL',phone:'+63',currency:'PHP',tz:'Asia/Manila',locale:'en_PH'},
  {name:'Turkey',iso2:'TR',iso3:'TUR',phone:'+90',currency:'TRY',tz:'Europe/Istanbul',locale:'tr_TR'},
  {name:'United Arab Emirates',iso2:'AE',iso3:'ARE',phone:'+971',currency:'AED',tz:'Asia/Dubai',locale:'ar_AE'},
  {name:'United States',iso2:'US',iso3:'USA',phone:'+1',currency:'USD',tz:'America/New_York',locale:'en_US'},
  {name:'United Kingdom',iso2:'GB',iso3:'GBR',phone:'+44',currency:'GBP',tz:'Europe/London',locale:'en_GB'},
  {name:'Vietnam',iso2:'VN',iso3:'VNM',phone:'+84',currency:'VND',tz:'Asia/Ho_Chi_Minh',locale:'vi_VN'},
  // add more here freely…
];

/* ===== Utility ===== */
const $ = (sel, root=document) => root.querySelector(sel);
function populateDatalist(datalistEl) {
  [...COUNTRY_REF].sort((a,b)=>a.name.localeCompare(b.name))
    .forEach(c => {
      const o = document.createElement('option');
      o.value = c.name;
      datalistEl.appendChild(o);
    });
}
function bindSmartAutofill(formEl, opts={}) {
  const name   = $('[name="name"]', formEl);
  const iso2   = $('[name="iso2"]', formEl);
  const iso3   = $('[name="iso3"]', formEl);
  const phone  = $('[name="phone_country_code"]', formEl);
  const curr   = $('[name="currency_code"]', formEl);
  const tz     = $('[name="default_tz"]', formEl);
  const locale = $('[name="locale"]', formEl);
  const datef  = $('[name="date_format"]', formEl);

  function upper(e){ e.target.value = (e.target.value || '').toUpperCase(); }
  iso2?.addEventListener('input', upper);
  iso3?.addEventListener('input', upper);
  curr?.addEventListener('input', upper);

  function deriveLocale(code){ return code ? ('en_' + code) : ''; }

  function fillFromMatch(m) {
    if (!m) return;
    if (iso2 && !iso2.value) iso2.value = m.iso2;
    if (iso3 && !iso3.value) iso3.value = m.iso3;
    if (phone && !phone.value) phone.value = m.phone;
    if (curr && !curr.value) curr.value = m.currency;
    if (tz && !tz.value) tz.value = m.tz;
    if (locale && !locale.value) locale.value = m.locale || deriveLocale(m.iso2);
    if (datef && !datef.value) datef.value = 'Y-m-d';
  }

  name?.addEventListener('change', () => {
    const v = (name.value||'').trim().toLowerCase();
    fillFromMatch(COUNTRY_REF.find(c => c.name.toLowerCase() === v));
  });
  iso2?.addEventListener('blur', () => {
    const code = (iso2.value||'').toUpperCase();
    const m = COUNTRY_REF.find(c => c.iso2 === code);
    if (!m) return;
    if (name && !name.value) name.value = m.name;
    fillFromMatch(m);
  });

  formEl?.addEventListener('submit', (e) => {
    if (iso2 && iso2.value.length !== 2) { alert('ISO2 must be 2 characters.'); e.preventDefault(); iso2.focus(); }
    if (iso3 && iso3.value.length !== 3) { alert('ISO3 must be 3 characters.'); e.preventDefault(); iso3.focus(); }
    // normalize
    if (iso2) iso2.value = (iso2.value||'').toUpperCase();
    if (iso3) iso3.value = (iso3.value||'').toUpperCase();
    if (curr) curr.value = (curr.value||'').toUpperCase();
  });
}

/* ===== Prepare Datalists & Bind forms ===== */
populateDatalist(document.getElementById('countryListAdd'));
populateDatalist(document.getElementById('countryListEdit'));
bindSmartAutofill(document.getElementById('countryFormAdd'));
bindSmartAutofill(document.getElementById('countryFormEdit'));

/* ===== Search filter ===== */
const tblFilter = document.getElementById('tblFilter');
const table = document.getElementById('countriesTable');
tblFilter?.addEventListener('input', () => {
  const q = (tblFilter.value || '').trim().toLowerCase();
  table.querySelectorAll('tbody tr').forEach(tr => {
    const n = tr.getAttribute('data-name') || '';
    const iso2 = tr.getAttribute('data-iso2') || '';
    tr.style.display = (n.includes(q) || iso2.includes(q)) ? '' : 'none';
  });
});

/* ===== View Modal population ===== */
const viewModal = document.getElementById('viewModal');
viewModal?.addEventListener('show.bs.modal', (ev) => {
  const btn = ev.relatedTarget;
  const d = (attr) => btn.getAttribute(attr) || '';
  $('#viewModalLabel').textContent = 'Country Details';
  $('#v_name').textContent    = d('data-name');
  $('#v_iso2').textContent    = d('data-iso2');
  $('#v_iso3').textContent    = d('data-iso3');
  $('#v_tz').textContent      = d('data-tz');
  $('#v_phone').textContent   = d('data-phone');
  $('#v_currency').textContent= d('data-currency');
  $('#v_locale').textContent  = d('data-locale');
  $('#v_datefmt').textContent = d('data-datefmt') || 'Y-m-d';
  $('#v_status').textContent  = d('data-active') === '1' ? 'Active' : 'Inactive';
});

/* ===== Edit Modal population ===== */
const editModal = document.getElementById('editModal');
editModal?.addEventListener('show.bs.modal', (ev) => {
  const btn = ev.relatedTarget;
  const d = (attr) => btn.getAttribute(attr) || '';

  // Fill fields
  $('#e_id').value       = d('data-id');
  $('#e_name').value     = d('data-name');
  $('#e_iso2').value     = d('data-iso2');
  $('#e_iso3').value     = d('data-iso3');
  $('#e_tz').value       = d('data-tz');
  $('#e_phone').value    = d('data-phone');
  $('#e_currency').value = d('data-currency');
  $('#e_locale').value   = d('data-locale');
  $('#e_datefmt').value  = d('data-datefmt') || 'Y-m-d';
  $('#e_active').checked = (d('data-active') === '1');

  // Suggest BU defaults based on ISO2/name if empty
  const iso2 = (d('data-iso2') || '').toUpperCase();
  const name = d('data-name') || '';
  const buCodeEl = $('#e_bu_code'); const buNameEl = $('#e_bu_name');
  if (buCodeEl && !buCodeEl.value && iso2) buCodeEl.value = 'SMC-' + iso2;
  if (buNameEl && !buNameEl.value && name) buNameEl.value = 'SMC ' + name;
});
</script>