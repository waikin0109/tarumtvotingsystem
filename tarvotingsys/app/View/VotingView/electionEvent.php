<?php
$_title = 'Election Event';
require_once __DIR__ . '/../AdminView/adminHeader.php';

// ---- SAFETY GUARD: avoid undefined variable warnings if someone opens this view directly
if (!isset($electionEvents) || !is_array($electionEvents)) {
    $electionEvents = [];
}
?>

<div>
    <div class="container-fluid d-flex justify-content-between align-items-center mb-4">
        <div class="row w-100">
            <div class="col-sm-6">
                <h2>Election Event</h2>
            </div>
            <div class="col-sm-6">
                <a href="/admin/election-event/create"><button class="btn btn-primary mx-2 me-5 position-absolute end-0">Create (+)</button></a>
            </div>
        </div>
    </div>

    <div class="container-fluid mb-5">
        <div class="bg-light">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="col-sm-1">No.</th>
                            <th class="col-sm-5">Event Name</th>
                            <th class="col-sm-2">Date Created</th>
                            <th class="col-sm-2">Status</th>
                            <th class="col-sm-2">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($electionEvents)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No election events found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($electionEvents as $index => $event): ?>
                                <?php $eventId = urlencode($event['electionID'] ?? ''); ?>
                                <tr class="clickable-row" data-href="/admin/election-event/view/<?= $eventId ?>">
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($event['title'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($event['dateCreated'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($event['status'] ?? '') ?></td>
                                    <td onclick="event.stopPropagation()">
                                        <a href="/admin/election-event/edit/<?= $eventId ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <form method="POST" action="/admin/election-event/delete/<?= $eventId ?>" class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this election event?');">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
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


<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>
