<?php
require_once __DIR__ . '/../includes/db.php';

$staff_id = $_GET['id'] ?? null;
$editMode = isset($_GET['edit']) && $_GET['edit'] == '1';

if (!$staff_id) {
    echo "<p>Invalid staff ID.</p>";
    exit;
}

// Handle form POST (Save/Save & Exit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'first_name', 'middle_name', 'surname', 'email', 'phone',
        'street', 'town', 'city', 'postcode',
        'roles', 'specialist_subjects', 'record_status'
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
        $updateData['staff_id'] = $staff_id;
        $sql = "UPDATE Staff SET " . implode(', ', $updateFields) . " WHERE staff_id = :staff_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateData);
    }

    // Redirect accordingly
    if (isset($_POST['save_exit'])) {
        header("Location: /index.php?page=staff_search&message=Staff updated successfully");
        exit();
    } else {
        header("Location: /index.php?page=staff_profile&id=" . urlencode($staff_id) . "&edit=1&message=Changes saved successfully");
        exit();
    }
}

// Fetch staff data
$stmt = $pdo->prepare("SELECT * FROM Staff WHERE staff_id = :id");
$stmt->execute(['id' => $staff_id]);
$staff = $stmt->fetch();

if (!$staff) {
    echo "<p>Staff member not found.</p>";
    exit;
}
?>

<h2>Staff Profile</h2>

<!-- Success Message -->
<?php if (isset($_GET['message'])): ?>
  <div class="toast">
    <?= htmlspecialchars($_GET['message']) ?>
  </div>
<?php endif; ?>

<?php if ($editMode): ?>
<form method="post" action="/index.php?page=staff_profile&id=<?= urlencode($staff_id) ?>">
<?php endif; ?>

<table>
  <tr><th>ID:</th><td><?= htmlspecialchars($staff['staff_id'] ?? '') ?></td></tr>

  <tr><th>Name:</th><td>
    <?php if ($editMode): ?>
      <input type="text" name="first_name" value="<?= htmlspecialchars($staff['first_name'] ?? '') ?>" placeholder="First Name">
      <input type="text" name="middle_name" value="<?= htmlspecialchars($staff['middle_name'] ?? '') ?>" placeholder="Middle Name">
      <input type="text" name="surname" value="<?= htmlspecialchars($staff['surname'] ?? '') ?>" placeholder="Surname">
    <?php else: ?>
      <?= htmlspecialchars(trim(($staff['first_name'] ?? '') . ' ' . ($staff['middle_name'] ?? '') . ' ' . ($staff['surname'] ?? ''))) ?>
    <?php endif; ?>
  </td></tr>

  <tr><th>Email:</th><td>
    <?= $editMode
        ? '<input type="email" name="email" value="' . htmlspecialchars($staff['email'] ?? '') . '">'
        : htmlspecialchars($staff['email'] ?? '') ?>
  </td></tr>

  <tr><th>Phone:</th><td>
    <?= $editMode
        ? '<input type="text" name="phone" value="' . htmlspecialchars($staff['phone'] ?? '') . '">'
        : htmlspecialchars($staff['phone'] ?? '') ?>
  </td></tr>

  <tr><th>Address:</th><td>
    <?php if ($editMode): ?>
      <input type="text" name="street" placeholder="Street" value="<?= htmlspecialchars($staff['street'] ?? '') ?>"><br>
      <input type="text" name="town" placeholder="Town" value="<?= htmlspecialchars($staff['town'] ?? '') ?>"><br>
      <input type="text" name="city" placeholder="City" value="<?= htmlspecialchars($staff['city'] ?? '') ?>"><br>
      <input type="text" name="postcode" placeholder="Postcode" value="<?= htmlspecialchars($staff['postcode'] ?? '') ?>">
    <?php else: ?>
      <?= htmlspecialchars(trim(($staff['street'] ?? '') . ', ' . ($staff['town'] ?? '') . ', ' . ($staff['city'] ?? '') . ', ' . ($staff['postcode'] ?? ''))) ?>
    <?php endif; ?>
  </td></tr>

  <tr><th>Roles:</th><td>
    <?= $editMode
        ? '<input type="text" name="roles" value="' . htmlspecialchars($staff['roles'] ?? '') . '">'
        : htmlspecialchars($staff['roles'] ?? '') ?>
  </td></tr>

  <tr><th>Specialist Subjects:</th><td>
    <?= $editMode
        ? '<textarea name="specialist_subjects">' . htmlspecialchars($staff['specialist_subjects'] ?? '') . '</textarea>'
        : htmlspecialchars($staff['specialist_subjects'] ?? '') ?>
  </td></tr>

  <tr><th>Record Status:</th><td>
    <?php if ($editMode): ?>
      <select name="record_status">
        <?php foreach (['Live', 'Dormant'] as $status): ?>
          <option value="<?= $status ?>" <?= ($staff['record_status'] ?? '') === $status ? 'selected' : '' ?>><?= $status ?></option>
        <?php endforeach; ?>
      </select>
    <?php else: ?>
      <?= htmlspecialchars($staff['record_status'] ?? '') ?>
    <?php endif; ?>
  </td></tr>
</table>

<?php if ($editMode): ?>
  <br>
  <button type="submit" name="save" class="button-primary">Save Changes</button>
  <button type="submit" name="save_exit" class="button-primary">Save & Exit</button>
  <a class="button-cancel" href="/index.php?page=staff_profile&id=<?= urlencode($staff_id) ?>">Cancel</a>
</form>
<?php else: ?>
  <p><a class="button-primary" href="/index.php?page=staff_profile&id=<?= urlencode($staff_id) ?>&edit=1">Edit Staff</a></p>
<?php endif; ?>
