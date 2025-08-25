<?php
// employer_manage_jobs.php (Updated)
require 'database_connection.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') { 
    header("Location: user_login.php"); 
    exit(); 
}

$employer_id = $_SESSION['user_id'];

// Fetch jobs with proposal count
$stmt = $pdo->prepare("
    SELECT 
        j.*, 
        (SELECT COUNT(*) FROM proposals p WHERE p.job_id = j.id) AS proposal_count 
    FROM 
        jobs j 
    WHERE 
        j.employer_id = ? 
    ORDER BY 
        j.created_at DESC
");
$stmt->execute([$employer_id]);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jobs - Freedly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* --- Global, Layout, and Sidebar Styles --- */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        :root {
            --primary-blue: #0d6efd; --primary-blue-dark: #0a58ca; --content-bg: #f4f7fa;
            --card-bg: #ffffff; --dark-text: #111827; --light-text: #6b7280;
            --border-color: #e5e7eb; --white-color: #ffffff; --success-color: #10b981;
            --warning-color: #f59e0b; --font-family: 'Inter', sans-serif;
        }
        body, html { margin: 0; padding: 0; font-family: var(--font-family); background-color: var(--content-bg); }
        .dashboard-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background-color: var(--primary-blue); padding: 1.5rem; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar .logo { font-size: 1.8rem; font-weight: 800; color: var(--white-color); text-decoration: none; text-align: center; margin-bottom: 2.5rem; padding: 0.5rem 0; }
        .sidebar-menu h3 { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: rgba(255, 255, 255, 0.6); margin: 1.5rem 0 0.5rem 0.75rem; }
        .sidebar-menu ul { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu ul li a { display: flex; align-items: center; gap: 0.85rem; padding: 0.85rem 0.75rem; color: rgba(255, 255, 255, 0.8); text-decoration: none; border-radius: 8px; margin-bottom: 0.25rem; font-weight: 500; transition: all 0.2s ease; }
        .sidebar-menu ul li a .icon { font-size: 1.1rem; width: 20px; text-align: center; color: rgba(255, 255, 255, 0.8); transition: color 0.2s ease; }
        .sidebar-menu ul li a:hover { background-color: var(--primary-blue-dark); color: var(--white-color); }
        .sidebar-menu ul li a:hover .icon { color: var(--white-color); }
        .sidebar-menu ul li a.active { background-color: var(--white-color); color: var(--primary-blue-dark); font-weight: 700; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); }
        .sidebar-menu ul li a.active .icon { color: var(--primary-blue-dark); }
        .main-content-wrapper { flex: 1; padding: 2.5rem; overflow-y: auto; }
        .main-content-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; }
        .main-content-header h2 { font-size: 1.8rem; font-weight: 700; color: var(--dark-text); margin: 0; }

        /* Modern Table Styles with Animations */
        .panel { 
            background-color: var(--card-bg); 
            padding: 2rem; 
            border-radius: 12px; 
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        @keyframes fadeInRow { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .content-table {
            width: 100%;
            border-collapse: collapse;
        }

        .content-table th {
            text-align: left;
            padding: 1rem;
            color: var(--light-text);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
        }

        .content-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--dark-text);
            font-weight: 500;
            vertical-align: middle;
        }

        .content-table tbody tr {
            opacity: 0;
            animation: fadeInRow 0.5s ease-out forwards;
        }

        .content-table tbody tr:last-child td {
            border-bottom: none;
        }

        .content-table tbody tr:hover {
            background-color: var(--content-bg);
        }

        /* Status Badge Styles */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-open { background-color: #e0f2fe; color: #0284c7; }
        .status-in_progress { background-color: #fef3c7; color: #92400e; }
        .status-pending_agreement { background-color: #fffbeb; color: #b45309; }
        .status-completed { background-color: #d1fae5; color: #059669; }
        .status-cancelled { background-color: #fee2e2; color: #b91c1c; }
        
        .btn { padding: 0.5rem 1rem; text-decoration: none; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s ease; }
        .btn-primary { background-color: var(--primary-blue); color: var(--white-color); }
        .btn-primary:hover { background-color: var(--primary-blue-dark); }
        .btn-success { background-color: var(--success-color); color: var(--white-color); }
        .btn-success:hover { background-color: #059669; }
        .btn-warning { background-color: var(--warning-color); color: var(--white-color); }
        .btn-warning:hover { background-color: #d97706; }
        
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
                    <li><a href="employer_dashboard.php"><i class="fas fa-home icon"></i> Dashboard</a></li>
                    <li><a href="employer_post_job.php"><i class="fas fa-plus-circle icon"></i> Post a New Job</a></li>
                    <li><a href="employer_manage_jobs.php" class="active"><i class="fas fa-briefcase icon"></i> Manage Jobs</a></li>
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
                <h2>My Job Postings</h2>
                <a href="employer_post_job.php" class="btn btn-primary">Post a New Job</a>
            </div>
            
            <div class="panel">
                <table class="content-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Proposals</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($jobs) > 0): ?>
                            <?php 
                            $delay = 0;
                            foreach ($jobs as $job): 
                                $delay += 0.1; // Stagger the animation delay
                            ?>
                            <tr style="animation-delay: <?= $delay ?>s;">
                                <td><?= htmlspecialchars($job['title']) ?></td>
                                <td>
                                    <?php if ($job['status'] === 'open'): ?>
                                        <?= $job['proposal_count'] ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= str_replace(' ', '_', $job['status']) ?>">
                                        <?= str_replace('_', ' ', htmlspecialchars($job['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($job['status'] == 'in_progress'): ?>
                                        <?php if ($job['freelancer_status'] == 'completed'): ?>
                                            <a href="employer_complete_job.php?job_id=<?= $job['id'] ?>" class="btn btn-success">Review & Complete</a>
                                        <?php else: ?>
                                            <a href="messaging_page.php?job_id=<?= $job['id'] ?>" class="btn btn-primary">Messages</a>
                                        <?php endif; ?>
                                    <?php elseif ($job['status'] == 'open'): ?>
                                        <a href="employer_view_proposals.php?job_id=<?= $job['id'] ?>" class="btn btn-primary">View Proposals</a>
                                    <?php elseif ($job['status'] == 'pending_agreement'): ?>
                                        <a href="messaging_page.php?job_id=<?= $job['id'] ?>" class="btn btn-warning">View Inquiry</a>
                                    <?php else: ?>
                                        <span style="color: var(--light-text);">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">You have not posted any jobs yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>