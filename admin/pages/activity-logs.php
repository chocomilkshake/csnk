<?php
$pageTitle = 'Activity Logs';
require_once '../includes/header.php';

// Role-based access: only admin and super admin can view activity logs
$role          = $currentUser['role'] ?? 'employee';
$isSuperAdmin  = ($role === 'super_admin');
$isAdmin       = ($role === 'admin');
$isEmployee    = ($role === 'employee');

if ($isEmployee) {
    setFlashMessage('error', 'You do not have permission to view activity logs.');
    redirect('dashboard.php');
    exit;
}

$conn = $database->getConnection();

/* -----------------------------------------------------------
   Fetch admin/users list for filter dropdown
   - Admin: hide super_admin users
   - Super Admin: show all
   ----------------------------------------------------------- */
$adminUsers = [];
if ($conn instanceof mysqli) {
    $sqlUsers = "
        SELECT id, username, full_name, role
        FROM admin_users
    ";
    if ($isAdmin) {
        $sqlUsers .= " WHERE role <> 'super_admin' ";
    }
    $sqlUsers .= " ORDER BY role DESC, full_name ASC";
    if ($resUsers = $conn->query($sqlUsers)) {
        $adminUsers = $resUsers->fetch_all(MYSQLI_ASSOC);
    }
}

/* -----------------------------------------------------------
   Fetch recent activity logs (server-side limited for performance)
   - Admin: exclude entries created by super_admins
   - ALSO: remove "Unknown user" by using INNER JOIN (no null user rows)
   ----------------------------------------------------------- */
$logs = [];
if ($conn instanceof mysqli) {
    $sqlLogs = "
        SELECT
            al.id,
            al.admin_id,
            al.action,
            al.description,
            al.created_at,
            au.full_name,
            au.username,
            au.role
        FROM activity_logs AS al
        INNER JOIN admin_users AS au ON al.admin_id = au.id
    ";
    if ($isAdmin) {
        $sqlLogs .= " WHERE COALESCE(au.role, '') <> 'super_admin' ";
    }
    $sqlLogs .= " ORDER BY al.created_at DESC LIMIT 250";

    if ($resLogs = $conn->query($sqlLogs)) {
        $logs = $resLogs->fetch_all(MYSQLI_ASSOC);
    }
}

/* -----------------------------------------------------------
   Enrichment: replace bare IDs inside descriptions with names
   Goal: turn "Applicant ID 9" into "Applicant ID 9 — Full Name"
   - We scan all descriptions to collect candidate Applicant/User/Admin IDs
   - Then fetch names and inject them (non-destructive)
   ----------------------------------------------------------- */

/** Collect candidate IDs from all descriptions (best-effort regex). */
$candidateApplicantIds = [];
$candidateUserIds      = [];  // admin_users IDs (admins/employees)
foreach ($logs as $lg) {
    $desc = (string)($lg['description'] ?? '');

    // Applicant ID patterns (look for word 'applicant' near 'ID')
    if (preg_match_all('/\b(Applicant|applicant)[^0-9]{0,30}\bID\b[:\s#]*([0-9]+)\b/', $desc, $m, PREG_SET_ORDER)) {
        foreach ($m as $hit) {
            $aid = (int)$hit[2];
            if ($aid > 0) $candidateApplicantIds[$aid] = true;
        }
    }

    // Admin/User ID patterns
    if (preg_match_all('/\b(Admin|User|admin|user)[^0-9]{0,30}\bID\b[:\s#]*([0-9]+)\b/', $desc, $m2, PREG_SET_ORDER)) {
        foreach ($m2 as $hit) {
            $uid = (int)$hit[2];
            if ($uid > 0) $candidateUserIds[$uid] = true;
        }
    }
}

/** Fetch maps */
$applicantNameMap = []; // id => "Full Name"
$userNameMap      = []; // id => "Full Name or Username"
if ($conn instanceof mysqli) {
    if (!empty($candidateApplicantIds)) {
        $ids = implode(',', array_map('intval', array_keys($candidateApplicantIds)));
        $sqlA = "SELECT id, first_name, middle_name, last_name, suffix FROM applicants WHERE id IN ($ids)";
        if ($resA = $conn->query($sqlA)) {
            while ($row = $resA->fetch_assoc()) {
                $full = getFullName(
                    $row['first_name'] ?? '',
                    $row['middle_name'] ?? '',
                    $row['last_name'] ?? '',
                    $row['suffix'] ?? ''
                );
                $applicantNameMap[(int)$row['id']] = $full ?: ('Applicant #' . (int)$row['id']);
            }
        }
    }
    if (!empty($candidateUserIds)) {
        $idsU = implode(',', array_map('intval', array_keys($candidateUserIds)));
        $sqlU = "SELECT id, full_name, username FROM admin_users WHERE id IN ($idsU)";
        if ($resU = $conn->query($sqlU)) {
            while ($row = $resU->fetch_assoc()) {
                $name = trim($row['full_name'] ?? '');
                if ($name === '') $name = $row['username'] ?? ('User #' . (int)$row['id']);
                $userNameMap[(int)$row['id']] = $name;
            }
        }
    }
}

