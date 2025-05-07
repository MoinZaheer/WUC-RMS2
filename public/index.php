
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DB connection
require_once __DIR__ . '/../app/includes/db.php';

$page = $_GET['page'] ?? 'login';

switch ($page) {
    case 'login':
        require_once __DIR__ . '/../app/pages/login.php';
        break;

    case 'dashboard':
    case 'test_hash':
    case 'students_search':
    case 'students_create':
    case 'students_profile':
    case 'staff_search':
    case 'staff_profile':
    case 'staff_create':
    case 'modules_search':
    case 'module_create':
    case 'module_profile':
    case 'personal_tutor_assign':
    case 'personal_tutors_search':
    case 'tutor_meetings':
    case 'attendance_admin':
    case 'assignments_search':
    case 'assignments_edit':
    case 'assignments_create':
    case 'grades':
    case 'timetables_calendar':
        // Twrapped in layout
        $contentPath = __DIR__ . '/../app/pages/' . $page . '.php';
        $pageTitle = ucfirst(str_replace('_', ' ', $page));

        // fallback if content file doesn't exist
        if (!file_exists($contentPath)) {
            $contentPath = __DIR__ . '/../app/pages/404.php';
            $pageTitle = "Page Not Found";
        }

        require_once __DIR__ . '/../app/includes/layout.php';
        break;

    default:
        require_once __DIR__ . '/../app/pages/404.php';
        break;
}
