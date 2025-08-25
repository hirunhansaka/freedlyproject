<?php
// employer_manage_profile.php
require 'database_connection.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: user_login.php"); exit();
}

$user_id = $_SESSION['user_id'];
$error_message = ''; $success_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $company_name = trim($_POST['company_name']);
    $profile_bio = trim($_POST['profile_bio']);
    $current_picture = $_POST['current_picture'];
    $profile_picture = $current_picture;

    // Handle file upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $target_dir = "uploads/";
        $file_name = time() . '_' . basename($_FILES["profile_picture"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
            $error_message = "Sorry, only JPG, JPEG, & PNG files are allowed.";
        } elseif (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            $profile_picture = $file_name;
        } else {
            $error_message = "Sorry, there was an error uploading your file.";
        }
    }
    
    if (empty($error_message)) {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, company_name = ?, profile_bio = ?, profile_picture = ? WHERE id = ?");
        if ($stmt->execute([$username, $company_name, $profile_bio, $profile_picture, $user_id])) {
            $_SESSION['username'] = $username;
            $success_message = "Profile updated successfully!";
        } else {
            $error_message = "Failed to update profile.";
        }
    }
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profile - Freedly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* --- Global, Layout, and Sidebar Styles --- */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        :root {
            --primary-blue: #0d6efd; --primary-blue-dark: #0a58ca; --content-bg: #f4f7fa;
            --card-bg: #ffffff; --dark-text: #111827; --light-text: #6b7280;
            --border-color: #e5e7eb; --white-color: #ffffff; --danger-color: #ef4444;
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

        /* Styles for Forms and Cards */
        .form-card { background-color: var(--card-bg); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); animation: fadeIn 0.5s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--dark-text); }
        .form-group input, .form-group textarea { width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 1rem; font-family: var(--font-family); box-sizing: border-box; transition: all 0.3s ease; }
        .form-group input:focus, .form-group textarea:focus { border-color: var(--primary-blue); box-shadow: 0 0 0 3px rgba(13,110,253,0.15); outline: none; }
        .btn { display: inline-block; padding: 0.8rem 1.5rem; text-decoration: none; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s ease; }
        .btn-primary { background-color: var(--primary-blue); color: var(--white-color); }
        .btn-primary:hover { background-color: var(--primary-blue-dark); transform: translateY(-2px); }
        .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; }
        .alert-danger { background-color: #fee2e2; color: #b91c1c; }
        .alert-success { background-color: #d1fae5; color: #059669; }
        
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
                    <li><a href="employer_manage_jobs.php"><i class="fas fa-briefcase icon"></i> Manage Jobs</a></li>
                </ul>
                <h3>Account</h3>
                <ul>
                    <li><a href="employer_manage_profile.php" class="active"><i class="fas fa-user-circle icon"></i> Manage Profile</a></li>
                    <li><a href="user_report_admin.php"><i class="fas fa-exclamation-triangle icon"></i> Report to Admin</a></li>
                    <li><a href="user_logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a></li>
                </ul>
            </div>
        </aside>

        <main class="main-content-wrapper">
            <div class="main-content-header">
                <h2>Manage Your Profile</h2>
                <p>Keep your public-facing information up to date.</p>
            </div>

            <?php if (!empty($error_message)): ?><p class="alert alert-danger"><?= htmlspecialchars($error_message) ?></p><?php endif; ?>
            <?php if (!empty($success_message)): ?><p class="alert alert-success"><?= htmlspecialchars($success_message) ?></p><?php endif; ?>
            
            <div class="form-card">
                <form action="employer_manage_profile.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Current Profile Picture</label><br>
                        <img src="uploads/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Picture" width="120" style="border-radius: 8px;">
                    </div>
                    <div class="form-group">
                        <label for="profile_picture">Upload New Profile Picture</label>
                        <input type="file" id="profile_picture" name="profile_picture">
                        <input type="hidden" name="current_picture" value="<?= htmlspecialchars($user['profile_picture']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="username">Your Name</label>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="company_name">Company Name (Optional)</label>
                        <input type="text" id="company_name" name="company_name" value="<?= htmlspecialchars($user['company_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="profile_bio">About Your Company</label>
                        <textarea id="profile_bio" name="profile_bio" rows="4"><?= htmlspecialchars($user['profile_bio'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Save Profile</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>