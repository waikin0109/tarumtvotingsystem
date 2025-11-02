<?php
$_title = 'Announcement';
require_once __DIR__ . '/../AdminView/adminHeader.php';

// ---- SAFETY GUARD: avoid undefined variable warnings if someone opens this view directly
if (!isset($announcements) || !is_array($announcements)) {
    $announcements = [];
}

$currentAdminId = $_SESSION['accountID'] ?? 0;
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
                                $isOwner = ((int) $a['accountID'] === (int) $currentAdminId);
                                $status = strtoupper($a['announcementStatus'] ?? '');
                                $date = $a['publishedAt'] ?: $a['createdAt'];
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <a href="/announcements/<?= (int) $a['announcementID'] ?>">
                                            <?= htmlspecialchars($a['title']) ?>
                                        </a>
                                        <?php if ($status === 'DRAFT'): ?>
                                            <span class="badge bg-secondary ms-2">Draft</span>
                                        <?php elseif ($status === 'PUBLISHED'): ?>
                                            <span class="badge bg-success ms-2">Published</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($a['senderName'] ?? 'Unknown') ?></td>
                                    <td><?= htmlspecialchars($date) ?></td>
                                    <td>
                                        <?php if ($status === 'DRAFT' && $isOwner): ?>
                                            <a class="btn btn-sm btn-outline-primary"
                                                href="/announcement/edit/<?= (int) $a['announcementID'] ?>">
                                                Update
                                            </a>
                                            <form class="d-inline" method="post"
                                                action="/announcement/publish/<?= (int) $a['announcementID'] ?>"
                                                onsubmit="return confirm('Publish now? This cannot be edited later.');">
                                                <button class="btn btn-sm btn-success">Publish</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary" disabled>Update</button>
                                        <?php endif; ?>

                                        <?php if ($isOwner): ?>
                                            <form class="d-inline" method="post"
                                                action="/announcement/delete/<?= (int) $a['announcementID'] ?>"
                                                onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary" disabled>Delete</button>
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

<!-- Optional: Clickable Row Script -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.clickable-row').forEach(row => {
            row.addEventListener('click', e => {
                // Ignore clicks on interactive elements so forms/links work
                if (e.target.closest('a, button, input, select, textarea, label, form')) return;
                window.location.href = row.dataset.href;
            });
        });

        // Stop row-click propagation on action elements
        document.querySelectorAll('.clickable-row .btn, .clickable-row form')
            .forEach(el => el.addEventListener('click', e => e.stopPropagation()));
    });
</script>

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>