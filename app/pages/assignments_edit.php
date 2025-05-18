<?php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$assignment_id = $_GET['id'] ?? null;
if (!$assignment_id) {
    echo "<p>Invalid assignment ID.</p>";
    exit;
}

// form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['title', 'type', 'weight', 'due_date', 'module_id'];
    $updateData = [];
    $updateFields = [];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $updateFields[] = "$field = :$field";
            $updateData[$field] = trim($_POST[$field]);
        }
    }

    if (!empty($updateFields)) {
        $updateData['assignment_id'] = $assignment_id;
        $sql = "UPDATE Assignments SET " . implode(', ', $updateFields) . " WHERE assignment_id = :assignment_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateData);
    }

    if (isset($_POST['save_exit'])) {
        $_SESSION['toast'] = "Assignment updated successfully.";
        header('Location: /index.php?page=assignments_search');
        exit;
    } else {
        header("Location: /index.php?page=assignments_edit&id=" . urlencode($assignment_id) . "&message=Assignment saved successfully");
        exit;
    }
}

$stmt = $pdo->prepare("SELECT * FROM Assignments WHERE assignment_id = :id");
$stmt->execute(['id' => $assignment_id]);
$assignment = $stmt->fetch();

if (!$assignment) {
    echo "<p>Assignment not found.</p>";
    exit;
}

// modules dropdown
$modules = $pdo->query("SELECT module_id, module_code, module_title FROM Modules ORDER BY module_code")->fetchAll();
?>

<h2>Edit Assignment</h2>

<!-- Toast  -->
<?php if (isset($_GET['message'])): ?>
  <div class="toast">
    <?= htmlspecialchars($_GET['message']) ?>
  </div>
<?php endif; ?>

<form method="post" action="/index.php?page=assignments_edit&id=<?= urlencode($assignment_id) ?>">

  <label>Title:
    <input type="text" name="title" value="<?= htmlspecialchars($assignment['title']) ?>" required>
  </label><br><br>

  <label>Type:
    <select name="type" required>
      <?php foreach (['Ass1', 'Ass2', 'Exam'] as $type): ?>
        <option value="<?= $type ?>" <?= $assignment['type'] === $type ? 'selected' : '' ?>><?= $type ?></option>
      <?php endforeach; ?>
    </select>
  </label><br><br>

  <label>Weight (%):
    <input type="number" name="weight" value="<?= htmlspecialchars($assignment['weight']) ?>" min="0" max="100" step="0.1" required>
  </label><br><br>

  <label>Due Date:
    <input type="date" name="due_date" value="<?= htmlspecialchars($assignment['due_date']) ?>" required>
  </label><br><br>

  <label>Module:
    <select name="module_id" required>
      <option value="">Select Module</option>
      <?php foreach ($modules as $mod): ?>
        <option value="<?= $mod['module_id'] ?>" <?= $assignment['module_id'] == $mod['module_id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($mod['module_code'] . ' - ' . $mod['module_title']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label><br><br>

  <button type="submit" name="save" class="button-primary">Save</button>
  <button type="submit" name="save_exit" class="button-primary">Save & Exit</button>
  <a href="/index.php?page=assignments_search" class="button-cancel">Cancel</a>
</form>
