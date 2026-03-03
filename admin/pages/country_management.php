<?php
$pageTitle = 'Country Management';
require_once '../includes/header.php';

/* =========================================================================================
   0) SAFETY: Only Admins / Super Admins may enter this page (defense-in-depth with header)
========================================================================================= */
if (!$isAdmin && !$isSuperAdmin) {
    setFlashMessage('error', 'You do not have permission to access this page.');
    redirect('dashboard.php');
}

/* =========================================================================================
   1) Quick migration: ensure countries.id is an AUTO_INCREMENT primary key
      (prevents id=0 inserts if schema was missing auto_increment/primary key)
========================================================================================= */
if ($conn instanceof mysqli) {
    // Check if primary key exists on `id`
    $hasPrimary = false;
    if ($res = $conn->query("SHOW INDEX FROM countries WHERE Key_name='PRIMARY'")) {
        $hasPrimary = ($res->num_rows > 0);
        $res->close();
    }
    if (!$hasPrimary) {
        // Attempt to add primary key and auto_increment
        @$conn->query("ALTER TABLE countries ADD PRIMARY KEY (id)");
    }
    // Ensure auto_increment on id
    $schema = $conn->query("SHOW CREATE TABLE countries");
    if ($schema) {
        $row = $schema->fetch_assoc();
        if (strpos($row['Create Table'], 'AUTO_INCREMENT') === false) {
            @$conn->query("ALTER TABLE countries MODIFY id SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT");
        }
        $schema->close();
    }

    // Fix any rows having id=0 (rare)
    if ($fix = $conn->query("SELECT COUNT(*) AS cnt FROM countries WHERE id = 0")) {
        $cnt = (int)($fix->fetch_assoc()['cnt'] ?? 0);
        $fix->close();
        if ($cnt > 0) {
            $nextRes = $conn->query("SELECT IFNULL(MAX(id),0)+1 AS nxt FROM countries");
            $nextRow = $nextRes ? $nextRes->fetch_assoc() : ['nxt' => 1];
            $next = (int)($nextRow['nxt'] ?? 1);
            if ($nextRes) $nextRes->close();
            while ($cnt--) {
                @$conn->query("UPDATE countries SET id={$next} WHERE id = 0 LIMIT 1");
                $next++;
            }
        }
    }
}

/* =========================================================================================
   2) Helpers
========================================================================================= */
function ensure_upper(?string $s, int $max = null): string {
    $s = strtoupper(trim((string)$s));
    if ($max !== null) $s = substr($s, 0, $max);
    return $s;
}

/* =========================================================================================
   3) Actions: toggle active OR delete (delete only if safe / no BUs)
========================================================================================= */
if (isset($_GET['action'], $_GET['id'])) {
    $targetId = (int)$_GET['id'];

    if ($_GET['action'] === 'toggle_active') {
        // flip active
        $stmt = $conn->prepare("UPDATE countries SET active = 1 - active WHERE id = ?");
        $stmt->bind_param('i', $targetId);
        if ($stmt->execute()) {
            setFlashMessage('success', 'Country status updated.');
        } else {
            setFlashMessage('error', 'Failed to update country status.');
        }
        $stmt->close();
        redirect('country_management.php');
    }

    if ($_GET['action'] === 'delete') {
        // Check if safe to delete
        $stmt = $conn->prepare("SELECT COUNT(*) FROM business_units WHERE country_id = ?");
        $stmt->bind_param('i', $targetId);
        $stmt->execute();
        $res = $stmt->get_result();
        $buCount = (int)($res->fetch_row()[0] ?? 0);
        $stmt->close();

        if ($buCount > 0) {
            setFlashMessage('error', 'Cannot delete: this country is linked to business units. Deactivate instead.');
        } else {
            $stmt = $conn->prepare("DELETE FROM countries WHERE id = ?");
            $stmt->bind_param('i', $targetId);
            if ($stmt->execute()) {
                setFlashMessage('success', 'Country deleted successfully.');
            } else {
                setFlashMessage('error', 'Failed to delete country.');
            }
            $stmt->close();
        }
        redirect('country_management.php');
    }
}

