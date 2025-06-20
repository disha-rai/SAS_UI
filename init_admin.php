<?php
require_once 'config.php';

$username = 'DiPuKur';
$password = password_hash('blueGreen', PASSWORD_DEFAULT);

try {
    $stmt = $conn->prepare("SELECT * FROM Admins WHERE username = :username");
    $stmt->execute(['username' => $username]);
    if ($stmt->rowCount() == 0) {
        $stmt = $conn->prepare("INSERT INTO Admins (username, password) VALUES (:username, :password)");
        $stmt->execute([
            'username' => $username,
            'password' => $password
        ]);
        echo "Admin user created: $username";
    } else {
        echo "Admin user already exists.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
