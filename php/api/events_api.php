<?php
/**
 * StudentHub Portal - Events JSON RESTful API Endpoint (Practical 13)
 * Subject: Web Development Frameworks (ITUE203) - Charusat
 * 
 * Features:
 * - Proper HTTP Method Handling: GET (list/detail), POST (create), PUT (update), DELETE (remove)
 * - Standard HTTP Status Codes: 200 OK, 201 Created, 400 Bad Request, 404 Not Found, 422 Validation Error, 500 Server Error
 * - Centralized Error Handling & Uniform JSON payload structure
 * - Strict server-side validation and sanitization
 * - Fallback simulation mode using data/events.json when MySQL is offline
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

function sendResponse($success, $data = null, $message = '', $statusCode = 200) {
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

// Read raw JSON input or POST form data
$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true) ?: [];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Handle method spoofing from forms or clients
if ($requestMethod === 'POST') {
    if (isset($jsonInput['_method'])) {
        $requestMethod = strtoupper($jsonInput['_method']);
    } elseif (isset($_POST['_method'])) {
        $requestMethod = strtoupper($_POST['_method']);
    } elseif (isset($_GET['action'])) {
        $action = strtolower($_GET['action']);
        if ($action === 'delete') $requestMethod = 'DELETE';
        if ($action === 'update') $requestMethod = 'PUT';
    }
}

$pdo = Database::getConnection();

// -----------------------------------------------------------------------------
// 1. GET: Fetch Events (All or by ID, with search & filter)
// -----------------------------------------------------------------------------
if ($requestMethod === 'GET') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    $q = trim($_GET['q'] ?? '');
    $category = trim($_GET['category'] ?? '');
    $status = trim($_GET['status'] ?? '');

    if ($pdo) {
        try {
            if ($id !== null) {
                $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
                $stmt->execute([$id]);
                $event = $stmt->fetch();
                if ($event) {
                    sendResponse(true, $event, "Event #$id retrieved successfully", 200);
                } else {
                    sendResponse(false, null, "Event with ID #$id was not found", 404);
                }
            } else {
                $where = ["1=1"];
                $params = [];

                if (!empty($q)) {
                    $where[] = "(title LIKE ? OR description LIKE ? OR venue LIKE ?)";
                    $params[] = "%$q%";
                    $params[] = "%$q%";
                    $params[] = "%$q%";
                }

                if (!empty($category) && $category !== 'all') {
                    $where[] = "category = ?";
                    $params[] = $category;
                }

                if (!empty($status) && $status !== 'all') {
                    $where[] = "status = ?";
                    $params[] = $status;
                }

                $sql = "SELECT * FROM events WHERE " . implode(" AND ", $where) . " ORDER BY event_date ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $events = $stmt->fetchAll();

                sendResponse(true, $events, count($events) . " events retrieved", 200);
            }
        } catch (PDOException $e) {
            sendResponse(false, null, "Database query error: " . $e->getMessage(), 500);
        }
    } else {
        // Fallback to data/events.json
        $jsonFile = __DIR__ . '/../../data/events.json';
        $events = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];
        if ($id !== null) {
            foreach ($events as $e) {
                if ($e['id'] == $id) {
                    sendResponse(true, $e, "Event found (offline cache)", 200);
                }
            }
            sendResponse(false, null, "Event not found", 404);
        }

        if (!empty($q)) {
            $events = array_values(array_filter($events, function($e) use ($q) {
                return stripos($e['title'], $q) !== false || stripos($e['description'], $q) !== false;
            }));
        }

        if (!empty($category) && $category !== 'all') {
            $events = array_values(array_filter($events, function($e) use ($category) {
                return $e['category'] === $category;
            }));
        }

        sendResponse(true, $events, count($events) . " events retrieved (cached)", 200);
    }
}

// -----------------------------------------------------------------------------
// 2. POST: Create New Event
// -----------------------------------------------------------------------------
if ($requestMethod === 'POST') {
    $title = trim($jsonInput['title'] ?? ($_POST['title'] ?? ''));
    $description = trim($jsonInput['description'] ?? ($_POST['description'] ?? ''));
    $category = trim($jsonInput['category'] ?? ($_POST['category'] ?? 'Technical'));
    $event_date = trim($jsonInput['event_date'] ?? ($_POST['event_date'] ?? date('Y-m-d')));
    $event_time = trim($jsonInput['event_time'] ?? ($_POST['event_time'] ?? '10:00 AM - 04:00 PM'));
    $venue = trim($jsonInput['venue'] ?? ($_POST['venue'] ?? ''));
    $organizer = trim($jsonInput['organizer'] ?? ($_POST['organizer'] ?? 'StudentHub'));
    $capacity = intval($jsonInput['capacity'] ?? ($_POST['capacity'] ?? 100));
    $poster_image = trim($jsonInput['poster_image'] ?? ($_POST['poster_image'] ?? '../images/StudentHub.png'));
    $status = trim($jsonInput['status'] ?? ($_POST['status'] ?? 'Open'));

    // Validation (422 Unprocessable Entity)
    $errors = [];
    if (empty($title) || strlen($title) < 3) $errors[] = "Title must be at least 3 characters long.";
    if (empty($description)) $errors[] = "Description is required.";
    if (empty($venue)) $errors[] = "Venue location is required.";
    if ($capacity <= 0) $errors[] = "Capacity must be greater than zero.";

    if (!empty($errors)) {
        sendResponse(false, ['errors' => $errors], "Validation failed", 422);
    }

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO events (title, description, category, event_date, event_time, venue, organizer, capacity, poster_image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $category, $event_date, $event_time, $venue, $organizer, $capacity, $poster_image, $status]);
            $newId = $pdo->lastInsertId();

            Database::logAudit(1, 'API_EVENT_CREATE', "Created event '$title' via JSON API (ID #$newId).");
            sendResponse(true, ['id' => $newId, 'title' => $title], "Event created successfully", 201);
        } catch (PDOException $e) {
            sendResponse(false, null, "Database error: " . $e->getMessage(), 500);
        }
    } else {
        // Mock success response
        $mockId = rand(100, 999);
        sendResponse(true, ['id' => $mockId, 'title' => $title], "Event created successfully (demo)", 201);
    }
}

// -----------------------------------------------------------------------------
// 3. PUT: Update Existing Event
// -----------------------------------------------------------------------------
if ($requestMethod === 'PUT') {
    $id = intval($jsonInput['id'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) {
        sendResponse(false, null, "Valid event ID is required for update", 400);
    }

    $title = trim($jsonInput['title'] ?? '');
    $description = trim($jsonInput['description'] ?? '');
    $category = trim($jsonInput['category'] ?? 'Technical');
    $event_date = trim($jsonInput['event_date'] ?? date('Y-m-d'));
    $event_time = trim($jsonInput['event_time'] ?? '10:00 AM');
    $venue = trim($jsonInput['venue'] ?? '');
    $capacity = intval($jsonInput['capacity'] ?? 100);
    $status = trim($jsonInput['status'] ?? 'Open');

    if (empty($title)) {
        sendResponse(false, null, "Title cannot be empty", 422);
    }

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE events SET title=?, description=?, category=?, event_date=?, event_time=?, venue=?, capacity=?, status=? WHERE id=?");
            $stmt->execute([$title, $description, $category, $event_date, $event_time, $venue, $capacity, $status, $id]);

            Database::logAudit(1, 'API_EVENT_UPDATE', "Updated event ID #$id via JSON API.");
            sendResponse(true, ['id' => $id, 'title' => $title], "Event updated successfully", 200);
        } catch (PDOException $e) {
            sendResponse(false, null, "Database update error: " . $e->getMessage(), 500);
        }
    } else {
        sendResponse(true, ['id' => $id, 'title' => $title], "Event updated successfully (demo)", 200);
    }
}

// -----------------------------------------------------------------------------
// 4. DELETE: Remove Event
// -----------------------------------------------------------------------------
if ($requestMethod === 'DELETE') {
    $id = intval($jsonInput['id'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) {
        sendResponse(false, null, "Valid event ID is required for deletion", 400);
    }

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$id]);

            Database::logAudit(1, 'API_EVENT_DELETE', "Deleted event ID #$id via JSON API.");
            sendResponse(true, ['id' => $id], "Event #$id deleted successfully", 200);
        } catch (PDOException $e) {
            sendResponse(false, null, "Delete operation failed: " . $e->getMessage(), 500);
        }
    } else {
        sendResponse(true, ['id' => $id], "Event #$id deleted successfully (demo)", 200);
    }
}

// Method not allowed
sendResponse(false, null, "Method $requestMethod not allowed", 405);
