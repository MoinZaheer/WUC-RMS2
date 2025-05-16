<?php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id = trim($_POST['staff_id']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $surname = trim($_POST['surname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $street = trim($_POST['street']);
    $town = trim($_POST['town']);
    $city = trim($_POST['city']);
    $postcode = trim($_POST['postcode']);
    $roles = $_POST['roles'];
    $specialist_subjects = trim($_POST['specialist_subjects']);
    $record_status = $_POST['record_status'];

    try {
        $stmt = $pdo->prepare("
            INSERT INTO Staff (
                staff_id, first_name, middle_name, surname, email, phone,
                street, town, city, postcode, roles, specialist_subjects, record_status
            ) VALUES (
                :staff_id, :first_name, :middle_name, :surname, :email, :phone,
                :street, :town, :city, :postcode, :roles, :specialist_subjects, :record_status
            )
        ");

        $stmt->execute([
            'staff_id' => $staff_id,
            'first_name' => $first_name,
            'middle_name' => $middle_name,
            'surname' => $surname,
            'email' => $email,
            'phone' => $phone,
            'street' => $street,
            'town' => $town,
            'city' => $city,
            'postcode' => $postcode,
            'roles' => $roles,
            'specialist_subjects' => $specialist_subjects,
            'record_status' => $record_status,
        ]);

        $_SESSION['flash_success'] = "Staff member created successfully.";
        header("Location: /index.php?page=staff_search");
        exit;
    } catch (PDOException $e) {
        $error = "Error creating staff member: " . $e->getMessage();
    }
}
?>

<h2>Create Staff Member</h2>

<?php if (!empty($error)): ?>
  <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="/index.php?page=staff_create" id="staffCreateForm">

<table>

  <tr><th>Staff ID:</th><td><input type="text" name="staff_id" required></td></tr>

  <tr><th>Record Status:</th><td>
    <select name="record_status" required>
      <option value="Live">Live</option>
      <option value="Dormant">Dormant</option>
    </select>
  </td></tr>
  
  <tr><th>First Name:</th><td><input type="text" name="first_name" required></td></tr>
  <tr><th>Middle Name:</th><td><input type="text" name="middle_name"></td></tr>
  <tr><th>Surname:</th><td><input type="text" name="surname" required></td></tr>

  <tr><th>Email:</th><td><input type="email" name="email" required></td></tr>
  <tr><th>Phone:</th><td><input type="text" name="phone"></td></tr>

  <tr><th>Address:</th><td>
    <input type="text" name="street" placeholder="Street"><br>
    <input type="text" name="town" placeholder="Town"><br>
    <input type="text" name="city" placeholder="City"><br>
    <input type="text" name="postcode" placeholder="Postcode">
  </td></tr>

  <tr><th>Roles:</th><td>
    <select name="roles" id="roles" required>
      <option value="ACADEMIC">Academic</option>
      <option value="ADMIN">Admin</option>
    </select>
  </td></tr>

  <tr><th>Specialist Subjects:</th><td>
    <textarea name="specialist_subjects" id="specialistSubjects" placeholder="(For Academic Staff only)"></textarea>
  </td></tr>

  
</table>

<br>

<button type="submit" class="button-primary">Create Staff</button>
<a href="/index.php?page=staff_search" class="button-cancel">Cancel</a>

</form>

<!-- disable specialist subject if role is not academic -->
<script>
document.getElementById('roles').addEventListener('change', function() {
    const specialistSubjects = document.getElementById('specialistSubjects');
    if (this.value === 'Academic') {
        specialistSubjects.disabled = false;
    } else {
        specialistSubjects.disabled = true;
        specialistSubjects.value = '';
    }
});

// trigger once on page load too
window.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('roles');
    const specialistSubjects = document.getElementById('specialistSubjects');
    if (roleSelect.value !== 'Academic') {
        specialistSubjects.disabled = true;
    }
});
</script>
