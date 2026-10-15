<?php
/**
 * StudentHub Portal - Event Management CRUD with File Upload (Practical 12)
 * Subject: Web Development Frameworks (ITUE203) - Charusat
 * 
 * Features:
 * - Admin Event Management: Add, View, Edit, and Delete campus events
 * - File Upload with strict validation:
 *     - MIME Type Validation (image/jpeg, image/png, image/webp)
 *     - Size limit enforcement (Max 2MB)
 *     - Cryptographically secure randomized file renaming (Practical 12 Advanced Extension)
 *     - Upload folder script execution prevention (.htaccess)
 * - Category and Status (Open / Closed) filtering (Practical 12 Intermediate Extension)
 * - Audit logging for all administrative event actions (Practical 12 Advanced Extension)
 * - Prepared Statements for SQL injection defense
 * - Offline fallback mode via data/events.json
 */

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';

$pdo = Database::getConnection();
$message = '';
$messageType = '';

if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'added':
            $message = 'New campus event and poster uploaded successfully!';
            $messageType = 'success';
            break;
        case 'updated':
            $message = 'Event details updated successfully!';
            $messageType = 'success';
            break;
        case 'deleted':
            $message = 'Event removed from directory.';
            $messageType = 'warning';
            break;
        case 'error':
            $message = htmlspecialchars($_GET['err'] ?? 'An error occurred during event processing.');
            $messageType = 'danger';
            break;
    }
}

// -----------------------------------------------------------------------------
// 1. FILE UPLOAD & VALIDATION HELPER (Practical 12 Core Requirement)
// -----------------------------------------------------------------------------
function handlePosterUpload($fileInputName, &$errorMsg) {
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // No file uploaded
    }

    $file = $_FILES[$fileInputName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = "File upload error code: " . $file['error'];
        return false;
    }

    // 1. Validate File Size (Max 2MB = 2 * 1024 * 1024)
    $maxSize = 2 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        $errorMsg = "Event poster size exceeds the maximum allowed limit of 2MB.";
        return false;
    }

    // 2. Validate MIME Type securely
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes)) {
        $errorMsg = "Invalid file type ($mimeType). Only JPG, PNG, and WebP images are permitted.";
        return false;
    }

    // 3. Determine Safe Extension
    $extensionMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];
    $ext = $extensionMap[$mimeType] ?? 'png';

    // 4. Secure File Renaming (Practical 12 Advanced Extension)
    $safeFilename = 'poster_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $targetDir = __DIR__ . '/../uploads/posters';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $targetPath = $targetDir . '/' . $safeFilename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        $errorMsg = "Failed to store uploaded poster on server.";
        return false;
    }

    // Return relative URL for storage in database
    return '../uploads/posters/' . $safeFilename;
}

