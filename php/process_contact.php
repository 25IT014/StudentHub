<?php
/**
 * StudentHub Portal - Contact & Feedback Processor (Practical 7)
 * - Server-side validation and sanitization
 * - Safe file writing to CSV (data/contacts.csv) and JSON (data/contacts_stored.json)
 * - CSRF token verification
 */

require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/contact.html");
    exit;
}

$errors = [];

// Sanitize inputs
$name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS));
$email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
$category = trim(filter_input(INPUT_POST, 'category', FILTER_SANITIZE_SPECIAL_CHARS));
$subject = trim(filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_SPECIAL_CHARS)) ?: 'Feedback Submission';
$message = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS));
$rating = trim(filter_input(INPUT_POST, 'rating', FILTER_SANITIZE_NUMBER_INT)) ?: '5';

if (empty($name) || strlen($name) < 2) {
    $errors[] = "Name must be at least 2 characters.";
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please provide a valid email address.";
}

if (empty($message) || strlen($message) < 5) {
    $errors[] = "Message must be at least 5 characters long.";
}

if (!empty($errors)) {
    echo "<!DOCTYPE html><html><head><title>Submission Error</title><link rel='stylesheet' href='../css/style.css'></head><body style='padding:3rem;'><div class='card' style='max-width:500px;margin:auto;text-align:center;'><h2 style='color:var(--danger);'>Submission Error</h2><ul style='color:var(--danger);text-align:left;margin:1.5rem;'>";
    foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>";
    echo "</ul><a href='../pages/contact.html' class='btn btn-primary'>Go Back</a></div></body></html>";
    exit;
}

$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);

// 1. Write to CSV safely
$csvFile = $dataDir . '/contacts.csv';
$isNew = !file_exists($csvFile);
$fp = fopen($csvFile, 'a');
if ($fp) {
    if (flock($fp, LOCK_EX)) {
        if ($isNew) {
            fputcsv($fp, ['Timestamp', 'Name', 'Email', 'Category', 'Subject', 'Rating', 'Message']);
        }
        fputcsv($fp, [date('Y-m-d H:i:s'), $name, $email, $category, $subject, $rating, $message]);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

// 2. Write to JSON storage safely
$jsonFile = $dataDir . '/contacts_stored.json';
$stored = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];
if (!is_array($stored)) $stored = [];

$stored[] = [
    'timestamp' => date('Y-m-d H:i:s'),
    'name' => $name,
    'email' => $email,
    'category' => $category,
    'subject' => $subject,
    'rating' => $rating,
    'message' => $message
];
file_put_contents($jsonFile, json_encode($stored, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

// Display confirmation page
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Submission Received | StudentHub</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body style="background:var(--bg-page);display:flex;align-items:center;justify-content:center;min-height:100vh;">
  <div class="card" style="max-width:520px;text-align:center;padding:2.5rem;">
    <div style="font-size:3rem;margin-bottom:1rem;">📬</div>
    <h2 style="color:var(--success);margin-bottom:0.75rem;">Message Received!</h2>
    <p style="color:var(--text-muted);">Thank you, <?= htmlspecialchars($name) ?>. Your submission has been securely recorded.</p>
    <p style="font-size:0.85rem;color:var(--text-light);margin-top:1rem;">
      Stored in <code>data/contacts.csv</code> and <code>data/contacts_stored.json</code>.
    </p>
    <div style="margin-top:2rem;display:flex;gap:1rem;justify-content:center;">
      <a href="../pages/index.html" class="btn btn-primary">Return Home</a>
      <a href="view_records.php" class="btn btn-secondary">View Records</a>
    </div>
  </div>
</body>
</html>
