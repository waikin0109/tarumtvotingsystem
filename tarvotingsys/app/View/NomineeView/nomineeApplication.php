<?php
$_title = 'Nominee Registration Form List';
require_once __DIR__ . '/../AdminView/adminHeader.php';
?>

<div>
    <div class="container-fluid d-flex justify-content-between align-items-center mb-4">
        <div class="row w-100">
            <div class="col-sm-7">
                <h2>Nominee Registration Application List</h2>
            </div>
        </div>
        <div class="col-sm-5">
            <a href="/nominee-application/create"><button class="btn btn-primary mx-2 me-5 position-absolute end-0">Create (+)</button></a>
        </div>
    </div>
    
    <div class="container-fluid mb-5">
        <div class="bg-light">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="col-sm-1">No.</th>
                            <th class="col-sm-3">Student Name</th>
                            <th class="col-sm-2">Status</th>
                            <th class="col-sm-4">Election Event</th>
                            <th class="col-sm-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($nomineeApplications)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No nominee applications found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($nomineeApplications as $index => $application): ?>
                                <tr class="clickable-row" data-href="/nominee-application/view/<?= urlencode($application['nomineeApplicationID'] ?? '') ?>">
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($application['fullName'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($application['applicationStatus'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($application['event_name'] ?? '—') ?></td>
                                    <td onclick="event.stopPropagation()">
                                        <a href="/nominee-application/edit/<?= urlencode($application['nomineeApplicationID'] ?? '') ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <form method="POST" action="/nominee-application/accept/<?= urlencode($application['nomineeApplicationID'] ?? '') ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to accept this nominee application?');">
                                            <button type="submit" class="btn btn-sm btn-success">Accept</button>
                                        </form>
                                        <form method="POST" action="/nominee-application/reject/<?= urlencode($application['nomineeApplicationID'] ?? '') ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to reject this nominee application?');">
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


<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?> 