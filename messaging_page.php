<?php
// messaging_page.php (Updated with Modern UI and Attachment Preview)
require 'database_connection.php';
if (!isset($_SESSION['user_id'])) { header("Location: user_login.php"); exit(); }
$job_id = filter_input(INPUT_GET, 'job_id', FILTER_VALIDATE_INT);
if (!$job_id) { header("Location: index.php"); exit(); }

$user_id = $_SESSION['user_id'];

// Verify user has access to this chat
$stmt_job = $pdo->prepare("SELECT j.*, u_emp.username as employer_name, u_free.username as freelancer_name 
    FROM jobs j
    JOIN users u_emp ON j.employer_id = u_emp.id
    LEFT JOIN users u_free ON j.assigned_freelancer_id = u_free.id
    WHERE j.id = ? AND (j.employer_id = ? OR j.assigned_freelancer_id = ?)");
$stmt_job->execute([$job_id, $user_id, $user_id]);
$job = $stmt_job->fetch(PDO::FETCH_ASSOC);

if (!$job) { die("Access Denied. You are not part of this project."); }

// Determine the other user's name for the header
$other_user_name = ($_SESSION['role'] === 'employer') ? $job['freelancer_name'] : $job['employer_name'];

// Handle Employer hiring the Freelancer
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hire_freelancer'])) {
    if ($_SESSION['role'] === 'employer' && $job['employer_id'] == $user_id && $job['status'] === 'pending_agreement') {
        $pdo->beginTransaction();
        try {
            $stmt_hire = $pdo->prepare("UPDATE jobs SET status = 'in_progress' WHERE id = ?");
            $stmt_hire->execute([$job_id]);
            $system_message = "🎉 PROJECT STARTED: The employer has officially hired the freelancer. The budget for this project is $" . htmlspecialchars($job['budget']) . ".";
            $stmt_msg = $pdo->prepare("INSERT INTO messages (job_id, sender_id, receiver_id, message_text, is_system_message) VALUES (?, ?, ?, ?, ?)");
            $stmt_msg->execute([$job_id, $user_id, $job['assigned_freelancer_id'], $system_message, 1]);
            $pdo->commit();
            header("Location: messaging_page.php?job_id=$job_id");
            exit();
        } catch (Exception $e) { $pdo->rollBack(); die("An error occurred while hiring."); }
    }
}
// Handle Freelancer marking job as complete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_complete'])) {
    if ($_SESSION['role'] === 'freelancer' && $job['assigned_freelancer_id'] == $user_id) {
        $stmt = $pdo->prepare("UPDATE jobs SET freelancer_status = 'completed' WHERE id = ?");
        $stmt->execute([$job_id]);
        header("Location: messaging_page.php?job_id=$job_id"); exit();
    }
}
// Handle new message submission
$receiver_id = ($user_id == $job['employer_id']) ? $job['assigned_freelancer_id'] : $job['employer_id'];
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $message_text = trim($_POST['message_text']);
    $file_path = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $target_dir = "uploads/";
        // Create a more secure and unique filename
        $file_extension = pathinfo($_FILES["attachment"]["name"], PATHINFO_EXTENSION);
        $file_name = uniqid('file_', true) . '.' . $file_extension;
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file)) {
            $file_path = $target_file;
        }
    }
    if (!empty($message_text) || $file_path) {
        $stmt_insert = $pdo->prepare("INSERT INTO messages (job_id, sender_id, receiver_id, message_text, file_path, is_system_message) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt_insert->execute([$job_id, $user_id, $receiver_id, $message_text, $file_path]);
        header("Location: messaging_page.php?job_id=$job_id"); exit();
    }
}
// Fetch all messages for this job, including sender's avatar
$stmt_messages = $pdo->prepare("SELECT m.*, u.username AS sender_name, u.profile_picture AS sender_avatar FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.job_id = ? ORDER BY m.sent_at ASC");
$stmt_messages->execute([$job_id]);
$messages = $stmt_messages->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Chat - Freedly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        :root {
            --primary-blue: #0d6efd; --primary-blue-dark: #0a58ca; --primary-blue-light: #4dabf7;
            --card-bg: #ffffff; --dark-text: #111827; --light-text: #6b7280;
            --border-color: #e5e7eb; --white-color: #ffffff;
            --font-family: 'Inter', sans-serif;
        }
        
        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        body, html {
            margin: 0; padding: 0; font-family: var(--font-family);
            background: linear-gradient(-45deg, #e0f2fe, #dbeafe, #e0e7ff, #e7e5e4);
            background-size: 400% 400%;
            animation: gradientAnimation 15s ease infinite;
        }
        .chat-page-container { max-width: 800px; margin: 2rem auto; }
        .chat-container {
            display: flex; flex-direction: column; height: 85vh;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            overflow: hidden; /* Prevent content from spilling */
        }
        
        .chat-header {
            padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color);
            display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;
        }
        .chat-header h2 { font-size: 1.2rem; margin: 0; color: var(--dark-text); }
        .chat-header p { margin: 0; color: var(--light-text); }
        .chat-header a { color: var(--primary-blue); text-decoration: none; font-weight: 500; }
        
        .message-box { flex-grow: 1; overflow-y: auto; padding: 1.5rem; }
        .message-wrapper {
            display: flex; gap: 0.75rem; margin-bottom: 1rem;
            opacity: 0;
            animation: fadeInUp 0.5s ease forwards;
        }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--white-color); }
        
        .message { padding: 0.75rem 1rem; border-radius: 18px; max-width: 70%; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .message p { margin: 0; line-height: 1.5; word-wrap: break-word; }
        .message small { display: block; margin-top: 0.25rem; opacity: 0.7; font-size: 0.75rem; }
        
        .received-wrapper { justify-content: flex-start; }
        .received { background-color: var(--white-color); color: var(--dark-text); border-bottom-left-radius: 4px; }
        
        .sent-wrapper { justify-content: flex-end; }
        .sent { background-image: linear-gradient(45deg, var(--primary-blue), var(--primary-blue-light)); color: var(--white-color); border-bottom-right-radius: 4px; }

        .system-message-wrapper { justify-content: center; }
        .system { background-color: #e0f2fe; color: #0284c7; text-align: center; font-size: 0.9rem; font-weight: 500; max-width: 100%; border-radius: 8px; }

        .file-attachment {
            display: flex; align-items: center; background: rgba(0,0,0,0.05);
            padding: 0.75rem; border-radius: 8px; margin-top: 0.5rem;
            text-decoration: none; color: inherit;
        }
        .file-attachment:hover { background: rgba(0,0,0,0.1); }
        .file-attachment .icon { margin-right: 0.75rem; font-size: 1.2rem; }
        .file-attachment strong { display: block; }
        .file-attachment small { margin-top: 0; }

        .job-action-box { padding: 1rem; border-radius: 12px; margin: 0 1.5rem 1.5rem 1.5rem; text-align: center; }
        .hire-box { background-color: #d1fae5; color: #059669; }
        .pending-box { background-color: #fffbeb; color: #b45309; }
        
        .message-input-container {
            padding: 1rem 1.5rem; border-top: 1px solid var(--border-color);
            flex-shrink: 0; background-color: rgba(255,255,255,0.5);
        }
        .message-input-area form { display: flex; align-items: center; gap: 0.75rem; }
        .message-input-area textarea {
            flex-grow: 1; background-color: var(--white-color); border: 1px solid var(--border-color);
            border-radius: 20px; padding: 0.75rem 1rem; font-family: var(--font-family);
            font-size: 1rem; resize: none; outline: none; transition: all 0.2s ease;
        }
        .message-input-area textarea:focus { border-color: var(--primary-blue); box-shadow: 0 0 0 3px rgba(13,110,253,0.15); }
        
        .btn { display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 50%; border: none; cursor: pointer; transition: all 0.2s ease; background: none; color: var(--light-text); }
        .btn:hover { color: var(--primary-blue); background-color: #e0f2fe; }
        .btn-primary { background-color: var(--primary-blue); color: var(--white-color); }
        .btn-primary:hover { background-color: var(--primary-blue-dark); transform: scale(1.1); }
        .btn .icon { font-size: 1.2rem; }
        
        /* ✅ NEW: Styles for Attachment Preview */
        .attachment-preview-container {
            display: none; /* Hidden by default */
            padding: 0.75rem;
            margin: 0 1.5rem 0.75rem 1.5rem;
            background-color: #eef2ff;
            border-radius: 12px;
            position: relative;
        }
        .attachment-preview-container .preview-image {
            max-height: 100px;
            border-radius: 8px;
            border: 2px solid var(--white-color);
        }
        .attachment-preview-container .preview-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            color: var(--dark-text);
        }
        .attachment-preview-container .preview-info i {
            font-size: 1.5rem;
            color: var(--light-text);
        }
        .remove-attachment-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 24px;
            height: 24px;
            background-color: rgba(0,0,0,0.3);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }
        .remove-attachment-btn:hover {
            background-color: rgba(0,0,0,0.5);
        }

        @media(max-width: 768px) { .chat-page-container { margin: 1rem; height: 90vh; } }
    </style>
</head>
<body>
    <div class="chat-page-container">
        <div class="chat-container">
            <div class="chat-header">
                <div>
                    <h2><?= htmlspecialchars($job['title']) ?></h2>
                    <p>With <?= htmlspecialchars($other_user_name ?? 'Freelancer') ?></p>
                </div>
                <a href="<?= $_SESSION['role'] === 'freelancer' ? 'freelancer_my_projects.php' : 'employer_manage_jobs.php' ?>">Back to Projects</a>
            </div>
            
            <?php if ($_SESSION['role'] === 'employer' && $job['status'] === 'pending_agreement'): ?>
            <div class="job-action-box hire-box">
                <form action="" method="POST" onsubmit="return confirm('Start project?');"><button type="submit" name="hire_freelancer" style="background:none;border:none;padding:0;font:inherit;cursor:pointer;color:inherit;font-weight:600;">Click here to hire and start the project.</button></form>
            </div>
            <?php endif; ?>
            <?php if ($_SESSION['role'] === 'freelancer' && $job['status'] === 'pending_agreement'): ?>
            <div class="job-action-box pending-box"><p style="margin:0;">Waiting for employer to hire and start the project.</p></div>
            <?php endif; ?>
            <?php if ($_SESSION['role'] === 'freelancer' && $job['status'] === 'in_progress' && $job['freelancer_status'] !== 'completed'): ?>
            <div class="job-action-box pending-box"><form action="" method="POST"><button type="submit" name="mark_complete" style="background:none;border:none;padding:0;font:inherit;cursor:pointer;color:inherit;font-weight:600;">Click here to mark the project as completed.</button></form></div>
            <?php endif; ?>

            <div class="message-box" id="message-box">
                <?php foreach($messages as $message): ?>
                    <?php if (!empty($message['is_system_message'])): ?>
                        <div class="message-wrapper system-message-wrapper">
                            <div class="message system"><?= htmlspecialchars($message['message_text']) ?></div>
                        </div>
                    <?php elseif ($message['sender_id'] == $user_id): ?>
                        <div class="message-wrapper sent-wrapper">
                            <div class="message sent">
                                <?= !empty($message['message_text']) ? '<p>' . nl2br(htmlspecialchars($message['message_text'])) . '</p>' : '' ?>
                                <?= $message['file_path'] ? '<a href="' . htmlspecialchars($message['file_path']) . '" target="_blank" class="file-attachment"><i class="fas fa-paperclip icon"></i> <div><strong>' . basename($message['file_path']) . '</strong><small>Click to open</small></div></a>' : '' ?>
                                <small><?= date('h:i A', strtotime($message['sent_at'])) ?></small>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="message-wrapper received-wrapper">
                            <img src="uploads/<?= htmlspecialchars($message['sender_avatar']) ?>" alt="avatar" class="avatar">
                            <div class="message received">
                                <?= !empty($message['message_text']) ? '<p>' . nl2br(htmlspecialchars($message['message_text'])) . '</p>' : '' ?>
                                <?= $message['file_path'] ? '<a href="' . htmlspecialchars($message['file_path']) . '" target="_blank" class="file-attachment"><i class="fas fa-paperclip icon"></i> <div><strong>' . basename($message['file_path']) . '</strong><small>Click to open</small></div></a>' : '' ?>
                                <small><?= date('h:i A', strtotime($message['sent_at'])) ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="message-input-container">
                 <div id="attachment-preview" class="attachment-preview-container"></div>
                
                <div class="message-input-area">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <textarea name="message_text" id="message_text" rows="1" placeholder="Type a message..."></textarea>
                        <label for="attachment" class="btn" title="Attach file"><i class="fas fa-paperclip icon"></i></label>
                        <input type="file" name="attachment" id="attachment" style="display:none;">
                        <button type="submit" name="send_message" class="btn btn-primary" title="Send Message"><i class="fas fa-paper-plane icon"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-scroll to the bottom of the message box
        const messageBox = document.getElementById('message-box');
        messageBox.scrollTop = messageBox.scrollHeight;

        // Auto-resize textarea
        const textarea = document.getElementById('message_text');
        textarea.addEventListener('input', () => {
            textarea.style.height = 'auto';
            textarea.style.height = (textarea.scrollHeight) + 'px';
        });

        // ✅ NEW: JavaScript for Attachment Preview
        const attachmentInput = document.getElementById('attachment');
        const previewContainer = document.getElementById('attachment-preview');

        attachmentInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                previewContainer.style.display = 'block';
                previewContainer.innerHTML = ''; // Clear previous preview

                // Add remove button
                const removeBtn = document.createElement('button');
                removeBtn.className = 'remove-attachment-btn';
                removeBtn.innerHTML = '&times;';
                removeBtn.title = 'Remove attachment';
                removeBtn.onclick = () => {
                    attachmentInput.value = ''; // Clear the file input
                    previewContainer.style.display = 'none';
                    previewContainer.innerHTML = '';
                };
                
                // Check if the file is an image
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'preview-image';
                        previewContainer.appendChild(img);
                        previewContainer.appendChild(removeBtn);
                    }
                    reader.readAsDataURL(file);
                } else {
                    // For non-image files, show file info
                    const fileInfo = document.createElement('div');
                    fileInfo.className = 'preview-info';
                    fileInfo.innerHTML = `<i class="fas fa-file-alt"></i> <span>${file.name}</span>`;
                    previewContainer.appendChild(fileInfo);
                    previewContainer.appendChild(removeBtn);
                }
            } else {
                previewContainer.style.display = 'none';
                previewContainer.innerHTML = '';
            }
        });
    </script>
</body>
</html>