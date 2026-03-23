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
            echo '  </div>';
            echo '</div>';
        }
    }
}