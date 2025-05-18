<?php
require_once __DIR__ . '/../includes/db.php';

// for dropdown options
$courses = $pdo->query("SELECT course_code, course_name FROM Courses")->fetchAll();
$staff = $pdo->query("SELECT staff_id, first_name, middle_name, surname FROM Staff")->fetchAll();

// form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $module_code = trim($_POST['module_code']);
    $module_title = trim($_POST['module_title']);
    $level = (int)($_POST['level'] ?? null);
    $credits = (int)($_POST['credits'] ?? null);
    $course_code = $_POST['course_code'] ?? null;
    $module_leader_id = $_POST['module_leader_id'] ?? null;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO Modules (module_code, module_title, level, credits, course_code, module_leader_id)
            VALUES (:module_code, :module_title, :level, :credits, :course_code, :module_leader_id)
        ");
        $stmt->execute([
            'module_code' => $module_code,
            'module_title' => $module_title,
            'level' => $level,
            'credits' => $credits,
            'course_code' => $course_code,
            'module_leader_id' => $module_leader_id,
        ]);

        $_SESSION['flash_success'] = "Module created successfully.";
        header("Location: /index.php?page=modules_search");
        exit;

    } catch (PDOException $e) {
        $error = "Error creating module: " . $e->getMessage();
    }
}
?>

<h2>Create Module</h2>

<?php if (!empty($error)): ?>
  <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="/index.php?page=module_create" id="moduleCreateForm">

<table>
  <tr><th>Module Code:</th><td><input type="text" name="module_code" required></td></tr>
  <tr><th>Module Title:</th><td><input type="text" name="module_title" required></td></tr>
  <tr><th>Level:</th><td><input type="number" name="level" min="3" max="7" required></td></tr>
  <tr><th>Credits:</th><td><input type="number" name="credits" min="10" max="120" required></td></tr>

  <tr><th>Course:</th><td>
    <select name="course_code" class="select2" required>
      <option value="">-- Select Course --</option>
      <?php foreach ($courses as $c): ?>
        <option value="<?= htmlspecialchars($c['course_code']) ?>">
          <?= htmlspecialchars($c['course_code']) ?> - <?= htmlspecialchars($c['course_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </td></tr>

  <tr><th>Module Leader:</th><td>
    <select name="module_leader_id" class="select2">
      <option value="">-- Optional --</option>
      <?php foreach ($staff as $s): ?>
        <option value="<?= htmlspecialchars($s['staff_id']) ?>">
          <?= htmlspecialchars(trim(($s['first_name'] ?? '') . ' ' . ($s['middle_name'] ?? '') . ' ' . ($s['surname'] ?? ''))) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </td></tr>

</table>

<br>

<button type="submit" class="button-primary">Create Module</button>
<a href="/index.php?page=modules_search" class="button-cancel">Cancel</a>

</form>

<?php if (!empty($_SESSION['flash_success'])): ?>
  <div class="toast">
    <?= htmlspecialchars($_SESSION['flash_success']) ?>
  </div>
  <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
