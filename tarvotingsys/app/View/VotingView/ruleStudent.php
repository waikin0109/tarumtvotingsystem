<?php
$_title    = 'Rules & Regulations';
$roleUpper = strtoupper($_SESSION['role'] ?? '');

// Header / footer includes based on role
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} elseif ($roleUpper === 'STUDENT') {
    require_once __DIR__ . '/../StudentView/studentHeader.php';
}

// Base URLs
$listBase = ($roleUpper === 'NOMINEE') ? '/nominee/rule'       : '/student/rule';
$viewBase = ($roleUpper === 'NOMINEE') ? '/nominee/rule/view/' : '/student/rule/view/';

// Defaults to avoid undefined
$search       = $search       ?? '';
$filterStatus = $filterStatus ?? '';
?>

<div class="container-fluid mt-4 mb-5">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h2 class="mb-0">Rules &amp; Regulations</h2>
            <p class="text-muted small mb-0">
                View election rules and regulations for TARUMT elections.
            </p>
        </div>
    </div>

    <!-- Search + filter bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form class="row g-2 align-items-end" method="get" action="<?= htmlspecialchars($listBase) ?>">
                <div class="col-md-5">
                    <label for="q" class="form-label mb-1">Search by Rule Title</label>
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
                    <label for="status" class="form-label mb-1">Election Status</label>
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

    <!-- Table card -->
    <div class="card mb-4" style="box-shadow:0 0.1rem 1rem rgba(0,0,0,.15);">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="col-sm-1">No.</th>
                            <th class="col-sm-5">Rule Title</th>
                            <th class="col-sm-4">Election Event</th>
                            <th class="col-sm-2">Election Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($rules)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No rules found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $startNumber = isset($pager)
                                ? (($pager->page - 1) * $pager->limit) + 1
                                : 1;
                            ?>

                            <?php foreach ($rules as $index => $rule): ?>
                                <?php
                                    $statusRaw  = strtoupper(trim($rule['event_status'] ?? ''));
                                    $badgeClass = match ($statusRaw) {
                                        'PENDING'   => 'bg-secondary',
                                        'ONGOING'   => 'bg-warning',
                                        'COMPLETED' => 'bg-success',
                                        default     => 'bg-secondary',
                                    };
                                ?>
                                <tr class="clickable-row"
                                    data-href="<?= htmlspecialchars($viewBase . urlencode($rule['ruleID'] ?? '')) ?>">
                                    <td><?= $startNumber + $index ?></td>
                                    <td>
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($rule['ruleTitle'] ?? '') ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($rule['event_name'] ?? '—') ?></td>
                                    <td>
                                        <?php if (!empty($statusRaw)): ?>
                                            <span class="badge <?= $badgeClass ?>">
                                                <?= htmlspecialchars($statusRaw) ?>
                                            </span>
                                        <?php else: ?>
                                            —
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
                    of <strong><?= $pager->item_count ?></strong> rules
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

<!-- Clickable Row -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', e => {
            if (e.target.closest('a, button, input, select, textarea, label, form')) return;
            const href = row.dataset.href;
            if (href) {
                window.location.href = href;
            }
        });
    });
});
</script>

<?php
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeFooter.php';
} elseif ($roleUpper === 'STUDENT') {
    require_once __DIR__ . '/../StudentView/studentFooter.php';
}
?>
