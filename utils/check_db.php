<?php
// adjust path if needed
require '../config/db.php';

// db.php already created $conn
if (!isset($conn) || $conn->connect_error) {
    die(isset($conn) ? "DB connect failed: " . $conn->connect_error : "DB connection not created");
}

echo "Connected to schord_db successfully.<br>";

$result = $conn->query("SHOW TABLES");
if (!$result) {
    die("Query failed: " . $conn->error);
}

echo "Tables found: " . $result->num_rows . "<br>";
while ($row = $result->fetch_array()) {
    echo "- " . $row[0] . "<br>";
}

$queries = [
    "SELECT COUNT(*) AS cnt FROM users",
    "SELECT COUNT(*) AS cnt FROM students",
    "SELECT COUNT(*) AS cnt FROM health_records",
    "SELECT COUNT(*) AS cnt FROM clinic_visits",
];

foreach ($queries as $sql) {
    $r = $conn->query($sql);
    if ($r) {
        $c = $r->fetch_assoc();
        echo "$sql = " . $c['cnt'] . "<br>";
    } else {
        echo "Query failed: " . $conn->error . "<br>";
    }
}
?>