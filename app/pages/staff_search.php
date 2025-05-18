<?php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

// selected columns
if (isset($_GET['columns'])) {
    $selected_columns = $_GET['columns'];
    $_SESSION['staff_selected_columns'] = $selected_columns;
} elseif (isset($_SESSION['staff_selected_columns'])) {
    $selected_columns = $_SESSION['staff_selected_columns'];
} else {
    $selected_columns = ['staff_id', 'name', 'email', 'phone', 'roles', 'record_status']; // default
}

// Available columns
$available_columns = [
    'staff_id' => 'Staff ID',
    'name' => 'Name',
    'email' => 'Email',
    'phone' => 'Phone',
    'address' => 'Address',
    'roles' => 'Roles',
    'specialist_subjects' => 'Specialist Subjects',
    'record_status' => 'Record Status'
];

// sql
$sql = "SELECT staff_id, first_name, middle_name, surname, email, phone, street, town, city, postcode, roles, specialist_subjects, record_status FROM Staff WHERE 1=1";

$params = [];
$conditions = [];

if (!empty($search)) {
    $keywords = preg_split('/\s+/', trim($search));
    $keyword_conditions = [];
    $i = 0;
    foreach ($keywords as $word) {
        $keyword_conditions[] = "(
            staff_id LIKE :kw_id_$i OR
            first_name LIKE :kw_first_$i OR
            middle_name LIKE :kw_middle_$i OR
            surname LIKE :kw_last_$i
        )";
        $params["kw_id_$i"] = "%$word%";
        $params["kw_first_$i"] = "%$word%";
        $params["kw_middle_$i"] = "%$word%";
        $params["kw_last_$i"] = "%$word%";
        $i++;
    }
    $conditions[] = '(' . implode(' AND ', $keyword_conditions) . ')';
}

if (!empty($status)) {
    $conditions[] = "record_status = :status";
    $params['status'] = $status;
}

if (!empty($conditions)) {
    $sql .= " AND " . implode(' AND ', $conditions);
}

// exec query
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $staff = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<pre>SQL Error: " . $e->getMessage() . "</pre>";
    exit;
}
?>

<h2>Search Staff</h2>

<?php if (isset($_GET['message'])): ?>
  <div class="toast">
    <?= htmlspecialchars($_GET['message']) ?>
  </div>
<?php endif; ?>


<!-- search n Filters -->
<form method="get" action="/index.php" class="search-form">
  <input type="hidden" name="page" value="staff_search">

  <input type="text" name="search" placeholder="Search by ID or Name" value="<?= htmlspecialchars($search) ?>">

  <select name="status" class="select2">
    <option value="">All Statuses</option>
    <?php foreach (['Live', 'Dormant'] as $stat): ?>
      <option value="<?= $stat ?>" <?= $status === $stat ? 'selected' : '' ?>><?= $stat ?></option>
    <?php endforeach; ?>
  </select>

  <button type="submit">Search</button>

  <!-- customise columns dropdown -->
  <div class="columns-dropdown-container" >
    <button type="button" onclick="toggleColumnsDropdown()">Customise Columns ⬇</button>

    <div id="columnsDropdown" class="columns-dropdown" >
      <?php foreach ($available_columns as $col => $label): ?>
        <div>
          <label>
            <input type="checkbox" name="columns[]" value="<?= $col ?>" <?= in_array($col, $selected_columns) ? 'checked' : '' ?>>
            <?= $label ?>
          </label>
        </div>
      <?php endforeach; ?>
      <div style="margin-top: 0.5rem;">
        <button type="submit">Apply</button>
      </div>
    </div>
  </div>
</form>

<!-- table -->
<?php if (!empty($staff)): ?>
  <table class="search-table">
    <thead>
      <tr>
        <?php foreach ($selected_columns as $col_key): ?>
          <th><?= htmlspecialchars($available_columns[$col_key] ?? ucfirst($col_key)) ?></th>
        <?php endforeach; ?>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($staff as $s): ?>
        <tr>
          <?php foreach ($selected_columns as $col_key): ?>
            <td>
              <?php
              switch ($col_key) {
                  case 'staff_id':
                      echo htmlspecialchars($s['staff_id'] ?? '');
                      break;
                  case 'name':
                      echo htmlspecialchars(trim(($s['first_name'] ?? '') . ' ' . ($s['middle_name'] ?? '') . ' ' . ($s['surname'] ?? '')));
                      break;
                  case 'email':
                      echo htmlspecialchars($s['email'] ?? '');
                      break;
                  case 'phone':
                      echo htmlspecialchars($s['phone'] ?? '');
                      break;
                  case 'address':
                      echo htmlspecialchars(trim(($s['street'] ?? '') . ', ' . ($s['town'] ?? '') . ', ' . ($s['city'] ?? '') . ', ' . ($s['postcode'] ?? '')));
                      break;
                  case 'roles':
                      echo htmlspecialchars($s['roles'] ?? '');
                      break;
                  case 'specialist_subjects':
                      echo htmlspecialchars($s['specialist_subjects'] ?? '');
                      break;
                  case 'record_status':
                      echo htmlspecialchars($s['record_status'] ?? '');
                      break;
              }
              ?>
            </td>
          <?php endforeach; ?>
          <td><a href="/index.php?page=staff_profile&id=<?= urlencode($s['staff_id']) ?>">View / Edit</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <p>No staff found.</p>
<?php endif; ?>

<script>
function toggleColumnsDropdown() {
    const dropdown = document.getElementById('columnsDropdown');
    dropdown.style.display = (dropdown.style.display === 'none' || dropdown.style.display === '') ? 'block' : 'none';
}
</script>
