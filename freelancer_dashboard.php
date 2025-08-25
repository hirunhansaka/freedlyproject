<?php
// freelancer_dashboard.php (Updated)
require 'database_connection.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    header("Location: user_login.php");
    exit();
}

$freelancer_id = $_SESSION['user_id'];

// Updated stats to reflect all assigned projects
$pending_projects = $pdo->prepare("SELECT count(*) FROM jobs WHERE assigned_freelancer_id = ? AND status = 'pending_agreement'");
$pending_projects->execute([$freelancer_id]);
$pending_projects_count = $pending_projects->fetchColumn();

$active_projects = $pdo->prepare("SELECT count(*) FROM jobs WHERE assigned_freelancer_id = ? AND status = 'in_progress'");
$active_projects->execute([$freelancer_id]);
$active_projects_count = $active_projects->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Freedly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* --- Global Styles & Variables --- */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        :root {
            --primary-blue: #0d6efd;
            --primary-blue-dark: #0a58ca;
            --primary-blue-light: #3c8cff;
            --dark-sidebar-bg: #111827;
            --sidebar-link-color: #9ca3af;
            --sidebar-link-hover-bg: #1f2937;
            --sidebar-link-active-color: #ffffff;
            --content-bg: #f4f7fa;
            --card-bg: #ffffff;
            --dark-text: #111827;
            --light-text: #6b7280;
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

        /* --- Animations --- */
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        /* ✅ NEW: Keyframes for the pulsing glow effect */
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 20px rgba(60, 140, 255, 0.3); }
            50% { box-shadow: 0 0 35px rgba(60, 140, 255, 0.5); }
        }

        /* ✅ NEW: Keyframes for the wave animation */
        @keyframes wave {
            0% { background-position-x: 0; }
            100% { background-position-x: -1000px; }
        }

        /* --- Main Layout --- */
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }

/* --- 1. MODERN SIDEBAR (Solid Blue Version) --- */
.sidebar {
    width: 260px;
    /* ✅ CHANGED: Set the background to your primary blue color */
    background-color: var(--primary-blue);
    
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
}

.sidebar .logo {
    font-size: 1.8rem;
    font-weight: 800;
    /* ✅ CHANGED: Logo color to white for contrast */
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
    /* ✅ CHANGED: Text color to a subtle white */
    color: rgba(255, 255, 255, 0.6);
    margin: 2rem 0 0.5rem 0.75rem;
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
    /* ✅ CHANGED: Text color to a brighter, semi-transparent white */
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
    /* ✅ CHANGED: Icon color to match the link text */
    color: rgba(255, 255, 255, 0.8);
    transition: color 0.2s ease;
}

.sidebar-menu ul li a:hover {
    /* ✅ CHANGED: Hover effect is a subtle, darker blue */
    background-color: var(--primary-blue-dark);
    color: var(--white-color);
}

.sidebar-menu ul li a:hover .icon {
    color: var(--white-color);
}

.sidebar-menu ul li a.active {
    /* ✅ CHANGED: Active state is now white with blue text for high contrast */
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

        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        /* ✅ NEW: Modern Animated Stat Card Style */
        .stat-card {
            position: relative;
            padding: 2rem;
            border-radius: 16px;
            color: var(--white-color);
            overflow: hidden;
            text-align: center;
            
            background-image: linear-gradient(45deg, var(--primary-blue-light) 0%, var(--primary-blue-dark) 100%);
            animation: fadeIn 0.5s ease-out forwards, pulseGlow 4s infinite ease-in-out;
            opacity: 0;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }

        /* Animated wave effect */
        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: -50%;
            width: 200%;
            height: 100px;
            opacity: 0.2;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100"><path d="M0 63c250-100 250 100 500 0s250-100 500 0v37H0z" fill="%23ffffff"/></svg>');
            background-repeat: repeat-x;
            animation: wave 10s linear infinite;
        }

        .stat-card h3 {
            font-size: 1.1rem;
            margin: 0 0 0.5rem 0;
            font-weight: 600;
            opacity: 0.9;
        }

        .stat-card .stat-number {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1;
        }

        .quick-actions {
            margin-top: 2.5rem;
            background-color: var(--card-bg);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
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
        }
        
        .btn-primary {
            background-color: var(--primary-blue);
            color: var(--white-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-blue-dark);
            transform: translateY(-2px);
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
                    <li><a href="freelancer_dashboard.php" class="active"><i class="fas fa-home icon"></i> Dashboard</a></li>
                    <li><a href="freelancer_my_projects.php"><i class="fas fa-briefcase icon"></i> My Projects</a></li>
                    <li><a href="freelancer_view_applications.php"><i class="fas fa-file-alt icon"></i> Job Applications</a></li>
                </ul>
                <h3>Services</h3>
                 <ul>
                    <li><a href="freelancer_create_service.php"><i class="fas fa-plus-circle icon"></i> Create Service</a></li>
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
                <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
                <p>Here's a summary of your activity. Today is <?= date('l, F jS, Y') ?>.</p>
            </div>
            
            <div class="stat-cards">
                <div class="stat-card">
                    <h3>Pending Inquiries</h3>
                    <p class="stat-number" data-target="<?= $pending_projects_count ?>">0</p>
                </div>
                <div class="stat-card">
                    <h3>Active Projects</h3>
                    <p class="stat-number" data-target="<?= $active_projects_count ?>">0</p>
                </div>
            </div>

            <div class="quick-actions">
                <p>Manage your work, create new service offerings, and communicate with clients.</p>
                <a href="freelancer_my_projects.php" class="btn btn-primary">Go to My Projects</a>
            </div>
        </main>
    </div>
    
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const counters = document.querySelectorAll('.stat-number');
            const speed = 200; // The lower the #, the faster the count

            counters.forEach(counter => {
                const animate = () => {
                    const target = +counter.getAttribute('data-target');
                    const count = +counter.innerText;
                    const increment = Math.ceil(target / speed);

                    if (count < target) {
                        counter.innerText = count + increment;
                        setTimeout(animate, 10);
                    } else {
                        counter.innerText = target;
                    }
                };
                animate();
            });
        });
    </script>
</body>
</html>