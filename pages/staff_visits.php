<?php
include '../config/db.php';
requireLogin();

$user = getCurrentUser();

// STAFF ONLY
if (strtolower($user['role']) !== 'staff') {
    header("Location: ../dashboards/dashboard.php");
    exit();
}

$success = '';
$error = '';
$edit_visit = null;

// Add or Update Visit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = sanitize($_POST['student_id']);
    $complaint = sanitize($_POST['complaint']);
    $treatment = sanitize($_POST['treatment']);
    $status = sanitize($_POST['status']);

    if (empty($student_id) || empty($complaint) || empty($treatment)) {
        $error = '❌ All fields are required';
    } else {
        if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
            $edit_id = sanitize($_POST['edit_id']);
            if ($conn->query("UPDATE clinic_visits SET complaint='$complaint', treatment='$treatment', status='$status' WHERE id='$edit_id'")) {
                $success = '✅ Visit updated successfully!';
                $_POST = array();
                $edit_visit = null;
            } else {
                $error = '❌ Error updating visit';
            }
        } else {
            if ($conn->query("INSERT INTO clinic_visits (student_id, visit_date, complaint, treatment, status) VALUES ('$student_id', NOW(), '$complaint', '$treatment', '$status')")) {
                $success = '✅ Visit recorded successfully!';
                $_POST = array();
            } else {
                $error = '❌ Error recording visit';
            }
        }
    }
}

// Delete Visit
if (isset($_GET['delete'])) {
    $id = sanitize($_GET['delete']);
    if ($conn->query("DELETE FROM clinic_visits WHERE id='$id'")) {
        $success = '✅ Visit deleted successfully!';
    } else {
        $error = '❌ Error deleting visit';
    }
}

// Get visit to edit
if (isset($_GET['edit'])) {
    $id = sanitize($_GET['edit']);
    $result = $conn->query("SELECT * FROM clinic_visits WHERE id='$id'");
    if ($result->num_rows > 0) {
        $edit_visit = $result->fetch_assoc();
    }
}

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$query = "SELECT cv.*, s.name, s.student_no FROM clinic_visits cv JOIN students s ON cv.student_id = s.id WHERE 1=1";
if ($search) {
    $query .= " AND (s.name LIKE '%$search%' OR s.student_no LIKE '%$search%' OR cv.complaint LIKE '%$search%')";
}
if ($status_filter) {
    $query .= " AND cv.status = '$status_filter'";
}
$query .= " ORDER BY cv.visit_date DESC";

$visits = $conn->query($query);
$total_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits")->fetch_assoc()['count'];
$ongoing = $conn->query("SELECT COUNT(*) as count FROM clinic_visits WHERE status='ongoing'")->fetch_assoc()['count'];
$completed = $conn->query("SELECT COUNT(*) as count FROM clinic_visits WHERE status='completed'")->fetch_assoc()['count'];

