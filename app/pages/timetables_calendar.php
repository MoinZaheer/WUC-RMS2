<?php
require_once __DIR__ . '/../includes/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$modules = $pdo->query("SELECT module_id, module_code, module_title FROM Modules ORDER BY module_code")->fetchAll();
$staff = $pdo->query("SELECT staff_id, first_name, surname FROM Staff ORDER BY surname")->fetchAll();
$groups = $pdo->query("SELECT DISTINCT group_number FROM Timetables WHERE group_number IS NOT NULL ORDER BY group_number")->fetchAll();
$type_options = ['Lecture', 'Practical', 'Exam', 'Event'];

$module_id = $_GET['module_id'] ?? '';
$staff_id = $_GET['staff_id'] ?? '';
$group = $_GET['group'] ?? '';
$type = $_GET['type'] ?? '';
?>

<h2>Timetable</h2>

<form method="get" action="/index.php" style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
    <input type="hidden" name="page" value="timetables_calendar">

    <select name="module_id">
        <option value="">All Modules</option>
        <?php foreach ($modules as $m): ?>
            <option value="<?= $m['module_id'] ?>" <?= $m['module_id'] == $module_id ? 'selected' : '' ?>>
                <?= htmlspecialchars($m['module_code'] . ' - ' . $m['module_title']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="staff_id">
        <option value="">All Staff</option>
        <?php foreach ($staff as $s): ?>
            <option value="<?= $s['staff_id'] ?>" <?= $s['staff_id'] == $staff_id ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['first_name'] . ' ' . $s['surname']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="group">
        <option value="">All Groups</option>
        <?php foreach ($groups as $g): ?>
            <option value="<?= $g['group_number'] ?>" <?= $g['group_number'] == $group ? 'selected' : '' ?>>
                Group <?= htmlspecialchars($g['group_number']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="type">
        <option value="">All Types</option>
        <?php foreach ($type_options as $t): ?>
            <option value="<?= $t ?>" <?= $t == $type ? 'selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="button-primary">Apply Filters</button>
    <a href="/index.php?page=session_create" class="button-primary">Add Session</a>
</form>

<div id="calendar"></div>
<div id="fcTooltip" class="fc-custom-tooltip"></div>

<!-- FullCalendar -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const calendarEl = document.getElementById('calendar');

  const baseParams = new URLSearchParams();
  <?php foreach (['module_id', 'staff_id', 'group', 'type'] as $key): ?>
    baseParams.set('<?= $key ?>', '<?= $_GET[$key] ?? '' ?>');
  <?php endforeach; ?>
  baseParams.set('page', 'timetable_events');



  const calendar = new FullCalendar.Calendar(calendarEl, {
    firstDay: 1,
    initialView: 'listWeek',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'timeGridWeek,dayGridMonth,listWeek'
    },
    slotLabelFormat: {
      hour: '2-digit',
      minute: '2-digit',
      hour12: true
    },
    views: {
        timeGridWeek: {
            dayHeaderFormat: {
            weekday: 'short',
            day: 'numeric',
            month: 'short'
            }
        },
        dayGridMonth: {
            dayHeaderFormat: {
            weekday: 'short'
            }
        }
    },
    events: '/index.php?' + baseParams.toString(),

    // currently turning off eventClick
    
    // eventClick: function(info) {
    //   const id = info.event.id;
    //   window.open('/index.php?page=session_profile&id=' + encodeURIComponent(id), '_blank');
    // },
    eventDidMount: function(info) {

        const start = new Date(info.event.start);
        const end = new Date(info.event.end);

        const formatTime = date => date.toLocaleTimeString('en-GB', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });

        const props = info.event.extendedProps;

        const tooltip = `${info.event.title}
        ${formatTime(start)} – ${formatTime(end)}
        Room: ${props.room_code}
        Group: ${props.group_number ?? '—'}`;

        const tooltipEl = document.getElementById('fcTooltip');
        info.el.addEventListener('mouseenter', function(e) {
            tooltipEl.innerText = tooltip;
            tooltipEl.style.display = 'block';
        });
        info.el.addEventListener('mousemove', function(e) {
            tooltipEl.style.top = (e.pageY + 10) + 'px';
            tooltipEl.style.left = (e.pageX + 10) + 'px';
        });
        info.el.addEventListener('mouseleave', function() {
            tooltipEl.style.display = 'none';
        });
    },
    // eventContent: function(arg) {
            
    //     const props = arg.event.extendedProps;

    //     const title = document.createElement('div');
    //     title.innerText = arg.event.title;

    //     const meta = document.createElement('div');
    //     meta.style.fontSize = '0.8em';
    //     meta.style.marginTop = '4px';
    //     meta.style.color = '#555';
    //     meta.innerText = `Room: ${props.room_code ?? '—'} | Group: ${props.group_number ?? '—'}`;

    //     return { domNodes: [title, meta] };
    // },

    height: 'auto'
  });

  calendar.render();
  
});
</script>

