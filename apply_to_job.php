<?php
// apply_to_job.php
require 'database_connection.php';
$job_id = filter_input(INPUT_GET, 'job_id', FILTER_VALIDATE_INT);
if (!$job_id) { header("Location: index.php"); exit(); }

// We need to check the job status here. A freelancer can apply only if it's 'open'.
// However, an employer might want to view the page even if it's in progress.
// So we fetch it regardless of status first, then check it in the HTML.
$stmt = $pdo->prepare("SELECT jobs.*, users.username AS employer_name, users.profile_picture as employer_avatar FROM jobs JOIN users ON jobs.employer_id = users.id WHERE jobs.id = ?");
$stmt->execute([$job_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) { 
    // A simple error page is better here.
    echo "<!DOCTYPE html><html><head><title>Error</title><style>body{font-family: sans-serif; text-align: center; padding-top: 50px;}</style></head><body><h1>Job Not Found</h1><p>The job you are looking for does not exist or has been removed.</p><a href='index.php'>Go to Homepage</a></body></html>";
    exit(); 
}

$error_message = ''; $success_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id']) && $_SESSION['role'] === 'freelancer') {
    $proposal_text = trim($_POST['proposal_text']);
    
    // Check if already applied
    $check_stmt = $pdo->prepare("SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?");
    $check_stmt->execute([$job_id, $_SESSION['user_id']]);
    if ($check_stmt->fetch()) {
        $error_message = "You have already applied for this job.";
    } elseif (empty($proposal_text)) {
        $error_message = "Your proposal cannot be empty.";
    } elseif ($job['status'] !== 'open') {
        $error_message = "This job is no longer accepting applications.";
    } else {
        $insert_stmt = $pdo->prepare("INSERT INTO proposals (job_id, freelancer_id, proposal_text) VALUES (?, ?, ?)");
        if($insert_stmt->execute([$job_id, $_SESSION['user_id'], $proposal_text])) {
            $success_message = "Your proposal has been submitted successfully!";
        } else {
            $error_message = "There was an error submitting your proposal.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for: <?= htmlspecialchars($job['title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        :root {
            --primary-blue: #0d6efd; --primary-blue-dark: #0a58ca; --primary-blue-light: #3c8cff;
            --content-bg: #f4f7fa; --card-bg: #ffffff; --dark-text: #111827;
            --light-text: #6b7280; --border-color: #e5e7eb; --white-color: #ffffff;
            --success-color: #10b981; --font-family: 'Inter', sans-serif;
        }
        body, html { margin: 0; padding: 0; font-family: var(--font-family); background-color: var(--content-bg); }
        
        /* Using the modern header style from index.php */
        .header { background-color: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); padding: 0.75rem 0; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); position: sticky; top: 0; z-index: 1000; }
        .header .container { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
        .header .logo { font-size: 1.8rem; font-weight: 800; color: var(--primary-blue); text-decoration: none; }
        .header .nav { display: flex; align-items: center; gap: 1.5rem; }
        .header .nav a { text-decoration: none; color: var(--light-text); font-weight: 600; transition: color 0.2s ease; }
        .header .nav a:hover { color: var(--primary-blue); }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 50px; font-weight: 700; border: none; cursor: pointer; transition: all 0.3s ease; }
        .btn-primary { background-color: var(--primary-blue); color: var(--white-color); }
        .btn-primary:hover { background-color: var(--primary-blue-dark); transform: translateY(-2px); }

        /* Main layout for the apply page */
        .apply-page-wrapper {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        /* Job Details Column (Left) */
        .job-details-column { animation: fadeIn 0.5s 0.1s ease-out forwards; opacity: 0; }
        .job-details-card {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.06);
        }
        .job-details-card h2 { font-size: 1.8rem; margin: 0 0 1rem 0; color: var(--dark-text); }
        .job-meta-info { display: flex; flex-wrap: wrap; gap: 1.5rem; margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); }
        .meta-item { display: flex; align-items: center; gap: 0.5rem; color: var(--light-text); }
        .meta-item .icon { font-size: 1.2rem; color: var(--primary-blue); }
        .meta-item span { font-weight: 600; color: var(--dark-text); }
        .job-description h3 { font-size: 1.2rem; margin-bottom: 1rem; }
        .job-description p { line-height: 1.7; color: var(--light-text); }

        /* Proposal Form Column (Right) */
        .proposal-form-column { animation: fadeIn 0.5s 0.2s ease-out forwards; opacity: 0; }
        .proposal-form-card { background: var(--card-bg); padding: 2rem; border-radius: 16px; box-shadow: 0 8px 30px rgba(0,0,0,0.06); }
        .proposal-form-card h3 { font-size: 1.2rem; margin: 0 0 1.5rem 0; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group textarea {
            width: 100%; padding: 1rem; border: 1px solid var(--border-color); border-radius: 8px;
            font-size: 1rem; font-family: var(--font-family); box-sizing: border-box;
            min-height: 150px; resize: vertical; transition: all 0.3s ease;
        }
        .form-group textarea:focus { border-color: var(--primary-blue); box-shadow: 0 0 0 3px rgba(13,110,253,0.15); outline: none; }
        .btn-submit { width: 100%; }

        .alert { padding: 1rem; margin-top: 1rem; border-radius: 8px; font-weight: 500; }
        .alert.info { background-color: #e0f2fe; color: #0284c7; }
        .alert a { color: var(--primary-blue-dark); font-weight: 700; text-decoration: none; }
        .alert a:hover { text-decoration: underline; }
        .alert-danger { background-color: #fee2e2; color: #b91c1c; }
        .alert-success { background-color: #d1fae5; color: #059669; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 992px) { .apply-page-wrapper { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <a href="index.php" class="logo">Freedly</a>
            <nav class="nav">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="<?= $_SESSION['role'] ?>_dashboard.php">Dashboard</a>
                    <a href="user_logout.php">Logout</a>
                <?php else: ?>
                    <a href="user_login.php">Login</a>
                    <a href="user_register.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="apply-page-wrapper">
        <div class="job-details-column">
            <div class="job-details-card">
                <h2><?= htmlspecialchars($job['title']) ?></h2>
                <div class="job-meta-info">
                    <div class="meta-item">
                        <i class="fas fa-user icon"></i>
                        Posted by <span><?= htmlspecialchars($job['employer_name']) ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-tag icon"></i>
                        Category: <span><?= htmlspecialchars($job['category']) ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-dollar-sign icon"></i>
                        Budget: <span style="color: var(--success-color);">$<?= htmlspecialchars($job['budget']) ?></span>
                    </div>
                </div>
                <div class="job-description">
                    <h3>Job Description</h3>
                    <p><?= nl2br(htmlspecialchars($job['description'])) ?></p>
                </div>
            </div>
        </div>

        <div class="proposal-form-column">
            <div class="proposal-form-card">
                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'freelancer'): ?>
                    <?php if ($job['status'] !== 'open'): ?>
                        <div class="alert info">This job is no longer accepting new applications.</div>
                    <?php else: ?>
                        <h3>Submit Your Proposal</h3>
                        <?php if (!empty($error_message)): ?><p class="alert alert-danger"><?= htmlspecialchars($error_message) ?></p><?php endif; ?>
                        <?php if (!empty($success_message)): ?><p class="alert alert-success"><?= $success_message ?></p><?php endif; ?>
                        
                        <form action="apply_to_job.php?job_id=<?= $job_id ?>" method="POST">
                            <div class="form-group">
                                <label for="proposal_text">Why are you the best fit for this job?</label>
                                <textarea id="proposal_text" name="proposal_text" required placeholder="Introduce yourself, highlight your relevant skills, and explain your approach to the project..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-submit">Submit Proposal</button>
                        </form>
                    <?php endif; ?>
                <?php elseif (isset($_SESSION['user_id']) && $_SESSION['role'] === 'employer'): ?>
                    <div class="alert info">You are logged in as an employer. You cannot apply for jobs.</div>
                <?php else: ?>
                    <div class="alert info">
                        <p><strong>You must be logged in as a freelancer to apply.</strong></p>
                        <a href="user_login.php">Log In</a> or <a href="user_register.php">Create a Freelancer Account</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>