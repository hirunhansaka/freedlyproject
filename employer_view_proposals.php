<?php
// employer_view_proposals.php
require 'database_connection.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') { header("Location: user_login.php"); exit(); }
$job_id = filter_input(INPUT_GET, 'job_id', FILTER_VALIDATE_INT);
if (!$job_id) { header("Location: employer_manage_jobs.php"); exit(); }

// Action: Accept a proposal
if (isset($_POST['accept_proposal'])) {
    $proposal_id = $_POST['proposal_id'];
    $freelancer_id = $_POST['freelancer_id'];
    
    $pdo->beginTransaction();
    try {
        // Update job status and assign freelancer
        $stmt1 = $pdo->prepare("UPDATE jobs SET status = 'in_progress', assigned_freelancer_id = ? WHERE id = ? AND employer_id = ?");
        $stmt1->execute([$freelancer_id, $job_id, $_SESSION['user_id']]);
        
        // Update this proposal status to accepted
        $stmt2 = $pdo->prepare("UPDATE proposals SET status = 'accepted' WHERE id = ?");
        $stmt2->execute([$proposal_id]);

        // Reject all other proposals for this job
        $stmt3 = $pdo->prepare("UPDATE proposals SET status = 'rejected' WHERE job_id = ? AND id != ?");
        $stmt3->execute([$job_id, $proposal_id]);

        $pdo->commit();
        header("Location: employer_view_proposals.php?job_id=$job_id&success=1");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("An error occurred: " . $e->getMessage());
    }
}

// Fetch job details
$stmt_job = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND employer_id = ?");
$stmt_job->execute([$job_id, $_SESSION['user_id']]);
$job = $stmt_job->fetch(PDO::FETCH_ASSOC);

if (!$job) { header("Location: employer_manage_jobs.php"); exit(); }

// Fetch proposals with freelancer details including profile picture
$stmt_proposals = $pdo->prepare("
    SELECT p.*, u.username, u.profile_title, u.profile_bio, u.profile_picture 
    FROM proposals p 
    JOIN users u ON p.freelancer_id = u.id 
    WHERE p.job_id = ? 
    ORDER BY p.status ASC, p.submitted_at DESC
");
$stmt_proposals->execute([$job_id]);
$proposals = $stmt_proposals->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposals for <?= htmlspecialchars($job['title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* --- Global, Layout, and Sidebar Styles --- */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        :root {
            --primary-blue: #0d6efd; --primary-blue-dark: #0a58ca; --content-bg: #f4f7fa;
            --card-bg: #ffffff; --dark-text: #111827; --light-text: #6b7280;
            --border-color: #e5e7eb; --white-color: #ffffff; --success-color: #10b981;
            --warning-color: #f59e0b; --danger-color: #ef4444; --font-family: 'Inter', sans-serif;
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
        .main-content-header { margin-bottom: 2.5rem; }
        .main-content-header h2 { font-size: 1.8rem; font-weight: 700; color: var(--dark-text); margin: 0 0 0.5rem 0; }
        .main-content-header a.back-link { color: var(--primary-blue); text-decoration: none; font-weight: 500; }
        .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; }
        .alert-success { background-color: #d1fae5; color: #059669; }

        /* ✅ NEW: Proposal List & Card Styles */
        .proposals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .proposal-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            opacity: 0;
            animation: fadeIn 0.5s ease-out forwards;
        }
        .proposal-card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        .proposal-avatar { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
        .freelancer-info h3 { margin: 0; font-size: 1.1rem; }
        .freelancer-info p { margin: 0.25rem 0 0 0; color: var(--light-text); font-size: 0.9rem; }
        
        .proposal-card-body { padding: 1.5rem; flex-grow: 1; color: var(--light-text); }
        .proposal-card-footer { padding: 1rem 1.5rem; background-color: #f9fafb; border-top: 1px solid var(--border-color); border-radius: 0 0 12px 12px; }
        
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 50px; font-size: 0.8rem; font-weight: 600; text-transform: capitalize; }
        .status-pending { background-color: #fffbeb; color: #b45309; }
        .status-accepted { background-color: #d1fae5; color: #059669; }
        .status-rejected { background-color: #fee2e2; color: #b91c1c; }

        .btn { padding: 0.6rem 1.2rem; text-decoration: none; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s ease; }
        .btn-primary { background-color: var(--primary-blue); color: var(--white-color); }
        .btn-success { background-color: var(--success-color); color: var(--white-color); }
        .btn-success:hover { background-color: #059669; }
        
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
                <div>
                    <h2>Proposals for "<?= htmlspecialchars($job['title']) ?>"</h2>
                    <a href="employer_manage_jobs.php" class="back-link">&larr; Back to My Jobs</a>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?><p class="alert alert-success">Freelancer has been hired! You can now start the conversation from the 'Manage Jobs' page.</p><?php endif; ?>
            
            <div class="proposals-grid">
                <?php if (count($proposals) > 0): ?>
                    <?php 
                    $delay = 0;
                    foreach ($proposals as $proposal): 
                        $delay += 0.1;
                        // Determine status class for the badge
                        $status_class = '';
                        if ($proposal['status'] == 'pending') { $status_class = 'status-pending'; } 
                        elseif ($proposal['status'] == 'accepted') { $status_class = 'status-accepted'; } 
                        elseif ($proposal['status'] == 'rejected') { $status_class = 'status-rejected'; }
                    ?>
                        <div class="proposal-card" style="animation-delay: <?= $delay ?>s;">
                            <div class="proposal-card-header">
                                <img src="uploads/<?= htmlspecialchars($proposal['profile_picture']) ?>" alt="avatar" class="proposal-avatar">
                                <div class="freelancer-info">
                                    <h3><?= htmlspecialchars($proposal['username']) ?></h3>
                                    <p><?= htmlspecialchars($proposal['profile_title'] ?? 'Freelancer') ?></p>
                                </div>
                                <span class="status-badge <?= $status_class ?>" style="margin-left: auto;"><?= htmlspecialchars($proposal['status']) ?></span>
                            </div>
                            <div class="proposal-card-body">
                                <p><?= nl2br(htmlspecialchars($proposal['proposal_text'])) ?></p>
                            </div>
                            <div class="proposal-card-footer">
                                <?php if ($job['status'] === 'open' && $proposal['status'] === 'pending'): ?>
                                <form action="" method="POST">
                                    <input type="hidden" name="proposal_id" value="<?= $proposal['id'] ?>">
                                    <input type="hidden" name="freelancer_id" value="<?= $proposal['freelancer_id'] ?>">
                                    <button type="submit" name="accept_proposal" class="btn btn-success">Accept & Hire</button>
                                </form>
                                <?php elseif ($proposal['status'] === 'accepted'): ?>
                                    <a href="messaging_page.php?job_id=<?= $job_id ?>" class="btn btn-primary">Go to Conversation</a>
                                <?php else: ?>
                                    <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="panel" style="text-align:center;">
                        <p>No proposals have been submitted for this job yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>