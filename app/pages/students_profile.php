<?php
require_once __DIR__ . '/../includes/db.php';

$student_id = $_GET['id'] ?? null;
$editMode = isset($_GET['edit']) && $_GET['edit'] == '1';

if (!$student_id) {
    echo "<p>Invalid student ID.</p>";
    exit;
}

// Handle form POST (Save/Save & Exit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'first_name', 'middle_name', 'surname', 'email', 'phone',
        'term_street', 'term_town', 'term_city', 'term_postcode',
        'non_term_street', 'non_term_town', 'non_term_city', 'non_term_postcode',
        'entry_qualifications', 'record_status', 'dormancy_reason', 'course_code'
    ];

    $updateData = [];
    $updateFields = [];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $updateFields[] = "$field = :$field";
            $updateData[$field] = trim($_POST[$field]);
        }
    }

    if (!empty($updateFields)) {
        $updateData['student_id'] = $student_id;
        $sql = "UPDATE Students SET " . implode(', ', $updateFields) . " WHERE student_id = :student_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateData);
    }

    // Redirect accordingly
    if (isset($_POST['save_exit'])) {
        header("Location: /index.php?page=students_search&message=Student updated successfully");
        exit();
    } else {
        header("Location: /index.php?page=students_profile&id=" . urlencode($student_id) . "&edit=1&message=Changes saved successfully");
        exit();
    }
}

// Fetch student data after any potential update
$stmt = $pdo->prepare("SELECT * FROM Students WHERE student_id = :id");
$stmt->execute(['id' => $student_id]);
$student = $stmt->fetch();

if (!$student) {
    echo "<p>Student not found.</p>";
    exit;
}

