<?php
$_title = 'Nominee Registration Form List';
require_once __DIR__ . '/../AdminView/adminHeader.php';

// Safety guards
if (!isset($nomineeApplications) || !is_array($nomineeApplications)) {
    $nomineeApplications = [];
}

// Defaults in case controller didn’t set them
$search       = $search       ?? '';
$filterStatus = $filterStatus ?? '';
?>

<div class="container-fluid mt-4 mb-5">

    <!-- Header + buttons -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h2 class="mb-0">Nominee Registration Application List</h2>
            <p class="text-muted small mb-0">
                Review and manage nominee applications linked to each election event.
            </p>
        </div>

        <div class="text-md-end">
            <a href="/admin/nominee-application/publish" class="btn btn-outline-primary mx-1">
                Publish
            </a>
            <a href="/admin/nominee-application/create" class="btn btn-primary mx-1">
                Create (+)
            </a>
        </div>
    </div>

    <!-- Search + filter bar (same style as electionEvent) -->
    <div class="card mb-4">
        <div class="card-body">
            <form class="row g-2 align-items-end" method="get" action="/admin/nominee-application">
                <div class="col-md-5">
                    <label for="q" class="form-label mb-1">Search by Student Name</label>
                    <input
                        type="text"
                        id="q"
                        name="q"
                        class="form-control"
                        placeholder="Search Here..."
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>

                <div class="col-md-3">
                    <label for="status" class="form-label mb-1">Application Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="PENDING"   <?= $filterStatus === 'PENDING'   ? 'selected' : '' ?>>Pending</option>
                        <option value="ACCEPTED"  <?= $filterStatus === 'ACCEPTED'  ? 'selected' : '' ?>>Accepted</option>
                        <option value="REJECTED"  <?= $filterStatus === 'REJECTED'  ? 'selected' : '' ?>>Rejected</option>
                        <option value="PUBLISHED" <?= $filterStatus === 'PUBLISHED' ? 'selected' : '' ?>>Published</option>
                    </select>
                </div>

                <div class="col-md-4 text-md-end">
                    <button type="submit" class="btn btn-outline-primary me-2">
                        Search
                    </button>
                    <?php if ($search !== '' || $filterStatus !== ''): ?>
                        <a href="/admin/nominee-application" class="btn btn-link text-decoration-none">
                        Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Table + pager (same card style as electionEvent) -->
    <div class="card mb-4" style="box-shadow:0 0.1rem 1rem rgba(0,0,0,.15);">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="col-sm-1">No.</th>
                            <th class="col-sm-3">Student Name</th>
                            <th class="col-sm-2">Application Status</th>
                            <th class="col-sm-4">Election Event</th>
                            <th class="col-sm-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($nomineeApplications)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No nominee applications found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                            // Row numbering with paging support
                            $startNumber = isset($pager)
                                ? (($pager->page - 1) * $pager->limit) + 1
                                : 1;
                            ?>

                            <?php foreach ($nomineeApplications as $index => $application): ?>
                                <?php
                                    $naId    = urlencode($application['nomineeApplicationID'] ?? '');
                                    $status  = strtoupper($application['applicationStatus'] ?? '');
                                    $eventId = (int)($application['electionID'] ?? 0);
                                    $eventName = $application['event_name'] ?? '—';
                                    $eventPublished = !empty($application['event_has_published']); // 1/0 from SQL

                                    // Badge colour by application status
                                    $badgeClass = match ($status) {
                                        'PENDING'   => 'bg-warning text-dark',
                                        'ACCEPTED'  => 'bg-success',
                                        'REJECTED'  => 'bg-danger',
                                        'PUBLISHED' => 'bg-info text-dark',
                                        default     => 'bg-light text-dark'
                                    };

                                    $isPubApp = ($status === 'PUBLISHED');
                                ?>
                                <tr class="clickable-row"
                                    data-href="/admin/nominee-application/view/<?= $naId ?>">
                                    <td><?= $startNumber + $index ?></td>
                                    <td><?= htmlspecialchars($application['fullName'] ?? '') ?></td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars($application['applicationStatus'] ?? 'Unknown') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($eventName) ?></div>
                                        <?php if ($eventPublished): ?>
                                            <div class="small text-muted mt-1">
                                                <span class="badge bg-success">
                                                    Election Published
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap" onclick="event.stopPropagation()">
                                        <?php if (!$eventPublished): ?>
                                            <!-- Edit allowed before election publish -->
                                            <a href="/admin/nominee-application/edit/<?= $naId ?>"
                                               class="btn btn-sm btn-warning me-1">
                                                Edit
                                            </a>
                                        <?php else: ?>
                                            <!-- Disable Edit after election publish -->
                                            <button type="button"
                                                    class="btn btn-sm btn-warning me-1"
                                                    disabled
                                                    data-bs-toggle="tooltip"
                                                    data-bs-title="Election published — editing disabled">
                                                Edit
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($isPubApp || $eventPublished): ?>
                                            <!-- After election is published: only View list of nominees -->
                                            <a href="/admin/nominee-application/publish/<?= $eventId ?>"
                                               class="btn btn-sm btn-info">
                                                View
                                            </a>
                                        <?php else: ?>
                                            <!-- Before publish: Accept / Reject -->
                                            <form method="POST"
                                                  action="/admin/nominee-application/accept/<?= $naId ?>"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Accept this nominee application?');">
                                                <button type="submit" class="btn btn-sm btn-success me-1">
                                                    Accept
                                                </button>
                                            </form>
                                            <form method="POST"
                                                  action="/admin/nominee-application/reject/<?= $naId ?>"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Reject this nominee application?');">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    Reject
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

        <!-- Pager row (same as electionEvent) -->
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
                    of <strong><?= $pager->item_count ?></strong> nominee applications
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

<!-- Clickable Row + Tooltips -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Row click navigation
    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', e => {
            if (e.target.closest('a, button, input, select, textarea, label, form')) return;
            window.location.href = row.dataset.href;
        });
    });

    // Stop bubbling from action controls
    document.querySelectorAll('.clickable-row .btn, .clickable-row form')
        .forEach(el => el.addEventListener('click', e => e.stopPropagation()));

    // Bootstrap tooltips for disabled Edit button
    if (window.bootstrap && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el);
        });
    }
});
</script>

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>
