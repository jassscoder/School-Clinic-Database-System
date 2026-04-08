<?php
include '../config/db.php';
requireLogin();

$user = getCurrentUser();

// Check if admin - redirect based on role
if (strtolower($user['role']) !== 'admin') {
    if (strtolower($user['role']) === 'nurse') {
        header("Location: nurse_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$success = '';
$error = '';
$edit_user = null;

// Add new user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_user') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = sanitize($_POST['role']);

    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = '❌ All fields are required';
    } elseif (strlen($password) < 6) {
        $error = '❌ Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = '❌ Passwords do not match';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '❌ Invalid email format';
    } else {
        $check = $conn->query("SELECT id FROM users WHERE email='$email'");
        if ($check->num_rows > 0) {
            $error = '❌ Email already exists';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            if ($conn->query("INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$hashed_password', '$role')")) {
                $success = '✅ User added successfully!';
                $_POST = array();
            } else {
                $error = '❌ Error adding user';
            }
        }
    }
}

// Update user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_user') {
    $edit_id = sanitize($_POST['edit_id']);
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $role = sanitize($_POST['role']);

    if (empty($name) || empty($email) || empty($role)) {
        $error = '❌ All fields are required';
    } else {
        $check = $conn->query("SELECT id FROM users WHERE email='$email' AND id!='$edit_id'");
        if ($check->num_rows > 0) {
            $error = '❌ Email already exists';
        } else {
            if ($conn->query("UPDATE users SET name='$name', email='$email', role='$role' WHERE id='$edit_id'")) {
                $success = '✅ User updated successfully!';
                $_POST = array();
                $edit_user = null;
            } else {
                $error = '❌ Error updating user';
            }
        }
    }
}

// Delete user
if (isset($_GET['delete'])) {
    $id = sanitize($_GET['delete']);
    if ($id == $user['id']) {
        $error = '❌ You cannot delete your own account';
    } else {
        if ($conn->query("DELETE FROM users WHERE id='$id'")) {
            $success = '✅ User deleted successfully!';
        } else {
            $error = '❌ Error deleting user';
        }
    }
}

// Get user statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$admins = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='admin'")->fetch_assoc()['count'];
$nurses = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='nurse'")->fetch_assoc()['count'];
$staff = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='staff'")->fetch_assoc()['count'];

// Search and filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitize($_GET['role']) : '';

$query = "SELECT * FROM users WHERE 1=1";
if ($search) {
    $query .= " AND (name LIKE '%$search%' OR email LIKE '%$search%')";
}
if ($role_filter) {
    $query .= " AND role = '$role_filter'";
}
$query .= " ORDER BY created_at DESC";

