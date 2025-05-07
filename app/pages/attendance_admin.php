<?php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// filters
$date = $_GET['date'] ?? date('Y-m-d');
$module_id = $_GET['module_id'] ?? '';
$status = $_GET['status'] ?? '';
$student_id = $_GET['student_id'] ?? '';

$params = [];
$conditions = [];

// Apply filters
if (!empty($date)) {
    $conditions[] = "t.date = :date";
    $params['date'] = $date;
}

if (!empty($module_id)) {
    $conditions[] = "t.module_id = :module_id";
    $params['module_id'] = $module_id;
}

if (!empty($status)) {
    $conditions[] = "a.status = :status";
    $params['status'] = $status;
}

if (!empty($student_id)) {
    $conditions[] = "s.student_id LIKE :student_id";
    $params['student_id'] = "%$student_id%";
}

$where_sql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// fetch attendance records
$sql = "
    SELECT a.*, t.date, t.start_time, m.module_code, m.module_title, s.first_name, s.surname, r.room_code
    FROM Attendance a
    JOIN Timetables t ON a.timetable_id = t.timetable_id
    JOIN Modules m ON t.module_id = m.module_id
    JOIN Students s ON a.student_id = s.student_id
    LEFT JOIN Rooms r ON t.room_id = r.room_id
    " . ($where_sql ? $where_sql . " AND" : "WHERE") . " s.record_status = 'Live'
    ORDER BY t.date DESC, t.start_time DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

// total students(filter )
if (empty($module_id) && empty($student_id)) {
    $total_students_stmt = $pdo->query("SELECT COUNT(*) FROM Students WHERE record_status = 'Live'");
    $total_students = $total_students_stmt->fetchColumn();
} else {
    // filter only Live students
    $live_student_ids = [];

    foreach ($records as $rec) {
        $sid = $rec['student_id'];
        $name = trim($rec['first_name'] . $rec['surname']);

        // double check
        if (!empty($sid)) {
            $live_student_ids[$sid] = true;
        }
    }

    $total_students = count($live_student_ids);
}


// attendance today %
$today_params = [];
$today_filter = "t.date = :today";
$today_params['today'] = $date;

if (!empty($module_id)) {
    $today_filter .= " AND t.module_id = :module_today";
    $today_params['module_today'] = $module_id;
}

$today_query = "
    SELECT status, COUNT(*) as count
    FROM Attendance a
    JOIN Timetables t ON a.timetable_id = t.timetable_id
    WHERE $today_filter
    GROUP BY status
";
$today_stmt = $pdo->prepare($today_query);
$today_stmt->execute($today_params);
$today_counts = $today_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$total_today = array_sum($today_counts);
$present_today = $today_counts['Present'] ?? 0;
$percentage_today = $total_today > 0 ? round(($present_today / $total_today) * 100, 1) : 0;

// monthly avg %
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');

$month_query = "
    SELECT status, COUNT(*) as count
    FROM Attendance a
    JOIN Timetables t ON a.timetable_id = t.timetable_id
    WHERE t.date BETWEEN :start AND :end"
    . (!empty($module_id) ? " AND t.module_id = :module_month" : "");

$month_stmt = $pdo->prepare($month_query);
$month_stmt->execute(array_merge([
    'start' => $month_start,
    'end' => $month_end
], !empty($module_id) ? ['module_month' => $module_id] : []));

$month_counts = $month_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$month_total = array_sum($month_counts);
$month_present = $month_counts['Present'] ?? 0;
$percentage_month = $month_total > 0 ? round(($month_present / $month_total) * 100, 1) : 0;

// modules
$modules = $pdo->query("SELECT module_id, module_code, module_title FROM Modules ORDER BY module_code")->fetchAll();
?>

<h2>Attendance Overview (Admin)</h2>

<!-- summary -->
<div style="display: flex; gap: 2rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
    <div><strong>% Present Today (<?= htmlspecialchars($date) ?>):</strong><br><?= $percentage_today ?>%</div>
    <div><strong>Avg Attendance This Month:</strong><br><?= $percentage_month ?>%</div>
    <div><strong>Total Students:</strong><br><?= $total_students ?></div>
</div>

<!-- filters-->
<form method="get" action="/index.php" style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;">
    <input type="hidden" name="page" value="attendance_admin">

    <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">

    <select name="module_id">
        <option value="">All Modules</option>
        <?php foreach ($modules as $mod): ?>
            <option value="<?= $mod['module_id'] ?>" <?= $mod['module_id'] == $module_id ? 'selected' : '' ?>>
                <?= htmlspecialchars($mod['module_code'] . ' - ' . $mod['module_title']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="status">
        <option value="">All Statuses</option>
        <?php foreach (['Present', 'Absent', 'Late', 'Excused'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
    </select>

    <input type="text" name="student_id" placeholder="Student ID" value="<?= htmlspecialchars($student_id) ?>">

    <button type="submit" class="button-primary">Filter</button>
</form>

<!-- table-->
<?php if (!empty($records)): ?>
    <table>
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Module</th>
                <th>Date</th>
                <th>Start Time</th>
                <th>Room</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $rec): ?>
                <tr>
                    <td><?= htmlspecialchars($rec['student_id']) ?></td>
                    <td><?= htmlspecialchars($rec['first_name'] . ' ' . $rec['surname']) ?></td>
                    <td><?= htmlspecialchars($rec['module_code']) ?></td>
                    <td><?= htmlspecialchars($rec['date']) ?></td>
                    <td><?= htmlspecialchars(substr($rec['start_time'], 0, 5)) ?></td>
                    <td><?= htmlspecialchars($rec['room_code'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($rec['status']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No attendance records found for the selected filters.</p>
<?php endif; ?>
