<?php
/**
 * StudentHub Portal - File Records Viewer (Practical 7 Intermediate Extension)
 * Reads and displays submitted data from CSV and JSON files
 */

$dataDir = __DIR__ . '/../data';
$regCsv = $dataDir . '/registrations.csv';
$regJson = $dataDir . '/registrations_stored.json';
$contactCsv = $dataDir . '/contacts.csv';

// Read Registrations CSV
$csvRecords = [];
if (file_exists($regCsv)) {
    if (($handle = fopen($regCsv, "r")) !== FALSE) {
        $header = fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== FALSE) {
            $csvRecords[] = $data;
        }
        fclose($handle);
    }
}

// Read Registrations JSON
$jsonRecords = [];
if (file_exists($regJson)) {
    $content = file_get_contents($regJson);
    $jsonRecords = json_decode($content, true) ?: [];
}

// Read Contacts CSV
$contactRecords = [];
if (file_exists($contactCsv)) {
    if (($handle = fopen($contactCsv, "r")) !== FALSE) {
        $cHeader = fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== FALSE) {
            $contactRecords[] = $data;
        }
        fclose($handle);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stored CSV & JSON Records | StudentHub</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>
  <header class="site-header">
    <div class="header-inner">
      <a href="../pages/index.html" class="brand-wrapper">
        <img src="../images/logo.png" alt="StudentHub Logo" class="brand-logo">
        <div class="brand-info">
          <h1>Stored File Submissions</h1>
          <p>Practical 7: CSV & JSON File Storage Viewer</p>
        </div>
      </a>
      <div class="header-controls">
        <a href="../pages/index.html" class="btn btn-secondary btn-sm">Back to Portal</a>
      </div>
    </div>
  </header>

  <main class="main-content">
    <div class="section-header">
      <div>
        <h2>File-Based Form Data Storage (Practical 7)</h2>
        <p style="color:var(--text-muted);margin:0;">Demonstrating persistent records stored via PHP in CSV and JSON formats.</p>
      </div>
      <div style="display:flex;gap:0.5rem;">
        <span class="badge badge-primary"><?= count($csvRecords) ?> Registrations</span>
        <span class="badge badge-info"><?= count($contactRecords) ?> Messages</span>
      </div>
    </div>

    <!-- Section 1: CSV Registrations -->
    <section class="content-section" style="margin-bottom:2rem;">
      <div class="section-header">
        <h3>1. Stored Registrations (CSV: <code>data/registrations.csv</code>)</h3>
        <span class="badge badge-success">CSV Format</span>
      </div>

      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Student ID</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Course</th>
              <th>Year</th>
              <th>Gender</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($csvRecords)): ?>
              <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-muted);">No CSV records found yet. Submit a registration form to view data here.</td></tr>
            <?php else: ?>
              <?php foreach ($csvRecords as $row): ?>
                <tr>
                  <td><?= htmlspecialchars($row[0] ?? '') ?></td>
                  <td><strong><?= htmlspecialchars($row[1] ?? '') ?></strong></td>
                  <td><?= htmlspecialchars($row[2] ?? '') ?></td>
                  <td><?= htmlspecialchars($row[3] ?? '') ?></td>
                  <td><?= htmlspecialchars($row[4] ?? '') ?></td>
                  <td><span class="badge badge-primary"><?= htmlspecialchars($row[5] ?? '') ?></span></td>
                  <td><?= htmlspecialchars($row[6] ?? '') ?></td>
                  <td><?= htmlspecialchars($row[7] ?? '') ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Section 2: JSON Registrations -->
    <section class="content-section" style="margin-bottom:2rem;">
      <div class="section-header">
        <h3>2. Stored Registrations (JSON: <code>data/registrations_stored.json</code>)</h3>
        <span class="badge badge-info">JSON Format</span>
      </div>

      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Student ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Course</th>
              <th>Year</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($jsonRecords)): ?>
              <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted);">No JSON records found yet.</td></tr>
            <?php else: ?>
              <?php foreach ($jsonRecords as $item): ?>
                <tr>
                  <td><?= htmlspecialchars($item['timestamp'] ?? '') ?></td>
                  <td><strong><?= htmlspecialchars($item['student_id'] ?? '') ?></strong></td>
                  <td><?= htmlspecialchars($item['name'] ?? '') ?></td>
                  <td><?= htmlspecialchars($item['email'] ?? '') ?></td>
                  <td><span class="badge badge-primary"><?= htmlspecialchars($item['course'] ?? '') ?></span></td>
                  <td><?= htmlspecialchars($item['year'] ?? '') ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Section 3: Contact & Feedback CSV Submissions -->
    <section class="content-section">
      <div class="section-header">
        <h3>3. Contact Submissions (CSV: <code>data/contacts.csv</code>)</h3>
        <span class="badge badge-warning">Feedback Log</span>
      </div>

      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Name</th>
              <th>Email</th>
              <th>Category</th>
              <th>Subject</th>
              <th>Rating</th>
              <th>Message</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($contactRecords)): ?>
              <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted);">No contact submissions recorded yet.</td></tr>
            <?php else: ?>
              <?php foreach ($contactRecords as $c): ?>
                <tr>
                  <td><?= htmlspecialchars($c[0] ?? '') ?></td>
                  <td><?= htmlspecialchars($c[1] ?? '') ?></td>
                  <td><?= htmlspecialchars($c[2] ?? '') ?></td>
                  <td><span class="badge badge-secondary"><?= htmlspecialchars($c[3] ?? '') ?></span></td>
                  <td><?= htmlspecialchars($c[4] ?? '') ?></td>
                  <td><?= htmlspecialchars($c[5] ?? '5') ?> / 5 ⭐</td>
                  <td><?= htmlspecialchars($c[6] ?? '') ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

  </main>

  <footer class="site-footer">
    <div class="footer-bottom">
      <p>&copy; 2026-27 StudentHub. Practical 7 CSV/JSON Processing Viewer.</p>
    </div>
  </footer>
</body>
</html>