$users = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --accent: #ec4899;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --text-lighter: #94a3b8;
            --bg-light: #f8fafc;
            --bg-lighter: #f1f5f9;
            --bg-card: #ffffff;
            --bg-hover: #f3f4f6;
            --border-color: #e2e8f0;
            --shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #fef3f2 100%);
            color: var(--text-dark);
            min-height: 100vh;
            letter-spacing: -0.3px;
        }

        body.dark-mode {
            background: linear-gradient(135deg, #06111f 0%, #0f172a 50%, #1a1f3a 100%);
            --bg-card: #1e293b;
            --bg-light: #1e293b;
            --text-dark: #f1f5f9;
            --text-light: #cbd5e1;
            --border-color: #334155;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: linear-gradient(135deg, var(--bg-card), rgba(255, 255, 255, 0.5));
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.1);
            border-left: 5px solid var(--primary);
        }

        body.dark-mode .header {
            background: linear-gradient(135deg, var(--bg-card), rgba(30, 41, 59, 0.8));
        }

        .header h1 {
            font-size: 28px;
            color: var(--text-dark);
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .header-actions {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
        }

        .btn-secondary {
            background: var(--bg-light);
            color: var(--text-dark);
            border: 2px solid var(--border-color);
        }

        .btn-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(239, 68, 68, 0.3);
        }

        .btn-sm {
            padding: 8px 14px;
            font-size: 12px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--bg-card), rgba(255, 255, 255, 0.5));
            backdrop-filter: blur(10px);
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.8);
            text-align: center;
        }

        body.dark-mode .stat-card {
            background: linear-gradient(135deg, var(--bg-card), rgba(30, 41, 59, 0.8));
            border-color: rgba(99, 102, 241, 0.1);
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
            margin: 10px 0;
        }

        .stat-card .label {
            font-size: 13px;
            color: var(--text-light);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Messages */
        .alert {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 5px solid;
            font-weight: 600;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: var(--success);
            color: var(--success);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border-color: var(--danger);
            color: var(--danger);
        }

        /* Filter Section */
        .filter-section {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            background: linear-gradient(135deg, var(--bg-card), rgba(255, 255, 255, 0.5));
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        body.dark-mode .filter-section {
            background: linear-gradient(135deg, var(--bg-card), rgba(30, 41, 59, 0.8));
            border-color: rgba(99, 102, 241, 0.1);
        }

        .filter-section input,
        .filter-section select {
            padding: 10px 14px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background: var(--bg-card);
            color: var(--text-dark);
        }

        .filter-section input:focus,
        .filter-section select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        /* Table */
        .table-wrapper {
            background: linear-gradient(135deg, var(--bg-card), rgba(255, 255, 255, 0.5));
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.8);
            overflow: hidden;
        }

        body.dark-mode .table-wrapper {
            background: linear-gradient(135deg, var(--bg-card), rgba(30, 41, 59, 0.8));
            border-color: rgba(99, 102, 241, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(236, 72, 153, 0.05));
            border-bottom: 2px solid var(--border-color);
        }

        body.dark-mode thead {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(236, 72, 153, 0.08));
        }

        th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 700;
            color: var(--text-dark);
            font-size: 13px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }

        tbody tr {
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background: var(--bg-hover);
        }

        body.dark-mode tbody tr:hover {
            background: rgba(99, 102, 241, 0.1);
        }

        .role-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .role-admin {
            background: rgba(99, 102, 241, 0.2);
            color: var(--primary);
        }

        .role-nurse {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }

        .role-staff {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-size: 20px;
            color: var(--text-dark);
            margin-bottom: 8px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👥 User Management</h1>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="document.getElementById('addUserForm').style.display = document.getElementById('addUserForm').style.display === 'none' ? 'block' : 'none'">➕ Add New User</button>
                <a href="dashboard_admin.php" class="btn btn-secondary">← Back</a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">✓ <?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">✕ <?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div style="font-size: 28px;">👥</div>
                <div class="number"><?php echo $total_users; ?></div>
                <div class="label">Total Users</div>
            </div>
            <div class="stat-card">
                <div style="font-size: 28px;">🔐</div>
                <div class="number"><?php echo $admins; ?></div>
                <div class="label">Admins</div>
            </div>
            <div class="stat-card">
                <div style="font-size: 28px;">🏥</div>
                <div class="number"><?php echo $nurses; ?></div>
                <div class="label">Nurses</div>
            </div>
            <div class="stat-card">
                <div style="font-size: 28px;">👔</div>
                <div class="number"><?php echo $staff; ?></div>
                <div class="label">Staff</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <input type="text" placeholder="🔍 Search by name or email..." value="<?php echo htmlspecialchars($search); ?>" onchange="window.location.href='?search='+this.value">
            <select onchange="window.location.href='?role='+this.value">
                <option value="">All Roles</option>
                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="nurse" <?php echo $role_filter === 'nurse' ? 'selected' : ''; ?>>Nurse</option>
                <option value="staff" <?php echo $role_filter === 'staff' ? 'selected' : ''; ?>>Staff</option>
            </select>
        </div>

        <!-- Table -->
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th width="140">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users && $users->num_rows > 0): ?>
                        <?php while ($u = $users->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><span class="role-badge role-<?php echo strtolower($u['role']); ?>"><?php echo $u['role']; ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($u['created_at'] ?? 'now')); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-primary btn-sm" onclick="editUser(<?php echo $u['id']; ?>)">Edit</button>
                                        <a href="?delete=<?php echo $u['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <div class="empty-state-icon">👥</div>
                                    <h3>No Users Found</h3>
                                    <p>There are no users matching your criteria.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function editUser(id) {
            alert('Edit functionality coming soon!');
        }
    </script>
