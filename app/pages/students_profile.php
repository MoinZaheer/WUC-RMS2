<?php
require_once __DIR__ . '/../includes/db.php';

$student_id = $_GET['id'] ?? null;
$editMode = isset($_GET['edit']) && $_GET['edit'] == '1';

if (!$student_id) {
    echo "<p>Invalid student ID.</p>";
    exit;
}

// Fetch student data
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

<?php if ($editMode): ?>
<form method="post" action="/app/handlers/student_update.php">
  <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_id) ?>">
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
  <tr><th>Term Time Address:</th>
      <td>
        <?php if ($editMode): ?>
          <input type="text" name="term_street" placeholder="Street" value="<?= htmlspecialchars($student['term_street'] ?? '') ?>"><br>
          <input type="text" name="term_town" placeholder="Town" value="<?= htmlspecialchars($student['term_town'] ?? '') ?>"><br>
          <input type="text" name="term_city" placeholder="City" value="<?= htmlspecialchars($student['term_city'] ?? '') ?>"><br>
          <input type="text" name="term_postcode" placeholder="Postcode" value="<?= htmlspecialchars($student['term_postcode'] ?? '') ?>">
        <?php else: ?>
          <?= htmlspecialchars(trim(($student['term_street'] ?? '') . ', ' . ($student['term_town'] ?? '') . ', ' . ($student['term_city'] ?? '') . ', ' . ($student['term_postcode'] ?? ''))) ?>
        <?php endif; ?>
      </td>
  </tr>

  <!-- Non-Term-time Address -->
  <tr><th>Non-Term Time Address:</th>
      <td>
        <?php if ($editMode): ?>
          <input type="text" name="non_term_street" placeholder="Street" value="<?= htmlspecialchars($student['non_term_street'] ?? '') ?>"><br>
          <input type="text" name="non_term_town" placeholder="Town" value="<?= htmlspecialchars($student['non_term_town'] ?? '') ?>"><br>
          <input type="text" name="non_term_city" placeholder="City" value="<?= htmlspecialchars($student['non_term_city'] ?? '') ?>"><br>
          <input type="text" name="non_term_postcode" placeholder="Postcode" value="<?= htmlspecialchars($student['non_term_postcode'] ?? '') ?>">
        <?php else: ?>
          <?= htmlspecialchars(trim(($student['non_term_street'] ?? '') . ', ' . ($student['non_term_town'] ?? '') . ', ' . ($student['non_term_city'] ?? '') . ', ' . ($student['non_term_postcode'] ?? ''))) ?>
        <?php endif; ?>
      </td>
  </tr>

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
    <?= $editMode
        ? '<input type="text" name="course_code" value="' . htmlspecialchars($student['course_code'] ?? '') . '">'
        : htmlspecialchars($student['course_code'] ?? '') ?>
  </td></tr>

  <tr><th>Personal Tutor:</th><td>
    <?= $tutor
        ? htmlspecialchars(trim(($tutor['first_name'] ?? '') . ' ' . ($tutor['middle_name'] ?? '') . ' ' . ($tutor['surname'] ?? '')))
        : 'Not Assigned' ?>
  </td></tr>
</table>

<?php if ($editMode): ?>
  <button type="submit">Save Changes</button>
  <a href="/index.php?page=students_profile&id=<?= urlencode($student_id) ?>">Cancel</a>
</form>
<?php else: ?>
  <p>
    <a href="/index.php?page=students_profile&id=<?= urlencode($student_id) ?>&edit=1">Edit Student</a>
  </p>
<?php endif; ?>
