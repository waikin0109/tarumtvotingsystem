<?php
$_title = "View Registration Form";
require_once __DIR__ . '/../AdminView/adminHeader.php';

// ---- Safe formatting ----
$startRaw     = $registrationFormData['registerStartDate'] ?? null;
$endRaw       = $registrationFormData['registerEndDate'] ?? null;
$createdRaw   = $registrationFormData['dateCreated'] ?? null;

$startAt      = $startRaw   ? date('Y-m-d H:i', strtotime($startRaw))   : '-';
$endAt        = $endRaw     ? date('Y-m-d H:i', strtotime($endRaw))     : '-';
$dateCreated  = $createdRaw ? date('Y-m-d',    strtotime($createdRaw))  : '-';

$formID       = $registrationFormData['registrationFormID'] ?? '-';
$formTitle    = $registrationFormData['registrationFormTitle'] ?? 'Untitled Registration Form';
$eventName    = $registrationFormData['event_name'] ?? 'No linked election event';
$timezone     = 'Asia/Kuala_Lumpur';

// Attributes list (safe guard)
$registrationFormAttributes = $registrationFormAttributes ?? [];

// Optional status mapping (if your table has a status column)
$statusRaw   = $registrationFormData['status'] ?? '';
$statusUpper = strtoupper($statusRaw);

$badgeClass = 'secondary';
if ($statusUpper === 'DRAFT') {
    $badgeClass = 'secondary';
} elseif (in_array($statusUpper, ['ACTIVE', 'OPEN'], true)) {
    $badgeClass = 'success';
} elseif (in_array($statusUpper, ['CLOSED', 'INACTIVE', 'EXPIRED'], true)) {
    $badgeClass = 'danger';
}

// Created by display
$adminId   = $registrationFormData['adminID']    ?? '';
$adminName = $registrationFormData['admin_name'] ?? '';
$createdBy = trim($adminId) !== ''
    ? ($adminName !== '' ? "{$adminId} - {$adminName}" : (string)$adminId)
    : ($adminName !== '' ? $adminName : 'Unknown');
?>

<div class="container-fluid mt-4 mb-5">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Registration Form Details</h2>
    </div>

    <!-- Header Card -->
    <div class="card border-0 mb-4 shadow-sm"
         style="border-radius: 1.5rem;">

        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">

            <!-- Left side: title + chips -->
            <div class="mb-3 mb-md-0">
                <h2 class="mt-1 mb-2">
                    <?= htmlspecialchars($formTitle) ?>
                </h2>

                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                        Form ID: <?= htmlspecialchars($formID) ?>
                    </span>
                    <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                        Timezone: <?= htmlspecialchars($timezone) ?>
                    </span>
                </div>
            </div>

            <!-- Right side: status + created date -->
            <div class="text-md-end ms-md-4">
                <?php if ($statusRaw !== ''): ?>
                    <div class="mb-2">
                        <span class="badge bg-<?= $badgeClass ?> rounded-pill px-4 py-2">
                            <?= htmlspecialchars($statusRaw) ?>
                        </span>
                    </div>
                <?php endif; ?>
                <div class="small text-muted">
                    <div class="fw-semibold">Created</div>
                    <div><?= htmlspecialchars($dateCreated) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content row -->
    <div class="row g-4">

        <!-- Schedule + Attributes -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title mb-4">Registration Window</h5>

                    <!-- Opens -->
                    <div class="d-flex align-items-start mb-4">
                        <div class="me-3 mt-1">
                            <span class="d-inline-block rounded-circle bg-success"
                                  style="width: 14px; height: 14px;"></span>
                        </div>
                        <div>
                            <div class="fw-semibold">Registration Start Date</div>
                            <div><?= htmlspecialchars($startAt) ?></div>
                        </div>
                    </div>

                    <!-- Closes -->
                    <div class="d-flex align-items-start mb-4">
                        <div class="me-3 mt-1">
                            <span class="d-inline-block rounded-circle bg-primary"
                                  style="width: 14px; height: 14px;"></span>
                        </div>
                        <div>
                            <div class="fw-semibold">Registration End Date</div>
                            <div><?= htmlspecialchars($endAt) ?></div>
                        </div>
                    </div>

                    <hr>

                    <!-- Attributes -->
                    <h6 class="text-uppercase text-muted small mb-2">Form Attributes</h6>

                    <?php if (!empty($registrationFormAttributes)): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($registrationFormAttributes as $attr): ?>
                                <span class="badge bg-light text-dark border rounded-pill px-3 py-2">
                                    <?= htmlspecialchars($attr) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="mb-0 text-muted fst-italic">No custom attributes defined for this form.</p>
                    <?php endif; ?>
                </div>
            </div>

            <p class="text-muted small mt-3 mb-0">
                Review completed? You can edit this registration form.
            </p>
        </div>

        <!-- Quick facts / Creation details -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted small mb-3">Form Info</h6>

                    <dl class="row mb-3 small">
                        <dt class="col-5">Form ID</dt>
                        <dd class="col-7"><?= htmlspecialchars($formID) ?></dd>

                        <dt class="col-5">Linked Election</dt>
                        <dd class="col-7"><?= htmlspecialchars($eventName) ?></dd>

                        <dt class="col-5">Registration Start Date</dt>
                        <dd class="col-7"><?= htmlspecialchars($startAt) ?></dd>

                        <dt class="col-5">Registration End Date</dt>
                        <dd class="col-7"><?= htmlspecialchars($endAt) ?></dd>
                    </dl>

                    <h6 class="text-uppercase text-muted small mb-2">Creation Details</h6>

                    <dl class="row mb-0 small">
                        <dt class="col-5">Created</dt>
                        <dd class="col-7"><?= htmlspecialchars($dateCreated) ?></dd>

                        <dt class="col-5">Created By</dt>
                        <dd class="col-7"><?= htmlspecialchars($createdBy) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom actions -->
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="/admin/election-registration-form" class="btn btn-outline-secondary">
            Back to Registration Forms
        </a>
        <a href="/admin/election-registration-form/edit/<?= urlencode($formID) ?>" class="btn btn-primary">
            Edit Registration Form
        </a>
    </div>
</div>

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>
