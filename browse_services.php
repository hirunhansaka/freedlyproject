<?php
// browse_services.php
require 'database_connection.php';

$sql = "SELECT s.*, u.id as freelancer_id, u.username as freelancer_name, u.profile_picture, u.level as freelancer_level 
        FROM services s 
        JOIN users u ON s.freelancer_id = u.id 
        ORDER BY u.level DESC, s.created_at DESC";

$stmt = $pdo->query($sql);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Services - Freedly</title>
      <link rel="stylesheet" href="footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        :root {
            --primary-blue: #0d6efd; --primary-blue-dark: #0a58ca;
            --content-bg: #f4f7fa; --card-bg: #ffffff;
            --dark-text: #111827; --light-text: #6b7280;
            --white-color: #ffffff; --border-color: #e5e7eb;
            --font-family: 'Inter', sans-serif;
        }
        body, html { margin: 0; padding: 0; font-family: var(--font-family); background-color: var(--content-bg); }

        /* --- Modern Header --- */
        .header { background-color: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); padding: 0.75rem 0; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); position: sticky; top: 0; z-index: 1000; }
        .header .container { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
        .header .logo { font-size: 1.8rem; font-weight: 800; color: var(--primary-blue); text-decoration: none; }
        .header .nav { display: flex; align-items: center; gap: 1.5rem; }
        .header .nav a { text-decoration: none; color: var(--light-text); font-weight: 600; transition: color 0.2s ease; }
        .header .nav a:hover { color: var(--primary-blue); }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 50px; font-weight: 700; border: none; cursor: pointer; transition: all 0.3s ease; }
        .btn-primary { background-color: var(--primary-blue); color: var(--white-color); }
        .btn-primary:hover { background-color: var(--primary-blue-dark); transform: translateY(-2px); }

        .page-wrapper { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        
        .filter-bar { text-align: center; margin-bottom: 3rem; }
        .filter-bar h1 { font-size: 2.5rem; color: var(--dark-text); margin-bottom: 1rem; }
        .filter-bar p { color: var(--light-text); font-size: 1.1rem; margin-top: 0; }
        
        /* ✅ DECORATED "NORMAL" SERVICE GRID */
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        .service-card {
            /* ✅ CHANGED: Clean, solid white card design */
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            overflow: hidden;
            opacity: 0;
            animation: fadeIn 0.6s ease-out forwards;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        /* Staggered load-in animation */
        <?php for ($i = 1; $i <= 12; $i++): ?>
        .service-card:nth-child(<?= $i ?>) { animation-delay: <?= $i * 0.05 ?>s; }
        <?php endfor; ?>

        /* ✅ CHANGED: Simple and elegant hover effect */
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .service-card a { text-decoration: none; color: var(--dark-text); }
        .service-image-wrapper {
            height: 180px;
            overflow: hidden;
        }
        .service-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        .service-card:hover .service-image { transform: scale(1.1); }
        
        .service-card-content {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1; /* Allows footer to stick to the bottom */
        }
        .freelancer-info { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .freelancer-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border-color); }
        .freelancer-name { font-weight: 600; }
        .freelancer-level { font-size: 0.8rem; color: var(--light-text); display: block; }
        .service-title {
            display: block;
            min-height: 48px; /* Set a min-height for 2 lines of text */
            overflow: hidden;
            font-weight: 600;
            line-height: 1.4;
            margin-top: 0.5rem;
            flex-grow: 1;
        }
        
        .service-footer { border-top: 1px solid var(--border-color); padding-top: 10px; margin-top: 1rem; }
        .service-price { color: var(--light-text); font-size: 0.9rem; }
        .service-price span { font-weight: 700; font-size: 1.2rem; color: var(--dark-text); }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <a href="index.php" class="logo">Freedly</a>
            <nav class="nav">
                <a href="index.php">Find Jobs</a>
                <a href="browse_services.php">Browse Services</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= $_SESSION['role'] ?>_dashboard.php">Dashboard</a>
                    <a href="user_logout.php">Logout</a>
                <?php else: ?>
                    <a href="user_login.php">Login</a>
                    <a href="user_register.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="page-wrapper">
        <div class="filter-bar">
            <h1>Find The Perfect Service</h1>
            <p>Browse our marketplace of talented freelancers ready to bring your ideas to life.</p>
        </div>
        <div class="service-grid">
            <?php if (count($services) > 0): ?>
                <?php foreach ($services as $service): ?>
                <div class="service-card">
                    <div class="service-image-wrapper">
                        <a href="view_service.php?id=<?= $service['id'] ?>">
                            <img class="service-image" src="uploads/<?= htmlspecialchars($service['service_image']) ?>" alt="<?= htmlspecialchars($service['title']) ?>">
                        </a>
                    </div>
                    <div class="service-card-content">
                        <div class="freelancer-info">
                            <img class="freelancer-avatar" src="uploads/<?= htmlspecialchars($service['profile_picture']) ?>" alt="<?= htmlspecialchars($service['freelancer_name']) ?>">
                            <div>
                                <a href="freelancer_public_profile.php?id=<?= $service['freelancer_id'] ?>" class="freelancer-name"><?= htmlspecialchars($service['freelancer_name']) ?></a>
                                <span class="freelancer-level"><?= htmlspecialchars($service['freelancer_level']) ?> Seller</span>
                            </div>
                        </div>
                        <a href="view_service.php?id=<?= $service['id'] ?>" class="service-title">
                            <?= htmlspecialchars($service['title']) ?>
                        </a>
                        <div class="service-footer">
                            <span class="service-price">STARTING AT <span>$<?= htmlspecialchars($service['price']) ?></span></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No services have been created yet. Check back soon!</p>
            <?php endif; ?>
        </div>
    </div>
    
     <?php require 'footer.php'; ?>

</body>
</html>