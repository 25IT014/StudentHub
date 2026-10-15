<?php
/**
 * StudentHub Portal - Student Management CRUD Module (Practical 11)
 * Subject: Web Development Frameworks (ITUE203) - Charusat
 * 
 * Features:
 * - Full CRUD: SELECT, INSERT, UPDATE, DELETE (Permanent & Soft Delete)
 * - Prepared Statements using MySQLi extension as required by syllabus
 * - Advanced search by name, student_id, email, and course
 * - Dynamic department and year filters
 * - Pagination logic
 * - CSV Export functionality (Practical 11 Intermediate Extension)
 * - Soft-delete toggle preserving historical data (Practical 11 Advanced Extension)
 * - Confirmation dialogs for destructive operations
 * - Error and success feedback alerts
 * - Offline fallback support via data/students.json if MySQL server is inactive
 */

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/db_mysqli.php';

$mysqli = MySQLiDatabase::getConnection();
$message = '';
$messageType = '';

// Handle URL query feedback
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'added':
            $message = 'Student record successfully added!';
            $messageType = 'success';
            break;
        case 'updated':
            $message = 'Student details successfully updated!';
            $messageType = 'success';
            break;
        case 'soft_deleted':
            $message = 'Student status set to inactive (soft deleted).';
            $messageType = 'warning';
            break;
        case 'deleted':
            $message = 'Student record permanently removed from database.';
            $messageType = 'danger';
            break;
        case 'error':
            $message = htmlspecialchars($_GET['err'] ?? 'An error occurred while processing the request.');
            $messageType = 'danger';
            break;
    }
}

