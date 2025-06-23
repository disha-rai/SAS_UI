<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['teacher_id'])) {
    header('Location: teacher_login.php');
    exit;
}

try {
    // Fetch teacher details
    $stmt = $conn->prepare("
        SELECT t.teacher_id, t.first_name, t.last_name, t.email
        FROM Teachers t
        WHERE t.teacher_id = :teacher_id
    ");
    $stmt->execute(['teacher_id' => $_SESSION['teacher_id']]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        throw new Exception('Teacher not found.');
    }

    // Fetch classes with attendance stats
    $stmt = $conn->prepare("
        SELECT 
            c.class_id, 
            c.class_name,
            c.department_id,
            c.semester_id,
            (SELECT COUNT(*) FROM Class_Students WHERE class_id = c.class_id) AS total_students,
            (SELECT COUNT(*) FROM attendance 
             WHERE class_id = c.class_id 
             AND attendance_date = CURDATE() 
             AND status = 'present') AS present_today,
            (SELECT COUNT(*) FROM attendance 
             WHERE class_id = c.class_id 
             AND attendance_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY) 
             AND status = 'present') AS present_yesterday
        FROM Classes c
        INNER JOIN teacher_classes tc ON c.class_id = tc.class_id
        WHERE tc.teacher_id = :teacher_id
        ORDER BY c.class_name
    ");
    $stmt->execute(['teacher_id' => $_SESSION['teacher_id']]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    error_log("Database error in teacher_dashboard.php: " . $e->getMessage());
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Error in teacher_dashboard.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - SAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>
    <style>
        body {
            background-color: #E0E0E0;
            font-family: 'Georgia', serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
        }

        .container-fluid {
            border: 1px solid #E0E0E0;
            height: 100vh;
            display: flex;
            padding: 0;
        }

        .left-sidebar {
            width: 200px;
            background-color: #F5F5F5;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        .sas-logo {
            font-family: 'Times New Roman', serif;
            font-size: 3rem;
            font-weight: bold;
            color: #212121;
            letter-spacing: 2px;
            margin-bottom: 20px;
        }

        .star-container {
            position: relative;
            width: 90%;
            height: 270px;
        }

        .star-graphic {
            position: absolute;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, #FFFFFF 10%, #E0E0E0 70%, transparent 100%);
            clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
            left: 50%;
            transform: translateX(-50%);
            top: 147px;
        }

        .star-graphic::before {
            content: '';
            position: absolute;
            width: 80px;
            height: 80px;
            top: 10px;
            left: 10px;
            background: radial-gradient(circle, #FFFFFF 10%, #E0E0E0 70%, transparent 100%);
            clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
            opacity: 0.7;
        }

        .nav-icons {
            position: absolute;
            top: 140px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            width: 100%;
            align-items: center;
        }

        .nav-icons i {
            color: #616161;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .nav-icons i:hover {
            color: #212121;
        }

        .main-content {
            flex-grow: 1;
            background-color: #1A237E;
            padding: 20px;
            color: #FFFFFF;
            position: relative;
            overflow-y: auto;
        }

        .main-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(ellipse at top left, rgba(255, 255, 255, 0.2) 0%, transparent 50%),
                radial-gradient(ellipse at bottom right, rgba(255, 255, 255, 0.15) 0%, transparent 60%),
                radial-gradient(ellipse at center, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            opacity: 0.5;
            z-index: 0;
        }

        .main-content::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 10% 20%, rgba(255, 255, 255, 0.8) 2px, transparent 3px),
                radial-gradient(circle at 80% 30%, rgba(255, 255, 255, 0.6) 1px, transparent 2px),
                radial-gradient(circle at 50% 70%, rgba(255, 255, 255, 0.7) 1.5px, transparent 2.5px),
                radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.5) 1px, transparent 2px),
                radial-gradient(circle at 90% 90%, rgba(255, 255, 255, 0.6) 1.2px, transparent 2px);
            opacity: 0.5;
            z-index: 0;
        }

        .main-content>* {
            position: relative;
            z-index: 1;
        }

        .top-bar {
            position: absolute;
            top: 0;
            right: 0;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .top-bar i {
            color: #212121;
            font-size: 1.2rem;
            cursor: pointer;
        }

        .top-bar i:hover {
            color: #616161;
        }

        .profile-pic {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #E0E0E0;
        }

        .greeting {
            font-family: 'Times New Roman', serif;
            font-size: 2.5rem;
            font-weight: bold;
            margin-top: 50px;
            margin-bottom: 20px;
        }

        .contact-box {
            background-color: #E0E0E0;
            color: #616161;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }

        .classes-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .classes-header h2 {
            font-family: 'Times New Roman', serif;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .date-button {
            background-color: #E0E0E0;
            color: #212121;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .classes-section {
            background-color: #283593;
            padding: 20px;
            border-radius: 10px;
        }

        .class-item {
            background-color: #3949AB;
            padding: 15px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .class-item:last-child {
            margin-bottom: 0;
        }

        .class-details {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .class-name {
            font-weight: bold;
        }

        .qr-btn {
            background-color: #0D6EFD;
            color: #FFFFFF;
            padding: 5px 15px;
            border-radius: 20px;
            text-decoration: none;
            cursor: pointer;
        }

        .qr-btn:hover {
            background-color: #0B5ED7;
        }

        .qr-section {
            display: none;
            margin-top: 10px;
            padding: 10px;
            background-color: #3949AB;
            border-radius: 10px;
            text-align: center;
        }

        .qr-image {
            max-width: 200px;
            margin: 10px auto;
            display: block;
        }

        .qr-message {
            font-weight: bold;
            margin-top: 10px;
            color: #FFFFFF;
        }

        .timer {
            font-size: 1.2rem;
            margin-top: 10px;
            color: #FFD700;
            font-weight: bold;
        }

        .attendance-stats {
            display: flex;
            gap: 10px;
            margin-top: 8px;
        }

        .attendance-stats span {
            background: rgba(255, 255, 255, 0.1);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.85rem;
        }

        @media (max-width: 768px) {
            .container-fluid {
                flex-direction: column;
                height: auto;
            }

            .left-sidebar,
            .right-sidebar {
                width: 100%;
                height: auto;
            }

            .main-content {
                width: 100%;
            }

            .star-container {
                height: 200px;
            }

            .nav-icons {
                top: 100px;
            }

            .star-graphic {
                top: 100px;
            }

            .todo-box,
            .date-box {
                width: 100%;
            }
        }

        .btn-logout {
            position: fixed;
            bottom: 20px;
            left: 20px;
            padding: 10px 20px;
            background-color: #ff4d4d;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-logout:hover {
            background-color: #e60000;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="left-sidebar">
            <div class="sas-logo">SAS</div>
            <div class="star-container">
                <div class="star-graphic"></div>
                <div class="nav-icons">
                    <i class="fas fa-home"></i>
                    <i class="fas fa-clock"></i>
                    <i class="fas fa-calendar"></i>
                    <i class="fas fa-copy"></i>
                    <i class="fas fa-star"></i>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="top-bar">
                <i class="fas fa-user"></i>
                <i class="fas fa-gear"></i>
                <i class="fas fa-bell"></i>
                <div class="profile-pic"></div>
            </div>
            <h1 class="greeting">Hello!!</h1>
            <div class="contact-box">
                <p>CONTACT DETAILS OF <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($teacher['email']); ?></p>
            </div>
            <div class="classes-header">
                <h2>CLASSES for <?php echo htmlspecialchars($teacher['first_name']); ?></h2>
                <span class="date-button"><?php echo date('F j, Y'); ?></span>
            </div>
            <div class="classes-section">
                <?php if (empty($classes)): ?>
                    <p>No classes assigned. Please contact your admin. <a href="mailto:admin@example.com" class="btn btn-primary btn-sm">Contact Admin</a></p>
                <?php else: ?>
                    <?php foreach ($classes as $class): ?>
                        <div class="class-item">
                            <div class="class-details">
                                <span class="class-name"><?= htmlspecialchars($class['class_name']) ?></span>
                                <div class="attendance-stats">
                                    <span>Total: <?= $class['total_students'] ?? 0 ?></span>
                                    <span>Today: <?= $class['present_today'] ?? 0 ?></span>
                                    <span>Yesterday: <?= $class['present_yesterday'] ?? 0 ?></span>
                                </div>
                            </div>
                            <button class="qr-btn" onclick="generateQR(<?= $class['class_id'] ?>)">Generate QR Code</button>
                            <div id="qr-section-<?= $class['class_id'] ?>" class="qr-section">
                                <div id="qr-container-<?= $class['class_id'] ?>" class="qr-image"></div>
                                <p id="timer-<?= $class['class_id'] ?>" class="timer">Valid for: 5:00</p>
                                <p id="qr-message-<?= $class['class_id'] ?>" class="qr-message">Scan with student devices</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button class="btn-logout" onclick="window.location.href='logout.php'">Logout</button>
        </div>

        <div class="right-sidebar">
            <div class="date-box">
                <span class="month"><?php echo date('F'); ?></span>
                <span class="day"><?php echo date('l'); ?></span>
                <span class="dates"><?php echo date('j'); ?></span>
            </div>
            <div class="todo-box">
                <h3>TO DO!</h3>
                <div class="todo-input">
                    <input type="text" id="todo-input" placeholder="Add a task...">
                    <button onclick="addTodo()">Add</button>
                </div>
                <div id="todo-list"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const flaskBaseUrl = 'http://192.168.168.232:5000'; // Specific IP and Flask port
        let activeTimers = {};

        function generateQR(classId) {
            if (isNaN(classId)) {
                console.error('Invalid classId:', classId);
                return;
            }
            const qrSection = document.getElementById(`qr-section-${classId}`);
            const message = document.getElementById(`qr-message-${classId}`);
            const timer = document.getElementById(`timer-${classId}`);
            qrSection.style.display = 'block';
            message.textContent = 'Generating QR code...';
            timer.textContent = 'Valid for: 5:00';

            console.log('Fetching from:', `${flaskBaseUrl}/generate-token/${classId}`);
            fetch(`${flaskBaseUrl}/generate-token/${classId}`, {
                    mode: 'cors',
                    credentials: 'omit'
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Parsed data:', data);
                    if (!data.qr_data) {
                        throw new Error('No QR data received');
                    }
                    const qrContainer = document.getElementById(`qr-container-${classId}`);
                    try {
                        if (typeof QRCode === 'undefined') {
                            throw new Error('QRCode library not loaded');
                        }
                        new QRCode(qrContainer, {
                            text: data.qr_data,
                            width: 200,
                            height: 200
                        });
                        console.log('QRCode generated with library');
                    } catch (qrError) {
                        console.error('QRCode library failed:', qrError);
                        qrContainer.innerHTML = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(data.qr_data)}">`;
                        console.log('Fallback QR image generated');
                    }
                    message.textContent = 'Scan with student devices';
                    startTimer(classId, data.expires_at);
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    message.textContent = `QR Generation Failed: ${error.message}`;
                    timer.textContent = 'Error occurred';
                });
        }

        function startTimer(classId, expiresAt) {
            const timer = document.getElementById(`timer-${classId}`);
            const message = document.getElementById(`qr-message-${classId}`);
            activeTimers[classId] = setInterval(() => {
                const now = new Date();
                const expiry = new Date(expiresAt);
                const timeLeft = Math.max(0, Math.floor((expiry - now) / 1000));
                const mins = Math.floor(timeLeft / 60);
                const secs = timeLeft % 60;
                timer.textContent = `Valid for: ${mins}:${secs < 10 ? '0' : ''}${secs}`;
                if (timeLeft <= 0) {
                    clearInterval(activeTimers[classId]);
                    timer.textContent = 'QR Expired!';
                    message.textContent = 'Generating new QR...';
                    generateQR(classId);
                }
            }, 1000);
        }

        function submitAttendance(classId, qrData) {
            const data = {
                qr_data: qrData,
                student_id: 'test_student',
                class_id: classId
            };
            console.log('Submitting:', data);
            fetch(`${flaskBaseUrl}/mark_attendance`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => {
                    console.log('Submit status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(result => {
                    console.log('Submit result:', result);
                    alert('You are marked: ' + result.message);
                })
                .catch(error => {
                    console.error('Submit Error:', error);
                    alert('Failed to mark attendance: ' + error.message);
                });
        }

        document.querySelectorAll('.qr-section').forEach(section => {
            section.addEventListener('click', () => {
                const classId = section.id.split('-')[2];
                const qrData = section.querySelector('.qr-image img')?.src.split('data=')[1] || 'xv_R7MQEnhEfDy-oUoZ82A';
                submitAttendance(classId, decodeURIComponent(qrData));
            });
        });

        function addTodo() {
            const input = document.getElementById('todo-input');
            const todoList = document.getElementById('todo-list');
            if (input.value.trim()) {
                const li = document.createElement('li');
                li.textContent = input.value;
                todoList.appendChild(li);
                input.value = '';
            }
        }
    </script>
</body>

</html>
