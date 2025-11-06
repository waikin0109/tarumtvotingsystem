<?php
$_title = 'Schedule & Location List';
require_once __DIR__ . '/../AdminView/adminHeader.php';

/** @var array $scheduleLocations */
$scheduleLocations = $scheduleLocations ?? [];
?>
<div>
  <div class="container-fluid d-flex justify-content-between align-items-center mb-4">
    <div class="row w-100">
      <div class="col-sm-8">
        <h2>Schedule & Location – Event Applications</h2>
      </div>
    </div>
    <div class="col-sm-4 d-flex justify-content-end">
      <a href="/schedule-location/upload" class="mx-2">
        <button class="btn btn-primary">Upload</button>
      </a>
      <a href="/schedule-location/create" class="mx-2">
        <button class="btn btn-primary">Create (+)</button>
      </a>
    </div>
  </div>

  <div class="container-fluid mb-5">
    <div class="bg-light">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th class="col-sm-1">No.</th>
              <th class="col-sm-2">Event Name</th>
              <th class="col-sm-2">Related Election Event</th>
              <th class="col-sm-1">Status</th>
              <th class="col-sm-2">Nominee Name</th>
              <th class="col-sm-2">Admin Name</th>
              <th class="col-sm-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($scheduleLocations)): ?>
              <tr>
                <td colspan="7" class="text-center text-muted">No schedule locations found.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($scheduleLocations as $index => $sl): 
                $id = urlencode($sl['eventApplicationID'] ?? '');
              ?>
                <tr class="clickable-row" data-href="/schedule-location/view/<?= $id ?>">
                  <td><?= (int)$index + 1 ?></td>
                  <td><?= htmlspecialchars($sl['eventName'] ?? '') ?></td>
                  <td><?= htmlspecialchars($sl['election_event'] ?? '') ?></td>
                  <td><?= htmlspecialchars($sl['eventApplicationStatus'] ?? '') ?></td>
                  <td><?= htmlspecialchars($sl['nominee_fullName'] ?? '') ?></td>
                  <td><?= htmlspecialchars($sl['admin_fullName'] ?? '-') ?></td>
                  <td onclick="event.stopPropagation()">
                    <a class="btn btn-sm btn-outline-primary me-1" href="/schedule-location/edit/<?= $id ?>">Edit</a>

                    <form method="POST" action="/schedule-location/accept/<?= $id ?>" class="d-inline"
                          onsubmit="return confirm('Accept this event application?');">
                      <button type="submit" class="btn btn-sm btn-success">Accept</button>
                    </form>

                    <form method="POST" action="/schedule-location/reject/<?= $id ?>" class="d-inline"
                          onsubmit="return confirm('Reject this event application?');">
                      <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Clickable Row -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.clickable-row').forEach(row => {
    row.addEventListener('click', e => {
      // Ignore clicks on interactive elements so forms/links work
      if (e.target.closest('a, button, input, select, textarea, label, form')) return;
      window.location.href = row.dataset.href;
    });
  });

  // Extra safety: stop row-click bubbling from action controls
  document.querySelectorAll('.clickable-row .btn, .clickable-row form')
    .forEach(el => el.addEventListener('click', e => e.stopPropagation()));
});
</script>

<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>