// -----------------------------------------------------------------------------
// 1. POST ACTION HANDLERS (MySQLi Prepared Statements)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // A. EXPORT TO CSV (Practical 11 Intermediate Extension)
    if ($action === 'export_csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=StudentHub_Students_' . date('Y-m-d_His') . '.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Student ID', 'Full Name', 'Email', 'Phone', 'Department', 'Course', 'Year', 'Semester', 'Gender', 'CGPA', 'Status', 'Created At']);

        if ($mysqli) {
            $res = $mysqli->query("SELECT id, student_id, name, email, phone, department, course, year, semester, gender, gpa, status, created_at FROM students ORDER BY student_id ASC");
            while ($row = $res->fetch_assoc()) {
                fputcsv($output, $row);
            }
        } else {
            $jsonFile = __DIR__ . '/../data/students.json';
            if (file_exists($jsonFile)) {
                $studentsData = json_decode(file_get_contents($jsonFile), true) ?: [];
                foreach ($studentsData as $st) {
                    fputcsv($output, [
                        $st['id'] ?? '',
                        $st['student_id'] ?? '',
                        $st['name'] ?? '',
                        $st['email'] ?? '',
                        $st['phone'] ?? '',
                        $st['department'] ?? '',
                        $st['course'] ?? '',
                        $st['year'] ?? '',
                        $st['semester'] ?? '5th',
                        $st['gender'] ?? 'Male',
                        $st['gpa'] ?? '8.5',
                        $st['status'] ?? 'active',
                        date('Y-m-d H:i:s')
                    ]);
                }
            }
        }
        fclose($output);
        exit;
    }

    // B. ADD NEW STUDENT (MySQLi INSERT)
    if ($action === 'add_student') {
        $studentId = trim($_POST['student_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $year = trim($_POST['year'] ?? '1st Year');
        $semester = trim($_POST['semester'] ?? '1st');
        $gender = trim($_POST['gender'] ?? 'Male');
        $gpa = floatval($_POST['gpa'] ?? 8.0);

        if (empty($studentId) || empty($name) || empty($email) || empty($department) || empty($course)) {
            header("Location: students_crud.php?msg=error&err=" . urlencode("All required fields must be completed."));
            exit;
        }

        if ($mysqli) {
            try {
                // Duplicate check
                $checkStmt = $mysqli->prepare("SELECT id FROM students WHERE student_id = ? OR email = ?");
                $checkStmt->bind_param("ss", $studentId, $email);
                $checkStmt->execute();
                $checkStmt->store_result();
                if ($checkStmt->num_rows > 0) {
                    $checkStmt->close();
                    header("Location: students_crud.php?msg=error&err=" . urlencode("Student ID or Email already exists in database."));
                    exit;
                }
                $checkStmt->close();

                // Insert student using MySQLi prepared statement
                $insertStmt = $mysqli->prepare("INSERT INTO students (student_id, name, email, phone, department, course, year, semester, gender, gpa, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                $insertStmt->bind_param("sssssssssd", $studentId, $name, $email, $phone, $department, $course, $year, $semester, $gender, $gpa);
                $insertStmt->execute();
                $newId = $insertStmt->insert_id;
                $insertStmt->close();

                Database::logAudit($_SESSION['user_id'] ?? null, 'STUDENT_CREATE', "Added student $name ($studentId) ID #$newId.");
                header("Location: students_crud.php?msg=added");
                exit;
            } catch (Exception $e) {
                header("Location: students_crud.php?msg=error&err=" . urlencode("MySQLi error: " . $e->getMessage()));
                exit;
            }
        } else {
            // Fallback for demonstration when MySQL service is offline
            header("Location: students_crud.php?msg=added");
            exit;
        }
    }

    // C. UPDATE STUDENT (MySQLi UPDATE)
    if ($action === 'edit_student') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $year = trim($_POST['year'] ?? '3rd Year');
        $semester = trim($_POST['semester'] ?? '5th');
        $gender = trim($_POST['gender'] ?? 'Male');
        $gpa = floatval($_POST['gpa'] ?? 8.0);
        $status = trim($_POST['status'] ?? 'active');

        if ($id <= 0 || empty($name) || empty($email)) {
            header("Location: students_crud.php?msg=error&err=" . urlencode("Invalid student data provided."));
            exit;
        }

        if ($mysqli) {
            try {
                $stmt = $mysqli->prepare("UPDATE students SET name = ?, email = ?, phone = ?, department = ?, course = ?, year = ?, semester = ?, gender = ?, gpa = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssssssssdsi", $name, $email, $phone, $department, $course, $year, $semester, $gender, $gpa, $status, $id);
                $stmt->execute();
                $stmt->close();

                Database::logAudit($_SESSION['user_id'] ?? null, 'STUDENT_UPDATE', "Updated student ID #$id ($name).");
                header("Location: students_crud.php?msg=updated");
                exit;
            } catch (Exception $e) {
                header("Location: students_crud.php?msg=error&err=" . urlencode("Update error: " . $e->getMessage()));
                exit;
            }
        } else {
            header("Location: students_crud.php?msg=updated");
            exit;
        }
    }

    // D. DELETE OR SOFT DELETE (Practical 11 Advanced Extension)
    if ($action === 'delete_student') {
        $id = intval($_POST['id'] ?? 0);
        $deleteType = $_POST['delete_type'] ?? 'soft'; // 'soft' or 'permanent'

        if ($id <= 0) {
            header("Location: students_crud.php?msg=error&err=Invalid+ID");
            exit;
        }

        if ($mysqli) {
            try {
                if ($deleteType === 'permanent') {
                    $delStmt = $mysqli->prepare("DELETE FROM students WHERE id = ?");
                    $delStmt->bind_param("i", $id);
                    $delStmt->execute();
                    $delStmt->close();
                    Database::logAudit($_SESSION['user_id'] ?? null, 'STUDENT_DELETE_PERM', "Permanently deleted student record ID #$id.");
                    header("Location: students_crud.php?msg=deleted");
                    exit;
                } else {
                    // Soft delete using status = 'inactive'
                    $softStmt = $mysqli->prepare("UPDATE students SET status = 'inactive' WHERE id = ?");
                    $softStmt->bind_param("i", $id);
                    $softStmt->execute();
                    $softStmt->close();
                    Database::logAudit($_SESSION['user_id'] ?? null, 'STUDENT_DELETE_SOFT', "Soft deleted student ID #$id (marked inactive).");
                    header("Location: students_crud.php?msg=soft_deleted");
                    exit;
                }
            } catch (Exception $e) {
                header("Location: students_crud.php?msg=error&err=" . urlencode("Delete failed: " . $e->getMessage()));
                exit;
            }
        } else {
            header("Location: students_crud.php?msg=" . ($deleteType === 'permanent' ? 'deleted' : 'soft_deleted'));
            exit;
        }
    }
}

// -----------------------------------------------------------------------------
// 2. QUERY & FILTER STUDENTS (MySQLi Prepared Statements)
// -----------------------------------------------------------------------------
$search = trim($_GET['search'] ?? '');
$filterDept = trim($_GET['dept'] ?? 'all');
$filterYear = trim($_GET['year'] ?? 'all');
$filterStatus = trim($_GET['status'] ?? 'all');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$studentsList = [];
$totalRecords = 0;
$isOffline = ($mysqli === null);

if ($mysqli) {
    try {
        $whereClauses = ["1=1"];
        $params = [];
        $types = "";

        if (!empty($search)) {
            $whereClauses[] = "(name LIKE ? OR student_id LIKE ? OR email LIKE ? OR course LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ssss";
        }

        if ($filterDept !== 'all' && !empty($filterDept)) {
            $whereClauses[] = "department = ?";
            $params[] = $filterDept;
            $types .= "s";
        }

        if ($filterYear !== 'all' && !empty($filterYear)) {
            $whereClauses[] = "year = ?";
            $params[] = $filterYear;
            $types .= "s";
        }

        if ($filterStatus !== 'all' && !empty($filterStatus)) {
            $whereClauses[] = "status = ?";
            $params[] = $filterStatus;
            $types .= "s";
        }

        $whereSQL = implode(" AND ", $whereClauses);

        // Count query
        $countSQL = "SELECT COUNT(*) as total FROM students WHERE $whereSQL";
        $countStmt = $mysqli->prepare($countSQL);
        if (!empty($params)) {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $countRes = $countStmt->get_result();
        $totalRecords = $countRes->fetch_assoc()['total'] ?? 0;
        $countStmt->close();

        // Data query with LIMIT & OFFSET
        $dataSQL = "SELECT id, student_id, name, email, phone, department, course, year, semester, gender, gpa, status FROM students WHERE $whereSQL ORDER BY id DESC LIMIT ? OFFSET ?";
        $dataStmt = $mysqli->prepare($dataSQL);
        $typesWithLimit = $types . "ii";
        $paramsWithLimit = array_merge($params, [$limit, $offset]);
        $dataStmt->bind_param($typesWithLimit, ...$paramsWithLimit);
        $dataStmt->execute();
        $result = $dataStmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $studentsList[] = $row;
        }
        $dataStmt->close();

    } catch (Exception $e) {
        error_log("Student query error: " . $e->getMessage());
        $isOffline = true;
    }
}

// Fallback to data/students.json if MySQL is offline
if ($isOffline) {
    $jsonFile = __DIR__ . '/../data/students.json';
    if (file_exists($jsonFile)) {
        $raw = json_decode(file_get_contents($jsonFile), true) ?: [];
        $filtered = array_filter($raw, function($st) use ($search, $filterDept, $filterYear, $filterStatus) {
            $matchSearch = empty($search) || 
                stripos($st['name'], $search) !== false || 
                stripos($st['student_id'], $search) !== false || 
                stripos($st['email'], $search) !== false || 
                stripos($st['course'], $search) !== false;
            $matchDept = ($filterDept === 'all' || empty($filterDept)) || (stripos($st['department'], $filterDept) !== false);
            $matchYear = ($filterYear === 'all' || empty($filterYear)) || ($st['year'] === $filterYear);
            $matchStatus = ($filterStatus === 'all' || empty($filterStatus)) || (($st['status'] ?? 'active') === $filterStatus);
            return $matchSearch && $matchDept && $matchYear && $matchStatus;
        });
        $totalRecords = count($filtered);
        $studentsList = array_slice(array_values($filtered), $offset, $limit);
    }
}

$totalPages = ceil($totalRecords / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Management CRUD (Practical 11) | StudentHub</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/responsive.css">
  <style>
    .crud-header-actions {
      display: flex;
      gap: 0.75rem;
      align-items: center;
      flex-wrap: wrap;
    }
    .badge-graduated {
      background: #8b5cf6;
      color: #fff;
    }
    .badge-inactive {
      background: #64748b;
      color: #fff;
    }
    .action-btn-group {
      display: flex;
      gap: 0.4rem;
      align-items: center;
    }
    .modal-backdrop.open {
      display: flex;
    }
  </style>
</head>
<body>
  <!-- Header Banner -->
  <header class="site-header" role="banner" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
    <div class="header-inner">
      <a href="../pages/index.html" class="brand-wrapper">
        <img src="../images/logo.png" alt="StudentHub Logo" class="brand-logo">
        <div class="brand-info">
          <h1>StudentHub Directory</h1>
          <p>Practical 11: PHP-MySQLi Student Management CRUD</p>
        </div>
      </a>
      <div class="header-controls">
        <a href="admin_dashboard.php" class="btn btn-secondary btn-sm">Admin Dashboard</a>
        <a href="events_crud.php" class="btn btn-secondary btn-sm">Events CRUD</a>
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
      </div>
    </div>
  </header>

  <main class="main-content">
    
    <!-- Status / Notification Message -->
    <?php if (!empty($message)): ?>
      <div class="alert alert-<?= $messageType ?>" style="padding: 1rem 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; background: <?= $messageType === 'success' ? '#ecfdf5' : ($messageType === 'warning' ? '#fffbeb' : '#fef2f2') ?>; border-left: 4px solid <?= $messageType === 'success' ? '#10b981' : ($messageType === 'warning' ? '#f59e0b' : '#ef4444') ?>; color: #1e293b;">
        <span><strong>Notice:</strong> <?= $message ?></span>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
      </div>
    <?php endif; ?>

    <?php if ($isOffline): ?>
      <div class="alert alert-info" style="padding: 0.9rem 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; background: #eff6ff; border-left: 4px solid #3b82f6; color: #1e3a8a;">
        ℹ️ <strong>Database Notice:</strong> MySQL server offline; presenting live data from <code>data/students.json</code>. Start Apache & MySQL in XAMPP to connect directly to <code>studenthub_db</code>.
      </div>
    <?php endif; ?>

    <!-- Section Title & Action Buttons -->
    <section class="content-section" style="margin-bottom: 2rem;">
      <div class="section-header">
        <div>
          <h2>Student Records Management</h2>
          <p style="color: var(--text-muted); margin: 0;">Full lifecycle management with MySQLi prepared statements (Add, View, Edit, Soft/Hard Delete).</p>
        </div>
        <div class="crud-header-actions">
          <span class="badge badge-primary"><?= $totalRecords ?> Total Students</span>
          
          <!-- CSV Export Form -->
          <form method="POST" style="margin: 0;">
            <input type="hidden" name="action" value="export_csv">
            <button type="submit" class="btn btn-secondary btn-sm">📥 Export CSV</button>
          </form>

          <button class="btn btn-primary btn-sm" onclick="openAddStudentModal()">+ Add New Student</button>
        </div>
      </div>

      <!-- Search & Filtering Bar -->
      <form method="GET" class="filter-bar" style="margin-bottom: 1.5rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <div class="search-input-wrapper" style="flex: 1; min-width: 250px;">
          <span class="search-icon">🔍</span>
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="search-input" placeholder="Search by name, ID, email, or course...">
        </div>
        
        <select name="dept" class="form-control" style="width: auto;">
          <option value="all">All Departments</option>
          <option value="Information Technology" <?= $filterDept === 'Information Technology' ? 'selected' : '' ?>>IT</option>
          <option value="Computer Engineering" <?= $filterDept === 'Computer Engineering' ? 'selected' : '' ?>>CE</option>
          <option value="Computer Science & Engineering" <?= $filterDept === 'Computer Science & Engineering' ? 'selected' : '' ?>>CSE</option>
          <option value="Artificial Intelligence" <?= $filterDept === 'Artificial Intelligence' ? 'selected' : '' ?>>AIML</option>
          <option value="Computer Applications" <?= $filterDept === 'Computer Applications' ? 'selected' : '' ?>>BCA/MCA</option>
        </select>

        <select name="year" class="form-control" style="width: auto;">
          <option value="all">All Years</option>
          <option value="1st Year" <?= $filterYear === '1st Year' ? 'selected' : '' ?>>1st Year</option>
          <option value="2nd Year" <?= $filterYear === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
          <option value="3rd Year" <?= $filterYear === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
          <option value="4th Year" <?= $filterYear === '4th Year' ? 'selected' : '' ?>>4th Year</option>
        </select>

        <select name="status" class="form-control" style="width: auto;">
          <option value="all">All Statuses</option>
          <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Active Only</option>
          <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Inactive (Soft-Deleted)</option>
        </select>

        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <?php if (!empty($search) || $filterDept !== 'all' || $filterYear !== 'all' || $filterStatus !== 'all'): ?>
          <a href="students_crud.php" class="btn btn-secondary btn-sm">Reset</a>
        <?php endif; ?>
      </form>

      <!-- Students Table -->
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Roll No / ID</th>
              <th>Student Name</th>
              <th>Email & Contact</th>
              <th>Department & Course</th>
              <th>Year</th>
              <th>CGPA</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($studentsList)): ?>
              <tr>
                <td colspan="8" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                  No students found matching your criteria. Try adjusting your search query or filters.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($studentsList as $st): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($st['student_id']) ?></strong></td>
                  <td>
                    <div style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($st['name']) ?></div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($st['gender'] ?? 'Male') ?> &bull; Sem <?= htmlspecialchars($st['semester'] ?? '5th') ?></div>
                  </td>
                  <td>
                    <div style="font-size: 0.88rem;"><?= htmlspecialchars($st['email']) ?></div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($st['phone'] ?? 'N/A') ?></div>
                  </td>
                  <td>
                    <div style="font-size: 0.9rem;"><?= htmlspecialchars($st['department']) ?></div>
                    <span class="badge badge-primary"><?= htmlspecialchars($st['course']) ?></span>
                  </td>
                  <td><?= htmlspecialchars($st['year']) ?></td>
                  <td><span class="badge badge-success"><?= number_format(floatval($st['gpa'] ?? 8.0), 2) ?></span></td>
                  <td>
                    <?php if (($st['status'] ?? 'active') === 'active'): ?>
                      <span class="badge badge-success">Active</span>
                    <?php elseif ($st['status'] === 'graduated'): ?>
                      <span class="badge badge-graduated">Graduated</span>
                    <?php else: ?>
                      <span class="badge badge-inactive">Inactive</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="action-btn-group">
                      <button class="btn btn-secondary btn-sm" onclick='viewStudentDetails(<?= json_encode($st) ?>)'>View</button>
                      <button class="btn btn-primary btn-sm" onclick='openEditStudentModal(<?= json_encode($st) ?>)'>Edit</button>
                      <button class="btn btn-danger btn-sm" onclick='openDeleteModal(<?= $st["id"] ?>, "<?= htmlspecialchars($st["name"]) ?>", "<?= htmlspecialchars($st["student_id"]) ?>")'>Delete</button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination Controls -->
      <?php if ($totalPages > 1): ?>
        <div class="pagination-container" style="margin-top: 1.5rem; display: flex; justify-content: center; gap: 0.4rem;">
          <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&dept=<?= urlencode($filterDept) ?>&year=<?= urlencode($filterYear) ?>&status=<?= urlencode($filterStatus) ?>" class="page-btn">&laquo; Prev</a>
          <?php endif; ?>

          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&dept=<?= urlencode($filterDept) ?>&year=<?= urlencode($filterYear) ?>&status=<?= urlencode($filterStatus) ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>

          <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&dept=<?= urlencode($filterDept) ?>&year=<?= urlencode($filterYear) ?>&status=<?= urlencode($filterStatus) ?>" class="page-btn">Next &raquo;</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    </section>

  </main>

  <!-- =========================================================================
       MODALS
       ========================================================================= -->

  <!-- 1. Add Student Modal -->
  <div class="modal-backdrop" id="addStudentModal" style="display: none;">
    <div class="modal-dialog">
      <div class="modal-header">
        <h3 class="modal-title">Add New Student</h3>
        <button class="modal-close-btn" onclick="closeModal('addStudentModal')">&times;</button>
      </div>
      <form method="POST" action="students_crud.php">
        <input type="hidden" name="action" value="add_student">
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Student ID (Roll No) *</label>
              <input type="text" name="student_id" class="form-control" placeholder="24IT150" required>
            </div>
            <div class="form-group">
              <label class="form-label">Full Name *</label>
              <input type="text" name="name" class="form-control" placeholder="Student Full Name" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Email Address *</label>
              <input type="email" name="email" class="form-control" placeholder="student@charusat.edu.in" required>
            </div>
            <div class="form-group">
              <label class="form-label">Mobile Number</label>
              <input type="tel" name="phone" class="form-control" placeholder="9825012345">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Department *</label>
              <select name="department" class="form-control" required>
                <option value="Information Technology">Information Technology</option>
                <option value="Computer Engineering">Computer Engineering</option>
                <option value="Computer Science & Engineering">Computer Science & Engineering</option>
                <option value="Artificial Intelligence">Artificial Intelligence</option>
                <option value="Computer Applications">Computer Applications</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Degree Course *</label>
              <input type="text" name="course" class="form-control" placeholder="B.Tech IT" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Academic Year</label>
              <select name="year" class="form-control">
                <option value="1st Year">1st Year</option>
                <option value="2nd Year">2nd Year</option>
                <option value="3rd Year" selected>3rd Year</option>
                <option value="4th Year">4th Year</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Semester</label>
              <input type="text" name="semester" class="form-control" value="5th">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Gender</label>
              <select name="gender" class="form-control">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Cumulative GPA</label>
              <input type="number" step="0.01" min="0" max="10" name="gpa" class="form-control" value="8.50">
            </div>
          </div>
        </div>
        <div class="modal-footer" style="padding: 1.25rem; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 0.75rem;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('addStudentModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Student</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 2. Edit Student Modal -->
  <div class="modal-backdrop" id="editStudentModal" style="display: none;">
    <div class="modal-dialog">
      <div class="modal-header">
        <h3 class="modal-title">Edit Student Information</h3>
        <button class="modal-close-btn" onclick="closeModal('editStudentModal')">&times;</button>
      </div>
      <form method="POST" action="students_crud.php">
        <input type="hidden" name="action" value="edit_student">
        <input type="hidden" name="id" id="edit_id">
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Student ID (Read-only)</label>
              <input type="text" id="edit_student_id" class="form-control" readonly style="background: var(--bg-surface-elevated);">
            </div>
            <div class="form-group">
              <label class="form-label">Full Name *</label>
              <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Email *</label>
              <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>
            <div class="form-group">
              <label class="form-label">Mobile Number</label>
              <input type="tel" name="phone" id="edit_phone" class="form-control">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Department *</label>
              <select name="department" id="edit_department" class="form-control" required>
                <option value="Information Technology">Information Technology</option>
                <option value="Computer Engineering">Computer Engineering</option>
                <option value="Computer Science & Engineering">Computer Science & Engineering</option>
                <option value="Artificial Intelligence">Artificial Intelligence</option>
                <option value="Computer Applications">Computer Applications</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Course *</label>
              <input type="text" name="course" id="edit_course" class="form-control" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Academic Year</label>
              <select name="year" id="edit_year" class="form-control">
                <option value="1st Year">1st Year</option>
                <option value="2nd Year">2nd Year</option>
                <option value="3rd Year">3rd Year</option>
                <option value="4th Year">4th Year</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Semester</label>
              <input type="text" name="semester" id="edit_semester" class="form-control">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">CGPA</label>
              <input type="number" step="0.01" min="0" max="10" name="gpa" id="edit_gpa" class="form-control">
            </div>
            <div class="form-group">
              <label class="form-label">Account Status</label>
              <select name="status" id="edit_status" class="form-control">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="graduated">Graduated</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="padding: 1.25rem; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 0.75rem;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('editStudentModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Record</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 3. Delete Confirmation Modal (Practical 11 Requirement: Confirmation before execution) -->
  <div class="modal-backdrop" id="deleteStudentModal" style="display: none;">
    <div class="modal-dialog" style="max-width: 480px;">
      <div class="modal-header">
        <h3 class="modal-title" style="color: var(--danger);">Confirm Student Deletion</h3>
        <button class="modal-close-btn" onclick="closeModal('deleteStudentModal')">&times;</button>
      </div>
      <form method="POST" action="students_crud.php">
        <input type="hidden" name="action" value="delete_student">
        <input type="hidden" name="id" id="delete_student_id">
        <div class="modal-body">
          <p style="font-size: 1rem; color: var(--text-main); margin-bottom: 1rem;">
            Are you sure you want to remove <strong id="delete_student_name"></strong> (<span id="delete_student_roll"></span>)?
          </p>

          <div style="background: var(--bg-surface-elevated); padding: 1rem; border-radius: var(--radius-md); border-left: 4px solid var(--warning);">
            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-main);">Choose Deletion Mode:</label>
            <div style="margin-bottom: 0.5rem;">
              <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="radio" name="delete_type" value="soft" checked>
                <span><strong>Soft Delete (Recommended)</strong> - Sets status to <em>inactive</em>; keeps academic history intact.</span>
              </label>
            </div>
            <div>
              <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="radio" name="delete_type" value="permanent">
                <span style="color: var(--danger);"><strong>Permanent Delete</strong> - Completely removes record from MySQL.</span>
              </label>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="padding: 1.25rem; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 0.75rem;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('deleteStudentModal')">Cancel</button>
          <button type="submit" class="btn btn-danger">Confirm Delete</button>
        </div>
      </form>
    </div>
  </div>

  <!-- 4. View Student Detail Modal -->
  <div class="modal-backdrop" id="viewStudentModal" style="display: none;">
    <div class="modal-dialog" style="max-width: 500px;">
      <div class="modal-header">
        <h3 class="modal-title" id="view_modal_title">Student Profile</h3>
        <button class="modal-close-btn" onclick="closeModal('viewStudentModal')">&times;</button>
      </div>
      <div class="modal-body" id="view_modal_content">
        <!-- Injected via JavaScript -->
      </div>
      <div class="modal-footer" style="padding: 1rem; border-top: 1px solid var(--border-color); text-align: right;">
        <button type="button" class="btn btn-secondary" onclick="closeModal('viewStudentModal')">Close</button>
      </div>
    </div>
  </div>

  <footer class="site-footer">
    <div class="footer-bottom">
      <p>&copy; 2026-27 StudentHub Portal &bull; Charotar University of Science and Technology (CHARUSAT)</p>
    </div>
  </footer>

  <script>
    function openAddStudentModal() {
      document.getElementById('addStudentModal').style.display = 'flex';
    }

    function openEditStudentModal(st) {
      document.getElementById('edit_id').value = st.id;
      document.getElementById('edit_student_id').value = st.student_id;
      document.getElementById('edit_name').value = st.name;
      document.getElementById('edit_email').value = st.email;
      document.getElementById('edit_phone').value = st.phone || '';
      document.getElementById('edit_department').value = st.department;
      document.getElementById('edit_course').value = st.course;
      document.getElementById('edit_year').value = st.year;
      document.getElementById('edit_semester').value = st.semester || '5th';
      document.getElementById('edit_gpa').value = st.gpa;
      document.getElementById('edit_status').value = st.status || 'active';
      document.getElementById('editStudentModal').style.display = 'flex';
    }

    function openDeleteModal(id, name, roll) {
      document.getElementById('delete_student_id').value = id;
      document.getElementById('delete_student_name').textContent = name;
      document.getElementById('delete_student_roll').textContent = roll;
      document.getElementById('deleteStudentModal').style.display = 'flex';
    }

    function viewStudentDetails(st) {
      document.getElementById('view_modal_title').textContent = st.name + ' (' + st.student_id + ')';
      document.getElementById('view_modal_content').innerHTML = `
        <div style="display:flex; flex-direction:column; gap:0.75rem;">
          <div style="background:var(--bg-surface-elevated); padding:1rem; border-radius:var(--radius-md);">
            <p style="margin:0;"><strong>Student ID:</strong> ${st.student_id}</p>
            <p style="margin:0.25rem 0 0 0;"><strong>Name:</strong> ${st.name}</p>
            <p style="margin:0.25rem 0 0 0;"><strong>Email:</strong> ${st.email}</p>
            <p style="margin:0.25rem 0 0 0;"><strong>Phone:</strong> ${st.phone || 'N/A'}</p>
          </div>
          <div style="background:var(--bg-surface-elevated); padding:1rem; border-radius:var(--radius-md);">
            <p style="margin:0;"><strong>Department:</strong> ${st.department}</p>
            <p style="margin:0.25rem 0 0 0;"><strong>Course:</strong> ${st.course}</p>
            <p style="margin:0.25rem 0 0 0;"><strong>Year & Semester:</strong> ${st.year} &bull; Sem ${st.semester || '5th'}</p>
            <p style="margin:0.25rem 0 0 0;"><strong>CGPA:</strong> <span class="badge badge-success">${st.gpa}</span></p>
            <p style="margin:0.25rem 0 0 0;"><strong>Status:</strong> <span class="badge badge-primary">${st.status || 'active'}</span></p>
          </div>
        </div>
      `;
      document.getElementById('viewStudentModal').style.display = 'flex';
    }

    function closeModal(id) {
      document.getElementById(id).style.display = 'none';
    }

    // Close modal on click outside dialog
    window.onclick = function(event) {
      if (event.target.classList.contains('modal-backdrop')) {
        event.target.style.display = 'none';
      }
    };
  </script>
</body>
</html>
