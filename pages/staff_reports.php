<?php
include '../config/db.php';
requireLogin();

$user = getCurrentUser();

// STAFF ONLY
if (strtolower($user['role']) !== 'staff') {
    header("Location: ../dashboards/dashboard.php");
    exit();
}

// Get statistics
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits")->fetch_assoc()['count'];
$ongoing_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits WHERE status='ongoing'")->fetch_assoc()['count'];
$completed_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits WHERE status='completed'")->fetch_assoc()['count'];

// Get monthly visit data
$monthly_visits = $conn->query("SELECT DATE_FORMAT(visit_date, '%Y-%m') as month, COUNT(*) as count FROM clinic_visits GROUP BY DATE_FORMAT(visit_date, '%Y-%m') ORDER BY month DESC LIMIT 12");
$months = [];
$visit_counts = [];
while ($row = $monthly_visits->fetch_assoc()) {
    array_unshift($months, $row['month']);
    array_unshift($visit_counts, $row['count']);
}

// Top students by visits
$top_students = $conn->query("SELECT s.name, COUNT(cv.id) as visit_count FROM students s LEFT JOIN clinic_visits cv ON s.id = cv.student_id GROUP BY s.id ORDER BY visit_count DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Staff - SCHoRD</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .chart-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }

        .chart-card h2 {
            margin-bottom: 20px;
            color: var(--text-dark);
            font-size: 18px;
        }

        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 20px;
        }

        .table-card {
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

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .badge-info {
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-wrapper { margin-left: 0; }
            .stats-grid {
                grid-template-columns: 1fr;
            }
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
            <li><a href="staff_visits.php"><span>📋</span> Visits</a></li>
            <li><a href="staff_health_records.php"><span>📝</span> Health Records</a></li>
            <li><a href="staff_reports.php" class="active"><span>📈</span> Reports</a></li>
            <li style="margin-top: 20px; border-top: 2px solid rgba(255,255,255,0.1); padding-top: 20px;">
                <a href="../auth/logout.php"><span>🚪</span> Logout</a>
            </li>
        </ul>
    </aside>

    <div class="main-wrapper">
        <header class="top-header">
            <h2 style="color: var(--text-dark);">📈 Reports</h2>
        </header>

        <div class="main-content">
            <div class="page-header">
                <h1>Staff Analytics & Reports</h1>
            </div>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Students</div>
                    <div class="stat-value"><?php echo $total_students; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Visits</div>
                    <div class="stat-value"><?php echo $total_visits; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Ongoing Visits</div>
                    <div class="stat-value"><?php echo $ongoing_visits; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Completed Visits</div>
                    <div class="stat-value"><?php echo $completed_visits; ?></div>
                </div>
            </div>

            <!-- CHARTS -->
            <div class="chart-card">
                <h2>📈 Monthly Visits Trend</h2>
                <div class="chart-container">
                    <canvas id="visitChart"></canvas>
                </div>
            </div>

            <!-- TOP STUDENTS -->
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Top Students by Visits</th>
                            <th>Number of Visits</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($student = $top_students->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><span class="badge badge-info"><?php echo $student['visit_count']; ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Visit Chart
        const ctx = document.getElementById('visitChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Visits',
                    data: <?php echo json_encode($visit_counts); ?>,
                    fill: true,
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 3,
                    tension: 0.4,
                    pointRadius: 6,
                    pointBackgroundColor: 'rgba(99, 102, 241, 1)',
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            font: { size: 14, weight: 'bold' },
                            color: '#1f2937'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    </script>
</body>
</html>
