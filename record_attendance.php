<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$class_id = $data['class_id'] ?? null;
$student_id = $data['student_id'] ?? null;
$status = $data['status'] ?? 'present';

if (!$class_id || !$student_id) {
    echo json_encode(['success' => false, 'message' => 'Missing class_id or student_id']);
    exit;
}

try {
    // For now, assuming semester_id is 1; adjust as needed
    $stmt = $conn->prepare("INSERT INTO attendance (class_id, student_id, semester_id, attendance_date, status, created_at) VALUES (:class_id, :student_id, 1, CURDATE(), :status, NOW())");
    $stmt->execute(['class_id' => $class_id, 'student_id' => $student_id, 'status' => $status]);
    echo json_encode(['success' => true, 'message' => 'Attendance recorded in PHP database']);
} catch (PDOException $e) {
    error_log("Database error in record_attendance.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
