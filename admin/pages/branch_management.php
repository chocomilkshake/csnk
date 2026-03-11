<?php
$pageTitle = 'Branch Management';
require_once '../includes/header.php';

/* ============================================================================
   RBAC: Admins and Super Admins only (defense in depth)
============================================================================ */
if (!$isAdmin && !$isSuperAdmin) {
  setFlashMessage('error', 'You do not have permission to access this page.');
  redirect('dashboard.php');
}

/* ============================================================================
   Helpers
============================================================================ */
function h($s)
{
  return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
function ensure_upper(?string $s, int $max = null): string
{
  $s = strtoupper(trim((string) $s));
  if ($max !== null)
    $s = substr($s, 0, $max);
  return $s;
}

/* ============================================================================
   Row actions (toggle active / delete / set default)
============================================================================ */
if (isset($_GET['action'], $_GET['id'])) {
  $targetId = (int) $_GET['id'];

  if ($_GET['action'] === 'toggle_active') {
    $stmt = $conn->prepare("UPDATE csnk_branches SET status = CASE WHEN status = 'ACTIVE' THEN 'INACTIVE' ELSE 'ACTIVE' END WHERE id = ?");
    $stmt->bind_param('i', $targetId);
    if ($stmt->execute())
      setFlashMessage('success', 'Branch status updated.');
    else
      setFlashMessage('error', 'Failed to update branch status.');
    $stmt->close();
    redirect('branch_management.php');
  }

  if ($_GET['action'] === 'set_default') {
    // First, unset all defaults
    $conn->query("UPDATE csnk_branches SET is_default = 0");
    // Then set the selected one as default
    $stmt = $conn->prepare("UPDATE csnk_branches SET is_default = 1 WHERE id = ?");
    $stmt->bind_param('i', $targetId);
    if ($stmt->execute())
      setFlashMessage('success', 'Default branch updated.');
    else
      setFlashMessage('error', 'Failed to set default branch.');
    $stmt->close();
    redirect('branch_management.php');
  }

  if ($_GET['action'] === 'delete') {
    // Check if branch is default - prevent deletion
    $stmt = $conn->prepare("SELECT is_default FROM csnk_branches WHERE id = ?");
    $stmt->bind_param('i', $targetId);
    $stmt->execute();
    $result = $stmt->get_result();
    $branch = $result->fetch_assoc();
    $stmt->close();

    if ($branch && $branch['is_default'] == 1) {
      setFlashMessage('error', 'Cannot delete the default branch. Set another branch as default first.');
    } else {
      $stmt = $conn->prepare("DELETE FROM csnk_branches WHERE id = ?");
      $stmt->bind_param('i', $targetId);
      if ($stmt->execute())
        setFlashMessage('success', 'Branch deleted.');
      else
        setFlashMessage('error', 'Failed to delete branch.');
      $stmt->close();
    }
    redirect('branch_management.php');
  }
}

/* ============================================================================
   Add/Edit submit
============================================================================ */
$errors = [];
$reopenModal = ''; // 'add' or 'edit' (to reopen on error)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_branch'])) {
  $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
  $code = ensure_upper($_POST['code'] ?? '', 50);
  $name = trim((string) ($_POST['name'] ?? ''));
  $status = isset($_POST['status']) ? $_POST['status'] : 'ACTIVE';
  $is_default = isset($_POST['is_default']) ? 1 : 0;
  $sort_order = (int) ($_POST['sort_order'] ?? 0);

  // Validation
  if ($code === '')
    $errors[] = "Branch Code is required.";
  if (strlen($code) < 2)
    $errors[] = "Branch Code must be at least 2 characters.";
  if ($name === '')
    $errors[] = "Branch Name is required.";
  if (!in_array($status, ['ACTIVE', 'INACTIVE']))
    $errors[] = "Invalid status.";

  // Uniqueness checks
  if ($id > 0) {
    $chk = $conn->prepare("SELECT COUNT(*) FROM csnk_branches WHERE id <> ? AND (code = ? OR name = ?)");
    $chk->bind_param('iss', $id, $code, $name);
  } else {
    $chk = $conn->prepare("SELECT COUNT(*) FROM csnk_branches WHERE code = ? OR name = ?");
    $chk->bind_param('ss', $code, $name);
  }
  $chk->execute();
  $dups = (int) ($chk->get_result()->fetch_row()[0] ?? 0);
  $chk->close();
  if ($dups > 0)
    $errors[] = "Another branch with the same Code or Name already exists.";

  if (empty($errors)) {
    $ok = false;

    // Get current user
    $currentUsername = $currentUser['username'] ?? 'unknown';

    if ($id > 0) {
      // UPDATE
      $sql = "UPDATE csnk_branches
                       SET code=?, name=?, status=?, sort_order=?, updated_by=?
                     WHERE id=?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('sssisi', $code, $name, $status, $sort_order, $currentUsername, $id);
      if ($stmt->execute())
        $ok = true;
      else
        $errors[] = "Database error (update): " . $conn->error;
      $stmt->close();

      // Handle default setting
      if ($ok && $is_default) {
        $conn->query("UPDATE csnk_branches SET is_default = 0 WHERE id <> " . $id);
        $conn->query("UPDATE csnk_branches SET is_default = 1 WHERE id = " . $id);
      }
    } else {
      // INSERT
      $sql = "INSERT INTO csnk_branches (code, name, status, is_default, sort_order, created_by)
                    VALUES (?, ?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('sssiis', $code, $name, $status, $is_default, $sort_order, $currentUsername);
      if ($stmt->execute()) {
        $ok = true;
        $branchId = $conn->insert_id;
      } else
        $errors[] = "Database error (insert): " . $conn->error;
      $stmt->close();

      // If set as default, unset others
      if ($ok && $is_default) {
        $conn->query("UPDATE csnk_branches SET is_default = 0 WHERE id <> " . $branchId);
      }
    }

    if (empty($errors) && $ok) {
      setFlashMessage('success', ($id > 0) ? 'Branch saved successfully.' : 'Branch created successfully.');
      redirect('branch_management.php');
    }
  }

  // If errors: reopen the right modal and pass the posted data back via JS
  $reopenModal = ($id > 0) ? 'edit' : 'add';
  $postedData = [
    'id' => $id,
    'code' => $code,
    'name' => $name,
    'status' => $status,
    'is_default' => $is_default,
    'sort_order' => $sort_order
  ];
} else {
  $postedData = null;
}

/* ============================================================================
   Load branches
============================================================================ */
$branches = [];
$sql = "SELECT * FROM csnk_branches ORDER BY sort_order ASC, name ASC";
$res = $conn->query($sql);
if ($res) {
  $branches = $res->fetch_all(MYSQLI_ASSOC);
  $res->close();
}

$totalCount = count($branches);
$activeCount = array_reduce($branches, fn($a, $c) => $a + ($c['status'] === 'ACTIVE' ? 1 : 0), 0);
$inactiveCount = $totalCount - $activeCount;
?>
<style>
  :root {
    --ink: #0f172a;
    --muted: #64748b;
    --ring: rgba(37, 99, 235, .14);
    --bg: #f8fafc;
    --card: #ffffff;
    --card-2: #ffffff;
    --line: #e2e8f0;
    --ok: #16a34a;
    --warn: #f59e0b;
    --err: #ef4444;
    --pri: #2563eb;
    --pri-2: #60a5fa;
  }

  /* Light background */
  body {
    background: linear-gradient(180deg, #f1f5f9, #f8fafc) fixed;
  }

  /* Page title */
  .page-title h4 {
    font-weight: 800;
    letter-spacing: .3px;
    color: #1e293b;
  }

  .page-title p {
    color: #64748b;
  }

  /* Stat chips */
  .stats-chip {
    display: flex;
    align-items: center;
    gap: .65rem;
    padding: .7rem 1rem;
    border: 1px solid var(--line);
    border-radius: 1rem;
    background: #ffffff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    color: #334155;
  }

  .stats-chip .icon {
    width: 30px;
    height: 30px;
    display: grid;
    place-items: center;
    border-radius: 10px;
    background: rgba(37, 99, 235, .1);
    color: #2563eb;
  }

  .stats-chip .count {
    font-weight: 800;
    color: #0f172a;
    font-size: 1.05rem;
  }

  .stats-chip small {
    color: #64748b;
    display: block;
    margin-top: -2px;
  }

  /* Card */
  .card {
    border: 1px solid var(--line);
    border-radius: 1rem;
    overflow: hidden;
    background: #ffffff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  }

  .card-header {
    background: #f8fafc;
    border-bottom: 1px solid var(--line);
    padding: .85rem 1rem;
    position: sticky;
    top: 0;
    z-index: 3;
    color: #334155;
  }

  .card-header .title {
    font-weight: 700;
  }

  .card-body {
    background: transparent;
  }

  /* Table */
  .table-wrap {
    max-height: 64vh;
    overflow: auto;
  }

  table.table {
    margin: 0;
    color: #334155;
  }

  table thead th {
    position: sticky;
    top: 0;
    background: #f1f5f9;
    z-index: 2;
    border-bottom: 1px solid var(--line);
    color: #1e293b;
    font-weight: 700;
    letter-spacing: .2px;
  }

  .table td,
  .table th {
    vertical-align: middle;
    border-color: var(--line);
  }

  .table tbody tr:hover {
    background: rgba(37, 99, 235, .04);
  }

  /* Badges */
  .badge-soft {
    border: 1px solid var(--line);
    background: #f8fafc;
    color: #334155;
  }

  .badge-code {
    background: rgba(37, 99, 235, .1);
    color: #1d4ed8;
    cursor: pointer;
  }

  .badge-default {
    background: rgba(16, 185, 129, .1);
    color: #15803d;
  }

  /* Buttons */
  .btn-action {
    min-width: 36px;
    border-radius: 10rem;
    position: relative;
    z-index: 1;
  }

  .btn-toggle {
    border-radius: 999px;
  }

  .btn,
  .form-control,
  .form-select {
    border-radius: .75rem;
  }

  /* Inputs + focus */
  .form-control,
  .form-select {
    background: #ffffff;
    border-color: var(--line);
    color: #1e293b;
  }

  .form-control:focus,
  .form-select:focus {
    box-shadow: 0 0 0 .25rem var(--ring);
    border-color: #93c5fd;
    outline: none;
    color: #0f172a;
  }

  .input-icon {
    position: relative;
  }

  .input-icon>.bi {
    position: absolute;
    left: .75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
  }

  .input-icon>input {
    padding-left: 2.25rem;
  }

  .form-label {
    font-weight: 700;
    color: #1e293b;
  }

  .small-hint {
    color: #64748b;
  }

  .bg-soft {
    background: #f8fafc;
  }

  /* Divider */
  hr.hr-soft {
    border: 0;
    height: 1px;
    background: linear-gradient(90deg, rgba(0, 0, 0, .04), rgba(0, 0, 0, .1), rgba(0, 0, 0, .04));
  }

  /* Footer (sticky controls) */
  .card-footer {
    border-top: 1px solid var(--line);
    background: #f8fafc;
    position: sticky;
    bottom: 0;
    z-index: 2;
    color: #64748b;
  }

  /* Status pills */
  .badge-status {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .25rem .6rem;
    border-radius: 999px;
    font-weight: 700;
  }

  .badge-status.ok {
    background: rgba(22, 163, 74, .1);
    color: #15803d;
    border: 1px solid rgba(22, 163, 74, .2);
  }

  .badge-status.no {
    background: rgba(239, 68, 68, .1);
    color: #dc2626;
    border: 1px solid rgba(239, 68, 68, .2);
  }

  /* Default star */
  .default-star {
    color: #f59e0b;
  }

  /* Responsive */
  @media (max-width: 575.98px) {
    .page-title h4 {
      font-size: 1.05rem;
    }
  }
</style>

<div class="row align-items-center justify-content-between mb-3 page-title">
  <div class="col">
    <h4 class="mb-1">Branch Management</h4>
    <p class="mb-0">Manage CSNK branches and their settings.</p>
  </div>

  <div class="col-auto d-flex flex-wrap gap-2">
    <div class="stats-chip" title="All branches in the system" data-bs-toggle="tooltip">
      <div class="icon"><i class="bi bi-diagram-3"></i></div>
      <div>
        <div class="count"><?= (int) $totalCount ?></div>
        <small>Total</small>
      </div>
    </div>
    <div class="stats-chip" title="Active branches" data-bs-toggle="tooltip">
      <div class="icon"><i class="bi bi-check2-circle"></i></div>
      <div>
        <div class="count"><?= (int) $activeCount ?></div>
        <small>Active</small>
      </div>
    </div>
    <div class="stats-chip" title="Inactive branches" data-bs-toggle="tooltip">
      <div class="icon"><i class="bi bi-slash-circle"></i></div>
      <div>
        <div class="count"><?= (int) $inactiveCount ?></div>
        <small>Inactive</small>
      </div>
    </div>
  </div>
</div>

<!-- Toolbar -->
<div class="row g-2 align-items-center toolbar mb-3">
  <div class="col-sm-6">
    <div class="input-icon">
      <i class="bi bi-search"></i>
      <input id="tblFilter" type="search" class="form-control" placeholder="Search by name or code..."
        aria-label="Search branches">
    </div>
  </div>
  <div class="col-sm-auto">
    <select id="statusFilter" class="form-select" aria-label="Filter by status">
      <option value="">All statuses</option>
      <option value="ACTIVE">Active</option>
      <option value="INACTIVE">Inactive</option>
    </select>
  </div>
  <div class="col-sm-auto">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"
      title="Add new branch">
      <i class="bi bi-plus-lg me-1"></i><span class="label">Add Branch</span>
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
          <?php foreach ($errors as $e): ?>
            <li><?php echo h($e); ?></li><?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Branches Table -->
<div class="card shadow-sm">
  <div class="card-header d-flex align-items-center justify-content-between">
    <div class="title"><i class="bi bi-diagram-3 me-2 text-primary"></i>Branches</div>
    <div class="text-muted small" id="tableSummary">Showing 0–0 of 0</div>
  </div>
  <div class="card-body p-0 table-wrap">
    <table class="table table-hover align-middle mb-0" id="branchesTable">
      <thead>
        <tr>
          <th class="ps-3">ID</th>
          <th>Branch Name</th>
          <th>Code</th>
          <th class="text-center">Default</th>
          <th class="text-center">Sort Order</th>
          <th class="text-center">Status</th>
          <th class="text-end pe-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($branches)): ?>
          <tr>
            <td colspan="7" class="text-center py-5 text-muted">
              <div class="mb-2"><i class="bi bi-emoji-frown" style="font-size:1.25rem;"></i></div>
              No branches found. Click <strong>Add Branch</strong> to create your first one.
            </td>
          </tr>
        <?php else:
          foreach ($branches as $b): ?>
            <?php
            $isActive = $b['status'] === 'ACTIVE';
            $isDefault = (int) $b['is_default'] === 1;
            ?>
            <tr data-name="<?php echo strtolower(h($b['name'])); ?>" data-code="<?php echo strtolower(h($b['code'])); ?>"
              data-status="<?php echo $b['status']; ?>">
              <td class="ps-3 text-muted small"><?php echo (int) $b['id']; ?></td>
              <td class="text-nowrap">
                <strong><?php echo h($b['name']); ?></strong>
              </td>
              <td class="text-nowrap">
                <span class="badge badge-soft badge-code">
                  <?php echo h($b['code']); ?>
                </span>
              </td>
              <td class="text-center">
                <?php if ($isDefault): ?>
                  <span class="badge badge-default"><i class="bi bi-star-fill me-1"></i>Default</span>
                <?php else: ?>
                  <a href="branch_management.php?action=set_default&id=<?php echo (int) $b['id']; ?>"
                    class="btn btn-outline-secondary btn-sm"
                    onclick="return confirm('Set <?php echo h($b['name']); ?> as the default branch?');"
                    title="Set as Default">
                    <i class="bi bi-star"></i>
                  </a>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php echo (int) $b['sort_order']; ?>
              </td>
              <td class="text-center">
                <a href="branch_management.php?action=toggle_active&id=<?php echo (int) $b['id']; ?>"
                  class="text-decoration-none btn btn-outline-<?php echo $isActive ? 'success' : 'danger'; ?> btn-sm btn-toggle px-3"
                  onclick="return confirm('Toggle status for <?php echo h($b['name']); ?>?');"
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
                  <button type="button" class="btn btn-info btn-sm btn-action text-white" data-bs-toggle="modal"
                    data-bs-target="#viewModal" data-id="<?php echo (int) $b['id']; ?>"
                    data-name="<?php echo h($b['name']); ?>" data-code="<?php echo h($b['code']); ?>"
                    data-status="<?php echo h($b['status']); ?>" data-is-default="<?php echo $isDefault ? '1' : '0'; ?>"
                    data-sort-order="<?php echo (int) $b['sort_order']; ?>"
                    data-created-at="<?php echo h($b['created_at']); ?>"
                    data-created-by="<?php echo h($b['created_by']); ?>"
                    data-updated-at="<?php echo h($b['updated_at']); ?>"
                    data-updated-by="<?php echo h($b['updated_by']); ?>" title="View details" aria-label="View">
                    <i class="bi bi-eye"></i>
                  </button>

                  <!-- Edit -->
                  <button type="button" class="btn btn-warning btn-sm btn-action" data-bs-toggle="modal"
                    data-bs-target="#editModal" data-id="<?php echo (int) $b['id']; ?>"
                    data-name="<?php echo h($b['name']); ?>" data-code="<?php echo h($b['code']); ?>"
                    data-status="<?php echo h($b['status']); ?>" data-is-default="<?php echo $isDefault ? '1' : '0'; ?>"
                    data-sort-order="<?php echo (int) $b['sort_order']; ?>" title="Edit branch" aria-label="Edit">
                    <i class="bi bi-pencil-square"></i>
                  </button>

                  <!-- Delete -->
                  <?php if (!$isDefault): ?>
                    <button type="button" class="btn btn-danger btn-sm btn-action" data-bs-toggle="modal"
                      data-bs-target="#confirmDeleteModal"
                      data-delete-url="branch_management.php?action=delete&id=<?php echo (int) $b['id']; ?>"
                      data-delete-name="<?php echo h($b['name']); ?>" title="Delete" aria-label="Delete">
                      <i class="bi bi-trash"></i>
                    </button>
                  <?php else: ?>
                    <button type="button" class="btn btn-danger btn-sm btn-action" disabled
                      title="Cannot delete default branch" aria-label="Delete disabled">
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
    <div class="small">
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
        <h6 class="modal-title" id="addModalLabel"><i class="bi bi-plus-circle me-2 text-primary"></i>Add New Branch
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="branchFormAdd" action="branch_management.php" method="POST" novalidate>
        <input type="hidden" name="id" value="0">

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Branch Code <span class="text-danger">*</span></label>
              <div class="input-icon">
                <i class="bi bi-code-slash"></i>
                <input type="text" name="code" class="form-control form-control-lg" placeholder="e.g., CSNK-MNL"
                  maxlength="50" required>
              </div>
              <div class="small-hint mt-2">Unique code (e.g., CSNK-MNL, CSNK-CEBU)</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Branch Name <span class="text-danger">*</span></label>
              <div class="input-icon">
                <i class="bi bi-diagram-3"></i>
                <input type="text" name="name" class="form-control form-control-lg" placeholder="e.g., Manila Branch"
                  required>
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select name="status" class="form-select form-control-lg">
                <option value="ACTIVE" selected>Active</option>
                <option value="INACTIVE">Inactive</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Sort Order</label>
              <input type="number" name="sort_order" class="form-control form-control-lg" value="0" min="0">
            </div>

            <div class="col-md-4">
              <label class="form-label">&nbsp;</label>
              <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="is_default" id="isDefaultAdd">
                <label class="form-check-label" for="isDefaultAdd">Set as Default Branch</label>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" name="save_branch" class="btn btn-success px-4">
            <i class="bi bi-check-lg me-1"></i> Save Branch
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
        <h6 class="modal-title" id="viewModalLabel"><i class="bi bi-eye me-2 text-primary"></i>Branch Details</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <dl class="row mb-0">
          <dt class="col-sm-4">Branch Name</dt>
          <dd class="col-sm-8" id="v_name"></dd>
          <dt class="col-sm-4">Code</dt>
          <dd class="col-sm-8" id="v_code"></dd>
          <dt class="col-sm-4">Status</dt>
          <dd class="col-sm-8" id="v_status"></dd>
          <dt class="col-sm-4">Default</dt>
          <dd class="col-sm-8" id="v_is_default"></dd>
          <dt class="col-sm-4">Sort Order</dt>
          <dd class="col-sm-8" id="v_sort_order"></dd>
          <dt class="col-sm-4">Created At</dt>
          <dd class="col-sm-8" id="v_created_at"></dd>
          <dt class="col-sm-4">Created By</dt>
          <dd class="col-sm-8" id="v_created_by"></dd>
          <dt class="col-sm-4">Updated At</dt>
          <dd class="col-sm-8" id="v_updated_at"></dd>
          <dt class="col-sm-4">Updated By</dt>
          <dd class="col-sm-8" id="v_updated_by"></dd>
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
        <h6 class="modal-title" id="editModalLabel"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Branch
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="branchFormEdit" action="branch_management.php" method="POST" novalidate>
        <input type="hidden" name="id" id="e_id">

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Branch Code <span class="text-danger">*</span></label>
              <div class="input-icon">
                <i class="bi bi-code-slash"></i>
                <input type="text" name="code" id="e_code" class="form-control form-control-lg" maxlength="50" required>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Branch Name <span class="text-danger">*</span></label>
              <div class="input-icon">
                <i class="bi bi-diagram-3"></i>
                <input type="text" name="name" id="e_name" class="form-control form-control-lg" required>
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select name="status" id="e_status" class="form-select form-control-lg">
                <option value="ACTIVE">Active</option>
                <option value="INACTIVE">Inactive</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Sort Order</label>
              <input type="number" name="sort_order" id="e_sort_order" class="form-control form-control-lg" value="0"
                min="0">
            </div>

            <div class="col-md-4">
              <label class="form-label">&nbsp;</label>
              <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="is_default" id="e_is_default">
                <label class="form-check-label" for="e_is_default">Set as Default Branch</label>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" name="save_branch" class="btn btn-success px-4">
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
        <h6 class="modal-title" id="confirmDeleteLabel"><i class="bi bi-trash me-2 text-danger"></i>Delete Branch</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Are you sure you want to permanently delete <strong id="delTargetName">this branch</strong>?
          This cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <a id="confirmDeleteBtn" class="btn btn-danger"><i class="bi bi-trash me-1"></i> Delete</a>
      </div>
    </div>
  </div>
</div>

<!-- ===========================
     TABLE FILTER & PAGINATION
=========================== -->
<script>
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

  // ---------- Tooltips ----------
  document.addEventListener('DOMContentLoaded', () => {
    $$('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
  });

  // ---------- View modal population ----------
  const viewModal = document.getElementById('viewModal');
  viewModal?.addEventListener('show.bs.modal', (ev) => {
    const b = ev.relatedTarget;
    const d = (a) => b.getAttribute(a) || '';
    document.getElementById('v_name').textContent = d('data-name');
    document.getElementById('v_code').textContent = d('data-code');
    document.getElementById('v_status').textContent = d('data-status');
    document.getElementById('v_is_default').textContent = d('data-is-default') === '1' ? 'Yes' : 'No';
    document.getElementById('v_sort_order').textContent = d('data-sort-order');
    document.getElementById('v_created_at').textContent = d('data-created-at') || '-';
    document.getElementById('v_created_by').textContent = d('data-created-by') || '-';
    document.getElementById('v_updated_at').textContent = d('data-updated-at') || '-';
    document.getElementById('v_updated_by').textContent = d('data-updated-by') || '-';
  });

  // ---------- Edit modal population ----------
  const editModal = document.getElementById('editModal');
  editModal?.addEventListener('show.bs.modal', (ev) => {
    const b = ev.relatedTarget;
    const d = (a) => b.getAttribute(a) || '';

    document.getElementById('e_id').value = d('data-id');
    document.getElementById('e_name').value = d('data-name');
    document.getElementById('e_code').value = d('data-code');
    document.getElementById('e_status').value = d('data-status');
    document.getElementById('e_sort_order').value = d('data-sort-order');
    document.getElementById('e_is_default').checked = (d('data-is-default') === '1');
  });

  // ---------- Delete confirmation modal ----------
  const confirmDeleteModal = document.getElementById('confirmDeleteModal');
  confirmDeleteModal?.addEventListener('show.bs.modal', (ev) => {
    const b = ev.relatedTarget;
    const url = b.getAttribute('data-delete-url');
    const nm = b.getAttribute('data-delete-name') || 'this branch';
    document.getElementById('delTargetName').textContent = nm;
    const link = document.getElementById('confirmDeleteBtn');
    link.setAttribute('href', url);
  });

  // ---------- Table quick filter + status + pagination ----------
  const tblFilter = document.getElementById('tblFilter');
  const statusFilter = document.getElementById('statusFilter');
  const table = document.getElementById('branchesTable');
  const tbody = table?.querySelector('tbody');
  const pageInfo = document.getElementById('pageInfo');
  const tableSummary = document.getElementById('tableSummary');
  const prevBtn = document.getElementById('prevPage');
  const nextBtn = document.getElementById('nextPage');

  const PAGE_SIZE = 25;
  let page = 1;

  function getRows() {
    return [...tbody.querySelectorAll('tr')];
  }
  function matchesFilter(tr, q, status) {
    const name = tr.getAttribute('data-name') || '';
    const code = tr.getAttribute('data-code') || '';
    const st = tr.getAttribute('data-status') || '';
    const qok = (!q) || name.includes(q) || code.includes(q);
    const sok = (!status) || (st === status);
    return qok && sok;
  }
  function applyFilters() {
    const q = (tblFilter.value || '').trim().toLowerCase();
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

    const startN = total ? ((page - 1) * size + 1) : 0;
    const endN = Math.min(page * size, total);
    if (tableSummary) tableSummary.textContent = `Showing ${startN}–${endN} of ${total}`;
    if (pageInfo) pageInfo.textContent = `Page ${page}/${Math.max(1, maxPage)}`;
    prevBtn?.classList.toggle('disabled', page <= 1);
    nextBtn?.classList.toggle('disabled', page >= maxPage);
  }
  tblFilter?.addEventListener('input', () => { page = 1; applyFilters(); });
  statusFilter?.addEventListener('change', () => { page = 1; applyFilters(); });
  prevBtn?.addEventListener('click', () => { if (!prevBtn.classList.contains('disabled')) { page--; applyFilters(); } });
  nextBtn?.addEventListener('click', () => { if (!nextBtn.classList.contains('disabled')) { page++; applyFilters(); } });
  document.addEventListener('DOMContentLoaded', applyFilters);

  // ---------- Re-open modal with posted data on validation errors ----------
  <?php if (!empty($errors) && !empty($reopenModal)): ?>
      (function () {
        const mode = <?php echo json_encode($reopenModal); ?>;
        const data = <?php echo json_encode($postedData ?? []); ?>;
        const open = () => {
          if (mode === 'add') {
            const modal = new bootstrap.Modal(document.getElementById('addModal'));
            modal.show();
            const f = document.getElementById('branchFormAdd');
            if (!f) return;
            Object.entries(data).forEach(([k, v]) => {
              const el = f.querySelector(`[name="${k}"]`);
              if (!el) return;
              if (el.type === 'checkbox') el.checked = (v == 1);
              else el.value = v ?? '';
            });
          } else if (mode === 'edit') {
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
            const f = document.getElementById('branchFormEdit');
            if (!f) return;
            const map = {
              id: 'e_id', name: 'e_name', code: 'e_code', status: 'e_status',
              is_default: 'e_is_default', sort_order: 'e_sort_order'
            };
            Object.entries(data).forEach(([k, v]) => {
              const sel = map[k] ? `#${map[k]}` : `[name="${k}"]`;
              const el = document.querySelector(sel);
              if (!el) return;
              if (el.type === 'checkbox') el.checked = (v == 1);
              else el.value = v ?? '';
            });
          }
        };
        if (document.readyState === 'complete') open();
        else window.addEventListener('load', open);
      })();
  <?php endif; ?>
</script>