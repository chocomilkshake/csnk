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
     Agency ID=0, BU Code=SMC-{ISO2}, BU Name=SMC {Country Name}, Active=1
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
                        // You may uncomment the next line if you want to show error:
                        // $errors[] = "Country saved, but failed to create Business Unit: " . $conn->error;
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
?>
<style>
/* ======== Modern, readable, friendly UI for admins ======== */
:root{
  --ring: rgba(37,99,235,.14);
  --ink: #0f172a;
  --muted: #64748b;
  --soft: #f8fafc;
}
.card { border: none; box-shadow: 0 1px 3px rgba(15,23,42,.06); }
.form-control, .form-select { border-radius:.6rem; }
.form-control:focus, .form-select:focus { box-shadow: 0 0 0 .25rem var(--ring); border-color:#93c5fd; }
.input-icon { position: relative; }
.input-icon > .bi { position:absolute; left:.75rem; top:50%; transform:translateY(-50%); color:var(--muted); }
.input-icon > input { padding-left: 2.25rem; }
.form-label { font-weight:700; color:var(--ink); }
.small-hint { color:var(--muted); }
.badge-soft { border:1px solid rgba(0,0,0,.06); }
.badge-iso2 { background:#f1f5f9; color:#0f172a; }
.badge-iso3 { background:#e0f2fe; color:#075985; }
.mono { font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }
.country-flag { font-size: 1.15rem; margin-right:.35rem; }
.btn-action { min-width: 36px; border-radius: 10rem; }
.table td, .table th { vertical-align: middle; }
.bg-soft { background: var(--soft); }
.modal-header { border-bottom:1px solid #eef2f7; }
.modal-footer { border-top:1px solid #eef2f7; }
.modal-title { font-weight:800; letter-spacing:.2px; }
hr.hr-soft { border:0; height:1px; background:linear-gradient(90deg, #eef2f7, #e2e8f0, #eef2f7); }
</style>

<div class="row mb-4">
  <div class="col">
    <h4 class="mb-0">Country Management</h4>
    <p class="text-muted small">Manage global countries and their regional settings.</p>
  </div>
  <div class="col-auto d-flex align-items-center gap-2">
    <input id="tblFilter" type="search" class="form-control form-control-sm" placeholder="Search countries...">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
      <i class="bi bi-plus-lg me-1"></i> Add New Country
    </button>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?><li><?php echo h($e); ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<!-- ============================================================================
     COUNTRIES TABLE
============================================================================ -->
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
            <th>Timezone</th>
            <th>Locale</th>
            <th class="text-center"># BUs</th>
            <th class="text-center">Status</th>
            <th class="text-end pe-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($countries)): ?>
            <tr><td colspan="10" class="text-center py-4 text-muted">No countries found.</td></tr>
          <?php else: foreach ($countries as $c): ?>
            <?php
              $flag = flag_emoji_from_iso2($c['iso2'] ?? '');
              $isActive = (int)($c['active'] ?? 0) === 1;
              $canDelete = ((int)$c['bu_count'] === 0);
            ?>
            <tr data-name="<?php echo strtolower(h($c['name'])); ?>" data-iso2="<?php echo strtolower(h($c['iso2'])); ?>">
              <td class="ps-3 mono text-muted small"><?php echo (int)$c['id']; ?></td>
              <td><span class="country-flag"><?php echo $flag; ?></span><strong><?php echo h($c['name']); ?></strong></td>
              <td>
                <span class="badge badge-soft badge-iso2"><?php echo h($c['iso2']); ?></span>
                <span class="badge badge-soft badge-iso3"><?php echo h($c['iso3']); ?></span>
              </td>
              <td><code class="text-danger"><?php echo h($c['phone_country_code']); ?></code></td>
              <td class="mono"><?php echo h($c['currency_code']); ?></td>
              <td><small class="text-muted"><?php echo h($c['default_tz']); ?></small></td>
              <td><small class="text-muted"><?php echo h($c['locale']); ?></small></td>
              <td class="text-center">
                <span class="badge rounded-pill <?php echo $c['bu_count']>0 ? 'text-bg-primary' : 'text-bg-light'; ?>"><?php echo (int)$c['bu_count']; ?></span>
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
                  ><i class="bi bi-eye"></i></button>

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
                  ><i class="bi bi-pencil-square"></i></button>

<!-- Delete -->
                  <?php if ($canDelete): ?>
                    <a href="country_management.php?action=delete&id=<?php echo (int)$c['id']; ?>"
                       class="btn btn-danger btn-sm btn-action"
                       onclick="return confirm('Permanently delete <?php echo h($c['name']); ?>? This cannot be undone.')"
                       title="Delete"><i class="bi bi-trash"></i></a>
                  <?php else: ?>
                    <button type="button" class="btn btn-danger btn-sm btn-action" disabled
                            title="Cannot delete while BUs are linked"><i class="bi bi-trash"></i></button>
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

<!-- ============================================================================
     ADD MODAL — simplified, modern, and friendly
     (BU creation is hidden & automatic)
============================================================================ -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-soft">
        <h6 class="modal-title" id="addModalLabel">Add New Country</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="countryFormAdd" action="country_management.php" method="POST">
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
                <input type="text" name="iso2" class="form-control form-control-lg" maxlength="2" placeholder="PH" required>
              </div>
            </div>

            <div class="col-md-2">
              <label class="form-label">ISO3 <span class="text-danger">*</span></label>
              <div class="input-icon">
                <i class="bi bi-alphabet"></i>
                <input type="text" name="iso3" class="form-control form-control-lg" maxlength="3" placeholder="PHL" required>
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Default Timezone</label>
              <div class="input-icon">
                <i class="bi bi-clock-history"></i>
                <input type="text" name="default_tz" class="form-control form-control-lg" placeholder="Asia/Manila">
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Phone Code</label>
              <div class="input-icon">
                <i class="bi bi-telephone"></i>
                <input type="text" name="phone_country_code" class="form-control form-control-lg" placeholder="+63">
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Currency Code</label>
              <div class="input-icon">
                <i class="bi bi-cash-coin"></i>
                <input type="text" name="currency_code" class="form-control form-control-lg" maxlength="3" placeholder="PHP">
              </div>
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
              A default Business Unit will be created automatically (e.g., <span class="mono">SMC-PH</span> / <span class="mono">SMC Philippines</span>). You don’t need to set anything here.
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

<!-- ============================================================================
     VIEW MODAL
============================================================================ -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-soft">
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

<!-- ============================================================================
     EDIT MODAL (no BU UI here; only country fields)
============================================================================ -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-soft">
        <h6 class="modal-title" id="editModalLabel">Edit Country</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="countryFormEdit" action="country_management.php" method="POST">
        <input type="hidden" name="id" id="e_id">
        <!-- For EDIT, do not auto-create new BU. Keep hidden fields empty by default. -->
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
                <input type="text" name="iso2" id="e_iso2" class="form-control form-control-lg" maxlength="2" required>
              </div>
            </div>

            <div class="col-md-2">
              <label class="form-label">ISO3 <span class="text-danger">*</span></label>
              <div class="input-icon">
                <i class="bi bi-alphabet"></i>
                <input type="text" name="iso3" id="e_iso3" class="form-control form-control-lg" maxlength="3" required>
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Default Timezone</label>
              <div class="input-icon">
                <i class="bi bi-clock-history"></i>
                <input type="text" name="default_tz" id="e_tz" class="form-control form-control-lg">
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Phone Code</label>
              <div class="input-icon">
                <i class="bi bi-telephone"></i>
                <input type="text" name="phone_country_code" id="e_phone" class="form-control form-control-lg">
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Currency Code</label>
              <div class="input-icon">
                <i class="bi bi-cash-coin"></i>
                <input type="text" name="currency_code" id="e_currency" class="form-control form-control-lg" maxlength="3">
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

<!-- ============================================================================
     SMART AUTOFILL & UI BEHAVIOR — External country_ref.js loaded
============================================================================ -->
<script src="../js/country_ref.js"></script>
<script>

const $  = (sel, root=document) => root.querySelector(sel);

/* ---------- Populate datalists ---------- */
function populateDatalist(datalistEl) {
  [...COUNTRY_REF].sort((a,b)=>a.name.localeCompare(b.name))
    .forEach(c => {
      const opt = document.createElement('option');
      opt.value = c.name;
      datalistEl.appendChild(opt);
    });
}
populateDatalist(document.getElementById('countryListAdd'));
populateDatalist(document.getElementById('countryListEdit'));

/* ---------- Helpers ---------- */
function deriveLocale(code){ return code ? ('en_' + code) : ''; }
function toUpper(el){ el && (el.value = (el.value || '').toUpperCase()); }

/* ---------- Smart autofill binding (Add) ---------- */
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
    const m = COUNTRY_REF.find(c => c.name.toLowerCase() === v);
    fillFromMatch(m);
  });
  iso2?.addEventListener('blur', () => {
    const code = (iso2.value||'').toUpperCase();
    const m = COUNTRY_REF.find(c => c.iso2 === code);
    if (m && name && !name.value) name.value = m.name;
    fillFromMatch(m);
  });
  name?.addEventListener('input', suggestBU);
  iso2?.addEventListener('input', suggestBU);

  form.addEventListener('submit', (e) => {
    if (iso2 && iso2.value.length !== 2) { alert('ISO2 must be 2 characters.'); e.preventDefault(); iso2.focus(); }
    if (iso3 && iso3.value.length !== 3) { alert('ISO3 must be 3 characters.'); e.preventDefault(); iso3.focus(); }
    toUpper(iso2); toUpper(iso3); toUpper(curr);
    suggestBU(); // final sync for hidden BU fields
  });
})();

/* ---------- Smart autofill binding (Edit) ---------- */
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
    const m = COUNTRY_REF.find(c => c.name.toLowerCase() === v);
    fillFromMatch(m);
  });
  iso2?.addEventListener('blur', () => {
    const code = (iso2.value||'').toUpperCase();
    const m = COUNTRY_REF.find(c => c.iso2 === code);
    if (m && name && !name.value) name.value = m.name;
    fillFromMatch(m);
  });

  form.addEventListener('submit', (e) => {
    if (iso2 && iso2.value.length !== 2) { alert('ISO2 must be 2 characters.'); e.preventDefault(); iso2.focus(); }
    if (iso3 && iso3.value.length !== 3) { alert('ISO3 must be 3 characters.'); e.preventDefault(); iso3.focus(); }
    toUpper(iso2); toUpper(iso3); toUpper(curr);
  });
})();

/* ---------- View modal population ---------- */
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

/* ---------- Edit modal population ---------- */
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

/* ---------- Table quick filter ---------- */
const tblFilter = document.getElementById('tblFilter');
const table = document.getElementById('countriesTable');
tblFilter?.addEventListener('input', () => {
  const q = (tblFilter.value||'').trim().toLowerCase();
  table.querySelectorAll('tbody tr').forEach(tr => {
    const n = tr.getAttribute('data-name') || '';
    const i = tr.getAttribute('data-iso2') || '';
    tr.style.display = (n.includes(q) || i.includes(q)) ? '' : 'none';
  });
});

/* ---------- Re-open modal with posted data on validation errors ---------- */
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