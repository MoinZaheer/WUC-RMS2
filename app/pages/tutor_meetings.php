<?php
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$student_id = $_GET['student_id'] ?? null;

if (!$student_id) {
    echo "<p>No student ID provided.</p>";
    exit;
}

// Fetch student name
$stmt = $pdo->prepare("SELECT first_name, surname FROM Students WHERE student_id = :student_id");
$stmt->execute(['student_id' => $student_id]);
$student = $stmt->fetch();

if (!$student) {
    echo "<p>Student not found.</p>";
    exit;
}

// Fetch meetings
$stmt = $pdo->prepare("
    SELECT tm.*, 
           st.first_name AS tutor_first, st.surname AS tutor_last,
           r.room_code
    FROM TutorMeetings tm
    LEFT JOIN Staff st ON tm.tutor_id = st.staff_id
    LEFT JOIN Rooms r ON tm.room_id = r.room_id
    WHERE tm.student_id = :student_id
    ORDER BY tm.meeting_date DESC, tm.meeting_time DESC
");
$stmt->execute(['student_id' => $student_id]);
$meetings = $stmt->fetchAll();
?>

<h2>Personal Tutorial Meetings for <?= htmlspecialchars($student['first_name'] . ' ' . $student['surname']) ?> (<?= htmlspecialchars($student_id) ?>)</h2>

<?php if (!empty($meetings)): ?>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Tutor</th>
                <th>Room</th>
                <th>Reason</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($meetings as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['meeting_date']) ?></td>
                    <td><?= htmlspecialchars($m['meeting_time']) ?></td>
                    <td><?= isset($m['tutor_first']) ? htmlspecialchars($m['tutor_first'] . ' ' . $m['tutor_last']) : '—' ?></td>
                    <td><?= htmlspecialchars($m['room_code'] ?? '—') ?></td>
                    <td><?= nl2br(htmlspecialchars($m['reason']?? '')) ?></td>
                    <td><?= nl2br(htmlspecialchars($m['notes'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No meetings found for this student.</p>
<?php endif; ?>

<p style="margin-top: 1rem;"><a href="/index.php?page=personal_tutors_search">← Back to Tutor Assignments</a></p>
