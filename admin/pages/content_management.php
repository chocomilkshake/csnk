<?php
// content_management.php — Agency-scoped Content Management with multi-image DnD uploader
$pageTitle = 'Content Management';
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Check if admin/super_admin
if (!$isAdmin && !$isSuperAdmin) {
    setFlashMessage('error', 'You do not have permission to access Content Management.');
    header('Location: dashboard.php');
    exit;
}

$conn = $database->getConnection();

// Auto-add business_unit_id columns if they don't exist
$checkCat = $conn->query("SHOW COLUMNS FROM content_categories LIKE 'business_unit_id'");
if ($checkCat && $checkCat->num_rows === 0) {
    $conn->query("ALTER TABLE content_categories ADD COLUMN business_unit_id INT UNSIGNED DEFAULT 0 AFTER id");
}
$checkCat->close();

$checkItem = $conn->query("SHOW COLUMNS FROM content_items LIKE 'business_unit_id'");
if ($checkItem && $checkItem->num_rows === 0) {
    $conn->query("ALTER TABLE content_items ADD COLUMN business_unit_id INT UNSIGNED DEFAULT 0 AFTER id");
}
$checkItem->close();

// Get agencies for selector (using numeric IDs: 1=CSNK, 2=SMC)
$agencies = [];
$res = $conn->query("SELECT id, code, name FROM agencies WHERE active = 1 ORDER BY id ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $agencies[$row['id']] = ['code' => $row['code'], 'name' => $row['name']];
    }
}

// If no agencies, add defaults
if (empty($agencies)) {
    $conn->query("INSERT INTO agencies (id, code, name, active) VALUES (1, 'csnk', 'CSNK', 1)");
    $conn->query("INSERT INTO agencies (id, code, name, active) VALUES (2, 'smc', 'SMC', 1)");
    $agencies[1] = ['code' => 'csnk', 'name' => 'CSNK'];
    $agencies[2] = ['code' => 'smc', 'name' => 'SMC'];
}

// Get selected agency from query (using numeric ID) or default to CSNK (1)
$activeAgencyId = isset($_GET['agency']) ? (int)$_GET['agency'] : 1;
if (!isset($agencies[$activeAgencyId])) {
    $activeAgencyId = 1; // Default to CSNK
}
$activeAgencyCode = $agencies[$activeAgencyId]['code'];

