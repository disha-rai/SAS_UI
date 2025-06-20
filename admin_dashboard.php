<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Attendance System - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #121212 0%, #1c2526 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #E0E0E0;
        }

        .dashboard-container {
            max-width: 800px;
            margin: 2rem auto;
            background: #1c2526;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            border: 1px solid #1E88E5;
            position: relative;
        }

        .nav-tabs .nav-link {
            color: #E0E0E0;
            border: none;
            border-bottom: 2px solid transparent;
        }

        .nav-tabs .nav-link.active {
            color: #1E88E5;
            border-bottom: 2px solid #1E88E5;
            background: #2a2e33;
        }

        .nav-tabs .nav-link:hover {
            color: #42A5F5;
            border-bottom: 2px solid #42A5F5;
        }

        .form-control {
            background: #2a2e33;
            border: 1px solid #1E88E5;
            color: #E0E0E0;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            background: #2a2e33;
            border-color: #42A5F5;
            color: #E0E0E0;
            box-shadow: none;
        }

        .btn-primary {
            background-color: #1E88E5;
            border: none;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-primary:hover {
            background-color: #1565C0;
            transform: translateY(-2px);
        }

        .btn-logout {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background-color: #d32f2f;
            border: none;
            color: #E0E0E0;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-logout:hover {
            background-color: #b71c1c;
            transform: translateY(-2px);
        }

        .alert {
            display: none;
            margin-bottom: 1rem;
        }

        h1 {
            color: #E0E0E0;
            text-align: center;
            margin-bottom: 2rem;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <button class="btn btn-logout" onclick="window.location.href='logout.php'">Logout</button>
        <h1>Admin Dashboard</h1>
        <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="student-tab" data-bs-toggle="tab" data-bs-target="#student" type="button" role="tab">Add Student</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="teacher-tab" data-bs-toggle="tab" data-bs-target="#teacher" type="button" role="tab">Add Teacher</button>
            </li>
        </ul>
        <div class="tab-content" id="adminTabContent">
            <div class="tab-pane fade show active" id="student" role="tabpanel">
                <div class="text-center">
                    <a href="add_student.php" class="btn btn-primary">Go to Add Student</a>
                </div>
            </div>
            <div class="tab-pane fade" id="teacher" role="tabpanel">
                <div id="teacherAlert" class="alert alert-danger" role="alert"></div>
                <form id="teacherForm" onsubmit="handleTeacherSubmit(event)">
                    <div class="mb-3">
                        <label for="teacherFirstName" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="teacherFirstName" name="firstName" placeholder="Enter first name" required>
                    </div>
                    <div class="mb-3">
                        <label for="teacherLastName" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="teacherLastName" name="lastName" placeholder="Enter last name" required>
                    </div>
                    <div class="mb-3">
                        <label for="teacherEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="teacherEmail" name="email" placeholder="Enter email" required>
                    </div>
                    <div class="mb-3">
                        <label for="departmentId" class="form-label">Department</label>
                        <select class="form-control" id="departmentId" name="departmentId" required onchange="loadClasses()">
                            <option value="">Select a department</option>
                            <?php
                            $stmt = $conn->query("SELECT department_id, department_name FROM departments ORDER BY department_name ASC");
                            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($departments as $department) {
                                echo "<option value='" . htmlspecialchars($department['department_id']) . "'>" . htmlspecialchars($department['department_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="classIds" class="form-label">Classes</label>
                        <select class="form-control" id="classIds" name="classIds[]" multiple></select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Add Teacher</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function loadClasses() {
            const departmentId = document.getElementById('departmentId').value;
            const classIdsSelect = document.getElementById('classIds');
            classIdsSelect.innerHTML = '<option value="">Select classes</option>';

            if (departmentId) {
                try {
                    const response = await fetch(`get_classes.php?for_teacher=true&department_id=${departmentId}`);
                    const classes = await response.json();
                    classes.forEach(cls => {
                        const option = document.createElement('option');
                        option.value = cls.class_id;
                        option.textContent = `${cls.class_name} (Sem: ${cls.semester_name})`;
                        classIdsSelect.appendChild(option);
                    });
                } catch (err) {
                    console.error('Error loading classes:', err);
                }
            }
        }

        async function handleTeacherSubmit(event) {
            event.preventDefault();
            const firstName = document.getElementById('teacherFirstName').value;
            const lastName = document.getElementById('teacherLastName').value;
            const email = document.getElementById('teacherEmail').value;
            const departmentId = document.getElementById('departmentId').value;
            const classIds = Array.from(document.getElementById('classIds').selectedOptions).map(option => option.value);
            const teacherAlert = document.getElementById('teacherAlert');

            if (!firstName || !lastName || !email || !departmentId) {
                teacherAlert.style.display = 'block';
                teacherAlert.textContent = 'Please fill in all required fields.';
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                teacherAlert.style.display = 'block';
                teacherAlert.textContent = 'Please enter a valid email.';
                return;
            }

            try {
                const response = await fetch('add_teacher.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        firstName,
                        lastName,
                        email,
                        departmentId,
                        classIds
                    })
                });
                const data = await response.json();
                teacherAlert.style.display = 'block';
                if (response.ok) {
                    teacherAlert.classList.remove('alert-danger');
                    teacherAlert.classList.add('alert-success');
                    teacherAlert.textContent = data.message;
                    document.getElementById('teacherForm').reset();
                } else {
                    teacherAlert.classList.remove('alert-success');
                    teacherAlert.classList.add('alert-danger');
                    teacherAlert.textContent = data.message;
                }
            } catch (err) {
                teacherAlert.style.display = 'block';
                teacherAlert.classList.remove('alert-success');
                teacherAlert.classList.add('alert-danger');
                teacherAlert.textContent = 'Server error. Please try again later.';
            }
        }
    </script>
</body>

</html>