</body>
</html>
            background: #e2e8f0;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background: #fee2e2;
            color: #7f1d1d;
            border-left: 4px solid var(--danger);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--bg-card);
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
        }

        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-light);
            text-transform: uppercase;
            font-weight: 600;
        }

        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            background: var(--bg-card);
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            flex-wrap: wrap;
            align-items: center;
        }

        .filters input,
        .filters select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            flex: 1;
            min-width: 200px;
        }

        .filters input:focus,
        .filters select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        .table-container {
            background: var(--bg-card);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: var(--bg-light);
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
        }

        th {
            padding: 15px 20px;
            text-align: left;
            font-weight: 700;
            color: var(--text-dark);
            font-size: 13px;
            text-transform: uppercase;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            color: var(--text-dark);
            font-size: 14px;
        }

        tbody tr:hover {
            background: var(--bg-light);
        }

        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
        }

        .role-admin {
            background: rgba(220, 38, 38, 0.1);
            color: var(--primary);
        }

        .role-nurse {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .role-staff {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .action-edit {
            background: #3b82f6;
            color: white;
        }

        .action-edit:hover {
            background: #2563eb;
        }

        .action-delete {
            background: #ef4444;
            color: white;
        }

        .action-delete:hover {
            background: #dc2626;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            font-size: 22px;
            color: var(--text-dark);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: var(--text-light);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .header-actions {
                width: 100%;
                flex-direction: column;
            }

            .filters {
                flex-direction: column;
            }

            .filters input,
            .filters select {
                min-width: 100%;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1>👥 Manage Users</h1>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="window.location.href='dashboard_admin.php'">← Back to Dashboard</button>
                <button class="btn btn-primary" onclick="openAddUserModal()" type="button">➕ Add New User</button>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_users; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $admins; ?></div>
                <div class="stat-label">Admin Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $nurses; ?></div>
                <div class="stat-label">Nurses</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $staff; ?></div>
                <div class="stat-label">Staff Members</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%; align-items: center;">
                <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; min-width: 200px;">
                <select name="role">
                    <option value="">All Roles</option>
                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="nurse" <?php echo $role_filter === 'nurse' ? 'selected' : ''; ?>>Nurse</option>
                    <option value="staff" <?php echo $role_filter === 'staff' ? 'selected' : ''; ?>>Staff</option>
                </select>
                <button type="submit" class="btn btn-secondary" style="min-width: auto;">🔍 Filter</button>
                <a href="admin_users.php" class="btn btn-secondary" style="min-width: auto;">Clear</a>
            </form>
        </div>

        <!-- Users Table -->
        <div class="table-container">
            <?php if ($users->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($u = $users->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $u['role']; ?>">
                                        <?php echo ucfirst($u['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <button class="action-btn action-edit" type="button" onclick="openEditUserModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['name']); ?>', '<?php echo htmlspecialchars($u['email']); ?>', '<?php echo $u['role']; ?>')">✏️ Edit</button>
                                        <?php if ($u['id'] !== $user['id']): ?>
                                            <button class="action-btn action-delete" type="button" onclick="if(confirm('Are you sure you want to delete this user?')) { window.location.href='admin_users.php?delete=<?php echo $u['id']; ?>'; }">🗑️ Delete</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: 40px; text-align: center; color: var(--text-light);">
                    <div style="font-size: 48px; margin-bottom: 10px;">👥</div>
                    <p>No users found. Try adjusting your filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal" id="addUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>➕ Add New User</h2>
                <button class="close-btn" onclick="closeModal('addUserModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" required>
                        <option value="staff">Staff</option>
                        <option value="nurse">Nurse</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group" style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">✅ Add User</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal" id="editUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit User</h2>
                <button class="close-btn" onclick="closeModal('editUserModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" id="edit_role" required>
                        <option value="staff">Staff</option>
                        <option value="nurse">Nurse</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group" style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">💾 Save Changes</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')" style="flex: 1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddUserModal() {
            document.getElementById('addUserModal').classList.add('active');
        }

        function openEditUserModal(id, name, email, role) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_role').value = role;
            document.getElementById('editUserModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        document.addEventListener('click', function(event) {
            const addModal = document.getElementById('addUserModal');
            const editModal = document.getElementById('editUserModal');
            if (event.target === addModal) addModal.classList.remove('active');
            if (event.target === editModal) editModal.classList.remove('active');
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                document.getElementById('addUserModal').classList.remove('active');
                document.getElementById('editUserModal').classList.remove('active');
            }
        });
    </script>
</body>
</html>
