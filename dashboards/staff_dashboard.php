<?php
include '../config/db.php';
requireLogin();

$user = getCurrentUser();

// Check if staff role - STAFF ONLY
if (strtolower($user['role']) !== 'staff') {
    if (strtolower($user['role']) === 'admin') {
        header("Location: dashboard_admin.php");
    } elseif (strtolower($user['role']) === 'nurse') {
        header("Location: nurse_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

// Get statistics
$total_students = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$total_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits")->fetch_assoc()['count'];
$total_records = $conn->query("SELECT COUNT(*) as count FROM health_records")->fetch_assoc()['count'];
$this_month_visits = $conn->query("SELECT COUNT(*) as count FROM clinic_visits WHERE MONTH(visit_date) = MONTH(CURDATE()) AND YEAR(visit_date) = YEAR(CURDATE())")->fetch_assoc()['count'];

// Get recent students (new admissions)
$recent_students = $conn->query("
    SELECT s.id, s.name, s.student_no, s.course, s.created_at
    FROM students s
    ORDER BY s.created_at DESC
    LIMIT 8
");

// Get recent visits
$recent_visits = $conn->query("
    SELECT cv.id, cv.visit_date, cv.complaint, cv.status, s.name as student_name, s.student_no
    FROM clinic_visits cv
    JOIN students s ON cv.student_id = s.id
    ORDER BY cv.visit_date DESC
    LIMIT 8
");

// Get health records with critical info
$health_overview = $conn->query("
    SELECT COUNT(*) as total_records, 
           SUM(CASE WHEN blood_pressure IS NOT NULL THEN 1 ELSE 0 END) as records_with_bp
    FROM health_records
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - SCHoRD Clinical Health Records</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #059669;
            --primary-light: #10b981;
            --primary-dark: #047857;
            --accent: #34d399;
            --accent-light: #6ee7b7;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #064e3b;
            --darker: #022c1d;
            --text-dark: #065f46;
            --text-light: #64748b;
            --text-lighter: #94a3b8;
            --bg-light: #ecfdf5;
            --bg-lighter: #d1fae5;
            --bg-card: #ffffff;
            --bg-hover: #f0fdf4;
            --border-color: #a7f3d0;
            --shadow: 0 10px 40px rgba(6, 78, 59, 0.1);
            --shadow-lg: 0 20px 60px rgba(6, 78, 59, 0.15);
            --shadow-glow: 0 0 60px rgba(5, 150, 105, 0.15);
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 50%, #e0fce8 100%);
            color: var(--text-dark);
            min-height: 100vh;
            letter-spacing: -0.3px;
        }

        body.dark-mode {
            background: linear-gradient(135deg, #022c1d 0%, #064e3b 50%, #0f5c47 100%);
            --bg-light: #1b4d3f;
            --bg-lighter: #0f5c47;
            --bg-card: #064e3b;
            --bg-hover: #0d5d4a;
            --border-color: #047857;
            --text-dark: #ecfdf5;
            --text-light: #cbd5e1;
            --text-lighter: #94a3b8;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(135deg, var(--darker) 0%, var(--dark) 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-right: 1px solid rgba(5, 150, 105, 0.1);
            box-shadow: 10px 0 40px rgba(0, 0, 0, 0.2);
            scrollbar-width: thin;
            scrollbar-color: rgba(5, 150, 105, 0.2) transparent;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(5, 150, 105, 0.2);
            border-radius: 10px;
        }

        .sidebar.collapsed {
            transform: translateX(-280px);
        }

        .sidebar-header {
            padding: 30px 25px;
            border-bottom: 1px solid rgba(5, 150, 105, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, rgba(5, 150, 105, 0.1) 0%, rgba(52, 211, 153, 0.05) 100%);
        }

        .sidebar-header h2 {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-logo {
            font-size: 28px;
            filter: drop-shadow(0 4px 10px rgba(5, 150, 105, 0.3));
        }

        .sidebar-nav {
            padding: 15px 0;
            list-style: none;
        }

        .sidebar-nav li {
            margin: 5px 0;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 20px;
            color: rgba(255, 255, 255, 0.65);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            cursor: pointer;
            border: none;
            background: none;
            text-align: left;
            width: 100%;
            font-size: 15px;
            position: relative;
            border-left: 3px solid transparent;
            pointer-events: auto;
            z-index: 10;
        }

        .sidebar-nav a::before {
            content: '';
            position: absolute;
            left: -20px;
            top: 0;
            bottom: 0;
            width: 0;
            background: rgba(5, 150, 105, 0.1);
            transition: width 0.3s ease;
            pointer-events: none;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            color: white;
            background: rgba(5, 150, 105, 0.1);
            transform: translateX(4px);
        }

        .sidebar-nav a:hover::before,
        .sidebar-nav a.active::before {
            width: 20px;
        }

        .sidebar-nav a.active {
            border-left-color: var(--accent);
            text-shadow: 0 0 10px rgba(5, 150, 105, 0.5);
        }

        .sidebar-nav .icon {
            font-size: 18px;
            min-width: 20px;
            filter: drop-shadow(0 2px 4px rgba(5, 150, 105, 0.2));
        }

        /* ===== MAIN CONTENT ===== */
        .main-wrapper {
            margin-left: 280px;
            transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .main-wrapper.sidebar-collapsed {
            margin-left: 0;
        }

        /* ===== TOP HEADER ===== */
        .top-header {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 18px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            position: sticky;
            top: 0;
            z-index: 100;
            gap: 20px;
        }

        body.dark-mode .top-header {
            background: rgba(6, 78, 59, 0.5);
            border-bottom-color: rgba(5, 150, 105, 0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-dark);
        }

        .header-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.5px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: rgba(5, 150, 105, 0.08);
            border: 1px solid rgba(5, 150, 105, 0.3);
            border-radius: 10px;
            padding: 10px 16px;
            gap: 10px;
            min-width: 300px;
        }

        .search-box input {
            border: none;
            background: none;
            outline: none;
            flex: 1;
            color: var(--text-dark);
            font-size: 14px;
        }

        .search-box input::placeholder {
            color: var(--text-light);
        }

        .theme-toggle {
            background: rgba(5, 150, 105, 0.1);
            border: 1px solid rgba(5, 150, 105, 0.3);
            border-radius: 10px;
            width: 44px;
            height: 44px;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .theme-toggle:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
            background: rgba(5, 150, 105, 0.08);
            border-radius: 10px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #059669, #10b981);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }

        /* ===== MAIN CONTENT AREA ===== */
        .main-content {
            padding: 35px;
        }

        .welcome-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.5) 100%);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 16px;
            border: 1px solid rgba(5, 150, 105, 0.1);
            box-shadow: var(--shadow);
        }

        body.dark-mode .welcome-section {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(6, 78, 59, 0.8) 100%);
            border-color: rgba(5, 150, 105, 0.15);
        }

        .welcome-text h2 {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .welcome-text p {
            color: var(--text-light);
            font-size: 14px;
        }

        .export-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(5, 150, 105, 0.3);
        }

        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 22px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.5) 100%);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        body.dark-mode .stat-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(6, 78, 59, 0.8) 100%);
            border-color: rgba(5, 150, 105, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(5, 150, 105, 0.15), transparent);
            border-radius: 50%;
            z-index: 0;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: -50px;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(52, 211, 153, 0.1), transparent);
            border-radius: 50%;
            z-index: 0;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 48px rgba(5, 150, 105, 0.2);
            border-color: rgba(5, 150, 105, 0.2);
        }

        .stat-card.success { --stat-color: var(--success); }
        .stat-card.warning { --stat-color: var(--warning); }
        .stat-card.danger { --stat-color: var(--danger); }
        .stat-card.info { --stat-color: var(--info); }

        .stat-icon {
            font-size: 42px;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.1));
        }

        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: var(--stat-color, var(--primary));
            margin-bottom: 6px;
            position: relative;
            z-index: 1;
            letter-spacing: -1px;
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-light);
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            position: relative;
            z-index: 1;
        }

        .stat-trend {
            font-size: 12px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            color: var(--success);
            position: relative;
            z-index: 1;
            font-weight: 600;
        }

        body.dark-mode .stat-trend {
            border-top-color: rgba(255, 255, 255, 0.05);
        }

        /* ===== ACTION CARDS GRID ===== */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 35px;
        }

        .action-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.5) 100%);
            border: 2px solid var(--border-color);
            border-radius: 14px;
            padding: 22px 18px;
            text-align: center;
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        body.dark-mode .action-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(6, 78, 59, 0.8) 100%);
            border-color: rgba(5, 150, 105, 0.1);
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: inherit;
            z-index: 0;
        }

        .action-card:hover {
            border-color: transparent;
            color: white;
            transform: translateY(-6px);
            box-shadow: 0 12px 32px rgba(5, 150, 105, 0.3);
        }

        .action-card:hover::before {
            opacity: 1;
        }

        .action-card:active {
            transform: translateY(-2px);
        }

        .action-icon {
            font-size: 34px;
            display: block;
            position: relative;
            z-index: 1;
        }

        .action-label {
            font-size: 14px;
            position: relative;
            z-index: 1;
        }

        /* ===== DATA SECTION ===== */
        .data-section {
            margin-bottom: 35px;
        }

        .section-header {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--primary);
        }

        .section-header::before {
            content: '';
            width: 4px;
            height: 24px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 2px;
        }

        /* ===== TABLE STYLES ===== */
        .table-wrapper {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.5) 100%);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(5, 150, 105, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.8);
            overflow: hidden;
        }

        body.dark-mode .table-wrapper {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(6, 78, 59, 0.8) 100%);
            border-color: rgba(5, 150, 105, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, rgba(5, 150, 105, 0.1), rgba(52, 211, 153, 0.05));
            border-bottom: 2px solid var(--border-color);
        }

        body.dark-mode thead {
            background: linear-gradient(135deg, rgba(5, 150, 105, 0.15), rgba(52, 211, 153, 0.08));
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
            background: rgba(5, 150, 105, 0.05);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
        }

        .status-ongoing {
            background: rgba(59, 130, 245, 0.1);
            color: #2563eb;
        }

        .status-completed {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        /* ===== INFO CARDS ===== */
        .info-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(255, 255, 255, 0.3) 100%);
            border-left: 4px solid var(--primary);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.1);
        }

        .info-card strong {
            color: var(--text-dark);
            display: block;
            margin-bottom: 8px;
        }

        .info-card p {
            font-size: 13px;
            color: var(--text-light);
            margin: 6px 0;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .main-content {
                padding: 25px;
            }

            .search-box {
                min-width: 250px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main-wrapper {
                margin-left: 0;
            }

            .menu-toggle {
                display: flex;
            }

            .main-content {
                padding: 20px;
            }

            .top-header {
                padding: 15px 20px;
            }

            .welcome-section {
                padding: 20px;
                gap: 15px;
                border-radius: 12px;
                flex-direction: column;
            }

            .welcome-text h2 {
                font-size: 24px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .search-box {
                max-width: 100%;
                min-width: 200px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .actions-grid {
                grid-template-columns: 1fr;
            }

            .header-right {
                gap: 10px;
            }

            .search-box {
                display: none;
            }

            .top-header {
                flex-wrap: wrap;
            }

            .stat-value {
                font-size: 28px;
            }

            .stat-icon {
                font-size: 32px;
            }

            .main-content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <span class="sidebar-logo">👨‍💼</span>
            <h2>SCHoRD</h2>
        </div>
        <ul class="sidebar-nav">
            <li><a class="sidebar-link active" href="staff_dashboard.php"><span class="icon">📊</span> Dashboard</a></li>
            <li><a class="sidebar-link" href="../pages/staff_students.php"><span class="icon">👥</span> Students</a></li>
            <li><a class="sidebar-link" href="../pages/staff_visits.php"><span class="icon">📋</span> Clinic Visits</a></li>
            <li><a class="sidebar-link" href="../pages/staff_health_records.php"><span class="icon">📄</span> Health Records</a></li>
            <li><a class="sidebar-link" href="../pages/staff_reports.php"><span class="icon">📈</span> Reports</a></li>
            <li style="margin-top: 30px; border-top: 1px solid rgba(5, 150, 105, 0.15); padding-top: 20px;">
                <a class="sidebar-link" href="../auth/logout.php"><span class="icon">🚪</span> Logout</a>
            </li>
        </ul>
    </aside>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper" id="mainWrapper">
        <!-- TOP HEADER -->
        <header class="top-header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle">☰</button>
                <h2 class="header-title">Staff Dashboard</h2>
            </div>
            
            <div class="header-right">
                <div class="search-box">
                    <span>🔍</span>
                    <input type="text" placeholder="Search students, records...">
                </div>

                <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">🌙</button>

                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                    <div>
                        <div style="font-weight: 600; font-size: 14px;"><?php echo substr(htmlspecialchars($user['name']), 0, 12); ?></div>
                        <div style="font-size: 12px; color: var(--text-light);">Staff</div>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <!-- WELCOME SECTION -->
            <div class="welcome-section">
                <div class="welcome-text">
                    <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>! 👋</h2>
                    <p>Manage student records and clinic operations</p>
                </div>
                <button class="export-btn" onclick="exportData()" title="Export Report">📥 Export Report</button>
            </div>

            <!-- KEY METRICS -->
            <div class="stats-grid">
                <div class="stat-card success">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value"><?php echo $total_students; ?></div>
                    <div class="stat-label">Total Students</div>
                    <div class="stat-trend">📚 Active records</div>
                </div>

                <div class="stat-card info">
                    <div class="stat-icon">📋</div>
                    <div class="stat-value"><?php echo $total_visits; ?></div>
                    <div class="stat-label">All Clinic Visits</div>
                    <div class="stat-trend">✓ Recorded</div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-icon">📅</div>
                    <div class="stat-value"><?php echo $this_month_visits; ?></div>
                    <div class="stat-label">This Month's Visits</div>
                    <div class="stat-trend">📊 Current period</div>
                </div>

                <div class="stat-card danger">
                    <div class="stat-icon">📄</div>
                    <div class="stat-value"><?php echo $health_overview['total_records']; ?></div>
                    <div class="stat-label">Health Records</div>
                    <div class="stat-trend">📑 Complete</div>
                </div>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="section-header">🚀 Quick Actions</div>
            <div class="actions-grid">
                <a href="../pages/staff_students.php" class="action-card">
                    <span class="action-icon">➕</span>
                    <span class="action-label">Add Student</span>
                </a>
                <a href="../pages/staff_visits.php" class="action-card">
                    <span class="action-icon">📅</span>
                    <span class="action-label">Log Visit</span>
                </a>
                <a href="../pages/staff_health_records.php" class="action-card">
                    <span class="action-icon">📝</span>
                    <span class="action-label">Update Record</span>
                </a>
                <a href="../pages/staff_reports.php" class="action-card">
                    <span class="action-icon">📊</span>
                    <span class="action-label">View Reports</span>
                </a>
            </div>

            <!-- RECENT VISITS -->
            <div class="data-section">
                <div class="section-header">📋 Recent Clinic Visits</div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>📅 Date/Time</th>
                                <th>👤 Student Name</th>
                                <th>🆔 ID</th>
                                <th>📝 Complaint</th>
                                <th>📊 Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($recent_visits && $recent_visits->num_rows > 0):
                                while ($visit = $recent_visits->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><strong><?php echo date('M d, H:i', strtotime($visit['visit_date'])); ?></strong></td>
                                    <td><?php echo htmlspecialchars($visit['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($visit['student_no']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($visit['complaint'] ?? 'N/A', 0, 30)); ?></td>
                                    <td><span class="status-badge status-<?php echo strtolower($visit['status'] ?? 'pending'); ?>"><?php echo ucfirst($visit['status'] ?? 'Pending'); ?></span></td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr><td colspan="5" style="text-align: center; padding: 30px;">No recent visits</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- RECENT STUDENTS -->
            <div class="data-section">
                <div class="section-header">👥 Recent Student Admissions</div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>👤 Student Name</th>
                                <th>🆔 Admission ID</th>
                                <th>📚 Course</th>
                                <th>🎂 Age</th>
                                <th>📅 Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($recent_students && $recent_students->num_rows > 0):
                                while ($student = $recent_students->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($student['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($student['student_no']); ?></td>
                                    <td><?php echo htmlspecialchars($student['course'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($student['age'] ?? '—'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($student['created_at'], 0)); ?></td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr><td colspan="5" style="text-align: center; padding: 30px;">No recent admissions</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- HEALTH OVERVIEW -->
            <div class="data-section">
                <div class="section-header">📊 Health Records Overview</div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 18px;">
                    <div class="info-card">
                        <strong>📄 Total Health Records</strong>
                        <p style="font-size: 18px; font-weight: 800; color: var(--primary); margin-top: 10px;"><?php echo $health_overview['total_records']; ?></strong></p>
                        <p>Complete health documentation on file</p>
                    </div>
                    <div class="info-card">
                        <strong>❤️ Vital Signs Recorded</strong>
                        <p style="font-size: 18px; font-weight: 800; color: var(--primary); margin-top: 10px;"><?php echo $health_overview['records_with_bp']; ?></p>
                        <p>Students with blood pressure readings</p>
                    </div>
                    <div class="info-card">
                        <strong>📋 Data Completeness</strong>
                        <p style="font-size: 18px; font-weight: 800; color: var(--primary); margin-top: 10px;">
                            <?php 
                            $percentage = $health_overview['total_records'] > 0 
                                ? round(($health_overview['records_with_bp'] / $health_overview['total_records']) * 100) 
                                : 0;
                            echo $percentage . '%';
                            ?>
                        </strong></p>
                        <p>Of health records have vital signs data</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set active nav item based on current page
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.sidebar-link');
            
            navLinks.forEach(link => {
                const linkHref = link.getAttribute('href').split('/').pop();
                if (linkHref === currentPage || (currentPage === '' && linkHref === 'staff_dashboard.php')) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        });

        // Menu Toggle
        document.getElementById('menuToggle').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('mainWrapper').classList.toggle('sidebar-collapsed');
        });

        // Theme Toggle
        document.getElementById('themeToggle').addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            this.textContent = isDark ? '☀️' : '🌙';
        });

        // Load theme preference
        window.addEventListener('load', function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.body.classList.add('dark-mode');
                document.getElementById('themeToggle').textContent = '☀️';
            }
        });

        // Export Data Function
        function exportData() {
            const timestamp = new Date().toLocaleString();
            const csvContent = [
                ['SCHoRD Staff Dashboard Report'],
                ['Generated:', timestamp],
                [''],
                ['Metric', 'Value'],
                ['Total Students', '<?php echo $total_students; ?>'],
                ['Total Clinic Visits', '<?php echo $total_visits; ?>'],
                ['This Month\'s Visits', '<?php echo $this_month_visits; ?>'],
                ['Health Records', '<?php echo $health_overview['total_records']; ?>']
            ].map(row => row.join(',')).join('\n');

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.setAttribute('href', URL.createObjectURL(blob));
            link.setAttribute('download', `SCHoRD-Staff-Report-${new Date().getTime()}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            showNotification('📥 Report exported successfully!', 'success');
        }

        // Notification Helper
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #059669, #10b981);
                color: white;
                padding: 16px 24px;
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(5, 150, 105, 0.3);
                z-index: 9999;
                animation: slideInRight 0.3s ease;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Add animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(400px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(400px); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