/* =========================================================================================
   4) Add / Edit (with optional linked BU creation)
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

    // Linked BU
    $create_linked_bu   = isset($_POST['create_linked_bu']);
    $bu_agency_id       = (int)($_POST['bu_agency_id'] ?? 0);
    $bu_code            = trim((string)($_POST['bu_code'] ?? ''));
    $bu_name            = trim((string)($_POST['bu_name'] ?? ''));
    $bu_active          = isset($_POST['bu_active']) ? 1 : 0;

    // Validation
    if (strlen($iso2) !== 2) $errors[] = "ISO2 must be exactly 2 characters.";
    if (strlen($iso3) !== 3) $errors[] = "ISO3 must be exactly 3 characters.";
    if ($name === '')        $errors[] = "Country Name is required.";

    // Simple uniqueness checks for ISO codes and Name
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
    if ($dups > 0) {
        $errors[] = "Another country with the same ISO2, ISO3 or Name already exists.";
    }

    // Linked BU requirements if requested
    if ($create_linked_bu) {
        if ($bu_code === '') $errors[] = "Business Unit Code is required when creating a linked BU.";
        if ($bu_name === '') $errors[] = "Business Unit Name is required when creating a linked BU.";
    }

    if (empty($errors)) {
        $countrySaved = false;
        $countryId = $id;

        if ($id > 0) {
            // Update
            $sql = "UPDATE countries 
                       SET iso2=?, iso3=?, name=?, default_tz=?, phone_country_code=?, currency_code=?, locale=?, date_format=?, active=? 
                     WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssssssii',
                $iso2, $iso3, $name, $default_tz, $phone_country_code, $currency_code, $locale, $date_format, $active, $id
            );
            if ($stmt->execute()) $countrySaved = true;
            else $errors[] = "Database error (update country): " . $conn->error;
            $stmt->close();
        } else {
            // Insert
            $sql = "INSERT INTO countries (iso2, iso3, name, default_tz, phone_country_code, currency_code, locale, date_format, active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssssssi',
                $iso2, $iso3, $name, $default_tz, $phone_country_code, $currency_code, $locale, $date_format, $active
            );
            if ($stmt->execute()) {
                $countrySaved = true;
                $countryId = $conn->insert_id;
            } else {
                $errors[] = "Database error (insert country): " . $conn->error;
            }
            $stmt->close();
        }

        // Create a linked BU if requested and country saved
        if ($countrySaved && $create_linked_bu) {
            // prevent duplicate BU code
            $exists = 0;
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
                if (!$stmtBU->execute()) {
                    $errors[] = "Failed to create linked Business Unit: " . $conn->error;
                }
                $stmtBU->close();
            }
        }

        if (empty($errors)) {
            setFlashMessage('success', $create_linked_bu ? 'Country and linked Business Unit saved successfully.' : 'Country saved successfully.');
            redirect('country_management.php');
        }
    }
}

/* =========================================================================================
   5) Load countries with BU counts
========================================================================================= */
$countries = [];
$sql = "SELECT c.*, (SELECT COUNT(*) FROM business_units WHERE country_id = c.id) as bu_count 
        FROM countries c 
        ORDER BY c.name ASC";
$res = $conn->query($sql);
if ($res) {
    $countries = $res->fetch_all(MYSQLI_ASSOC);
    $res->close();
}

/* =========================================================================================
   6) Edit mode preload
========================================================================================= */
$editCountry = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editId = (int)$_GET['id'];
    foreach ($countries as $c) {
        if ((int)$c['id'] === $editId) { $editCountry = $c; break; }
    }
    // Prefill "Create linked BU" when user clicked the "Add BU" action
    if (isset($_GET['add_bu_for'])) {
        if (!$editCountry && isset($_GET['id'])) {
            $editId = (int)$_GET['id'];
            foreach ($countries as $c) {
                if ((int)$c['id'] === $editId) { $editCountry = $c; break; }
            }
        }
    }
}

