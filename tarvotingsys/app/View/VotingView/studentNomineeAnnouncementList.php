<?php
$_title = 'Announcements';
require_once __DIR__ . '/../AdminView/adminHeader.php';

$announcements = $announcements ?? [];
?>

<div class="container-fluid mt-4 mb-5">
    <h2>Announcement</h2>

    <div class="table-responsive mt-3">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:55%">Title</th>
                    <th style="width:25%">Sender</th>
                    <th style="width:20%">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($announcements)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted">No announcements.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($announcements as $a): ?>
                        <?php $id = (int) ($a['announcementID'] ?? 0); ?>
                        <tr>
                            <td>
                                <a href="/announcements/public/<?= $id ?>">
                                    <?= htmlspecialchars($a['title'] ?? '') ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($a['senderName'] ?? 'Unknown') ?></td>
                            <td><?= htmlspecialchars($a['publishedAt'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>