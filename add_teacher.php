<?php
header('Content-Type: application/json');
require_once 'config.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
error_log("Received data: " . json_encode($data)); // Debug log
$firstName = $data['firstName'] ?? '';
$lastName = $data['lastName'] ?? '';
$email = $data['email'] ?? '';
$departmentId = $data['departmentId'] ?? '';
$classIds = $data['classIds'] ?? [];

if (empty($firstName) || empty($lastName) || empty($email) || empty($departmentId)) {
    http_response_code(400);
    echo json_encode(['message' => 'Please fill in all required fields']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid email format or length exceeds 255 characters']);
    exit;
}

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("INSERT INTO Teachers (first_name, last_name, email, department_id, created_at) VALUES (:first_name, :last_name, :email, :department_id, :created_at)");
    $stmt->execute([
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'department_id' => $departmentId,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    $teacherId = $conn->lastInsertId();

    if (!empty($classIds) && is_array($classIds)) {
        $stmt = $conn->prepare("INSERT INTO teacher_classes (teacher_id, class_id) VALUES (:teacher_id, :class_id)");
        foreach ($classIds as $classId) {
            $stmt->execute(['teacher_id' => $teacherId, 'class_id' => $classId]);
        }
    }

    $conn->commit();
    echo json_encode(['message' => 'Teacher added successfully with selected classes. They can now sign up using their email.']);
} catch (PDOException $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(['message' => 'Server error: ' . $e->getMessage()]);
}
