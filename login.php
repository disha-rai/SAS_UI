<?php
// Ensure no errors are displayed
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
ob_start(); // Start output buffering
require_once 'config.php';
session_start();

// Check for any output before JSON
if (ob_get_length()) {
    error_log("Unexpected output in login.php: " . ob_get_contents());
    ob_end_clean();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';
$type = $data['type'] ?? '';

// Sanitize inputs
$username = filter_var($username, FILTER_SANITIZE_STRING);
$type = filter_var($type, FILTER_SANITIZE_STRING);

if (empty($username) || empty($password) || empty($type)) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['message' => 'Please fill in all fields']);
    exit;
}

try {
    if ($type === 'admin') {
        $stmt = $conn->prepare("SELECT admin_id, password FROM Admins WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $redirect = 'admin_dashboard.php';
        $session_key = 'admin_id';
    } elseif ($type === 'student') {
        $stmt = $conn->prepare("SELECT student_id, password, verified FROM Student_Accounts WHERE email = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $redirect = 'student_dashboard.php';
        $session_key = 'student_id';

        if ($user && $user['verified'] == 0) {
            ob_end_clean();
            http_response_code(403);
            echo json_encode(['message' => 'Account not verified. Please complete the signup process.']);
            exit;
        }
    } elseif ($type === 'teacher') {
        $stmt = $conn->prepare("SELECT teacher_id, password, verified FROM Teacher_Accounts WHERE email = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $redirect = 'teacher_dashboard.php';
        $session_key = 'teacher_id';

        if ($user && $user['verified'] == 0) {
            ob_end_clean();
            http_response_code(403);
            echo json_encode(['message' => 'Account not verified. Please complete the signup process.']);
            exit;
        }
    } else {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['message' => 'Invalid user type']);
        exit;
    }

    if (!$user || !password_verify($password, $user['password'])) {
        ob_end_clean();
        http_response_code(401);
        echo json_encode(['message' => 'Invalid credentials']);
        exit;
    }

    $_SESSION[$session_key] = $user[$type === 'admin' ? 'admin_id' : ($type === 'teacher' ? 'teacher_id' : 'student_id')];
    ob_end_clean();
    echo json_encode(['message' => 'Login successful', 'redirect' => $redirect]);
} catch (PDOException $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
    error_log("Database error in login.php: " . $e->getMessage());
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['message' => 'Server error: ' . $e->getMessage()]);
    error_log("General error in login.php: " . $e->getMessage());
}
