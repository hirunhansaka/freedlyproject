<?php
// index.php (Updated with correct header link)
require 'database_connection.php';

// Predefined popular categories for the navigation bar
$popular_categories = [
    'Web Development',
    'Graphic Design',
    'Writing & Translation',
    'Digital Marketing',
    'Video & Animation',
    'Admin Support'
];

$search_term = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

$sql = "SELECT jobs.*, users.username AS employer_name FROM jobs JOIN users ON jobs.employer_id = users.id WHERE jobs.status = 'open'";
$params = [];

if (!empty($search_term)) {
    $sql .= " AND (jobs.title LIKE ? OR jobs.description LIKE ?)";
    $params[] = '%' . $search_term . '%';
    $params[] = '%' . $search_term . '%';
}
if (!empty($category_filter)) {
    $sql .= " AND jobs.category = ?";
    $params[] = $category_filter;
}
$sql .= " ORDER BY jobs.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch a few freelancers to feature
$featured_freelancers = $pdo->query("SELECT id, username, profile_title, profile_picture FROM users WHERE role = 'freelancer' AND profile_title IS NOT NULL LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="footer.css">
    <title>Welcome to Freedly - Hire Freelancers & Find Work</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
    /* --- 1. GLOBAL STYLES & VARIABLES --- */
    @import url('https://fonts.googleapis.com/css2?family=Lato:wght@400;700;900&display=swap');

    :root {
        --primary-blue: #0d6efd; 
        --secondary-blue: #0b5ed7;
        --light-blue-bg: #f0f5ff;
        --dark-text: #212529;
        --light-text: #6c757d;
        --border-color: #dee2e6;
        --white-color: #ffffff;
        --success-color: #198754;
        --font-family: 'Lato', sans-serif;
    }

    * {
        box-sizing: border-box;
    }

    body {
        font-family: var(--font-family);
        color: var(--dark-text);
        background-color: var(--white-color);
        margin: 0;
        /* Adjusted padding for the two fixed headers */
        padding-top: 118px; 
    }

    .container {
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
        padding-left: 1rem;
        padding-right: 1rem;
        width: 100%;
    }

    h1, h2, h3, h4, h5, h6 {
        font-weight: 700;
    }

    /* --- 2. HEADER & NAVIGATION (Modernized) --- */

    .header {
        background-color: var(--white-color);
        padding: 0.75rem 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 1000;
    }

    .header .container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
    }

    .header .logo {
        font-size: 1.8rem;
        font-weight: 900;
        color: var(--primary-blue);
        text-decoration: none;
    }

    /* Mobile menu button */
    .menu-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 1.5rem;
        color: var(--dark-text);
        cursor: pointer;
    }

    .header .nav {
        display: flex;
        align-items: center;
    }

    .header .nav a {
        margin-left: 1.5rem;
        text-decoration: none;
        color: var(--light-text);
        font-weight: 700;
        transition: color 0.3s ease;
        position: relative;
        padding-bottom: 5px;
    }

    .header .nav a::after {
        content: '';
        position: absolute;
        width: 100%;
        transform: scaleX(0);
        height: 2px;
        bottom: 0;
        left: 0;
        background-color: var(--primary-blue);
        transform-origin: bottom right;
        transition: transform 0.25s ease-out;
    }

    .header .nav a:hover::after {
        transform: scaleX(1);
        transform-origin: bottom left;
    }

    .header .nav a:hover {
        color: var(--primary-blue);
    }

    .btn {
        display: inline-flex; /* Use flexbox for icon alignment */
        align-items: center;
        justify-content: center;
        padding: 0.75rem 1.5rem;
        text-decoration: none;
        border-radius: 50px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background-color: #00ccffff;
        color: var(--white-color);
        box-shadow: 0 4px 15px rgba(13, 29, 253, 0);
    }

    .btn-primary:hover {
        background-color: #00ccffff;
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(13, 109, 253, 0);
    }

    .btn-primary::after {
        content: '\f061'; /* Font Awesome unicode for arrow-right */
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        margin-left: 0.5rem;
        transition: transform 0.3s ease;
    }

    .btn-primary:hover::after {
        transform: translateX(3px); /* Makes the arrow move on hover */
    }

    .secondary-nav {
        background-color: #f8f9fa; /* Solid light gray background */
        padding: 0.5rem 0;
        border-bottom: 1px solid var(--border-color);
        position: fixed;
        top: 60px; /* Adjust based on your header's final height */
        width: 100%;
        z-index: 999;
    }

    .secondary-nav .container {
        display: flex;
        gap: 0.75rem;
        overflow-x: auto;
        scrollbar-width: none;
        padding: 5px 0;
    }

    .secondary-nav .container::-webkit-scrollbar {
        display: none;
    }

    .secondary-nav a {
        text-decoration: none;
        color: var(--light-text);
        font-weight: 600;
        white-space: nowrap;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        border: 1px solid transparent;
        transition: all 0.3s ease;
    }

    .secondary-nav a:hover, .secondary-nav a.active {
        color: var(--white-color);
        background-color: var(--primary-blue);
        border-color: var(--primary-blue);
    }
    
    /* --- 3. HERO SECTION with VIDEO --- */
    .hero {
        position: relative;
        height: 500px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: var(--white-color);
        overflow: hidden;
    }

    .hero-video-bg {
        position: absolute;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: -2;
    }
    
    /* ✅ FIXED: Added dark overlay for text readability */
    .hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.4); /* Dark overlay */
        z-index: -1;
    }

    .hero .container {
        position: relative;
        z-index: 2;
        padding: 0 1rem;
    }

    .hero h1 {
        font-size: 3rem;
        font-weight: 900;
        margin-bottom: 1rem;
        text-shadow: 0 2px 8px rgba(0,0,0,0.7);
    }

    .hero p {
        font-size: 1.2rem;
        max-width: 600px;
        margin: 0 auto 1.5rem auto;
        font-weight: 500;
        text-shadow: 0 2px 8px rgba(0,0,0,0.7);
    }

    .hero .search-bar {
        display: flex;
        max-width: 600px;
        margin: 1.5rem auto 0 auto;
        background: var(--white-color);
        border-radius: 50px;
        padding: 0.5rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .hero .search-bar input {
        flex-grow: 1;
        border: none;
        outline: none;
        padding: 0 1.5rem;
        font-size: 1rem;
        background: transparent;
        color: var(--dark-text);
    }

    .hero .search-bar button {
        background-color: var(--primary-blue);
        color: var(--white-color);
        border: none;
        border-radius: 50px;
        padding: 0.8rem 2rem;
        font-weight: 700;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .hero .search-bar button:hover {
        background-color: var(--secondary-blue);
    }
    
    /* --- 4. SECTIONS & CARDS --- */
    .how-it-works, .featured-freelancers {
        padding: 60px 0;
    }
    
    main#jobs.container {
        padding: 60px 1rem;
    }

    h2.section-title {
        text-align: center;
        font-size: 2.2rem;
        font-weight: 900;
        margin-bottom: 3rem;
    }

    .how-it-works .steps {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 2rem;
        text-align: center;
    }

    .how-it-works .step i {
        font-size: 3rem;
        color: var(--primary-blue);
        margin-bottom: 1rem;
    }

    .job-listing, .freelancer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .job-card, .freelancer-card {
        background: var(--white-color);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        display: flex;
        flex-direction: column;
    }

    .job-card:hover, .freelancer-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 25px rgba(0,0,0,0.1);
    }

    .job-card .job-category {
        background-color: var(--light-blue-bg);
        color: var(--primary-blue);
        padding: 0.3rem 0.8rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 700;
        display: inline-block;
        margin-bottom: 1rem;
        align-self: flex-start;
    }

    .job-card h3 {
        font-size: 1.25rem;
        margin-bottom: 0.5rem;
        flex-grow: 1; /* ✅ FIXED: Ensures titles align */
    }

    .job-card .job-budget {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--success-color);
        margin: 1rem 0;
    }

    .freelancer-card {
        text-align: center;
    }
    
    .freelancer-card img {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        margin-left: auto;
        margin-right: auto;
        margin-bottom: 1rem;
        border: 4px solid var(--white-color);
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    .btn-secondary {
        background-color: #e9ecef;
        color: var(--dark-text);
    }

    /* Why Us Section */
    #why-us-section {
        padding: 60px 0;
    }
    
    .section-padding {
        padding: 60px 0;
    }
    
    .row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -15px;
    }
    
    .align-items-center {
        align-items: center;
    }
    
    .g-5 {
        gap: 3rem;
    }
    
    .col-lg-6 {
        flex: 0 0 auto;
        width: 50%;
        padding: 0 15px;
    }
    
    .feature-item {
        display: flex;
        align-items: flex-start;
        margin-top: 1.5rem;
    }
    
    .feature-item .icon {
        font-size: 1.5rem;
        color: var(--primary-blue);
        margin-right: 1rem;
        flex-shrink: 0;
    }
    
    .img-fluid {
        max-width: 100%;
        height: auto;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    /* ✅ FIXED: Responsive adjustments for smaller screens */
    @media (max-width: 992px) {
        .hero h1 {
            font-size: 2.5rem;
        }
        
        .col-lg-6 {
            width: 100%;
        }
        
        #why-us-section .row {
            flex-direction: column-reverse;
        }
        
        #why-us-section .col-lg-6:last-child {
            margin-bottom: 2rem;
        }
    }
    
    @media (max-width: 768px) {
        body {
            padding-top: 108px; /* Adjust for potentially wrapped header */
        }
        
        .header {
            padding: 0.5rem 0;
        }
        
        .menu-toggle {
            display: block;
        }
        
        .header .nav {
            position: fixed;
            top: 60px;
            left: -100%;
            width: 80%;
            height: calc(100vh - 60px);
            background: var(--white-color);
            flex-direction: column;
            align-items: flex-start;
            padding: 2rem;
            box-shadow: 5px 0 15px rgba(0,0,0,0.1);
            transition: left 0.3s ease;
            z-index: 1000;
        }
        
        .header .nav.active {
            left: 0;
        }
        
        .header .nav a {
            margin: 0 0 1.5rem 0;
            padding: 0.5rem 0;
            width: 100%;
            border-bottom: 1px solid var(--border-color);
        }
        
        .secondary-nav {
            top: 53px;
        }
        
        .hero {
            height: 400px;
        }
        
        .hero h1 {
            font-size: 2.2rem;
        }
        
        .hero p {
            font-size: 1rem;
            padding: 0 1rem;
        }
        
        .hero .search-bar {
            flex-direction: column;
            border-radius: 10px;
            padding: 1rem;
        }
        
        .hero .search-bar input {
            padding: 0.8rem;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        
        h2.section-title {
            font-size: 1.8rem;
        }
        
        .how-it-works, .featured-freelancers, #why-us-section {
            padding: 40px 0;
        }
        
        .job-listing, .freelancer-grid {
            grid-template-columns: 1fr;
        }
        
        .g-5 {
            gap: 2rem;
        }
    }

    @media (max-width: 576px) {
        .hero {
            height: 350px;
        }
        
        .hero h1 {
            font-size: 1.8rem;
        }
        
        .hero .search-bar button {
            padding: 0.8rem 1.5rem;
        }
        
        .how-it-works .steps {
            grid-template-columns: 1fr;
        }
        
        .job-card, .freelancer-card {
            padding: 1rem;
        }
        
        .secondary-nav .container {
            padding: 5px 1rem;
        }
        
        .secondary-nav a {
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
        }
    }
