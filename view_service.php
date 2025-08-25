<?php
// view_service.php (Updated)
require 'database_connection.php';
$service_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$service_id) { die("Invalid service ID."); }

$stmt = $pdo->prepare("SELECT s.*, u.username as freelancer_name, u.profile_picture, u.level, u.id as freelancer_id FROM services s JOIN users u ON s.freelancer_id = u.id WHERE s.id = ?");
$stmt->execute([$service_id]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$service) { die("Service not found."); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($service['title']) ?></title>
    <link rel="stylesheet" href="main_styles.css">
</head>
<body>
    <header class="header">
        <a href="index.php" class="logo">Freedly</a>
        <nav class="nav">
            <a href="browse_services.php">Back to Services</a>
        </nav>
    </header>
    <div class="container service-view">
        <div class="service-main-content">
            <h1><?= htmlspecialchars($service['title']) ?></h1>
            <img class="service-main-image" src="uploads/<?= htmlspecialchars($service['service_image']) ?>" alt="<?= htmlspecialchars($service['title']) ?>">
            <h3>About This Service</h3>
            <p><?= nl2br(htmlspecialchars($service['description'])) ?></p>
        </div>
        <div class="service-sidebar">
            <div class="service-price-box">
                <div class="price-header">
                    <span>Starting At</span>
                    <span class="price-amount">$<?= htmlspecialchars($service['price']) ?></span>
                </div>
                <div class="delivery-time">
                    <p><strong><?= htmlspecialchars($service['delivery_days']) ?> Days Delivery</strong></p>
                </div>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] === 'employer'): ?>
                        <a href="initiate_contact.php?service_id=<?= $service['id'] ?>" class="btn btn-primary" style="width:100%; text-align:center;">Contact to Order</a>
                    <?php elseif ($_SESSION['user_id'] == $service['freelancer_id']): ?>
                        <button class="btn" style="width:100%; background-color: #6c757d; cursor: not-allowed;" disabled>This is Your Service</button>
                    <?php else: // Logged in as another freelancer ?>
                        <button class="btn" style="width:100%; background-color: #6c757d; cursor: not-allowed;" disabled>Login as Employer to Order</button>
                    <?php endif; ?>
                <?php else: // Not logged in ?>
                    <a href="user_login.php" class="btn btn-primary" style="width:100%; text-align:center;">Login to Order</a>
                <?php endif; ?>

            </div>
            <div class="freelancer-contact-box">
                <h4>About The Seller</h4>
                <a href="freelancer_public_profile.php?id=<?= $service['freelancer_id'] ?>">
                    <img src="uploads/<?= htmlspecialchars($service['profile_picture']) ?>" alt="<?= htmlspecialchars($service['freelancer_name']) ?>">
                    <div>
                        <strong><?= htmlspecialchars($service['freelancer_name']) ?></strong>
                        <span><?= htmlspecialchars($service['level']) ?> Seller</span>
                    </div>
                </a>
            </div>
        </div>
    </div>
</body>
</html>