<?php
/**
 * StudentHub Portal - Secure Authentication Handler (Practical 10)
 * Features:
 * - password_verify()
 * - session_regenerate_id(true) (Fixation defense)
 * - Role-Based Access Control (Admin vs Student redirection)
 * - Session Timeout (15 minutes inactivity tracking)
 * - Remember-Me Token using random 32-byte token hashed with SHA-256 (Practical 10 Advanced)
 * - Last-login timestamp update (Practical 10 Intermediate)
 * - Audit logging
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/login.html");
    exit;
}

$email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
$password = $_POST['password'] ?? '';
$role = trim($_POST['role'] ?? 'student');
$remember_me = isset($_POST['remember_me']);

$pdo = Database::getConnection();

// Fallback demo credentials if database is not active
if (!$pdo) {
    if (($role === 'admin' && $email === 'admin@charusat.edu.in' && $password === 'Admin@123') ||
        ($role === 'student' && $email === 'student@charusat.edu.in' && $password === 'Password@123')) {
        
        session_regenerate_id(true);
        $_SESSION['user_id'] = ($role === 'admin') ? 1 : 2;
        $_SESSION['user_role'] = $role;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = ($role === 'admin') ? 'Administrator' : 'Aarav Sharma';
        $_SESSION['LAST_ACTIVITY'] = time();

        if ($role === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: ../pages/dashboard.html?login=success");
        }
        exit;
    } else {
        header("Location: ../pages/login.html?error=invalid_credentials");
        exit;
    }
}

// 1. Query User from Database using Prepared Statement
try {
    $stmt = $pdo->prepare("SELECT id, username, email, password_hash, role, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // 2. Verify Password using password_verify() (Practical 10)
    if ($user && password_verify($password, $user['password_hash'])) {
        
        if ($user['status'] !== 'active') {
            header("Location: ../pages/login.html?error=account_suspended");
            exit;
        }

        // Regenerate Session ID to prevent session fixation attacks
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['LAST_ACTIVITY'] = time();

        // 3. Update Last-Login Timestamp (Practical 10 Intermediate)
        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);

        // 4. Remember-Me Token via SHA-256 Hash in Database (Practical 10 Advanced)
        if ($remember_me) {
            $randomToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $randomToken);

            $tokenStmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
            $tokenStmt->execute([$tokenHash, $user['id']]);

            // Set secure cookie valid for 30 days
            setcookie('remember_token', $randomToken, [
                'expires' => time() + (86400 * 30),
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        // Log action in audit trail
        Database::logAudit($user['id'], 'LOGIN_SUCCESS', "User {$user['email']} authenticated successfully.");

        // 5. Role-Based Redirection (Practical 10)
        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: ../pages/dashboard.html");
        }
        exit;

    } else {
        // Invalid credentials
        if ($user) {
            Database::logAudit($user['id'], 'LOGIN_FAILED', "Failed login attempt for {$user['email']}.");
        }
        header("Location: ../pages/login.html?error=invalid_credentials");
        exit;
    }

} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    header("Location: ../pages/login.html?error=server_error");
    exit;
}
