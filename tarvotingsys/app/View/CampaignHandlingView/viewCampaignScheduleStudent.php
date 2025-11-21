<?php
$_title = 'Schedule Location Event';
$roleUpper = strtoupper($_SESSION['role'] ?? '');

if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} elseif ($roleUpper === 'STUDENT')  {
    require_once __DIR__ . '/../StudentView/studentHeader.php';
}

$backUrl = ($roleUpper === 'NOMINEE')
    ? '/nominee/schedule-location'
    : '/student/schedule-location';

$calendarUrl = ($roleUpper === 'NOMINEE')
    ? '/nominee/schedule-location/calendar-feed'
    : '/student/schedule-location/calendar-feed';

$viewUrl = ($roleUpper === 'NOMINEE')
    ? '/nominee/schedule-location/view/'
    : '/student/schedule-location/view/';
?>

<div class="container-fluid mt-4 mb-5">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">
      Final Campaign Schedule - <?= htmlspecialchars($electionTitle ?? ''); ?>
    </h2>
    <a href="<?= htmlspecialchars($backUrl) ?>" class="btn btn-outline-secondary">Back to List</a>
  </div>


  <div class="card">
    <div class="card-body">
      <div id="calendar"></div>
    </div>
  </div>
</div>

<!-- FullCalendar (CDN) -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', async function () {
  const calendarEl = document.getElementById('calendar');
  const electionId = <?= (int)($electionId ?? 0); ?>;

  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    height: 'auto',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short' },

    events: async (info, success, failure) => {
      try {
        const res = await fetch(
          '<?= htmlspecialchars($calendarUrl) ?>?electionID=' + encodeURIComponent(electionId)
        );
        if (!res.ok) throw new Error('Failed to load events');
        const data = await res.json();

        const evs = data.map(e => ({
          id: e.eventID,
          title: `${e.eventType}: ${e.eventName} @ ${e.eventLocationName}`,
          start: e.eventStartDateTime,
          end: e.eventEndDateTime,
          extendedProps: {
            eventApplicationID: e.eventApplicationID
          }
        }));

        success(evs);
      } catch (err) {
        console.error(err);
        failure(err);
      }
    },

    eventClick: function(info) {
      const eaid = info.event.extendedProps.eventApplicationID;
      if (!eaid) return;

      const baseViewUrl = "<?= $viewUrl ?>";
      window.location.href = baseViewUrl + eaid;
    }
  });

  calendar.render();
});

</script>

<?php
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeFooter.php';
} elseif ($roleUpper === 'STUDENT')  {
    require_once __DIR__ . '/../StudentView/studentFooter.php';
}
?>
