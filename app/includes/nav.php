<?php
$role = $_SESSION['role'] ?? '';

echo '<nav class="dashboard-nav">';
echo '<ul>';

if ($role === 'admin') {
    echo <<<HTML
    <li><a href="/index.php?page=dashboard">Dashboard</a></li>

    <li>
      <button>Students <span class="caret">▼</span></button>
      <ul>
        <li><a href="/index.php?page=students_create">Create Student</a></li>
        <li><a href="/index.php?page=students_search">Search Students</a></li>
      </ul>
    </li>

    <li>
      <button>Staff <span class="caret">▼</span></button>
      <ul>
        <li><a href="/index.php?page=staff_create">Create Staff</a></li>
        <li><a href="/index.php?page=staff_search">Search Staff</a></li>
      </ul>
    </li>

    <li><a href="/index.php?page=modules">Modules</a></li>
    <li><a href="/index.php?page=personal_tutors">Personal Tutors</a></li>
    <li><a href="/index.php?page=attendance">Attendance</a></li>
    <li><a href="/index.php?page=assignments">Assignments</a></li>
    <li><a href="/index.php?page=grades">Grades</a></li>
    <li><a href="/index.php?page=timetables">Timetables</a></li>
HTML;
}

elseif ($role === 'student') {
    echo <<<HTML
    <li><a href="/index.php?page=modules">Modules</a></li>
    <li><a href="/index.php?page=personal_tutorials">Personal Tutorials</a></li>
    <li><a href="/index.php?page=attendance">Attendance</a></li>
    <li><a href="/index.php?page=assignments">Assignments</a></li>
    <li><a href="/index.php?page=grades">Grades</a></li>
    <li><a href="/index.php?page=timetable">Timetable</a></li>
HTML;
}

elseif ($role === 'staff') {
    echo <<<HTML
    <li><a href="/index.php?page=modules">Modules</a></li>
    <li><a href="/index.php?page=personal_tutorials">Personal Tutorials</a></li>
    <li><a href="/index.php?page=assignments">Assignments</a></li>
    <li><a href="/index.php?page=grades">Grades</a></li>
    <li><a href="/index.php?page=timetables">Timetables</a></li>
    <li><a href="/index.php?page=attendance">Attendance</a></li>
HTML;
}

echo '</ul>';
echo '</nav>';
?>
