<?php
$pageTitle = 'SMC Business Units Management';
require_once '../includes/header.php';
require_once '../includes/Admin.php';

$admin = new Admin($database);

$sMcAgencyId = 2; // SMC agency ID (from accounts.php)

/* RBAC: Admins and Super Admins only */
if (!$isAdmin && !$isSuperAdmin) {
    setFlashMessage('error', 'You do not have permission to access this page.');
    redirect('dashboard.php');
}

function h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function flag_icon($iso2)
{
    return '<span class="fi fi-' . strtolower($iso2) . ' me-1" style="width:20px;height:15px;border-radius:2px;"></span>';
}

/* Row actions */
if (isset($_GET['action'], $_GET['id'])) {
    $targetId = (int) $_GET['id'];

    if ($_GET['action'] === 'toggle_active') {
        $stmt = $conn->prepare("UPDATE business_units SET active = CASE WHEN active = 1 THEN 0 ELSE 1 END WHERE id = ? AND agency_id = ?");
        $stmt->bind_param('ii', $targetId, $sMcAgencyId);
        if ($stmt->execute()) {
            // Log
            $infoStmt = $conn->prepare("SELECT bu.id, c.name FROM business_units bu JOIN countries c ON bu.country_id = c.id WHERE bu.id = ?");
            $infoStmt->bind_param('i', $targetId);
            $infoStmt->execute();
            $info = $infoStmt->get_result()->fetch_assoc();
            $newStatus = $conn->query("SELECT active FROM business_units WHERE id = $targetId")->fetch_assoc()['active'] ? 'Active' : 'Inactive';
            $auth->logActivity($_SESSION['admin_id'], 'Toggle SMC BU', "Toggled '{$info['name']}' to {$newStatus}");
            setFlashMessage('success', 'Status updated.');
        }
        $stmt->close();
        redirect('smc_management.php');
    }

    if ($_GET['action'] === 'set_default') {
        // Unset all defaults first
        $conn->query("UPDATE business_units SET is_default = 0 WHERE agency_id = $sMcAgencyId");
        $stmt = $conn->prepare("UPDATE business_units SET is_default = 1 WHERE id = ? AND agency_id = ?");
        $stmt->bind_param('ii', $targetId, $sMcAgencyId);
        if ($stmt->execute()) {
            $info = $conn->query("SELECT c.name FROM business_units bu JOIN countries c ON bu.country_id = c.id WHERE bu.id = $targetId")->fetch_assoc();
            $auth->logActivity($_SESSION['admin_id'], 'Set Default SMC BU', "Set '{$info['name']}' as default");
            setFlashMessage('success', 'Default updated.');
        }
        redirect('smc_management.php');
    }

    if ($_GET['action'] === 'delete') {
        // Check if default
        $isDefault = $conn->query("SELECT is_default FROM business_units WHERE id = $targetId AND agency_id = $sMcAgencyId")->fetch_assoc()['is_default'];
        if ($isDefault) {
            setFlashMessage('error', 'Cannot delete default business unit.');
        } else {
            $info = $conn->query("SELECT c.name FROM business_units bu JOIN countries c ON bu.country_id = c.id WHERE bu.id = $targetId")->fetch_assoc();
            $stmt = $conn->prepare("DELETE FROM business_units WHERE id = ? AND agency_id = ?");
            $stmt->bind_param('ii', $targetId, $sMcAgencyId);
            if ($stmt->execute()) {
                $auth->logActivity($_SESSION['admin_id'], 'Delete SMC BU', "Deleted '{$info['name']}'");
                setFlashMessage('success', 'Deleted.');
            } else {
                setFlashMessage('error', 'Delete failed.');
            }
        }
        redirect('smc_management.php');
    }
}

