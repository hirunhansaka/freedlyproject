<?php
// freelancer_my_projects.php
require 'database_connection.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    header("Location: user_login.php");
    exit();
}

$freelancer_id = $_SESSION['user_id'];

// This query fetches ALL jobs the freelancer is assigned to, regardless of how they started.
$stmt = $pdo->prepare("
    SELECT j.*, u.username as employer_name
    FROM jobs j
    JOIN users u ON j.employer_id = u.id
    WHERE j.assigned_freelancer_id = ?
    ORDER BY
        CASE j.status
            WHEN 'pending_agreement' THEN 1
            WHEN 'in_progress' THEN 2
            WHEN 'completed' THEN 3
            ELSE 4
        END, j.created_at DESC
");
$stmt->execute([$freelancer_id]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Projects - Freedly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* --- Global Styles & Variables --- */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        :root {
            --primary-blue: #0d6efd;
            --primary-blue-dark: #0a58ca;
            --sidebar-bg-start: #1c2a4c;
            --sidebar-bg-end: #111827;
            --sidebar-link-color: #9ca3af;
            --sidebar-link-hover-bg: #1f2937;
            --sidebar-link-active-color: #ffffff;
            --content-bg: #f4f7fa;
            --card-bg: #ffffff;
            --dark-text: #111827;
            --light-text: #6b7280;
            --border-color: #e5e7eb;
            --white-color: #ffffff;
            --success-color: #10b981;
            --warning-color: #f59e0b;
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

       /* --- Global Color Variables --- */
:root {
    --primary-blue: #0d6efd;
    --primary-blue-dark: #0a58ca;
    --white-color: #ffffff;
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

        /* ✅ NEW: Project List & Card Styles */
        .project-list {
            display: grid;
            gap: 1.5rem;
        }

        .project-card {
            background-color: var(--card-bg);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap; /* Allows wrapping on small screens */
            gap: 1rem;
            animation: fadeIn 0.5s ease-out forwards;
            opacity: 0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .project-card h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
            color: var(--dark-text);
        }
        
        .project-card .employer-name {
            color: var(--light-text);
            margin: 0.25rem 0 0 0;
            font-size: 0.9rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending {
            background-color: #fffbeb;
            color: #b45309;
        }
        .status-inprogress {
            background-color: #e0f2fe;
            color: #0284c7;
        }
        .status-completed {
            background-color: #d1fae5;
            color: #059669;
        }
        .status-default {
            background-color: #e5e7eb;
            color: #4b5563;
        }

        .btn { display: inline-block; padding: 0.6rem 1.2rem; text-decoration: none; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s ease; }
        .btn-primary { background-color: var(--primary-blue); color: var(--white-color); }
        .btn-primary:hover { background-color: var(--primary-blue-dark); transform: translateY(-2px); }

        /* Responsive */
        @media (max-width: 992px) {
            .dashboard-wrapper { flex-direction: column; }
            .sidebar { width: 100%; height: auto; box-sizing: border-box; }
            .main-content-wrapper { padding: 1.5rem; }
        }
        @media (max-width: 600px) {
            .project-card {
                flex-direction: column;
                align-items: flex-start;
            }
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
                    <li><a href="freelancer_my_projects.php" class="active"><i class="fas fa-briefcase icon"></i> My Projects</a></li>
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
                <h2>My Projects</h2>
                <p>All your service inquiries and active jobs in one place.</p>
            </div>
            
            <div class="project-list">
                <?php if (count($projects) > 0): ?>
                    <?php 
                    $delay = 0;
                    foreach ($projects as $project): 
                        $delay += 0.1; // Stagger the animation delay

                        // Determine status class for the badge
                        $status_class = 'status-default';
                        if ($project['status'] == 'pending_agreement') {
                            $status_class = 'status-pending';
                        } elseif ($project['status'] == 'in_progress') {
                             $status_class = 'status-inprogress';
                        } elseif ($project['status'] == 'completed') {
                             $status_class = 'status-completed';
                        }
                    ?>
                        <div class="project-card" style="animation-delay: <?= $delay ?>s;">
                            <div class="project-details">
                                <h3><?= htmlspecialchars($project['title']) ?></h3>
                                <p class="employer-name">With: <?= htmlspecialchars($project['employer_name']) ?></p>
                            </div>
                            <div class="project-status">
                                <span class="status-badge <?= $status_class ?>">
                                    <?= str_replace('_', ' ', htmlspecialchars($project['status'])) ?>
                                </span>
                            </div>
                            <div class="project-action">
                                <a href="messaging_page.php?job_id=<?= $project['id'] ?>" class="btn btn-primary">View Chat</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="project-card" style="animation-delay: 0.1s;">
                        <p>You have no active projects or inquiries yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>