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
        } else {
            $_SESSION['flash'] = "error|Failed to update invoice.";
        }
    }

                        <select class="form-select" name="client_email" required onchange="loadClient(this)">
                            <option value="">Select client</option>
                            <?php foreach ($clients as $c): ?>
                                    data-name="<?= h($i['client_name']) ?>"
                                    data-email="<?= h($i['client_email']) ?>"
                                    data-phone="<?= h($i['client_phone']) ?>"
                                    data-total="<?= number_format($i['total_amount'],2) ?>"
                                    data-due="<?= h($i['due_date']) ?>"
                                    data-status="<?= h($i['status']) ?>"
                                    title="View">
                                    <i class="bi bi-eye"></i>
                                </button>
                <?php endforeach; ?>
                <?php if (!$invoices): ?>
                    <tr>
    <div class="toast-body border-top">
      <div class="d-flex gap-2">
        <button class="btn btn-outline-light btn-sm flex-fill" id="undoDelete">Undo Delete</button>
        <button class="btn btn-light btn-sm" data-bs-dismiss="toast">Dismiss</button>
      </div>
    </div>
  </div>
</div>
``

<script>
const monthlyStats = <?= json_encode($monthlyStats) ?>;
const statusStats  = <?= json_encode($statusStats) ?>;

// Fallback demo data if no real data
const demoMonthly = [
  {month: 'Jan 2026', amount: 15000},
  {month: 'Feb 2026', amount: 32000},
  {month: 'Mar 2026', amount: 28000}
];
const demoStatus = [
  {status: 'PENDING', count: 5},
  {status: 'PAID', count: 12}
];

const useDemo = monthlyStats.length === 0;

const ctx1 = document.getElementById('monthlyChart')?.getContext('2d');
if (ctx1) {
  new Chart(ctx1, {
    type: 'line',
    data: {
        labels: (useDemo ? demoMonthly : monthlyStats).map(r => r.month),
        datasets: [{
            data: (useDemo ? demoMonthly : monthlyStats).map(r => r.amount || 0),
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37,99,235,0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: { 
      plugins:{
        legend:{display:false},
        title: {
          display: useDemo,
          text: 'Demo Data - Create invoices to see real analytics',
          font: {size: 14},
          padding: 20
        }
      },
      scales:{y:{beginAtZero:true}} 
    }
  });
}

const ctx2 = document.getElementById('statusChart')?.getContext('2d');
if (ctx2) {
  new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: (useDemo ? demoStatus : statusStats).map(r => r.status),
        datasets: [{
            data: (useDemo ? demoStatus : statusStats).map(r => r.count || 0),
            backgroundColor: ['#facc15','#22c55e','#ef4444']
        }]
    },
    options: { 
      plugins:{
        legend:{position:'bottom'},
        title: {
          display: useDemo,
          text: 'Demo Data - Mark invoices as PAID to update',
          font: {size: 14},
          padding: 20
        }
      }
    }
  });
}

function loadClient(sel){
    const opt = sel.options[sel.selectedIndex];
    if(!opt) return;

    document.getElementById('c-name').value = opt.dataset.name || '';
    document.getElementById('c-phone').value = opt.dataset.phone || '';
    document.getElementById('c-bu').value = opt.dataset.bu || '';

    const apps = JSON.parse(opt.dataset.apps || '[]');
    const wrap = document.getElementById('salary-fields');
    wrap.innerHTML = '';
    apps.forEach(a=>{
        wrap.insertAdjacentHTML('beforeend',`
            <div class="col-md-6">
                <div class="card border shadow-sm">
                    <div class="card-body py-2">
                        <div class="small fw-semibold mb-1">${a.name}</div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control salary"
                                   name="applicants[${a.id}]" value="0" min="0" step="100">
                        </div>
                    </div>
                </div>
            </div>
        `);
    });
    calc();
}

function calc(){
    let t=0;
    document.querySelectorAll('.salary').forEach(i=>t+=parseFloat(i.value)||0);
    document.getElementById('total').textContent =
        t.toLocaleString('en-PH',{minimumFractionDigits:2});
}
document.addEventListener('input',e=>{
    if(e.target.classList.contains('salary')) calc();
});


document.addEventListener('click', function(e) {

  /* VIEW MODAL */
  if (e.target.closest('.view-btn')) {
    const b = e.target.closest('.view-btn');
    document.getElementById('view-client-name').textContent = b.dataset.name;
    document.getElementById('view-client-email').textContent = b.dataset.email;
    document.getElementById('view-client-phone').textContent = b.dataset.phone;
    document.getElementById('view-total').textContent = '₱' + b.dataset.total;
    document.getElementById('view-due').textContent = b.dataset.due;
    document.getElementById('view-status').textContent = b.dataset.status;
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