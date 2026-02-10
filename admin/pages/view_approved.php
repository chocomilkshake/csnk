<?php
// FILE: pages/view_approved.php
$pageTitle = 'View Approved (Applicant + Client)';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

// We assume $database (mysqli), and helpers: redirect, formatDate, getFileUrl, getFullName, setFlashMessage
$applicant = new Applicant($database);

/** Preserve search (if user came from approved.php with ?q=) */
$q = '';
if (isset($_GET['q'])) {
    $q = trim((string)$_GET['q']);
    if (mb_strlen($q) > 200) $q = mb_substr($q, 0, 200);
}

if (!isset($_GET['id'])) {
    $dest = 'approved.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
    redirect($dest);
    exit;
}
$id = (int)$_GET['id'];

/** Load Applicant (ensure not deleted) */
function safe(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

try {
    $stmt = $database->prepare("SELECT * FROM applicants WHERE id = ? AND (status <> 'deleted' OR status IS NULL) LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $applicantData = $res ? $res->fetch_assoc() : false;
} catch (Throwable $e) {
    $applicantData = false;
}

if (!$applicantData) {
    setFlashMessage('error', 'Applicant not found.');
    $dest = 'approved.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
    redirect($dest);
    exit;
}

/** Load Documents (if Applicant class supports it) */
$documents = [];
if (method_exists($applicant, 'getDocuments')) {
    $documents = $applicant->getDocuments($id);
}

/** Load latest booking (most recent) */
$latestBooking = null;
try {
    $stmt = $database->prepare("
        SELECT cb.*
        FROM client_bookings cb
        WHERE cb.applicant_id = ?
        ORDER BY cb.created_at DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $latestBooking = $res ? $res->fetch_assoc() : null;
} catch (Throwable $e) {
    $latestBooking = null;
}

/** Load all bookings (for table) */
$allBookings = [];
try {
    $stmt = $database->prepare("
        SELECT cb.*
        FROM client_bookings cb
        WHERE cb.applicant_id = ?
        ORDER BY cb.created_at DESC
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $allBookings = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
} catch (Throwable $e) {
    $allBookings = [];
}

/* ========= Helpers (renderers; Bootstrap-only utilities) ========= */

function renderBadgesFromJson(?string $json, string $badgeClass = 'bg-light text-primary border', int $max = 0): string {
    if ($json === null || trim($json) === '') return '<span class="text-muted">N/A</span>';
    $arr = json_decode($json, true);
    $items = [];
    if (is_array($arr)) {
        foreach ($arr as $v) if (is_string($v) && trim($v) !== '') $items[] = trim($v);
    } else {
        $fallback = trim($json, " \t\n\r\0\x0B[]\"");
        if ($fallback !== '') foreach (explode(',', $fallback) as $p) if (trim($p) !== '') $items[] = trim($p);
    }
    if (!$items) return '<span class="text-muted">N/A</span>';
    if ($max > 0) $items = array_slice($items, 0, $max);
    $out = [];
    foreach ($items as $label) $out[] = '<span class="badge rounded-pill '.$badgeClass.' fw-semibold">'.safe($label).'</span>';
    return implode(' ', $out);
}

function renderServicesBadges(?string $json): string {
    if ($json === null || trim($json) === '') return '<span class="text-muted">N/A</span>';
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) return '<span class="text-muted">'.safe($json).'</span>';
    $labels = [];
    if (is_array($data)) {
        foreach ($data as $item) {
            if (is_string($item) && trim($item) !== '') $labels[] = trim($item);
            elseif (is_array($item)) {
                foreach (['name','label','service','title'] as $k) {
                    if (!empty($item[$k]) && is_string($item[$k])) { $labels[] = trim($item[$k]); break; }
                }
            }
        }
    }
    if (!$labels) return '<span class="text-muted">N/A</span>';
    $out = [];
    foreach ($labels as $label) $out[] = '<span class="badge rounded-pill bg-light text-dark border fw-semibold">'.safe($label).'</span>';
    return '<div class="d-flex flex-wrap gap-1">'.implode(' ', $out).'</div>';
}

/** Prepared values */
$pictureUrl = !empty($applicantData['picture']) ? getFileUrl($applicantData['picture']) : null;
$fullName   = getFullName($applicantData['first_name'], $applicantData['middle_name'], $applicantData['last_name'], $applicantData['suffix']);
$status     = (string)($applicantData['status'] ?? 'approved');
$badgeColor = ['pending'=>'warning','on_process'=>'info','approved'=>'success','deleted'=>'secondary'][$status] ?? 'secondary';

$primaryPhone = trim((string)($applicantData['phone_number'] ?? ''));
$altPhone     = trim((string)($applicantData['alt_phone_number'] ?? ''));
$email        = (string)($applicantData['email'] ?? 'N/A');

$prefLocBadges = renderBadgesFromJson($applicantData['preferred_location'] ?? '', 'bg-light text-primary border', 8);
$skillsBadges  = renderBadgesFromJson($applicantData['specialization_skills'] ?? '', 'bg-light text-danger border', 10);

$languagesDisplay = (function($json){
    if ($json === null || trim($json) === '') return 'N/A';
    $arr = json_decode($json, true);
    if (!is_array($arr) || !$arr) return 'N/A';
    $clean = array_values(array_filter(array_map('trim', $arr)));
    return $clean ? safe(implode(', ', $clean)) : 'N/A';
})($applicantData['languages'] ?? '');

/** URLs */
$backUrl  = 'approved.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
$editUrl  = 'edit-applicant.php?id=' . $id . ($q !== '' ? ('&q=' . urlencode($q)) : '');
$printUrl = 'print-applicant.php?id=' . $id . ($q !== '' ? ('&q=' . urlencode($q)) : '');
?>

<!-- Header actions -->
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0 fw-semibold">Applicant + Client (Approved)</h4>
  <div class="d-flex gap-2">
    <a href="<?php echo safe($printUrl); ?>" target="_blank" class="btn btn-dark">
      <i class="bi bi-printer me-1"></i> Print / Save as PDF
    </a>
    <a href="<?php echo safe($editUrl); ?>" class="btn btn-warning">
      <i class="bi bi-pencil me-1"></i> Edit Applicant
    </a>
    <a href="<?php echo safe($backUrl); ?>" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i> Back to Approved
    </a>
  </div>
</div>

<!-- Top row: Applicant (left) + Latest Client Booking (right) -->
<div class="row g-3">

  <!-- Applicant Card -->
  <div class="col-xl-6">
    <div class="card">
      <div class="card-header bg-white py-2">
        <div class="d-flex justify-content-between align-items-center">
          <div class="fw-semibold d-flex align-items-center gap-2">
            <i class="bi bi-person-badge"></i> Applicant
          </div>
          <span class="badge bg-<?php echo $badgeColor; ?> rounded-pill">
            <?php echo safe(ucfirst(str_replace('_',' ', $status))); ?>
          </span>
        </div>
      </div>
      <div class="card-body">

        <div class="d-flex align-items-center gap-3 mb-2">
          <?php if (!empty($pictureUrl)): ?>
            <img src="<?php echo safe($pictureUrl); ?>" alt="Photo" class="rounded-circle" style="width:100px;height:100px;object-fit:cover;">
          <?php else: ?>
            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width:100px;height:100px;">
              <span class="fw-bold fs-2"><?php echo strtoupper(substr($applicantData['first_name'], 0, 1)); ?></span>
            </div>
          <?php endif; ?>

          <div class="min-w-0">
            <div class="fw-bold fs-5 text-truncate"><?php echo $fullName; ?></div>
            <div class="text-muted small">Applied: <?php echo safe(formatDate($applicantData['created_at'])); ?></div>
          </div>
        </div>

        <div class="row g-2">
          <div class="col-6">
            <div class="text-muted small">Phone (Primary)</div>
            <div class="fw-semibold"><?php echo $primaryPhone !== '' ? safe($primaryPhone) : 'N/A'; ?></div>
          </div>
          <div class="col-6">
            <div class="text-muted small">Phone (Alternate)</div>
            <div class="fw-semibold"><?php echo $altPhone !== '' ? safe($altPhone) : 'N/A'; ?></div>
          </div>
          <div class="col-6">
            <div class="text-muted small">Email</div>
            <div class="fw-semibold text-truncate"><?php echo safe($email); ?></div>
          </div>
          <div class="col-6">
            <div class="text-muted small">Date of Birth</div>
            <div class="fw-semibold"><?php echo safe(formatDate($applicantData['date_of_birth'])); ?></div>
          </div>
          <div class="col-6">
            <div class="text-muted small">Experience</div>
            <div class="fw-semibold">
              <?php $yrs = (int)($applicantData['years_experience'] ?? 0); echo $yrs . ($yrs === 1 ? ' year' : ' years'); ?>
            </div>
          </div>
          <div class="col-6">
            <div class="text-muted small">Employment</div>
            <div class="fw-semibold"><?php echo !empty($applicantData['employment_type']) ? safe($applicantData['employment_type']) : 'N/A'; ?></div>
          </div>

          <div class="col-12">
            <div class="text-muted small">Address</div>
            <div class="fw-semibold"><?php echo safe($applicantData['address']); ?></div>
          </div>

          <div class="col-12">
            <div class="text-muted small">Preferred Location(s)</div>
            <div class="d-flex flex-wrap gap-1"><?php echo $prefLocBadges; ?></div>
          </div>

          <div class="col-12">
            <div class="text-muted small">Specialization Skills</div>
            <div class="d-flex flex-wrap gap-1"><?php echo $skillsBadges; ?></div>
          </div>

          <div class="col-12">
            <div class="text-muted small">Languages</div>
            <div class="fw-semibold"><?php echo $languagesDisplay; ?></div>
          </div>

          <!-- Educational Attainment -->
          <div class="col-12">
            <div class="text-muted small">Educational Attainment</div>
            <?php
              $eduArr = json_decode($applicantData['educational_attainment'] ?? '', true);
              if (is_array($eduArr)) {
                $labels = ['elementary'=>'Elementary','highschool'=>'High School','senior_high'=>'Senior High','college'=>'College'];
                echo '<ul class="mb-0 ps-3">';
                foreach ($labels as $k=>$label) {
                  if (!empty($eduArr[$k]) && is_array($eduArr[$k])) {
                    $row = $eduArr[$k]; $parts = [];
                    if (!empty($row['school'])) $parts[] = $row['school'];
                    if (!empty($row['strand'])) $parts[] = $row['strand'];
                    if (!empty($row['course'])) $parts[] = $row['course'];
                    if (!empty($row['year']))   $parts[] = $row['year'];
                    if ($parts) echo '<li class="small">'.safe($label).': '.safe(implode(' • ', $parts)).'</li>';
                  }
                }
                echo '</ul>';
              } else { echo '<div class="text-muted">N/A</div>'; }
            ?>
          </div>

          <!-- Work History -->
          <div class="col-12">
            <div class="text-muted small">Work History</div>
            <?php
              $workArr = json_decode($applicantData['work_history'] ?? '', true);
              if (is_array($workArr) && $workArr) {
                echo '<ul class="mb-0 ps-3">';
                foreach ($workArr as $w) {
                  if (!is_array($w)) continue;
                  $parts = [];
                  if (!empty($w['company']))  $parts[] = $w['company'];
                  if (!empty($w['role']))     $parts[] = $w['role'];
                  if (!empty($w['years']))    $parts[] = $w['years'];
                  if (!empty($w['location'])) $parts[] = $w['location'];
                  if ($parts) echo '<li class="small">'.safe(implode(' — ', $parts)).'</li>';
                }
                echo '</ul>';
              } else { echo '<div class="text-muted">N/A</div>'; }
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Latest Client Booking -->
  <div class="col-xl-6">
    <div class="card">
      <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <div class="fw-semibold d-flex align-items-center gap-2">
          <i class="bi bi-people"></i> Latest Client Booking
        </div>
        <?php if ($latestBooking): ?>
          <?php $bColor = ['submitted'=>'secondary','confirmed'=>'success','cancelled'=>'danger'][(string)$latestBooking['status']] ?? 'secondary'; ?>
          <span class="badge bg-<?php echo $bColor; ?> text-uppercase"><?php echo safe($latestBooking['status']); ?></span>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if (!$latestBooking): ?>
          <p class="text-muted mb-0">No client booking found for this applicant.</p>
        <?php else: ?>
          <?php
            $clientName = trim(($latestBooking['client_first_name'] ?? '') . ' ' . ($latestBooking['client_middle_name'] ?? '') . ' ' . ($latestBooking['client_last_name'] ?? ''));
            if ($clientName === '') $clientName = '—';
          ?>
          <div class="mb-2">
            <div class="text-muted small">Client</div>
            <div class="fw-semibold"><?php echo safe($clientName); ?></div>
          </div>

          <div class="row g-2">
            <div class="col-6">
              <div class="text-muted small">Client Email</div>
              <div class="fw-semibold text-truncate"><?php echo safe($latestBooking['client_email'] ?? '—'); ?></div>
            </div>
            <div class="col-6">
              <div class="text-muted small">Client Phone</div>
              <div class="fw-semibold"><?php echo safe($latestBooking['client_phone'] ?? '—'); ?></div>
            </div>
            <div class="col-6">
              <div class="text-muted small">Appointment</div>
              <div class="fw-semibold"><?php echo safe($latestBooking['appointment_type'] ?? '—'); ?></div>
            </div>
            <div class="col-6">
              <div class="text-muted small">Date &amp; Time</div>
              <div class="fw-semibold">
                <?php
                  $d = !empty($latestBooking['appointment_date']) ? formatDate($latestBooking['appointment_date']) : '—';
                  $t = !empty($latestBooking['appointment_time']) ? $latestBooking['appointment_time'] : '';
                  echo safe(trim($d . ' ' . $t));
                ?>
              </div>
            </div>
          </div>

          <div class="mt-2">
            <div class="text-muted small">Client Address</div>
            <div class="fw-semibold"><?php echo safe($latestBooking['client_address'] ?? '—'); ?></div>
          </div>

          <div class="mt-2">
            <div class="text-muted small">Services</div>
            <?php echo renderServicesBadges($latestBooking['services_json'] ?? null); ?>
          </div>

          <div class="row g-2 mt-2">
            <div class="col-6">
              <div class="text-muted small">Created</div>
              <div class="fw-semibold"><?php echo !empty($latestBooking['created_at']) ? safe(formatDate($latestBooking['created_at'])) : '—'; ?></div>
            </div>
            <div class="col-6">
              <div class="text-muted small">Updated</div>
              <div class="fw-semibold"><?php echo !empty($latestBooking['updated_at']) ? safe(formatDate($latestBooking['updated_at'])) : '—'; ?></div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div><!-- /row -->

<!-- ACCORDIONS -->
<div class="accordion mt-3" id="extraInfoAccordion">

  <!-- All Bookings (with Email / Call actions) -->
  <div class="accordion-item">
    <h2 class="accordion-header" id="headingBookings">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBookings" aria-expanded="false" aria-controls="collapseBookings">
        All Client Bookings (Complete)
      </button>
    </h2>
    <div id="collapseBookings" class="accordion-collapse collapse" aria-labelledby="headingBookings" data-bs-parent="#extraInfoAccordion">
      <div class="accordion-body">
        <?php if (empty($allBookings)): ?>
          <p class="text-muted mb-0">No bookings yet.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Client</th>
                  <th>Contacts</th>
                  <th>Appointment</th>
                  <th>Date &amp; Time</th>
                  <th>Status</th>
                  <th class="text-center">Actions</th>
                  <th>Created</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($allBookings as $i => $b): ?>
                  <?php
                    $cName  = trim(($b['client_first_name'] ?? '') . ' ' . ($b['client_middle_name'] ?? '') . ' ' . ($b['client_last_name'] ?? ''));
                    if ($cName === '') $cName = '—';
                    $status = (string)($b['status'] ?? 'submitted');
                    $badge  = ['submitted'=>'secondary','confirmed'=>'success','cancelled'=>'danger'][$status] ?? 'secondary';
                    $cid    = isset($b['id']) ? (int)$b['id'] : $i;
                    $emailB = trim((string)($b['client_email'] ?? ''));
                    $phoneB = trim((string)($b['client_phone'] ?? ''));
                    $subject = rawurlencode('Regarding your appointment');
                    $body    = rawurlencode("Hello $cName,\n\nFollowing up regarding your appointment.\n\nThank you,");
                    $mailto  = 'mailto:'.rawurlencode($emailB).'?subject='.$subject.'&body='.$body;
                    $modalId = 'contactModal'.$cid;
                  ?>
                  <tr>
                    <td><?php echo (int)($i + 1); ?></td>
                    <td class="fw-semibold"><?php echo safe($cName); ?></td>
                    <td>
                      <div><?php echo safe($emailB !== '' ? $emailB : '—'); ?></div>
                      <div class="text-muted small"><?php echo safe($phoneB !== '' ? $phoneB : '—'); ?></div>
                    </td>
                    <td><?php echo safe($b['appointment_type'] ?? '—'); ?></td>
                    <td>
                      <?php
                        $d = !empty($b['appointment_date']) ? formatDate($b['appointment_date']) : '—';
                        $t = !empty($b['appointment_time']) ? $b['appointment_time'] : '';
                        echo safe(trim($d . ' ' . $t));
                      ?>
                    </td>
                    <td><span class="badge bg-<?php echo $badge; ?>"><?php echo safe($status); ?></span></td>
                    <td class="text-center">
                      <div class="btn-group">
                        <a href="<?php echo safe($mailto); ?>" class="btn btn-sm btn-outline-primary" title="Email Client">
                          <i class="bi bi-envelope"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-success" title="Show Contact" data-bs-toggle="modal" data-bs-target="#<?php echo safe($modalId); ?>">
                          <i class="bi bi-telephone"></i>
                        </button>
                      </div>
                    </td>
                    <td><?php echo !empty($b['created_at']) ? formatDate($b['created_at']) : '—'; ?></td>
                  </tr>

                  <!-- Contact Modal -->
                  <div class="modal fade" id="<?php echo safe($modalId); ?>" tabindex="-1" aria-labelledby="<?php echo safe($modalId); ?>Label" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="<?php echo safe($modalId); ?>Label">
                            <i class="bi bi-person-lines-fill me-2"></i>Client Contact
                          </h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <div class="mb-3">
                            <div class="text-muted small">Client</div>
                            <div class="fw-semibold"><?php echo safe($cName); ?></div>
                          </div>
                          <div class="row g-3">
                            <div class="col-md-6">
                              <div class="text-muted small">Email</div>
                              <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-envelope text-muted"></i>
                                <?php if ($emailB !== ''): ?>
                                  <a href="mailto:<?php echo safe($emailB); ?>" class="value-sm text-decoration-none"><?php echo safe($emailB); ?></a>
                                <?php else: ?>
                                  <span class="text-muted">N/A</span>
                                <?php endif; ?>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="text-muted small">Phone</div>
                              <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-telephone text-muted"></i>
                                <?php if ($phoneB !== ''): ?>
                                  <a href="tel:<?php echo safe($phoneB); ?>" class="value-sm text-decoration-none"><?php echo safe($phoneB); ?></a>
                                <?php else: ?>
                                  <span class="text-muted">N/A</span>
                                <?php endif; ?>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <?php if ($emailB !== ''): ?>
                            <a href="<?php echo safe($mailto); ?>" class="btn btn-primary">
                              <i class="bi bi-envelope me-1"></i>Email
                            </a>
                          <?php endif; ?>
                          <?php if ($phoneB !== ''): ?>
                            <a href="tel:<?php echo safe($phoneB); ?>" class="btn btn-success">
                              <i class="bi bi-telephone me-1"></i>Call
                            </a>
                          <?php endif; ?>
                          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
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

  <!-- Documents -->
  <div class="accordion-item">
    <h2 class="accordion-header" id="headingDocs">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDocs" aria-expanded="false" aria-controls="collapseDocs">
        Applicant Documents
      </button>
    </h2>
    <div id="collapseDocs" class="accordion-collapse collapse" aria-labelledby="headingDocs" data-bs-parent="#extraInfoAccordion">
      <div class="accordion-body">
        <?php if (empty($documents)): ?>
          <p class="text-muted mb-0">No documents uploaded yet.</p>
        <?php else: ?>
          <div class="list-group">
            <?php foreach ($documents as $doc): ?>
              <div class="list-group-item d-flex justify-content-between align-items-center">
                <span>
                  <i class="bi bi-file-earmark-text me-2"></i>
                  <?php echo ucfirst(str_replace('_', ' ', (string)$doc['document_type'])); ?>
                </span>
                <?php if (!empty($doc['file_path'])): ?>
                  <a href="<?php echo safe(getFileUrl($doc['file_path'])); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye me-1"></i>View
                  </a>
                <?php else: ?>
                  <span class="text-muted small">N/A</span>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div><!-- /accordion -->

<?php require_once '../includes/footer.php'; ?>