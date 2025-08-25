<?php
// freelancer_create_service.php
require 'database_connection.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') { 
    header("Location: user_login.php"); 
    exit(); 
}

$error_message = ''; 
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $category = trim($_POST['category']);
    $delivery_days = filter_var($_POST['delivery_days'], FILTER_VALIDATE_INT);
    $service_image = 'default_service.png';

    if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] == 0) {
        $target_dir = "uploads/";
        $file_name = "service_" . time() . '_' . basename($_FILES["service_image"]["name"]);
        $target_file = $target_dir . $file_name;
        if(move_uploaded_file($_FILES["service_image"]["tmp_name"], $target_file)) {
            $service_image = $file_name;
        } else { 
            $error_message = "Error uploading image."; 
        }
    }

    if (empty($error_message) && !empty($title) && !empty($description) && $price !== false && !empty($category) && $delivery_days !== false) {
        $stmt = $pdo->prepare("INSERT INTO services (freelancer_id, title, description, price, category, delivery_days, service_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$_SESSION['user_id'], $title, $description, $price, $category, $delivery_days, $service_image])) {
            $success_message = "Your new service has been created successfully!";
        } else { 
            $error_message = "Failed to create service."; 
        }
    } elseif (empty($error_message)) {
        $error_message = "Please fill in all required fields correctly.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create a New Service - Freedly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* --- Global Styles & Variables --- */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        :root {
            --primary-blue: #0d6efd;
            --primary-blue-dark: #0a58ca;
            --content-bg: #f4f7fa;
            --card-bg: #ffffff;
            --dark-text: #111827;
            --light-text: #6b7280;
            --border-color: #e5e7eb;
            --white-color: #ffffff;
            --font-family: 'Inter', sans-serif;
        }

        body, html {
            margin: 0;
            padding: 0;
            font-family: var(--font-family);
            background-color: var(--content-bg);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* --- Main Layout --- */
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* --- 1. MODERN SIDEBAR (Solid Blue Version) --- */
        .sidebar {
            width: 260px;
            background-color: var(--primary-blue);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .sidebar .logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--white-color);
            text-decoration: none;
            text-align: center;
            margin-bottom: 2.5rem;
            padding: 0.5rem 0;
        }

        .sidebar-menu h3 {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.6);
            margin: 1.5rem 0 0.5rem 0.75rem;
        }

        .sidebar-menu ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu ul li a {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.85rem 0.75rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.25rem;
            font-weight: 500;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .sidebar-menu ul li a .icon {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            transition: color 0.2s ease;
        }

        .sidebar-menu ul li a:hover {
            background-color: var(--primary-blue-dark);
            color: var(--white-color);
        }

        .sidebar-menu ul li a:hover .icon {
            color: var(--white-color);
        }

        .sidebar-menu ul li a.active {
            background-color: var(--white-color);
            color: var(--primary-blue-dark);
            font-weight: 700;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar-menu ul li a.active .icon {
            color: var(--primary-blue-dark);
        }

        /* --- 2. MAIN CONTENT AREA --- */
        .main-content-wrapper {
            flex: 1;
            padding: 2.5rem;
            overflow-y: auto;
        }

        .main-content-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark-text);
            margin: 0 0 0.5rem 0;
        }

        .main-content-header p {
            color: var(--light-text);
            margin: 0 0 2.5rem 0;
        }

        /* ✅ NEW: Styles for the form card and elements */
        .form-card {
            background-color: var(--card-bg);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-text);
        }

        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            font-family: var(--font-family);
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
            outline: none;
        }
        
        /* Modern style for file input */
        .form-group .file-input {
            padding: 0.5rem;
            background-color: var(--content-bg);
        }
        .form-group .file-input::file-selector-button {
            background-color: var(--primary-blue);
            color: var(--white-color);
            border: none;
            padding: 0.6rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 1rem;
            transition: background-color 0.2s ease;
        }
        .form-group .file-input::file-selector-button:hover {
            background-color: var(--primary-blue-dark);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        .btn-primary {
            background-color: var(--primary-blue);
            color: var(--white-color);
            font-size: 1rem;
        }
        .btn-primary:hover {
            background-color: var(--primary-blue-dark);
            transform: translateY(-2px);
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
        }
        .alert-danger { background-color: #fee2e2; color: #b91c1c; }
        .alert-success { background-color: #d1fae5; color: #059669; }

        /* Responsive */
        @media (max-width: 992px) {
            .dashboard-wrapper { flex-direction: column; }
            .sidebar { width: 100%; height: auto; box-sizing: border-box; }
            .main-content-wrapper { padding: 1.5rem; }
        }
        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <a href="index.php" class="logo">Freedly</a>
            <div class="sidebar-menu">
                <h3>Menu</h3>
                <ul>
                    <li><a href="freelancer_dashboard.php"><i class="fas fa-home icon"></i> Dashboard</a></li>
                    <li><a href="freelancer_my_projects.php"><i class="fas fa-briefcase icon"></i> My Projects</a></li>
                    <li><a href="freelancer_view_applications.php"><i class="fas fa-file-alt icon"></i> Job Applications</a></li>
                </ul>
                <h3>Services</h3>
                 <ul>
                    <li><a href="freelancer_create_service.php" class="active"><i class="fas fa-plus-circle icon"></i> Create Service</a></li>
                    <li><a href="freelancer_manage_services.php"><i class="fas fa-tasks icon"></i> Manage Services</a></li>
                </ul>
                <h3>Account</h3>
                <ul>
                    <li><a href="freelancer_manage_profile.php"><i class="fas fa-user-circle icon"></i> Manage Profile</a></li>
                    <li><a href="user_report_admin.php"><i class="fas fa-exclamation-triangle icon"></i> Report to Admin</a></li>
                    <li><a href="user_logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a></li>
                </ul>
            </div>
        </aside>

        <main class="main-content-wrapper">
            <div class="main-content-header">
                <h2>Create a New Service</h2>
                <p>Offer your skills as a pre-packaged service that employers can browse and buy.</p>
            </div>
            
            <div class="form-card">
                 <?php if (!empty($error_message)): ?><p class="alert alert-danger"><?= htmlspecialchars($error_message) ?></p><?php endif; ?>
                 <?php if (!empty($success_message)): ?><p class="alert alert-success"><?= htmlspecialchars($success_message) ?></p><?php endif; ?>

                <form action="freelancer_create_service.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Service Title</label>
                        <input type="text" id="title" name="title" required placeholder="e.g., I will design a modern minimalist logo">
                    </div>
                    <div class="form-group">
                        <label for="description">Full Description</label>
                        <textarea id="description" name="description" required rows="6" placeholder="Describe what you offer, your process, and what the buyer will receive."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <input type="text" id="category" name="category" list="categories" placeholder="e.g., Graphic Design" required>
                        <datalist id="categories">
                            <option value="Web Development">
                            <option value="Graphic Design">
                            <option value="Writing & Translation">
                            <option value="Digital Marketing">
                        </datalist>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price ($)</label>
                            <input type="number" step="0.01" id="price" name="price" required placeholder="50.00">
                        </div>
                        <div class="form-group">
                            <label for="delivery_days">Delivery Time (in days)</label>
                            <input type="number" id="delivery_days" name="delivery_days" required placeholder="3">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="service_image">Service Image (Thumbnail)</label>
                        <input type="file" id="service_image" name="service_image" class="file-input">
                    </div>
                    <button type="submit" class="btn btn-primary">Create Service</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>