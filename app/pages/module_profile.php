<?php
require_once __DIR__ . '/../includes/db.php';

$module_id = $_GET['id'] ?? null;
$editMode = isset($_GET['edit']) && $_GET['edit'] == '1';

if (!$module_id) {
    echo "<p>Invalid module ID.</p>";
    exit;
}

// Handle form POST (Save/Save & Exit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['module_code', 'module_title', 'level', 'credits', 'course_code', 'module_leader_id'];
    $updateData = [];
    $updateFields = [];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $updateFields[] = "$field = :$field";
            $updateData[$field] = trim($_POST[$field]);
        }
    }

    if (!empty($updateFields)) {
        $updateData['module_id'] = $module_id;
        $sql = "UPDATE Modules SET " . implode(', ', $updateFields) . " WHERE module_id = :module_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateData);
    }

    // Redirect accordingly
    if (isset($_POST['save_exit'])) {
        header("Location: /index.php?page=modules_search&message=Module updated successfully");
        exit();
    } else {
        header("Location: /index.php?page=module_profile&id=" . urlencode($module_id) . "&edit=1&message=Changes saved successfully");
        exit();
    }
}

// Fetch module data
$stmt = $pdo->prepare("
    SELECT m.*, 
           c.course_name,
           CONCAT(s.first_name, ' ', s.surname) AS module_leader_name
    FROM Modules m
    LEFT JOIN Courses c ON m.course_code = c.course_code
    LEFT JOIN Staff s ON m.module_leader_id = s.staff_id
    WHERE m.module_id = :id
");
$stmt->execute(['id' => $module_id]);
$module = $stmt->fetch();

if (!$module) {
    echo "<p>Module not found.</p>";
    exit;
}

// Fetch dropdown options
$courses = $pdo->query("SELECT course_code, course_name FROM Courses")->fetchAll();
$staff = $pdo->query("SELECT staff_id, first_name, surname FROM Staff")->fetchAll();
?>

<h2>Module Profile</h2>

<!-- Toast message -->
<?php if (isset($_GET['message'])): ?>
  <div class="toast">
    <?= htmlspecialchars($_GET['message']) ?>
  </div>
<?php endif; ?>

<?php if ($editMode): ?>
<form method="post" action="/index.php?page=module_profile&id=<?= urlencode($module_id) ?>">
<?php endif; ?>

<table>
  <tr><th>Module ID:</th><td><?= htmlspecialchars($module['module_id'] ?? '') ?></td></tr>

  <tr><th>Module Code:</th><td>
    <?php if ($editMode): ?>
      <input type="text" name="module_code" value="<?= htmlspecialchars($module['module_code'] ?? '') ?>" required>
    <?php else: ?>
      <?= htmlspecialchars($module['module_code'] ?? '') ?>
    <?php endif; ?>
  </td></tr>

  <tr><th>Title:</th><td>
    <?php if ($editMode): ?>
      <input type="text" name="module_title" value="<?= htmlspecialchars($module['module_title'] ?? '') ?>">
    <?php else: ?>
      <?= htmlspecialchars($module['module_title'] ?? '') ?>
    <?php endif; ?>
  </td></tr>

  <tr><th>Level:</th><td>
    <?php if ($editMode): ?>
      <input type="number" name="level" value="<?= htmlspecialchars($module['level'] ?? '') ?>" min="3" max="7">
    <?php else: ?>
      <?= htmlspecialchars($module['level'] ?? '') ?>
    <?php endif; ?>
  </td></tr>

  <tr><th>Credits:</th><td>
    <?php if ($editMode): ?>
      <input type="number" name="credits" value="<?= htmlspecialchars($module['credits'] ?? '') ?>" min="0">
    <?php else: ?>
      <?= htmlspecialchars($module['credits'] ?? '') ?>
    <?php endif; ?>
  </td></tr>

  <tr><th>Course:</th><td>
    <?php if ($editMode): ?>
      <select name="course_code" required>
        <option value="">-- Select Course --</option>
        <?php foreach ($courses as $c): ?>
          <option value="<?= htmlspecialchars($c['course_code']) ?>" <?= ($module['course_code'] ?? '') === $c['course_code'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['course_code']) ?> - <?= htmlspecialchars($c['course_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    <?php else: ?>
      <?= htmlspecialchars(($module['course_code'] ?? '') . ' - ' . ($module['course_name'] ?? '')) ?>
    <?php endif; ?>
  </td></tr>

  <tr><th>Module Leader:</th><td>
    <?php if ($editMode): ?>
      <select name="module_leader_id">
        <option value="">-- None --</option>
        <?php foreach ($staff as $s): ?>
          <option value="<?= htmlspecialchars($s['staff_id']) ?>" <?= ($module['module_leader_id'] ?? '') === $s['staff_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($s['first_name'] . ' ' . $s['surname']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    <?php else: ?>
      <?= htmlspecialchars($module['module_leader_name'] ?? '—') ?>
    <?php endif; ?>
  </td></tr>

</table>

<?php if ($editMode): ?>
  <br>
  <button type="submit" name="save" class="button-primary">Save Changes</button>
  <button type="submit" name="save_exit" class="button-primary">Save & Exit</button>
  <a href="/index.php?page=module_profile&id=<?= urlencode($module_id) ?>" class="button-cancel">Cancel</a>
</form>
<?php else: ?>
  <p><a class="button-primary" href="/index.php?page=module_profile&id=<?= urlencode($module_id) ?>&edit=1">Edit Module</a></p>
<?php endif; ?>
