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
        <div class="col-sm-5 d-flex justify-content-end">
            <a href="/admin/nominee-application/publish">
                <button class="btn btn-primary mx-2">Publish</button>
            </a>
            <a href="/admin/nominee-application/create">
                <button class="btn btn-primary mx-2">Create (+)</button>
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
                                <tr class="clickable-row" data-href="/admin/nominee-application/view/<?= urlencode($application['nomineeApplicationID'] ?? '') ?>">
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($application['fullName'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($application['applicationStatus'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($application['event_name'] ?? '—') ?></td>
                                    <td onclick="event.stopPropagation()">
                                        <?php
                                            $naId    = urlencode($application['nomineeApplicationID'] ?? '');
                                            $status  = strtoupper($application['applicationStatus'] ?? '');
                                            $eventId = (int)($application['electionID'] ?? 0);
                                            $isPubApp = ($status === 'PUBLISHED');
                                            $eventPublished = !empty($application['event_has_published']); // 1/0 from SQL
                                        ?>

                                        <!-- Edit (optional: disable when event already published) -->
                                        <?php if (!$eventPublished): ?>
                                            <a href="/admin/nominee-application/edit/<?= $naId ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-warning" disabled title="Event published—editing disabled">Edit</button>
                                        <?php endif; ?>

                                        <?php if ($isPubApp || $eventPublished): ?>
                                            <!-- After election is published (regardless of this row's status), only show View -->
                                            <a href="/admin/nominee-application/publish/<?= $eventId ?>" class="btn btn-sm btn-info">View</a>
                                        <?php else: ?>
                                            <!-- Before publish: Accept / Reject available -->
                                            <form method="POST" action="/admin/nominee-application/accept/<?= $naId ?>" class="d-inline"
                                                onsubmit="return confirm('Accept this nominee application?');">
                                            <button type="submit" class="btn btn-sm btn-success">Accept</button>
                                            </form>
                                            <form method="POST" action="/admin/nominee-application/reject/<?= $naId ?>" class="d-inline"
                                                onsubmit="return confirm('Reject this nominee application?');">
                                            <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                            </form>
                                        <?php endif; ?>
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