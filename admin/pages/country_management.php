<?php
$pageTitle = 'Country Management';
require_once '../includes/header.php';

// RBAC: Only admins or super admins can manage countries
if (!$isAdmin && !$isSuperAdmin) {
    setFlashMessage('error', 'You do not have permission to access this page.');
    redirect('dashboard.php');
}

$errors = [];
$success = '';

/* ============================
   DEACTIVATE / DELETE
============================= */
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'deactivate') {
    $targetId = (int)$_GET['id'];
    
    // Check if any business units are linked to this country
    $stmt = $conn->prepare("SELECT COUNT(*) FROM business_units WHERE country_id = ?");
    $stmt->bind_param('i', $targetId);
    $stmt->execute();
    $res = $stmt->get_result();
    $buCount = $res->fetch_row()[0];
    $stmt->close();

    if ($buCount > 0) {
        // Soft delete: just set active = 0
        $stmt = $conn->prepare("UPDATE countries SET active = 0 WHERE id = ?");
        $stmt->bind_param('i', $targetId);
        if ($stmt->execute()) {
            setFlashMessage('success', 'Country deactivated successfully. (Cannot delete as it is linked to business units)');
        } else {
            setFlashMessage('error', 'Failed to deactivate country.');
        }
        $stmt->close();
    } else {
        // Hard delete: safe since no BUs reference it
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

/* ============================
   ADD / EDIT (with optional linked BU)
============================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_country'])) {
    $id                 = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $iso2               = strtoupper(sanitizeInput($_POST['iso2'] ?? ''));
    $iso3               = strtoupper(sanitizeInput($_POST['iso3'] ?? ''));
    $name               = sanitizeInput($_POST['name'] ?? '');
    $default_tz         = sanitizeInput($_POST['default_tz'] ?? '');
    $phone_country_code = sanitizeInput($_POST['phone_country_code'] ?? '');
    $currency_code      = strtoupper(sanitizeInput($_POST['currency_code'] ?? ''));
    $locale             = sanitizeInput($_POST['locale'] ?? '');
    $date_format        = sanitizeInput($_POST['date_format'] ?? '');
    $active             = isset($_POST['active']) ? 1 : 0;

    // NEW: optional BU fields
    $create_linked_bu   = isset($_POST['create_linked_bu']); // checkbox
    $bu_agency_id       = (int)($_POST['bu_agency_id'] ?? 0);
    $bu_code            = sanitizeInput($_POST['bu_code'] ?? '');
    $bu_name            = sanitizeInput($_POST['bu_name'] ?? '');
    $bu_active          = isset($_POST['bu_active']) ? 1 : 0;

    // Validation
    if (strlen($iso2) !== 2) $errors[] = "ISO2 must be exactly 2 characters.";
    if (strlen($iso3) !== 3) $errors[] = "ISO3 must be exactly 3 characters.";
    if (empty($name))        $errors[] = "Name is required.";

    // If user asked to create a BU, minimally require code + name
    if ($create_linked_bu) {
        if ($bu_code === '') $errors[] = "Business Unit Code is required when creating a linked BU.";
        if ($bu_name === '') $errors[] = "Business Unit Name is required when creating a linked BU.";
    }

    if (empty($errors)) {
        $countrySaved = false;
        $countryId = $id;

        if ($id > 0) {
            // Update Country
            $sql = "UPDATE countries 
                       SET iso2=?, iso3=?, name=?, default_tz=?, phone_country_code=?, currency_code=?, locale=?, date_format=?, active=? 
                     WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssssssii', $iso2, $iso3, $name, $default_tz, $phone_country_code, $currency_code, $locale, $date_format, $active, $id);
            if ($stmt->execute()) {
                $countrySaved = true;
            } else {
                $errors[] = "Database error (update country): " . $conn->error;
            }
            $stmt->close();
        } else {
            // Insert Country
            $sql = "INSERT INTO countries (iso2, iso3, name, default_tz, phone_country_code, currency_code, locale, date_format, active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssssssi', $iso2, $iso3, $name, $default_tz, $phone_country_code, $currency_code, $locale, $date_format, $active);
            if ($stmt->execute()) {
                $countrySaved = true;
                $countryId = $conn->insert_id; // NEW: get new country id
            } else {
                $errors[] = "Database error (insert country): " . $conn->error;
            }
            $stmt->close();
        }

        // If country saved and user wants a linked BU, attempt to create it
        if ($countrySaved && $create_linked_bu) {
            // Optional: prevent duplicate BU code
            $exists = 0;
            $chk = $conn->prepare("SELECT COUNT(*) FROM business_units WHERE code = ?");
            $chk->bind_param('s', $bu_code);
            $chk->execute();
            $rs = $chk->get_result();
            $exists = (int)$rs->fetch_row()[0];
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
        // If we reach here with errors, fall through to show them.
    }
}

// Load countries with BU counts
$countries = [];
$sql = "SELECT c.*, (SELECT COUNT(*) FROM business_units WHERE country_id = c.id) as bu_count FROM countries c ORDER BY c.name ASC";
$res = $conn->query($sql);
if ($res) {
    $countries = $res->fetch_all(MYSQLI_ASSOC);
}

// Edit mode
$editCountry = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editId = (int)$_GET['id'];
    foreach ($countries as $c) {
        if ((int)$c['id'] === $editId) {
            $editCountry = $c;
            break;
        }
    }
    // Prefill "Create linked BU" when user clicked the "Add BU" action
if (isset($_GET['add_bu_for'])) {
    // Ensure we have the edit form visible and a country loaded
    if (!$editCountry && isset($_GET['id'])) {
        $editId = (int)$_GET['id'];
        foreach ($countries as $c) {
            if ((int)$c['id'] === $editId) { $editCountry = $c; break; }
        }
    }
    // The actual checkbox is pre-checked in the form via GET detection.
}   
}
?>




<div class="row mb-4">
    <div class="col">
        <h4 class="mb-0">Country Management</h4>
        <p class="text-muted small">Manage global countries and their regional settings.</p>
    </div>
    <div class="col-auto">
        <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#countryFormSection">
            <i class="bi bi-plus-lg me-1"></i> Add New Country
        </button>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
                <li><?php echo $err; ?></li>
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
            <form action="country_management.php" method="POST">
                <?php if ($editCountry): ?>
                    <input type="hidden" name="id" value="<?php echo (int)$editCountry['id']; ?>">
                <?php endif; ?>
                
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Country Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Philippines" value="<?php echo h($editCountry['name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">ISO2 <span class="text-danger">*</span></label>
                        <input type="text" name="iso2" class="form-control" placeholder="PH" maxlength="2" value="<?php echo h($editCountry['iso2'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">ISO3 <span class="text-danger">*</span></label>
                        <input type="text" name="iso3" class="form-control" placeholder="PHL" maxlength="3" value="<?php echo h($editCountry['iso3'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Default Timezone</label>
                        <input type="text" name="default_tz" class="form-control" placeholder="Asia/Manila" value="<?php echo h($editCountry['default_tz'] ?? ''); ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Phone Code</label>
                        <input type="text" name="phone_country_code" class="form-control" placeholder="+63" value="<?php echo h($editCountry['phone_country_code'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Currency Code</label>
                        <input type="text" name="currency_code" class="form-control" placeholder="PHP" maxlength="3" value="<?php echo h($editCountry['currency_code'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Locale</label>
                        <input type="text" name="locale" class="form-control" placeholder="en_PH" value="<?php echo h($editCountry['locale'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Date Format</label>
                        <input type="text" name="date_format" class="form-control" placeholder="Y-m-d" value="<?php echo h($editCountry['date_format'] ?? ''); ?>">
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="active" id="activeSwitch" <?php echo (!isset($editCountry) || $editCountry['active']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="activeSwitch">Country is Active</label>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="row">
                    <div class="col-12">
                        <h6 class="mb-2">Linked Business Unit (optional)</h6>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="create_linked_bu" id="createLinkedBuSwitch"
                                <?php
                                // If user clicked "Add BU" from the table (see section 3), or if form reloaded with errors while checked
                                $prefillBu = isset($_GET['add_bu_for']) || isset($_POST['create_linked_bu']);
                                echo $prefillBu ? 'checked' : '';
                                ?>>
                            <label class="form-check-label" for="createLinkedBuSwitch">Create a new Business Unit for this country</label>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Agency ID</label>
                        <input type="number" min="0" name="bu_agency_id" class="form-control" placeholder="e.g. 1"
                            value="<?php echo h($_POST['bu_agency_id'] ?? ''); ?>">
                        <div class="form-text">Leave 0 if you don't use agencies.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">BU Code <span class="text-danger">*</span></label>
                        <input type="text" name="bu_code" class="form-control" placeholder="e.g. SMC-PH"
                            value="<?php echo h($_POST['bu_code'] ?? (isset($editCountry) ? ('SMC-' . strtoupper($editCountry['iso2'] ?? '')) : '')); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">BU Name <span class="text-danger">*</span></label>
                        <input type="text" name="bu_name" class="form-control" placeholder="e.g. SMC Philippines"
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
                </div>

                <script>
                // Optional: show/hide BU inputs when toggled (pure cosmetic, server-side handles everything safely)
                document.addEventListener('DOMContentLoaded', function() {
                    const sw = document.getElementById('createLinkedBuSwitch');
                    const fields = ['bu_agency_id','bu_code','bu_name','buActiveSwitch'].map(id=>document.getElementsByName(id)[0]||document.getElementById(id));
                    function toggleBu() {
                        const enabled = sw.checked;
                        fields.forEach(el => { if (el) { el.closest('.col-md-3,.col-md-4,.col-md-2').style.opacity = enabled ? '1' : '0.5'; }});
                    }
                    if (sw) {
                        sw.addEventListener('change', toggleBu);
                        toggleBu();
                    }
                });
                </script>



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
            <table class="table table-hover align-middle mb-0">
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
                        <tr>
                            <td class="ps-3 text-muted mono small"><?php echo (int)$c['id']; ?></td>
                            <td><strong><?php echo h($c['name']); ?></strong></td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border"><?php echo h($c['iso2']); ?></span>
                                <span class="badge bg-info-subtle text-info border"><?php echo h($c['iso3']); ?></span>
                            </td>
                            <td><code><?php echo h($c['phone_country_code']); ?></code></td>
                            <td><?php echo h($c['currency_code']); ?></td>
                            <td><small class="text-muted"><?php echo h($c['locale']); ?></small></td>
                            <td class="text-center">
                                <span class="badge rounded-pill <?php echo $c['bu_count'] > 0 ? 'bg-primary' : 'bg-light text-dark border'; ?>">
                                    <?php echo (int)$c['bu_count']; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($c['active']): ?>
                                    <span class="badge bg-success-subtle text-success border-success-subtle border px-3">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border-danger-subtle border px-3">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <div class="btn-group">
                                    <a href="country_management.php?action=edit&id=<?php echo (int)$c['id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="country_management.php?action=deactivate&id=<?php echo (int)$c['id']; ?>" 
                                       class="btn btn-sm <?php echo $c['active'] ? 'btn-outline-danger' : 'btn-outline-success'; ?>" 
                                       onclick="return confirm('<?php echo $c['active'] ? 'Deactivate this country?' : 'Reactivate this country?'; ?>');"
                                       title="<?php echo $c['active'] ? 'Deactivate' : 'Activate'; ?>">
                                        <i class="bi <?php echo $c['active'] ? 'bi-x-circle' : 'bi-check-circle'; ?>"></i>
                                    </a>
                                    <a href="country_management.php?action=edit&id=<?php echo (int)$c['id']; ?>&add_bu_for=<?php echo (int)$c['id']; ?>"
                                    class="btn btn-sm btn-outline-primary"
                                    title="Create a Business Unit for this country">
                                        <i class="bi bi-building-add"></i>
                                    </a>
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
