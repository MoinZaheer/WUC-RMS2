<?php
require_once __DIR__ . '/../includes/db.php';

$conditions = [];
$params = [];

if (!empty($_GET['module_id'])) {
    $conditions[] = "t.module_id = :module_id";
    $params['module_id'] = $_GET['module_id'];
}
if (!empty($_GET['staff_id'])) {
    $conditions[] = "t.staff_id = :staff_id";
    $params['staff_id'] = $_GET['staff_id'];
}
if (!empty($_GET['group'])) {
    $conditions[] = "t.group_number = :group";
    $params['group'] = $_GET['group'];
}
if (!empty($_GET['type'])) {
    $conditions[] = "t.type = :type";
    $params['type'] = $_GET['type'];
}

$where_sql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$sql = "
    SELECT t.timetable_id, t.date, t.start_time, t.end_time, t.type,
           m.module_code
    FROM Timetables t
    LEFT JOIN Modules m ON t.module_id = m.module_id
    $where_sql
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sessions = $stmt->fetchAll();

$typeColors = [
    'Lecture' => '#3788d8',   // blue
    'Practical' => '#f39c12', // orange
    'Exam' => '#e74c3c',      // red
    'Event' => '#27ae60'      // green
];

$events = [];
foreach ($sessions as $s) {
    if (!$s['date'] || !$s['start_time'] || !$s['end_time']) continue;

    $events[] = [
        'id' => $s['timetable_id'],
        'title' => $s['type'] . ': ' . ($s['module_code'] ?? 'Unknown'),
        'start' => $s['date'] . 'T' . $s['start_time'],
        'end' => $s['date'] . 'T' . $s['end_time'],
        'color' => $typeColors[$s['type']] ?? '#7f8c8d'
    ];
}

header('Content-Type: application/json');
echo json_encode($events);
exit;
