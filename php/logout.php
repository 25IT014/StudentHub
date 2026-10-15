<?php
/**
 * StudentHub Portal - Secure Logout Handler (Practical 10)
 * Destroys session, unsets cookies, clears remember-me token, and logs audit record.
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? 'Unknown User';

// 1. Audit Log Entry for Logout
if ($userId) {
    Database::logAudit($userId, 'USER_LOGOUT', "User $userName successfully logged out.");
}

// 2. Clear Database Remember Token & Cookie if Present (Practical 10 Advanced)
if (isset($_COOKIE['remember_token'])) {
    if ($userId) {
        $pdo = Database::getConnection();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
                $stmt->execute([$userId]);
            } catch (PDOException $e) {
                error_log("Failed to clear remember token: " . $e->getMessage());
            }
        }
    }

    // Invalidate Cookie
    setcookie('remember_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// 3. Clear Session Variables
$_SESSION = [];

// 4. Destroy Session Cookie in Browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 5. Destroy PHP Session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// 6. Redirect to Login Page with Feedback
header("Location: ../pages/login.html?status=logged_out");
exit;
