<?php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//filters
$search = $_GET['search'] ?? '';
$tutor = $_GET['tutor'] ?? '';
$course = $_GET['course'] ?? '';
$unassigned_only = isset($_GET['unassigned']) ? true : false;

$default_columns = ['student_id', 'student_name', 'tutor_id', 'tutor_name'];

if (isset($_GET['columns'])) {
    $_SESSION['tutors_selected_columns'] = $_GET['columns'];
}
$selected_columns = $_SESSION['tutors_selected_columns'] ?? $default_columns;

$selected_columns = array_values(array_filter($selected_columns, function($col) use ($default_columns) {
    return in_array($col, $default_columns);
}));

$available_columns = [
    'student_id' => 'Student ID',
    'student_name' => 'Student Name',
    'tutor_id' => 'Tutor ID',
    'tutor_name' => 'Tutor Name'
];

// dropdown values
$tutors = $pdo->query("SELECT staff_id, first_name, surname FROM Staff WHERE roles LIKE '%Personal tutor%' AND record_status = 'Live'")->fetchAll();
$courses = $pdo->query("SELECT course_code, course_name FROM Courses")->fetchAll();

//base query
$sql = "SELECT pt.student_id, 
               s.first_name AS student_first, s.surname AS student_last, s.course_code,
               st.staff_id AS tutor_id, st.first_name AS tutor_first, st.surname AS tutor_last
        FROM PersonalTutors pt
        JOIN Students s ON pt.student_id = s.student_id
        LEFT JOIN Staff st ON pt.tutor_id = st.staff_id
        WHERE 1=1";

$params = [];
$conditions = [];

if (!empty($search)) {
    $keywords = preg_split('/\s+/', trim($search));
    $i = 0;
    foreach ($keywords as $word) {
        $conditions[] = "(pt.student_id LIKE :student_id_$i OR pt.tutor_id LIKE :tutor_id_$i)";
        $params["student_id_$i"] = "%$word%";
        $params["tutor_id_$i"] = "%$word%";
        $i++;
    }
}

if (!empty($tutor)) {
    $conditions[] = "pt.tutor_id = :tutor";
    $params['tutor'] = $tutor;
}

if (!empty($course)) {
    $conditions[] = "s.course_code = :course";
    $params['course'] = $course;
}

if ($unassigned_only) {
    $conditions[] = "pt.tutor_id IS NULL";
}

if (!empty($conditions)) {
    $sql .= " AND " . implode(' AND ', $conditions);
}

// main query
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tutor_assignments = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<pre>SQL Error: " . $e->getMessage() . "</pre>";
    exit;
}

// Tutor load summary
$tutor_loads = $pdo->query("
    SELECT st.staff_id, st.first_name, st.surname, COUNT(pt.student_id) AS tutee_count
    FROM PersonalTutors pt
    JOIN Staff st ON pt.tutor_id = st.staff_id
    GROUP BY st.staff_id
    ORDER BY tutee_count DESC
")->fetchAll();
?>

<h2>Search Personal Tutors</h2>

<!-- filters -->
<form method="get" action="/index.php" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center;">
    <input type="hidden" name="page" value="personal_tutors_search">

    <input type="text" name="search" placeholder="Search by Student ID or Tutor ID" value="<?= htmlspecialchars($search) ?>" style="flex: 1 0 250px; min-width: 250px;">

    <select name="tutor" class="select2">
        <option value="">All Tutors</option>
        <?php foreach ($tutors as $t): ?>
            <option value="<?= $t['staff_id'] ?>" <?= $tutor === $t['staff_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="course" class="select2">
        <option value="">All Courses</option>
        <?php foreach ($courses as $c): ?>
            <option value="<?= $c['course_code'] ?>" <?= $course === $c['course_code'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label style="margin-left: 1rem;">
        <input type="checkbox" name="unassigned" <?= $unassigned_only ? 'checked' : '' ?>>
        Show Unassigned Only
    </label>

    <button type="submit" class="button-primary">Search</button>
</form>

<!-- tutor Load Summary -->
<details style="margin-top: 1rem;">
    <summary><strong>Tutor Load Summary (Click to Expand)</strong></summary>
    <ul style="margin-top: 0.5rem;">
        <?php foreach ($tutor_loads as $load): ?>
            <li>
                <?= htmlspecialchars($load['first_name'] . ' ' . $load['surname']) ?> 
                (<?= $load['staff_id'] ?>): 
                <?= $load['tutee_count'] ?> tutees
            </li>
        <?php endforeach; ?>
    </ul>
</details>

<!-- customize Columns -->
<div class="columns-dropdown-container" style="margin-top: 1rem;">
    <button type="button" onclick="toggleColumnsDropdown()">Customize Columns ⬇</button>

    <div id="columnsDropdown" class="columns-dropdown" style="display:none;">
        <form method="get" action="/index.php">
            <input type="hidden" name="page" value="personal_tutors_search">

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

<!-- results Table -->
<?php if (!empty($tutor_assignments)): ?>
    <table class="search-table">
        <thead>
            <tr>
                <?php foreach ($selected_columns as $col): ?>
                    <th><?= htmlspecialchars($available_columns[$col] ?? ucfirst($col)) ?></th>
                <?php endforeach; ?>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tutor_assignments as $pt): ?>
                <tr>
                    <?php foreach ($selected_columns as $col): ?>
                        <td>
                            <?php
                            switch ($col) {
                                case 'student_id':
                                    echo htmlspecialchars($pt['student_id']);
                                    break;
                                case 'student_name':
                                    echo htmlspecialchars($pt['student_first'] . ' ' . $pt['student_last']);
                                    break;
                                case 'tutor_id':
                                    echo htmlspecialchars($pt['tutor_id'] ?? 'Unassigned');
                                    break;
                                case 'tutor_name':
                                    echo isset($pt['tutor_first']) ? htmlspecialchars($pt['tutor_first'] . ' ' . $pt['tutor_last']) : 'Unassigned';
                                    break;
                            }
                            ?>
                        </td>
                    <?php endforeach; ?>
                    <td>
                        <a href="/index.php?page=students_profile&id=<?= urlencode($pt['student_id']) ?>">View Student</a>
                        <?php if ($pt['tutor_id']): ?>
                            | <a href="/index.php?page=staff_profile&id=<?= urlencode($pt['tutor_id']) ?>">View Tutor</a>
                        <?php endif; ?>
                        | <a href="/index.php?page=tutor_meetings&student_id=<?= urlencode($pt['student_id']) ?>">View Meetings</a>
                    </td>

                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No tutor assignments found.</p>
<?php endif; ?>

<script>
function toggleColumnsDropdown() {
    const dropdown = document.getElementById('columnsDropdown');
    dropdown.style.display = (dropdown.style.display === 'none' || dropdown.style.display === '') ? 'block' : 'none';
}
</script>
