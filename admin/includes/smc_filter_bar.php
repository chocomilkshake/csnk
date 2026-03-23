<?php
/**
 * SMC Filter Bar (Status + Country) - Reusable include
 *
 * Usage (in a page):
 *
 *   require_once $ADMIN_ROOT . '/includes/smc_filter_bar.php';
 *   $filterState = smc_filter_boot([
 *       'base_url'    => 'turkey_applicants.php', // current page or target
 *       'session_ns'  => 'smc_tr_applicants',     // namespace for session keys
 *       'applicant'   => $applicant,              // instance of Applicant model
 *       'buId'        => $buScope,                // optional BU scope (e.g., SMC BU)
 *       // Optional overrides:
 *       // 'allowed_statuses' => ['all','pending','on_process','approved'],
 *       // 'not_deleted'      => true,
 *       // 'not_blacklisted'  => true,
 *   ]);
 *
 *   // Render the bars (Status + Country)
 *   smc_filter_render($filterState);
 *
 *   // Get the computed filters to fetch your list:
 *   $filters      = $filterState['filters'];
 *   $q            = $filterState['q'];
 *   $status       = $filterState['status'];
 *   $country      = $filterState['country'];
 *   $counts       = $filterState['counts'];
 *   $countries    = $filterState['countriesWithCounts'];
 *   $preserveQS   = $filterState['preserveQS'];
 *   $preserveQSwQ = $filterState['preserveQSWithQuestion'];
 */

