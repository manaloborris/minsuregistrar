<?php
define('APP_ROOT', __DIR__);
require __DIR__ . '/config.php';
require __DIR__ . '/scheme/Database.php';
require __DIR__ . '/scheme/helpers.php';

$db = db();

foreach (['users', 'students', 'admins'] as $table) {
    echo "TABLE: {$table}\n";
    $rows = $db->raw("SHOW INDEX FROM `{$table}`")->fetchAll();
    foreach ($rows as $row) {
        echo $row['Key_name'] . ' | ' . $row['Column_name'] . ' | Non_unique=' . $row['Non_unique'] . "\n";
    }
    echo "\n";
}

echo "EXPLAIN users login\n";
$explain = $db->raw('EXPLAIN SELECT id, username, password, role FROM users WHERE username = ? AND role = "student" LIMIT 1', ['TEST'])->fetchAll();
foreach ($explain as $row) {
    echo json_encode($row, JSON_UNESCAPED_SLASHES) . "\n";
}

echo "EXPLAIN student login\n";
$explain2 = $db->raw('EXPLAIN SELECT student_id, first_name, last_name, course, year_level, email, contact_number FROM students WHERE student_id = ? LIMIT 1', ['TEST'])->fetchAll();
foreach ($explain2 as $row) {
    echo json_encode($row, JSON_UNESCAPED_SLASHES) . "\n";
}
