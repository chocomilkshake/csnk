<?php
// country_management.php — modernized Content Management with multi-image DnD uploader
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

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // =========================
    // Category operations
    // =========================
    if ($action === 'add_category') {
        $name = sanitizeInput($_POST['category_name'] ?? '');
        $description = sanitizeInput($_POST['category_description'] ?? '');

        if (!empty($name)) {
            $stmt = $conn->prepare("SELECT COALESCE(MAX(display_order), 0) + 1 as next_order FROM content_categories");
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $nextOrder = $row['next_order'] ?? 1;
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO content_categories (name, description, display_order) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $name, $description, $nextOrder);
            if ($stmt->execute()) {
                $message = 'Category added successfully!';
                $messageType = 'success';
                $auth->logActivity($_SESSION['admin_id'], 'Add Content Category', "Added category: $name");
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
            $stmt = $conn->prepare("UPDATE content_categories SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $description, $id);
            if ($stmt->execute()) {
                $message = 'Category updated successfully!';
                $messageType = 'success';
                $auth->logActivity($_SESSION['admin_id'], 'Update Content Category', "Updated category ID: $id");
            } else {
                $message = 'Failed to update category.';
                $messageType = 'danger';
            }
            $stmt->close();
        } else {
            $message = 'Category name is required.';
            $messageType = 'warning';
        }
    } elseif ($action === 'delete_category') {
        $id = (int) ($_POST['category_id'] ?? 0);

        if ($id > 0) {
            // Collect image paths to delete from disk
            $imgs = [];
            $stmt = $conn->prepare("SELECT image_path FROM content_items WHERE category_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                if (!empty($row['image_path'])) $imgs[] = $row['image_path'];
            }
            $stmt->close();

            foreach ($imgs as $img) {
                deleteFile($img);
            }

            // Delete items, then category
            $stmt = $conn->prepare("DELETE FROM content_items WHERE category_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM content_categories WHERE id = ?");
            $stmt->bind_param("i", $id);
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
            $stmt = $conn->prepare("UPDATE content_categories SET is_active = ? WHERE id = ?");
            $newStatus = $isActive ? 0 : 1;
            $stmt->bind_param("ii", $newStatus, $id);
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

    // NEW: bulk add via drag-and-drop multiple images
    elseif ($action === 'add_content_bulk') {
        $categoryId   = (int) ($_POST['category_id'] ?? 0);
        $descAll      = sanitizeInput($_POST['content_description'] ?? '');
        $titles       = $_POST['titles'] ?? []; // optional array, aligned to files
        // Files posted via JS FormData: content_images[] in order
        if ($categoryId > 0 && isset($_FILES['content_images'])) {
            $files = $_FILES['content_images'];
            $fileCount = is_array($files['name']) ? count($files['name']) : 0;

            if ($fileCount > 0) {
                // get current max display order
                $stmt = $conn->prepare("SELECT COALESCE(MAX(display_order), 0) as max_order FROM content_items WHERE category_id = ?");
                $stmt->bind_param("i", $categoryId);
                $stmt->execute();
                $res = $stmt->get_result()->fetch_assoc();
                $currentOrder = (int)($res['max_order'] ?? 0);
                $stmt->close();

                $inserted = 0;
                for ($i = 0; $i < $fileCount; $i++) {
                    // Build a pseudo $_FILES entry to reuse uploadFile()
                    $one = [
                        'name'     => $files['name'][$i] ?? '',
                        'type'     => $files['type'][$i] ?? '',
                        'tmp_name' => $files['tmp_name'][$i] ?? '',
                        'error'    => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                        'size'     => $files['size'][$i] ?? 0,
                    ];

                    if ($one['error'] === UPLOAD_ERR_OK && !empty($one['tmp_name'])) {
                        $savedPath = uploadFile($one, 'contents');
                        if ($savedPath) {
                            $title = '';
                            if (!empty($titles) && isset($titles[$i]) && trim($titles[$i]) !== '') {
                                $title = sanitizeInput($titles[$i]);
                            } else {
                                // fallback: filename without extension
                                $nameOnly = pathinfo($one['name'], PATHINFO_FILENAME);
                                $title = sanitizeInput($nameOnly);
                            }
                            $currentOrder++;

                            $stmt = $conn->prepare("
                                INSERT INTO content_items (category_id, title, image_path, description, display_order) 
                                VALUES (?, ?, ?, ?, ?)");
                            $stmt->bind_param("isssi", $categoryId, $title, $savedPath, $descAll, $currentOrder);
                            if ($stmt->execute()) {
                                $inserted++;
                            } else {
                                // failed DB insert, remove uploaded file
                                deleteFile($savedPath);
                            }
                            $stmt->close();
                        }
                    }
                }

                if ($inserted > 0) {
                    $message = "Successfully added {$inserted} image(s)!";
                    $messageType = 'success';
                    $auth->logActivity($_SESSION['admin_id'], 'Add Content Items (Bulk)', "Added {$inserted} item(s) to category {$categoryId}");
                } else {
                    $message = 'No images were saved. Please check the files and try again.';
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
    }

    elseif ($action === 'add_content') {
        // (kept for single-add compatibility)
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $title = sanitizeInput($_POST['content_title'] ?? '');
        $description = sanitizeInput($_POST['content_description'] ?? '');

        if ($categoryId > 0 && isset($_FILES['content_image']) && $_FILES['content_image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = uploadFile($_FILES['content_image'], 'contents');
            if ($imagePath) {
                $stmt = $conn->prepare("SELECT COALESCE(MAX(display_order), 0) + 1 as next_order FROM content_items WHERE category_id = ?");
                $stmt->bind_param("i", $categoryId);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $nextOrder = $row['next_order'] ?? 1;
                $stmt->close();

                $stmt = $conn->prepare("INSERT INTO content_items (category_id, title, image_path, description, display_order) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isssi", $categoryId, $title, $imagePath, $description, $nextOrder);
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
            $newImagePath = '';
            $params = [$title, $description, $id];
            $types = "ssi";

            if (isset($_FILES['content_image']) && $_FILES['content_image']['error'] === UPLOAD_ERR_OK) {
                // Get old image path
                $stmt = $conn->prepare("SELECT image_path FROM content_items WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $oldItem = $result->fetch_assoc();
                $stmt->close();

                // Upload new first
                $uploaded = uploadFile($_FILES['content_image'], 'contents');
                if ($uploaded) {
                    if ($oldItem && !empty($oldItem['image_path'])) {
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
            $sql .= " WHERE id = ?";

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
    } elseif ($action === 'delete_content') {
        $id = (int) ($_POST['content_id'] ?? 0);

        if ($id > 0) {
            // Get image path before deleting
            $stmt = $conn->prepare("SELECT image_path FROM content_items WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $item = $result->fetch_assoc();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM content_items WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                if ($item && !empty($item['image_path'])) {
                    deleteFile($item['image_path']);
                }
                $message = 'Content deleted successfully!';
                $messageType = 'success';
                $auth->logActivity($_SESSION['admin_id'], 'Delete Content Item', "Deleted content ID: $id");
            } else {
                $message = 'Failed to delete content.';
                $messageType = 'danger';
            }
            $stmt->close();
        }
    } elseif ($action === 'toggle_content') {
        $id = (int) ($_POST['content_id'] ?? 0);

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE content_items SET is_active = NOT is_active WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = 'Content visibility toggled!';
                $messageType = 'success';
            } else {
                $message = 'Failed to toggle content.';
                $messageType = 'danger';
            }
            $stmt->close();
        }
    }
}

// =========================
// Fetch for display
// =========================
$categories = [];
$stmt = $conn->query("SELECT * FROM content_categories ORDER BY display_order ASC, id ASC");
if ($stmt) {
    while ($row = $stmt->fetch_assoc()) {
        $categories[] = $row;
    }
}

// content with category info
$contentItems = [];
$stmt = $conn->query("
    SELECT ci.*, cc.name as category_name 
    FROM content_items ci 
    LEFT JOIN content_categories cc ON ci.category_id = cc.id 
    ORDER BY COALESCE(cc.display_order, 9999) ASC, ci.display_order ASC, ci.id ASC
");
if ($stmt) {
    while ($row = $stmt->fetch_assoc()) {
        $contentItems[] = $row;
    }
}

// counts per category
$categoryCounts = [];
foreach ($contentItems as $itm) {
    $cid = (int)$itm['category_id'];
    $categoryCounts[$cid] = ($categoryCounts[$cid] ?? 0) + 1;
}
?>

<!-- Tailwind (CDN) -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          brand: { DEFAULT: '#b42a00', dark: '#8d2100', light: '#ffede6' },
          ink: '#101320',
          soft: '#f7f9fb'
        },
        boxShadow: {
          card: '0 6px 18px rgba(0,0,0,.08)',
          cardHover: '0 14px 36px rgba(0,0,0,.16)'
        }
      }
    }
  }
</script>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0 fw-semibold">Content Management</h4>
  <a href="dashboard.php" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
  </a>
</div>

<?php if (!empty($message)): ?>
  <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Stats row -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="p-3 rounded-3" style="background:#ffede6"><i class="bi bi-folder2-open fs-4 text-danger"></i></div>
        <div>
          <div class="text-muted small">Categories</div>
          <div class="h5 mb-0"><?= count($categories) ?></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="p-3 rounded-3" style="background:#e8f5e9"><i class="bi bi-images fs-4 text-success"></i></div>
        <div>
          <div class="text-muted small">Images</div>
          <div class="h5 mb-0"><?= count($contentItems) ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="contentTab" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab">
      <i class="bi bi-folder me-2"></i>Categories
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="content-tab" data-bs-toggle="tab" data-bs-target="#content" type="button" role="tab">
      <i class="bi bi-images me-2"></i>Content / Images
    </button>
  </li>
</ul>

<div class="tab-content" id="contentTabContent">

  <!-- =================== -->
  <!-- Categories Tab     -->
  <!-- =================== -->
  <div class="tab-pane fade show active" id="categories" role="tabpanel">
    <div class="row">
      <div class="col-lg-4 mb-4">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold">Add Category</h5>
          </div>
          <div class="card-body">
            <form method="POST">
              <input type="hidden" name="action" value="add_category">
              <div class="mb-3">
                <label class="form-label">Category Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="category_name" required placeholder="e.g., Kasambahay">
              </div>
              <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="category_description" rows="2" placeholder="Optional description"></textarea>
              </div>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Add Category
              </button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-semibold">Categories</h5>
          </div>
          <div class="card-body">
            <?php if (empty($categories)): ?>
              <p class="text-muted mb-0">No categories yet. Add one to get started.</p>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table align-middle table-hover">
                  <thead>
                    <tr>
                      <th>Order</th>
                      <th>Name</th>
                      <th>Description</th>
                      <th>Status</th>
                      <th>Items</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($categories as $cat): 
                      $cid = (int)$cat['id'];
                      $itemCount = $categoryCounts[$cid] ?? 0;
                  ?>
                    <tr>
                      <td><?= (int) $cat['display_order'] ?></td>
                      <td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
                      <td><?= htmlspecialchars($cat['description'] ?? '-') ?></td>
                      <td>
                        <?php if ($cat['is_active']): ?>
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
                            <input type="hidden" name="is_active" value="<?= $cat['is_active'] ?>">
                            <button type="submit" class="btn btn-outline-<?= $cat['is_active'] ? 'warning' : 'success' ?>" title="<?= $cat['is_active'] ? 'Deactivate' : 'Activate' ?>">
                              <i class="bi bi-<?= $cat['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                            </button>
                          </form>
                          <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure? This will also delete all images in this category.');">
                            <input type="hidden" name="action" value="delete_category">
                            <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
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

  <!-- =================== -->
  <!-- Content Tab        -->
  <!-- =================== -->
  <div class="tab-pane fade" id="content" role="tabpanel">
    <div class="row">

      <!-- Bulk Uploader (Modern) -->
      <div class="col-lg-5 mb-4">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
            <h5 class="mb-0 fw-semibold">Add Images (Bulk)</h5>
            <span class="badge rounded-pill text-bg-light">Drag &amp; drop, reorder</span>
          </div>

          <div class="card-body">
            <form id="bulkForm" method="POST" enctype="multipart/form-data" onsubmit="return false;">
              <input type="hidden" name="action" value="add_content_bulk">

              <div class="mb-3">
                <label class="form-label">Category <span class="text-danger">*</span></label>
                <select class="form-select" name="category_id" id="bulkCategory" required>
                  <option value="">Select Category</option>
                  <?php foreach ($categories as $cat): ?>
                    <?php if ($cat['is_active']): ?>
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
              <div id="dropzone"
                   class="border-2 border-dashed rounded-3 p-4 text-center bg-light position-relative"
                   style="border-color:#cbd5e1;">
                <div class="py-4">
                  <div class="mb-2">
                    <i class="bi bi-cloud-arrow-up fs-1 text-secondary"></i>
                  </div>
                  <p class="mb-1 fw-semibold">Drop images here or click to browse</p>
                  <p class="text-muted small mb-0">You can drag to reorder before uploading</p>
                </div>
                <input id="fileInput" type="file" accept="image/*" multiple class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor:pointer;">
              </div>

              <!-- Previews (sortable) -->
              <div id="previewGrid" class="mt-3 row g-3">
                <!-- JS fills tiles here -->
              </div>

              <div class="d-flex gap-2 mt-3">
                <button id="btnClearAll" type="button" class="btn btn-outline-secondary btn-sm" disabled>Clear All</button>
                <button id="btnUpload" type="submit" class="btn btn-primary ms-auto" disabled>
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
                        <?php if ($item['is_active']): ?>
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
                            <button type="submit" class="btn btn-outline-<?= $item['is_active'] ? 'warning' : 'success' ?>" title="<?= $item['is_active'] ? 'Hide' : 'Show' ?>">
                              <i class="bi bi-<?= $item['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                            </button>
                          </form>
                          <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this content?');">
                            <input type="hidden" name="action" value="delete_content">
                            <input type="hidden" name="content_id" value="<?= $item['id'] ?>">
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

<?php require_once '../includes/footer.php'; ?>

<!-- =============== -->
<!-- Bulk Uploader JS -->
<!-- =============== -->
<script>
(function () {
  const dropzone   = document.getElementById('dropzone');
  const fileInput  = document.getElementById('fileInput');
  const preview    = document.getElementById('previewGrid');
  const btnUpload  = document.getElementById('btnUpload');
  const btnClear   = document.getElementById('btnClearAll');
  const bulkForm   = document.getElementById('bulkForm');
  const bulkCat    = document.getElementById('bulkCategory');

  // Internal state: array of {file, title}
  let items = [];

  // Helpers
  function enableControls() {
    const hasItems = items.length > 0;
    btnUpload.disabled = !hasItems || !bulkCat.value;
    btnClear.disabled = !hasItems;
  }

  function fileToDataURL(file) {
    return new Promise((resolve) => {
      const reader = new FileReader();
      reader.onload = e => resolve(e.target.result);
      reader.readAsDataURL(file);
    });
  }

  function createTile(idx, url, name, titleVal) {
    // Column
    const col = document.createElement('div');
    col.className = 'col-6';
    col.draggable = true;
    col.dataset.index = String(idx);

    // Card
    const card = document.createElement('div');
    card.className = 'border rounded-3 shadow-sm overflow-hidden position-relative';

    // Drag handle
    const handle = document.createElement('div');
    handle.className = 'position-absolute top-0 start-0 p-1';
    handle.innerHTML = '<span class="badge text-bg-dark"><i class="bi bi-grip-vertical"></i></span>';

    // Remove button
    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'btn btn-sm btn-light position-absolute top-0 end-0 m-1 rounded-circle shadow-sm';
    remove.innerHTML = '<i class="bi bi-x-lg"></i>';

    // Image
    const imgWrap = document.createElement('div');
    imgWrap.className = 'bg-light';
    imgWrap.style.height = '140px';
    const img = document.createElement('img');
    img.src = url;
    img.alt = name || 'image';
    img.className = 'w-100 h-100';
    img.style.objectFit = 'cover';
    imgWrap.appendChild(img);

    // Title input
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

    card.appendChild(handle);
    card.appendChild(remove);
    card.appendChild(imgWrap);
    card.appendChild(body);
    col.appendChild(card);

    // Remove logic
    remove.addEventListener('click', () => {
      const i = parseInt(col.dataset.index, 10);
      if (!Number.isNaN(i)) {
        items.splice(i, 1);
        render();
      }
    });

    // Drag logic
    col.addEventListener('dragstart', (e) => {
      e.dataTransfer.setData('text/plain', col.dataset.index);
      // add dragging style
      col.classList.add('opacity-50');
    });
    col.addEventListener('dragend', () => col.classList.remove('opacity-50'));
    col.addEventListener('dragover', (e) => e.preventDefault());
    col.addEventListener('drop', (e) => {
      e.preventDefault();
      const from = parseInt(e.dataTransfer.getData('text/plain'), 10);
      const to = parseInt(col.dataset.index, 10);
      if (!Number.isNaN(from) && !Number.isNaN(to) && from !== to) {
        const [moved] = items.splice(from, 1);
        items.splice(to, 0, moved);
        render();
      }
    });

    return col;
  }

  function render() {
    preview.innerHTML = '';
    items.forEach(async (entry, i) => {
      const url = await fileToDataURL(entry.file);
      const tile = createTile(i, url, entry.file.name, entry.title || '');
      preview.appendChild(tile);
    });
    enableControls();
  }

  function addFiles(fileList) {
    const accept = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
    const maxFiles = 40; // arbitrary safety limit
    for (const file of fileList) {
      if (!accept.includes(file.type)) continue;
      if (items.length >= maxFiles) break;
      items.push({ file, title: '' });
    }
    render();
  }

  // Drag & drop
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

  // Click to select
  fileInput.addEventListener('change', (e) => {
    addFiles(e.target.files);
    fileInput.value = ''; // reset
  });

  // Clear all
  btnClear.addEventListener('click', () => {
    items = [];
    render();
  });

  // Enable upload when category chosen
  document.getElementById('bulkCategory').addEventListener('change', enableControls);

  // Submit via fetch to preserve file order
  btnUpload.addEventListener('click', async () => {
    if (!items.length) return;
    if (!bulkCat.value) { alert('Please select a category.'); return; }

    btnUpload.disable

<style>
/* subtle helper to show draggable */
[draggable="true"] { cursor: grab; }
[draggable="true"]:active { cursor: grabbing; }
</style>