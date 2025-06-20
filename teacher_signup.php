<?php
session_start();
require_once 'config.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/xampp/htdocs/smart_attendance/php_errors.log');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }

    $step = $_POST['step'] ?? 'details';

    if ($step === 'details') {
        $data = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'email' => trim($_POST['email'] ?? '')
        ];

        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email'])) {
            throw new Exception('Please fill in all required fields', 400);
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format', 400);
        }

        error_log("Data for teacher verification: " . json_encode($data));

        try {
            $stmt = $conn->prepare("SELECT teacher_id FROM Teachers WHERE email = :email");
            $stmt->execute(['email' => $data['email']]);
            $teacher = $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Database error in verifying teacher details: " . $e->getMessage(), 500);
        }

        if (!$teacher) {
            throw new Exception('Details not found. Please contact the admin to register.', 401);
        }

        $otp = sprintf("%06d", mt_rand(0, 999999));
        $created_at = date('Y-m-d H:i:s');
        $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        try {
            $conn->prepare("DELETE FROM Otps WHERE email = ?")->execute([$data['email']]);
        } catch (PDOException $e) {
            throw new Exception("Database error in deleting old OTPs: " . $e->getMessage(), 500);
        }

        try {
            $conn->prepare("INSERT INTO Otps (email, otp, created_at, expires_at, used) VALUES (?, ?, ?, ?, 0)")
                ->execute([$data['email'], $otp, $created_at, $expires_at]);
        } catch (PDOException $e) {
            throw new Exception("Database error in inserting new OTP: " . $e->getMessage(), 500);
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'erm.foreg@gmail.com';
        $mail->Password = 'fnls zcbx igdw kfxk';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->setFrom('erm.foreg@gmail.com', 'Smart Attendance System');
        $mail->addAddress($data['email']);
        $mail->Subject = 'Smart Attendance System OTP';
        $mail->Body = "Your OTP is: $otp. It is valid for 10 minutes.";
        $mail->send();

        $_SESSION['signup_data'] = array_merge($data, ['teacher_id' => $teacher['teacher_id']]);

        echo json_encode(['status' => 'success', 'message' => 'OTP sent to your email', 'nextStep' => 'otp']);
    } elseif ($step === 'otp') {
        $otp = trim($_POST['otp'] ?? '');
        $email = $_SESSION['signup_data']['email'] ?? '';

        if (empty($otp) || empty($email)) {
            throw new Exception(empty($otp) ? 'Please enter the OTP' : 'Session expired. Please start over.', 400);
        }

        $stmt = $conn->prepare("SELECT id, expires_at, used FROM Otps WHERE email = ? AND otp = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$email, $otp]);
        $otp_record = $stmt->fetch();

        if (!$otp_record || $otp_record['used'] == 1 || date('Y-m-d H:i:s') > $otp_record['expires_at']) {
            throw new Exception(
                !$otp_record ? 'Invalid OTP' : ($otp_record['used'] == 1 ? 'This OTP has already been used' : 'OTP has expired. Please request a new one.'),
                401
            );
        }

        $conn->prepare("UPDATE Otps SET used = 1 WHERE id = ?")->execute([$otp_record['id']]);

        echo json_encode(['status' => 'success', 'message' => 'OTP verified successfully', 'nextStep' => 'password']);
    } elseif ($step === 'password') {
        $password = trim($_POST['password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $teacher_id = $_SESSION['signup_data']['teacher_id'] ?? '';
        $email = $_SESSION['signup_data']['email'] ?? '';

        if (empty($password) || empty($confirm_password) || empty($username) || empty($teacher_id) || empty($email)) {
            throw new Exception('Please fill in all fields' . (empty($teacher_id) || empty($email) ? ' or session expired. Please start over.' : ''), 400);
        }

        if ($password !== $confirm_password || strlen($password) < 6) {
            throw new Exception($password !== $confirm_password ? 'Passwords do not match' : 'Password must be at least 6 characters long', 400);
        }

        $stmt = $conn->prepare("SELECT COUNT(*) FROM Teachers WHERE teacher_id = ?");
        $stmt->execute([$teacher_id]);
        if ($stmt->fetchColumn() == 0) {
            throw new Exception('Invalid teacher ID. Please start over.', 400);
        }

        $stmt = $conn->prepare("SELECT id FROM Teacher_Accounts WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('Username or email already exists', 400);
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $conn->prepare("INSERT INTO Teacher_Accounts (teacher_id, email, username, password, verified) VALUES (?, ?, ?, ?, 1)")
            ->execute([$teacher_id, $email, $username, $hashed_password]);

        unset($_SESSION['signup_data']);

        echo json_encode(['status' => 'success', 'message' => 'Account created successfully', 'nextStep' => 'complete']);
    } else {
        throw new Exception('Invalid step', 400);
    }
} catch (Exception $e) {
    unset($_SESSION['signup_data']);
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    unset($_SESSION['signup_data']);
    $error_message = "Database error: " . $e->getMessage();
    error_log($error_message);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $error_message]);
}
