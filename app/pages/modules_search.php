<?php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle filters
$search = $_GET['search'] ?? '';
$level = $_GET['level'] ?? '';
$course = $_GET['course'] ?? '';

// Reset columns if broken
$default_columns = ['module_code', 'module_title', 'level', 'credits', 'course_code', 'module_leader'];

if (isset($_GET['columns'])) {
    $_SESSION['modules_selected_columns'] = $_GET['columns'];
}
$selected_columns = $_SESSION['modules_selected_columns'] ?? $default_columns;

// Make sure Record Status is removed
$selected_columns = array_values(array_filter($selected_columns, function($col) use ($default_columns) {
    return in_array($col, $default_columns);
}));

// Available columns
$available_columns = [
    'module_code' => 'Module Code',
    'module_title' => 'Title',
    'level' => 'Level',
    'credits' => 'Credits',
    'course_code' => 'Course',
    'module_leader' => 'Module Leader'
];

// Base SQL
$sql = "SELECT m.*, 
               c.course_name,
               CONCAT(s.first_name, ' ', s.surname) AS module_leader
        FROM Modules m
        LEFT JOIN Courses c ON m.course_code = c.course_code
        LEFT JOIN Staff s ON m.module_leader_id = s.staff_id
        WHERE 1=1";

$params = [];
$conditions = [];

if (!empty($search)) {
    $keywords = preg_split('/\s+/', trim($search));
    $i = 0;
    foreach ($keywords as $word) {
        $conditions[] = "(m.module_code LIKE :search_code_$i OR m.module_title LIKE :search_title_$i)";
        $params["search_code_$i"] = "%$word%";
        $params["search_title_$i"] = "%$word%";
        $i++;
    }
}

if (!empty($level)) {
    $conditions[] = "m.level = :level";
    $params['level'] = $level;
}

if (!empty($course)) {
    $conditions[] = "m.course_code = :course";
    $params['course'] = $course;
}

if (!empty($conditions)) {
    $sql .= " AND " . implode(' AND ', $conditions);
}

// Fetch Modules
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $modules = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<pre>SQL Error: " . $e->getMessage() . "</pre>";
    exit;
}

// Fetch dropdown courses
$courses = $pdo->query("SELECT course_code, course_name FROM Courses")->fetchAll();
?>

<h2>Search Modules</h2>

<form method="get" action="/index.php">
    <input type="hidden" name="page" value="modules_search">

    <input type="text" name="search" placeholder="Search by Code or Title" value="<?= htmlspecialchars($search) ?>">

    <select name="level" class="select2">
        <option value="">All Levels</option>
        <?php for ($lvl = 3; $lvl <= 7; $lvl++): ?>
            <option value="<?= $lvl ?>" <?= $level == $lvl ? 'selected' : '' ?>>Level <?= $lvl ?></option>
        <?php endfor; ?>
    </select>

    <select name="course" class="select2">
        <option value="">All Courses</option>
        <?php foreach ($courses as $c): ?>
            <option value="<?= $c['course_code'] ?>" <?= $course === $c['course_code'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['course_code']) ?> - <?= htmlspecialchars($c['course_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Search</button>

    <!-- Customize Columns -->
    <div class="columns-dropdown-container ">
        <button type="button" onclick="toggleColumnsDropdown()">Customize Columns ⬇</button>

        <div id="columnsDropdown" class="columns-dropdown" style="display:none;">
            <form method="get" action="/index.php">
                <input type="hidden" name="page" value="modules_search">

                <?php foreach ($available_columns as $col => $label): ?>
                    <div>
                        <label>
                            <input type="checkbox" name="columns[]" value="<?= $col ?>" <?= in_array($col, $selected_columns) ? 'checked' : '' ?>>
                            <?= $label ?>
                        </label>
                    </div>
                <?php endforeach; ?>

                <button type="submit" style="margin-top: 0.5rem;">Apply</button>
            </form>
        </div>
    </div>
</form>

<?php if (!empty($modules)): ?>
<table>
    <thead>
        <tr>
            <?php foreach ($selected_columns as $col): ?>
                <th><?= htmlspecialchars($available_columns[$col] ?? ucfirst($col)) ?></th>
            <?php endforeach; ?>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($modules as $m): ?>
            <tr>
                <?php foreach ($selected_columns as $col): ?>
                    <td>
                        <?php
                        switch ($col) {
                            case 'module_code': echo htmlspecialchars($m['module_code']); break;
                            case 'module_title': echo htmlspecialchars($m['module_title']); break;
                            case 'level': echo htmlspecialchars($m['level']); break;
                            case 'credits': echo htmlspecialchars($m['credits']); break;
                            case 'course_code':
                                echo htmlspecialchars(($m['course_code'] ?? '') . ' - ' . ($m['course_name'] ?? ''));
                                break;
                            case 'module_leader': echo htmlspecialchars($m['module_leader'] ?? '—'); break;
                        }
                        ?>
                    </td>
                <?php endforeach; ?>
                <td><a href="/index.php?page=module_profile&id=<?= urlencode($m['module_id']) ?>">View / Edit</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
  <p>No modules found.</p>
<?php endif; ?>

<script>
function toggleColumnsDropdown() {
    const dropdown = document.getElementById('columnsDropdown');
    dropdown.style.display = (dropdown.style.display === 'none' || dropdown.style.display === '') ? 'block' : 'none';
}
</script>
