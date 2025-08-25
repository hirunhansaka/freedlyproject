<?php
// employer_complete_job.php
session_start(); // ✅ Added this line
require 'database_connection.php';

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: user_login.php");
    exit();
}

$job_id = filter_input(INPUT_GET, 'job_id', FILTER_VALIDATE_INT);
if (!$job_id) {
    header("Location: employer_dashboard.php");
    exit();
}

// Verify this job belongs to the logged-in employer and is ready for completion
$stmt_job = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND employer_id = ? AND status = 'in_progress' AND freelancer_status = 'completed'");

// ⭐ FIX: Swapped the order of $job_id and $_SESSION['user_id'] to match the query
$stmt_job->execute([$job_id, $_SESSION['user_id']]);
$job = $stmt_job->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    // A simple error page is better here
    echo "<!DOCTYPE html><html><head><title>Error</title><style>body{font-family: sans-serif; text-align: center; padding: 50px;}</style></head><body><h1>Invalid Action</h1><p>This job is not ready for completion or you do not have permission to view it.</p><a href='employer_manage_jobs.php'>Back to My Jobs</a></body></html>";
    exit();
}

$error_message = '';
$success_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $comment = trim($_POST['comment']);
    $payment_confirmed = isset($_POST['payment_confirmed']);

    if (!$rating || $rating < 1 || $rating > 5) {
        $error_message = "Please select a rating between 1 and 5 stars.";
    } elseif (empty($comment)) {
        $error_message = "Please leave a comment for the freelancer.";
    } elseif (!$payment_confirmed) {
        $error_message = "Please confirm that the payment has been sent.";
    } else {
        $pdo->beginTransaction();
        try {
            // 1. Insert the review
            $stmt_insert = $pdo->prepare("INSERT INTO reviews (job_id, freelancer_id, employer_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
            $stmt_insert->execute([$job_id, $job['assigned_freelancer_id'], $_SESSION['user_id'], $rating, $comment]);

            // 2. Update the job status to 'completed'
            $stmt_update = $pdo->prepare("UPDATE jobs SET status = 'completed' WHERE id = ?");
            $stmt_update->execute([$job_id]);

            $pdo->commit();
            $success_message = "Thank you for your feedback! The job has now been marked as completed.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "An error occurred. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Project - Freedly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* --- Global, Layout, and Sidebar Styles --- */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        :root {
            --primary-blue: #0d6efd; --primary-blue-dark: #0a58ca; --content-bg: #f4f7fa;
            --card-bg: #ffffff; --dark-text: #111827; --light-text: #6b7280;
            --border-color: #e5e7eb; --white-color: #ffffff; --warning-color: #f59e0b;
            --font-family: 'Inter', sans-serif;
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
        .main-content-header h2 { font-size: 1.8rem; font-weight: 700; color: var(--dark-text); margin: 0 0 0.5rem 0; }
        .main-content-header p { color: var(--light-text); margin: 0 0 2.5rem 0; }

        /* Styles for the form card and elements */
        .form-card { background-color: var(--card-bg); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--dark-text); }
        .form-group textarea { width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 1rem; font-family: var(--font-family); box-sizing: border-box; transition: all 0.3s ease; min-height: 120px; resize: vertical; }
        .form-group textarea:focus { border-color: var(--primary-blue); box-shadow: 0 0 0 3px rgba(13,110,253,0.15); outline: none; }
        .btn { display: inline-block; padding: 0.8rem 1.5rem; text-decoration: none; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s ease; }
        .btn-primary { background-color: var(--primary-blue); color: var(--white-color); font-size: 1rem; width: 100%; }
        .btn-primary:hover { background-color: var(--primary-blue-dark); transform: translateY(-2px); }
        .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; }
        .alert-danger { background-color: #fee2e2; color: #b91c1c; }
        .alert-success { background-color: #d1fae5; color: #059669; }
        .success-card { text-align: center; }

        /* Modern Star Rating */
        .star-rating { display: flex; flex-direction: row-reverse; justify-content: center; gap: 5px; }
        .star-rating input { display: none; }
        .star-rating label { font-size: 2.5rem; color: var(--border-color); cursor: pointer; transition: color 0.2s ease, transform 0.2s ease; }
        .star-rating label:hover { transform: scale(1.1); }
        .star-rating :checked ~ label, .star-rating label:hover, .star-rating label:hover ~ label { color: var(--warning-color); }

        /* Modern Custom Checkbox */
        .custom-checkbox { display: block; position: relative; padding-left: 35px; margin-bottom: 12px; cursor: pointer; font-size: 1rem; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none; }
        .custom-checkbox input { position: absolute; opacity: 0; cursor: pointer; height: 0; width: 0; }
        .checkmark { position: absolute; top: 0; left: 0; height: 24px; width: 24px; background-color: #eee; border-radius: 6px; transition: background-color 0.2s ease; }
        .custom-checkbox:hover input ~ .checkmark { background-color: #ccc; }
        .custom-checkbox input:checked ~ .checkmark { background-color: var(--primary-blue); }
        .checkmark:after { content: ""; position: absolute; display: none; }
        .custom-checkbox input:checked ~ .checkmark:after { display: block; }
        .custom-checkbox .checkmark:after { left: 9px; top: 5px; width: 5px; height: 10px; border: solid white; border-width: 0 3px 3px 0; -webkit-transform: rotate(45deg); -ms-transform: rotate(45deg); transform: rotate(45deg); }

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
                <h2>Complete Project & Leave a Review</h2>
                <p>Finalize the project: "<?= htmlspecialchars($job['title']) ?>"</p>
            </div>
            
            <div class="form-card">
                 <?php if (!empty($success_message)): ?>
                    <div class="success-card">
                        <h3>Thank You!</h3>
                        <p class="alert alert-success"><?= htmlspecialchars($success_message) ?></p>
                        <a href="employer_manage_jobs.php" class="btn btn-primary" style="width: auto;">Back to My Jobs</a>
                    </div>
                <?php else: ?>
                    <?php if (!empty($error_message)): ?><p class="alert alert-danger"><?= htmlspecialchars($error_message) ?></p><?php endif; ?>
                    <form action="employer_complete_job.php?job_id=<?= $job_id ?>" method="POST">
                        <div class="form-group" style="text-align: center;">
                            <label>Your Rating *</label>
                            <div class="star-rating">
                                <input type="radio" id="5-stars" name="rating" value="5" /><label for="5-stars" class="star">&#9733;</label>
                                <input type="radio" id="4-stars" name="rating" value="4" /><label for="4-stars" class="star">&#9733;</label>
                                <input type="radio" id="3-stars" name="rating" value="3" /><label for="3-stars" class="star">&#9733;</label>
                                <input type="radio" id="2-stars" name="rating" value="2" /><label for="2-stars" class="star">&#9733;</label>
                                <input type="radio" id="1-star" name="rating" value="1" /><label for="1-star" class="star">&#9733;</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="comment">Feedback Comment *</label>
                            <textarea id="comment" name="comment" required placeholder="Share your experience working with this freelancer..."></textarea>
                        </div>
                        <div class="form-group">
                            <label class="custom-checkbox">I confirm that the payment has been sent for this project.
                                <input type="checkbox" id="payment_confirmed" name="payment_confirmed" value="1">
                                <span class="checkmark"></span>
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Review & Complete Job</button>
                    </form>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>