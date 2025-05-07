<?php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// filters
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$module_id = $_GET['module_id'] ?? '';
$status = $_GET['status'] ?? '';
$student_id = $_GET['student_id'] ?? '';

$params = [];
$conditions = [];


if (!empty($start_date)) {
    $conditions[] = "t.date >= :start_date";
    $params['start_date'] = $start_date;
}
if (!empty($end_date)) {
    $conditions[] = "t.date <= :end_date";
    $params['end_date'] = $end_date;
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

$where_sql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) . " AND s.record_status = 'Live'" : "WHERE s.record_status = 'Live'";

// base quer
$sql = "
    SELECT a.*, t.date, t.start_time, m.module_code, m.module_title, s.first_name, s.surname, r.room_code
    FROM Attendance a
    JOIN Timetables t ON a.timetable_id = t.timetable_id
    JOIN Modules m ON t.module_id = m.module_id
    JOIN Students s ON a.student_id = s.student_id
    LEFT JOIN Rooms r ON t.room_id = r.room_id
    $where_sql
    ORDER BY t.date DESC, t.start_time DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

// total students
if (empty($module_id) && empty($student_id)) {
    $total_students = $pdo->query("SELECT COUNT(*) FROM Students WHERE record_status = 'Live'")->fetchColumn();
} else {
    $live_student_ids = [];
    foreach ($records as $rec) {
        if (!empty($rec['student_id'])) {
            $live_student_ids[$rec['student_id']] = true;
        }
    }
    $total_students = count($live_student_ids);
}

// summary based on filters
$summary_params = [];
$summary_conditions = ["s.record_status = 'Live'"];

if (!empty($start_date)) {
    $summary_conditions[] = "t.date >= :start_date_summary";
    $summary_params['start_date_summary'] = $start_date;
}
if (!empty($end_date)) {
    $summary_conditions[] = "t.date <= :end_date_summary";
    $summary_params['end_date_summary'] = $end_date;
}
if (!empty($module_id)) {
    $summary_conditions[] = "t.module_id = :module_summary";
    $summary_params['module_summary'] = $module_id;
}

$summary_sql = "
    SELECT a.status, COUNT(*) AS count
    FROM Attendance a
    JOIN Timetables t ON a.timetable_id = t.timetable_id
    JOIN Students s ON a.student_id = s.student_id
    WHERE " . implode(" AND ", $summary_conditions) . "
    GROUP BY a.status
";
$summary_stmt = $pdo->prepare($summary_sql);
$summary_stmt->execute($summary_params);
$summary_counts = $summary_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$total_attendance = array_sum($summary_counts);
$present_count = $summary_counts['Present'] ?? 0;
$summary_percentage = $total_attendance > 0 ? round(($present_count / $total_attendance) * 100, 1) : 0;


if (empty($start_date)) {
    $summary_label = "Present Today (" . date('d-m-Y') . ")";
} elseif (!empty($start_date) && empty($end_date)) {
    $summary_label = "Present on " . date('d-m-Y', strtotime($start_date));
} else {
    $summary_label = "Avg Attendance from " . date('d-m-Y', strtotime($start_date)) . " to " . date('d-m-Y', strtotime($end_date));
}


$modules = $pdo->query("SELECT module_id, module_code, module_title FROM Modules ORDER BY module_code")->fetchAll();
?>

<h2>Attendance Overview</h2>

<!-- summary -->
<div style="display: flex; gap: 2rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
    <div><strong><?= $summary_label ?>:</strong><br><?= $summary_percentage ?>%</div>
    <!-- <div><strong>Total Students:</strong><br><?= $total_students ?></div> -->
</div>

<!-- filter -->
<form method="get" action="/index.php" style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;">
    <input type="hidden" name="page" value="attendance_admin">

    <label>
        Start Date:
        <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
    </label>

    <label>
        End Date:
        <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
    </label>

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

<!-- table -->
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
                    <td><?= htmlspecialchars(date('d-m-Y', strtotime($rec['date']))) ?></td>
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
