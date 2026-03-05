<?php
// FILE: admin/pages/turkey_on-process.php (SMC - Turkey On Process Applicants)
$pageTitle = 'SMC Manpower Agency Co.';

$ADMIN_ROOT = dirname(__DIR__);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $ADMIN_ROOT . '/includes/config.php';
require_once $ADMIN_ROOT . '/includes/Database.php';
require_once $ADMIN_ROOT . '/includes/Auth.php';
require_once $ADMIN_ROOT . '/includes/functions.php';

$database = new Database();
$auth = new Auth($database);
$auth->requireLogin();

// Check if user has permission to view SMC data
if (!$auth->canSeeSMC()) {
    header('Location: applicants.php');
    exit;
}

$conn = $database->getConnection();

/** -----------------------------------------------------------------
 *  Compute/prepare values that do NOT output HTML
 *  ----------------------------------------------------------------- */
$smcBuId = 0;
if ($conn instanceof mysqli) {
    $sqlFindSMCBu = "SELECT bu.id
                     FROM business_units bu
                     JOIN agencies ag ON ag.id = bu.agency_id
                     WHERE ag.code = 'smc' AND bu.active = 1
                     ORDER BY bu.id ASC
                     LIMIT 1";
    if ($stmt = $conn->prepare($sqlFindSMCBu)) {
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if (!empty($row['id'])) {
            $smcBuId = (int) $row['id'];
            $_SESSION['smc_bu_id'] = $smcBuId;
        }
    }
}

if (empty($_SESSION['current_bu_id'])) {
    header('Location: login.php');
    exit;
}

// Build preserved query string but EXCLUDE action parameters that cause loops
$filterOutKeys = ['page', 'action', 'id', 'to', 'csrf'];
$preserveQS = '';
if (!empty($_GET)) {
    $kept = array_filter(
        $_GET,
        function ($v, $k) use ($filterOutKeys) {
            return !in_array($k, $filterOutKeys, true) && $v !== '' && $v !== null;
        },
        ARRAY_FILTER_USE_BOTH
    );
    if (!empty($kept)) {
        $preserveQS = '&' . http_build_query($kept);
    }
}
$preserveQSWithQuestion = !empty($preserveQS) ? ('?' . ltrim($preserveQS, '&')) : '';

// CSRF token (and we'll require it on actions)
if (empty($_SESSION['csrf_token'])) {
    try { $_SESSION['
// Handle status update action
if (
    isset($_GET['action'], $_GET['id'], $_GET['to'])
    && $_GET['action'] === 'update_status'
) {
    $csrfOk = isset($_GET['csrf']) && hash_equals($csrf, (string)$_GET['csrf']);

    if (!$csrfOk) {
    if (in_array($to, $allowedStatuses, true)) {
        $updated = false;
  
                    $businessUnitId = $currentApp['business_unit_id'];
                }
                $stmtCheck->close();
            }

            if ($stmt = $conn->prepare("UPDATE applicants SET status = ? WHERE id = ?")) {
                $stmt->bind_param("si", $to, $id);
                $updated = $stmt->execute();
                $stmt->close();
            }
                }
            }
        }
$isSuperAdmin = ($currentRole === 'super_admin');
$isAdmin      = ($currentRole === 'admin');
$isEmployee   = ($currentRole ===

$applicants = $applicant->getApplicants($buScope, $countryId, $status, $q, $notDeleted, $notBlacklisted, $page, $pageSize);
$totalApplicants = $applicant->getApplicantsCount($buScope, $countryId, $status, $q, $notDeleted, $notBlacklisted);
$totalPages = ceil($totalApplicants / $pageSize);

$countriesWithCounts = $applicant->getCountriesWithCounts($buScope, $status, $q, $notDeleted, $notBlacklisted);

function renderPreferredLocation(?string $json, int $maxLen = 30): string {
    if (empty($json)) return 'N/A';i> Change Status</button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="changeStatusBtn-<?php echo (int)$app['id']; ?>">
                                                    <li><a class="dropdown-item <?php echo ($app['status'] === 'pending') ? 'disabled' : ''; ?>" href="turkey_on-process.php?action=update_status&id=<?php echo (int)$app['id']; ?>&to=pending<?php echo $preserveQS; ?>&csrf=<?php echo h($csrf); ?>"><i class="bi bi-hourglass-split text-warning"></i><span>Pending</span></a></li>
                                                    <li><a class="dropdown-item <?php echo ($app['status'] === 'on_process') ? 'disabled' : ''; ?>" href="turkey_on-process.php?action=update_status&id=<?php echo (int)$app['id']; ?>&to=on_process<?php echo $preserveQS; ?>&csrf=<?php echo h($csrf); ?>"><i class="bi bi-arrow-repeat text-info"></i><span>On Process</span></a></li>
                                                    <li><a class="dropdown-item <?php echo ($app['status'] === 'approved') ? 'disabled' : ''; ?>" href="turkey_on-process.php?action=update_status&id=<?php echo (int)$app['id']; ?>&to=approved<?php echo $preserveQS; ?>&csrf=<?php echo h($csrf); ?>"><i class="bi bi-check2-circle text-success"></i><span>Approved</span></a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once $ADMIN_ROOT . '/includes/footer.php'; ?>