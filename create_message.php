<?php
// create_message.php

session_start();
require 'database_connection.php';

// 1. Check if the user is logged in. If not, redirect to the login page.
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

// 2. Get the ID of the freelancer to contact from the URL.
$recipient_id = filter_input(INPUT_GET, 'recipient_id', FILTER_VALIDATE_INT);
if (!$recipient_id) {
    die("Invalid recipient ID.");
}

// 3. Prevent users from messaging themselves.
$sender_id = $_SESSION['user_id'];
if ($sender_id == $recipient_id) {
    // Redirect back to their own profile or dashboard.
    header("Location: freelancer_dashboard.php");
    exit();
}

// The user starting the chat is assumed to be the 'employer' in this context.
$employer_id = $sender_id;
$freelancer_id = $recipient_id;

// 4. Check if a conversation (a 'job' in a pending state) already exists between these two users.
// This prevents creating duplicate conversation threads.
$stmt_check = $pdo->prepare(
    "SELECT id FROM jobs WHERE employer_id = ? AND assigned_freelancer_id = ? AND status = 'pending_agreement'"
);
$stmt_check->execute([$employer_id, $freelancer_id]);
$existing_job = $stmt_check->fetch(PDO::FETCH_ASSOC);

if ($existing_job) {
    // 5. If a conversation already exists, redirect to that messaging page.
    $job_id = $existing_job['id'];
    header("Location: messaging_page.php?job_id=" . $job_id);
    exit();

} else {
    // 6. If no conversation exists, create a new "placeholder" job to start the chat.
    // This job acts as the container for the new conversation.
    $pdo->beginTransaction();
    try {
        // Create the new placeholder job record
        $stmt_create_job = $pdo->prepare(
            "INSERT INTO jobs (employer_id, assigned_freelancer_id, title, status) VALUES (?, ?, ?, ?)"
        );
        // We use a generic title. The employer can edit this later.
        $stmt_create_job->execute([$employer_id, $freelancer_id, 'New Inquiry', 'pending_agreement']);
        
        // Get the ID of the job we just created
        $new_job_id = $pdo->lastInsertId();

        // Optional: Add a system message to start the chat
        $system_message = "Conversation started by the client.";
        $stmt_msg = $pdo->prepare(
            "INSERT INTO messages (job_id, sender_id, receiver_id, message_text, is_system_message) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt_msg->execute([$new_job_id, $employer_id, $freelancer_id, $system_message, 1]);

        $pdo->commit();

        // 7. Redirect the user to the messaging page for the newly created job.
        header("Location: messaging_page.php?job_id=" . $new_job_id);
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        // For debugging, you can use: die("Error creating conversation: " . $e->getMessage());
        die("An error occurred. Please try again.");
    }
}
?>