/** Enrich a single description by injecting names after IDs (non-destructive). */
function enrichDescription(string $desc, array $appMap, array $userMap): string
{
    // Enrich Applicant IDs
    $desc = preg_replace_callback(
        '/\b(Applicant|applicant)([^0-9]{0,30}\bID\b[:\s#]*)([0-9]+)\b/',
        function ($m) use ($appMap) {
            $id = (int)$m[3];
            $name = $appMap[$id] ?? null;
            if ($name) return $m[1] . $m[2] . $id . ' — ' . $name;
            return $m[0];
        },
        $desc
    );

    // Enrich Admin/User IDs
    $desc = preg_replace_callback(
        '/\b(Admin|User|admin|user)([^0-9]{0,30}\bID\b[:\s#]*)([0-9]+)\b/',
        function ($m) use ($userMap) {
            $id = (int)$m[3];
            $name = $userMap[$id] ?? null;
            if ($name) return $m[1] . $m[2] . $id . ' — ' . $name;
            return $m[0];
        },
        $desc
    );

    return $desc;
}
?>
<style>
    .card-activity {
        border-radius: 1rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        transition: box-shadow 0.3s ease;
    }
    .card-activity:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    .table-activity { margin-bottom: 0; }
    .table-activity thead th {
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #6b7280;
        border-bottom: 2px solid #e5e7eb;
        background: linear-gradient(180deg, #f9fafb 0%, #ffffff 100%);
        font-weight: 700;
        padding: 1rem 1.25rem;
    }
    .table-activity tbody tr {
        transition: all 0.2s ease;
        border-bottom: 1px solid #f3f4f6;
    }
    .table-activity tbody tr:nth-child(even) { background-color: #fafbfc; }
    .table-activity tbody tr:hover {
        background-color: #f0f4ff;
        transform: translateX(2px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    .table-activity tbody td { padding: 1rem 1.25rem; vertical-align: middle; }
    .activity-chip {
        border-radius: 999px;
        padding: .35rem .85rem;
        font-size: .75rem;
        font-weight: 600;
        background: linear-gradient(135deg, rgba(37,99,235,.1) 0%, rgba(37,99,235,.08) 100%);
        color: #1d4ed8;
        border: 1px solid rgba(37,99,235,.2);
        display: inline-block;
        transition: all 0.2s ease;
    }
    .activity-chip:hover {
        background: linear-gradient(135deg, rgba(37,99,235,.15) 0%, rgba(37,99,235,.12) 100%);
        transform: scale(1.05);
    }
    .activity-user { display: flex; flex-direction: column; gap: .25rem; }
    .activity-user-main { font-weight: 600; color: #1f2937; font-size: .9rem; }
    .activity-user-sub { font-size: .75rem; color: #9ca3af; }
    .pill-range { display: flex; gap: .5rem; flex-wrap: wrap; }
    .pill-range .btn {
        border-radius: 999px !important;
        padding: .4rem .9rem;
        font-weight: 500;
        font-size: .8rem;
        transition: all 0.2s ease;
        border: 1.5px solid #d1d5db;
    }
    .pill-range .btn:hover { transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
    .pill-range .btn.active {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #ffffff;
        border-color: #2563eb;
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3);
    }
    .badge-role {
        padding: .35rem .7rem;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 600;
        text-transform: capitalize;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1 fw-semibold">Activity Logs</h5>
        <small class="text-muted">
            Audit trail of logins, account changes, applicant updates, exports and other key actions.
        </small>
    </div>
    <span class="badge bg-light text-dark">
        Showing <?php echo count($logs); ?> recent entries
    </span>
</div>

<div class="card mb-3 card-activity">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Filter by user</label>
                <select id="filterUser" class="form-select form-select-sm">
                    <option value="">All users</option>
                    <?php foreach ($adminUsers as $user): ?>
                        <?php
                        $name  = $user['full_name'] ?: $user['username'];
                        $label = trim($name . ' (' . $user['role'] . ')');
                        ?>
                        <option
                            value="<?php echo (int)$user['id']; ?>"
                            data-name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Search (action, description, user)</label>
                <input
                    type="text"
                    id="filterSearch"
                    class="form-control form-control-sm"
                    placeholder="e.g. Delete applicant, export, login…"
                >
            </div>

            <div class="col-md-4 text-md-end">
                <label class="form-label small text-muted mb-1 d-block">Quick range</label>
                <div class="btn-group btn-group-sm pill-range" role="group" aria-label="Quick time range">
                    <button type="button" class="btn btn-outline-secondary active" data-range="all">All</button>
                    <button type="button" class="btn btn-outline-secondary" data-range="24h">24h</button>
                    <button type="button" class="btn btn-outline-secondary" data-range="3d">3d</button>
                    <button type="button" class="btn btn-outline-secondary" data-range="7d">7d</button>
                    <button type="button" class="btn btn-outline-secondary" data-range="1month">1month</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card card-activity">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-activity" id="activityTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 22%;">User</th>
                        <th style="width: 12%;">Role</th>
                        <th style="width: 16%;">Action</th>
                        <th>Description</th>
                        <th style="width: 20%;">When</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No activity has been recorded yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <?php
                            $displayName = $log['full_name'] ?: ($log['username'] ?: 'Unknown user');
                            $roleLabel   = $log['role'] ?: '—';
                            $when        = formatDateTime($log['created_at']);
                            $rawDesc     = (string)($log['description'] ?? '');
                            $enriched    = enrichDescription($rawDesc, $applicantNameMap, $userNameMap);

                            // Safety: skip any row that somehow still has unknown (shouldn't happen with INNER JOIN)
                            if (strcasecmp($displayName, 'Unknown user') === 0) {
                                continue;
                            }
                            ?>
                            <tr
                                data-user-id="<?php echo (int)($log['admin_id'] ?? 0); ?>"
                                data-created-at="<?php echo htmlspecialchars($log['created_at'], ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                <td>
                                    <div class="activity-user">
                                        <span class="activity-user-main"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="activity-user-sub">
                                            <?php echo htmlspecialchars($log['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($roleLabel !== '—'): ?>
                                        <span class="badge-role bg-light text-dark">
                                            <?php echo htmlspecialchars(str_replace('_', ' ', $roleLabel), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="activity-chip">
                                        <?php echo htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td class="text-truncate" style="max-width: 560px;">
                                    <?php echo htmlspecialchars($enriched, ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td>
                                    <span class="d-block"><?php echo htmlspecialchars($when, ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    const tableBody    = document.querySelector('#activityTable tbody');
    const filterUser   = document.getElementById('filterUser');
    const filterSearch = document.getElementById('filterSearch');
    const rangeButtons = document.querySelectorAll('[data-range]');

    if (!tableBody) return;

    function parseDate(value) {
        // value is MySQL DATETIME (YYYY-MM-DD HH:MM:SS)
        const d = new Date(value.replace(' ', 'T') + 'Z');
        return isNaN(d.getTime()) ? null : d;
    }

    function matchesRange(rowDate, range) {
        if (!rowDate || range === 'all') return true;
        const now = new Date();
        const diffMs = now - rowDate;
        const oneDay = 24 * 60 * 60 * 1000;
        if (range === '24h')    return diffMs <= oneDay;
        if (range === '3d')     return diffMs <= 3 * oneDay;
        if (range === '7d')     return diffMs <= 7 * oneDay;
        if (range === '1month') return diffMs <= 30 * oneDay;
        return true;
    }

    function applyFilters() {
        const userId = filterUser?.value || '';
        const search = (filterSearch?.value || '').toLowerCase().trim();
        const activeRangeBtn = document.querySelector('[data-range].active');
        const range = activeRangeBtn ? activeRangeBtn.getAttribute('data-range') : 'all';

        const rows = tableBody.querySelectorAll('tr');
        rows.forEach(row => {
            const rowUserId = row.getAttribute('data-user-id') || '';
            const createdAt = row.getAttribute('data-created-at') || '';
            const rowDate   = parseDate(createdAt);

            const text = row.textContent.toLowerCase();
            const matchesUser = !userId || userId === rowUserId;
            const matchesText = !search || text.includes(search);
            const matchesTime = matchesRange(rowDate, range);

            const visible = matchesUser && matchesText && matchesTime;
            row.style.display = visible ? '' : 'none';
        });
    }

    filterUser?.addEventListener('change', applyFilters);
    filterSearch?.addEventListener('input', function () {
        window.clearTimeout(this._csnkTimer);
        this._csnkTimer = window.setTimeout(applyFilters, 150);
    });

    rangeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            rangeButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            applyFilters();
        });
    });
})();
</script>

<?php require_once '../includes/footer.php'; ?>