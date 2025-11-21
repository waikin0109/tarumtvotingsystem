<?php
$_title = "View Schedule Location Application Details";
$roleUpper = strtoupper($_SESSION['role'] ?? '');

// Header by role
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} elseif ($roleUpper === 'STUDENT') {
    require_once __DIR__ . '/../StudentView/studentHeader.php';
} elseif ($roleUpper === 'ADMIN') {
    require_once __DIR__ . '/../AdminView/adminHeader.php';
}

// Back link based on role
$backLink = match ($roleUpper) {
    'ADMIN'   => '/admin/schedule-location',
    'STUDENT' => '/student/schedule-location',
    'NOMINEE' => '/nominee/schedule-location',
    default   => '/login'
};

// Defensive defaults
$eventName     = $schedule['eventName']     ?? 'Untitled Campaign Event';
$electionTitle = $schedule['electionTitle'] ?? '-';
$eventType     = $schedule['eventType']     ?? '-';
$desiredStart  = $schedule['desiredStart']  ?: '—';
$desiredEnd    = $schedule['desiredEnd']    ?: '—';
$submittedAt   = $schedule['submittedAt']   ?: '—';
$statusRaw     = $schedule['status']        ?? 'PENDING';
$badgeClass    = $schedule['badgeClass']    ?? 'bg-secondary';
$adminName     = $schedule['adminName']     ?: '—';
$nomineeName   = $schedule['nomineeName']   ?: '—';
$appId         = (int)($schedule['id'] ?? 0);
?>

<div class="container-fluid mt-4 mb-5">

    <!-- Page title row -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Schedule Location Application</h2>
    </div>

    <!-- Top card (similar style to Election Event Details) -->
    <div class="card border-0 mb-4 shadow-sm" style="border-radius: 1.5rem;">
        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">

            <!-- Left: title + tags -->
            <div class="mb-3 mb-md-0">
                <h2 class="mt-1 mb-2">
                    <?= htmlspecialchars($eventName) ?>
                </h2>

                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                        Election: <?= htmlspecialchars($electionTitle) ?>
                    </span>
                    <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                        Type: <?= htmlspecialchars($eventType) ?>
                    </span>
                </div>
            </div>

            <!-- Right: status + submitted at -->
            <div class="text-md-end ms-md-4">
                <div class="mb-2">
                    <span class="badge <?= htmlspecialchars($badgeClass) ?> rounded-pill px-4 py-2">
                        <?= htmlspecialchars($statusRaw) ?>
                    </span>
                </div>
                <div class="small text-muted">
                    <div class="fw-semibold">Submitted At</div>
                    <div><?= htmlspecialchars($submittedAt) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content row (2 columns like election event view) -->
    <div class="row g-4">

        <!-- Left: timing + basic info -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title mb-4">Event Schedule</h5>

                    <!-- Desired Start -->
                    <div class="d-flex align-items-start mb-4">
                        <div class="me-3 mt-1">
                            <span class="d-inline-block rounded-circle bg-success"
                                  style="width: 14px; height: 14px;"></span>
                        </div>
                        <div>
                            <div class="fw-semibold">Desired Start</div>
                            <div><?= htmlspecialchars($desiredStart) ?></div>
                        </div>
                    </div>

                    <!-- Desired End -->
                    <div class="d-flex align-items-start mb-4">
                        <div class="me-3 mt-1">
                            <span class="d-inline-block rounded-circle bg-primary"
                                  style="width: 14px; height: 14px;"></span>
                        </div>
                        <div>
                            <div class="fw-semibold">Desired End</div>
                            <div><?= htmlspecialchars($desiredEnd) ?></div>
                        </div>
                    </div>

                    <hr>

                    <!-- Extra info -->
                    <h6 class="text-uppercase text-muted small mb-2">Related Election</h6>
                    <p class="mb-2">
                        <?= htmlspecialchars($electionTitle) ?>
                    </p>

                    <h6 class="text-uppercase text-muted small mb-2">Event Type</h6>
                    <p class="mb-0">
                        <?= htmlspecialchars($eventType) ?>
                    </p>
                </div>
            </div>

            <?php if ($roleUpper === 'ADMIN'): ?>
                <p class="text-muted small mt-3 mb-0">
                    Review completed? You may update this application if needed.
                </p>
            <?php else: ?>
                <p class="text-muted small mt-3 mb-0">
                    This page shows the official record of your application.
                </p>
            <?php endif; ?>
        </div>

        <!-- Right: status + people -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted small mb-3">Application Status</h6>

                    <div class="mb-3">
                        <span class="badge <?= htmlspecialchars($badgeClass) ?> px-3 py-2">
                            <?= htmlspecialchars($statusRaw) ?>
                        </span>
                    </div>

                    <h6 class="text-uppercase text-muted small mb-2">People Involved</h6>

                    <dl class="row mb-3 small">
                        <dt class="col-5">Nominee</dt>
                        <dd class="col-7"><?= htmlspecialchars($nomineeName) ?></dd>

                        <dt class="col-5">Admin</dt>
                        <dd class="col-7"><?= htmlspecialchars($adminName) ?></dd>
                    </dl>

                    <h6 class="text-uppercase text-muted small mb-2">Application Info</h6>
                    <dl class="row mb-0 small">
                        <dt class="col-5">Application ID</dt>
                        <dd class="col-7">#<?= htmlspecialchars((string)$appId) ?></dd>

                        <dt class="col-5">Submitted At</dt>
                        <dd class="col-7"><?= htmlspecialchars($submittedAt) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom actions -->
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="<?= htmlspecialchars($backLink) ?>" class="btn btn-outline-secondary">Back</a>
        <?php if ($roleUpper === 'ADMIN'): ?>
            <a href="/admin/schedule-location/edit/<?= (int)$appId ?>" class="btn btn-primary">Edit</a>
        <?php endif; ?>
    </div>
</div>

<?php
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeFooter.php';
} elseif ($roleUpper === 'STUDENT') {
    require_once __DIR__ . '/../StudentView/studentFooter.php';
} else {
    require_once __DIR__ . '/../AdminView/adminFooter.php';
}
?>