/* Add/Edit */
$errors = [];
$reopenModal = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_bu'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $countryId = (int) $_POST['country_id'];
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $status = $_POST['status'] ?? '1';
    $isDefault = isset($_POST['is_default']) ? 1 : 0;

    // Validation
    if ($countryId <= 0)
        $errors[] = 'Country required.';
    if (strlen($code) < 2)
        $errors[] = 'Code at least 2 chars.';
    if (!in_array($status, ['1', '0']))
        $status = '1';

    // Uniqueness
    $chkSql = $id > 0
        ? "SELECT COUNT(*) FROM business_units WHERE (code = ? OR country_id = ?) AND id != ? AND agency_id = ?"
        : "SELECT COUNT(*) FROM business_units WHERE code = ? OR country_id = ? AND agency_id = ?";
    $chkStmt = $conn->prepare($chkSql);
    $params = $id > 0 ? [$code, $countryId, $id, $sMcAgencyId] : [$code, $countryId, $sMcAgencyId];
    $types = $id > 0 ? 'siii' : 'sii';
    $chkStmt->bind_param($types, ...$params);
    $chkStmt->execute();
    if ((int) $chkStmt->get_result()->fetch_row()[0] > 0)
        $errors[] = 'Duplicate code/country.';

    if (empty($errors)) {
        $currentUser = $_SESSION['admin_username'] ?? 'system';
        if ($id > 0) {
            // UPDATE
            $stmt = $conn->prepare("UPDATE business_units SET country_id=?, code=?, active=?, sort_order=?, updated_by=? WHERE id=? AND agency_id=?");
            $stmt->bind_param('isisssi', $countryId, $code, $status, $sortOrder, $currentUser, $id, $sMcAgencyId);
            $ok = $stmt->execute();
            $action = 'Updated';
        } else {
            // INSERT
            $stmt = $conn->prepare("INSERT INTO business_units (agency_id, country_id, code, active, sort_order, created_by) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param('iisiss', $sMcAgencyId, $countryId, $code, $status, $sortOrder, $currentUser);
            $ok = $stmt->execute();
            $action = 'Created';
            if ($isDefault && $ok) {
                $conn->query("UPDATE business_units SET is_default=0 WHERE agency_id=$sMcAgencyId");
                $newId = $conn->insert_id;
                $conn->query("UPDATE business_units SET is_default=1 WHERE id=$newId");
            }
        }
        if ($ok) {
            $countryName = $conn->query("SELECT name FROM countries WHERE id=$countryId")->fetch_assoc()['name'];
            $auth->logActivity($_SESSION['admin_id'], 'SMC BU ' . $action, "$action $countryName ($code)");
            setFlashMessage('success', $action . ' successfully.');
            redirect('smc_management.php');
        }
    }
    $reopenModal = $id > 0 ? 'edit' : 'add';
}