// -----------------------------------------------------------------------------
// 2. POST ACTION PROCESSING
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // A. ADD EVENT
    if ($action === 'add_event') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? 'Technical');
        $event_date = trim($_POST['event_date'] ?? date('Y-m-d'));
        $event_time = trim($_POST['event_time'] ?? '10:00 AM - 04:00 PM');
        $venue = trim($_POST['venue'] ?? '');
        $organizer = trim($_POST['organizer'] ?? 'StudentHub Committee');
        $capacity = intval($_POST['capacity'] ?? 100);
        $status = trim($_POST['status'] ?? 'Open');

        if (empty($title) || empty($description) || empty($venue)) {
            header("Location: events_crud.php?msg=error&err=" . urlencode("Title, description, and venue are mandatory fields."));
            exit;
        }

        $uploadError = '';
        $posterPath = handlePosterUpload('poster_file', $uploadError);
        if ($posterPath === false) {
            header("Location: events_crud.php?msg=error&err=" . urlencode($uploadError));
            exit;
        }
        if ($posterPath === null) {
            $posterPath = '../images/StudentHub.png'; // default fallback
        }

        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO events (title, description, category, event_date, event_time, venue, organizer, capacity, poster_image, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $userId = $_SESSION['user_id'] ?? 1;
                $stmt->execute([$title, $description, $category, $event_date, $event_time, $venue, $organizer, $capacity, $posterPath, $status, $userId]);
                $newId = $pdo->lastInsertId();

                Database::logAudit($userId, 'EVENT_CREATE', "Created event '$title' (ID #$newId) with category $category.");
                header("Location: events_crud.php?msg=added");
                exit;
            } catch (PDOException $e) {
                header("Location: events_crud.php?msg=error&err=" . urlencode("Database insert failed: " . $e->getMessage()));
                exit;
            }
        } else {
            header("Location: events_crud.php?msg=added");
            exit;
        }
    }

    // B. EDIT EVENT
    if ($action === 'edit_event') {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? 'Technical');
        $event_date = trim($_POST['event_date'] ?? date('Y-m-d'));
        $event_time = trim($_POST['event_time'] ?? '10:00 AM - 04:00 PM');
        $venue = trim($_POST['venue'] ?? '');
        $organizer = trim($_POST['organizer'] ?? 'StudentHub Committee');
        $capacity = intval($_POST['capacity'] ?? 100);
        $status = trim($_POST['status'] ?? 'Open');

        if ($id <= 0 || empty($title)) {
            header("Location: events_crud.php?msg=error&err=" . urlencode("Invalid event data."));
            exit;
        }

        $uploadError = '';
        $newPoster = handlePosterUpload('poster_file', $uploadError);
        if ($newPoster === false) {
            header("Location: events_crud.php?msg=error&err=" . urlencode($uploadError));
            exit;
        }

        if ($pdo) {
            try {
                if ($newPoster) {
                    $stmt = $pdo->prepare("UPDATE events SET title=?, description=?, category=?, event_date=?, event_time=?, venue=?, organizer=?, capacity=?, poster_image=?, status=? WHERE id=?");
                    $stmt->execute([$title, $description, $category, $event_date, $event_time, $venue, $organizer, $capacity, $newPoster, $status, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE events SET title=?, description=?, category=?, event_date=?, event_time=?, venue=?, organizer=?, capacity=?, status=? WHERE id=?");
                    $stmt->execute([$title, $description, $category, $event_date, $event_time, $venue, $organizer, $capacity, $status, $id]);
                }

                Database::logAudit($_SESSION['user_id'] ?? 1, 'EVENT_UPDATE', "Updated event ID #$id ('$title').");
                header("Location: events_crud.php?msg=updated");
                exit;
            } catch (PDOException $e) {
                header("Location: events_crud.php?msg=error&err=" . urlencode("Update error: " . $e->getMessage()));
                exit;
            }
        } else {
            header("Location: events_crud.php?msg=updated");
            exit;
        }
    }

    // C. DELETE EVENT
    if ($action === 'delete_event') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            header("Location: events_crud.php?msg=error&err=Invalid+ID");
            exit;
        }

        if ($pdo) {
            try {
                // Remove poster if custom uploaded
                $fetchStmt = $pdo->prepare("SELECT poster_image, title FROM events WHERE id = ?");
                $fetchStmt->execute([$id]);
                $ev = $fetchStmt->fetch();

                if ($ev && !empty($ev['poster_image']) && strpos($ev['poster_image'], 'uploads/posters') !== false) {
                    $fullPath = __DIR__ . '/../' . ltrim($ev['poster_image'], './');
                    if (file_exists($fullPath)) {
                        @unlink($fullPath);
                    }
                }

                $delStmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
                $delStmt->execute([$id]);

                $title = $ev['title'] ?? "ID #$id";
                Database::logAudit($_SESSION['user_id'] ?? 1, 'EVENT_DELETE', "Deleted event '$title' (ID #$id).");
                header("Location: events_crud.php?msg=deleted");
                exit;
            } catch (PDOException $e) {
                header("Location: events_crud.php?msg=error&err=" . urlencode("Delete error: " . $e->getMessage()));
                exit;
            }
        } else {
            header("Location: events_crud.php?msg=deleted");
            exit;
        }
    }
}

// -----------------------------------------------------------------------------
// 3. RETRIEVE & FILTER EVENTS
// -----------------------------------------------------------------------------
$search = trim($_GET['search'] ?? '');
$categoryFilter = trim($_GET['category'] ?? 'all');
$statusFilter = trim($_GET['status'] ?? 'all');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 8;
$offset = ($page - 1) * $limit;

