<?php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Filters
$module_id = $_GET['module_id'] ?? '';
$assignment_id = $_GET['assignment_id'] ?? '';
$student_id = $_GET['student_id'] ?? '';

$params = [];
$conditions = [];

if (!empty($assignment_id)) {
    $conditions[] = "g.assignment_id = :assignment_id";
    $params['assignment_id'] = $assignment_id;
}

if (!empty($module_id)) {
    $conditions[] = "a.module_id = :module_id";
    $params['module_id'] = $module_id;
}

if (!empty($student_id)) {
    $conditions[] = "s.student_id LIKE :student_id";
    $params['student_id'] = "%$student_id%";
}

$where_sql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Query grades
$sql = "
    SELECT g.*, s.first_name, s.surname,
           a.title AS assignment_title, a.type AS assignment_type, a.due_date,
           m.module_code, m.module_title
    FROM Grades g
    JOIN Students s ON g.student_id = s.student_id
    JOIN Assignments a ON g.assignment_id = a.assignment_id
    LEFT JOIN Modules m ON a.module_id = m.module_id
    $where_sql
    ORDER BY m.module_code, a.title, s.surname
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$grades = $stmt->fetchAll();

// Dropdown data
$modules = $pdo->query("SELECT module_id, module_code, module_title FROM Modules ORDER BY module_code")->fetchAll();

$assignments = [];
if (!empty($module_id)) {
    $assignments_stmt = $pdo->prepare("SELECT assignment_id, title FROM Assignments WHERE module_id = :module_id ORDER BY title");
    $assignments_stmt->execute(['module_id' => $module_id]);
    $assignments = $assignments_stmt->fetchAll();
}
?>

<h2>View Grades</h2>

<!-- Filters -->
<form method="get" action="/index.php" style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
    <input type="hidden" name="page" value="grades">

    <select name="module_id" onchange="this.form.submit()">
        <option value="">All Modules</option>
        <?php foreach ($modules as $mod): ?>
            <option value="<?= $mod['module_id'] ?>" <?= $mod['module_id'] == $module_id ? 'selected' : '' ?>>
                <?= htmlspecialchars($mod['module_code'] . ' - ' . $mod['module_title']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="assignment_id">
        <option value="">All Assignments</option>
        <?php foreach ($assignments as $ass): ?>
            <option value="<?= $ass['assignment_id'] ?>" <?= $ass['assignment_id'] == $assignment_id ? 'selected' : '' ?>>
                <?= htmlspecialchars($ass['title']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input type="text" name="student_id" placeholder="Student ID" value="<?= htmlspecialchars($student_id) ?>">

    <button type="submit" class="button-primary">Filter</button>
</form>

<!-- Grades Table -->
<?php if (!empty($grades)): ?>
    <table>
        <thead>
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Assignment</th>
                <th>Type</th>
                <th>Score</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($grades as $g): ?>
                <tr>
                    <td><?= htmlspecialchars($g['student_id']) ?></td>
                    <td><?= htmlspecialchars($g['first_name'] . ' ' . $g['surname']) ?></td>
                    <td><?= htmlspecialchars($g['assignment_title']) ?></td>
                    <td><?= htmlspecialchars($g['assignment_type']) ?></td>
                    <td><?= htmlspecialchars($g['score']) ?></td>
                    <td><?= htmlspecialchars($g['grade_letter']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No grades found for the selected filters.</p>
<?php endif; ?>
