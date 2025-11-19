<?php
$_title = 'Election Event';
require_once __DIR__ . '/../AdminView/adminHeader.php';

// Safety guard
if (!isset($electionEvents) || !is_array($electionEvents)) {
    $electionEvents = [];
}

// Defaults in case controller didn’t set them
$search       = $search       ?? '';
$filterStatus = $filterStatus ?? '';
?>

<div class="container-fluid mt-4 mb-5">

    <!-- Header + Create button -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h2 class="mb-0">Election Event</h2>
            <p class="text-muted small mb-0">
                Manage all election events in TARUMT.
            </p>
        </div>

        <div>
            <a href="/admin/election-event/create" class="btn btn-primary">
                Create Election Event (+)
            </a>
        </div>
    </div>

    <!-- Search + filter bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form class="row g-2 align-items-end" method="get" action="/admin/election-event">
                <div class="col-md-5">
                    <label for="q" class="form-label mb-1">Search by Event Name</label>
                    <input type="text" id="q" name="q" class="form-control" placeholder="Search Here..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="col-md-3">
                    <label for="status" class="form-label mb-1">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="PENDING"   <?= $filterStatus === 'PENDING'   ? 'selected' : '' ?>>Pending</option>
                        <option value="ONGOING"   <?= $filterStatus === 'ONGOING'   ? 'selected' : '' ?>>Ongoing</option>
                        <option value="COMPLETED" <?= $filterStatus === 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>

                <div class="col-md-4 text-md-end">
                    <button type="submit" class="btn btn-outline-primary me-2">
                        Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card mb-4" style="box-shadow:0 0.1rem 1rem rgba(0,0,0,.15);">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="col-sm-1">No.</th>
                            <th class="col-sm-4">Event Name</th>
                            <th class="col-sm-3">Date Created</th>
                            <th class="col-sm-2">Status</th>
                            <th class="col-sm-2">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($electionEvents)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No election events found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $startNumber = isset($pager)
                                ? (($pager->page - 1) * $pager->limit) + 1
                                : 1;
                            ?>
                            <?php foreach ($electionEvents as $idx => $event): ?>
                                <?php
                                    $eventId   = urlencode($event['electionID'] ?? '');
                                    $statusRaw = strtoupper(trim($event['status'] ?? ''));
                                    $isLocked  = in_array($statusRaw, ['ONGOING','COMPLETED'], true);

                                    $badgeClass = match ($statusRaw) {
                                        'PENDING'   => 'bg-secondary',
                                        'ONGOING'   => 'bg-warning',
                                        'COMPLETED' => 'bg-success',
                                        default     => 'bg-secondary',
                                    };
                                ?>
                                <tr class="clickable-row" data-href="/admin/election-event/view/<?= $eventId ?>">
                                    <td><?= $startNumber + $idx ?></td>
                                    <td>
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($event['title'] ?? '') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($event['dateCreated'] ?? '') ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars($statusRaw) ?>
                                        </span>
                                    </td>
                                    <td onclick="event.stopPropagation()">
                                        <!-- Edit -->
                                        <?php if ($isLocked): ?>
                                            <button
                                                class="btn btn-sm btn-warning"
                                                type="button"
                                                disabled
                                                data-bs-toggle="tooltip"
                                                data-bs-title="Unavailable for ongoing/completed events"
                                            >
                                                Edit
                                            </button>
                                        <?php else: ?>
                                            <a href="/admin/election-event/edit/<?= $eventId ?>"
                                               class="btn btn-sm btn-warning">
                                                Edit
                                            </a>
                                        <?php endif; ?>

                                        <!-- Delete -->
                                        <?php if ($isLocked): ?>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-danger"
                                                disabled
                                                data-bs-toggle="tooltip"
                                                data-bs-title="Unavailable for ongoing/completed events"
                                            >
                                                Delete
                                            </button>
                                        <?php else: ?>
                                            <form method="POST"
                                                  action="/admin/election-event/delete/<?= $eventId ?>"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this election event?');">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    Delete
                                                </button>
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

        <!-- Pager row -->
        <?php if (isset($pager) && $pager->page_count > 1): ?>
            <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div class="text-muted small">
                    <?php
                        $from = ($pager->item_count === 0)
                            ? 0
                            : (($pager->page - 1) * $pager->limit) + 1;
                        $to   = ($pager->page - 1) * $pager->limit + $pager->count;
                    ?>
                    Showing <strong><?= $from ?></strong> to <strong><?= $to ?></strong>
                    of <strong><?= $pager->item_count ?></strong> election events
                </div>
                <div>
                    <?php
                        // Keep current filters in pager links
                        $href = http_build_query([
                            'q'      => $search,
                            'status' => $filterStatus,
                        ]);
                        $pager->html($href, "class='pagination-wrapper'");
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Clickable Row + tooltips -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', e => {
            if (e.target.closest('a, button, input, select, textarea, label, form')) return;
            window.location.href = row.dataset.href;
        });
    });

    document.querySelectorAll('.clickable-row .btn, .clickable-row form')
        .forEach(el => el.addEventListener('click', e => e.stopPropagation()));

    // Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
});
</script>

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>