$eventsList = [];
$totalRecords = 0;
$isOffline = ($pdo === null);

if ($pdo) {
    try {
        $where = ["1=1"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(title LIKE ? OR description LIKE ? OR venue LIKE ? OR organizer LIKE ?)";
            $st = "%$search%";
            $params = array_merge($params, [$st, $st, $st, $st]);
        }

        if ($categoryFilter !== 'all' && !empty($categoryFilter)) {
            $where[] = "category = ?";
            $params[] = $categoryFilter;
        }

        if ($statusFilter !== 'all' && !empty($statusFilter)) {
            $where[] = "status = ?";
            $params[] = $statusFilter;
        }

        $whereSQL = implode(" AND ", $where);

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE $whereSQL");
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetchColumn();

        $query = "SELECT * FROM events WHERE $whereSQL ORDER BY event_date ASC LIMIT $limit OFFSET $offset";
        $dataStmt = $pdo->prepare($query);
        $dataStmt->execute($params);
        $eventsList = $dataStmt->fetchAll();

    } catch (PDOException $e) {
        error_log("Events query error: " . $e->getMessage());
        $isOffline = true;
    }
}

if ($isOffline) {
    $jsonFile = __DIR__ . '/../data/events.json';
    if (file_exists($jsonFile)) {
        $raw = json_decode(file_get_contents($jsonFile), true) ?: [];
        $filtered = array_filter($raw, function($e) use ($search, $categoryFilter, $statusFilter) {
            $matchSearch = empty($search) || 
                stripos($e['title'], $search) !== false || 
                stripos($e['description'], $search) !== false || 
                stripos($e['venue'], $search) !== false;
            $matchCat = ($categoryFilter === 'all' || empty($categoryFilter)) || ($e['category'] === $categoryFilter);
            $matchStat = ($statusFilter === 'all' || empty($statusFilter)) || ($e['status'] === $statusFilter);
            return $matchSearch && $matchCat && $matchStat;
        });
        $totalRecords = count($filtered);
        $eventsList = array_slice(array_values($filtered), $offset, $limit);
    }
}

