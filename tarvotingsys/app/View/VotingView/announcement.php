<?php
$_title = 'Announcement';
require_once __DIR__ . '/../AdminView/adminHeader.php';

// ---- SAFETY GUARDS ----
if (!isset($announcements) || !is_array($announcements)) {
    $announcements = [];
}

$search = $search ?? '';
$currentAdminId = (int) ($_SESSION['accountID'] ?? 0);
$isAdmin = strtoupper($_SESSION['role'] ?? '') === 'ADMIN';

?>

<div class="container-fluid mt-4 mb-5">

    <!-- Header + Create Button -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h2 class="mb-0">Announcement</h2>
            <p class="text-muted small mb-0">
                Manage all announcements in TARUMT voting system.
            </p>
        </div>
        <div>
            <a href="/announcement/create" class="btn btn-primary">
                Create Announcement (+)
            </a>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form class="row g-2 align-items-end" method="get" action="/announcements">
                <div class="col-md-6">
                    <label for="q" class="form-label mb-1">Search by Title</label>
                    <input type="text" id="q" name="q" class="form-control" placeholder="Search announcements"
                        value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="col-md-6 text-md-end">
                    <button type="submit" class="btn btn-outline-primary me-2">
                        Search
                    </button>

                    <?php if ($search !== ''): ?>
                        <a href="/announcements" class="btn btn-link text-decoration-none">
                            Reset
                        </a>
                    <?php endif; ?>
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
                            <th scope="col" class="col-sm-1">No.</th>
                            <th scope="col" class="col-sm-4">Title</th>
                            <th scope="col" class="col-sm-2">Sender</th>
                            <th scope="col" class="col-sm-2">Date</th>
                            <th scope="col" class="col-sm-1">Status</th>
                            <th scope="col" class="col-sm-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($announcements)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No announcements found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $startNumber = isset($pager)
                                ? (($pager->page - 1) * $pager->limit) + 1
                                : 1;
                            ?>

                            <?php foreach ($announcements as $index => $a): ?>
                                <?php
                                $id = (int) ($a['announcementID'] ?? 0);
                                $ownerId = (int) ($a['accountID'] ?? 0);
                                $isOwner = $isAdmin && ($ownerId === $currentAdminId);
                                $status = strtoupper($a['announcementStatus'] ?? '');
                                $createdAt = $a['createdAt'] ?? null;
                                $publishedAt = $a['publishedAt'] ?? null;

                                // date column: use published date if available, else created date
                                $date = $publishedAt ?: $createdAt;

                                // is scheduled time still in the future?
                                $isFuture = $publishedAt ? (strtotime($publishedAt) > time()) : false;

                                // badge style like election event
                                $badgeClass = match ($status) {
                                    'DRAFT' => 'bg-secondary',
                                    'SCHEDULED' => 'bg-warning text-dark',
                                    'PUBLISHED' => 'bg-success',
                                    default => 'bg-secondary',
                                };
                                ?>
                                <tr class="clickable-row" data-href="/announcements/<?= $id ?>">
                                    <!-- No. -->
                                    <td><?= $startNumber + $index ?></td>

                                    <!-- Title (link to view details) -->
                                    <td> <?= htmlspecialchars($a['title'] ?? '') ?></td>

                                    <td><?= htmlspecialchars($a['senderName'] ?? 'Unknown') ?></td>

                                    <td><?= htmlspecialchars($date ?? '') ?></td>

                                    <td>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars($status) ?>
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="text-nowrap" onclick="event.stopPropagation()">
                                        <div class="d-flex flex-wrap gap-2 justify-content-start">

                                            <!-- Update / Publish (only for owner + DRAFT) -->
                                            <?php if ($status === 'DRAFT' && $isOwner): ?>
                                                <a class="btn btn-sm btn-warning" href="/announcement/edit/<?= $id ?>">
                                                    Edit
                                                </a>

                                                <form class="d-inline" method="post" action="/announcement/publish/<?= $id ?>"
                                                    onsubmit="return confirm('Publish now? This cannot be edited later.');">
                                                    <button class="btn btn-sm btn-success" type="submit">
                                                        Publish
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-warning" type="button" disabled
                                                    data-bs-toggle="tooltip"
                                                    data-bs-title="Only owner can edit Draft announcements">
                                                    Edit
                                                </button>
                                            <?php endif; ?>

                                            <!-- Unschedule (only owner + SCHEDULED + future) -->
                                            <?php if ($status === 'SCHEDULED'): ?>
                                                <?php if ($isOwner && $isFuture): ?>
                                                    <form class="d-inline" method="post" action="/announcement/revert/<?= $id ?>"
                                                        onsubmit="return confirm('Unschedule this announcement? The scheduled time will be removed.');">
                                                        <button class="btn btn-sm btn-info" type="submit">
                                                            Unschedule
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-info" type="button" disabled data-bs-toggle="tooltip"
                                                        data-bs-title="Only owner can unschedule future announcements">
                                                        Unschedule
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <!-- Delete (only owner) -->
                                            <?php if ($isOwner): ?>
                                                <form class="d-inline" method="post" action="/announcement/delete"
                                                    onsubmit="return confirm('Delete this announcement and all its attachments?');">
                                                    <input type="hidden" name="announcement_id" value="<?= $id ?>">
                                                    <button class="btn btn-sm btn-danger" type="submit">
                                                        Delete
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-danger" type="button" disabled
                                                    data-bs-toggle="tooltip" data-bs-title="Only owner can delete announcements">
                                                    Delete
                                                </button>
                                            <?php endif; ?>

                                        </div>
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
                    $to = ($pager->page - 1) * $pager->limit + $pager->count;
                    ?>
                    Showing <strong><?= $from ?></strong> to <strong><?= $to ?></strong>
                    of <strong><?= $pager->item_count ?></strong> announcements
                </div>
                <div>
                    <?php
                    // Keep current search in pager links
                    $href = http_build_query([
                        'q' => $search,
                    ]);
                    $pager->html($href, "class='pagination-wrapper'");
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

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

        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
    });
</script>

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>