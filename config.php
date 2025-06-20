<?php
$host = 'localhost';
$db = 'attendance_system';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    throw new PDOException("Connection failed");
}

define('FLASK_SCAN_URL', 'https://bbec-2409-40e1-314a-e9a6-ec0b-462b-6954-82b4.ngrok-free.app');
