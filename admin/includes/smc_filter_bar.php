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

