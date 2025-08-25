<?php
// user_profile.php
require 'database_connection.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php"); exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$dashboard_link = $_SESSION['role'] . '_dashboard.php';
// This link logic is now for the button at the bottom of the page
if ($_SESSION['role'] === 'freelancer') {
    $profile_edit_link = 'freelancer_manage_profile.php';
} elseif ($_SESSION['role'] === 'employer') {
    $profile_edit_link = 'employer_manage_profile.php';
} else {
    $profile_edit_link = '#'; // Admin has no edit profile page in this design
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Freedly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        :root {
            --primary-blue: #0d6efd; --primary-blue-dark: #0a58ca; --content-bg: #f4f7fa;
            --card-bg: #ffffff; --dark-text: #111827; --light-text: #6b7280;
            --border-color: #e5e7eb; --white-color: #ffffff;
            --font-family: 'Inter', sans-serif;
        }
        body, html { margin: 0; padding: 0; font-family: var(--font-family); background-color: var(--content-bg); }
        
        /* Modern Header (from index.php) */
        .header { background-color: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); padding: 0.75rem 0; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); position: sticky; top: 0; z-index: 1000; }
        .header .container { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
        .header .logo { font-size: 1.8rem; font-weight: 800; color: var(--primary-blue); text-decoration: none; }
        .header .nav { display: flex; align-items: center; gap: 1.5rem; }
        .header .nav a { text-decoration: none; color: var(--light-text); font-weight: 600; transition: color 0.2s ease; }
        .header .nav a:hover { color: var(--primary-blue); }

        /* Animations */
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        /* Profile Page Layout */
        .profile-page-wrapper {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .profile-header {
            background-image: linear-gradient(45deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
            height: 200px;
            border-radius: 16px;
            position: relative;
            animation: fadeIn 0.6s ease-out;
        }

        .profile-avatar {
            position: absolute;
            bottom: -75px; /* Pulls the avatar down to overlap */
            left: 50%;
            transform: translateX(-50%);
        }

        .profile-avatar img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 6px solid var(--white-color);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .profile-content-card {
            background-color: var(--card-bg);
            padding: 6rem 2rem 2rem 2rem; /* Top padding to make space for avatar */
            margin-top: -50px; /* Pulls the card up under the avatar */
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            text-align: center;
            opacity: 0;
            animation: slideUp 0.6s 0.2s ease-out forwards;
        }

        .profile-content-card h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-text);
            margin: 0;
        }

        .profile-content-card .profile-title {
            font-size: 1.1rem;
            color: var(--primary-blue);
            font-weight: 500;
            margin: 0.25rem 0 1rem 0;
        }
        
        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
            margin-bottom: 2rem;
        }
        .role-freelancer { background-color: #d1fae5; color: #059669; }
        .role-employer { background-color: #e0f2fe; color: #0284c7; }

        .profile-section {
            text-align: left;
            border-top: 1px solid var(--border-color);
            padding-top: 2rem;
            margin-top: 2rem;
        }
        .profile-section h3 { font-size: 1.2rem; margin: 0 0 1rem 0; color: var(--dark-text); }
        .profile-section p { color: var(--light-text); line-height: 1.7; margin: 0; }
        .profile-section .info-item { margin-bottom: 0.5rem; }
        
        .btn { display: inline-block; padding: 0.8rem 1.5rem; text-decoration: none; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s ease; }
        .btn-primary { background-color: var(--primary-blue); color: var(--white-color); margin-top: 2rem; }
        .btn-primary:hover { background-color: var(--primary-blue-dark); transform: translateY(-2px); }

    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <a href="index.php" class="logo">Freedly</a>
            <nav class="nav">
                <a href="<?= htmlspecialchars($dashboard_link) ?>">Dashboard</a>
                <a href="user_logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <div class="profile-page-wrapper">
        <div class="profile-header">
            <div class="profile-avatar">
                <img src="uploads/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Picture">
            </div>
        </div>
        <div class="profile-content-card">
            <h2><?= htmlspecialchars($user['username']) ?></h2>
            
            <?php if ($_SESSION['role'] === 'freelancer' && !empty($user['profile_title'])): ?>
                <p class="profile-title"><?= htmlspecialchars($user['profile_title']) ?></p>
            <?php elseif ($_SESSION['role'] === 'employer' && !empty($user['company_name'])): ?>
                <p class="profile-title"><?= htmlspecialchars($user['company_name']) ?></p>
            <?php endif; ?>
            
            <span class="role-badge role-<?= htmlspecialchars($user['role']) ?>"><?= ucfirst(htmlspecialchars($user['role'])) ?></span>

            <div class="profile-section">
                <h3>About</h3>
                <p><?= !empty($user['profile_bio']) ? nl2br(htmlspecialchars($user['profile_bio'])) : 'No bio available.' ?></p>
            </div>

            <div class="profile-section">
                <h3>Contact Information</h3>
                <p class="info-item"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                <?php if ($_SESSION['role'] === 'freelancer' && !empty($user['address'])): ?>
                    <p class="info-item"><strong>Address:</strong> <?= htmlspecialchars($user['address']) ?></p>
                <?php endif; ?>
            </div>
            
            <a href="<?= htmlspecialchars($profile_edit_link) ?>" class="btn btn-primary">Edit Profile</a>
        </div>
    </div>
</body>
</html>