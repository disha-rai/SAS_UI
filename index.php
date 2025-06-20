<?php
session_start();
if (isset($_SESSION['admin_id']) || isset($_SESSION['student_id']) || isset($_SESSION['teacher_id'])) {
    header('Location: ' . (isset($_SESSION['admin_id']) ? 'admin_dashboard.php' : (isset($_SESSION['teacher_id']) ? 'teacher_dashboard.php' : 'student_dashboard.php')));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Attendance System</title>
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
            overflow: hidden;
        }

        .login-container {
            background: #1c2526;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 400px;
            border: 1px solid #1E88E5;
        }

        .login-container h2 {
            color: #E0E0E0;
            text-align: center;
            margin-bottom: 1.5rem;
            font-weight: 600;
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

        .alert {
            display: none;
            margin-bottom: 1rem;
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

        @media (max-width: 576px) {
            .login-container {
                padding: 1.5rem;
                max-width: 90%;
            }

            .login-container h2 {
                font-size: 1.5rem;
            }

            .btn-primary {
                padding: 0.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="stars"></div>
    <div class="login-container">
        <h2>Smart Attendance System</h2>
        <ul class="nav nav-tabs mb-4" id="authTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">Login</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="signup-tab" data-bs-toggle="tab" data-bs-target="#signup" type="button" role="tab">Sign Up</button>
            </li>
        </ul>
        <div class="tab-content" id="authTabContent">
            <!-- Login Form -->
            <div class="tab-pane fade show active" id="login" role="tabpanel">
                <div id="loginAlert" class="alert alert-danger" role="alert"></div>
                <form id="loginForm" onsubmit="handleLogin(event)">
                    <div class="mb-3">
                        <label for="loginEmail" class="form-label" id="loginLabel">Email (Student/Teacher) / Username (Admin)</label>
                        <input type="text" class="form-control" id="loginEmail" placeholder="Enter email (student/teacher) or username (admin)" required>
                    </div>
                    <div class="mb-3">
                        <label for="loginPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="loginPassword" placeholder="Enter password" required>
                    </div>
                    <div class="mb-3">
                        <label for="userType" class="form-label">User Type</label>
                        <select class="form-control" id="userType" required onchange="updateLoginLabel()">
                            <option value="admin">Admin</option>
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
            <!-- Signup Form -->
            <div class="tab-pane fade" id="signup" role="tabpanel">
                <div id="signupAlert" class="alert alert-danger" role="alert"></div>
                <p class="text-center">Please select your role to sign up:</p>
                <a href="student_signup.html" class="btn btn-primary w-100 mb-2">Sign Up as a Student</a>
                <a href="teacher_signup.html" class="btn btn-primary w-100">Sign Up as a Teacher</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        function updateLoginLabel() {
            const userType = document.getElementById('userType').value;
            const loginLabel = document.getElementById('loginLabel');
            if (userType === 'student') {
                loginLabel.textContent = 'Email (Student)';
            } else if (userType === 'teacher') {
                loginLabel.textContent = 'Email (Teacher)';
            } else {
                loginLabel.textContent = 'Username (Admin)';
            }
        }

        async function handleLogin(event) {
            event.preventDefault();
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            const userType = document.getElementById('userType').value;
            const loginAlert = document.getElementById('loginAlert');

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email || !password || !userType) {
                showAlert(loginAlert, 'Please fill in all fields.', 'danger');
                return;
            }
            if ((userType === 'student' || userType === 'teacher') && !emailPattern.test(email)) {
                showAlert(loginAlert, 'Please enter a valid email address.', 'danger');
                return;
            }

            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username: email,
                        password,
                        type: userType
                    })
                });
                const data = await response.json();
                if (response.ok) {
                    showAlert(loginAlert, data.message, 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else {
                    showAlert(loginAlert, data.message, 'danger');
                }
            } catch (err) {
                showAlert(loginAlert, 'Server error: ' + err.message, 'danger');
            }
        }

        function showAlert(alertElement, message, type) {
            alertElement.style.display = 'block';
            alertElement.classList.remove('alert-danger', 'alert-success');
            alertElement.classList.add(`alert-${type}`);
            alertElement.textContent = message;
            setTimeout(() => {
                alertElement.style.display = 'none';
            }, 3000);
        }
    </script>
</body>

</html>