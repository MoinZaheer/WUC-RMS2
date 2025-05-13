<?php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $module_id = $_POST['module_id'];
    $staff_id = $_POST['staff_id'];
    $room_id = $_POST['room_id'];
    $group_id = !empty($_POST['group_id']) ? $_POST['group_id'] : null;
    $type = $_POST['type'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    $is_recurring = isset($_POST['is_recurring']);
    $days_of_week = $is_recurring ? $_POST['repeat_days'] : [];
    $start_date = $_POST['date'];
    $end_date = $is_recurring && !empty($_POST['repeat_until']) ? $_POST['repeat_until'] : $start_date;

    
    // to generate recurring session ID if needed
    $recurring_session_id = $is_recurring ? bin2hex(random_bytes(16)) : null;

    try {
        $pdo->beginTransaction();

        $period = new DatePeriod(
            new DateTime($start_date),
            new DateInterval('P1D'),
            (new DateTime($end_date))->modify('+1 day')
        );

        $count = 0;
        foreach ($period as $dateObj) {
            $day = strtolower($dateObj->format('l'));
            if (!$is_recurring || in_array($day, $days_of_week)) {
                $stmt = $pdo->prepare("
                    INSERT INTO Timetables (
                        module_id, staff_id, room_id, date, start_time, end_time, type, group_id, recurring_session_id
                    ) VALUES (
                        :module_id, :staff_id, :room_id, :date, :start_time, :end_time, :type, :group_id, :recurring_session_id
                    )
                ");
                $stmt->execute([
                    'module_id' => $module_id,
                    'staff_id' => $staff_id,
                    'room_id' => $room_id,
                    'date' => $dateObj->format('Y-m-d'),
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'type' => $type,
                    'group_id' => $group_id,
                    'recurring_session_id' => $recurring_session_id
                ]);
                $count++;
            }
        }

        $pdo->commit();
        $_SESSION['flash_success'] = "$count session" . ($count > 1 ? "s" : "") . " created successfully.";
        header("Location: /index.php?page=timetables_calendar");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
?>

<h2>Create Timetable Session</h2>

<?php if (!empty($error)): ?>
  <div class="alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="/index.php?page=session_create" id="sessionCreateForm">
<table>
  <tr>
    <th>Type:</th>
    <td>
      <select name="type" required>
        <option value="Lecture">Lecture</option>
        <option value="Practical">Practical</option>
        <option value="Exam">Exam</option>
        <option value="Event">Event</option>
      </select>
    </td>
  </tr>

  <tr>
    <th>Module:</th>
    <td>
      <select name="module_id" required>
        <option value="">Select Module</option>
        <?php
        $modules = $pdo->query("SELECT module_id, module_code, module_title FROM Modules ORDER BY module_code");
        foreach ($modules as $module) {
            echo "<option value='{$module['module_id']}'>"
               . htmlspecialchars("{$module['module_code']} - {$module['module_title']}")
               . "</option>";
        }
        ?>
      </select>
    </td>
  </tr>

  <tr>
    <th>Staff:</th>
    <td>
      <select name="staff_id" required>
        <option value="">Select Staff</option>
        <?php
        $staff = $pdo->query("SELECT staff_id, first_name, surname, roles FROM Staff WHERE record_status = 'Live' ORDER BY surname");
        foreach ($staff as $s) {
            $label = "{$s['first_name']} {$s['surname']}";
            if (!empty($s['roles'])) {
                $label .= " (" . strtok($s['roles'], ',') . ")";
            }
            echo "<option value='{$s['staff_id']}'>" . htmlspecialchars($label) . "</option>";
        }
        ?>
      </select>
    </td>
  </tr>



  <tr>
    <th>Room:</th>
    <td>
      <select name="room_id" required>
        <option value="">Select Room</option>
        <?php
        $rooms = $pdo->query("SELECT room_id, room_code, room_type, capacity FROM Rooms ORDER BY room_code");
        foreach ($rooms as $r) {
            $label = "{$r['room_code']} - {$r['room_type']} ({$r['capacity']})";
            echo "<option value='{$r['room_id']}'>" . htmlspecialchars($label) . "</option>";
        }
        ?>
      </select>
    </td>
  </tr>

  <tr>
    <th>Group:</th>
    <td>
      <select name="group_id">
        <option value="">(Optional)</option>
        <?php
        $groups = $pdo->query("SELECT group_id, level, year FROM StudentGroups ORDER BY group_id");
        foreach ($groups as $g) {
            $label = "Group {$g['group_id']} - Level {$g['level']}, Year {$g['year']}";
            echo "<option value='{$g['group_id']}'>" . htmlspecialchars($label) . "</option>";
        }
        ?>
      </select>
    </td>
  </tr>
  
  <tr>
    <th>Recurring?</th>
    <td>
      <input type="checkbox" name="is_recurring" id="is_recurring" onchange="toggleRepeat()">
      <label for="is_recurring">Repeat Weekly</label>
    </td>
  </tr>

  <tr class="repeat-row" style="display:none;">
    <th>Repeat On:</th>
    <td>
      <?php
      $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
      foreach ($days as $day) {
          echo "<label><input type='checkbox' name='repeat_days[]' value='$day'> " . ucfirst($day) . "</label> ";
      }
      ?>
    </td>
  </tr>

  <tr class="repeat-row" style="display:none;">
    <th>Repeat Until:</th>
    <td><input type="date" name="repeat_until"></td>
  </tr>

  <tr><th>Date:</th><td><input type="date" name="date" required></td></tr>
  <tr><th>Start Time:</th><td><input type="time" name="start_time" required></td></tr>
  <tr><th>End Time:</th><td><input type="time" name="end_time" required></td></tr>

  

  
</table>

<br>
<button type="submit" class="button-primary">Create Session</button>
<a href="/index.php?page=timetables_calendar" class="button-cancel">Cancel</a>
</form>

<script>
function toggleRepeat() {
  const isChecked = document.getElementById('is_recurring').checked;
  document.querySelectorAll('.repeat-row').forEach(row => {
    row.style.display = isChecked ? '' : 'none';
  });
}
</script>
