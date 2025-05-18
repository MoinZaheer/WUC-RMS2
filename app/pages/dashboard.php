<?php
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/layout.php';

// stats from the db
$totalStudents = $pdo->query("SELECT COUNT(*) FROM Students WHERE record_status = 'Live'")->fetchColumn();
$totalStaff = $pdo->query("SELECT COUNT(*) FROM Staff WHERE record_status = 'Live'")->fetchColumn();
$totalModules = $pdo->query("SELECT COUNT(*) FROM Modules")->fetchColumn();
$totalSessionsToday = $pdo->prepare("SELECT COUNT(*) FROM Timetables WHERE date = CURDATE()");
$totalSessionsToday->execute();
$totalSessionsToday = $totalSessionsToday->fetchColumn();

// sample announcements
$announcements = [
  ["title" => "Maintenance Downtime", "message" => "The system will be offline Sunday 3-6 AM.", "audience" => "all", "created_at" => "2024-05-10"],
  ["title" => "Exam Deadline", "message" => "All exam grades must be submitted by Friday.", "audience" => "staff", "created_at" => "2024-05-12"]
];

// sample tasks
$tasks = [
  ["task" => "Review new student applications", "due" => "2024-05-15"],
  ["task" => "Schedule June timetable", "due" => "2024-05-20"]
];

// sample timetable list view, mock
$timetableSessions = [
  ["module" => "C1001", "date" => "2024-05-13", "start" => "10:00", "end" => "12:00", "type" => "Lecture"],
  ["module" => "C1003", "date" => "2024-05-13", "start" => "13:00", "end" => "14:30", "type" => "Practical"]
];
?>

<div class="page-content">
  <h1>My Dashboard</h1>

  <div class="stats-row">
    <div class="stat-card">Total Students: <strong><?= $totalStudents ?></strong></div>
    <div class="stat-card">Total Staff: <strong><?= $totalStaff ?></strong></div>
    <div class="stat-card">Modules: <strong><?= $totalModules ?></strong></div>
    <div class="stat-card">Today’s Sessions: <strong><?= $totalSessionsToday ?></strong></div>
  </div>

  <div class="widgets-row">
    <!-- announcements -->
    <div class="widget">
      <h3>Announcements</h3>
      <ul>
        <?php foreach ($announcements as $a): ?>
          <li><strong><?= htmlspecialchars($a['title']) ?>:</strong> <?= htmlspecialchars($a['message']) ?><br><small><?= $a['created_at'] ?> | <?= ucfirst($a['audience']) ?></small></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <!-- task List -->
    <div class="widget">
      <h3>Task List</h3>
      <ul>
        <?php foreach ($tasks as $task): ?>
          <li><?= htmlspecialchars($task['task']) ?><br><small>Due: <?= $task['due'] ?></small></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <!-- course calendar, mock view only -->
    <div class="widget">
      <h3>Course Calendar (This Week)</h3>
      <ul>
        <?php foreach ($timetableSessions as $s): ?>
          <li><strong><?= $s['module'] ?>:</strong> <?= $s['type'] ?> on <?= $s['date'] ?> (<?= $s['start'] ?> - <?= $s['end'] ?>)</li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<style>

</style>
