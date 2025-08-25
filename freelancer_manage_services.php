<?php
// freelancer_manage_services.php
require 'database_connection.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') { 
    header("Location: user_login.php"); 
    exit(); 
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_service'])) {
    $service_id = $_POST['service_id'];
    // First, get the image filename to delete the file
    $stmt_img = $pdo->prepare("SELECT service_image FROM services WHERE id = ? AND freelancer_id = ?");
    $stmt_img->execute([$service_id, $_SESSION['user_id']]);
    $image = $stmt_img->fetchColumn();

    // Now, delete the service from the database
    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ? AND freelancer_id = ?");
    if ($stmt->execute([$service_id, $_SESSION['user_id']])) {
        // If deletion is successful and the image is not the default, delete the file
        if ($image && $image != 'default_service.png' && file_exists('uploads/' . $image)) {
            unlink('uploads/' . $image);
        }
    }
}
$stmt = $pdo->prepare("SELECT * FROM services WHERE freelancer_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage My Services - Freedly</title>
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
            --danger-color: #ef4444;
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

        /* --- Animations --- */
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

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

        .main-content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
        }

        .main-content-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark-text);
            margin: 0;
        }

        .btn { display: inline-block; padding: 0.6rem 1.2rem; text-decoration: none; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s ease; }
        .btn-primary { background-color: var(--primary-blue); color: var(--white-color); }
        .btn-primary:hover { background-color: var(--primary-blue-dark); transform: translateY(-2px); }
        .btn-danger { background-color: var(--danger-color); color: var(--white-color); width: 100%;}
        .btn-danger:hover { background-color: #b91c1c; }

        /* ✅ NEW: Service Management Grid & Card Styles */
        .service-management-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .service-management-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            animation: fadeIn 0.5s ease-out forwards;
            opacity: 0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .service-management-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }
        
        .service-management-card img {
            width: 100%;
            height: 160px;
            object-fit: cover;
        }

        .card-content {
            padding: 1rem 1.5rem 1.5rem 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .card-content h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
            color: var(--dark-text);
            flex-grow: 1;
        }

        .card-content .details {
            display: flex;
            justify-content: space-between;
            color: var(--light-text);
            font-size: 0.9rem;
            margin: 1rem 0;
        }
        .card-content .details span {
            font-weight: 600;
            color: var(--dark-text);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .dashboard-wrapper { flex-direction: column; }
            .sidebar { width: 100%; height: auto; box-sizing: border-box; }
            .main-content-wrapper { padding: 1.5rem; }
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
                    <li><a href="freelancer_create_service.php"><i class="fas fa-plus-circle icon"></i> Create Service</a></li>
                    <li><a href="freelancer_manage_services.php" class="active"><i class="fas fa-tasks icon"></i> Manage Services</a></li>
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
                <h2>My Services</h2>
                <a href="freelancer_create_service.php" class="btn btn-primary">Create New Service</a>
            </div>
            
            <div class="service-management-grid">
                <?php if (count($services) > 0): ?>
                    <?php 
                    $delay = 0;
                    foreach ($services as $service): 
                        $delay += 0.1;
                    ?>
                        <div class="service-management-card" style="animation-delay: <?= $delay ?>s;">
                            <img src="uploads/<?= htmlspecialchars($service['service_image']) ?>" alt="<?= htmlspecialchars($service['title']) ?>">
                            <div class="card-content">
                                <h3><?= htmlspecialchars($service['title']) ?></h3>
                                <p class="details">
                                    Price: <span>$<?= htmlspecialchars($service['price']) ?></span>
                                    Delivery: <span><?= htmlspecialchars($service['delivery_days']) ?> Days</span>
                                </p>
                                <form action="freelancer_manage_services.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this service?');">
                                    <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                                    <button type="submit" name="delete_service" class="btn btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>You have not created any services yet.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>