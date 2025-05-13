<?php
require_once __DIR__ . '/../includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$timetable_id = $_GET['id'] ?? null;
$editMode = isset($_GET['edit']) && $_GET['edit'] == '1';

if (!$timetable_id) {
    echo "<p>Invalid session ID.</p>";
    exit;
}

// Handle DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    if ($_POST['delete'] === 'recurring' && !empty($_POST['recurring_session_id'])) {
        $stmt = $pdo->prepare("DELETE FROM Timetables WHERE recurring_session_id = ?");
        $stmt->execute([$_POST['recurring_session_id']]);
        $_SESSION['flash_success'] = "All recurring sessions deleted.";
    } elseif ($_POST['delete'] === 'single') {
        $stmt = $pdo->prepare("DELETE FROM Timetables WHERE timetable_id = ?");
        $stmt->execute([$timetable_id]);
        $_SESSION['flash_success'] = "Session deleted.";
    }
    header("Location: /index.php?page=timetables_calendar");
    exit;
}

// Handle UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $fields = ['module_id', 'staff_id', 'room_id', 'date', 'start_time', 'end_time', 'type', 'group_id'];
    $updateData = [];
    $updateFields = [];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $updateFields[] = "$field = :$field";
            $updateData[$field] = $_POST[$field];
        }
    }

    $updateData['timetable_id'] = $timetable_id;
    $sql = "UPDATE Timetables SET " . implode(', ', $updateFields) . " WHERE timetable_id = :timetable_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($updateData);

    if (isset($_POST['save_exit'])) {
        header("Location: /index.php?page=timetables_calendar&message=Session updated successfully");
        exit();
    } else {
        header("Location: /index.php?page=session_profile&id=$timetable_id&edit=1&message=Changes saved successfully");
        exit();
    }
}

// Fetch session data
$stmt = $pdo->prepare("SELECT * FROM Timetables WHERE timetable_id = :id");
$stmt->execute(['id' => $timetable_id]);
$session = $stmt->fetch();

if (!$session) {
    echo "<p>Session not found.</p>";
    exit;
}

$modules = $pdo->query("SELECT module_id, module_code, module_title FROM Modules ORDER BY module_code")->fetchAll();
$staffList = $pdo->query("SELECT staff_id, first_name, surname FROM Staff WHERE roles LIKE '%Academic%' AND record_status = 'Live' ORDER BY surname")->fetchAll();
$rooms = $pdo->query("SELECT room_id, room_code FROM Rooms ORDER BY room_code")->fetchAll();
$groups = $pdo->query("SELECT group_id, level, year FROM StudentGroups ORDER BY group_id")->fetchAll();
?>

<h2>Session Profile</h2>
<?php if (isset($_GET['message'])): ?>
  <div class="toast">
    <?= htmlspecialchars($_GET['message']) ?>
  </div>
<?php endif; ?>

<?php if ($editMode): ?>
<form method="post" action="/index.php?page=session_profile&id=<?= urlencode($timetable_id) ?>">
<?php endif; ?>

<table>
  <tr><th>Type:</th><td>
    <?php if ($editMode): ?>
      <select name="type">
        <?php foreach (['Lecture','Practical','Exam','Event'] as $t): ?>
          <option value="<?= $t ?>" <?= $session['type'] === $t ? 'selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
      </select>
    <?php else: ?>
      <?= htmlspecialchars($session['type']) ?>
    <?php endif; ?>
  </td></tr>

  <tr><th>Date:</th><td><?= $editMode
    ? '<input type="date" name="date" value="' . $session['date'] . '">' 
    : htmlspecialchars($session['date']) ?></td></tr>

  <tr><th>Start Time:</th><td><?= $editMode
    ? '<input type="time" name="start_time" value="' . $session['start_time'] . '">' 
    : htmlspecialchars($session['start_time']) ?></td></tr>

  <tr><th>End Time:</th><td><?= $editMode
    ? '<input type="time" name="end_time" value="' . $session['end_time'] . '">' 
    : htmlspecialchars($session['end_time']) ?></td></tr>

  <tr><th>Module:</th><td>
    <?php if ($editMode): ?>
      <select name="module_id">
        <?php foreach ($modules as $m): ?>
          <option value="<?= $m['module_id'] ?>" <?= $session['module_id'] == $m['module_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars("{$m['module_code']} - {$m['module_title']}") ?>
          </option>
        <?php endforeach; ?>
      </select>
    <?php else: ?>
      <?= htmlspecialchars($session['module_id']) ?>
    <?php endif; ?>
  </td></tr>

  <tr><th>Staff:</th><td>
    <?php if ($editMode): ?>
      <select name="staff_id">
        <?php foreach ($staffList as $s): ?>
          <option value="<?= $s['staff_id'] ?>" <?= $session['staff_id'] == $s['staff_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars("{$s['first_name']} {$s['surname']}") ?>
          </option>
        <?php endforeach; ?>
      </select>
    <?php else: ?>
      <?= htmlspecialchars($session['staff_id']) ?>
    <?php endif; ?>
  </td></tr>

  <tr><th>Room:</th><td>
    <?php if ($editMode): ?>
      <select name="room_id">
        <?php foreach ($rooms as $r): ?>
          <option value="<?= $r['room_id'] ?>" <?= $session['room_id'] == $r['room_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($r['room_code']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    <?php else: ?>
      <?= htmlspecialchars($session['room_id']) ?>
    <?php endif; ?>
  </td></tr>

  <tr><th>Group:</th><td>
    <?php if ($editMode): ?>
      <select name="group_id">
        <option value="">(Optional)</option>
        <?php foreach ($groups as $g): ?>
          <option value="<?= $g['group_id'] ?>" <?= $session['group_id'] == $g['group_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars("Group {$g['group_id']} - Level {$g['level']}, Year {$g['year']}") ?>
          </option>
        <?php endforeach; ?>
      </select>
    <?php else: ?>
      <?= htmlspecialchars($session['group_id']) ?>
    <?php endif; ?>
  </td></tr>
</table>

<?php if ($editMode): ?>
  <br>
  <button type="submit" name="save" class="button-primary">Save Changes</button>
  <button type="submit" name="save_exit" class="button-primary">Save & Exit</button>
  <a class="button-cancel" href="/index.php?page=session_profile&id=<?= urlencode($timetable_id) ?>">Cancel</a>
</form>
<?php else: ?>
  <br>
  <a class="button-primary" href="/index.php?page=session_profile&id=<?= urlencode($timetable_id) ?>&edit=1">Edit Session</a>

  <form method="post" action="" style="display:inline-block;">
    <input type="hidden" name="delete" value="single">
    <button type="submit" class="button-cancel" onclick="return confirm('Delete this session?')">Delete This Session</button>
  </form>

  <?php if (!empty($session['recurring_session_id'])): ?>
  <form method="post" action="" style="display:inline-block; margin-left: 1rem;">
    <input type="hidden" name="delete" value="recurring">
    <input type="hidden" name="recurring_session_id" value="<?= $session['recurring_session_id'] ?>">
    <button type="submit" class="button-cancel" onclick="return confirm('Delete all recurring sessions?')">Delete All Recurring Sessions</button>
  </form>
  <?php endif; ?>
<?php endif; ?>
