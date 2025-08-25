<?php
// initiate_contact.php
require 'database_connection.php';

// Ensure user is an employer and is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
    header("Location: user_login.php");
    exit();
}

$employer_id = $_SESSION['user_id'];
$service_id = filter_input(INPUT_GET, 'service_id', FILTER_VALIDATE_INT);

if (!$service_id) {
    die("Invalid Service ID.");
}

// Get service details to create the inquiry
$stmt_service = $pdo->prepare("SELECT * FROM services WHERE id = ?");
$stmt_service->execute([$service_id]);
$service = $stmt_service->fetch(PDO::FETCH_ASSOC);

if (!$service) {
    die("Service not found.");
}

$freelancer_id = $service['freelancer_id'];
$job_title = "Inquiry for service: " . $service['title'];

// To prevent duplicate inquiries, check if one already exists
$stmt_check = $pdo->prepare(
    "SELECT id FROM jobs WHERE employer_id = ? AND assigned_freelancer_id = ? AND title = ? AND status = 'pending_agreement'"
);
$stmt_check->execute([$employer_id, $freelancer_id, $job_title]);
$existing_job = $stmt_check->fetch(PDO::FETCH_ASSOC);

if ($existing_job) {
    // If an inquiry already exists, just redirect to that chat
    $job_id = $existing_job['id'];
} else {
    // Otherwise, create a new job inquiry
    $description = "This is a preliminary job created from an inquiry about the service: '" . $service['title'] . "'. Please discuss the project details and finalize the agreement here.";
    
    $stmt_insert = $pdo->prepare(
        "INSERT INTO jobs (employer_id, assigned_freelancer_id, title, description, budget, category, status, freelancer_status) VALUES (?, ?, ?, ?, ?, ?, 'pending_agreement', '')"
    );
    $stmt_insert->execute([
        $employer_id, 
        $freelancer_id, 
        $job_title,
        $description,
        $service['price'],
        $service['category']
    ]);
    
    // Get the ID of the new job we just created
    $job_id = $pdo->lastInsertId();
}

// Redirect the employer to the messaging page for this new inquiry
header("Location: messaging_page.php?job_id=" . $job_id);
exit();