/* =========================================================================================
   7) Small helpers for UI
========================================================================================= */
function flag_emoji_from_iso2(?string $iso2): string {
    $iso2 = strtoupper(trim((string)$iso2));
    if (strlen($iso2) !== 2) return '';
    $codePoints = [];
    for ($i=0; $i<2; $i++) {
        $codePoints[] = 0x1F1E6 + (ord($iso2[$i]) - ord('A'));
    }
    return mb_convert_encoding('&#' . $codePoints[0] . ';', 'UTF-8', 'HTML-ENTITIES')
         . mb_convert_encoding('&#' . $codePoints[1] . ';', 'UTF-8', 'HTML-ENTITIES');
}
?>
<!-- =========================================
     PAGE: Country Management
========================================= -->
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
            <?php foreach ($errors as $err): ?>
                <li><?php echo h($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Add/Edit Form Section -->
<div class="collapse <?php echo ($editCountry || !empty($errors)) ? 'show' : ''; ?> mb-4" id="countryFormSection">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 pt-3">
            <h6 class="mb-0"><?php echo $editCountry ? 'Edit Country' : 'Add New Country'; ?></h6>
        </div>
        <div class="card-body">
            <form action="country_management.php" method="POST" id="countryForm" novalidate>
                <?php if ($editCountry): ?>
                    <input type="hidden" name="id" value="<?php echo (int)$editCountry['id']; ?>">
                <?php endif; ?>

                <!-- Smart Country picker -->
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">Country Name <span class="text-danger">*</span></label>
                        <input list="countryList" name="name" id="countryName" class="form-control"
                               placeholder="e.g. Philippines"
                               value="<?php echo h($editCountry['name'] ?? ''); ?>" required>
                        <datalist id="countryList"><!-- populated by JS --></datalist>
                        <div class="form-text">Tip: Pick from the list to auto-fill ISO, phone, currency, timezone and locale.</div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">ISO2 <span class="text-danger">*</span></label>
                        <input type="text" name="iso2" id="iso2" class="form-control" maxlength="2"
                               placeholder="PH" value="<?php echo h($editCountry['iso2'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">ISO3 <span class="text-danger">*</span></label>
                        <input type="text" name="iso3" id="iso3" class="form-control" maxlength="3"
                               placeholder="PHL" value="<?php echo h($editCountry['iso3'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Default Timezone</label>
                        <input type="text" name="default_tz" id="default_tz" class="form-control"
                               placeholder="Asia/Manila" value="<?php echo h($editCountry['default_tz'] ?? ''); ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Phone Code</label>
                        <input type="text" name="phone_country_code" id="phone_country_code" class="form-control"
                               placeholder="+63" value="<?php echo h($editCountry['phone_country_code'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Currency Code</label>
                        <input type="text" name="currency_code" id="currency_code" class="form-control" maxlength="3"
                               placeholder="PHP" value="<?php echo h($editCountry['currency_code'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Locale</label>
                        <input type="text" name="locale" id="locale" class="form-control"
                               placeholder="en_PH" value="<?php echo h($editCountry['locale'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Date Format</label>
                        <input type="text" name="date_format" id="date_format" class="form-control"
                               placeholder="Y-m-d" value="<?php echo h($editCountry['date_format'] ?? 'Y-m-d'); ?>">
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="active" id="activeSwitch"
                                   <?php echo (!isset($editCountry) || ($editCountry['active'] ?? 1)) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="activeSwitch">Country is Active</label>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Linked BU (optional) -->
                <div class="row">
                    <div class="col-12 d-flex align-items-center justify-content-between">
                        <h6 class="mb-2">Linked Business Unit (optional)</h6>
                        <div class="form-check form-switch">
                            <?php
                            $prefillBu = isset($_GET['add_bu_for']) || isset($_POST['create_linked_bu']);
                            ?>
                            <input class="form-check-input" type="checkbox" name="create_linked_bu" id="createLinkedBuSwitch"
                                   <?php echo $prefillBu ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="createLinkedBuSwitch">Create a new BU for this country</label>
                        </div>
                    </div>

                    <fieldset id="buFieldset" class="row g-3" <?php echo $prefillBu ? '' : 'disabled'; ?>>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Agency ID</label>
                            <input type="number" min="0" name="bu_agency_id" id="bu_agency_id" class="form-control" placeholder="e.g. 1"
                                   value="<?php echo h($_POST['bu_agency_id'] ?? ''); ?>">
                            <div class="form-text">Leave 0 if you don't use agencies.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">BU Code <span class="text-danger">*</span></label>
                            <input type="text" name="bu_code" id="bu_code" class="form-control" placeholder="e.g. SMC-PH"
                                   value="<?php
                                        $fallbackCode = isset($editCountry) ? ('SMC-' . strtoupper($editCountry['iso2'] ?? '')) : '';
                                        echo h($_POST['bu_code'] ?? $fallbackCode);
                                   ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">BU Name <span class="text-danger">*</span></label>
                            <input type="text" name="bu_name" id="bu_name" class="form-control" placeholder="e.g. SMC Philippines"
                                   value="<?php echo h($_POST['bu_name'] ?? ($editCountry['name'] ?? '')); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold d-block">BU Active</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="bu_active" id="buActiveSwitch"
                                    <?php echo (!isset($_POST['bu_active']) || isset($_POST['bu_active'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="buActiveSwitch">Active</label>
                            </div>
                        </div>
                    </fieldset>
                </div>

                <div class="mt-4 pt-3 border-top d-flex gap-2">
                    <button type="submit" name="save_country" class="btn btn-success px-4">
                        <i class="bi bi-check-lg me-1"></i> Save Country
                    </button>
                    <?php if ($editCountry): ?>
                        <a href="country_management.php" class="btn btn-outline-secondary">Cancel</a>
                    <?php else: ?>
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#countryFormSection">Cancel</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Countries Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="countriesTable">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3">ID</th>

                    <?php if (empty($countries)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">No countries found.</td></tr>
                    <?php else: foreach ($countries as $c): ?>
                        <?php
                        $flag = flag_emoji_from_iso2($c['iso2'] ?? '');
                        $isActive = (int)($c['active'] ?? 0) === 1;
    -subtle text-success border-success-subtle border px-3">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border-danger-subtle border px-3">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td
  {name:'Canada',iso2:'CA',iso3:'CAN',phone:'+1',currency:'CAD',tz:'America/Toronto',locale:'en_CA'},
  {name:'China',iso2:'CN',iso3:'CHN',phone:'+86',currency:'CNY',tz:'Asia/Shanghai',locale:'zh_CN'},
  {name:'Czechia',iso2:'CZ',iso3:'CZE',phone:'+420',currency:'CZK',tz:'Europe/Prague',locale:'cs_CZ'},
  {name:'Denmark',iso2:'DK',iso3:'DNK',phone:'+45',currency:'DKK',tz:'Europe/Copenhagen',locale:'da_DK'},
  {name:'Egyptch(n => {
    const opt = document.createElement('option');
    opt.value = n;
    datalist.appendChild(opt);
  });

  // Uppercase enforcement for codes
  function forceUpper(e) {
    e.target.value = e.target.
    const match = COUNTRY_REF.find(c => c.name.toLowerCase() === val);
    if (!match) return;

    iso2Input.value = match.iso2 || '';
    iso3Input.value = match.iso3 || '';
    phoneInput.value = match.phone || '';
    currInput.value = match.currency || '';
    tzInput.value = match.tz || '';
    if (!locInput.value) locInput.value = deriveLocale(match.iso2, match.name);
    if (!dateFmt.value) date