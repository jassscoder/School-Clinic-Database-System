<?php
include '../config/db.php';
requireLogin();

$user = getCurrentUser();

// NURSE ONLY
if (strtolower($user['role']) !== 'nurse') {
    header("Location: ../dashboards/dashboard.php");
    exit();
}

$success = '';
$error = '';
$edit_record = null;
$selected_student = null;

if (isset($_GET['student'])) {
    $student_id = sanitize($_GET['student']);
    $result = $conn->query("SELECT * FROM students WHERE id='$student_id'");
    if ($result->num_rows > 0) {
        $selected_student = $result->fetch_assoc();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = sanitize($_POST['student_id']);
    $allergies = sanitize($_POST['allergies']);
    $conditions = sanitize($_POST['conditions']);
    $height = sanitize($_POST['height'] ?? '');
    $weight = sanitize($_POST['weight'] ?? '');
    $blood_type = sanitize($_POST['blood_type'] ?? '');
    $blood_pressure = sanitize($_POST['blood_pressure'] ?? '');
    $temperature = sanitize($_POST['temperature'] ?? '');

    if (empty($student_id)) {
        $error = '❌ Student is required';
    } else {
        $check = $conn->query("SELECT id FROM health_records WHERE student_id='$student_id'");
        if ($check->num_rows > 0) {
            if ($conn->query("UPDATE health_records SET allergies='$allergies', conditions='$conditions', height='$height', weight='$weight', blood_type='$blood_type', blood_pressure='$blood_pressure', temperature='$temperature' WHERE student_id='$student_id'")) {
                $success = '✅ Health record updated successfully!';
            } else {
                $error = '❌ Error updating record';
            }
        } else {
            if ($conn->query("INSERT INTO health_records (student_id, allergies, conditions, height, weight, blood_type, blood_pressure, temperature) VALUES ('$student_id', '$allergies', '$conditions', '$height', '$weight', '$blood_type', '$blood_pressure', '$temperature')")) {
                $success = '✅ Health record created successfully!';
            } else {
                $error = '❌ Error creating record';
            }
        }
    }
}

$records = $conn->query("
    SELECT hr.*, s.name, s.student_no 
    FROM health_records hr 
    RIGHT JOIN students s ON hr.student_id = s.id 
    ORDER BY s.name ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Records - Nurse - SCHoRD</title>
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
            --danger: #ef4444;
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
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
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
            box-shadow: 0 6px 20px rgba(8, 145, 178, 0.3);
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
            <li><a href="nurse_health_records.php" class="active"><span>❤️</span> Health Records</a></li>
            <li><a href="nurse_reports.php"><span>📈</span> Reports</a></li>
            <li style="margin-top: 20px; border-top: 2px solid rgba(255,255,255,0.1); padding-top: 20px;">
                <a href="../auth/logout.php"><span>🚪</span> Logout</a>
            </li>
        </ul>
    </aside>

    <div class="main-wrapper">
        <header class="top-header">
            <h2 style="color: var(--text-dark);">❤️ Health Records</h2>
        </header>

        <div class="main-content">
            <div class="page-header">
                <h1>Health Records Management</h1>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="form-card">
                <h2>Add/Update Health Record</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Student *</label>
                        <select name="student_id" required>
                            <option value="">Select Student</option>
                            <?php
                            $students = $conn->query("SELECT id, name, student_no FROM students ORDER BY name");
                            while ($s = $students->fetch_assoc()): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo ($selected_student && $selected_student['id'] == $s['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['name']) . ' (' . $s['student_no'] . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Blood Type</label>
                            <input type="text" name="blood_type" placeholder="e.g., O+">
                        </div>
                        <div class="form-group">
                            <label>Height (cm)</label>
                            <input type="text" name="height" placeholder="e.g., 170">
                        </div>
                        <div class="form-group">
                            <label>Weight (kg)</label>
                            <input type="text" name="weight" placeholder="e.g., 65">
                        </div>
                        <div class="form-group">
                            <label>Blood Pressure</label>
                            <input type="text" name="blood_pressure" placeholder="e.g., 120/80">
                        </div>
                        <div class="form-group">
                            <label>Temperature (°C)</label>
                            <input type="text" name="temperature" placeholder="e.g., 37.5">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Allergies</label>
                        <textarea name="allergies" placeholder="List any allergies..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Medical Conditions</label>
                        <textarea name="conditions" placeholder="List any medical conditions..."></textarea>
                    </div>

                    <button type="submit" class="btn">✅ Save Health Record</button>
                </form>
            </div>

            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Blood Type</th>
                            <th>Allergies</th>
                            <th>Conditions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($record = $records->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['name']); ?></td>
                                <td><?php echo $record['blood_type'] ?? '-'; ?></td>
                                <td><?php echo $record['allergies'] ? htmlspecialchars(substr($record['allergies'], 0, 30)) . '...' : '-'; ?></td>
                                <td><?php echo $record['conditions'] ? htmlspecialchars(substr($record['conditions'], 0, 30)) . '...' : '-'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
