<?php
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/layout.php';

// Live statistics
$totalStudents = $pdo->query("SELECT COUNT(*) FROM Students WHERE record_status = 'Live'")->fetchColumn();
$totalStaff = $pdo->query("SELECT COUNT(*) FROM Staff WHERE record_status = 'Live'")->fetchColumn();
$totalModules = $pdo->query("SELECT COUNT(*) FROM Modules")->fetchColumn();
$totalSessionsToday = $pdo->prepare("SELECT COUNT(*) FROM Timetables WHERE date = CURDATE()");
$totalSessionsToday->execute();
$totalSessionsToday = $totalSessionsToday->fetchColumn();

// Sample announcements
$announcements = [
  ["title" => "Maintenance Downtime", "message" => "The system will be offline Sunday 3-6 AM.", "audience" => "all", "created_at" => "2024-05-10"],
  ["title" => "Exam Deadline", "message" => "All exam grades must be submitted by Friday.", "audience" => "staff", "created_at" => "2024-05-12"]
];

// Sample tasks
$tasks = [
  ["task" => "Review new student applications", "due" => "2024-05-15"],
  ["task" => "Schedule June timetable", "due" => "2024-05-20"]
];

// Sample timetable list view (mocked from recent sessions)
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
    <!-- Announcements -->
    <div class="widget">
      <h3>Announcements</h3>
      <ul>
        <?php foreach ($announcements as $a): ?>
          <li><strong><?= htmlspecialchars($a['title']) ?>:</strong> <?= htmlspecialchars($a['message']) ?><br><small><?= $a['created_at'] ?> | <?= ucfirst($a['audience']) ?></small></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <!-- Task List -->
    <div class="widget">
      <h3>Task List</h3>
      <ul>
        <?php foreach ($tasks as $task): ?>
          <li><?= htmlspecialchars($task['task']) ?><br><small>Due: <?= $task['due'] ?></small></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <!-- Course Calendar (list view mock) -->
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
  .stats-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
  }
  .stat-card {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.1);
    flex: 1;
    font-size: 1.2rem;
  }
  .widgets-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1.5rem;
  }
  .widget {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.1);
  }
  .widget ul {
    padding-left: 1rem;
  }
  .widget ul li {
    margin-bottom: 0.75rem;
  }
</style>
