<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['student_id'])) {
    error_log("Student Dashboard - No student_id in session, redirecting to index.php");
    header('Location: index.php');
    exit;
}

try {
    // Fetch student details
    $stmt = $conn->prepare("
        SELECT s.student_id, s.first_name, s.last_name, s.roll_number, s.email
        FROM Students s 
        JOIN Student_Accounts sa ON s.student_id = sa.student_id 
        WHERE sa.student_id = :student_id
    ");
    $stmt->execute(['student_id' => $_SESSION['student_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        throw new Exception('Student not found.');
    }

    // Fetch student's classes
    $stmt = $conn->prepare("
        SELECT c.class_id, c.class_name
        FROM Classes c 
        JOIN Class_Students cs ON c.class_id = cs.class_id 
        WHERE cs.student_id = :student_id
    ");
    $stmt->execute(['student_id' => $_SESSION['student_id']]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Number of classes fetched for student_id " . $_SESSION['student_id'] . ": " . count($classes));
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    error_log("Database error in student_dashboard.php: " . $e->getMessage());
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Error in student_dashboard.php: " . $e->getMessage());
}

// Handle AJAX status messages
$message = '';
if (isset($_GET['status'])) {
    $message = $_GET['status'] === 'success' ? 'Attendance marked successfully!' : 'Failed to mark attendance.';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - SAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
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

        .attendance-btn {
            background-color: #0D6EFD;
            color: #FFFFFF;
            padding: 5px 15px;
            border-radius: 20px;
            text-decoration: none;
            cursor: pointer;
        }

        .attendance-btn:hover {
            background-color: #0B5ED7;
        }

        .status-message {
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            display: <?php echo $message ? 'block' : 'none'; ?>;
            color: <?php echo $message === 'Attendance marked successfully!' ? '#28a745' : '#dc3545'; ?>;
            background-color: <?php echo $message === 'Attendance marked successfully!' ? '#d4edda' : '#f8d7da'; ?>;
        }

        #qr-reader {
            width: 100%;
            max-width: 500px;
            height: 300px;
            margin: 20px auto;
            display: none;
        }

        #scan-message {
            font-weight: bold;
            margin-top: 10px;
            color: #FFFFFF;
        }

        #manual-input {
            margin-top: 10px;
            display: none;
        }

        #toggle-manual,
        #start-scan,
        #stop-scan {
            margin-top: 10px;
        }

        #stop-scan {
            display: none;
        }

        .scanner-section {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background-color: #3949AB;
            border-radius: 10px;
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
                <p>CONTACT DETAILS OF <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                <p><strong>Roll Number:</strong> <?php echo htmlspecialchars($student['roll_number']); ?></p>
            </div>
            <div class="classes-header">
                <h2>CLASSES for <?php echo htmlspecialchars($student['first_name']); ?></h2>
                <span class="date-button"><?php echo date('F j, Y'); ?></span>
            </div>
            <div class="classes-section">
                <?php if (empty($classes)): ?>
                    <p>No classes scheduled. Please contact your admin to enroll in classes. <a href="mailto:admin@example.com" class="btn btn-primary btn-sm">Contact Admin</a></p>
                <?php else: ?>
                    <?php foreach ($classes as $index => $class): ?>
                        <div class="class-item">
                            <div class="class-details">
                                <span class="class-name"><?php echo htmlspecialchars($class['class_name']); ?></span>
                            </div>
                            <button class="attendance-btn" onclick="startScanning(<?php echo $class['class_id']; ?>)">Mark Attendance</button>
                        </div>
                        <div id="scanner-<?php echo $class['class_id']; ?>" class="scanner-section">
                            <div id="qr-reader-<?php echo $class['class_id']; ?>" class="qr-reader"></div>
                            <div id="manual-input-<?php echo $class['class_id']; ?>" class="manual-input">
                                <input type="text" id="qr-input-<?php echo $class['class_id']; ?>" class="form-control" placeholder="Paste QR data here">
                                <button id="submit-manual-<?php echo $class['class_id']; ?>" class="btn btn-primary mt-2">Submit QR</button>
                            </div>
                            <button id="start-scan-<?php echo $class['class_id']; ?>" class="btn btn-success mt-2">Start Scanning</button>
                            <button id="stop-scan-<?php echo $class['class_id']; ?>" class="btn btn-danger mt-2">Stop Scanning</button>
                            <button id="toggle-manual-<?php echo $class['class_id']; ?>" class="btn btn-secondary mt-2">Switch to Manual Input</button>
                            <p id="scan-message-<?php echo $class['class_id']; ?>" class="scan-message"></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="status-message"><?php echo htmlspecialchars($message); ?></div>
            </div>
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
            <button class="btn btn-logout" onclick="window.location.href='logout.php'">Logout</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        let scanners = {};
        const baseUrl = '<?php echo FLASK_SCAN_URL; ?>';
        const studentId = <?php echo json_encode($_SESSION['student_id']); ?>;
        let retryCounts = {};

        function startScanning(classId) {
            document.querySelectorAll('.scanner-section').forEach(section => section.style.display = 'none');
            const scannerSection = document.getElementById(`scanner-${classId}`);
            scannerSection.style.display = 'block';
            if (!scanners[classId]) {
                initScanner(classId);
            }
            document.getElementById(`start-scan-${classId}`).click();
        }

        function initScanner(classId) {
            const qrReader = document.getElementById(`qr-reader-${classId}`);
            scanners[classId] = new Html5QrcodeScanner(
                `qr-reader-${classId}`, {
                    fps: 5,
                    qrbox: {
                        width: 250,
                        height: 250
                    },
                    facingMode: "environment",
                    disableFlip: false
                },
                false
            );

            document.getElementById(`start-scan-${classId}`).addEventListener('click', () => {
                if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                    qrReader.style.display = 'block';
                    document.getElementById(`start-scan-${classId}`).style.display = 'none';
                    document.getElementById(`stop-scan-${classId}`).style.display = 'block';
                    scanners[classId].render(
                        (decodedText) => onScanSuccess(decodedText, classId),
                        (error) => onScanFailure(error, classId)
                    );
                    console.log(`Scanner started for class ${classId}`);
                } else {
                    document.getElementById(`scan-message-${classId}`).textContent = 'Camera not supported. Use manual input.';
                    document.getElementById(`scan-message-${classId}`).style.color = 'red';
                    showManualInput(classId);
                }
            });

            document.getElementById(`stop-scan-${classId}`).addEventListener('click', () => {
                stopScanner(classId);
            });

            document.getElementById(`toggle-manual-${classId}`).addEventListener('click', () => {
                showManualInput(classId);
            });

            document.getElementById(`submit-manual-${classId}`).addEventListener('click', () => {
                const qrData = document.getElementById(`qr-input-${classId}`).value.trim();
                if (qrData) {
                    submitAttendance(qrData, classId);
                } else {
                    document.getElementById(`scan-message-${classId}`).textContent = 'Please paste a QR code first!';
                    document.getElementById(`scan-message-${classId}`).style.color = 'red';
                }
            });
        }

        function stopScanner(classId) {
            if (scanners[classId]) {
                scanners[classId].clear();
                document.getElementById(`qr-reader-${classId}`).style.display = 'none';
                document.getElementById(`start-scan-${classId}`).style.display = 'block';
                document.getElementById(`stop-scan-${classId}`).style.display = 'none';
                retryCounts[classId] = 0;
                console.log(`Scanner stopped for class ${classId}`);
            }
        }

        function onScanSuccess(decodedText, classId) {
            console.log(`Scan successful for class ${classId}: ${decodedText}`);
            retryCounts[classId] = 0;
            stopScanner(classId);
            submitAttendance(decodedText, classId);
        }

        function onScanFailure(error, classId) {
            console.error(`Scan failed for class ${classId}: ${error}`);
            const messageEl = document.getElementById(`scan-message-${classId}`);
            retryCounts[classId] = (retryCounts[classId] || 0) + 1;
            const maxRetries = 3;
            if (retryCounts[classId] <= maxRetries) {
                if (error.includes("secure context") || error.includes("NotAllowedError")) {
                    messageEl.textContent = 'Camera access denied. Use manual input or grant permissions.';
                    messageEl.style.color = 'red';
                    stopScanner(classId);
                    showManualInput(classId);
                } else {
                    messageEl.textContent = `Scan failed (${retryCounts[classId]}/${maxRetries}): ${error}. Retrying...`;
                    messageEl.style.color = 'red';
                    setTimeout(() => {
                        document.getElementById(`start-scan-${classId}`).click();
                    }, 2000);
                }
            } else {
                messageEl.textContent = 'Scan failed after multiple attempts. Switching to manual input.';
                messageEl.style.color = 'red';
                stopScanner(classId);
                showManualInput(classId);
            }
        }

        function submitAttendance(qrData, classId) {
            console.log(`Submitting attendance for class ${classId}: ${qrData}`);
            fetch(`${baseUrl}/mark_attendance`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        qr_data: qrData,
                        class_id: classId,
                        student_id: studentId
                    })
                })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    const messageEl = document.getElementById(`scan-message-${classId}`);
                    messageEl.textContent = data.message;
                    messageEl.style.color = data.success ? 'green' : 'red';
                    document.getElementById(`scanner-${classId}`).style.display = data.success ? 'none' : 'block';
                })
                .catch(error => {
                    console.error(`Fetch error for class ${classId}: ${error}`);
                    document.getElementById(`scan-message-${classId}`).textContent = `Error: ${error.message}`;
                    document.getElementById(`scan-message-${classId}`).style.color = 'red';
                    showManualInput(classId);
                });
        }

        function showManualInput(classId) {
            document.getElementById(`manual-input-${classId}`).style.display = 'block';
            document.getElementById(`toggle-manual-${classId}`).style.display = 'none';
            stopScanner(classId);
        }

        function loadTodos() {
            const todos = JSON.parse(localStorage.getItem('todos')) || [];
            const todoList = document.getElementById('todo-list');
            todoList.innerHTML = '';
            todos.forEach((todo, index) => {
                const div = document.createElement('div');
                div.className = 'todo-item';
                div.innerHTML = `
                    <input type="checkbox" id="todo${index}" ${todo.completed ? 'checked' : ''}>
                    <label for="todo${index}">${todo.text}</label>
                    <button class="remove-todo-btn" onclick="removeTodo(${index})">-</button>
                `;
                const checkbox = div.querySelector(`#todo${index}`);
                checkbox.addEventListener('change', () => {
                    todos[index].completed = checkbox.checked;
                    localStorage.setItem('todos', JSON.stringify(todos));
                });
                todoList.appendChild(div);
            });
        }

        function addTodo() {
            const input = document.getElementById('todo-input');
            const text = input.value.trim();
            if (text) {
                const todos = JSON.parse(localStorage.getItem('todos')) || [];
                todos.push({
                    text,
                    completed: false
                });
                localStorage.setItem('todos', JSON.stringify(todos));
                input.value = '';
                loadTodos();
            }
        }

        function removeTodo(index) {
            const todos = JSON.parse(localStorage.getItem('todos')) || [];
            todos.splice(index, 1);
            localStorage.setItem('todos', JSON.stringify(todos));
            loadTodos();
        }

        loadTodos();
    </script>
</body>

</html>