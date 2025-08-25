<?php
// Start the session at the very top to access session variables
session_start();

// Include the database connection file
require 'database_connection.php';

// Get the freelancer ID from the URL and validate it as an integer
$freelancer_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$freelancer_id) {
    // If the ID is missing or invalid, stop the script
    die("Invalid freelancer profile.");
}

// Fetch the main details of the freelancer from the 'users' table
$stmt_user = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'freelancer'");
$stmt_user->execute([$freelancer_id]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC);

// If no freelancer is found with that ID, stop the script
if (!$user) {
    die("Freelancer not found.");
}

// Fetch the freelancer's skills
$stmt_skills = $pdo->prepare("SELECT skill_name FROM freelancer_skills WHERE freelancer_id = ?");
$stmt_skills->execute([$freelancer_id]);
$skills = $stmt_skills->fetchAll(PDO::FETCH_COLUMN);

// Fetch the freelancer's education history
$stmt_education = $pdo->prepare("SELECT * FROM freelancer_education WHERE freelancer_id = ? ORDER BY year_completed DESC");
$stmt_education->execute([$freelancer_id]);
$educations = $stmt_education->fetchAll(PDO::FETCH_ASSOC);

// Fetch the freelancer's offered services
$stmt_services = $pdo->prepare("SELECT * FROM services WHERE freelancer_id = ? ORDER BY created_at DESC");
$stmt_services->execute([$freelancer_id]);
$services = $stmt_services->fetchAll(PDO::FETCH_ASSOC);

// Fetch the freelancer's reviews and join with the users table to get the employer's name
$stmt_reviews = $pdo->prepare("
    SELECT r.*, u.username as employer_name
    FROM reviews r
    JOIN users u ON r.employer_id = u.id
    WHERE r.freelancer_id = ?
    ORDER BY r.created_at DESC
");
$stmt_reviews->execute([$freelancer_id]);
$reviews = $stmt_reviews->fetchAll(PDO::FETCH_ASSOC);

// Calculate the average rating for the freelancer
$avg_rating = 0;
$review_count = count($reviews);
if ($review_count > 0) {
    $total_rating = 0;
    foreach ($reviews as $review) {
        $total_rating += $review['rating'];
    }
    // Round the average rating to one decimal place
    $avg_rating = round($total_rating / $review_count, 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($user['username']) ?>'s Profile - Freedly</title>
    <link rel="stylesheet" href="main_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>

    <header class="header">
        <a href="index.php" class="logo">Freedly</a>
        <nav class="nav">
            <a href="index.php">Home</a>
            <a href="index.php#jobs">Find Work</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?= $_SESSION['role'] ?>_dashboard.php">Dashboard</a>
            <?php else: ?>
                <a href="user_login.php">Login</a>
                <a href="user_register.php" class="btn btn-primary">Sign Up</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="container public-profile">
        <div class="profile-sidebar">
            <img src="uploads/<?= htmlspecialchars($user['profile_picture']) ?>" alt="<?= htmlspecialchars($user['username']) ?>">
            <h1><?= htmlspecialchars($user['username']) ?></h1>
            <h2><?= htmlspecialchars($user['profile_title'] ?? 'Freelancer') ?></h2>
            <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($user['address'] ?? 'Location not specified') ?></p>

            <div class="profile-rating">
                <div class="stars" style="--rating: <?= $avg_rating ?>;" title="<?= $avg_rating ?> out of 5 stars"></div>
                <span><strong><?= $avg_rating ?></strong> (<?= $review_count ?> reviews)</span>
            </div>

            <?php if (isset($_SESSION['user_id'])): // Check if a user is logged in ?>
                <?php if ($_SESSION['user_id'] == $freelancer_id): // Check if they are viewing their own profile ?>
                    <a href="freelancer_dashboard.php" class="btn btn-secondary" style="width:100%; text-align:center; margin-top: 1rem;">Edit Your Profile</a>
                <?php else: // A logged-in user is viewing someone else's profile ?>
                    <a href="create_message.php?recipient_id=<?= $freelancer_id ?>" class="btn btn-primary" style="width:100%; text-align:center; margin-top: 1rem;">Contact <?= htmlspecialchars($user['username']) ?></a>
                <?php endif; ?>
            <?php else: // No user is logged in ?>
                <a href="user_login.php" class="btn btn-primary" style="width:100%; text-align:center; margin-top: 1rem;">Login to Contact</a>
            <?php endif; ?>
        </div>

        <div class="profile-main">
            <h3>About Me</h3>
            <p><?= nl2br(htmlspecialchars($user['profile_bio'] ?? 'No bio available.')) ?></p>

            <hr>

            <h3>Skills</h3>
            <div class="skills-container">
                <?php if (!empty($skills)): ?>
                    <?php foreach ($skills as $skill): ?>
                        <span class="skill-tag"><?= htmlspecialchars($skill) ?></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No skills listed yet.</p>
                <?php endif; ?>
            </div>

            <hr>

            <h3>Education</h3>
            <div class="education-list">
                <?php if (!empty($educations)): ?>
                    <?php foreach($educations as $edu): ?>
                        <div class="education-item">
                            <h4><?= htmlspecialchars($edu['degree']) ?></h4>
                            <p><?= htmlspecialchars($edu['institution']) ?> - <?= htmlspecialchars($edu['year_completed']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                       <p>No education history provided.</p>
                <?php endif; ?>
            </div>

            <hr>

            <h3>Services Offered</h3>
            <div class="service-grid-profile">
                <?php if(!empty($services)): ?>
                    <?php foreach($services as $service): ?>
                    <div class="service-card-small">
                        <a href="view_service.php?id=<?= $service['id'] ?>">
                            <img src="uploads/<?= htmlspecialchars($service['service_image']) ?>" alt="<?= htmlspecialchars($service['title']) ?>">
                            <p><?= htmlspecialchars($service['title']) ?></p>
                            <span>From $<?= htmlspecialchars($service['price']) ?></span>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>This freelancer is not offering any services at the moment.</p>
                <?php endif; ?>
            </div>

            <hr>

            <h3>Feedback from Employers</h3>
            <div class="review-list">
                <?php if (!empty($reviews)): ?>
                    <?php foreach($reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-rating">
                                <div class="stars" style="--rating: <?= $review['rating'] ?>;"></div>
                            </div>
                            <p>"<?= nl2br(htmlspecialchars($review['comment'])) ?>"</p>
                            <small>-- <strong><?= htmlspecialchars($review['employer_name']) ?></strong> on <?= date('M d, Y', strtotime($review['created_at'])) ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>This freelancer has not received any feedback yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>