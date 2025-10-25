<?php
$_title = 'Election Event - Admin Panel';
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
                <a href="/election-event/create"><button class="btn btn-primary mx-2 me-5 position-absolute end-0">Create (+)</button></a>
            </div>
        </div>
    </div>
    <div class="container-fluid mb-5">
        <div class="bg-light">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
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
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($event['title'] ?? '') ?></td>
                                <td><?= htmlspecialchars($event['dateCreated'] ?? '') ?></td>
                                <td><?= htmlspecialchars($event['status'] ?? '') ?></td>
                                <td>
                                    <a href="/election-event/edit?id=<?= urlencode($event['id'] ?? '') ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="/election-event/delete?id=<?= urlencode($event['id'] ?? '') ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this event?');">Delete</a>
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

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
