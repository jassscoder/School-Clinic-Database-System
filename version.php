<?php
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'app' => 'SCHORD',
    'commit' => 'dde198c',
    'deployed_marker' => 'staff-bom-fix',
    'generated_at_utc' => gmdate('c')
], JSON_UNESCAPED_SLASHES);
