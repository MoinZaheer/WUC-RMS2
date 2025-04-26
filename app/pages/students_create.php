<?php
require_once __DIR__ . '/../includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch courses for dropdown
$courses = $pdo->query("SELECT course_code, course_name FROM Courses")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $surname = trim($_POST['surname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $term_street = trim($_POST['term_street']);
    $term_town = trim($_POST['term_town']);
    $term_city = trim($_POST['term_city']);
    $term_postcode = trim($_POST['term_postcode']);
    $non_term_street = trim($_POST['non_term_street']);
    $non_term_town = trim($_POST['non_term_town']);
    $non_term_city = trim($_POST['non_term_city']);
    $non_term_postcode = trim($_POST['non_term_postcode']);
    $entry_qualifications = trim($_POST['entry_qualifications']);
    $record_status = $_POST['record_status'];
    $dormancy_reason = $_POST['dormancy_reason'] ?? null;
    $course_code = $_POST['course_code'];

    try {
        $stmt = $pdo->prepare("
            INSERT INTO Students (
                student_id, first_name, middle_name, surname, email, phone,
                term_street, term_town, term_city, term_postcode,
                non_term_street, non_term_town, non_term_city, non_term_postcode,
                entry_qualifications, record_status, dormancy_reason, course_code
            ) VALUES (
                :student_id, :first_name, :middle_name, :surname, :email, :phone,
                :term_street, :term_town, :term_city, :term_postcode,
                :non_term_street, :non_term_town, :non_term_city, :non_term_postcode,
                :entry_qualifications, :record_status, :dormancy_reason, :course_code
            )
        ");

        $stmt->execute([
            'student_id' => $student_id,
            'first_name' => $first_name,
            'middle_name' => $middle_name,
            'surname' => $surname,
            'email' => $email,
            'phone' => $phone,
            'term_street' => $term_street,
            'term_town' => $term_town,
            'term_city' => $term_city,
            'term_postcode' => $term_postcode,
            'non_term_street' => $non_term_street,
            'non_term_town' => $non_term_town,
            'non_term_city' => $non_term_city,
            'non_term_postcode' => $non_term_postcode,
            'entry_qualifications' => $entry_qualifications,
            'record_status' => $record_status,
            'dormancy_reason' => $dormancy_reason,
            'course_code' => $course_code,
        ]);

        $_SESSION['flash_success'] = "Student created successfully.";
        header("Location: /index.php?page=students_create");
        exit;

    } catch (PDOException $e) {
        $error = "Error creating student: " . $e->getMessage();
    }
}
?>

<h2>Create Student</h2>

<?php if (!empty($_SESSION['flash_success'])): ?>
  <div class="toast"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
  <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="/index.php?page=students_create" id="studentCreateForm">

<table>
  <tr><th>Student ID:</th><td><input type="text" name="student_id" required></td></tr>

  <tr><th>First Name:</th><td><input type="text" name="first_name" required></td></tr>
  <tr><th>Middle Name:</th><td><input type="text" name="middle_name"></td></tr>
  <tr><th>Surname:</th><td><input type="text" name="surname" required></td></tr>

  <tr><th>Email:</th><td><input type="email" name="email" required></td></tr>
  <tr><th>Phone:</th><td><input type="text" name="phone"></td></tr>

  <tr><th>Term Time Address:</th><td>
    <input type="text" name="term_street" placeholder="Street"><br>
    <input type="text" name="term_town" placeholder="Town"><br>
    <input type="text" name="term_city" placeholder="City"><br>
    <input type="text" name="term_postcode" placeholder="Postcode">
  </td></tr>

  <tr><th>Non-Term Address:</th><td>
    <input type="text" name="non_term_street" placeholder="Street"><br>
    <input type="text" name="non_term_town" placeholder="Town"><br>
    <input type="text" name="non_term_city" placeholder="City"><br>
    <input type="text" name="non_term_postcode" placeholder="Postcode">
  </td></tr>

  <tr><th>Entry Qualifications:</th><td><textarea name="entry_qualifications"></textarea></td></tr>

  <tr><th>Record Status:</th><td>
    <select name="record_status" id="recordStatus" required>
      <option value="Provisional">Provisional</option>
      <option value="Live">Live</option>
      <option value="Dormant">Dormant</option>
    </select>
  </td></tr>

  <tr><th>Dormancy Reason:</th><td>
    <select name="dormancy_reason" id="dormancyReason" disabled>
      <option value="">-- Select --</option>
      <option value="Graduated">Graduated</option>
      <option value="Withdrawn">Withdrawn</option>
      <option value="Terminated">Terminated</option>
    </select>
  </td></tr>

  <tr><th>Course Code:</th><td>
    <select name="course_code" required>
      <?php foreach ($courses as $c): ?>
        <option value="<?= htmlspecialchars($c['course_code']) ?>">
          <?= htmlspecialchars($c['course_code']) ?> - <?= htmlspecialchars($c['course_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </td></tr>
</table>

<br>

<button type="submit" class="button-primary">Create Student</button>
<a href="/index.php?page=students_search" class="button-cancel">Cancel</a>

</form>

<!-- Only enable Dormancy Reason if status is Dormant -->
<script>
document.getElementById('recordStatus').addEventListener('change', function() {
    const dormancyReason = document.getElementById('dormancyReason');
    if (this.value === 'Dormant') {
        dormancyReason.disabled = false;
    } else {
        dormancyReason.disabled = true;
        dormancyReason.value = '';
    }
});
</script>
