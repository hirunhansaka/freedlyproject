<?php
session_start(); // Must be at the very top
require 'database_connection.php';

// 1. Authentication and Authorization Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// 2. Handle all form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // --- Form 1: Update Main Profile Info ---
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        $profile_title = trim($_POST['profile_title']);
        $profile_bio = trim($_POST['profile_bio']);
        $id_number = trim($_POST['id_number']);
        $address = trim($_POST['address']);

        // Safely get the current picture filename from the hidden input
        $current_picture = isset($_POST['current_picture']) ? $_POST['current_picture'] : '';
        $new_profile_picture_filename = $current_picture;

        if (empty($username) || empty($id_number) || empty($address)) {
            $error_message = "Full Name, ID Number, and Address are required fields.";
        } else {
            // --- Handle New Profile Picture Upload ---
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
                $target_dir = "uploads/";
                $file_info = pathinfo($_FILES["profile_picture"]["name"]);
                $file_extension = strtolower($file_info['extension']);
                $allowed_extensions = ["jpg", "jpeg", "png"];

                if (!in_array($file_extension, $allowed_extensions)) {
                    $error_message = "Sorry, only JPG, JPEG, & PNG files are allowed for the profile picture.";
                } else {
                    // Create a unique filename to prevent overwrites
                    $unique_filename = uniqid('user_', true) . '.' . $file_extension;
                    $target_file = $target_dir . $unique_filename;

                    if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                        $new_profile_picture_filename = $unique_filename;
                        
                        // ✅ FIXED: Delete the old picture if it exists and is different from the new one
                        if (!empty($current_picture) && $current_picture !== $new_profile_picture_filename && file_exists($target_dir . $current_picture)) {
                            unlink($target_dir . $current_picture);
                        }
                    } else {
                        $error_message = "Sorry, there was an error uploading your profile picture.";
                    }
                }
            }
            
            // --- Update Database if no errors occurred ---
            if (empty($error_message)) {
                $sql = "UPDATE users SET username = ?, profile_title = ?, profile_bio = ?, profile_picture = ?, id_number = ?, address = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute([$username, $profile_title, $profile_bio, $new_profile_picture_filename, $id_number, $address, $user_id])) {
                    $_SESSION['username'] = $username; // Update session username
                    $success_message = "Profile updated successfully!";
                } else {
                    $error_message = "Failed to update profile. Please try again.";
                }
            }
        }
    }

    // --- Form 2: Add a new Skill ---
    if (isset($_POST['add_skill'])) {
        $skill_name = trim($_POST['skill_name']);
        if (!empty($skill_name)) {
            $stmt = $pdo->prepare("INSERT INTO freelancer_skills (freelancer_id, skill_name) VALUES (?, ?)");
            $stmt->execute([$user_id, $skill_name]);
            $success_message = "Skill added!";
        } else { $error_message = "Skill name cannot be empty."; }
    }

    // --- Form 3: Delete a Skill ---
    if (isset($_POST['delete_skill'])) {
        $skill_id = $_POST['skill_id'];
        $stmt = $pdo->prepare("DELETE FROM freelancer_skills WHERE id = ? AND freelancer_id = ?");
        $stmt->execute([$skill_id, $user_id]);
        $success_message = "Skill removed!";
    }

    // --- Form 4: Add Education ---
    if (isset($_POST['add_education'])) {
        $degree = trim($_POST['degree']);
        $institution = trim($_POST['institution']);
        $year_completed = filter_var($_POST['year_completed'], FILTER_VALIDATE_INT);
        if (!empty($degree) && !empty($institution) && $year_completed) {
            $stmt = $pdo->prepare("INSERT INTO freelancer_education (freelancer_id, degree, institution, year_completed) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $degree, $institution, $year_completed]);
            $success_message = "Education added!";
        } else { $error_message = "Please fill all education fields correctly."; }
    }

    // --- Form 5: Delete Education ---
     if (isset($_POST['delete_education'])) {
        $education_id = $_POST['education_id'];
        $stmt = $pdo->prepare("DELETE FROM freelancer_education WHERE id = ? AND freelancer_id = ?");
        $stmt->execute([$education_id, $user_id]);
        $success_message = "Education record removed!";
    }
}

