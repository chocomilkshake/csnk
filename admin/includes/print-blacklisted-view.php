<?php
// FILE: includes/print-blacklisted-view.php
// Purpose: Print-friendly view of an applicant's entire blacklist history.

declare(strict_types=1);

require_once __DIR__ . '/header.php';
require_once __DIR__ . '/Applicant.php';

// Ensure session for consistency (header.php usually starts it)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Resolve role (from header.php globals)
$role         = $currentUser['role'] ?? 'employee';
$isSuperAdmin = ($role === 'super_admin');
$isAdmin      = ($role === 'admin');

if (!($isAdmin || $isSuperAdmin)) {
    setFlashMessage('error', 'You do not have permission to print applicant blacklist history.');
    redirect('../pages/dashboard.php');
    exit;
}

// Validate applicant id
if (!isset($_GET['id'])) {
    setFlashMessage('error', 'Applicant ID is required.');
    redirect('../pages/applicants.php');
    exit;
}
$applicantId = (int)$_GET['id'];
if ($applicantId <= 0) {
    setFlashMessage('error', 'Invalid applicant ID.');
    redirect('../pages/applicants.php');
    exit;
}

/* ----------------- Local safe helpers ----------------- */
if (!function_exists('h')) {
    function h(string $str): string { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('formatDateTimeSafe')) {
    function formatDateTimeSafe(?string $dt): string {
        if (function_exists('formatDateTime')) {
            return formatDateTime((string)$dt);
        }
        if (!$dt) return '';
        $ts = strtotime($dt);
        return $ts ? date('M d, Y h:i A', $ts) : (string)$dt;
    }
}
if (!function_exists('getFullNameSafe')) {
    function getFullNameSafe(string $fn, string $mn, string $ln, string $sx): string {
        if (function_exists('getFullName')) return getFullName($fn, $mn, $ln, $sx);
        $name = trim($fn . ' ' . ($mn !== '' ? ($mn . ' ') : '') . $ln);
        if ($sx !== '') $name .= ', ' . $sx;
        return $name;
    }
}
if (!function_exists('getFileUrlSafe')) {
    function getFileUrlSafe(string $path): string {
        if (function_exists('getFileUrl')) return getFileUrl($path);
        return $path;
    }
}
function jsonToListPaths(?string $json): array {
    if ($json === null || trim($json) === '') return [];
    $arr = json_decode($json, true);
    if (!is_array($arr)) return [];
    return array_values(array_filter(array_map('strval', $arr)));
}
function looksLikeImage(string $url): bool {
    $qpos = strpos($url, '?');
    if ($qpos !== false) $url = substr($url, 0, $qpos);
    $url = strtolower($url);
    return (bool)preg_match('/\.(png|jpe?g|gif|bmp|webp|svg)$/i', $url);
}

/* ----------------- Applicant basics ----------------- */
$applicant = new Applicant($database);
$applicantData = $applicant->getById($applicantId);
if (!$applicantData) {
    setFlashMessage('error', 'Applicant not found.');
    redirect('../pages/applicants.php');
    exit;
}

$fullName = getFullNameSafe(
    (string)($applicantData['first_name'] ?? ''),
    (string)($applicantData['middle_name'] ?? ''),
    (string)($applicantData['last_name'] ?? ''),
    (string)($applicantData['suffix'] ?? '')
);

$pictureUrl = !empty($applicantData['picture'])
    ? (function_exists('getFileUrl') ? getFileUrl($applicantData['picture']) : $applicantData['picture'])
    : null;

/* ----------------- Active status ----------------- */
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

/* ----------------- History ----------------- */
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

// Counts
$totalCount    = count($history);
$activeCount   = 0;
$revertedCount = 0;
foreach ($history as $h) {
    if ((int)($h['is_active'] ?? 0) === 1) $activeCount++; else $revertedCount++;
}

$nowLabel = date('M d, Y h:i A');
$backHref = '../pages/view-applicant-history.php?id=' . (int)$applicantId;
?>
<style>
/* ---------- PRINT LAYOUT ---------- */
@media print {
  body * { visibility: hidden !important; }
  #printArea, #printArea * { visibility: visible !important; }
  #printArea { position: absolute; left: 0; top: 0; width: 100%; margin: 0 !important; padding: 0 !important; }
  .screen-toolbar { display: none !important; }
  a[href]:after { content: ""; } /* do not append (url) after links */
}

/* ---------- SCREEN LAYOUT ---------- */
.screen-toolbar {
  position: sticky; top: 0; z-index: 30; background: #fff; border-bottom: 1px solid #eef2f7;
  display: flex; gap: .5rem; justify-content: flex-end; padding: .5rem;
}
.screen-toolbar .btn {
  border-radius: 999px; padding: .25rem .75rem; font-weight: 600; border: 1px solid #e5e7eb; background: #f8fafc;
}
.screen-toolbar .btn-primary { background: #0d6efd; color: #fff; border-color: #0d6efd; }

.print-wrap {
  max-width: 1000px; margin: 1rem auto; background: #fff; border: 1px solid #eef2f7; border-radius: .75rem;
  box-shadow: 0 1px 2px rgba(0,0,0,.03);
}
.print-header { padding: 1rem 1.25rem; border-bottom: 1px solid #eef2f7; }
.print-header h2 { margin: 0 0 .25rem 0; font-weight: 700; }
.print-header .muted { color: #64748b; font-size: .9rem; }
.print-body { padding: 1.25rem; }

.chips { display: flex; flex-wrap: wrap; gap: .4rem; }
.chip {
  display: inline-flex; gap: .4rem; align-items: center; font-weight: 600; font-size: .85rem;
  border: 1px solid #e5e7eb; background: #f9fafb; padding: .25rem .5rem; border-radius: 999px;
}
.dot { width: .5rem; height: .5rem; border-radius: 999px; display: inline-block; }
.dot.all { background: #64748b; }
.dot.active { background: #dc3545; }
.dot.reverted { background: #198754; }

.applicant-row { display: flex; gap: 1rem; align-items: center; margin-bottom: 1rem; }
.applicant-photo {
  width: 72px; height: 72px; border-radius: 50%; overflow: hidden; background: #f1f5f9; display: flex;
  align-items: center; justify-content: center; font-weight: 800; color: #334155; font-size: 1.25rem;
}
.applicant-photo img { width: 100%; height: 100%; object-fit: cover; }
.badge {
  display: inline-block; padding: .25rem .5rem; border-radius: .5rem; font-weight: 700; font-size: .75rem;
}
.badge-danger { background: #fdecec; color: #c1121f; border: 1px solid #f7c9c9; }
.badge-success { background: #e8f7ec; color: #146c43; border: 1px solid #c7ebd2; }

.table-hist {
  width: 100%; border-collapse: separate; border-spacing: 0; border: 1px solid #e5e7eb; border-radius: .5rem; overflow: hidden;
}
.table-hist thead th {
  background: #f8fafc; color: #334155; font-weight: 700; font-size: .9rem; padding: .65rem .6rem; border-bottom: 1px solid #e5e7eb;
}
.table-hist tbody td {
  font-size: .92rem; vertical-align: top; padding: .6rem .6rem; border-bottom: 1px solid #f1f5f9;
}
.table-hist tbody tr:last-child td { border-bottom: 0; }
.small-muted { color: #64748b; font-size: .85rem; }
.reason { color: #b91c1c; font-weight: 700; }
.status-pill { font-weight: 700; font-size: .8rem; border-radius: .5rem; padding: .1rem .4rem; border: 1px solid #e5e7eb; }
.status-active { background: #fdecec; color: #c1121f; border-color: #f7c9c9; }
.status-reverted { background: #e8f7ec; color: #146c43; border-color: #c7ebd2; }
.proof-list { margin: 0; padding-left: 1.1rem; }
.proof-list li { margin: .25rem 0; word-break: break-all; }
.break-words { word-break: break-word; overflow-wrap: anywhere; }

.proof-thumb { display: block; width: 120px; height: 80px; object-fit: cover; border: 1px solid #e5e7eb; border-radius: .25rem; margin-bottom: .25rem; }

@media print {
  tr { page-break-inside: avoid; }
}
</style>

<div class="screen-toolbar">
  <a href="<?php echo h($backHref); ?>" class="btn">← Back</a>
  <button class="btn btn-primary" onclick="window.print()">🖨️ Print</button>
</div>

<div id="printArea" class="print-wrap">
  <div class="print-header">
    <h2>Applicant Blacklist History</h2>
    <div class="muted">Generated: <?php echo h($nowLabel); ?></div>
  </div>

  <div class="print-body">
    <!-- Applicant Summary -->
    <div class="applicant-row">
      <div class="applicant-photo">
        <?php if (!empty($pictureUrl)): ?>
          <img src="<?php echo h($pictureUrl); ?>" alt="Applicant Photo">
        <?php else: ?>
          <?php
            $fi = strtoupper(substr((string)($applicantData['first_name'] ?? ''), 0, 1));
            echo h($fi !== '' ? $fi : '?');
          ?>
        <?php endif; ?>
      </div>
      <div style="flex:1">
        <div style="font-weight: 800; font-size: 1.1rem;"><?php echo h($fullName); ?></div>
        <div class="small-muted">
          Applicant ID: <?php echo (int)$applicantId; ?>
          <?php if ($activeBlacklistId): ?>
            <span class="badge badge-danger" style="margin-left:.5rem;">Currently Blacklisted</span>
          <?php else: ?>
            <span class="badge badge-success" style="margin-left:.5rem;">Not Blacklisted</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="chips">
        <span class="chip"><span class="dot all"></span> Total: <?php echo (int)$totalCount; ?></span>
        <span class="chip"><span class="dot active"></span> Active: <?php echo (int)$activeCount; ?></span>
        <span class="chip"><span class="dot reverted"></span> Reverted: <?php echo (int)$revertedCount; ?></span>
      </div>
    </div>

    <?php if (empty($history)): ?>
      <div class="small-muted" style="padding: 1rem 0;">No blacklist history recorded for this applicant.</div>
    <?php else: ?>
      <table class="table-hist">
        <thead>
          <tr>
            <th style="width: 11rem;">Created At</th>
            <th style="width: 7rem;">Status</th>
            <th style="width: 22rem;">Reason &amp; Issue</th>
            <th style="width: 12rem;">Logged By</th>
            <th>Proofs</th>
            <th style="width: 16rem;">Reversion / Compliance</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($history as $rec): ?>
          <?php
            $isActive       = (int)($rec['is_active'] ?? 0) === 1;
            $createdAt      = formatDateTimeSafe($rec['created_at'] ?? '');
            $reason         = (string)($rec['reason'] ?? '');
            $issue          = (string)($rec['issue'] ?? '');
            $proofs         = jsonToListPaths($rec['proof_paths'] ?? null);

            $createdByLabel = $rec['created_by_name'] ?: ($rec['created_by_username'] ?: 'System');

            $revertedBy     = $rec['reverted_by_name'] ?: ($rec['reverted_by_username'] ?: '');
            $revertedAt     = !empty($rec['reverted_at']) ? formatDateTimeSafe($rec['reverted_at']) : '';
            $compNote       = (string)($rec['compliance_note'] ?? '');
            $compProofs     = jsonToListPaths($rec['compliance_proof_paths'] ?? null);
          ?>
          <tr>
            <td>
              <div><?php echo h($createdAt); ?></div>
            </td>
            <td>
              <span class="status-pill <?php echo $isActive ? 'status-active' : 'status-reverted'; ?>">
                <?php echo $isActive ? 'Active' : 'Reverted'; ?>
              </span>
            </td>
            <td class="break-words">
              <?php if ($reason !== ''): ?>
                <div class="reason">Reason: <?php echo nl2br(h($reason)); ?></div>
              <?php endif; ?>
              <?php if ($issue !== ''): ?>
                <div class="small-muted" style="margin-top:.25rem;">Issue: <?php echo nl2br(h($issue)); ?></div>
              <?php endif; ?>
            </td>
            <td>
              <div><?php echo h($createdByLabel); ?></div>
            </td>
            <td class="break-words">
              <?php if (empty($proofs)): ?>
                <span class="small-muted">None</span>
              <?php else: ?>
                <ol class="proof-list">
                  <?php foreach ($proofs as $i => $p):
                    $url = getFileUrlSafe($p);
                    $isImg = looksLikeImage($url);
                  ?>
                  <li>
                    <div><strong>Proof <?php echo $i + 1; ?>:</strong></div>
                    <?php if ($isImg): ?>
                      <img src="<?php echo h($url); ?>" alt="Proof <?php echo $i + 1; ?>" class="proof-thumb">
                    <?php endif; ?>
                    <div class="small-muted"><?php echo h($url); ?></div>
                  </li>
                  <?php endforeach; ?>
                </ol>
              <?php endif; ?>
            </td>
            <td class="break-words">
              <?php if ($isActive): ?>
                <div class="small-muted">—</div>
              <?php else: ?>
                <div><strong>Reverted by:</strong> <?php echo h($revertedBy !== '' ? $revertedBy : '—'); ?></div>
                <div class="small-muted"><?php echo $revertedAt !== '' ? h($revertedAt) : '—'; ?></div>
                <div style="margin-top:.35rem;"><strong>Compliance Note:</strong></div>
                <div class="small-muted"><?php echo $compNote !== '' ? nl2br(h($compNote)) : '—'; ?></div>
                <div style="margin-top:.35rem;"><strong>Compliance Proofs:</strong></div>
                <?php if (empty($compProofs)): ?>
                  <div class="small-muted">None</div>
                <?php else: ?>
                  <ol class="proof-list">
                    <?php foreach ($compProofs as $i => $p):
                      $url = getFileUrlSafe($p);
                      $isImg = looksLikeImage($url);
                    ?>
                    <li>
                      <div><strong>File <?php echo $i + 1; ?>:</strong></div>
                      <?php if ($isImg): ?>
                        <img src="<?php echo h($url); ?>" alt="Compliance File <?php echo $i + 1; ?>" class="proof-thumb">
                      <?php endif; ?>
                      <div class="small-muted"><?php echo h($url); ?></div>
                    </li>
                    <?php endforeach; ?>
                  </ol>
                <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<script>
// Auto-open print dialog after the page has fully rendered
window.addEventListener('load', function(){
  setTimeout(function(){ try { window.print(); } catch(e){} }, 250);
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>