// Get business units for selected agency
$businessUnits = [];
$stmt = $conn->prepare("
    SELECT bu.id, bu.code, bu.name, bu.country_id, c.name as country_name
    FROM business_units bu
    LEFT JOIN countries c ON c.id = bu.country_id
    WHERE bu.agency_id = ? AND bu.active = 1
    ORDER BY c.name, bu.name
");
$stmt->bind_param("i", $activeAgencyId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $businessUnits[$row['id']] = $row;
}
$stmt->close();

// Get selected BU or default to first
$activeBUId = isset($_GET['bu']) && isset($_GET['bu']) ? (int)$_GET['bu'] : 0;
if ($activeBUId <= 0 || !isset($businessUnits[$activeBUId])) {
    $firstBU = reset($businessUnits);
    $activeBUId = $firstBU ? (int)$firstBU['id'] : 0;
}

// If no BU available, show message
$showNoBUMessage = empty($businessUnits) || $activeBUId <= 0;

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$showNoBUMessage) {
    $action = $_POST['action'] ?? '';

    // Ensure we're using the correct BU from hidden field
    $formBU = (int)($_POST['business_unit_id'] ?? $activeBUId);
    if ($formBU !== $activeBUId) {
        $formBU = $activeBUId;
    }

    // =========================
    // Category operations
    // =========================
    if ($action === 'add_category') {
        $name = sanitizeInput($_POST['category_name'] ?? '');
        $description = sanitizeInput($_POST['category_description'] ?? '');

        if (!empty($name)) {
            // Get max display_order for this BU
            $stmt = $conn->prepare("SELECT COALESCE(MAX(display_order), 0) + 1 as next_order FROM content_categories WHERE business_unit_id = ?");
            $stmt->bind_param("i", $formBU);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $nextOrder = $row['next_order'] ?? 1;
            $stmt->close();

            $isActive = 1;
            $stmt = $conn->prepare("INSERT INTO content_categories (business_unit_id, name, description, display_order, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issii", $formBU, $name, $description, $nextOrder, $isActive);
            if ($stmt->execute()) {
                $message = 'Category added successfully!';
                $messageType = 'success';
                $auth->logActivity($_SESSION['admin_id'], 'Add Content Category', "Added category: $name (BU: $formBU)");
            } else {
                $message = 'Failed to add category.';
                $messageType = 'danger';
            }
            $stmt->close();
        } else {
            $message = 'Category name is required.';
            $messageType = 'warning';
        }
    } elseif ($action === 'update_category') {
        $id = (int) ($_POST['category_id'] ?? 0);
        $name = sanitizeInput($_POST['category_name'] ?? '');
        $description = sanitizeInput($_POST['category_description'] ?? '');

        if (!empty($name) && $id > 0) {
            // Verify ownership
            $stmt = $conn->prepare("SELECT id FROM content_categories WHERE id = ? AND business_unit_id = ?");
            $stmt->bind_param("ii", $id, $activeBUId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                $message = 'Invalid category.';
                $messageType = 'danger';
                $stmt->close();
            } else {
                $stmt->close();
                $stmt = $conn->prepare("UPDATE content_categories SET name = ?, description = ? WHERE id = ? AND business_unit_id = ?");
                $stmt->bind_param("ssii", $name, $description, $id, $activeBUId);
                if ($stmt->execute()) {
                    $message = 'Category updated successfully!';
                    $messageType = 'success';
                    $auth->logActivity($_SESSION['admin_id'], 'Update Content Category', "Updated category ID: $id");
                } else {
                    $message = 'Failed to update category.';
                    $messageType = 'danger';
                }
                $stmt->close();
            }
        } else {
            $message = 'Category name is required.';
            $messageType = 'warning';
        }
    } elseif ($action === 'delete_category') {
        $id = (int) ($_POST['category_id'] ?? 0);

        if ($id > 0) {
            // Verify ownership
            $stmt = $conn->prepare("SELECT image_path FROM content_items WHERE category_id = ? AND business_unit_id = ?");
            $stmt->bind_param("ii", $id, $activeBUId);
            $stmt->execute();
            $res = $stmt->get_result();
            $imgs = [];
            while ($row = $res->fetch_assoc()) {
                if (!empty($row['image_path'])) $imgs[] = $row['image_path'];
            }
            $stmt->close();

            // Delete files from disk
            foreach ($imgs as $img) {
                deleteFile($img);
            }

            // Delete items, then category
            $stmt = $conn->prepare("DELETE FROM content_items WHERE category_id = ? AND business_unit_id = ?");
            $stmt->bind_param("ii", $id, $activeBUId);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM content_categories WHERE id = ? AND business_unit_id = ?");
            $stmt->bind_param("ii", $id, $activeBUId);
            if ($stmt->execute()) {
                $message = 'Category and all its items deleted successfully!';
                $messageType = 'success';
                $auth->logActivity($_SESSION['admin_id'], 'Delete Content Category', "Deleted category ID: $id");
            } else {
                $message = 'Failed to delete category.';
                $messageType = 'danger';
            }
            $stmt->close();
        }
    } elseif ($action === 'toggle_category') {
        $id = (int) ($_POST['category_id'] ?? 0);
        $isActive = (int) ($_POST['is_active'] ?? 1);

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE content_categories SET is_active = ? WHERE id = ? AND business_unit_id = ?");
            $newStatus = $isActive ? 0 : 1;
            $stmt->bind_param("iii", $newStatus, $id, $activeBUId);
            if ($stmt->execute()) {
                $message = $newStatus ? 'Category activated!' : 'Category deactivated!';
                $messageType = 'success';
            } else {
                $message = 'Failed to toggle category.';
                $messageType = 'danger';
            }
            $stmt->close();
        }
    }

    // =========================
    // Content item operations
    // =========================
    elseif ($action === 'add_content_bulk') {
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $descAll = sanitizeInput($_POST['content_description'] ?? '');
        $titles = $_POST['titles'] ?? [];

        // Validate category exists and is active for this BU
        $stmt = $conn->prepare("SELECT id FROM content_categories WHERE id = ? AND business_unit_id = ? AND is_active = 1");
        $stmt->bind_param("ii", $categoryId, $activeBUId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $message = 'Invalid category selected.';
            $messageType = 'danger';
            $categoryId = 0;
        }
        $stmt->close();

        if ($categoryId > 0 && isset($_FILES['content_images'])) {
            $files = $_FILES['content_images'];
            $fileCount = is_array($files['name']) ? count($files['name']) : 0;

            if ($fileCount > 0) {
                // Get current max display order
                $stmt = $conn->prepare("SELECT COALESCE(MAX(display_order), 0) as max_order FROM content_items WHERE category_id = ? AND business_unit_id = ?");
                $stmt->bind_param("ii", $categoryId, $activeBUId);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();
                $currentOrder = (int)($res['max_order'] ?? 0);
                $stmt->close();

                $inserted = 0;
                $errors = [];

                for ($i = 0; $i < $fileCount; $i++) {
                    $one = [
                        'name' => $files['name'][$i] ?? '',
                        'type' => $files['type'][$i] ?? '',
                        'tmp_name' => $files['tmp_name'][$i] ?? '',
                        'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $files['size'][$i] ?? 0,
                    ];

                    if ($one['error'] !== UPLOAD_ERR_OK || empty($one['tmp_name'])) {
                        $errors[] = "File {$one['name']}: Upload error code " . $one['error'];
                        continue;
                    }

                    $savedPath = uploadFile($one, 'contents');

                    if ($savedPath) {
                        $title = '';
                        if (!empty($titles) && isset($titles[$i]) && trim($titles[$i]) !== '') {
                            $title = sanitizeInput($titles[$i]);
                        } else {
                            $nameOnly = pathinfo($one['name'], PATHINFO_FILENAME);
                            $title = sanitizeInput($nameOnly);
                        }
                        $currentOrder++;
                        $isActive = 1;

                        $stmt = $conn->prepare("INSERT INTO content_items (business_unit_id, category_id, title, image_path, description, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("iisssii", $activeBUId, $categoryId, $title, $savedPath, $descAll, $currentOrder, $isActive);
                        if ($stmt->execute()) {
                            $inserted++;
                        } else {
                            $errors[] = "Database error for {$one['name']}: " . $stmt->error;
                            deleteFile($savedPath);
                        }
                        $stmt->close();
                    } else {
                        $errors[] = "Failed to upload {$one['name']}. Check upload folder permissions.";
                    }
                }

                if ($inserted > 0) {
                    $message = "Successfully added {$inserted} image(s)!";
                    $messageType = 'success';
                    $auth->logActivity($_SESSION['admin_id'], 'Add Content Items (Bulk)', "Added {$inserted} item(s) to category {$categoryId}");
                } else {
                    $message = !empty($errors) ? implode("; ", $errors) : 'No images were saved. Please check the files and try again.';
                    $messageType = 'danger';
                }
            } else {
                $message = 'Please add at least one image.';
                $messageType = 'warning';
            }
        } else {
            $message = 'Please select a category and upload images.';
            $messageType = 'warning';
        }
    } elseif ($action === 'add_content') {
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $title = sanitizeInput($_POST['content_title'] ?? '');
        $description = sanitizeInput($_POST['content_description'] ?? '');

        if ($categoryId > 0 && isset($_FILES['content_image']) && $_FILES['content_image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = uploadFile($_FILES['content_image'], 'contents');
            if ($imagePath) {
                $stmt = $conn->prepare("SELECT COALESCE(MAX(display_order), 0) + 1 as next_order FROM content_items WHERE category_id = ? AND business_unit_id = ?");
                $stmt->bind_param("ii", $categoryId, $activeBUId);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $nextOrder = $row['next_order'] ?? 1;
                $stmt->close();

                $isActive = 1;
                $stmt = $conn->prepare("INSERT INTO content_items (business_unit_id, category_id, title, image_path, description, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisssii", $activeBUId, $categoryId, $title, $imagePath, $description, $nextOrder, $isActive);
                if ($stmt->execute()) {
                    $message = 'Content added successfully!';
                    $messageType = 'success';
                    $auth->logActivity($_SESSION['admin_id'], 'Add Content Item', "Added content to category: $categoryId");
                } else {
                    deleteFile($imagePath);
                    $message = 'Failed to save content.';
                    $messageType = 'danger';
                }
                $stmt->close();
            } else {
                $message = 'Failed to upload image.';
                $messageType = 'danger';
            }
        } else {
            $message = 'Please select a category and image.';
            $messageType = 'danger';
        }
    } elseif ($action === 'update_content') {
        $id = (int) ($_POST['content_id'] ?? 0);
        $title = sanitizeInput($_POST['content_title'] ?? '');
        $description = sanitizeInput($_POST['content_description'] ?? '');

        if ($id > 0) {
            // Get old image path
            $stmt = $conn->prepare("SELECT image_path FROM content_items WHERE id = ? AND business_unit_id = ?");
            $stmt->bind_param("ii", $id, $activeBUId);
            $stmt->execute();
            $result = $stmt->get_result();
            $oldItem = $result->fetch_assoc();
            $stmt->close();

            if (!$oldItem) {
                $message = 'Invalid content item.';
                $messageType = 'danger';
            } else {
                $newImagePath = '';
                $params = [$title, $description, $id, $activeBUId];
                $types = "ssii";

                if (isset($_FILES['content_image']) && $_FILES['content_image']['error'] === UPLOAD_ERR_OK) {
                    $uploaded = uploadFile($_FILES['content_image'], 'contents');
                    if ($uploaded) {
                        if (!empty($oldItem['image_path'])) {
                            deleteFile($oldItem['image_path']);
                        }
                        $newImagePath = $uploaded;
                        $params = array_merge([$newImagePath], $params);
                        $types = "s" . $types;
                    }
                }

                $sql = "UPDATE content_items SET title = ?, description = ?";
                if (!empty($newImagePath)) {
                    $sql .= ", image_path = ?";
                }
                $sql .= " WHERE id = ? AND business_unit_id = ?";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);

                if ($stmt->execute()) {
                    $message = 'Content updated successfully!';
                    $messageType = 'success';
                    $auth->logActivity($_SESSION['admin_id'], 'Update Content Item', "Updated content ID: $id");
                } else {
                    $message = 'Failed to update content.';
                    $messageType = 'danger';
                }
                $stmt->close();
            }
        }
    } elseif ($action === 'delete_content') {
        $id = (int) ($_POST['content_id'] ?? 0);

        if ($id > 0) {
            // Get image path before deleting
            $stmt = $conn->prepare("SELECT image_path FROM content_items WHERE id = ? AND business_unit_id = ?");
            $stmt->bind_param("ii", $id, $activeBUId);
            $stmt->execute();
            $result = $stmt->get_result();
                      $cid = (int)$cat['id'];
                      $itemCount = $categoryCounts[$cid] ?? 0;
                  ?>
                    <tr>
                      <td><?= (int) $cat['display_order'] ?></td>
                      <td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
                      <td><?= htmlspecialchars($cat['description'] ?? '-') ?></td>
                      <td>
                        <?php if (!empty($cat['is_active'])): ?>
                          <span class="badge bg-success">Active</span>
                        <?php else: ?>
                          <span class="badge bg-secondary">Inactive</span>
                        <?php endif; ?>
                      </td>
                      <td><span class="badge bg-primary"><?= $itemCount ?></span></td>
                      <td>
                        <div class="btn-group btn-group-sm">
                          <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?= $cat['id'] ?>" title="Edit">
                            <i class="bi bi-pencil"></i>
                          </button>
                          <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="toggle_category">
                            <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                            <input type="hidden" name="is_active" value="<?= (int)$cat['is_active'] ?>">
                            <input type="hidden" name="business_unit_id" value="<?= (int)$activeBUId ?>">
                            <button type="submit" class="btn btn-outline-<?= !empty($cat['is_active']) ? 'warning' : 'success' ?>" title="<?= !empty($cat['is_active']) ? 'Deactivate' : 'Activate' ?>">
                              <i class="bi bi-<?= !empty($cat['is_active']) ? 'eye-slash' : 'eye' ?>"></i>
                            </button>
                          </form>
                          <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure? This will also delete all images in this category.');">
                            <input type="hidden" name="action" value="delete_category">
                            <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                            <input type="hidden" name="business_unit_id" value="<?= (int)$activeBUId ?>">
                            <button type="submit" class="btn btn-outline-danger" title="Delete">
                              <i class="bi bi-trash"></i>
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>

                    <!-- Edit Category Modal -->
                    <div class="modal fade" id="editCategoryModal<?= $cat['id'] ?>" tabindex="-1">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title">Edit Category</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                          </div>
                          <form method="POST">
                            <div class="modal-body">
                              <input type="hidden" name="action" value="update_category">
                              <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                              <input type="hidden" name="business_unit_id" value="<?= (int)$activeBUId ?>">
                              <div class="mb-3">
                                <label class="form-label">Category Name</label>
                                <input type="text" class="form-control" name="category_name" value="<?= htmlspecialchars($cat['name']) ?>" required>
                              </div>
                              <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="category_description" rows="2"><?= htmlspecialchars($cat['description'] ?? '') ?></textarea>
                              </div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Content Tab -->
  <div class="tab-pane fade" id="content" role="tabpanel">
    <div class="row">

      <!-- Bulk Uploader -->
      <div class="col-lg-5 mb-4">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
            <h5 class="mb-0 fw-semibold">Add Images (Bulk)</h5>
            <span class="badge rounded-pill text-bg-light">Drag &amp; drop</span>
          </div>

          <div class="card-body">
            <form id="bulkForm" method="POST" enctype="multipart/form-data" onsubmit="return false;">
              <input type="hidden" name="action" value="add_content_bulk">
              <input type="hidden" name="business_unit_id" value="<?= (int)$activeBUId ?>">

              <div class="mb-3">
                <label class="form-label">Category <span class="text-danger">*</span></label>
                <select class="form-select" name="category_id" id="bulkCategory" required>
                  <option value="">Select Category</option>
                  <?php foreach ($categories as $cat): ?>
                    <?php if (!empty($cat['is_active'])): ?>
                      <option value="<?= $cat['id'] ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                        <?= isset($categoryCounts[$cat['id']]) ? ' ('.$categoryCounts[$cat['id']].')' : '' ?>
                      </option>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label">Description (applies to all)</label>
                <textarea class="form-control" name="content_description" id="bulkDesc" rows="2" placeholder="Optional description for all uploads"></textarea>
              </div>

              <!-- Dropzone -->
              <div id="dropzone" class="border-2 border-dashed rounded-3 p-4 text-center bg-light position-relative" style="border-color:#cbd5e1;">
                <div class="py-4">
                  <div class="mb-2">
                    <i class="bi bi-cloud-arrow-up fs-1 text-secondary"></i>
                  </div>
                  <p class="mb-1 fw-semibold">Drop images here or click to browse</p>
                  <p class="text-muted small mb-0">Supports JPG, PNG, GIF, WebP</p>
                </div>
                <input id="fileInput" type="file" accept="image/*" multiple class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor:pointer;">
              </div>

              <!-- Previews -->
              <div id="previewGrid" class="mt-3 row g-3"></div>

              <div class="d-flex gap-2 mt-3">
                <button id="btnClearAll" type="button" class="btn btn-outline-secondary btn-sm" disabled>Clear All</button>
                <button id="btnUpload" type="button" class="btn btn-primary ms-auto" disabled>
                  <i class="bi bi-cloud-arrow-up me-1"></i>Upload All
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Existing Items -->
      <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
            <h5 class="mb-0 fw-semibold">Content Items</h5>
            <div class="text-muted small"><?= count($contentItems) ?> item(s)</div>
          </div>
          <div class="card-body">
            <?php if (empty($contentItems)): ?>
              <p class="text-muted mb-0">No content yet. Add images to see them here.</p>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table align-middle table-hover">
                  <thead>
                    <tr>
                      <th>Image</th>
                      <th>Category</th>
                      <th>Title</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($contentItems as $item): ?>
                    <tr>
                      <td>
                        <?php if (!empty($item['image_path'])): ?>
                          <img src="<?= getFileUrl($item['image_path']) ?>" alt="" class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                        <?php else: ?>
                          <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="bi bi-image text-muted"></i>
                          </div>
                        <?php endif; ?>
                      </td>
                      <td><?= htmlspecialchars($item['category_name'] ?? '-') ?></td>
                      <td><?= htmlspecialchars($item['title'] ?? '-') ?></td>
                      <td>
                        <?php if (!empty($item['is_active'])): ?>
                          <span class="badge bg-success">Active</span>
                        <?php else: ?>
                          <span class="badge bg-secondary">Hidden</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <div class="btn-group btn-group-sm">
                          <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editContentModal<?= $item['id'] ?>" title="Edit">
                            <i class="bi bi-pencil"></i>
                          </button>
                          <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="toggle_content">
                            <input type="hidden" name="content_id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="business_unit_id" value="<?= (int)$activeBUId ?>">
                            <button type="submit" class="btn btn-outline-<?= !empty($item['is_active']) ? 'warning' : 'success' ?>" title="<?= !empty($item['is_active']) ? 'Hide' : 'Show' ?>">
                              <i class="bi bi-<?= !empty($item['is_active']) ? 'eye-slash' : 'eye' ?>"></i>
                            </button>
                          </form>
                          <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this content?');">
                            <input type="hidden" name="action" value="delete_content">
                            <input type="hidden" name="content_id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="business_unit_id" value="<?= (int)$activeBUId ?>">
                            <button type="submit" class="btn btn-outline-danger" title="Delete">
                              <i class="bi bi-trash"></i>
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>

                    <!-- Edit Content Modal -->
                    <div class="modal fade" id="editContentModal<?= $item['id'] ?>" tabindex="-1">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title">Edit Content</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                          </div>
                          <form method="POST" enctype="multipart/form-data">
                            <div class="modal-body">
                              <input type="hidden" name="action" value="update_content">
                              <input type="hidden" name="content_id" value="<?= $item['id'] ?>">
                              <input type="hidden" name="business_unit_id" value="<?= (int)$activeBUId ?>">
                              <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" class="form-control" name="content_title" value="<?= htmlspecialchars($item['title'] ?? '') ?>">
                              </div>
                              <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="content_description" rows="2"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
                              </div>
                              <div class="mb-3">
                                <label class="form-label">Replace Image</label>
                                <input type="file" class="form-control" name="content_image" accept="image/*">
                                <div class="form-text">Leave empty to keep current image</div>
                                <?php if (!empty($item['image_path'])): ?>
                                  <div class="mt-2">
                                    <img src="<?= getFileUrl($item['image_path']) ?>" class="img-thumbnail" style="max-height: 100px;">
                                  </div>
                                <?php endif; ?>
                              </div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>

<!-- Bulk Uploader JS -->
<script>
(function () {
  const dropzone = document.getElementById('dropzone');
  const fileInput = document.getElementById('fileInput');
  const preview = document.getElementById('previewGrid');
  const btnUpload = document.getElementById('btnUpload');
  const btnClear = document.getElementById('btnClearAll');
  const bulkCat = document.getElementById('bulkCategory');

  let items = [];
  let isUploading = false;

  function enableControls() {
    const hasItems = items.length > 0;
    const isCategoryChosen = (bulkCat.value !== '');
    btnUpload.disabled = !hasItems || !isCategoryChosen || isUploading;
    btnClear.disabled = !hasItems || isUploading;
  }

  function fileToDataURL(file) {
    return new Promise((resolve) => {
      const reader = new FileReader();
      reader.onload = e => resolve(e.target.result);
      reader.readAsDataURL(file);
    });
  }

  function createTile(idx, url, name, titleVal) {
    const col = document.createElement('div');
    col.className = 'col-6';
    col.draggable = true;
    col.dataset.index = String(idx);

    const card = document.createElement('div');
    card.className = 'border rounded-3 shadow-sm overflow-hidden position-relative';

    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'btn btn-sm btn-light position-absolute top-0 end-0 m-1 rounded-circle shadow-sm';
    remove.innerHTML = '<i class="bi bi-x-lg"></i>';

    const imgWrap = document.createElement('div');
    imgWrap.className = 'bg-light';
    imgWrap.style.height = '140px';
    const img = document.createElement('img');
    img.src = url;
    img.alt = name || 'image';
    img.className = 'w-100 h-100';
    img.style.objectFit = 'cover';
    imgWrap.appendChild(img);

    const body = document.createElement('div');
    body.className = 'p-2';
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'form-control form-control-sm';
    input.placeholder = 'Title (optional)';
    input.value = titleVal || '';
    input.addEventListener('input', () => {
      const i = parseInt(col.dataset.index, 10);
      if (!Number.isNaN(i) && items[i]) {
        items[i].title = input.value;
      }
    });
    body.appendChild(input);

    card.appendChild(remove);
    card.appendChild(imgWrap);
    card.appendChild(body);
    col.appendChild(card);

    remove.addEventListener('click', () => {
      const i = parseInt(col.dataset.index, 10);
      if (!Number.isNaN(i)) {
        items.splice(i, 1);
        render();
      }
    });

    return col;
  }

  async function render() {
    preview.innerHTML = '';
    for (let i = 0; i < items.length; i++) {
      const entry = items[i];
      const url = await fileToDataURL(entry.file);
      const tile = createTile(i, url, entry.file.name, entry.title || '');
      preview.appendChild(tile);
    }
    enableControls();
  }

  function addFiles(fileList) {
    const accept = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    const maxFiles = 40;
    for (const file of fileList) {
      if (!accept.includes(file.type)) continue;
      if (items.length >= maxFiles) break;
      items.push({ file, title: '' });
    }
    render();
  }

  dropzone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropzone.classList.add('border-primary', 'bg-white');
  });
  dropzone.addEventListener('dragleave', () => {
    dropzone.classList.remove('border-primary', 'bg-white');
  });
  dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzone.classList.remove('border-primary', 'bg-white');
    if (e.dataTransfer.files?.length) {
      addFiles(e.dataTransfer.files);
    }
  });

  fileInput.addEventListener('change', (e) => {
    addFiles(e.target.files);
    fileInput.value = '';
  });

  btnClear.addEventListener('click', () => {
    if (isUploading) return;
    items = [];
    render();
  });

  document.getElementById('bulkCategory').addEventListener('change', enableControls);

  btnUpload.addEventListener('click', async () => {
    if (isUploading) return;
    if (!items.length) return;
    if (bulkCat.value === '') { alert('Please select a category.'); return; }

    isUploading = true;
    enableControls();
    const origHtml = btnUpload.innerHTML;
    btnUpload.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Uploading...';

    try {
      const fd = new FormData();
      fd.append('action', 'add_content_bulk');
      fd.append('category_id', bulkCat.value);
      fd.append('business_unit_id', '<?= (int)$activeBUId ?>');
      fd.append('content_description', document.getElementById('bulkDesc').value || '');

      items.forEach(({file, title}) => {
        fd.append('content_images[]', file, file.name);
        fd.append('titles[]', title || '');
      });

      // Use window.location.origin to get the full URL including hostname
      const baseUrl = window.location.origin + window.location.pathname + '?agency=<?= (int)$activeAgencyId ?>&bu=<?= (int)$activeBUId ?>';
      await fetch(baseUrl, { method: 'POST', body: fd });
      window.location.reload();
    } catch (e) {
      console.error(e);
      alert('Upload failed. Please try again.');
      btnUpload.innerHTML = origHtml;
      isUploading = false;
      enableControls();
    }
  });
})();
</script>

