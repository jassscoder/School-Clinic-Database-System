<?php
include '../config/db.php';
requireLogin();

$user = getCurrentUser();

// NURSE ONLY
if (strtolower($user['role']) !== 'nurse') {
    header("Location: ../dashboards/dashboard.php");
    exit();
}

$total_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits")->fetch_assoc()['count'];
$total_patients = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$completed_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits WHERE status='completed'")->fetch_assoc()['count'];
$ongoing_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits WHERE status='ongoing'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Nurse - SCHoRD</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0891b2;
            --primary-dark: #0e7490;
            --secondary: #06b6d4;
            --success: #10b981;
            --warning: #f59e0b;
            --dark: #082f49;
            --text-dark: #0c2340;
            --text-light: #64748b;
            --bg-light: #f8fbff;
            --bg-card: #ffffff;
            --shadow: 0 4px 20px rgba(8, 47, 73, 0.08);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
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
            box-shadow: var(--shadow);
        }

        .main-content {
            padding: 30px;
        }

        .page-header h1 {
            font-size: 32px;
            font-weight: 800;
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
            padding: 25px;
            box-shadow: var(--shadow);
            border-top: 5px solid var(--primary);
            text-align: center;
        }

        .stat-value {
            font-size: 38px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-light);
            text-transform: uppercase;
            font-weight: 700;
        }

        .chart-container {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            position: relative;
            height: 350px;
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-wrapper { margin-left: 0; }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <span>⚕️</span>
            <div>
                <h2>SCHoRD</h2>
                <span class="role-badge">👩‍⚕️ NURSE</span>
            </div>
        </div>
        <ul class="sidebar-nav">
            <li><a href="../dashboards/nurse_dashboard.php"><span>📊</span> Dashboard</a></li>
            <li><a href="nurse_patients.php"><span>👥</span> Patients</a></li>
            <li><a href="nurse_visits.php"><span>📋</span> Visits</a></li>
            <li><a href="nurse_health_records.php"><span>❤️</span> Health Records</a></li>
            <li><a href="nurse_reports.php" class="active"><span>📈</span> Reports</a></li>
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
                <h1>Reports & Analytics</h1>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Patients</div>
                    <div class="stat-value"><?php echo $total_patients; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Visits</div>
                    <div class="stat-value"><?php echo $total_visits; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Completed</div>
                    <div class="stat-value"><?php echo $completed_visits; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Ongoing</div>
                    <div class="stat-value"><?php echo $ongoing_visits; ?></div>
                </div>
            </div>

            <div class="chart-container">
                <canvas id="visitChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        const visitCtx = document.getElementById('visitChart')?.getContext('2d');
        if (visitCtx) {
            new Chart(visitCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'Ongoing'],
                    datasets: [{
                        data: [<?php echo $completed_visits; ?>, <?php echo $ongoing_visits; ?>],
                        backgroundColor: ['#10b981', '#f59e0b'],
                        borderColor: ['#047857', '#d97706'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { font: { size: 14 }, padding: 20 }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
