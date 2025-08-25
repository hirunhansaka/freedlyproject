<?php
// admin_manage_users.php
require 'database_connection.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: user_login.php");
    exit();
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $user_id_to_delete = $_POST['user_id'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id_to_delete]);
    header("Location: admin_manage_users.php");
    exit();
}

// Handle freelancer level update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['set_level'])) {
    $user_id_to_update = $_POST['user_id'];
    $new_level = $_POST['level'];
    $stmt = $pdo->prepare("UPDATE users SET level = ? WHERE id = ? AND role = 'freelancer'");
    $stmt->execute([$new_level, $user_id_to_update]);
}

// Fetch all non-admin users including level
$stmt = $pdo->query("SELECT id, username, email, role, level, created_at FROM users WHERE role != 'admin' ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Freedly Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* --- Global, Layout, and Sidebar Styles (Same as your Admin Dashboard) --- */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        :root {
            --primary-blue: #0d6efd; --primary-blue-dark: #0a58ca; --sidebar-bg-start: #1c2a4c; --sidebar-bg-end: #111827;
            --sidebar-link-color: #9ca3af; --sidebar-link-hover-bg: #1f2937; --sidebar-link-active-color: #ffffff;
            --content-bg: #f4f7fa; --card-bg: #ffffff; --dark-text: #111827;
            --light-text: #6b7280; --border-color: #e5e7eb; --white-color: #ffffff;
            --success-color: #10b981; --warning-color: #f59e0b; --danger-color: #ef4444;
            --font-family: 'Inter', sans-serif;
        }
        body, html { margin: 0; padding: 0; font-family: var(--font-family); background-color: var(--content-bg); }
        .dashboard-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background-image: linear-gradient(180deg, var(--sidebar-bg-start) 0%, var(--sidebar-bg-end) 100%); padding: 1.5rem; display: flex; flex-direction: column; color: var(--white-color); flex-shrink: 0; }
        .sidebar .logo { font-size: 1.8rem; font-weight: 800; color: var(--white-color); text-decoration: none; text-align: center; margin-bottom: 2.5rem; padding: 0.5rem 0; }
        .sidebar-menu h3 { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--sidebar-link-color); margin: 1.5rem 0 0.5rem 0.75rem; }
        .sidebar-menu ul { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu ul li a { display: flex; align-items: center; gap: 0.85rem; padding: 0.85rem 0.75rem; color: var(--sidebar-link-color); text-decoration: none; border-radius: 8px; margin-bottom: 0.25rem; font-weight: 500; transition: all 0.2s ease; }
        .sidebar-menu ul li a .icon { font-size: 1.1rem; width: 20px; text-align: center; }
        .sidebar-menu ul li a:hover { background-color: var(--sidebar-link-hover-bg); color: var(--sidebar-link-active-color); }
        .sidebar-menu ul li a.active { background-color: var(--primary-blue); color: var(--sidebar-link-active-color); box-shadow: 0 4px 10px rgba(0, 115, 230, 0.3); }
        .main-content-wrapper { flex: 1; padding: 2.5rem; overflow-y: auto; }
        .main-content-header h2 { font-size: 1.8rem; font-weight: 700; color: var(--dark-text); margin: 0 0 0.5rem 0; }
        .main-content-header p { color: var(--light-text); margin: 0 0 2.5rem 0; }

        /* ✅ NEW: Modern Table Styles */
        .panel { 
            background-color: var(--card-bg); 
            padding: 2rem; 
            border-radius: 12px; 
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); 
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .content-table {
            width: 100%;
            border-collapse: collapse;
        }

        .content-table th {
            text-align: left;
            padding: 1rem;
            color: var(--light-text);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
        }

        .content-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--dark-text);
            font-weight: 500;
            vertical-align: middle;
        }

        .content-table tbody tr:last-child td {
            border-bottom: none;
        }

        .content-table tbody tr:hover {
            background-color: var(--content-bg);
        }

        /* Role Badge Styles */
        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .role-freelancer { background-color: #d1fae5; color: #059669; }
        .role-employer { background-color: #e0f2fe; color: #0284c7; }
        
        /* In-table form element styles */
        .level-form { display: flex; gap: 8px; align-items: center; }
        .level-form select {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            font-family: var(--font-family);
            font-size: 0.9rem;
        }
        .btn { padding: 0.5rem 1rem; text-decoration: none; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s ease; }
        .btn-primary { background-color: var(--primary-blue); color: var(--white-color); }
        .btn-primary:hover { background-color: var(--primary-blue-dark); }
        .btn-danger { background-color: var(--danger-color); color: var(--white-color); }
        .btn-danger:hover { background-color: #b91c1c; }
        
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
            <a href="index.php" class="logo">Freedly Admin</a>
            <div class="sidebar-menu">
                <h3>Menu</h3>
                <ul>
                    <li><a href="admin_dashboard.php"><i class="fas fa-home icon"></i> Dashboard</a></li>
                    <li><a href="admin_manage_users.php" class="active"><i class="fas fa-users icon"></i> Manage Users</a></li>
                    <li><a href="admin_manage_jobs.php"><i class="fas fa-briefcase icon"></i> Manage Jobs</a></li>
                    <li><a href="admin_manage_reports.php"><i class="fas fa-flag icon"></i> Manage Reports</a></li>
                </ul>
                <h3>Account</h3>
                <ul>
                    <li><a href="user_logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a></li>
                </ul>
            </div>
        </aside>

        <main class="main-content-wrapper">
            <div class="main-content-header">
                <h2>Manage Users</h2>
                <p>Oversee all freelancer and employer accounts on the platform.</p>
            </div>
            
            <div class="panel">
                <table class="content-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Level</th>
                            <th>Joined On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <span class="role-badge role-<?= htmlspecialchars($user['role']) ?>">
                                    <?= ucfirst(htmlspecialchars($user['role'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['role'] === 'freelancer'): ?>
                                    <form action="admin_manage_users.php" method="POST" class="level-form">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <select name="level">
                                            <option value="New" <?= ($user['level'] == 'New') ? 'selected' : '' ?>>New</option>
                                            <option value="Level 1" <?= ($user['level'] == 'Level 1') ? 'selected' : '' ?>>Level 1</option>
                                            <option value="Top Rated" <?= ($user['level'] == 'Top Rated') ? 'selected' : '' ?>>Top Rated</option>
                                        </select>
                                        <button type="submit" name="set_level" class="btn btn-primary">Set</button>
                                    </form>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                            <td>
                                <form action="admin_manage_users.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This is irreversible.');">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" name="delete_user" class="btn btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($users) === 0): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No users found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>