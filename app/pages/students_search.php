<?php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// filters
$search = $_GET['search'] ?? '';
$course = $_GET['course'] ?? '';
$tutor = $_GET['tutor'] ?? '';
$status = $_GET['status'] ?? '';

// columns to display (session stored or default ones)
if (isset($_GET['columns'])) {
    $selected_columns = $_GET['columns'];
    $_SESSION['students_selected_columns'] = $selected_columns; // save to session
} elseif (isset($_SESSION['students_selected_columns'])) {
    $selected_columns = $_SESSION['students_selected_columns'];
} else {
    $selected_columns = ['student_id', 'name', 'email', 'phone', 'record_status', 'course_code', 'tutor_name']; // default
}

// Available columns
$available_columns = [
    'student_id' => 'Student ID',
    'name' => 'Name',
    'email' => 'Email',
    'phone' => 'Phone',
    'record_status' => 'Record Status',
    'course_code' => 'Course Code',
    'tutor_name' => 'Personal Tutor',
    'term_address' => 'Term Address',
    'non_term_address' => 'Non-Term Address'
];

// Base query
$sql = "SELECT s.student_id, s.first_name, s.middle_name, s.surname, s.email, s.phone,
               s.term_street, s.term_town, s.term_city, s.term_postcode,
               s.non_term_street, s.non_term_town, s.non_term_city, s.non_term_postcode,
               s.record_status, s.course_code,
               CONCAT(st.first_name, ' ', st.surname) AS tutor_name
        FROM Students s
        LEFT JOIN PersonalTutors pt ON s.student_id = pt.student_id
        LEFT JOIN Staff st ON pt.tutor_id = st.staff_id
        WHERE 1=1";

$params = [];
$conditions = [];

if (!empty($search)) {
    $keywords = preg_split('/\s+/', trim($search));
    $keyword_conditions = [];
    $i = 0;
    foreach ($keywords as $word) {
        $keyword_conditions[] = "(
            s.student_id LIKE :keyword_id_$i OR
            s.first_name LIKE :keyword_first_$i OR
            s.middle_name LIKE :keyword_middle_$i OR
            s.surname LIKE :keyword_last_$i
        )";
        $params["keyword_id_$i"] = "%$word%";
        $params["keyword_first_$i"] = "%$word%";
        $params["keyword_middle_$i"] = "%$word%";
        $params["keyword_last_$i"] = "%$word%";
        $i++;
    }
    $conditions[] = '(' . implode(' AND ', $keyword_conditions) . ')';
}

if (!empty($course)) {
    $conditions[] = "s.course_code = :course";
    $params['course'] = $course;
}
if (!empty($tutor)) {
    $conditions[] = "pt.tutor_id = :tutor";
    $params['tutor'] = $tutor;
}
if (!empty($status)) {
    $conditions[] = "s.record_status = :status";
    $params['status'] = $status;
}

if (!empty($conditions)) {
    $sql .= " AND " . implode(' AND ', $conditions);
}

// Execute
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<pre>SQL Error: " . $e->getMessage() . "</pre>";
    exit;
}

// Dropdown options
$courses = $pdo->query("SELECT course_code FROM Courses")->fetchAll();
$tutors = $pdo->query("SELECT staff_id, first_name, surname FROM Staff")->fetchAll();
?>

<h2>Search Students</h2>

<?php if (isset($_GET['message'])): ?>
  <div class="toast">
    <?= htmlspecialchars($_GET['message']) ?>
  </div>
<?php endif; ?>

<form method="get" action="/index.php" class="search-form" >
  <input type="hidden" name="page" value="students_search">

  <input type="text" name="search" placeholder="Search by name or ID" value="<?= htmlspecialchars($search) ?>">

  <select name="course">
    <option value="">All Courses</option>
    <?php foreach ($courses as $c): ?>
      <option value="<?= $c['course_code'] ?>" <?= $course === $c['course_code'] ? 'selected' : '' ?>>
        <?= $c['course_code'] ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select name="tutor">
    <option value="">All Tutors</option>
    <?php foreach ($tutors as $t): ?>
      <option value="<?= $t['staff_id'] ?>" <?= $tutor === $t['staff_id'] ? 'selected' : '' ?>>
        <?= $t['first_name'] . ' ' . $t['surname'] ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select name="status">
    <option value="">All Statuses</option>
    <?php foreach (['Provisional', 'Live', 'Dormant'] as $stat): ?>
      <option value="<?= $stat ?>" <?= $status === $stat ? 'selected' : '' ?>><?= $stat ?></option>
    <?php endforeach; ?>
  </select>

  <button type="submit">Search</button>

  <!-- Customise Columns Button -->
  <div class="columns-dropdown-container" >
    <button type="button" onclick="toggleColumnsDropdown()">Customise Columns ⬇</button>
    <div id="columnsDropdown" class="columns-dropdown" >
      <?php foreach ($available_columns as $col => $label): ?>
        <div>
          <label>
            <input type="checkbox" name="columns[]" value="<?= $col ?>" <?= in_array($col, $selected_columns) ? 'checked' : '' ?>>
            <?= $label ?>
          </label>
        </div>
      <?php endforeach; ?>
      <div style="margin-top: 0.5rem;">
        <button type="submit">Apply</button>
      </div>
    </div>
  </div>

</form>

<?php if ($students): ?>
  <table class="search-table">
    <thead>
      <tr>
        <?php foreach ($selected_columns as $col_key): ?>
          <th><?= htmlspecialchars($available_columns[$col_key] ?? ucfirst($col_key)) ?></th>
        <?php endforeach; ?>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($students as $s): ?>
        <tr>
          <?php foreach ($selected_columns as $col_key): ?>
            <td>
              <?php
              switch ($col_key) {
                  case 'student_id':
                      echo htmlspecialchars($s['student_id'] ?? '');
                      break;
                  case 'name':
                      echo htmlspecialchars(trim(($s['first_name'] ?? '') . ' ' . ($s['middle_name'] ?? '') . ' ' . ($s['surname'] ?? '')));
                      break;
                  case 'email':
                      echo htmlspecialchars($s['email'] ?? '');
                      break;
                  case 'phone':
                      echo htmlspecialchars($s['phone'] ?? '');
                      break;
                  case 'record_status':
                      echo htmlspecialchars($s['record_status'] ?? '');
                      break;
                  case 'course_code':
                      echo htmlspecialchars($s['course_code'] ?? '');
                      break;
                  case 'tutor_name':
                      echo htmlspecialchars($s['tutor_name'] ?? '—');
                      break;
                  case 'term_address':
                      echo htmlspecialchars(trim(($s['term_street'] ?? '') . ', ' . ($s['term_town'] ?? '') . ', ' . ($s['term_city'] ?? '') . ', ' . ($s['term_postcode'] ?? '')));
                      break;
                  case 'non_term_address':
                      echo htmlspecialchars(trim(($s['non_term_street'] ?? '') . ', ' . ($s['non_term_town'] ?? '') . ', ' . ($s['non_term_city'] ?? '') . ', ' . ($s['non_term_postcode'] ?? '')));
                      break;
              }
              ?>
            </td>
          <?php endforeach; ?>
          <td><a href="/index.php?page=students_profile&id=<?= urlencode($s['student_id']) ?>">View / Edit</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <p>No students found.</p>
<?php endif; ?>

<!-- Tiny JS to handle dropdown -->
<script>
function toggleColumnsDropdown() {
    var dropdown = document.getElementById('columnsDropdown');
    dropdown.style.display = (dropdown.style.display === 'none' || dropdown.style.display === '') ? 'block' : 'none';
}
</script>
