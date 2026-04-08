<?php
include '../config/db.php';
requireLogin();

$user = getCurrentUser();

// Only staff can access this page
if (strtolower($user['role']) !== 'staff') {
    header("Location: ../dashboards/dashboard.php");
    exit();
}

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Get all patients with their latest health records
if ($search) {
    $patients = $conn->query("
        SELECT s.*, 
               COUNT(cv.id) as total_visits
        FROM students s 
        LEFT JOIN clinic_visits cv ON s.id = cv.student_id
        WHERE s.name LIKE '%$search%' OR s.student_no LIKE '%$search%' OR s.course LIKE '%$search%'
        GROUP BY s.id
        ORDER BY s.name ASC
    ");
} else {
    $patients = $conn->query("
        SELECT s.*, 
               COUNT(cv.id) as total_visits
        FROM students s 
        LEFT JOIN clinic_visits cv ON s.id = cv.student_id
        GROUP BY s.id
        ORDER BY s.name ASC
    ");
}

$total_patients = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients Management - Staff - SCHoRD</title>
    <style>
        <?php
            // Staff indigo theme
            $primary = '#6366f1';
            $primary_dark = '#4f46e5';
        ?>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: <?php echo $primary; ?>;
            --primary-dark: <?php echo $primary_dark; ?>;
            --secondary: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #f3f4f6;
            --bg-card: #ffffff;
            --shadow: 0 4px 20px rgba(63, 63, 70, 0.08);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: var(--text-dark);
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, #1f2937 0%, var(--primary-dark) 100%);
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

        /* MAIN */
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

        .search-box {
            display: flex;
            align-items: center;
            background: var(--bg-light);
            border: 2px solid var(--primary);
            border-radius: 25px;
            padding: 10px 20px;
            gap: 10px;
            width: 400px;
        }

        .search-box input {
            border: none;
            background: none;
            outline: none;
            flex: 1;
            color: var(--text-dark);
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
            border-radius: 15px;
            padding: 20px;
            box-shadow: var(--shadow);
            border-top: 5px solid var(--primary);
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-light);
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
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
            color: var(--text-dark);
            font-size: 14px;
        }

        tbody tr:hover {
            background: var(--bg-light);
        }

        .btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main-wrapper {
                margin-left: 0;
            }

            .search-box {
                width: 100%;
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
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <span>👨‍💼</span>
            <div>
                <h2>SCHoRD</h2>
                <span class="role-badge">👤 STAFF</span>
            </div>
        </div>
        <ul class="sidebar-nav">
            <li><a href="../dashboards/staff_dashboard.php"><span>📊</span> Dashboard</a></li>
            <li><a href="staff_patients.php" class="active"><span>👥</span> Patients</a></li>
            <li><a href="staff_visits.php"><span>📋</span> Visits</a></li>
            <li><a href="staff_health_records.php"><span>📝</span> Health Records</a></li>
            <li><a href="staff_reports.php"><span>📈</span> Reports</a></li>
            <li style="margin-top: 20px; border-top: 2px solid rgba(255,255,255,0.1); padding-top: 20px;">
                <a href="../auth/logout.php"><span>🚪</span> Logout</a>
            </li>
        </ul>
    </aside>

    <div class="main-wrapper">
        <!-- TOP HEADER -->
        <header class="top-header">
            <div>
                <h2 style="color: var(--text-dark);">👥 Patients Management</h2>
            </div>
            <div class="search-box">
                <span>🔍</span>
                <form method="GET" style="display: flex; flex: 1;">
                    <input type="text" name="search" placeholder="Search patient by name or ID..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" style="background: none; border: none; cursor: pointer; color: var(--primary);">🔎</button>
                </form>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <div style="background: #6366f1; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; text-align: center;">
                ✅ STAFF PATIENTS PAGE (staff_patients.php) - INDIGO THEME
            </div>

            <!-- PAGE HEADER -->
            <div class="page-header">
                <h1>Patient Records</h1>
            </div>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Patients</div>
                    <div class="stat-value"><?php echo $total_patients; ?></div>
                </div>
            </div>

            <!-- PATIENTS TABLE -->
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>👤 Patient Name</th>
                            <th>📋 ID</th>
                            <th>📚 Course</th>
                            <th>🎂 Age</th>
                            <th>📊 Visits</th>
                            <th>🔧 Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($patient = $patients->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($patient['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($patient['student_no']); ?></td>
                                <td><?php echo htmlspecialchars($patient['course']); ?></td>
                                <td><?php echo $patient['age']; ?></td>
                                <td><strong><?php echo $patient['total_visits'] ?? 0; ?></strong></td>
                                <td>
                                    <a href="health_records.php?student=<?php echo $patient['id']; ?>" class="btn">View Records</a>
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
