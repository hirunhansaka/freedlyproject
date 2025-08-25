<?php
// user_login.php
require 'database_connection.php';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // --- Corrected Validations ---
    if (empty($email) || empty($password)) {
        $error_message = "Email and password are required.";
    } 
    // ✅ ADDED: Server-side check for a valid email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } 
    else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } elseif ($user['role'] == 'employer') {
                header("Location: employer_dashboard.php");
            } else {
                header("Location: freelancer_dashboard.php");
            }
            exit();
        } else {
            $error_message = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Freedly</title>
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

    .form-group input {
        width: 100%;
        height: 50px;
        padding: 10px 15px;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
        box-sizing: border-box; /* Ensures padding is included in the width/height */
    }

    .form-group input:focus {
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
        background-color: var(--primary-blue) !important;
        color: var(--white-color) !important;
        transition: background-color 0.3s ease;
    }

    .btn.btn-primary:hover {
        background-color: var(--secondary-blue) !important;
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

    /* ✅ FIXED: Correct CSS for the error alert box */
    .alert {
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: 5px;
        text-align: center;
        border: 1px solid transparent;
    }
    .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
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
                <p>Hire proven experts for any project, from quick turnarounds to long-term collaborations.</p>
            </div>
        </div>

        <div class="login-form-column">
            <div class="form-container">
                <h2>Welcome Back!</h2>
                <p class="subtitle">Log in to continue to your account.</p>

                <?php if (!empty($error_message)): ?>
                    <p class="alert alert-danger"><?= htmlspecialchars($error_message) ?></p>
                <?php endif; ?>

                <form action="user_login.php" method="POST">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>

                <p class="bottom-text">Don't have an account? <a href="user_register.php">Register here</a></p>
            </div>
        </div>
    </div>
</body>
</html>