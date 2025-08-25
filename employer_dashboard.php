<?php
// employer_dashboard.php (Updated)
require 'database_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: user_login.php");
    exit();
}

$employer_id = $_SESSION['user_id'];

$open_jobs = $pdo->prepare("SELECT count(*) FROM jobs WHERE employer_id = ? AND status = 'open'");
$open_jobs->execute([$employer_id]);
$open_jobs_count = $open_jobs->fetchColumn();

$inprogress_jobs = $pdo->prepare("SELECT count(*) FROM jobs WHERE employer_id = ? AND status = 'in_progress'");
$inprogress_jobs->execute([$employer_id]);
$inprogress_jobs_count = $inprogress_jobs->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Dashboard - Freedly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* --- Global Styles & Variables --- */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        :root {
            --primary-blue: #0d6efd;
            --primary-blue-dark: #0a58ca;
            --primary-blue-light: #3c8cff;
            --content-bg: #f4f7fa;
            --card-bg: #ffffff;
            --dark-text: #111827;
            --light-text: #6b7280;
            --white-color: #ffffff;
            --success-color: #10b981;
            --success-color-dark: #059669;
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
        @keyframes pulseGlowBlue { 0%, 100% { box-shadow: 0 0 20px rgba(13, 110, 253, 0.3); } 50% { box-shadow: 0 0 35px rgba(13, 110, 253, 0.5); } }
        @keyframes pulseGlowGreen { 0%, 100% { box-shadow: 0 0 20px rgba(16, 185, 129, 0.3); } 50% { box-shadow: 0 0 35px rgba(16, 185, 129, 0.5); } }
        @keyframes wave { 0% { background-position-x: 0; } 100% { background-position-x: -1000px; } }

        /* --- Main Layout --- */
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* --- 1. MODERN SOLID BLUE SIDEBAR --- */
        .sidebar {
            width: 260px;
            background-color: var(--primary-blue);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }
        .sidebar .logo { font-size: 1.8rem; font-weight: 800; color: var(--white-color); text-decoration: none; text-align: center; margin-bottom: 2.5rem; padding: 0.5rem 0; }
        .sidebar-menu h3 { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: rgba(255, 255, 255, 0.6); margin: 1.5rem 0 0.5rem 0.75rem; }
        .sidebar-menu ul { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu ul li a { display: flex; align-items: center; gap: 0.85rem; padding: 0.85rem 0.75rem; color: rgba(255, 255, 255, 0.8); text-decoration: none; border-radius: 8px; margin-bottom: 0.25rem; font-weight: 500; transition: all 0.2s ease; }
        .sidebar-menu ul li a .icon { font-size: 1.1rem; width: 20px; text-align: center; color: rgba(255, 255, 255, 0.8); transition: color 0.2s ease; }
        .sidebar-menu ul li a:hover { background-color: var(--primary-blue-dark); color: var(--white-color); }
        .sidebar-menu ul li a:hover .icon { color: var(--white-color); }
        .sidebar-menu ul li a.active { background-color: var(--white-color); color: var(--primary-blue-dark); font-weight: 700; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); }
        .sidebar-menu ul li a.active .icon { color: var(--primary-blue-dark); }

        /* --- 2. MAIN CONTENT AREA --- */
        .main-content-wrapper {
            flex: 1;
            padding: 2.5rem;
            overflow-y: auto;
        }
        .main-content-header h2 { font-size: 1.8rem; font-weight: 700; color: var(--dark-text); margin: 0 0 0.5rem 0; }
        .main-content-header p { color: var(--light-text); margin: 0 0 2.5rem 0; }
        .stat-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; }

        /* ✅ DECORATED & ANIMATED STAT CARDS */
        .stat-card {
            position: relative; padding: 2rem; border-radius: 16px; color: var(--white-color);
            overflow: hidden; text-align: center; opacity: 0;
            transition: transform 0.3s ease; animation: fadeIn 0.5s ease-out forwards;
        }
        .stat-card:hover { transform: translateY(-5px); }

        /* Different colors for each card */
        .stat-card.open-jobs {
            background-image: linear-gradient(45deg, #3b82f6 0%, #0d6efd 100%);
            animation-delay: 0.1s;
            animation-name: fadeIn, pulseGlowBlue;
            animation-duration: 0.5s, 4s;
            animation-timing-function: ease-out, ease-in-out;
            animation-fill-mode: forwards, forwards;
            animation-iteration-count: 1, infinite;
        }
        .stat-card.in-progress-jobs {
            background-image: linear-gradient(45deg, #00c04aff 0%, #059669 100%);
            animation-delay: 0.2s;
            animation-name: fadeIn, pulseGlowGreen;
            animation-duration: 0.5s, 4s;
            animation-timing-function: ease-out, ease-in-out;
            animation-fill-mode: forwards, forwards;
            animation-iteration-count: 1, infinite;
        }

        /* Animated wave effect */
        .stat-card::after {
            content: ''; position: absolute; bottom: 0; left: -50%; width: 200%; height: 100px;
            opacity: 0.2; background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100"><path d="M0 63c250-100 250 100 500 0s250-100 500 0v37H0z" fill="%23ffffff"/></svg>');
            background-repeat: repeat-x; animation: wave 10s linear infinite;
        }
        .stat-card h3 { font-size: 1.1rem; margin: 0 0 0.5rem 0; font-weight: 600; opacity: 0.9; }
        .stat-card .stat-number { font-size: 3.5rem; font-weight: 800; line-height: 1; }

        .quick-actions {
            margin-top: 2.5rem; background-color: var(--card-bg); padding: 1.5rem;
            border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        .btn { display: inline-block; padding: 0.8rem 1.5rem; text-decoration: none; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s ease; }
        .btn-primary { background-color: var(--primary-blue); color: var(--white-color); }
        .btn-primary:hover { background-color: var(--primary-blue-dark); transform: translateY(-2px); }
        
        @media (max-width: 992px) { .dashboard-wrapper { flex-direction: column; } .sidebar { width: 100%; height: auto; box-sizing: border-box; } .main-content-wrapper { padding: 1.5rem; } }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <a href="index.php" class="logo">Freedly</a>
            <div class="sidebar-menu">
                <h3>Menu</h3>
                <ul>
                    <li><a href="employer_dashboard.php" class="active"><i class="fas fa-home icon"></i> Dashboard</a></li>
                    <li><a href="employer_post_job.php"><i class="fas fa-plus-circle icon"></i> Post a New Job</a></li>
                    <li><a href="employer_manage_jobs.php"><i class="fas fa-briefcase icon"></i> Manage Jobs</a></li>
                </ul>
                <h3>Account</h3>
                <ul>
                    <li><a href="employer_manage_profile.php"><i class="fas fa-user-circle icon"></i> Manage Profile</a></li>
                    <li><a href="user_report_admin.php"><i class="fas fa-exclamation-triangle icon"></i> Report to Admin</a></li>
                    <li><a href="user_logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a></li>
                </ul>
            </div>
        </aside>

        <main class="main-content-wrapper">
            <div class="main-content-header">
                <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
                <p>Here's an overview of your hiring activity. Today is <?= date('l, F jS, Y') ?>.</p>
            </div>
            
            <div class="stat-cards">
                <div class="stat-card open-jobs">
                    <h3>Open Jobs</h3>
                    <p class="stat-number" data-target="<?= $open_jobs_count ?>">0</p>
                </div>
                <div class="stat-card in-progress-jobs">
                    <h3>In Progress Jobs</h3>
                    <p class="stat-number" data-target="<?= $inprogress_jobs_count ?>">0</p>
                </div>
            </div>

            <div class="quick-actions">
                <p>Ready to find the perfect talent for your next project?</p>
                <a href="employer_post_job.php" class="btn btn-primary">Post a Job Now</a>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const counters = document.querySelectorAll('.stat-number');
            counters.forEach(counter => {
                const animate = () => {
                    const target = +counter.getAttribute('data-target');
                    const count = +counter.innerText;
                    const increment = Math.ceil((target - count) / 15);
                    if (count < target) {
                        counter.innerText = count + increment;
                        setTimeout(animate, 20);
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