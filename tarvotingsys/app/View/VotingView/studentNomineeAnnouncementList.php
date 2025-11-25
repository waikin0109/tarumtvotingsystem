<?php
$_title = 'Announcement';
$roleUpper = strtoupper($_SESSION['role'] ?? '');

// Header / footer includes based on role
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} elseif ($roleUpper === 'STUDENT') {
    require_once __DIR__ . '/../StudentView/studentHeader.php';
}

// ---- SAFETY GUARDS ----
if (!isset($announcements) || !is_array($announcements)) {
    $announcements = [];
}

$search = $search ?? '';
?>

<div class="container-fluid mt-4 mb-5">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h2 class="mb-0">Announcement</h2>
            <p class="text-muted small mb-0">
                View announcements published in the TARUMT voting system.
            </p>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form class="row g-2 align-items-end" method="get"
                action="<?= htmlspecialchars(strtok($_SERVER['REQUEST_URI'], '?')) ?>">
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
                        <a href="<?= htmlspecialchars(strtok($_SERVER['REQUEST_URI'], '?')) ?>"
                            class="btn btn-link text-decoration-none">
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
                            <th scope="col" class="col-sm-5">Title</th>
                            <th scope="col" class="col-sm-3">Sender</th>
                            <th scope="col" class="col-sm-3">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($announcements)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
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
                                $title = (string) ($a['title'] ?? '');
                                $senderName = (string) ($a['senderName'] ?? 'Unknown');
                                $publishedAt = $a['publishedAt'] ?? null;

                                $dateDisplay = '';
                                if (!empty($publishedAt)) {
                                    $ts = strtotime($publishedAt);
                                    if ($ts !== false) {
                                        $dateDisplay = date('Y-m-d H:i:s', $ts);
                                    } else {
                                        $dateDisplay = $publishedAt;
                                    }
                                }

                                ?>
                                <tr class="clickable-row" data-href="/announcements/public/<?= $id ?>">
                                    <!-- No. -->
                                    <td><?= $startNumber + $index ?></td>

                                    <!-- Title -->
                                    <td><?= htmlspecialchars($title) ?></td>

                                    <!-- Sender -->
                                    <td><?= htmlspecialchars($senderName) ?></td>

                                    <!-- Date -->
                                    <td><?= htmlspecialchars($dateDisplay) ?></td>
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
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeFooter.php';
} elseif ($roleUpper === 'STUDENT') {
    require_once __DIR__ . '/../StudentView/studentFooter.php';
}
?>