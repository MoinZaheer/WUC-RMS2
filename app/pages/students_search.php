<?php
require_once __DIR__ . '/../includes/db.php';

// Handle search/filter input
$search = $_GET['search'] ?? '';
$course = $_GET['course'] ?? '';
$tutor = $_GET['tutor'] ?? '';
$status = $_GET['status'] ?? '';

// Base query
$sql = "SELECT s.student_id, s.first_name, s.middle_name, s.surname, s.email, s.phone, s.record_status, s.course_code,
               CONCAT(st.first_name, ' ', st.surname) AS tutor_name
        FROM Students s
        LEFT JOIN PersonalTutors pt ON s.student_id = pt.student_id
        LEFT JOIN Staff st ON pt.tutor_id = st.staff_id
        WHERE 1=1";


$conditions = [];
$params = [];

if (!empty($search)) {
  $keywords = preg_split('/\s+/', trim($search));
  $keyword_conditions = [];
  $i = 0;

  foreach ($keywords as $word) {
      // unique placeholders per field
      $param_id = ":keyword_id_$i";
      $param_first = ":keyword_first_$i";
      $param_middle = ":keyword_middle_$i";
      $param_last = ":keyword_last_$i";

      $keyword_conditions[] = "(
          s.student_id LIKE $param_id OR
          s.first_name LIKE $param_first OR
          s.middle_name LIKE $param_middle OR
          s.surname LIKE $param_last
      )";

      // bind values
      $params["keyword_id_$i"] = "%$word%";
      $params["keyword_first_$i"] = "%$word%";
      $params["keyword_middle_$i"] = "%$word%";
      $params["keyword_last_$i"] = "%$word%";

      $i++;
  }

  if (!empty($keyword_conditions)) {
      $conditions[] = '(' . implode(' AND ', $keyword_conditions) . ')';
  }
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

// only add WHERE conditions if any filters are applied
if (!empty($conditions)) {
    $sql .= " AND " . implode(' AND ', $conditions);
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<pre>SQL Error: " . $e->getMessage() . "</pre>";
    echo "<pre>SQL: $sql</pre>";
    echo "<pre>Params: " . print_r($params, true) . "</pre>";
    
    // temporary debug output
    echo "<pre>SQL: $sql</pre>";
    echo "<pre>Params: " . print_r($params, true) . "</pre>";

    exit;
}


// dropdown options
$courses = $pdo->query("SELECT course_code FROM Courses")->fetchAll();
$tutors = $pdo->query("SELECT staff_id, first_name, surname FROM Staff")->fetchAll();
?>

<h2>Search Students</h2>

<form method="get" action="/index.php">
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
</form>

<?php if ($students): ?>
  <table>
    <thead>
      <tr>
        <th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Course</th><th>Tutor</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($students as $s): ?>
        <tr>
          <td><?= htmlspecialchars($s['student_id'] ?? '') ?></td>
          <td><?= htmlspecialchars(trim(($s['first_name'] ?? '') . ' ' . ($s['middle_name'] ?? '') . ' ' . ($s['surname'] ?? ''))) ?></td>
          <td><?= htmlspecialchars($s['email'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['phone'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['record_status'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['course_code'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['tutor_name'] ?? '—') ?></td>
          <td>
          <a href="/index.php?page=students_profile&id=<?= urlencode($s['student_id']) ?>">View/ Edit</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <p>No students found.</p>
<?php endif; ?>