</style>
</head>
<body>
    
    <header class="header">
        <div class="container">
            <a href="index.php" class="logo">Freedly</a>
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <nav class="nav" id="mainNav">
                <a href="index.php">Home</a>
                <a href="#jobs">Find Work</a>
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

    <nav class="secondary-nav">
        <div class="container">
            <a href="index.php#jobs">All Jobs</a>
            <?php foreach ($popular_categories as $cat): ?>
            <a href="index.php?category=<?= urlencode($cat) ?>#jobs"><?= htmlspecialchars($cat) ?></a>
            <?php endforeach; ?>
        </div>
    </nav>

   <section class="hero">
        <video autoplay muted loop class="hero-video-bg">
            <source src="video/login.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        
        <div class="container">
            <h1>Find The Perfect Freelancer For Your Project</h1>
            <p>Get quality work done efficiently and affordably. Join our network today.</p>
            <form action="index.php" method="GET" class="search-bar">
                <input type="text" name="search" placeholder="What service are you looking for?" value="<?= htmlspecialchars($search_term) ?>">
                <button type="submit">Search</button>
            </form>
        </div>
    </section>

    <section class="how-it-works">
        <div class="container">
            <h2 class="section-title">How It Works</h2>
            <div class="steps">
                <div class="step">
                    <i class="fas fa-file-alt"></i>
                    <h3>1. Post Your Job</h3>
                    <p>Describe your project and the skills you require in a simple brief.</p>
                </div>
                <div class="step">
                    <i class="fas fa-users"></i>
                    <h3>2. Choose Freelancers</h3>
                    <p>Receive proposals from talented freelancers and choose the best fit.</p>
                </div>
                <div class="step">
                    <i class="fas fa-check-circle"></i>
                    <h3>3. Get It Done</h3>
                    <p>Collaborate, track progress, and pay securely once the job is complete.</p>
                </div>
            </div>
        </div>
    </section>

    <main class="container" id="jobs">
        <h2 class="section-title"><?= !empty($category_filter) ? 'Jobs in "' . htmlspecialchars($category_filter) . '"' : 'Latest Jobs' ?></h2>
        <div class="job-listing">
            <?php if (count($jobs) > 0): ?>
                <?php foreach ($jobs as $job): ?>
                <div class="job-card">
                    <span class="job-category"><?= htmlspecialchars($job['category']) ?></span>
                    <h3><?= htmlspecialchars($job['title']) ?></h3>
                    <p><strong>Posted By:</strong> <?= htmlspecialchars($job['employer_name']) ?></p>
                    <p class="job-budget">$<?= htmlspecialchars($job['budget']) ?></p>
                    <a href="apply_to_job.php?job_id=<?= $job['id'] ?>" class="btn btn-primary">Apply Now</a>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No jobs found matching your criteria. Try a different search!</p>
            <?php endif; ?>
        </div>
    </main>

    <section class="featured-freelancers">
        <div class="container">
            <h2 class="section-title">Featured Freelancers</h2>
            <div class="freelancer-grid">
                <?php foreach($featured_freelancers as $freelancer): ?>
                <div class="freelancer-card">
                    <img src="uploads/<?= htmlspecialchars($freelancer['profile_picture']) ?>" alt="<?= htmlspecialchars($freelancer['username']) ?>">
                    <h4><?= htmlspecialchars($freelancer['username']) ?></h4>
                    <p><?= htmlspecialchars($freelancer['profile_title']) ?></p>
                    <a href="freelancer_public_profile.php?id=<?= $freelancer['id'] ?>" class="btn btn-secondary">View Profile</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="why-us-section" class="section-padding">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <h2 class="section-title">A whole world of freelance talent at your fingertips</h2>
                    <div class="d-flex mt-4 feature-item">
                        <div class="icon"><i class="fas fa-shield-alt"></i></div>
                        <div>
                            <h5>Proof of quality</h5>
                            <p>Check any pro's work samples, client reviews, and identity verification.</p>
                        </div>
                    </div>
                    <div class="d-flex mt-3 feature-item">
                        <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                        <div>
                            <h5>No cost until you hire</h5>
                            <p>Interview potential fits for your job, negotiate rates, and only pay for work you approve.</p>
                        </div>
                    </div>
                     <div class="d-flex mt-3 feature-item">
                        <div class="icon"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <h5>Safe and secure</h5>
                            <p>Focus on your work knowing we help protect your data and privacy. We're here with 24/7 support if you need it.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                     <img src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?q=80&w=600" class="img-fluid" alt="Team working">
                </div>
            </div>
        </div>
    </section>

    <?php require 'footer.php'; ?>

    <script>
        // Mobile menu toggle functionality
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('mainNav').classList.toggle('active');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const nav = document.getElementById('mainNav');
            const menuToggle = document.getElementById('menuToggle');
            
            if (!nav.contains(event.target) && !menuToggle.contains(event.target) && nav.classList.contains('active')) {
                nav.classList.remove('active');
            }
        });
    </script>
</body>
</html>