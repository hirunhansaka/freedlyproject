<?php
// admin_dashboard.php
require 'database_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: user_login.php");
    exit();
}

// Fetch stats for the dashboard
$user_count = $pdo->query("SELECT count(*) FROM users WHERE role != 'admin'")->fetchColumn();
$job_count = $pdo->query("SELECT count(*) FROM jobs")->fetchColumn();
$report_count = $pdo->query("SELECT count(*) FROM reports WHERE status = 'pending'")->fetchColumn();

// Fetch recent activity (e.g., latest 5 registered users)
$recent_users = $pdo->query("SELECT username, created_at FROM users WHERE role != 'admin' ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Freedly</title>
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
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        /* --- Main Layout --- */
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* --- 1. DARK BLUE SIDEBAR --- */
        .sidebar {
            width: 260px;
            background-image: linear-gradient(180deg, var(--sidebar-bg-start) 0%, var(--sidebar-bg-end) 100%);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            color: var(--white-color);
            flex-shrink: 0;
        }
        .sidebar .logo { font-size: 1.8rem; font-weight: 800; color: var(--white-color); text-decoration: none; text-align: center; margin-bottom: 2.5rem; padding: 0.5rem 0; }
        .sidebar-menu h3 { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--sidebar-link-color); margin: 1.5rem 0 0.5rem 0.75rem; }
        .sidebar-menu ul { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu ul li a { display: flex; align-items: center; gap: 0.85rem; padding: 0.85rem 0.75rem; color: var(--sidebar-link-color); text-decoration: none; border-radius: 8px; margin-bottom: 0.25rem; font-weight: 500; transition: all 0.2s ease; }
        .sidebar-menu ul li a .icon { font-size: 1.1rem; width: 20px; text-align: center; }
        .sidebar-menu ul li a:hover { background-color: var(--sidebar-link-hover-bg); color: var(--sidebar-link-active-color); }
        .sidebar-menu ul li a.active { background-color: var(--primary-blue); color: var(--sidebar-link-active-color); box-shadow: 0 4px 10px rgba(0, 115, 230, 0.3); }

        /* --- 2. MAIN CONTENT AREA --- */
        .main-content-wrapper { flex: 1; padding: 2.5rem; overflow-y: auto; }
        .main-content-header h2 { font-size: 1.8rem; font-weight: 700; color: var(--dark-text); margin: 0 0 0.5rem 0; }
        .main-content-header p { color: var(--light-text); margin: 0 0 2.5rem 0; }

        /* ✅ SUPER ANIMATED STAT CARDS */
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        .stat-card {
            position: relative;
            padding: 1.5rem;
            border-radius: 16px;
            color: var(--white-color);
            overflow: hidden;
            animation: fadeIn 0.5s ease-out forwards;
            opacity: 0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            transform-style: preserve-3d; /* For 3D tilt effect */
        }
        .stat-card:nth-child(1) { background-image: linear-gradient(45deg, #2563eb 0%, #3b82f6 100%); animation-delay: 0.1s; }
        .stat-card:nth-child(2) { background-image: linear-gradient(45deg, #16a34a 0%, #22c55e 100%); animation-delay: 0.2s; }
        .stat-card:nth-child(3) { background-image: linear-gradient(45deg, #d97706 0%, #f59e0b 100%); animation-delay: 0.3s; }

        .stat-card-content { transform: translateZ(20px); /* Lifts content for 3D effect */ }
        .stat-card .icon { font-size: 1.8rem; margin-bottom: 0.5rem; opacity: 0.8; }
        .stat-card h3 { font-size: 1rem; margin: 0 0 0.25rem 0; font-weight: 500; opacity: 0.9; }
        .stat-card .stat-number { font-size: 2.8rem; font-weight: 800; line-height: 1; }

        /* Dashboard Panels */
        .dashboard-panels {
            margin-top: 2.5rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .panel { background-color: var(--card-bg); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); }
        .panel h3 { font-size: 1.2rem; color: var(--dark-text); margin: 0 0 1.5rem 0; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; }
        
        .quick-actions .btn { display: flex; align-items: center; gap: 0.75rem; width: 100%; text-align: left; margin-bottom: 1rem; padding: 0.8rem 1.2rem; text-decoration: none; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s ease; background-color: var(--content-bg); color: var(--dark-text); }
        .quick-actions .btn:hover { background-color: var(--primary-blue); color: var(--white-color); }
        
        .activity-list ul { list-style: none; padding: 0; margin: 0; }
        .activity-list li { display: flex; align-items: center; gap: 1rem; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color); }
        .activity-list li:last-child { border-bottom: none; }
        .activity-list .icon { font-size: 1rem; color: var(--light-text); }
        .activity-list p { margin: 0; color: var(--dark-text); }
        .activity-list span { font-size: 0.8rem; color: var(--light-text); margin-left: auto; }
        
        @media (max-width: 992px) { .dashboard-wrapper { flex-direction: column; } .sidebar { width: 100%; height: auto; box-sizing: border-box; } .main-content-wrapper { padding: 1.5rem; } }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <a href="index.php" class="logo">Freedly Admin</a>
            <div class="sidebar-menu">
                <h3>Menu</h3>
                <ul>
                    <li><a href="admin_dashboard.php" class="active"><i class="fas fa-home icon"></i> Dashboard</a></li>
                    <li><a href="admin_manage_users.php"><i class="fas fa-users icon"></i> Manage Users</a></li>
                    <li><a href="admin_manage_jobs.php"><i class="fas fa-briefcase icon"></i> Manage Jobs</a></li>
                    <li><a href="admin_manage_reports.php"><i class="fas fa-flag icon"></i> Manage Reports</a></li>
                </ul>
                <h3>Account</h3>
                <ul>
                    <li><a href="user_logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a></li>
                </ul>
            </div>
        </aside>

        <main class="main-content-wrapper">
            <div class="main-content-header">
                <h2>Admin Dashboard</h2>
                <p>Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>! Today is <?= date('l, F jS, Y') ?>.</p>
            </div>
            
            <div class="stat-cards">
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="icon"><i class="fas fa-users"></i></div>
                        <h3>Total Users</h3>
                        <p class="stat-number" data-target="<?= $user_count ?>">0</p>
                    </div>
                </div>
                <div class="stat-card">
                     <div class="stat-card-content">
                        <div class="icon"><i class="fas fa-briefcase"></i></div>
                        <h3>Total Jobs</h3>
                        <p class="stat-number" data-target="<?= $job_count ?>">0</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-content">
                        <div class="icon"><i class="fas fa-flag"></i></div>
                        <h3>Pending Reports</h3>
                        <p class="stat-number" data-target="<?= $report_count ?>">0</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-panels">
                <div class="panel quick-actions">
                    <h3>Quick Actions</h3>
                    <a href="admin_manage_users.php" class="btn"><i class="fas fa-users"></i> Manage All Users</a>
                    <a href="admin_manage_jobs.php" class="btn"><i class="fas fa-briefcase"></i> Manage All Jobs</a>
                    <a href="admin_manage_reports.php" class="btn"><i class="fas fa-flag"></i> View Pending Reports</a>
                </div>
                <div class="panel recent-activity">
                    <h3>Recent Registrations</h3>
                    <div class="activity-list">
                        <ul>
                            <?php foreach($recent_users as $user): ?>
                            <li>
                                <i class="fas fa-user-plus icon"></i>
                                <p><?= htmlspecialchars($user['username']) ?> joined.</p>
                                <span><?= date('M d', strtotime($user['created_at'])) ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

        </main>
    </div>
    
    <script>
        // Number counter animation
        document.addEventListener("DOMContentLoaded", () => {
            const counters = document.querySelectorAll('.stat-number');
            counters.forEach(counter => {
                const animate = () => {
                    const target = +counter.getAttribute('data-target');
                    const count = +counter.innerText;
                    const increment = Math.ceil((target - count) / 10);
                    if (count < target) {
                        counter.innerText = count + increment;
                        setTimeout(animate, 20);
                    } else {
                        counter.innerText = target;
                    }
                };
                animate();
            });

            // 3D tilt effect for stat cards
            const cards = document.querySelectorAll('.stat-card');
            cards.forEach(card => {
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    const { width, height } = rect;
                    const rotateX = (y - height / 2) / (height / 2) * -10; // Max 10deg tilt
                    const rotateY = (x - width / 2) / (width / 2) * 10;   // Max 10deg tilt
                    
                    card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
                });

                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
                });
            });
        });
    </script>
</body>
</html>