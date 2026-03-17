<?php
session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();
if (!$conn) die('Database connection failed.');

$message = '';

/* ================= HANDLE ACTIONS ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ✅ CREATE INVOICE */
    if (isset($_POST['create_invoice'])) {

        $client_email = trim($_POST['client_email']);
        $client_name  = trim($_POST['client_name']);
        $client_phone = trim($_POST['client_phone']);
        $business_unit_id = (int)$_POST['business_unit_id'];
        $due_date = $_POST['due_date'];

        $total_amount = 0;
        foreach ($_POST['applicants'] ?? [] as $salary) {
            $total_amount += (float)$salary;
        }

        $stmt = $conn->prepare("
            INSERT INTO salary_invoices
            (client_email, client_name, client_phone, business_unit_id, total_amount, due_date, status)
            VALUES (?, ?, ?, ?, ?, ?, 'PENDING')
        ");
        $stmt->bind_param(
            "sssids",
            $client_email,
            $client_name,
            $client_phone,
            $business_unit_id,
            $total_amount,
            $due_date
        );

        if ($stmt->execute()) {
            $invoice_id = $conn->insert_id;

            foreach ($_POST['applicants'] ?? [] as $app_id => $salary) {
                if ((float)$salary > 0) {
                    $item = $conn->prepare("
                        INSERT INTO salary_invoice_items
                        (invoice_id, applicant_id, basic_salary)
                        VALUES (?, ?, ?)
                    ");
                    $item->bind_param("iid", $invoice_id, $app_id, $salary);
                    $item->execute();
                }
            }

            $_SESSION['flash'] =
                "success|Invoice #{$invoice_id} created successfully (₱" .
                number_format($total_amount, 2) . ").";
        } else {
            $_SESSION['flash'] = "error|Failed to create invoice.";
        }
    }

    /* ✅ UPDATE INVOICE (EDIT MODAL) */
    if (isset($_POST['update_invoice'])) {

        $id = (int)$_POST['invoice_id'];
        $client_email = trim($_POST['edit_client_email']);
        $client_name  = trim($_POST['edit_client_name']);
        $client_phone = trim($_POST['edit_client_phone']);
        $business_unit = (int)$_POST['edit_business_unit_id'];
        $total = (float)$_POST['edit_total_amount'];
        $due_date = $_POST['edit_due_date'];

        $stmt = $conn->prepare("
            UPDATE salary_invoices
            SET client_email=?, client_name=?, client_phone=?,
                business_unit_id=?, total_amount=?, due_date=?
            WHERE id=? AND deleted_at IS NULL
        ");

        // ✅ FIXED TYPE STRING
        $stmt->bind_param(
            "sssidsi",
            $client_email,
            $client_name,
            $client_phone,
            $business_unit,
            $total,
            $due_date,
            $id
        );

        if ($stmt->execute()) {
            $_SESSION['flash'] = "success|Invoice updated successfully.";
        } else {et.status;
  }

  /* EDIT MODAL */
  if (e.target.closest('.edit-btn')) {
    const b = e.target.closest('.edit-btn');
    document.getElementById('edit-id').value = b.dataset.id;
    document.getElementById('edit_client_name').value = b.dataset.name;
    document.getElementById('edit-email').value = b.dataset.email;
    document.getElementById('edit-phone').value = b.dataset.phone;
    document.getElementById('edit_business_unit_id').value = '1';
    document.getElementById('edit-total').value = b.dataset.total;
    document.getElementById('edit-due').value = b.dataset.due;
  }

  /* TOGGLE STATUS */
  if (e.target.closest('.toggle-btn')) {
    const btn = e.target.closest('.toggle-btn');
    const id = btn.dataset.id;
    const currentStatus = btn.dataset.status;
    const newStatus = currentStatus === 'PAID' ? 'PENDING' : 'PAID';

    fetch('payments_clients.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `toggle_status=1&invoice_id=${id}&new_status=${newStatus}`
    }).then(() => {
      location.reload();
    });
  }

});


let deleteTimer;
let deleteInvoiceId = null;
let seconds = 10;
let toastInstance = null;

document.addEventListener('click', e => {

  /* DELETE CLICK */
  if (e.target.closest('.delete-btn')) {
    const btn = e.target.closest('.delete-btn');
    deleteInvoiceId = btn.dataset.id;

    fetch('payments_clients.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: 'soft_delete=1&invoice_id=' + deleteInvoiceId
    });

    document.getElementById('toastText').textContent =
      'Invoice for ' + btn.dataset.client + ' was deleted.';

    showToast();
  }

  /* UNDO */
  if (e.target.id === 'undoDelete') {
    if (toastInstance) toastInstance.hide();
    clearInterval(deleteTimer);
    fetch('payments_clients.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: 'undo_delete=1&invoice_id=' + deleteInvoiceId
    }).then(() => location.reload());
  }

  /* FORCE DELETE / DISMISS */
  if (e.target.id === 'closeToast' || e.target.matches('[data-bs-dismiss="toast"]')) {
    forceDelete();
  }
});

function showToast() {
  const toastEl = document.getElementById('deleteToast');
  toastInstance = new bootstrap.Toast(toastEl);
  toastInstance.show();
  
  const timerEl = document.getElementById('timer');
  seconds = 10;
  timerEl.textContent = seconds;

  deleteTimer = setInterval(() => {
    seconds--;
    timerEl.textContent = seconds;
    if (seconds <= 0) {
      forceDelete();
    }
  }, 1000);
}

function forceDelete() {
  if (toastInstance) toastInstance.hide();
  clearInterval(deleteTimer);
  fetch('payments_clients.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'force_delete=1&invoice_id=' + deleteInvoiceId
  }).then(() => location.reload());
}
</script>


<!-- ✅ VIEW INVOICE MODAL -->
<div class="modal fade" id="viewModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Invoice Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <strong>Client Name</strong>
            <div id="view-client-name" class="text-muted"></div>
          </div>
          <div class="col-md-6">
            <strong>Email</strong>
            <div id="view-client-email" class="text-muted"></div>
          </div>
          <div class="col-md-6">
            <strong>Phone</strong>
            <div id="view-client-phone" class="text-muted"></div>
          </div>
          <div class="col-md-6">
            <strong>Status</strong>
            <div id="view-status" class="text-muted"></div>
          </div>
          <div class="col-md-6">
            <strong>Total Amount</strong>
            <div id="view-total" class="fw-bold text-primary"></div>
          </div>
          <div class="col-md-6">
            <strong>Due Date</strong>
            <div id="view-due" class="text-muted"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>




<!-- ✅ EDIT INVOICE MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content">
      <input type="hidden" name="update_invoice" value="1">
      <input type="hidden" name="invoice_id" id="edit-id">
      <input type="hidden" name="edit_business_unit_id" id="edit_business_unit_id" value="1">

      <div class="modal-header bg-warning">
        <h5 class="modal-title">Edit Invoice</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Client Name</label>
          <input type="text" name="edit_client_name" id="edit_client_name" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Email</label>
          <input type="email" name="edit_client_email" id="edit-email" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Phone</label>
          <input type="text" name="edit_client_phone" id="edit-phone" class="form-control">
        </div>
        <div class="mb-2">
          <label class="form-label">Due Date</label>
          <input type="date" name="edit_due_date" id="edit-due" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Total Amount</label>
          <input type="number" step="0.01" name="edit_total_amount" id="edit-total" class="form-control" required>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-warning">Save Changes</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<?php include '../includes/footer.php'; ?>