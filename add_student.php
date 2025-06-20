<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

try {
    $stmt = $conn->query("SELECT department_id, department_name FROM Departments ORDER BY department_name ASC");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->query("SELECT semester_id, semester_name FROM Semesters ORDER BY semester_name ASC");
    $semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $departments = $semesters = [];
    $error = 'Database error: ' . $e->getMessage();
    error_log("Fetch Departments/Semesters Error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $roll_number = trim($_POST['roll_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department_id = trim($_POST['department_id'] ?? ''); // Add department_id
    $class_ids = $_POST['class_ids'] ?? [];

    // Validate all fields, including department_id
    if (empty($first_name) || empty($roll_number) || empty($email) || empty($department_id) || empty($class_ids)) {
        $error = 'Please fill in all required fields and select at least one class.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        try {
            // Check for duplicate email or roll number
            $stmt = $conn->prepare("SELECT COUNT(*) FROM Students WHERE email = ? OR roll_number = ?");
            $stmt->execute([$email, $roll_number]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Email or roll number already exists.';
            } else {
                $conn->beginTransaction();

                // Insert into Students, including department_id
                $stmt = $conn->prepare("INSERT INTO Students (first_name, last_name, roll_number, email, department_id, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$first_name, $last_name, $roll_number, $email, $department_id, date('Y-m-d H:i:s')]);
                $student_id = $conn->lastInsertId();

                // Insert into Class_Students (Note: Table name in schema is Class_Students, not Student_Classes)
                $stmt = $conn->prepare("INSERT INTO Class_Students (student_id, class_id) VALUES (?, ?)");
                foreach ($class_ids as $class_id) {
                    $stmt->execute([$student_id, (int)$class_id]);
                }

                $conn->commit();
                $success = 'Student added successfully. The student can now sign up using their email.';
            }
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'Error: ' . $e->getMessage();
            error_log("Add Student Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body {
            background: linear-gradient(135deg, #121212 0%, #1c2526 100%) !important;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #E0E0E0;
        }

        .form-container {
            background: #1c2526;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 500px;
            border: 1px solid #1E88E5;
        }

        .form-control,
        .form-check-input {
            background: #2a2e33;
            border: 1px solid #1E88E5;
            color: #E0E0E0;
        }

        .form-control:focus {
            background: #2a2e33;
            border-color: #42A5F5;
            color: #E0E0E0;
            box-shadow: none;
        }

        .form-check-input:checked {
            background-color: #1E88E5;
            border-color: #1E88E5;
        }

        .btn-primary {
            background-color: #1E88E5;
            border: none;
        }

        .btn-primary:hover {
            background-color: #1565C0;
        }

        .btn-secondary {
            background-color: #6C757D;
            border: none;
        }

        .btn-secondary:hover {
            background-color: #5A6268;
        }

        .stars {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: transparent;
            pointer-events: none;
            z-index: -1;
        }

        .stars::before {
            content: '';
            position: absolute;
            width: 2px;
            height: 2px;
            background: #E0E0E0;
            box-shadow: 100px 50px #E0E0E0, 200px 300px #E0E0E0, 300px 100px #E0E0E0,
                400px 400px #E0E0E0, 500px 200px #E0E0E0, 600px 350px #E0E0E0;
            animation: twinkle 5s infinite;
            opacity: 0.7;
        }

        @keyframes twinkle {

            0%,
            100% {
                opacity: 0.7;
            }

            50% {
                opacity: 0.3;
            }
        }

        #class_ids_container {
            max-height: 150px;
            overflow-y: auto;
            padding: 10px;
            background: #2a2e33;
            border: 1px solid #1E88E5;
            border-radius: 4px;
        }

        .form-check-label {
            color: #E0E0E0;
        }
    </style>
</head>

<body>
    <div class="stars"></div>
    <div class="form-container">
        <h2 class="text-center mb-4">Add Student</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="first_name" class="form-label">First Name</label>
                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="last_name" class="form-label">Last Name</label>
                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label for="roll_number" class="form-label">Roll Number</label>
                <input type="text" class="form-control" id="roll_number" name="roll_number" value="<?php echo htmlspecialchars($_POST['roll_number'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="department_id" class="form-label">Department</label>
                <select class="form-control" id="department_id" name="department_id" onchange="filterClasses()" required>
                    <option value="">Select a department</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo htmlspecialchars($department['department_id']); ?>">
                            <?php echo htmlspecialchars($department['department_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="semester_id" class="form-label">Semester</label>
                <select class="form-control" id="semester_id" name="semester_id" onchange="filterClasses()" required>
                    <option value="">Select a semester</option>
                    <?php foreach ($semesters as $semester): ?>
                        <option value="<?php echo htmlspecialchars($semester['semester_id']); ?>">
                            <?php echo htmlspecialchars($semester['semester_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Classes (Select at least one)</label>
                <div id="class_ids_container">
                    <div id="class_ids">
                        <!-- Classes will be populated dynamically -->
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Add Student</button>
        </form>
        <a href="admin_dashboard.php" class="btn btn-secondary w-100 mt-3">Back to Dashboard</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        let classes = [];
        fetch('get_classes.php')
            .then(response => response.ok ? response.json() : Promise.reject('Failed to load classes'))
            .then(data => {
                classes = data;
                filterClasses();
            })
            .catch(error => {
                console.error('Error fetching classes:', error);
                document.getElementById('class_ids').innerHTML = '<p class="text-muted">Failed to load classes.</p>';
            });

        function filterClasses() {
            const departmentId = document.getElementById('department_id').value;
            const semesterId = document.getElementById('semester_id').value;
            const classContainer = document.getElementById('class_ids');
            classContainer.innerHTML = '';

            if (departmentId && semesterId) {
                const filteredClasses = classes.filter(c =>
                    c.department_id == departmentId && c.semester_id == semesterId
                );
                if (filteredClasses.length === 0) {
                    classContainer.innerHTML = '<p class="text-muted">No classes available for this department and semester.</p>';
                } else {
                    filteredClasses.forEach(c => {
                        const div = document.createElement('div');
                        div.className = 'form-check';
                        div.innerHTML = `
                            <input type="checkbox" class="form-check-input" name="class_ids[]" value="${c.class_id}" id="class_${c.class_id}">
                            <label class="form-check-label" for="class_${c.class_id}">${c.class_name}</label>
                        `;
                        classContainer.appendChild(div);
                    });
                }
            } else {
                classContainer.innerHTML = '<p class="text-muted">Please select a department and semester first.</p>';
            }
        }

        document.querySelector('form').addEventListener('submit', e => {
            if (!document.querySelectorAll('input[name="class_ids[]"]:checked').length) {
                e.preventDefault();
                alert('Please select at least one class.');
            }
        });
    </script>
</body>

</html>