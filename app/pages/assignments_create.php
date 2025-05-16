<?php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $type = $_POST['type'] ?? '';
    $weight = $_POST['weight'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $module_id = $_POST['module_id'] ?? '';

    $stmt = $pdo->prepare("
        INSERT INTO Assignments (title, type, weight, due_date, module_id)
        VALUES (:title, :type, :weight, :due_date, :module_id)
    ");
    $stmt->execute([
        'title' => trim($title),
        'type' => $type,
        'weight' => $weight,
        'due_date' => $due_date,
        'module_id' => $module_id
    ]);

    if (isset($_POST['save_add'])) {
        $_SESSION['toast'] = "Assignment created successfully.";
        header("Location: /index.php?page=assignments_create");
        exit;
    } else {
        $_SESSION['toast'] = "Assignment created successfully.";
        header("Location: /index.php?page=assignments_search");
        exit;
    }
}

// Get modules for dropdown
$modules = $pdo->query("SELECT module_id, module_code, module_title FROM Modules ORDER BY module_code")->fetchAll();
?>

<h2>Create New Assessment</h2>

<!-- Toast -->
<?php if (!empty($_SESSION['toast'])): ?>
  <div class="toast"><?= htmlspecialchars($_SESSION['toast']) ?></div>
  <?php unset($_SESSION['toast']); ?>
<?php endif; ?>

<form method="post" action="/index.php?page=assignments_create">

  <label>Title:
    <input type="text" name="title" required>
  </label><br><br>

  <label>Type:
    <select name="type" class="select2" required>
      <?php foreach (['Ass1', 'Ass2', 'Exam'] as $t): ?>
        <option value="<?= $t ?>"><?= $t ?></option>
      <?php endforeach; ?>
    </select>
  </label><br><br>

  <label>Weight (%):
    <input type="number" name="weight" min="0" max="100" step="0.1" required>
  </label><br><br>

  <label>Due Date:
    <input type="date" name="due_date" required>
  </label><br><br>

  <label>Module:
    <select name="module_id" class="select2" required>
      <option value="">Select Module</option>
      <?php foreach ($modules as $mod): ?>
        <option value="<?= $mod['module_id'] ?>">
          <?= htmlspecialchars($mod['module_code'] . ' - ' . $mod['module_title']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label><br><br>

  <button type="submit" name="save" class="button-primary">Save</button>
  <button type="submit" name="save_add" class="button-primary">Save & Add Another</button>
  <a href="/index.php?page=assignments_search" class="button-cancel">Cancel</a>
</form>
