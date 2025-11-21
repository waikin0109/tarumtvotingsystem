<?php
$_title = 'Schedule PENDING Event Applications';
require_once __DIR__ . '/../AdminView/adminHeader.php';
$queue     = $queue     ?? [];
$locations = $locations ?? [];
?>

<div class="container-fluid mt-4 mb-5">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Schedule for Pending Event Application</h2>
    <div class="d-flex gap-2">
      <a href="/admin/schedule-location" class="btn btn-outline-secondary">Back to List</a>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title mb-3">Pending Queue</h5>
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Event Name</th>
              <th>Type</th>
              <th>Desired Start</th>
              <th>Desired End</th>
              <th>Nominee</th>
              <th>Election</th>
              <th style="width: 260px;">Location / Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($queue)): ?>
              <tr><td colspan="7" class="text-center text-muted">No pending applications.</td></tr>
            <?php else: foreach ($queue as $row): 
              $id  = (int)$row['eventApplicationID'];
            ?>
              <tr>
                <td><?= htmlspecialchars($row['eventName']) ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['eventType']) ?></span></td>
                <td>
                  <?php 
                    if (!empty($row['desiredStartDateTime'])) {
                      $d = new DateTime($row['desiredStartDateTime'], new DateTimeZone('Asia/Kuala_Lumpur'));
                      echo $d->format('d M Y, h:i A');
                    } else echo '—';
                  ?>
                </td>
                <td>
                  <?php 
                    if (!empty($row['desiredEndDateTime'])) {
                      $d = new DateTime($row['desiredEndDateTime'], new DateTimeZone('Asia/Kuala_Lumpur'));
                      echo $d->format('d M Y, h:i A');
                    } else echo '—';
                  ?>
                </td>
                <td><?= htmlspecialchars($row['nomineeName']) ?></td>
                <td><?= htmlspecialchars($row['electionTitle']) ?></td>
                <td>
                  <form class="d-flex gap-2" action="/admin/schedule-location/accept/<?= $id ?>" method="POST">
                    <select name="eventLocationID" class="form-select form-select-sm" required>
                      <option value="">Pick location...</option>
                      <?php foreach ($locations as $loc): ?>
                        <option value="<?= (int)$loc['eventLocationID'] ?>">
                          <?= htmlspecialchars($loc['eventLocationName']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-success" type="submit"
                      onclick="return confirm('Accept and schedule this event?');">Accept</button>
                  </form>

                  <form class="mt-1" action="/admin/schedule-location/reject/<?= $id ?>" method="POST"
                        onsubmit="return confirm('Reject this application?');">
                    <button class="btn btn-sm btn-outline-danger w-100" type="submit">Reject</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Calendar -->
  <div class="card">
    <div class="card-body">
      <h5 class="card-title mb-3">Scheduled Events Calendar</h5>
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
    events: async (info, success, failure) => {
      try {
        const res = await fetch('/admin/schedule-location/calendar-feed');
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
    eventClick: async function(info) {
  const eaid = info.event.extendedProps.eventApplicationID;
  if (!eaid) return;

  if (!confirm('Unschedule this event? It will move back to PENDING.')) return;

  try {
    const res = await fetch(`/admin/schedule-location/unschedule/${eaid}`, {
      method: 'POST',
      headers: { 'Accept':'application/json' }
    });
    if (!res.ok) throw new Error('Unschedule failed');
    info.event.remove(); // remove from the calendar UI immediately
    alert('Unscheduled. Application is now PENDING.');
  } catch (e) {
    console.error(e);
    alert('Operation failed.');
  }
}

  });
  calendar.render();
});
</script>


<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>
