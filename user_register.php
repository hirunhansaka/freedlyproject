<?php
// user_register.php (with corrected validations)
require 'database_connection.php';
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'] ?? ''; // Use null coalescing operator for safety

    // --- Corrected & Enhanced Validations ---
    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        $error_message = "All fields are required.";
    } 
    // ✅ ADDED: Validate username contains only allowed characters
    elseif (!preg_match("/^[a-zA-Z0-9\s]+$/", $username)) {
        $error_message = "Username can only contain letters, numbers, and spaces.";
    }
    // ✅ ADDED: Validate role is one of the expected values
    elseif (!in_array($role, ['freelancer', 'employer'])) {
        $error_message = "Invalid role selected.";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } 
    elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    }
    // ✅ ADDED: Check for password complexity (at least one letter and one number)
    elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error_message = "Password must contain at least one letter and one number.";
    }
    else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error_message = "This email address is already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$username, $email, $hashed_password, $role])) {
                $success_message = "Registration successful! You can now <a href='user_login.php'>log in</a>.";
            } else {
                $error_message = "An error occurred during registration. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Account - Freedly</title>
    <style>
        /* --- Global Styles & Variables (Adapted from your example) --- */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');

        :root {
            --primary-blue: #0073e6;
            --secondary-blue: #005cb9;
            --dark-text: #222325;
            --light-text: #5f6368;
            --border-color: #dcdfe2;
            --white-color: #ffffff;
            --light-blue-bg: #f4f7fa;
            --font-family: 'Inter', sans-serif;
        }

        body, html {
            height: 100%;
            margin: 0;
            font-family: var(--font-family);
            background-color: var(--light-blue-bg);
        }

        /* --- Main Login Page Container --- */
        .login-page-container {
            display: flex;
            min-height: 100vh;
            align-items: stretch;
        }

        /* --- 1. Branding Column (Left Side) --- */
        .login-branding-column {
            flex-basis: 55%;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 50px;
            text-align: center;
            overflow: hidden;
            animation: slideInFromLeft 1s ease-out;
        }

        .login-video-bg {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            min-width: 100%;
            min-height: 100%;
            z-index: 1;
            object-fit: cover;
        }

        .login-branding-column::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(0, 92, 185, 0.85), rgba(0, 115, 230, 0.85));
            z-index: 2;
        }

        .branding-content {
            position: relative;
            z-index: 3;
            color: var(--white-color);
        }

        .branding-content .logo {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-decoration: none;
            color: var(--white-color);
        }

        .branding-content p {
            font-size: 1.1rem;
            max-width: 450px;
            line-height: 1.6;
            opacity: 0.9;
        }

        /* --- 2. Form Column (Right Side) --- */
        .login-form-column {
            flex-basis: 45%;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
            animation: fadeIn 1.2s ease-in-out;
        }

        .form-container {
            width: 100%;
            max-width: 380px;
            padding: 40px;
            border-radius: 16px;
            background-color: var(--white-color);
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .form-container h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
            text-align: center;
            color: var(--dark-text);
        }

        .form-container .subtitle {
            font-size: 1rem;
            color: var(--light-text);
            margin-bottom: 30px;
            text-align: center;
        }

        .form-group {
             margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-group input, .form-group select {
            width: 100%;
            height: 50px;
            padding: 0 15px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            box-sizing: border-box; /* Ensures padding is included in the width/height */
            font-size: 1rem;
            font-family: var(--font-family);
        }
        
        /* Style for the select dropdown arrow */
        .form-group select {
            appearance: none;
            -webkit-appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%236c757d%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 15px top 50%;
            background-size: .65em auto;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(0, 115, 230, 0.15);
            outline: none;
        }

        .btn.btn-primary {
            width: 100%;
            padding: 12px;
            font-size: 1rem;
            font-weight: 700;
            border-radius: 8px;
            background-color: var(--primary-blue);
            transition: background-color 0.3s ease;
            color: var(--white-color);
        }

        .btn.btn-primary:hover {
            background-color: var(--secondary-blue);
        }

        .form-container .bottom-text {
            text-align: center;
            color: var(--light-text);
            font-size: 0.9rem;
            margin-top: 2rem;
        }

        .form-container .bottom-text a {
            color: var(--primary-blue);
            font-weight: 600;
            text-decoration: none;
        }

        .form-container .bottom-text a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            text-align: center;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            color: #0f5132;
            background-color: #d1e7dd;
            border-color: #badbcc;
        }

        /* --- Animations --- */
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideInFromLeft { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }

        /* --- Responsive Design --- */
        @media (max-width: 992px) {
            .login-branding-column { display: none; }
            .login-form-column { flex-basis: 100%; }
        }
    </style>
</head>
<body>
    <div class="login-page-container">
        <div class="login-branding-column">
            <video autoplay muted loop class="login-video-bg">
                <source src="video/login.mp4" type="video/mp4">
            </video>
            <div class="branding-content">
                <a href="index.php" class="logo">Freedly</a>
                <p>Showcase your skills, connect with innovative companies, and turn your passion into a thriving career.</p>
            </div>
        </div>

        <div class="login-form-column">
            <div class="form-container">
                <h2>Create Your Account</h2>
                <p class="subtitle">Get started with Freedly today.</p>

                <?php if (!empty($error_message)): ?>
                    <p class="alert alert-danger"><?= htmlspecialchars($error_message) ?></p>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?= $success_message ?></div>
                <?php endif; ?>
                
                <form action="user_register.php" method="POST">
                    <div class="form-group">
                        <label for="username">Full Name</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="role">I am a:</label>
                        <select id="role" name="role" required>
                            <option value="freelancer">Freelancer (Looking for work)</option>
                            <option value="employer">Employer (Looking to hire)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Create Account</button>
                </form>

                <p class="bottom-text">Already have an account? <a href="user_login.php">Login here</a></p>
            </div>
        </div>
    </div>
</body>
</html>