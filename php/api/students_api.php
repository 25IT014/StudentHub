<?php
/**
 * StudentHub Portal - Students JSON RESTful API Endpoint (Practical 13)
 * Handles GET, POST, PUT, DELETE for students via Fetch API without page reload.
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/db.php';

function sendStudentResponse($success, $data = null, $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'status_code' => $statusCode,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true) ?: [];
$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod === 'POST') {
    if (isset($jsonInput['_method'])) $requestMethod = strtoupper($jsonInput['_method']);
    elseif (isset($_POST['_method'])) $requestMethod = strtoupper($_POST['_method']);
}

$pdo = Database::getConnection();

// 1. GET: Fetch students
if ($requestMethod === 'GET') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    $q = trim($_GET['q'] ?? '');
    $dept = trim($_GET['dept'] ?? '');

    if ($pdo) {
        try {
            if ($id !== null) {
                $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
                $stmt->execute([$id]);
                $student = $stmt->fetch();
                if ($student) {
                    sendStudentResponse(true, $student, "Student found", 200);
                } else {
                    sendStudentResponse(false, null, "Student not found", 404);
                }
            } else {
                $where = ["1=1"];
                $params = [];
                if (!empty($q)) {
                    $where[] = "(name LIKE ? OR student_id LIKE ? OR email LIKE ?)";
                    $params[] = "%$q%";
                    $params[] = "%$q%";
                    $params[] = "%$q%";
                }
                if (!empty($dept) && $dept !== 'all') {
                    $where[] = "department = ?";
                    $params[] = $dept;
                }
                $sql = "SELECT * FROM students WHERE " . implode(" AND ", $where) . " ORDER BY id DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $students = $stmt->fetchAll();
                sendStudentResponse(true, $students, count($students) . " students retrieved", 200);
            }
        } catch (PDOException $e) {
            sendStudentResponse(false, null, "Database error: " . $e->getMessage(), 500);
        }
    } else {
        $jsonFile = __DIR__ . '/../../data/students.json';
        $students = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];
        if ($id !== null) {
            foreach ($students as $s) {
                if ($s['id'] == $id) sendStudentResponse(true, $s, "Student found (offline cache)", 200);
            }
            sendStudentResponse(false, null, "Student not found", 404);
        }
        if (!empty($q)) {
            $students = array_values(array_filter($students, function($s) use ($q) {
                return stripos($s['name'], $q) !== false || stripos($s['student_id'], $q) !== false;
            }));
        }
        sendStudentResponse(true, $students, count($students) . " students retrieved (cached)", 200);
    }
}

// 2. POST: Add student
if ($requestMethod === 'POST') {
    $studentId = trim($jsonInput['student_id'] ?? ($_POST['student_id'] ?? ''));
    $name = trim($jsonInput['name'] ?? ($_POST['name'] ?? ''));
    $email = trim($jsonInput['email'] ?? ($_POST['email'] ?? ''));
    $phone = trim($jsonInput['phone'] ?? ($_POST['phone'] ?? ''));
    $department = trim($jsonInput['department'] ?? ($_POST['department'] ?? ''));
    $course = trim($jsonInput['course'] ?? ($_POST['course'] ?? ''));
    $year = trim($jsonInput['year'] ?? ($_POST['year'] ?? '3rd Year'));
    $gpa = floatval($jsonInput['gpa'] ?? ($_POST['gpa'] ?? 8.5));

    if (empty($studentId) || empty($name) || empty($email)) {
        sendStudentResponse(false, null, "Student ID, Name, and Email are required", 422);
    }

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO students (student_id, name, email, phone, department, course, year, gpa, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$studentId, $name, $email, $phone, $department, $course, $year, $gpa]);
            $newId = $pdo->lastInsertId();
            sendStudentResponse(true, ['id' => $newId, 'student_id' => $studentId], "Student added successfully", 201);
        } catch (PDOException $e) {
            sendStudentResponse(false, null, "Insert error: " . $e->getMessage(), 500);
        }
    } else {
        sendStudentResponse(true, ['id' => rand(100, 999), 'student_id' => $studentId], "Student added (demo)", 201);
    }
}

// 3. PUT: Update student
if ($requestMethod === 'PUT') {
    $id = intval($jsonInput['id'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) sendStudentResponse(false, null, "Valid ID required", 400);

    $name = trim($jsonInput['name'] ?? '');
    $email = trim($jsonInput['email'] ?? '');
    $department = trim($jsonInput['department'] ?? '');
    $course = trim($jsonInput['course'] ?? '');
    $gpa = floatval($jsonInput['gpa'] ?? 8.5);

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE students SET name=?, email=?, department=?, course=?, gpa=? WHERE id=?");
            $stmt->execute([$name, $email, $department, $course, $gpa, $id]);
            sendStudentResponse(true, ['id' => $id], "Student updated successfully", 200);
        } catch (PDOException $e) {
            sendStudentResponse(false, null, "Update error: " . $e->getMessage(), 500);
        }
    } else {
        sendStudentResponse(true, ['id' => $id], "Student updated (demo)", 200);
    }
}

// 4. DELETE: Delete student
if ($requestMethod === 'DELETE') {
    $id = intval($jsonInput['id'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) sendStudentResponse(false, null, "Valid ID required", 400);

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$id]);
            sendStudentResponse(true, ['id' => $id], "Student deleted successfully", 200);
        } catch (PDOException $e) {
            sendStudentResponse(false, null, "Delete error: " . $e->getMessage(), 500);
        }
    } else {
        sendStudentResponse(true, ['id' => $id], "Student deleted (demo)", 200);
    }
}
