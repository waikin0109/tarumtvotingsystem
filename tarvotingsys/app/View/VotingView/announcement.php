<?php
$_title = 'Announcement';
require_once __DIR__ . '/../AdminView/adminHeader.php';

// ---- SAFETY GUARD: avoid undefined variable warnings if someone opens this view directly
if (!isset($announcements) || !is_array($announcements)) {
    $announcements = [];
}

$currentAdminId = $_SESSION['accountID'] ?? 0;
$isAdmin = strtoupper($_SESSION['role'] ?? '') === 'ADMIN';
?>

<div>
    <div class="container-fluid d-flex justify-content-between align-items-center mb-4">
        <div class="row w-100">
            <div class="col-sm-6">
                <h2>Announcement</h2>
            </div>
            <div class="col-sm-6">
                <a href="/announcement/create"><button class="btn btn-primary mx-2 me-5 position-absolute end-0">Create
                        (+)</button></a>
            </div>
        </div>
    </div>

    <div class="container-fluid mb-5">
        <div class="bg-light">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col-sm-1">No.</th>
                            <th scope="col-sm-5">Title</th>
                            <th scope="col-sm-2">Sender</th>
                            <th scope="col-sm-2">Date</th>
                            <th scope="col-sm-2">Actions</th>
                        </tr>
                    <tbody>
                        <?php if (empty($announcements)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No announcements found.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1;
                            foreach ($announcements as $a): ?>
                                <?php
                                $id = (int) ($a['announcementID'] ?? 0);
                                $isOwner = $isAdmin && ((int) ($a['accountID'] ?? 0) === $currentAdminId);
                                $status = strtoupper($a['announcementStatus'] ?? '');
                                $publishedAt = $a['publishedAt'] ?? null;
                                $isFuture = $publishedAt ? (strtotime($publishedAt) > time()) : false;
                                $date = $a['publishedAt'] ?: ($a['createdAt'] ?? '');
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <a href="/announcements/<?= $id ?>">
                                            <?= htmlspecialchars($a['title'] ?? '') ?>
                                        </a>
                                        <?php if ($status === 'DRAFT'): ?>
                                            <span class="badge bg-secondary ms-2">Draft</span>
                                        <?php elseif ($status === 'SCHEDULED'): ?>
                                            <span class="badge bg-warning text-dark ms-2">Scheduled</span>
                                        <?php elseif ($status === 'PUBLISHED'): ?>
                                            <span class="badge bg-success ms-2">Published</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($a['senderName'] ?? 'Unknown') ?></td>
                                    <td><?= htmlspecialchars($date) ?></td>

                                    <td class="text-nowrap">
                                        <div class="d-flex flex-wrap gap-2 justify-content-start">
                                            <?php if ($status === 'DRAFT' && $isOwner): ?>
                                                <a class="btn btn-sm btn-outline-primary"
                                                    href="/announcement/edit/<?= $id ?>">Update</a>
                                                <form class="d-inline" method="post" action="/announcement/publish/<?= $id ?>"
                                                    onsubmit="return confirm('Publish now? This cannot be edited later.');">
                                                    <button class="btn btn-sm btn-success">Publish</button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary" disabled>Update</button>
                                            <?php endif; ?>

                                            <?php if ($status === 'SCHEDULED'): ?>
                                                <?php if ($isOwner && $isFuture): ?>
                                                    <form class="d-inline" method="post" action="/announcement/revert/<?= $id ?>"
                                                        onsubmit="return confirm('Unschedule this announcement? The scheduled time will be removed.');">
                                                        <button class="btn btn-sm btn-warning">Unschedule</button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-secondary" disabled>Unschedule</button>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <?php if ($isOwner): ?>
                                                <form class="d-inline" method="post" action="/announcement/delete"
                                                    onsubmit="return confirm('Delete this announcement and all its attachments?');">
                                                    <input type="hidden" name="announcement_id" value="<?= $id ?>">
                                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary" disabled>Delete</button>
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
    </div>
</div>

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>