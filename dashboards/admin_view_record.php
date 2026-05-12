<?php
header('Content-Type: text/html; charset=utf-8');
include '../config/db.php';
requireLogin();

$user = getCurrentUser();

// Admin only
if (strtolower($user['role']) !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: admin_records.php");
    exit();
}

$id = sanitize($_GET['id']);

$result = $conn->query("SELECT hr.*, s.name as student_name, s.student_no, s.course, s.age FROM health_records hr JOIN students s ON hr.student_id = s.id WHERE hr.id='$id' LIMIT 1");

if (!$result || $result->num_rows === 0) {
    $error = 'Record not found';
    $record = null;
} else {
    $record = $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Health Record - Admin</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f7fafc; color: #0f172a; }
        .container { max-width: 900px; margin: 30px auto; padding: 20px; }
        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 10px 30px rgba(2,6,23,0.06); }
        .card h2 { margin-bottom: 10px; }
        .row { display: flex; gap: 20px; flex-wrap: wrap; }
        .col { flex: 1 1 250px; min-width: 220px; }
        .label { color: #64748b; font-size: 13px; margin-bottom: 6px; }
        .value { font-weight: 700; font-size: 15px; margin-bottom: 12px; }
        .btn { padding: 10px 16px; border-radius: 8px; border: none; cursor: pointer; }
        .btn-secondary { background: #f1f5f9; color: #0f172a; border: 1px solid #e2e8f0; }
        .meta { margin-top: 18px; color: #64748b; font-size: 13px; }
        pre.notes { background: #f8fafc; padding: 12px; border-radius: 8px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="container">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h1>Health Record Details</h1>
            <div>
                <a href="admin_records.php" class="btn btn-secondary">← Back</a>
            </div>
        </div>

        <div class="card">
            <?php if (isset($error)): ?>
                <div style="padding:12px;border-radius:8px;background:#fee2e2;color:#991b1b;margin-bottom:12px;"><?php echo htmlspecialchars($error); ?></div>
            <?php else: ?>
                <h2><?php echo htmlspecialchars($record['student_name']); ?> <span style="font-weight:400;color:#94a3b8;">(<?php echo htmlspecialchars($record['student_no']); ?>)</span></h2>
                <div class="meta">Recorded: <?php echo isset($record['created_at']) ? date('M d, Y h:ia', strtotime($record['created_at'])) : 'N/A'; ?></div>

                <div class="row" style="margin-top:18px;">
                    <div class="col">
                        <div class="label">Course / Age</div>
                        <div class="value"><?php echo htmlspecialchars($record['course'] ?? 'N/A'); ?> / <?php echo htmlspecialchars($record['age'] ?? 'N/A'); ?></div>

                        <div class="label">Blood Type</div>
                        <div class="value"><?php echo htmlspecialchars($record['blood_type'] ?? 'N/A'); ?></div>

                        <div class="label">Allergies</div>
                        <div class="value"><?php echo htmlspecialchars($record['allergies'] ?? 'None'); ?></div>
                    </div>

                    <div class="col">
                        <div class="label">Chronic Conditions</div>
                        <div class="value"><?php echo htmlspecialchars($record['chronic_conditions'] ?? 'None'); ?></div>

                        <div class="label">Medications</div>
                        <div class="value"><?php echo htmlspecialchars($record['medications'] ?? 'None'); ?></div>

                        <div class="label">Other Notes</div>
                        <pre class="notes"><?php echo htmlspecialchars($record['notes'] ?? ''); ?></pre>
                    </div>
                </div>

                <div style="margin-top:18px;display:flex;gap:8px;">
                    <a href="admin_records.php?delete=<?php echo $record['id']; ?>" onclick="return confirm('Delete this record?')" class="btn btn-secondary">Delete</a>
                    <a href="admin_records.php" class="btn" style="background:#6366f1;color:white;border-radius:8px;">Close</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
