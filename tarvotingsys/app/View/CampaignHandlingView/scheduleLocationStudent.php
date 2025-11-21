<?php
$_title   = 'Schedule Location Event';
$roleUpper = strtoupper($_SESSION['role'] ?? '');

// Header by role
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} elseif ($roleUpper === 'STUDENT')  {
    require_once __DIR__ . '/../StudentView/studentHeader.php';
}

/** @var array $scheduleLocations */
/** @var \Helper\SimplePager|null $pager */
/** @var string $search */

$scheduleLocations = $scheduleLocations ?? [];
$search            = $search ?? '';

// Base URLs
$viewBase = ($roleUpper === 'NOMINEE')
    ? '/nominee/schedule-location/schedule/view/'
    : '/student/schedule-location/schedule/view/';

$listAction = ($roleUpper === 'NOMINEE')
    ? '/nominee/schedule-location'
    : '/student/schedule-location';
?>

<div class="container-fluid mt-4 mb-5">

    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h2 class="mb-0">Nominees' Campaign Timetable</h2>
            <p class="text-muted small mb-0">
                View the campaign and debate schedules for each election event.
            </p>
        </div>

        <?php if ($roleUpper === 'NOMINEE'): ?>
            <div class="text-md-end">
                <a href="/nominee/schedule-location/create" class="btn btn-primary mx-1">
                    Apply (+)
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Search bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form class="row g-2 align-items-end"
                  method="get"
                  action="<?= htmlspecialchars($listAction) ?>">
                <div class="col-md-6">
                    <label for="q" class="form-label mb-1">Search Election Event</label>
                    <input
                        type="text"
                        id="q"
                        name="q"
                        class="form-control"
                        placeholder="Search by election event name..."
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>
                <div class="col-md-6 text-md-end">
                    <button type="submit" class="btn btn-outline-primary me-2 mt-3 mt-md-0">
                        Search
                    </button>
                    <?php if ($search !== ''): ?>
                        <a href="<?= htmlspecialchars($listAction) ?>" class="btn btn-link text-decoration-none mt-3 mt-md-0">
                            Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Card + Table -->
    <div class="card mb-4" style="box-shadow:0 0.1rem 1rem rgba(0,0,0,.15);">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="col-sm-1">No.</th>
                            <th class="col-sm-11">Election Event (Campaign Timetable)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($scheduleLocations)): ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted py-4">
                                    <?php if ($search !== ''): ?>
                                        No election events matched your search.
                                    <?php else: ?>
                                        No schedule location found.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $startNumber = isset($pager)
                                ? (($pager->page - 1) * $pager->limit) + 1
                                : 1;
                            ?>
                            <?php foreach ($scheduleLocations as $index => $scheduleLocation): ?>
                                <?php
                                    $electionId = (int)($scheduleLocation['electionID'] ?? 0);
                                    $eventName  = $scheduleLocation['event_name'] ?? '';
                                ?>
                                <tr class="clickable-row"
                                    data-href="<?= htmlspecialchars($viewBase . $electionId) ?>">
                                    <td><?= $startNumber + $index ?></td>
                                    <td><?= htmlspecialchars($eventName) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pager -->
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

<!-- Clickable Row -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', e => {
            if (e.target.closest('a, button, input, select, textarea, label, form')) return;
            window.location.href = row.dataset.href;
        });
    });
});
</script>

<?php
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeFooter.php';
} elseif ($roleUpper === 'STUDENT')  {
    require_once __DIR__ . '/../StudentView/studentFooter.php';
}
?>
