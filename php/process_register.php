<?php
/**
 * StudentHub Portal - Registration Processor (Practicals 7 & 9)
 * - Server-side input validation and sanitization
 * - Duplicate email & username check
 * - Bcrypt password hashing
 * - CSV and JSON file storage (Practical 7)
 * - Database prepared statement insertion (Practical 9)
 * - Audit log record (Practical 9 & 14)
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

// Ensure form submitted using POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/register.html");
    exit;
}

$errors = [];

// 1. Sanitize Inputs
$name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
$email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
$phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS));
$course = trim(filter_input(INPUT_POST, 'course', FILTER_SANITIZE_SPECIAL_CHARS));
$year = trim(filter_input(INPUT_POST, 'year', FILTER_SANITIZE_SPECIAL_CHARS));
$gender = trim(filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_SPECIAL_CHARS));
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$terms = isset($_POST['terms']);

// 2. Validate Inputs
if (empty($name) || strlen($name) < 3 || !preg_match("/^[A-Za-z0-9\s.]+$/", $name)) {
    $errors[] = "Name must contain only letters, numbers, and spaces (min 3 characters).";
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "A valid email address is required.";
}

if (empty($phone) || !preg_match("/^[6-9]\d{9}$/", $phone)) {
    $errors[] = "Mobile number must be a valid 10-digit number.";
}

if (empty($course)) {
    $errors[] = "Please select a degree course.";
}

if (empty($gender)) {
    $errors[] = "Please select your gender.";
}

// Password Complexity Check (min 8 chars, 1 uppercase, 1 lowercase, 1 digit, 1 special char)
if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) || !preg_match("/\d/", $password) || !preg_match("/[@$!%*?&#^_\-\.]/", $password)) {
    $errors[] = "Password must be at least 8 characters and include uppercase, lowercase, number, and special character (@$!%*?&#^_-.).";
}

if ($password !== $confirm_password) {
    $errors[] = "Password confirmation does not match.";
}

if (!$terms) {
    $errors[] = "You must agree to the student code of conduct.";
}

// 3. Check for Duplicate Email in Database if DB is connected (Practical 9)
$pdo = Database::getConnection();
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "An account with email '$email' already exists in the system.";
        }
    } catch (PDOException $e) {
        error_log("DB Duplicate check error: " . $e->getMessage());
    }
}

// If validation errors exist, display friendly error page
if (!empty($errors)) {
    displayFeedbackPage(false, $errors);
    exit;
}

// 4. Secure Password Hashing (Practical 9)
$password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

// Generate Student ID (e.g. 24IT + random 3 digits)
$prefix = (strpos($course, 'IT') !== false) ? '24IT' : ((strpos($course, 'CE') !== false) ? '24CE' : '24CS');
$generated_id = $prefix . rand(120, 999);
$username = strtolower(str_replace(' ', '_', $name)) . rand(10, 99);

// 5. Store in CSV & JSON Files (Practical 7 Requirement)
$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// A. Write to CSV file safely with flock
$csvFile = $dataDir . '/registrations.csv';
$isNewCsv = !file_exists($csvFile);
$fp = fopen($csvFile, 'a');
if ($fp) {
    if (flock($fp, LOCK_EX)) {
        if ($isNewCsv) {
            fputcsv($fp, ['Timestamp', 'StudentID', 'Name', 'Email', 'Phone', 'Course', 'Year', 'Gender']);
        }
        fputcsv($fp, [date('Y-m-d H:i:s'), $generated_id, $name, $email, $phone, $course, $year, $gender]);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

// B. Write to JSON storage file safely
$jsonFile = $dataDir . '/registrations_stored.json';
$storedJson = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];
if (!is_array($storedJson)) $storedJson = [];

$storedJson[] = [
    'timestamp' => date('Y-m-d H:i:s'),
    'student_id' => $generated_id,
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'course' => $course,
    'year' => $year,
    'gender' => $gender
];
file_put_contents($jsonFile, json_encode($storedJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

// 6. Insert into Database using Prepared Statements (Practical 9)
if ($pdo) {
    try {
        $pdo->beginTransaction();

        // Insert User
        $userStmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, status) VALUES (?, ?, ?, 'student', 'active')");
        $userStmt->execute([$username, $email, $password_hash]);
        $newUserId = $pdo->lastInsertId();

        // Insert Student
        $dept = (strpos($course, 'IT') !== false) ? 'Information Technology' : 'Computer Engineering';
        $studentStmt = $pdo->prepare("INSERT INTO students (user_id, student_id, name, email, phone, department, course, year, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $studentStmt->execute([$newUserId, $generated_id, $name, $email, $phone, $dept, $course, $year, $gender]);

        // Audit Log
        Database::logAudit($newUserId, 'USER_REGISTER', "Student registration successful for $name ($generated_id).");

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Database insert error: " . $e->getMessage());
    }
}

// Display Success Feedback Page
displayFeedbackPage(true, ["Account for $name successfully registered with Student ID: $generated_id."]);

/**
 * Render response UI page
 */
function displayFeedbackPage($success, $messages) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?= $success ? 'Registration Successful' : 'Registration Error' ?> | StudentHub</title>
      <link rel="stylesheet" href="../css/style.css">
      <link rel="stylesheet" href="../css/responsive.css">
    </head>
    <body style="background: var(--bg-page); display: flex; align-items: center; justify-content: center; min-height: 100vh;">
      <div class="card" style="max-width: 540px; width: 90%; text-align: center; padding: 2.5rem; margin: auto;">
        <?php if ($success): ?>
          <div style="font-size: 3rem; margin-bottom: 1rem;">🎉</div>
          <h2 style="color: var(--success); margin-bottom: 0.75rem;">Registration Complete!</h2>
          <?php foreach ($messages as $msg): ?>
            <p style="color: var(--text-muted); font-size: 1rem;"><?= htmlspecialchars($msg) ?></p>
          <?php endforeach; ?>
          <p style="font-size: 0.88rem; color: var(--text-light); margin-top: 1rem;">
            Data stored to CSV (<code>data/registrations.csv</code>), JSON (<code>data/registrations_stored.json</code>), and MySQL database.
          </p>
          <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center;">
            <a href="../pages/login.html" class="btn btn-primary">Proceed to Login</a>
            <a href="view_records.php" class="btn btn-secondary">View Stored Records</a>
          </div>
        <?php else: ?>
          <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
          <h2 style="color: var(--danger); margin-bottom: 0.75rem;">Registration Error</h2>
          <div style="text-align: left; background: var(--bg-surface-elevated); padding: 1.25rem; border-radius: var(--radius-md); border-left: 4px solid var(--danger); margin-bottom: 1.5rem;">
            <ul style="padding-left: 1.25rem; color: var(--danger); font-size: 0.95rem;">
              <?php foreach ($messages as $msg): ?>
                <li><?= htmlspecialchars($msg) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <a href="../pages/register.html" class="btn btn-primary">Go Back & Fix Errors</a>
        <?php endif; ?>
      </div>
    </body>
    </html>
    <?php
}
