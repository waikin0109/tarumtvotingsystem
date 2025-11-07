<?php
$_title = 'View Final Schedule';
require_once __DIR__ . '/../AdminView/adminHeader.php';
?>

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Final Campaign Schedule</h2>
    <a href="/schedule-location" class="btn btn-outline-secondary">Back to List</a>
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

  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    height: 'auto',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short' },

    // Same feed as your Schedule Board
    events: async (info, success, failure) => {
      try {
        const res = await fetch('/schedule-location/calendar-feed');
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

    // Click -> go to the event application details page
    eventClick: function(info) {
      const eaid = info.event.extendedProps.eventApplicationID;
      if (!eaid) return;
      window.location.href = `/schedule-location/view/${eaid}`;
    }
  });

  calendar.render();
});
</script>

<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>
