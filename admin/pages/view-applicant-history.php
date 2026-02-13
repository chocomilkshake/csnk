<?php
// FILE: pages/view-applicant-history.php
$pageTitle = 'Applicant Blacklist History';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

// Ensure session for consistency (header.php usually starts it)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Resolve role (from header.php globals)
$role         = $currentUser['role'] ?? 'employee';
$isSuperAdmin = ($role === 'super_admin');
$isAdmin      = ($role === 'admin');

if (!($isAdmin || $isSuperAdmin)) {
    setFlashMessage('error', 'You do not have permission to view applicant blacklist history.');
    redirect('dashboard.php');
    exit;
}

// Validate applicant id
if (!isset($_GET['id'])) {
    setFlashMessage('error', 'Applicant ID is required.');
    redirect('applicants.php');
    exit;
}
$applicantId = (int)$_GET['id'];
if ($applicantId <= 0) {
    setFlashMessage('error', 'Invalid applicant ID.');
    redirect('applicants.php');
    exit;
}

// Load applicant basics
$applicant = new Applicant($database);
$applicantData = $applicant->getById($applicantId);
if (!$applicantData) {
    setFlashMessage('error', 'Applicant not found.');
    redirect('applicants.php');
    exit;
}

// Check if currently blacklisted (to show quick link)
$conn = $database->getConnection();
$activeBlacklistId = null;
if ($conn instanceof mysqli) {
    if ($stmt = $conn->prepare("SELECT id FROM blacklisted_applicants WHERE applicant_id = ? AND is_active = 1 ORDER BY created_at DESC LIMIT 1")) {
        $stmt->bind_param("i", $applicantId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        if (!empty($row['id'])) {
            $activeBlacklistId = (int)$row['id'];
        }
        $stmt->close();
    }
}

// Fetch full history for this applicant
$history = [];
if ($conn instanceof mysqli) {
    $sqlHist = "
        SELECT
            b.*,
            au.full_name AS created_by_name, au.username AS created_by_username,
            ru.full_name AS reverted_by_name, ru.username AS reverted_by_username
        FROM blacklisted_applicants b
        LEFT JOIN admin_users au ON au.id = b.created_by
        LEFT JOIN admin_users ru ON ru.id = b.reverted_by
        WHERE b.applicant_id = ?
        ORDER BY b.created_at DESC
    ";
    if ($stmtH = $conn->prepare($sqlHist)) {
        $stmtH->bind_param("i", $applicantId);
        $stmtH->execute();
        $resH = $stmtH->get_result();
        $history = $resH ? $resH->fetch_all(MYSQLI_ASSOC) : [];
        $stmtH->close();
    }
}

// Helpers
function jsonToListPaths(?string $json): array {
    if ($json === null || trim($json) === '') return [];
    $arr = json_decode($json, true);
    if (!is_array($arr)) return [];
    return array_values(array_filter(array_map('strval', $arr)));
}

$fullName = getFullName(
    $applicantData['first_name'] ?? '',
    $applicantData['middle_name'] ?? '',
    $applicantData['last_name'] ?? '',
    $applicantData['suffix'] ?? ''
);

$pictureUrl = !empty($applicantData['picture']) ? getFileUrl($applicantData['picture']) : null;

// Quick counts
$totalCount    = count($history);
$activeCount   = 0;
$revertedCount = 0;
foreach ($history as $h) {
    if ((int)($h['is_active'] ?? 0) === 1) $activeCount++; else $revertedCount++;
}
?>
<style>
/* ====== Modern Timeline / Stepper UI ====== */
:root{
  --rail:#e9ecef;
  --dot:#0d6efd;
  --dot-shadow: rgba(13,110,253,.18);
  --muted:#6c757d;
  --soft-danger-bg:#fdecec; --soft-danger:#c1121f; --soft-danger-b:#f7c9c9;
  --soft-success-bg:#e8f7ec; --soft-success:#146c43; --soft-success-b:#c7ebd2;
}
.history-toolbar{
  position: sticky; top: -.5rem; z-index: 5;
  background: #fff; border: 1px solid #eef2f7; border-radius: .75rem;
  padding: .75rem .75rem; box-shadow: 0 1px 2px rgba(0,0,0,.03);
}
.stat-chip{
  display:inline-flex; align-items:center; gap:.4rem; padding:.25rem .6rem;
  border-radius:999px; background:#f8fafc; border:1px solid #eef2f7; color:#0f172a; font-weight:600; font-size:.85rem;
}
.stat-chip .dot{width:.5rem;height:.5rem;border-radius:999px;display:inline-block}
.stat-chip .dot.active{background:#dc3545}
.stat-chip .dot.reverted{background:#198754}
.timeline{
  position:relative; margin:0; padding:0 0 0 1.5rem; list-style:none;
}
.timeline::before{
  content:''; position:absolute; left:10px; top:0; bottom:0; width:2px; background:var(--rail);
}
.timeline-item{
  position:relative; margin-bottom:1rem;
}
.timeline-item::before{
  content:''; position:absolute; left:5px; top:.65rem; width:12px; height:12px; background:var(--dot); border-radius:50%;
  box-shadow: 0 0 0 4px var(--dot-shadow);
}
.timeline-card{
  border:1px solid #eef2f7; border-radius:.75rem; overflow:hidden; box-shadow: 0 1px 1px rgba(0,0,0,.03);
}
.timeline-card .card-header{
  background:#fff; border-bottom:1px solid #f1f3f5; padding:.75rem 1rem;
}
.timeline-card .summary{
  display:flex; flex-wrap:wrap; gap:.5rem 1rem; align-items:center;
}
.badge-soft-danger{background:var(--soft-danger-bg); color:var(--soft-danger); border:1px solid var(--soft-danger-b);}
.badge-soft-success{background:var(--soft-success-bg); color:var(--soft-success); border:1px solid var(--soft-success-b);}
.meta{
  display:flex; gap:.5rem 1rem; flex-wrap:wrap; color:var(--muted); font-size:.9rem;
}
.meta .item{display:flex; align-items:center; gap:.4rem;}
.file-pill{
  display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .55rem;
  border:1px solid #e9ecef; border-radius:999px; background:#f8f9fa; color:#0d6efd; text-decoration:none; font-size:.85rem;
}
.file-pill:hover{ background:#eef2f7; text-decoration:none; }
.filter-wrap{
  display:flex; gap:.5rem; flex-wrap:wrap;
}
.form-rounded{border-radius:999px !important;}
/* small screen paddings */
@media (max-width: 576px){
  .timeline{padding-left:1.25rem;}
  .timeline::before{left:8px;}
  .timeline-item::before{left:2px;}
}
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0 fw-semibold">Applicant Blacklist History</h4>
    <small class="text-muted">Complete timeline of blacklist actions, compliance, and reversions.</small>
  </div>
  <div class="d-flex gap-2">
    <?php if ($activeBlacklistId): ?>
      <a class="btn btn-outline-danger btn-sm" href="<?php echo 'blacklisted-view.php?id='.(int)$activeBlacklistId; ?>">
        <i class="bi bi-slash-circle me-1"></i>Active Blacklist Details
      </a>
    <?php endif; ?>
    <!-- ✅ NEW: Print button -->
    <a class="btn btn-outline-primary btn-sm" target="_blank" href="<?php echo '../includes/print-blacklisted-view.php?id='.(int)$applicantId; ?>">
      <i class="bi bi-printer me-1"></i>Print History
    </a>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo 'view-applicant.php?id='.(int)$applicantId; ?>">
      <i class="bi bi-arrow-left me-1"></i>Back to Applicant
    </a>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body">
    <div class="d-flex align-items-center gap-3">
      <?php if ($pictureUrl): ?>
        <img src="<?php echo htmlspecialchars($pictureUrl, ENT_QUOTES, 'UTF-8'); ?>"
             alt="Photo" class="rounded-circle" width="64" height="64" style="object-fit:cover;">
      <?php else: ?>
        <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center"
             style="width:64px;height:64px;">
          <?php echo strtoupper(substr((string)($applicantData['first_name'] ?? ''), 0, 1)); ?>
        </div>
      <?php endif; ?>
      <div class="flex-grow-1">
        <div class="fw-semibold fs-5"><?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="text-muted small">
          Applicant ID: <?php echo (int)$applicantId; ?>
          <?php if ($activeBlacklistId): ?>
            <span class="badge badge-soft-danger ms-2">Currently Blacklisted</span>
          <?php else: ?>
            <span class="badge badge-soft-success ms-2">Not Blacklisted</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="text-end">
        <span class="stat-chip me-1" title="Total records">
          <span class="dot" style="background:#64748b"></span> <?php echo (int)$totalCount; ?>
        </span>
        <span class="stat-chip me-1" title="Active">
          <span class="dot active"></span> <?php echo (int)$activeCount; ?>
        </span>
        <span class="stat-chip" title="Reverted">
          <span class="dot reverted"></span> <?php echo (int)$revertedCount; ?>
        </span>
      </div>
    </div>
  </div>
</div>

<!-- Filters -->
<div class="history-toolbar mb-3">
  <div class="filter-wrap">
    <div class="input-group input-group-sm" style="max-width: 360px;">
      <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
      <input id="historySearch" type="text" class="form-control form-control-sm form-rounded"
             placeholder="Search reason, issue, user, date...">
    </div>

    <div>
      <select id="statusFilter" class="form-select form-select-sm form-rounded">
        <option value="all" selected>Show: All</option>
        <option value="active">Active only</option>
        <option value="reverted">Reverted only</option>
      </select>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <?php if (empty($history)): ?>
      <div class="text-center text-muted py-4">No blacklist history recorded for this applicant.</div>
    <?php else: ?>
      <ul class="timeline mb-0" id="historyTimeline">
        <?php foreach ($history as $rec): ?>
          <?php
            $isActive       = (int)($rec['is_active'] ?? 0) === 1;
            $statusKey      = $isActive ? 'active' : 'reverted';
            $createdByLabel = $rec['created_by_name'] ?: ($rec['created_by_username'] ?: 'System');
            $createdAt      = formatDateTime($rec['created_at'] ?? '');
            $reason         = (string)($rec['reason'] ?? '');
            $issue          = (string)($rec['issue'] ?? '');
            $proofs         = jsonToListPaths($rec['proof_paths'] ?? null);

            $revertedBy     = $rec['reverted_by_name'] ?: ($rec['reverted_by_username'] ?: '');
            $revertedAt     = !empty($rec['reverted_at']) ? formatDateTime($rec['reverted_at']) : '';
            $compNote       = (string)($rec['compliance_note'] ?? '');
            $compProofs     = jsonToListPaths($rec['compliance_proof_paths'] ?? null);

            $searchBlob = strtolower(trim(
              $createdAt.' '.$reason.' '.$issue.' '.$createdByLabel.' '.$revertedBy.' '.$revertedAt
            ));
            $rowId = (int)$rec['id'];
          ?>
          <li class="timeline-item history-item"
              data-status="<?php echo $statusKey; ?>"
              data-search="<?php echo htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="timeline-card">
              <div class="card-header">
                <div class="summary">
                  <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i><?php echo htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8'); ?></span>
                  <?php if ($isActive): ?>
                    <span class="badge badge-soft-danger">Active</span>
                  <?php else: ?>
                    <span class="badge badge-soft-success">Reverted</span>
                  <?php endif; ?>
                  <span class="fw-semibold text-danger text-truncate" style="max-width: 46ch;" title="<?php echo htmlspecialchars($reason, ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-flag-fill me-1"></i><?php echo htmlspecialchars($reason, ENT_QUOTES, 'UTF-8'); ?>
                  </span>
                  <span class="text-muted small ms-auto">
                    <i class="bi bi-person-badge me-1"></i>Logged by:
                    <span class="fw-semibold"><?php echo htmlspecialchars($createdByLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                  </span>
                </div>
              </div>

              <div class="accordion accordion-flush" id="recAcc<?php echo $rowId; ?>">
                <div class="accordion-item">
                  <h2 class="accordion-header" id="head<?php echo $rowId; ?>">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#col<?php echo $rowId; ?>" aria-expanded="false"
                            aria-controls="col<?php echo $rowId; ?>">
                      View details
                    </button>
                  </h2>
                  <div id="col<?php echo $rowId; ?>" class="accordion-collapse collapse" aria-labelledby="head<?php echo $rowId; ?>">
                    <div class="accordion-body">

                      <?php if ($issue !== ''): ?>
                        <div class="mb-3">
                          <div class="small text-muted mb-1">Issue / Details</div>
                          <div><?php echo nl2br(htmlspecialchars($issue, ENT_QUOTES, 'UTF-8')); ?></div>
                        </div>
                      <?php endif; ?>

                      <div class="mb-3">
                        <div class="small text-muted mb-1">Original Proofs</div>
                        <?php if (empty($proofs)): ?>
                          <div class="text-muted small">None</div>
                        <?php else: ?>
                          <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($proofs as $i => $p): $url = getFileUrl($p); ?>
                              <a class="file-pill" target="_blank" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="bi bi-paperclip"></i> Proof <?php echo $i + 1; ?>
                              </a>
                            <?php endforeach; ?>
                          </div>
                        <?php endif; ?>
                      </div>

                      <div class="meta mb-2">
                        <div class="item"><i class="bi bi-clock-history"></i><span><?php echo htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8'); ?></span></div>
                        <div class="item"><i class="bi bi-person-badge"></i><span><?php echo htmlspecialchars($createdByLabel, ENT_QUOTES, 'UTF-8'); ?></span></div>
                      </div>

                      <?php if (!$isActive): ?>
                        <hr>
                        <div class="row g-3">
                          <div class="col-md-6">
                            <div class="small text-muted mb-1">Reverted by</div>
                            <div class="fw-semibold"><?php echo htmlspecialchars($revertedBy !== '' ? $revertedBy : '—', ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="text-muted small"><?php echo $revertedAt !== '' ? htmlspecialchars($revertedAt, ENT_QUOTES, 'UTF-8') : '—'; ?></div>
                          </div>
                          <div class="col-md-6">
                            <div class="small text-muted mb-1">Compliance Note</div>
                            <div><?php echo $compNote !== '' ? nl2br(htmlspecialchars($compNote, ENT_QUOTES, 'UTF-8')) : '<span class="text-muted">—</span>'; ?></div>
                          </div>
                        </div>

                        <div class="mt-3">
                          <div class="small text-muted mb-1">Compliance Proofs</div>
                          <?php if (empty($compProofs)): ?>
                            <div class="text-muted small">None</div>
                          <?php else: ?>
                            <div class="d-flex flex-wrap gap-2">
                              <?php foreach ($compProofs as $i => $p): $url = getFileUrl($p); ?>
                                <a class="file-pill" target="_blank" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">
                                  <i class="bi bi-file-earmark-check"></i> File <?php echo $i + 1; ?>
                                </a>
                              <?php endforeach; ?>
                            </div>
                          <?php endif; ?>
                        </div>
                      <?php endif; ?>

                    </div>
                  </div>
                </div>
              </div>

            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<script>
(function(){
  const searchInput = document.getElementById('historySearch');
  const statusSelect = document.getElementById('statusFilter');
  const items = Array.from(document.querySelectorAll('.history-item'));

  function applyFilters(){
    const q = (searchInput?.value || '').toLowerCase().trim();
    const status = statusSelect?.value || 'all';

    items.forEach(li => {
      const s = li.getAttribute('data-status') || '';
      const blob = li.getAttribute('data-search') || '';
      const matchStatus = status === 'all' ? true : (s === status);
      const matchText = q === '' ? true : blob.includes(q);
      li.style.display = (matchStatus && matchText) ? '' : 'none';
    });
  }

  searchInput?.addEventListener('input', applyFilters);
  statusSelect?.addEventListener('change', applyFilters);
})();
</script>

<?php require_once '../includes/footer.php'; ?>