$students = $conn->query("SELECT id, name, student_no FROM students ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Visits - Staff - SCHoRD</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #818cf8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1e1b4b;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f3f0ff;
            --bg-card: #ffffff;
            --shadow: 0 4px 20px rgba(99, 102, 241, 0.08);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
            color: var(--text-dark);
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, var(--dark) 0%, var(--primary-dark) 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
            border-right: 3px solid var(--primary);
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-header h2 {
            font-size: 18px;
            font-weight: 800;
        }

        .sidebar-nav {
            padding: 20px 0;
            list-style: none;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--secondary);
            padding-left: 16px;
        }

        .role-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.15);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-top: 10px;
            color: var(--secondary);
        }

        .main-wrapper {
            margin-left: 260px;
        }

        .top-header {
            background: var(--bg-card);
            border-bottom: 2px solid var(--primary);
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .main-content {
            padding: 30px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow);
            border-top: 5px solid var(--primary);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-light);
            text-transform: uppercase;
            font-weight: 700;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .form-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-size: 13px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            border: 2px solid var(--bg-light);
            border-radius: 8px;
            color: var(--text-dark);
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.3);
        }

        .table-card {
            background: var(--bg-card);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }

        th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--bg-light);
        }

        tbody tr:hover {
            background: var(--bg-light);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-ongoing {
            background: rgba(245, 158, 11, 0.15);
            color: var(--warning);
        }

        .status-completed {
            background: rgba(16, 185, 129, 0.15);
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
            font-size: 11px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }

        .action-edit {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .action-delete {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .action-btn:hover {
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-wrapper { margin-left: 0; }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <span>👔</span>
            <div>
                <h2>SCHoRD</h2>
                <span class="role-badge">👨‍💼 STAFF</span>
            </div>
        </div>
        <ul class="sidebar-nav">
            <li><a href="../dashboards/staff_dashboard.php"><span>📊</span> Dashboard</a></li>
            <li><a href="staff_patients.php"><span>👥</span> Patients</a></li>
            <li><a href="staff_visits.php" class="active"><span>📋</span> Visits</a></li>
            <li><a href="staff_health_records.php"><span>📝</span> Health Records</a></li>
            <li><a href="staff_reports.php"><span>📈</span> Reports</a></li>
            <li style="margin-top: 20px; border-top: 2px solid rgba(255,255,255,0.1); padding-top: 20px;">
                <a href="../auth/logout.php"><span>🚪</span> Logout</a>
            </li>
        </ul>
    </aside>

    <div class="main-wrapper">
        <header class="top-header">
            <h2 style="color: var(--text-dark);">📋 Clinic Visits</h2>
        </header>

        <div class="main-content">
            <div class="page-header">
                <h1>Clinic Visits Management</h1>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Visits</div>
                    <div class="stat-value"><?php echo $total_visits; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Ongoing</div>
                    <div class="stat-value"><?php echo $ongoing; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Completed</div>
                    <div class="stat-value"><?php echo $completed; ?></div>
                </div>
            </div>

            <!-- FORM -->
            <div class="form-card">
                <h2><?php echo $edit_visit ? 'Edit Visit' : 'Record New Visit'; ?></h2>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Student</label>
                            <select name="student_id" required>
                                <option value="">Select Student</option>
                                <?php while ($student = $students->fetch_assoc()): ?>
                                    <option value="<?php echo $student['id']; ?>" 
                                        <?php echo ($edit_visit && $edit_visit['student_id'] == $student['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($student['name']) . ' (' . $student['student_no'] . ')'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" required>
                                <option value="ongoing" <?php echo (!$edit_visit || $edit_visit['status'] == 'ongoing') ? 'selected' : ''; ?>>Ongoing</option>
                                <option value="completed" <?php echo ($edit_visit && $edit_visit['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Complaint</label>
                        <textarea name="complaint" required><?php echo $edit_visit ? htmlspecialchars($edit_visit['complaint']) : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Treatment</label>
                        <textarea name="treatment" required><?php echo $edit_visit ? htmlspecialchars($edit_visit['treatment']) : ''; ?></textarea>
                    </div>
                    <?php if ($edit_visit): ?>
                        <input type="hidden" name="edit_id" value="<?php echo $edit_visit['id']; ?>">
                    <?php endif; ?>
                    <button type="submit" class="btn">✅ <?php echo $edit_visit ? 'Update' : 'Save'; ?> Visit</button>
                </form>
            </div>

            <!-- TABLE -->
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Complaint</th>
                            <th>Treatment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($visit = $visits->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('Y-m-d H:i', strtotime($visit['visit_date'])); ?></td>
                                <td><?php echo htmlspecialchars($visit['name']); ?></td>
                                <td><?php echo htmlspecialchars(substr($visit['complaint'], 0, 30)); ?></td>
                                <td><?php echo htmlspecialchars(substr($visit['treatment'], 0, 30)); ?></td>
                                <td><span class="status-badge status-<?php echo $visit['status']; ?>"><?php echo ucfirst($visit['status']); ?></span></td>
                                <td>
                                    <div class="actions">
                                        <a href="?edit=<?php echo $visit['id']; ?>" class="action-btn action-edit">Edit</a>
                                        <a href="?delete=<?php echo $visit['id']; ?>" class="action-btn action-delete" onclick="return confirm('Delete?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