// Fetch personal tutor
$tutorStmt = $pdo->prepare("
  SELECT st.first_name, st.middle_name, st.surname
  FROM PersonalTutors pt
  JOIN Staff st ON pt.tutor_id = st.staff_id
  WHERE pt.student_id = :id
");
$tutorStmt->execute(['id' => $student_id]);
$tutor = $tutorStmt->fetch();
?>

<h2>Student Profile</h2>

<!-- Message if successfull or not -->
<?php if (isset($_GET['message'])): ?>
  <div class="toast">
    <?= htmlspecialchars($_GET['message']) ?>
  </div>
<?php endif; ?>


<?php if ($editMode): ?>
<form method="post" action="/index.php?page=students_profile&id=<?= urlencode($student_id) ?>">
<?php endif; ?>

<table>
  <tr><th>ID:</th><td><?= htmlspecialchars($student['student_id'] ?? '') ?></td></tr>

  <tr><th>Name:</th><td>
    <?php if ($editMode): ?>
      <input type="text" name="first_name" value="<?= htmlspecialchars($student['first_name'] ?? '') ?>" placeholder="First Name">
      <input type="text" name="middle_name" value="<?= htmlspecialchars($student['middle_name'] ?? '') ?>" placeholder="Middle Name">
      <input type="text" name="surname" value="<?= htmlspecialchars($student['surname'] ?? '') ?>" placeholder="Surname">
    <?php else: ?>
      <?= htmlspecialchars(trim(($student['first_name'] ?? '') . ' ' . ($student['middle_name'] ?? '') . ' ' . ($student['surname'] ?? ''))) ?>
    <?php endif; ?>
  </td></tr>

  <tr><th>Email:</th><td>
    <?= $editMode
        ? '<input type="email" name="email" value="' . htmlspecialchars($student['email'] ?? '') . '">'
        : htmlspecialchars($student['email'] ?? '') ?>
  </td></tr>

  <tr><th>Phone:</th><td>
    <?= $editMode
        ? '<input type="text" name="phone" value="' . htmlspecialchars($student['phone'] ?? '') . '">'
        : htmlspecialchars($student['phone'] ?? '') ?>
  </td></tr>

  <!-- Term-time Address -->
  <tr><th>Term Time Address:</th><td>
    <?php if ($editMode): ?>
      <input type="text" name="term_street" placeholder="Street" value="<?= htmlspecialchars($student['term_street'] ?? '') ?>"><br>
      <input type="text" name="term_town" placeholder="Town" value="<?= htmlspecialchars($student['term_town'] ?? '') ?>"><br>
      <input type="text" name="term_city" placeholder="City" value="<?= htmlspecialchars($student['term_city'] ?? '') ?>"><br>
      <input type="text" name="term_postcode" placeholder="Postcode" value="<?= htmlspecialchars($student['term_postcode'] ?? '') ?>">
    <?php else: ?>
      <?= htmlspecialchars(trim(($student['term_street'] ?? '') . ', ' . ($student['term_town'] ?? '') . ', ' . ($student['term_city'] ?? '') . ', ' . ($student['term_postcode'] ?? ''))) ?>
    <?php endif; ?>
  </td></tr>

  <!-- Non-Term-time Address -->
  <tr><th>Non-Term Time Address:</th><td>
    <?php if ($editMode): ?>
      <input type="text" name="non_term_street" placeholder="Street" value="<?= htmlspecialchars($student['non_term_street'] ?? '') ?>"><br>
      <input type="text" name="non_term_town" placeholder="Town" value="<?= htmlspecialchars($student['non_term_town'] ?? '') ?>"><br>
      <input type="text" name="non_term_city" placeholder="City" value="<?= htmlspecialchars($student['non_term_city'] ?? '') ?>"><br>
      <input type="text" name="non_term_postcode" placeholder="Postcode" value="<?= htmlspecialchars($student['non_term_postcode'] ?? '') ?>">
    <?php else: ?>
      <?= htmlspecialchars(trim(($student['non_term_street'] ?? '') . ', ' . ($student['non_term_town'] ?? '') . ', ' . ($student['non_term_city'] ?? '') . ', ' . ($student['non_term_postcode'] ?? ''))) ?>
    <?php endif; ?>
  </td></tr>

  <tr><th>Entry Qualifications:</th><td>
    <?= $editMode
        ? '<textarea name="entry_qualifications">' . htmlspecialchars($student['entry_qualifications'] ?? '') . '</textarea>'
        : htmlspecialchars($student['entry_qualifications'] ?? '') ?>
  </td></tr>

  <tr><th>Record Status:</th><td>
    <?php if ($editMode): ?>
      <select name="record_status">
        <?php foreach (['Provisional', 'Live', 'Dormant'] as $status): ?>
          <option value="<?= $status ?>" <?= ($student['record_status'] ?? '') === $status ? 'selected' : '' ?>><?= $status ?></option>
        <?php endforeach; ?>
      </select>
    <?php else: ?>
      <?= htmlspecialchars($student['record_status'] ?? '') ?>
    <?php endif; ?>
  </td></tr>

  <?php if (($student['record_status'] ?? '') === 'Dormant'): ?>
    <tr><th>Dormancy Reason:</th><td>
      <?php if ($editMode): ?>
        <select name="dormancy_reason">
          <?php foreach (['Graduated', 'Withdrawn', 'Terminated'] as $reason): ?>
            <option value="<?= $reason ?>" <?= ($student['dormancy_reason'] ?? '') === $reason ? 'selected' : '' ?>><?= $reason ?></option>
          <?php endforeach; ?>
        </select>
      <?php else: ?>
        <?= htmlspecialchars($student['dormancy_reason'] ?? '') ?>
      <?php endif; ?>
    </td></tr>
  <?php endif; ?>

  <tr><th>Course Code:</th><td>
    <?php if ($editMode): ?>
      <select name="course_code" class="form-select select2" required>
        <option value="">-- Select Course --</option>
        <?php
          $courses = $pdo->query("SELECT course_code, course_name FROM Courses")->fetchAll();
          foreach ($courses as $course) {
        $selected = ($student['course_code'] ?? '') === $course['course_code'] ? 'selected' : '';
        echo '<option value="' . htmlspecialchars($course['course_code']) . '" ' . $selected . '>'
             . htmlspecialchars($course['course_code'] . ' - ' . $course['course_name'])
             . '</option>';
        }        
        ?>
      </select>
    <?php else: ?>
      <?= htmlspecialchars($student['course_code'] ?? '') ?>
    <?php endif; ?>
</td></tr>


  <tr><th>Personal Tutor:</th><td>
    <?= $tutor
        ? htmlspecialchars(trim(($tutor['first_name'] ?? '') . ' ' . ($tutor['middle_name'] ?? '') . ' ' . ($tutor['surname'] ?? '')))
        : 'Not Assigned' ?>
  </td></tr>
</table>

<?php if ($editMode): ?>
  <br>
  <button type="submit" name="save" class="button-primary">Save Changes</button>
  <button type="submit" name="save_exit" class="button-primary">Save & Exit</button>
  <a class="button-cancel" href="/index.php?page=students_profile&id=<?= urlencode($student_id) ?>">Cancel</a>
</form>
<?php else: ?>
  <p><a class="button-primary" href="/index.php?page=students_profile&id=<?= urlencode($student_id) ?>&edit=1">Edit Student</a></p>
<?php endif; ?>

