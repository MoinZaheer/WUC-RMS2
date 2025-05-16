<?php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['toast'])): ?>
    <div class="toast">
      <?= htmlspecialchars($_SESSION['toast']) ?>
    </div>
    <?php unset($_SESSION['toast']);
endif;
  

// Filters
$module_id = $_GET['module_id'] ?? '';
$type = $_GET['type'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$params = [];
$conditions = [];

if (!empty($module_id)) {
    $conditions[] = "a.module_id = :module_id";
    $params['module_id'] = $module_id;
}
if (!empty($type)) {
    $conditions[] = "a.type = :type";
    $params['type'] = $type;
}
if (!empty($start_date)) {
    $conditions[] = "a.due_date >= :start_date";
    $params['start_date'] = $start_date;
}
if (!empty($end_date)) {
    $conditions[] = "a.due_date <= :end_date";
    $params['end_date'] = $end_date;
}

$where_sql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Query assignments
$sql = "
    SELECT a.assignment_id, a.title, a.type, a.weight, a.due_date,
           m.module_code, m.module_title
    FROM Assignments a
    LEFT JOIN Modules m ON a.module_id = m.module_id
    $where_sql
    ORDER BY a.due_date DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assignments = $stmt->fetchAll();

// Get modules for dropdown
$modules = $pdo->query("SELECT module_id, module_code, module_title FROM Modules ORDER BY module_code")->fetchAll();
?>

<h2>Search Assignments</h2>

<!-- Filters -->
<form method="get" action="/index.php" style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
    <input type="hidden" name="page" value="assignments_search">

    <select name="module_id" class="select2">
        <option value="">All Modules</option>
        <?php foreach ($modules as $mod): ?>
            <option value="<?= $mod['module_id'] ?>" <?= $mod['module_id'] == $module_id ? 'selected' : '' ?>>
                <?= htmlspecialchars($mod['module_code'] . ' - ' . $mod['module_title']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="type" class="select2">
        <option value="">All Types</option>
        <?php foreach (['Ass1', 'Ass2', 'Exam'] as $t): ?>
            <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
    </select>

    <label>
        Start Date:
        <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
    </label>

    <label>
        End Date:
        <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
    </label>

    <button type="submit" class="button-primary">Search</button>
</form>

<!-- table -->
<p>
  <a href="/index.php?page=assignments_create" class="button-primary">Add New Assessment</a>
</p>

<?php if (!empty($assignments)): ?>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Type</th>
                <th>Weight (%)</th>
                <th>Due Date</th>
                <th>Module</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assignments as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['title']) ?></td>
                    <td><?= htmlspecialchars($a['type']) ?></td>
                    <td><?= htmlspecialchars($a['weight']) ?></td>
                    <td>
                    <?= $a['due_date'] ? htmlspecialchars(date('d-m-Y', strtotime($a['due_date']))) : '—' ?>
                    </td>

                    <td><?= htmlspecialchars($a['module_code'] . ' - ' . $a['module_title']) ?></td>
                    <td>
                        <a href="/index.php?page=grades&assignment_id=<?= urlencode($a['assignment_id']) ?>">View Grades</a> |
                        <a href="/index.php?page=assignments_edit&id=<?= urlencode($a['assignment_id']) ?>">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No assignments found for the selected filters.</p>
<?php endif; ?>