$totalPages = ceil($totalRecords / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Event Management CRUD (Practical 12) | StudentHub</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <style>
    .events-admin-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 1.5rem;
      margin-top: 1.5rem;
    }
    .event-admin-card {
      background: var(--bg-surface);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      box-shadow: var(--shadow-sm);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .event-admin-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-md);
    }
    .event-poster-img {
      width: 100%;
      height: 180px;
      object-fit: cover;
      background: var(--bg-surface-elevated);
    }
    .event-card-body {
      padding: 1.25rem;
      display: flex;
      flex-direction: column;
      flex: 1;
    }
    .event-card-footer {
      padding: 0.85rem 1.25rem;
      background: var(--bg-surface-elevated);
      border-top: 1px solid var(--border-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
  </style>
</head>
<body>
  <!-- Header Banner -->
  <header class="site-header" role="banner" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);">
    <div class="header-inner">
      <a href="../pages/index.html" class="brand-wrapper">
        <img src="../images/logo.png" alt="StudentHub Logo" class="brand-logo">
        <div class="brand-info">
          <h1>StudentHub Events</h1>
          <p>Practical 12: Admin Event CRUD & Poster Upload Validation</p>
        </div>
      </a>
      <div class="header-controls">
        <a href="admin_dashboard.php" class="btn btn-secondary btn-sm">Admin Dashboard</a>
        <a href="students_crud.php" class="btn btn-secondary btn-sm">Students CRUD</a>
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
      </div>
    </div>
  </header>

  <main class="main-content">
    
    <!-- Feedback Alerts -->
    <?php if (!empty($message)): ?>
      <div class="alert alert-<?= $messageType ?>" style="padding: 1rem 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; background: <?= $messageType === 'success' ? '#ecfdf5' : ($messageType === 'warning' ? '#fffbeb' : '#fef2f2') ?>; border-left: 4px solid <?= $messageType === 'success' ? '#10b981' : ($messageType === 'warning' ? '#f59e0b' : '#ef4444') ?>; color: #1e293b;">
        <span><strong>Notice:</strong> <?= $message ?></span>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
      </div>
    <?php endif; ?>

    <?php if ($isOffline): ?>
      <div class="alert alert-info" style="padding: 0.9rem 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; background: #eff6ff; border-left: 4px solid #3b82f6; color: #1e3a8a;">
        ℹ️ <strong>Offline Mode:</strong> MySQL is disconnected; displaying data from <code>data/events.json</code>. Launch Apache & MySQL in XAMPP to upload posters and manage live database records.
      </div>
    <?php endif; ?>

    <section class="content-section">
      <div class="section-header">
        <div>
          <h2>Campus Events Administration</h2>
          <p style="color: var(--text-muted); margin: 0;">Create and schedule events, validate poster images, update details, or cancel sessions.</p>
        </div>
        <div style="display: flex; gap: 0.75rem; align-items: center;">
          <span class="badge badge-primary"><?= $totalRecords ?> Events</span>
          <button class="btn btn-primary btn-sm" onclick="openAddEventModal()">+ Add New Event</button>
        </div>
      </div>

      <!-- Filters Form (Practical 12 Intermediate Extension) -->
      <form method="GET" class="filter-bar" style="margin-top: 1rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <div class="search-input-wrapper" style="flex: 1; min-width: 260px;">
          <span class="search-icon">🔍</span>
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="search-input" placeholder="Search events by title, description, or venue...">
        </div>

        <select name="category" class="form-control" style="width: auto;">
          <option value="all">All Categories</option>
          <option value="Technical" <?= $categoryFilter === 'Technical' ? 'selected' : '' ?>>Technical</option>
          <option value="Workshop" <?= $categoryFilter === 'Workshop' ? 'selected' : '' ?>>Workshop</option>
          <option value="Sports" <?= $categoryFilter === 'Sports' ? 'selected' : '' ?>>Sports</option>
          <option value="Cultural" <?= $categoryFilter === 'Cultural' ? 'selected' : '' ?>>Cultural</option>
          <option value="Career" <?= $categoryFilter === 'Career' ? 'selected' : '' ?>>Career</option>
          <option value="Social" <?= $categoryFilter === 'Social' ? 'selected' : '' ?>>Social</option>
        </select>

        <select name="status" class="form-control" style="width: auto;">
          <option value="all">All Statuses</option>
          <option value="Open" <?= $statusFilter === 'Open' ? 'selected' : '' ?>>Open for Registration</option>
          <option value="Closed" <?= $statusFilter === 'Closed' ? 'selected' : '' ?>>Closed / Full</option>
          <option value="Cancelled" <?= $statusFilter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>

        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <?php if (!empty($search) || $categoryFilter !== 'all' || $statusFilter !== 'all'): ?>
          <a href="events_crud.php" class="btn btn-secondary btn-sm">Reset</a>
        <?php endif; ?>
      </form>

      <!-- Event Cards Grid -->
      <div class="events-admin-grid">
        <?php if (empty($eventsList)): ?>
          <div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: var(--text-muted);">
            No events match the specified filter criteria.
          </div>
        <?php else: ?>
          <?php foreach ($eventsList as $ev): ?>
            <?php 
              $poster = !empty($ev['poster_image']) ? $ev['poster_image'] : ($ev['poster'] ?? '../images/StudentHub.png');
              $title = $ev['title'] ?? 'Untitled Event';
              $category = $ev['category'] ?? 'General';
              $date = $ev['event_date'] ?? ($ev['date'] ?? '2026-10-30');
              $venue = $ev['venue'] ?? 'Campus';
              $seats = $ev['capacity'] ?? ($ev['seats'] ?? 100);
              $reg = $ev['registered_count'] ?? ($ev['registered'] ?? 0);
              $status = $ev['status'] ?? 'Open';
            ?>
            <article class="event-admin-card">
              <img src="<?= htmlspecialchars($poster) ?>" alt="<?= htmlspecialchars($title) ?>" class="event-poster-img" onerror="this.src='../images/StudentHub.png'">
              <div class="event-card-body">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                  <span class="badge badge-primary"><?= htmlspecialchars($category) ?></span>
                  <span class="badge <?= $status === 'Open' ? 'badge-success' : 'badge-danger' ?>"><?= htmlspecialchars($status) ?></span>
                </div>
                <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem;"><?= htmlspecialchars($title) ?></h3>
                <p style="font-size: 0.88rem; color: var(--text-muted); flex: 1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 1rem;">
                  <?= htmlspecialchars($ev['description'] ?? '') ?>
                </p>
                <div style="font-size: 0.82rem; color: var(--text-muted); display: flex; flex-direction: column; gap: 0.25rem;">
                  <div>📅 <strong>Date:</strong> <?= htmlspecialchars($date) ?> (<?= htmlspecialchars($ev['event_time'] ?? ($ev['time'] ?? '10:00 AM')) ?>)</div>
                  <div>📍 <strong>Venue:</strong> <?= htmlspecialchars($venue) ?></div>
                  <div>🎟️ <strong>Capacity:</strong> <?= $reg ?> / <?= $seats ?> seats</div>
                </div>
              </div>
              <div class="event-card-footer">
                <button class="btn btn-secondary btn-sm" onclick='viewEventDetails(<?= json_encode($ev) ?>)'>View</button>
                <div style="display: flex; gap: 0.4rem;">
                  <button class="btn btn-primary btn-sm" onclick='openEditEventModal(<?= json_encode($ev) ?>)'>Edit</button>
                  <button class="btn btn-danger btn-sm" onclick='openDeleteEventModal(<?= $ev["id"] ?>, "<?= htmlspecialchars(addslashes($title)) ?>")'>Delete</button>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div class="pagination-container" style="margin-top: 2rem; display: flex; justify-content: center; gap: 0.4rem;">
          <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($categoryFilter) ?>&status=<?= urlencode($statusFilter) ?>" class="page-btn">&laquo; Prev</a>
          <?php endif; ?>

          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($categoryFilter) ?>&status=<?= urlencode($statusFilter) ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>

          <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($categoryFilter) ?>&status=<?= urlencode($statusFilter) ?>" class="page-btn">Next &raquo;</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    </section>

  </main>

  <!-- =========================================================================
       MODALS
       ========================================================================= -->

  <!-- 1. Add Event Modal (With File Upload) -->
  <div class="modal-backdrop" id="addEventModal" style="display: none;">
    <div class="modal-dialog" style="max-width: 600px;">
      <div class="modal-header">
        <h3 class="modal-title">Schedule New Campus Event</h3>
        <button class="modal-close-btn" onclick="closeModal('addEventModal')">&times;</button>
      </div>
      <form method="POST" action="events_crud.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_event">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Event Title *</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. AI Hackathon 2026" required>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Category *</label>
              <select name="category" class="form-control" required>
                <option value="Technical">Technical</option>
                <option value="Workshop">Workshop</option>
                <option value="Sports">Sports</option>
                <option value="Cultural">Cultural</option>
                <option value="Career">Career</option>
                <option value="Social">Social</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Event Status</label>
              <select name="status" class="form-control">
                <option value="Open" selected>Open</option>
                <option value="Closed">Closed</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Event Date *</label>
              <input type="date" name="event_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Time Schedule</label>
              <input type="text" name="event_time" class="form-control" placeholder="10:00 AM - 04:00 PM">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Campus Venue *</label>
              <input type="text" name="venue" class="form-control" placeholder="e.g. Auditorium Hall A" required>
            </div>
            <div class="form-group">
              <label class="form-label">Max Seating Capacity</label>
              <input type="number" name="capacity" class="form-control" value="100" min="1" max="5000">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Organizing Committee / Club</label>
            <input type="text" name="organizer" class="form-control" placeholder="e.g. Department of IT">
          </div>

          <div class="form-group">
            <label class="form-label">Event Description *</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Provide event agenda, prerequisites, and prizes..." required></textarea>
          </div>

          <!-- File Upload Field with validation notices -->
          <div class="form-group" style="background: var(--bg-surface-elevated); padding: 1rem; border-radius: var(--radius-md); border: 1px dashed var(--border-color);">
            <label class="form-label" style="display: flex; justify-content: space-between;">
              <span><strong>Event Poster Image</strong> (Practical 12)</span>
              <span style="font-size: 0.8rem; color: var(--text-muted);">Max 2MB (JPG, PNG, WebP)</span>
            </label>
            <input type="file" name="poster_file" accept="image/jpeg,image/png,image/webp" class="form-control" style="background: var(--bg-surface);">
            <p style="font-size: 0.78rem; color: var(--text-muted); margin: 0.5rem 0 0 0;">
              Files are sanitized, renamed, and stored in protected <code>uploads/posters/</code> directory.
            </p>
          </div>
        </div>
        <div class="modal-footer" style="padding: 1.25rem; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 0.75rem;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('addEventModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Publish Event</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 2. Edit Event Modal -->
  <div class="modal-backdrop" id="editEventModal" style="display: none;">
    <div class="modal-dialog" style="max-width: 600px;">
      <div class="modal-header">
        <h3 class="modal-title">Edit Campus Event</h3>
        <button class="modal-close-btn" onclick="closeModal('editEventModal')">&times;</button>
      </div>
      <form method="POST" action="events_crud.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit_event">
        <input type="hidden" name="id" id="edit_event_id">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Event Title *</label>
            <input type="text" name="title" id="edit_title" class="form-control" required>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Category *</label>
              <select name="category" id="edit_category" class="form-control" required>
                <option value="Technical">Technical</option>
                <option value="Workshop">Workshop</option>
                <option value="Sports">Sports</option>
                <option value="Cultural">Cultural</option>
                <option value="Career">Career</option>
                <option value="Social">Social</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Status</label>
              <select name="status" id="edit_status" class="form-control">
                <option value="Open">Open</option>
                <option value="Closed">Closed</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Event Date *</label>
              <input type="date" name="event_date" id="edit_date" class="form-control" required>
            </div>
            <div class="form-group">
              <label class="form-label">Time</label>
              <input type="text" name="event_time" id="edit_time" class="form-control">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Venue *</label>
              <input type="text" name="venue" id="edit_venue" class="form-control" required>
            </div>
            <div class="form-group">
              <label class="form-label">Capacity</label>
              <input type="number" name="capacity" id="edit_capacity" class="form-control" min="1">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Organizer</label>
            <input type="text" name="organizer" id="edit_organizer" class="form-control">
          </div>

          <div class="form-group">
            <label class="form-label">Description *</label>
            <textarea name="description" id="edit_description" class="form-control" rows="3" required></textarea>
          </div>

          <div class="form-group" style="background: var(--bg-surface-elevated); padding: 1rem; border-radius: var(--radius-md);">
            <label class="form-label">Replace Poster (Optional)</label>
            <input type="file" name="poster_file" accept="image/jpeg,image/png,image/webp" class="form-control" style="background: var(--bg-surface);">
            <small style="color:var(--text-muted);">Leave empty to keep existing poster image.</small>
          </div>
        </div>
        <div class="modal-footer" style="padding: 1.25rem; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 0.75rem;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('editEventModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Event</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 3. Delete Confirmation Modal -->
  <div class="modal-backdrop" id="deleteEventModal" style="display: none;">
    <div class="modal-dialog" style="max-width: 480px;">
      <div class="modal-header">
        <h3 class="modal-title" style="color: var(--danger);">Confirm Event Cancellation</h3>
        <button class="modal-close-btn" onclick="closeModal('deleteEventModal')">&times;</button>
      </div>
      <form method="POST" action="events_crud.php">
        <input type="hidden" name="action" value="delete_event">
        <input type="hidden" name="id" id="delete_event_id">
        <div class="modal-body">
          <p style="font-size: 1rem; color: var(--text-main);">
            Are you sure you want to delete <strong id="delete_event_title"></strong>?
          </p>
          <p style="font-size: 0.85rem; color: var(--text-muted);">
            This will permanently remove the event record and delete any uploaded poster files from the server.
          </p>
        </div>
        <div class="modal-footer" style="padding: 1.25rem; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 0.75rem;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('deleteEventModal')">Cancel</button>
          <button type="submit" class="btn btn-danger">Confirm Delete</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 4. View Event Modal -->
  <div class="modal-backdrop" id="viewEventModal" style="display: none;">
    <div class="modal-dialog" style="max-width: 540px;">
      <div class="modal-header">
        <h3 class="modal-title" id="view_event_modal_title">Event Details</h3>
        <button class="modal-close-btn" onclick="closeModal('viewEventModal')">&times;</button>
      </div>
      <div class="modal-body" id="view_event_modal_content"></div>
      <div class="modal-footer" style="padding: 1rem; border-top: 1px solid var(--border-color); text-align: right;">
        <button type="button" class="btn btn-secondary" onclick="closeModal('viewEventModal')">Close</button>
      </div>
    </div>
  </div>

  <footer class="site-footer">
    <div class="footer-bottom">
      <p>&copy; 2026-27 StudentHub Portal &bull; ITUE203 Web Development Frameworks</p>
    </div>
  </footer>

  <script>
    function openAddEventModal() {
      document.getElementById('addEventModal').style.display = 'flex';
    }

    function openEditEventModal(ev) {
      document.getElementById('edit_event_id').value = ev.id;
      document.getElementById('edit_title').value = ev.title;
      document.getElementById('edit_category').value = ev.category || 'Technical';
      document.getElementById('edit_status').value = ev.status || 'Open';
      document.getElementById('edit_date').value = ev.event_date || (ev.date || '');
      document.getElementById('edit_time').value = ev.event_time || (ev.time || '');
      document.getElementById('edit_venue').value = ev.venue;
      document.getElementById('edit_capacity').value = ev.capacity || (ev.seats || 100);
      document.getElementById('edit_organizer').value = ev.organizer || '';
      document.getElementById('edit_description').value = ev.description;
      document.getElementById('editEventModal').style.display = 'flex';
    }

    function openDeleteEventModal(id, title) {
      document.getElementById('delete_event_id').value = id;
      document.getElementById('delete_event_title').textContent = title;
      document.getElementById('deleteEventModal').style.display = 'flex';
    }

    function viewEventDetails(ev) {
      const poster = ev.poster_image || (ev.poster || '../images/StudentHub.png');
      document.getElementById('view_event_modal_title').textContent = ev.title;
      document.getElementById('view_event_modal_content').innerHTML = `
        <div style="display:flex; flex-direction:column; gap:1rem;">
          <img src="${poster}" alt="${ev.title}" style="width:100%; max-height:220px; object-fit:cover; border-radius:var(--radius-md);" onerror="this.src='../images/StudentHub.png'">
          <div>
            <span class="badge badge-primary">${ev.category}</span>
            <span class="badge ${ev.status === 'Open' ? 'badge-success' : 'badge-danger'}" style="margin-left:8px;">${ev.status}</span>
          </div>
          <p style="color:var(--text-main); font-size:0.95rem; line-height:1.5;">${ev.description}</p>
          <div style="background:var(--bg-surface-elevated); padding:1rem; border-radius:var(--radius-md); font-size:0.88rem; display:flex; flex-direction:column; gap:0.4rem;">
            <div>📅 <strong>Date:</strong> ${ev.event_date || ev.date} (${ev.event_time || ev.time || 'TBA'})</div>
            <div>📍 <strong>Venue:</strong> ${ev.venue}</div>
            <div>👥 <strong>Organizer:</strong> ${ev.organizer || 'StudentHub'}</div>
            <div>🎟️ <strong>Capacity:</strong> ${ev.registered_count || ev.registered || 0} / ${ev.capacity || ev.seats || 100} Registered</div>
          </div>
        </div>
      `;
      document.getElementById('viewEventModal').style.display = 'flex';
    }

    function closeModal(id) {
      document.getElementById(id).style.display = 'none';
    }

    window.onclick = function(e) {
      if (e.target.classList.contains('modal-backdrop')) {
        e.target.style.display = 'none';
      }
    };
  </script>
</body>
</html>
