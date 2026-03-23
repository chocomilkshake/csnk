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
 *   // Render the bars (Status + Country)s, true) && $statusFromSession !== 'all') {
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

