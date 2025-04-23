<?php
// only content here
if ($_SESSION['role'] === 'admin') {
  include __DIR__ . '/widgets/admin_widgets.php';
} elseif ($_SESSION['role'] === 'staff') {
  include __DIR__ . '/widgets/staff_widgets.php';
} elseif ($_SESSION['role'] === 'student') {
  include __DIR__ . '/widgets/student_widgets.php';
}
?>