<?php
$_title   = 'Nominee Lists';
$roleUpper = strtoupper($_SESSION['role'] ?? '');

// Check user role and load respective headers
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} elseif ($roleUpper === 'STUDENT')  {
    require_once __DIR__ . '/../StudentView/studentHeader.php';
}

// Base URL to view final list for a specific election
$viewBase = ($roleUpper === 'NOMINEE')
    ? '/nominee/nominee-final-list/view/'
    : '/student/nominee-final-list/view/';

// Safety guard
if (!isset($nomineeApplications) || !is_array($nomineeApplications)) {
    $nomineeApplications = [];
}

// Current search term (by election event name)
$search = trim($_GET['q'] ?? '');

// Filter nominee applications to include only those with 'PUBLISHED' status
$publishedNomineeApplications = array_filter($nomineeApplications, function ($nomineeApplication) {
    return isset($nomineeApplication['applicationStatus'])
        && $nomineeApplication['applicationStatus'] === 'PUBLISHED';
});

// Remove duplicate election events by using the event name as a key
$uniqueElectionEvents = [];
foreach ($publishedNomineeApplications as $nomineeApplication) {
    $eventName = $nomineeApplication['event_name'] ?? '';
    if (!isset($uniqueElectionEvents[$eventName])) {
        $uniqueElectionEvents[$eventName] = $nomineeApplication;
    }
}
$uniqueNomineeApplications = array_values($uniqueElectionEvents); // Re-index

// Apply search filter (by election event name)
if ($search !== '') {
    $uniqueNomineeApplications = array_values(array_filter(
        $uniqueNomineeApplications,
        function ($row) use ($search) {
            $eventName = $row['event_name'] ?? '';
            return stripos($eventName, $search) !== false;
        }
    ));
}

// --- Simple paging logic (view-level) ---
$totalItems = count($uniqueNomineeApplications);
$perPage    = 10; // change if you want different page size
$page       = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageCount  = (int) ceil($totalItems / $perPage);

if ($page > $pageCount && $pageCount > 0) {
    $page = $pageCount;
}

$offset          = ($page - 1) * $perPage;
$itemsForPage    = array_slice($uniqueNomineeApplications, $offset, $perPage);
$currentCount    = count($itemsForPage);
$from            = ($totalItems === 0) ? 0 : $offset + 1;
$to              = $offset + $currentCount;

// Helper to build URLs with q + page
$baseUrl     = strtok($_SERVER['REQUEST_URI'] ?? '', '?') ?: '';
$queryParams = ['q' => $search];
$buildPageUrl = function (int $pageNumber) use ($baseUrl, $queryParams): string {
    $params = $queryParams;
    $params['page'] = $pageNumber;
    $qs = http_build_query($params);
    return $baseUrl . ($qs ? ('?' . $qs) : '');
};
?>

<div class="container-fluid mt-4 mb-5">

    <!-- Header (same style as nomineeApplication) -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h2 class="mb-0">Nominees' Final Lists</h2>
            <p class="text-muted small mb-0">
                View the official final nominee lists for each published election event.
            </p>
        </div>
    </div>

    <!-- Search bar (similar style) -->
    <div class="card mb-4">
        <div class="card-body">
            <form class="row g-2 align-items-end"
                  method="get"
                  action="<?= htmlspecialchars($baseUrl) ?>">
                <div class="col-md-8">
                    <label for="q" class="form-label mb-1">Search by Election Event</label>
                    <input
                        type="text"
                        id="q"
                        name="q"
                        class="form-control"
                        placeholder="Search Here..."
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>

                <div class="col-md-4 text-md-end">
                    <button type="submit" class="btn btn-outline-primary me-2">
                        Search
                    </button>
                    <?php if ($search !== ''): ?>
                        <a href="<?= htmlspecialchars($baseUrl) ?>" class="btn btn-link text-decoration-none">
                        Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Card + Table (same card feel as nomineeApplication) -->
    <div class="card mb-4" style="box-shadow:0 0.1rem 1rem rgba(0,0,0,.15);">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="col-sm-1">No.</th>
                            <th class="col-sm-11">Election Event (Final Nominee List)</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($itemsForPage)): ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted py-4">
                                    <?php if ($search !== ''): ?>
                                        No published nominee lists matched your search.
                                    <?php else: ?>
                                        No published nominee lists are available yet.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($itemsForPage as $index => $nomineeApplication): ?>
                                <?php
                                    $eventId   = urlencode($nomineeApplication['electionID'] ?? '');
                                    $eventName = $nomineeApplication['event_name'] ?? '—';
                                ?>
                                <tr class="clickable-row"
                                    data-href="<?= $viewBase . $eventId ?>">
                                    <td><?= $from + $index ?></td>
                                    <td><?= htmlspecialchars($eventName) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pager row (similar style to nomineeApplication) -->
        <?php if ($pageCount > 1): ?>
            <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div class="text-muted small">
                    Showing <strong><?= $from ?></strong> to <strong><?= $to ?></strong>
                    of <strong><?= $totalItems ?></strong> election events
                </div>
                <div>
                    <nav aria-label="Nominee final list pagination">
                        <ul class="pagination mb-0">
                            <!-- Prev -->
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link"
                                   href="<?= ($page > 1) ? htmlspecialchars($buildPageUrl($page - 1)) : '#' ?>"
                                   tabindex="-1">
                                    &laquo;
                                </a>
                            </li>

                            <!-- Page numbers -->
                            <?php for ($i = 1; $i <= $pageCount; $i++): ?>
                                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                    <a class="page-link"
                                       href="<?= htmlspecialchars($buildPageUrl($i)) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <!-- Next -->
                            <li class="page-item <?= ($page >= $pageCount) ? 'disabled' : '' ?>">
                                <a class="page-link"
                                   href="<?= ($page < $pageCount) ? htmlspecialchars($buildPageUrl($page + 1)) : '#' ?>">
                                    &raquo;
                                </a>
                            </li>
                        </ul>
                    </nav>
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