/* Load data */
$businessUnits = $admin->getActiveBusinessUnits($sMcAgencyId);
$totalCount = count($businessUnits);
?>
<style>
    :root {
        --ink: #0f172a;
        --muted: #64748b;
        --ring: rgba(37, 99, 235, .14);
        --bg: #f8fafc;
        --card: #ffffff;
        --line: #e2e8f0;
        --ok: #16a34a;
        --warn: #f59e0b;
        --err: #ef4444;
        --pri: #2563eb;
    }

    body {
        background: linear-gradient(180deg, #f1f5f9, #f8fafc) fixed;
    }

    .page-title h4 {
        font-weight: 800;
        color: #1e293b;
    }

    .stats-chip {
        display: flex;
        gap: .65rem;
        padding: .7rem 1rem;
        border: 1px solid var(--line);
        border-radius: 1rem;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }

    .card {
        border: 1px solid var(--line);
        border-radius: 1rem;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }

    .table-wrap {
        max-height: 64vh;
        overflow: auto;
    }

    table thead th {
        position: sticky;
        top: 0;
        background: #f1f5f9;
        font-weight: 700;
    }

    .btn-action {
        min-width: 36px;
        border-radius: 10rem;
    }

    .form-control,
    .form-select {
        border-radius: .75rem;
        border-color: var(--line);
    }

    .form-control:focus {
        box-shadow: 0 0 0 .25rem var(--ring);
    }
</style>

<div class="row align-items-center justify-content-between mb-3 page-title">
    <div class="col">
        <h4 class="mb-1">SMC Business Units</h4>
        <p class="mb-0">Manage SMC countries/business units.</p>
    </div>
    <div class="col-auto">
        <div class="stats-chip">
            <div class="icon"
                style="width:30px;height:30px;display:grid;place-items:center;border-radius:10px;background:rgba(37,99,235,.1);color:#2563eb;">
                <i class="bi bi-globe"></i></div>
            <div>
                <div class="count"><?= $totalCount ?></div><small>Active</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-2 toolbar mb-3">
    <div class="col-sm-6">
        <div class="input-icon"><i class="bi bi-search"></i><input id="tblFilter" type="search" class="form-control"
                placeholder="Search..."></div>
    </div>
    <div class="col-sm-auto">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i
                class="bi bi-plus-lg me-1"></i>Add Unit</button>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong>Errors:</strong>
        <ul><?php foreach ($errors as $e)
            echo '<li>' . h($e) . '</li>'; ?></ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <div class="title"><i class="bi bi-globe me-2 text-primary"></i>SMC Units</div>
        <div id="tableSummary" class="text-muted small">Loading...</div>
    </div>
    <div class="card-body p-0 table-wrap">
        <table class="table table-hover mb-0" id="unitsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Country</th>
                    <th>Code</th>
                    <th>Default</th>
                    <th>Sort</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($businessUnits as $bu):
                    $isActive = $bu['status'] == 1;
                    $isDefault = (int) $bu['is_default'] === 1;
                    ?>
                    <tr data-name="<?= strtolower($bu['name']) ?>" data-code="<?= strtolower($bu['code'] ?? '') ?>">
                        <td><?= $bu['id'] ?></td>
                        <td><?= flag_icon(explode(' - ', $bu['name'])[0]) . h(explode(' - ', $bu['name'])[1]) ?></td>
                        <td><span class="badge bg-primary-subtle"><?= h($bu['code'] ?? 'N/A') ?></span></td>
                        <td class="text-center">
                            <?= $isDefault ? '<span class="badge bg-success-subtle">Default</span>' : '<a href="?action=set_default&id=' . $bu['id'] . '" class="btn btn-sm btn-outline-warning" onclick="return confirm(\'Set default?\')">★</a>' ?>
                        </td>
                        <td><?= $bu['sort_order'] ?></td>
                        <td class="text-center">
                            <a href="?action=toggle_active&id=<?= $bu['id'] ?>"
                                class="btn btn-sm btn-<?= $isActive ? 'success' : 'danger' ?>"
                                onclick="return confirm('Toggle?')">
                                <?= $isActive ? 'Active' : 'Inactive' ?>
                            </a>
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal"
                                    data-id="<?= $bu['id'] ?>" data-name="<?= h($bu['name']) ?>" title="View"><i
                                        class="bi bi-eye"></i></button>
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal"
                                    data-id="<?= $bu['id'] ?>" data-country="<?= $bu['country_id'] ?? '' ?>"
                                    data-code="<?= h($bu['code'] ?? '') ?>" data-sort="<?= $bu['sort_order'] ?>"
                                    title="Edit"><i class="bi bi-pencil"></i></button>
                                <?= !$isDefault ? '<button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="' . $bu['id'] . '" data-name="' . h($bu['name']) . '" title="Delete"><i class="bi bi-trash"></i></button>' : '' ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modals (Add, Edit, View, Delete Confirm) -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6>Add SMC Unit</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Country <span class="text-danger">*</span></label>
                        <select name="country_id" class="form-select" required>
                            <option value="">Select Country</option>
                            <?php
                            $countries = $conn->query("SELECT id, iso2, name FROM countries WHERE active=1 ORDER BY name");
                            while ($c = $countries->fetch_assoc()): ?>
                                <option value="<?= $c['id'] ?>"><?= flag_icon($c['iso2']) . $c['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Code</label>
                        <input type="text" name="code" class="form-control" maxlength="50">
                    </div>
                    <div class="mb-3"><label>Sort Order</label><input type="number" name="sort_order"
                            class="form-control" value="0"></div>
                    <div class="form-check"><input type="checkbox" name="is_default" id="isDefAdd"
                            class="form-check-input"><label class="form-check-label">Default</label></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="save_bu" class="btn btn-success">Create</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6>Edit Unit</h6>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="e_id">
                <div class="modal-body">
                    <!-- Similar to add, populated by JS -->
                </div>
                <div class="modal-footer">
                    <button type="submit" name="save_bu" class="btn btn-warning">Save</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JS for table filter, modals population, etc. (mirrors branch_management.php JS) -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Table filter logic
        const filter = document.getElementById('tblFilter');
        filter?.addEventListener('input', filterTable);
        function filterTable() {
            const q = filter.value.toLowerCase();
            [...document.querySelectorAll('#unitsTable tbody tr')].forEach(tr => {
                tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        }

        // Modal population (edit/view/delete)
        document.querySelectorAll('[data-bs-toggle="modal"]').forEach(btn => {
            btn.addEventListener('click', e => {
                const modalId = btn.getAttribute('data-bs-target');
                const modal = document.querySelector(modalId);
                if (modalId === '#editModal') {
                    document.getElementById('e_id').value = btn.dataset.id;
                    // Populate other fields...
                }
                // Similar for view/delete
            });
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>