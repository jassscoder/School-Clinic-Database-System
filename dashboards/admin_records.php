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

// Delete health record
if (isset($_GET['delete'])) {
    $id = sanitize($_GET['delete']);
    if ($conn->query("DELETE FROM health_records WHERE id='$id'")) {
        $success = '✅ Health record deleted successfully!';
    } else {
        $error = '❌ Error deleting record';
    }
}

// Get health records
$records = $conn->query("
    SELECT hr.*, s.name as student_name, s.student_no
    FROM health_records hr
    JOIN students s ON hr.student_id = s.id
    ORDER BY hr.created_at DESC
    LIMIT 100
");

if (!$records) {
    echo "<div style='padding: 20px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin: 20px;'><strong>Database Error:</strong> " . htmlspecialchars($conn->error) . "<br><a href='../utils/migrate_database.php' style='color: #dc2626; font-weight: bold;'>Run database migration →</a></div>";
    $records = $conn->query("SELECT hr.id, hr.student_id, s.name as student_name, s.student_no FROM health_records hr JOIN students s ON hr.student_id = s.id LIMIT 0");
}

$total_records = $conn->query("SELECT COUNT(*) as count FROM health_records")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Records - Admin Dashboard</title>
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

        .header-right {
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

        /* Messages */
        .alert {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 5px solid;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
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
            font-size: 13px;
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
            <h1>📋 Health Records Management</h1>
            <div class="header-right">
                <a href="dashboard_admin.php" class="btn btn-secondary">← Back</a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <span>✓</span> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <span>✕</span> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Total Records</th>
                        <th>Last Updated</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($records && $records->num_rows > 0): ?>
                        <?php while ($record = $records->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($record['student_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($record['student_no']); ?></td>
                                <td><?php echo $total_records; ?></td>
                                <td><?php echo isset($record['created_at']) ? date('M d, Y', strtotime($record['created_at'])) : 'N/A'; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="#" class="btn btn-primary btn-sm">View</a>
                                        <a href="?delete=<?php echo $record['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <div class="empty-state-icon">📭</div>
                                    <h3>No Records Found</h3>
                                    <p>There are no health records to display at this time.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-secondary {
            background: var(--bg-light);
            color: var(--text-dark);
            border: 1px solid #ddd;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background: #fee2e2;
            color: #7f1d1d;
            border-left: 4px solid #ef4444;
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

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .action-view {
            background: #3b82f6;
            color: white;
        }

        .action-view:hover {
            background: #2563eb;
        }

        .action-delete {
            background: #ef4444;
            color: white;
        }

        .action-delete:hover {
            background: #dc2626;
        }

        .actions {
            display: flex;
            gap: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1>📋 Health Records</h1>
            </div>
            <div>
                <button class="btn btn-secondary" onclick="window.location.href='dashboard_admin.php'">← Back to Dashboard</button>
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
                <div class="stat-value"><?php echo $total_records; ?></div>
                <div class="stat-label">Total Records</div>
            </div>
        </div>

        <!-- Records Table -->
        <div class="table-container">
            <?php if ($records->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Blood Pressure</th>
                            <th>Temperature</th>
                            <th>Weight</th>
                            <th>Last Check</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = $records->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($r['student_name']); ?></strong><br><small><?php echo htmlspecialchars($r['student_no']); ?></small></td>
                                <td><?php echo htmlspecialchars($r['blood_pressure'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($r['temperature'] ?? 'N/A'); ?> °C</td>
                                <td><?php echo htmlspecialchars($r['weight'] ?? 'N/A'); ?> kg</td>
                                <td><?php echo $r['last_check_date'] ? date('M d, Y', strtotime($r['last_check_date'])) : 'N/A'; ?></td>
                                <td>
                                    <div class="actions">
                                        <button class="action-btn action-view" type="button" onclick="window.location.href='../pages/health_records.php?id=<?php echo $r['student_id']; ?>'">👁️ View</button>
                                        <button class="action-btn action-delete" type="button" onclick="if(confirm('Are you sure?')) { window.location.href='admin_records.php?delete=<?php echo $r['id']; ?>'; }">🗑️ Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: 40px; text-align: center; color: var(--text-light);">
                    <div style="font-size: 48px; margin-bottom: 10px;">📋</div>
                    <p>No health records found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