// 3. Fetch all current profile data for display
$stmt_user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC);

$stmt_skills = $pdo->prepare("SELECT * FROM freelancer_skills WHERE freelancer_id = ?");
$stmt_skills->execute([$user_id]);
$skills = $stmt_skills->fetchAll(PDO::FETCH_ASSOC);

$stmt_education = $pdo->prepare("SELECT * FROM freelancer_education WHERE freelancer_id = ? ORDER BY year_completed DESC");
$stmt_education->execute([$user_id]);
$educations = $stmt_education->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profile - Freedly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* --- Global, Layout, and Sidebar Styles (Same as your other dashboard pages) --- */
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

        /* Styles for Tabs, Forms, and Item Lists */
        .tabs { border-bottom: 2px solid var(--border-color); display: flex; margin-bottom: 2rem; }
        .tab-link { background: none; border: none; padding: 1rem 1.5rem; font-size: 1rem; font-weight: 600; cursor: pointer; color: var(--light-text); border-bottom: 3px solid transparent; margin-bottom: -2px; transition: color 0.3s ease, border-color 0.3s ease; }
        .tab-link:hover { color: var(--primary-blue); }
        .tab-link.active { color: var(--dark-text); border-bottom-color: var(--primary-blue); }
        .tab-content { display: none; animation: fadeIn 0.5s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .form-card { background-color: var(--card-bg); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
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
        .item-list { list-style: none; padding: 0; }
        .item-list li { display: flex; justify-content: space-between; align-items: center; background-color: var(--content-bg); padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .item-list small { color: var(--light-text); }
        .delete-btn { background: none; border: none; color: var(--danger-color); font-size: 1.2rem; cursor: pointer; transition: transform 0.2s ease; }
        .delete-btn:hover { transform: scale(1.2); }
        .add-item-form { display: flex; gap: 1rem; margin-top: 1.5rem; }
        .add-item-form input { flex-grow: 1; }
        .add-item-form button { width: auto; }
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
                    <li><a href="freelancer_manage_services.php"><i class="fas fa-tasks icon"></i> Manage Services</a></li>
                </ul>
                <h3>Account</h3>
                <ul>
                    <li><a href="freelancer_manage_profile.php" class="active"><i class="fas fa-user-circle icon"></i> Manage Profile</a></li>
                    <li><a href="user_report_admin.php"><i class="fas fa-exclamation-triangle icon"></i> Report to Admin</a></li>
                    <li><a href="user_logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a></li>
                </ul>
            </div>
        </aside>

        <main class="main-content-wrapper">
            <div class="main-content-header">
                <h2>Manage Your Profile</h2>
                <p>Keep your professional information up to date.</p>
            </div>
            
            <?php if (!empty($error_message)): ?><p class="alert alert-danger"><?= htmlspecialchars($error_message) ?></p><?php endif; ?>
            <?php if (!empty($success_message)): ?><p class="alert alert-success"><?= htmlspecialchars($success_message) ?></p><?php endif; ?>

            <div class="tabs">
                <button class="tab-link active" onclick="openTab(event, 'PersonalInfo')">Personal Info</button>
                <button class="tab-link" onclick="openTab(event, 'Skills')">Skills</button>
                <button class="tab-link" onclick="openTab(event, 'Education')">Education</button>
            </div>

            <div id="PersonalInfo" class="tab-content">
                <div class="form-card">
                    <form action="freelancer_manage_profile.php" method="POST" enctype="multipart/form-data">
                        
                        <?php if (!empty($user['profile_picture'])): ?>
                        <div class="form-group">
                            <label>Current Profile Picture</label><br>
                            <img src="uploads/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Picture" width="120" style="border-radius: 8px;">
                        </div>
                        <?php endif; ?>

                        <input type="hidden" name="current_picture" value="<?= htmlspecialchars($user['profile_picture'] ?? '') ?>">
                        
                        <div class="form-group"><label for="profile_picture">Upload New Profile Picture</label><input type="file" id="profile_picture" name="profile_picture"></div>
                        <div class="form-group"><label for="username">Full Name *</label><input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required></div>
                        <div class="form-group"><label for="id_number">National ID / Passport Number *</label><input type="text" id="id_number" name="id_number" value="<?= htmlspecialchars($user['id_number'] ?? '') ?>" required></div>
                        <div class="form-group"><label for="address">Address *</label><textarea id="address" name="address" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea></div>
                        <div class="form-group"><label for="profile_title">Profile Title (e.g., Senior PHP Developer)</label><input type="text" id="profile_title" name="profile_title" value="<?= htmlspecialchars($user['profile_title'] ?? '') ?>"></div>
                        <div class="form-group"><label for="profile_bio">Bio / About You</label><textarea id="profile_bio" name="profile_bio" rows="4"><?= htmlspecialchars($user['profile_bio'] ?? '') ?></textarea></div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Save Personal Info</button>
                    </form>
                </div>
            </div>

            <div id="Skills" class="tab-content">
                <div class="form-card">
                    <h3>Your Skills</h3>
                    <ul class="item-list">
                        <?php foreach($skills as $skill): ?>
                        <li><span><?= htmlspecialchars($skill['skill_name']) ?></span>
                            <form action="freelancer_manage_profile.php" method="POST" onsubmit="return confirm('Delete this skill?');">
                                <input type="hidden" name="skill_id" value="<?= $skill['id'] ?>"><button type="submit" name="delete_skill" class="delete-btn" title="Remove skill">&times;</button>
                            </form>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <form action="freelancer_manage_profile.php" method="POST" class="add-item-form">
                        <input type="text" name="skill_name" placeholder="Add a new skill (e.g., PHP, Photoshop)" required>
                        <button type="submit" name="add_skill" class="btn btn-primary">Add Skill</button>
                    </form>
                </div>
            </div>

            <div id="Education" class="tab-content">
                 <div class="form-card">
                    <h3>Your Education</h3>
                    <ul class="item-list">
                        <?php foreach($educations as $edu): ?>
                        <li>
                            <div><strong><?= htmlspecialchars($edu['degree']) ?></strong><br><small><?= htmlspecialchars($edu['institution']) ?> - <?= htmlspecialchars($edu['year_completed']) ?></small></div>
                            <form action="freelancer_manage_profile.php" method="POST" onsubmit="return confirm('Delete this education record?');">
                                <input type="hidden" name="education_id" value="<?= $edu['id'] ?>"><button type="submit" name="delete_education" class="delete-btn" title="Remove record">&times;</button>
                            </form>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <form action="freelancer_manage_profile.php" method="POST" style="margin-top: 2rem;">
                        <h4>Add New Education Record</h4>
                        <div class="form-group"><input type="text" name="degree" placeholder="Degree (e.g., B.Sc. in Computer Science)" required></div>
                        <div class="form-group"><input type="text" name="institution" placeholder="Institution (e.g., University of Colombo)" required></div>
                        <div class="form-group"><input type="number" name="year_completed" placeholder="Year Completed (e.g., 2025)" min="1950" max="2099" required></div>
                        <button type="submit" name="add_education" class="btn btn-primary">Add Education</button>
                    </form>
                 </div>
            </div>
        </main>
    </div>

    <script>
        function openTab(evt, tabName) {
            let i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tab-link");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }

        // Automatically open the first tab on page load
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelector('.tab-link').click();
        });
    </script>
</body>
</html>