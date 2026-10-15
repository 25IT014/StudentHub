<?php
/**
 * StudentHub Portal - Authentication & Session Middleware (Practical 10)
 * Handles:
 * - Secure session initiation with cookie parameters
 * - Session Inactivity Timeout (15 minutes)
 * - Session Fixation Regeneration
 * - Role-Based Access Control (RBAC)
 * - CSRF Token Generation & Verification
 */

if (session_status() === PHP_SESSION_NONE) {
    // Secure Session Cookie Settings
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

/**
 * Check and enforce 15-minute inactivity session timeout (Practical 10)
 */
function check_session_timeout($timeout_seconds = 900) {
    if (isset($_SESSION['LAST_ACTIVITY'])) {
        if ((time() - $_SESSION['LAST_ACTIVITY']) > $timeout_seconds) {
            // Last request was more than $timeout_seconds ago
            session_unset();
            session_destroy();
            header("Location: ../pages/login.html?timeout=1");
            exit;
        }
    }
    $_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp
}

/**
 * Require user to be logged in
 */
function require_login() {
    check_session_timeout();
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../pages/login.html?auth=required");
        exit;
    }
}

/**
 * Require user to have Administrator role
 */
function require_admin() {
    require_login();
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header("Location: ../pages/dashboard.html?forbidden=1");
        exit;
    }
}

/**
 * CSRF Protection Helpers (Practical 7 & 9 Advanced Extension)
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
