<?php
$_title   = 'Election Registration';
$roleUpper = strtoupper($_SESSION['role'] ?? '');

// Check user role and load respective headers
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} elseif ($roleUpper === 'STUDENT')  {
    require_once __DIR__ . '/../StudentView/studentHeader.php';
}

// Safety guards
$filteredRegistrationForms = $filteredRegistrationForms ?? [];
$myAppsByForm              = $myAppsByForm ?? [];
$search                    = $search ?? '';

// Normalise to reuse admin-style code
$registrationForms = $filteredRegistrationForms;

// Base URLs
$listAction = ($roleUpper === 'NOMINEE')
    ? '/nominee/election-registration-form'
    : '/student/election-registration-form';

$viewBase = ($roleUpper === 'NOMINEE')
    ? '/nominee/election-registration-form/view/'
    : '/student/election-registration-form/view/';

$registerBase = ($roleUpper === 'NOMINEE')
    ? '/nominee/election-registration-form/register/'
    : '/student/election-registration-form/register/';
?>

<div class="container-fluid mt-4 mb-5">

    <!-- Header (same style as admin) -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h2 class="mb-0">Election Registration</h2>
            <p class="text-muted small mb-0">
                Find available registration forms and submit your application for TARUMT election events.
            </p>
        </div>
    </div>

    <!-- Search bar (same layout as admin list; using `search` param) -->
    <div class="card mb-4">
        <div class="card-body">
            <form class="row g-2 align-items-end" method="get" action="<?= htmlspecialchars($listAction) ?>">
                <div class="col-md-8">
                    <label for="q" class="form-label mb-1">Search by Registration Form Title</label>
                    <input type="text"
                           id="q"
                           name="q"
                           class="form-control"
                           placeholder="Search Here..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="col-md-4 text-md-end">
                    <button type="submit" class="btn btn-outline-primary me-2">
                        Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Table (same card + shadow style as admin) -->
    <div class="card mb-4" style="box-shadow:0 0.1rem 1rem rgba(0,0,0,.15);">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="col-sm-1 text-center">No.</th>
                            <th class="col-sm-5">Registration Form</th>
                            <th class="col-sm-4">Election Event</th>
                            <th class="col-sm-2 text-center">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($registrationForms)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No registration forms found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                                // Optional pager support (same logic as admin, if you pass $pager)
                                $startNumber = isset($pager)
                                    ? (($pager->page - 1) * $pager->limit) + 1
                                    : 1;
                            ?>

                            <?php foreach ($registrationForms as $idx => $registrationForm): ?>
                                <?php
                                    $formId = (int)($registrationForm['registrationFormID'] ?? 0);
                                    $mine   = $myAppsByForm[$formId] ?? null;
                                ?>
                                <tr>
                                    <td class="text-center"><?= $startNumber + $idx ?></td>
                                    <td>
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($registrationForm['registrationFormTitle'] ?? '') ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($registrationForm['event_name'] ?? '—') ?></td>
                                    <td class="text-center text-nowrap">
                                        <?php if ($mine): ?>
                                            <a href="<?= $viewBase . (int)$mine['nomineeApplicationID'] ?>"
                                               class="btn btn-sm btn-secondary">
                                                View Application
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= $registerBase . urlencode((string)$formId) ?>"
                                               class="btn btn-sm btn-warning">
                                                Register
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Optional pager row if you decide to paginate for students -->
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
                    of <strong><?= $pager->item_count ?></strong> registration forms
                </div>
                <div>
                    <?php
                        // Keep search query in pager links
                        $href = http_build_query(['q' => $search]);
                        $pager->html($href, "class='pagination-wrapper'");
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeFooter.php';
} elseif ($roleUpper === 'STUDENT')  {
    require_once __DIR__ . '/../StudentView/studentFooter.php';
}
?>
