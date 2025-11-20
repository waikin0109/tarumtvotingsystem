<?php
$_title = 'Election Registration Form';
require_once __DIR__ . '/../AdminView/adminHeader.php';

// Safety guard
if (!isset($registrationForms) || !is_array($registrationForms)) {
    $registrationForms = [];
}

// Defaults in case controller didn’t set them
$search = $search ?? '';
?>

<div class="container-fluid mt-4 mb-5">

    <!-- Header + Create button -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h2 class="mb-0">Election Registration Form</h2>
            <p class="text-muted small mb-0">
                Manage all registration forms for TARUMT election events.
            </p>
        </div>

        <div>
            <a href="/admin/election-registration-form/create" class="btn btn-primary">
                Create Registration Form (+)
            </a>
        </div>
    </div>

    <!-- Search bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form class="row g-2 align-items-end" method="get" action="/admin/election-registration-form">
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

    <!-- Table -->
    <div class="card mb-4" style="box-shadow:0 0.1rem 1rem rgba(0,0,0,.15);">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="col-sm-1">No.</th>
                            <th class="col-sm-5">Registration Form</th>
                            <th class="col-sm-4">Election Event</th>
                            <th class="col-sm-2">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($registrationForms)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No registration forms found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                                // If you pass a $pager object, use it for numbering; else start from 1
                                $startNumber = isset($pager)
                                    ? (($pager->page - 1) * $pager->limit) + 1
                                    : 1;
                            ?>

                            <?php foreach ($registrationForms as $idx => $form): ?>
                                <?php
                                    $formId   = urlencode($form['registrationFormID'] ?? '');
                                    $title    = $form['registrationFormTitle'] ?? '';
                                    $event    = $form['event_name'] ?? '—';

                                    $startRaw = $form['registerStartDate'] ?? null;
                                    $endRaw   = $form['registerEndDate']   ?? null;

                                    $startTs  = $startRaw ? strtotime($startRaw) : null;
                                    $isLocked = $startTs !== null && $startTs <= time();

                                ?>
                                <tr class="clickable-row" data-href="/admin/election-registration-form/view/<?= $formId ?>">
                                    <td><?= $startNumber + $idx ?></td>
                                    <td>
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($title) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($event) ?></td>
                                    <td onclick="event.stopPropagation()">
                                        <?php if ($isLocked): ?>
                                            <button
                                                class="btn btn-sm btn-warning"
                                                type="button"
                                                disabled
                                                data-bs-toggle="tooltip"
                                                data-bs-title="Unavailable after registration start"
                                            >
                                                Edit
                                            </button>

                                            <button
                                                class="btn btn-sm btn-danger"
                                                type="button"
                                                disabled
                                                data-bs-toggle="tooltip"
                                                data-bs-title="Unavailable after registration start"
                                            >
                                                Delete
                                            </button>
                                        <?php else: ?>
                                            <!-- Normal Edit/Delete -->
                                            <a href="/admin/election-registration-form/edit/<?= $formId ?>"
                                               class="btn btn-sm btn-warning">
                                                Edit
                                            </a>

                                            <form method="POST"
                                                  action="/admin/election-registration-form/delete/<?= $formId ?>"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this registration form?');">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    Delete
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

        <!-- Pager row (optional, only if you pass $pager like electionEvent) -->
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

<!-- Clickable Row + tooltips -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Make table row clickable (except on controls)
    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', e => {
            if (e.target.closest('a, button, input, select, textarea, label, form')) return;
            window.location.href = row.dataset.href;
        });
    });

    // Prevent buttons/forms from triggering row click
    document.querySelectorAll('.clickable-row .btn, .clickable-row form')
        .forEach(el => el.addEventListener('click', e => e.stopPropagation()));

    // Bootstrap tooltips (same as election event view)
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
});
</script>

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>