if (!function_exists('smc_h')) {
    // HTML escape helper; use existing h() if you already have it
    function smc_h(string $v): string
    {
        if (function_exists('h'))
            return h($v);
        return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('smc_filter_boot')) {
    /**
     * Initialize filters (GET + SESSION), compute counts and provide render state
     */
    function smc_filter_boot(array $opts): array
    {
        // --- Options
        $baseUrl = $opts['base_url'] ?? basename($_SERVER['PHP_SELF']);
        $sessionNs = $opts['session_ns'] ?? 'smc_filter';
        $applicant = $opts['applicant'] ?? null;      // Applicant model
        $buScope = $opts['buId'] ?? null;      // optional: enforce BU
        $allowedStatuses = $opts['allowed_statuses'] ?? ['all', 'pending', 'on_process', 'approved'];

        // typical flags used across pages
        $notDeleted = array_key_exists('not_deleted', $opts) ? (bool) $opts['not_deleted'] : true;
        $notBlacklisted = array_key_exists('not_blacklisted', $opts) ? (bool) $opts['not_blacklisted'] : true;

        // --- Namespaced session keys
        $SESSION_KEY_Q = $sessionNs . '_q';
        $SESSION_KEY_STATUS = $sessionNs . '_status';
        $SESSION_KEY_COUNTRY = $sessionNs . '_country';

        // --- Clear behavior (clear only "q", preserve status and country)
        if (isset($_GET['clear']) && $_GET['clear'] === '1') {
            unset($_SESSION[$SESSION_KEY_Q]);
            // Build redirect with preserved params
            $preserve = [];
            $statusFromSession = $_SESSION[$SESSION_KEY_STATUS] ?? 'all';
            $countryFromSession = $_SESSION[$SESSION_KEY_COUNTRY] ?? 'all';
            if (in_array($statusFromSession, $allowedStatuses, true) && $statusFromSession !== 'all') {
                $preserve['status'] = $statusFromSession;
            }
            if ($countryFromSession !== 'all') {
                $preserve['country'] = $countryFromSession;
            }
            $qs = !empty($preserve) ? ('?' . http_build_query($preserve)) : '';
            if (function_exists('redirect')) {
                redirect($baseUrl . $qs);
                exit;
            } else {
                header('Location: ' . $baseUrl . $qs);
                exit;
            }
        }

        // --- Gather q
        $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        if (isset($_GET['q'])) {
            if (mb_strlen($q) > 100)
                $q = mb_substr($q, 0, 100);
            $_SESSION[$SESSION_KEY_Q] = $q;
        } elseif (!empty($_SESSION[$SESSION_KEY_Q])) {
            $q = (string) $_SESSION[$SESSION_KEY_Q];
        }

        // --- Gather status
        $status = $_GET['status'] ?? ($_SESSION[$SESSION_KEY_STATUS] ?? 'all');
        $status = strtolower(trim((string) $status));

        $defaultStatus = isset($opts['default_status']) ? strtolower(trim((string) $opts['default_status'])) : null;
        if ($defaultStatus !== null && !in_array($defaultStatus, $allowedStatuses, true)) {
            $defaultStatus = null;
        }

        if (!in_array($status, $allowedStatuses, true)) {
            $status = $defaultStatus ?? 'all';
        }

        // If no explicit `status` is set, but a page-specific default exists, apply it
        if ($status === 'all' && $defaultStatus !== null) {
            $status = $defaultStatus;
        }

        $_SESSION[$SESSION_KEY_STATUS] = $status;

        // --- Gather country
        $country = $_GET['country'] ?? ($_SESSION[$SESSION_KEY_COUNTRY] ?? 'all');
        $country = (string) $country;
        if (!($country === 'all' || is_numeric($country)))
            $country = 'all';
        $_SESSION[$SESSION_KEY_COUNTRY] = $country;

        // --- Build filters array for the model
        $filters = [
            'buId' => $buScope,                      // can be null if no BU constraint
            'countryId' => ($country !== 'all') ? (int) $country : null,
            'status' => $status,
            'q' => $q,
            'notDeleted' => $notDeleted,
            'notBlacklisted' => $notBlacklisted,
        ];

        // --- Fetch country rows + counts (replicating your behavior)
        $countriesWithCounts = [];
        $counts = ['all' => 0, 'pending' => 0, 'on_process' => 0, 'approved' => 0];

        if ($applicant && method_exists($applicant, 'getCountriesWithCounts')) {
            // Base list for the current status selection (kept as-is for display order)
            $countries = $applicant->getCountriesWithCounts(
                $filters['buId'],
                $filters['status'],
                $filters['q'],
                $filters['notDeleted'],
                $filters['notBlacklisted']
            );

            // Preload per-status lists once
            $pendingList = $applicant->getCountriesWithCounts($filters['buId'], 'pending', $filters['q'], $filters['notDeleted'], $filters['notBlacklisted']);
            $onProcessList = $applicant->getCountriesWithCounts($filters['buId'], 'on_process', $filters['q'], $filters['notDeleted'], $filters['notBlacklisted']);
            $approvedList = $applicant->getCountriesWithCounts($filters['buId'], 'approved', $filters['q'], $filters['notDeleted'], $filters['notBlacklisted']);

            // Convert to maps keyed by country_id
            $toMap = function (array $rows): array {
                $map = [];
                foreach ($rows as $row) {
                    $cid = (int) ($row['id'] ?? 0);
                    $map[$cid] = (int) ($row['count'] ?? 0);
                }
                return $map;
            };
            $pendingMap = $toMap($pendingList);
            $onProcessMap = $toMap($onProcessList);
            $approvedMap = $toMap($approvedList);

            // ✅ Compute global totals independent of current selection
            $counts['pending'] = array_sum($pendingMap);
            $counts['on_process'] = array_sum($onProcessMap);
            $counts['approved'] = array_sum($approvedMap);
            $counts['all'] = $counts['pending'] + $counts['on_process'] + $counts['approved'];

            // Enrich the current display list with per-bucket values
            foreach ($countries as &$c) {
                $cid = (int) $c['id'];
        <style>
            .status-group {
                border-color: #94a3b8;
                box-shadow: 0 6px 12px rgba(15, 23, 42, .06);
            }

            .status-btn:focus {
                outline: 3px solid rgba(99, 102, 241, .35);
                outline-offset: 2px;
            }

            .status-btn--active {
                color: #fff;
                border-color: #4f46e5;
                background: linear-gradient(180deg, #6366f1 0%, #4f46e5 100%);
                box-shadow: 0 8px 18px rgba(79, 70, 229, .25);
            }

            .status-btn--active:hover {
                background: linear-gradient(180deg, #5457ee 0%, #463fd3 100%);
                border-color: #463fd3;
            }

            .status-icon {
                font-size: .95em;
                line-height: 1;
                opacity: .9;
            }

            .badge-pill {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 22px;
                height: 20px;
                padding: 0 .4rem;
                border-radius: 999px;
                font-weight: 700;
                font-size: .75rem;
                line-height: 1;
                background: #eef2ff;
                color: #4338ca;
                border: 1px solid rgba(0, 0, 0, .04);
            }

            .country-group {
                display: inline-flex;
                gap: .5rem;
                padding: .5rem;
                border: 1px solid #e5e7eb;
                border-radius: 1rem;
                background: rgba(255, 255, 255, .85);
                backdrop-filter: saturate(140%) blur(2px);
                box-shadow: 0 1px 2px rgba(0, 0, 0, .04), 0 1px 3px rgba(0, 0, 0, .10);
            }

            .country-btn {
                display: inline-flex;
                align-items: center;
                gap: .35rem;
                padding: .35rem .75rem;
                border-radius: .75rem;
                font-size: .8rem;
                font-weight: 500;
                text-decoration: none;
                border: 1px solid #cbd5e1;
                color: #334155;
                background: #fff;
                transition: all .15s ease;
                box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
            }

            .country-btn:hover {
                transform: translateY(-1px);
                background: #f8fafc;
                border-color: #94a3b8;
            }

            .country-btn--active {
                color: #fff;
                border-color: #059669;
                background: linear-gradient(180deg, #10b981 0%, #059669 100%);
                box-shadow: 0 4px 10px rgba(5, 150, 105, .25);
            }

            .country-btn--active:hover {
                background: linear-gradient(180deg, #0ea56e 0%, #047857 100%);
            }

            .filter-label {
                font-size: .75rem;
                font-weight: 600;
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: .5px;
                margin-bottom: .25rem;
            }

            .page-header-row {
                row-gap: .5rem;
            }
        </style>
        <?php

        // --------- Render Status buttons ---------
        $qParam = $q;
        $renderBtn = function (string $label, string $value, string $currentStatus, string $q, string $icon, int $count) use ($baseUrl) {
            $isActive = ($value === $currentStatus) || ($value === 'all' && $currentStatus === 'all');
            $href = $baseUrl . '?status=' . urlencode($value);
            if ($q !== '')
                $href .= '&q=' . urlencode($q);
            $classes = 'status-btn' . ($isActive ? ' status-btn--active' : '');
            $iconHtml = $icon !== '' ? '<i class="status-icon ' . smc_h($icon) . '"></i>' : '';
            $countHtml = '<span class="badge-pill ms-1">' . (int) $count . '</span>';
            return '<a href="' . smc_h($href) . '" class="' . $classes . '">' . $iconHtml . '<span>' . smc_h($label) . '</span>' . $countHtml . '</a>';
        };

        echo '<div class="status-group">';
        echo $renderBtn('All', 'all', $status, $qParam, 'bi bi-list-ul', (int) ($counts['all'] ?? 0));
        echo $renderBtn('Pending', 'pending', $status, $qParam, 'bi bi-hourglass-split', (int) ($counts['pending'] ?? 0));
        echo $renderBtn('On-Process', 'on_process', $status, $qParam, 'bi bi-arrow-repeat', (int) ($counts['on_process'] ?? 0));
        echo $renderBtn('Hired', 'approved', $status, $qParam, 'bi bi-check2-circle', (int) ($counts['approved'] ?? 0));
        echo '</div>';

        // --------- Render Country buttons (if any) ---------
        if (!empty($countriesWithCounts)) {
            echo '<div class="col-12 mt-2">';
            echo '  <div class="filter-label">Filter by Country</div>';
            echo '  <div class="country-group">';

            $renderCountryBtn = function (string $label, string $countryId, string $currentCountry, string $q, string $status, int $count) use ($baseUrl) {
                $isActive = ($countryId === $currentCountry) || ($countryId === 'all' && $currentCountry === 'all');
                $href = $baseUrl . '?country=' . urlencode($countryId);
                if ($q !== '')
                    $href .= '&q=' . urlencode($q);
                if ($status !== 'all')
                    $href .= '&status=' . urlencode($status);
                $classes = 'country-btn' . ($isActive ? ' country-btn--active' : '');
                $countHtml = $count > 0 ? '<span class="badge-pill ms-1">' . (int) $count . '</span>' : '';
                return '<a href="' . smc_h($href) . '" class="' . $classes . '"><span>' . smc_h($label) . '</span>' . $countHtml . '</a>';
            };

            // "All" first, showing the count for the selected status
            echo $renderCountryBtn('All', 'all', $country, $q, $status, (int) ($counts['all'] ?? 0));

            foreach ($countriesWithCounts as $c) {
                $countryName = smc_h($c['name'] ?? 'Unknown');
                $countryId = (string) ((int) ($c['id'] ?? 0));
                $count = (int) ($c['count'] ?? 0); // count is for current status selection
                echo $renderCountryBtn($countryName, $countryId, $country, $q, $status, $count);
            }

            echo '  </div>';
            echo '</div>';
        }
    }
}