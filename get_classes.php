<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $departmentId = $_GET['department_id'] ?? null;
    $forTeacher = isset($_GET['for_teacher']) && $_GET['for_teacher'] === 'true';

    if ($forTeacher && !$departmentId) {
        echo json_encode([]);
        exit;
    }

    $query = "SELECT c.class_id, c.class_name, c.department_id, c.semester_id, d.department_name, s.semester_name 
              FROM Classes c 
              JOIN Departments d ON c.department_id = d.department_id 
              JOIN Semesters s ON c.semester_id = s.semester_id";
    $params = [];

    if ($forTeacher && $departmentId) {
        $query .= " WHERE c.department_id = :department_id";
        $params = ['department_id' => $departmentId];
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($classes);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
