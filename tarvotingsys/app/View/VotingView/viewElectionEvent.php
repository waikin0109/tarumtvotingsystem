<?php
$_title = "View Election Event Details";
require_once __DIR__ . '/../AdminView/adminHeader.php';

// ---- Safe formatting ----
$startRaw  = $electionEventData['electionStartDate'] ?? null;
$endRaw    = $electionEventData['electionEndDate'] ?? null;
$createdRaw = $electionEventData['dateCreated'] ?? null;

$startAt   = $startRaw   ? date('Y-m-d H:i', strtotime($startRaw))   : '-';
$endAt     = $endRaw     ? date('Y-m-d H:i', strtotime($endRaw))     : '-';
$dateCreated = $createdRaw ? date('Y-m-d', strtotime($createdRaw)) : '-';

$statusRaw   = $electionEventData['status'] ?? '';
$statusUpper = strtoupper($statusRaw);

// Map status to badge colour
$badgeClass = 'secondary';
if ($statusUpper === 'DRAFT') {
    $badgeClass = 'secondary';
} elseif ($statusUpper === 'SCHEDULED') {
    $badgeClass = 'info';
} elseif (in_array($statusUpper, ['ONGOING', 'ACTIVE'], true)) {
    $badgeClass = 'warning';
} elseif ($statusUpper === 'COMPLETED') {
    $badgeClass = 'success';
}

$eventID   = $electionEventData['electionID'] ?? '-';
$ownerID   = $electionEventData['accountID'] ?? '-';
$ownerName = $electionEventData['creatorName'] ?? '-';
$timezone  = 'Asia/Kuala_Lumpur';
$title     = $electionEventData['title'] ?? 'Untitled Election Event';
$desc      = $electionEventData['description'] ?? '';
?>

<div class="container mt-4 mb-5">

    <!-- Gradient Header -->
    <div class="card border-0 mb-4 shadow-sm"
         style="border-radius: 1.5rem;">
        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">

            <!-- Left side: breadcrumb + title + chips -->
            <div class="mb-3 mb-md-0">
                <h2 class="mt-1 mb-2">
                    <?= htmlspecialchars($title) ?>
                </h2>

                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                        ID: <?= htmlspecialchars($eventID) ?>
                    </span>
                    <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                        Timezone: <?= htmlspecialchars($timezone) ?>
                    </span>
                </div>
            </div>

            <!-- Right side: status + created -->
            <div class="text-md-end ms-md-4">
                <div class="mb-2">
                    <span class="badge bg-<?= $badgeClass ?> rounded-pill px-4 py-2">
                        <?= htmlspecialchars($statusRaw ?: 'Unknown Status') ?>
                    </span>
                </div>
                <div class="small text-white-75">
                    <div class="fw-semibold">Created</div>
                    <div><?= htmlspecialchars($dateCreated) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content row -->
    <div class="row g-4">

        <!-- Schedule + Description -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title mb-4">Schedule</h5>

                    <!-- Starts -->
                    <div class="d-flex align-items-start mb-4">
                        <div class="me-3 mt-1">
                            <span class="d-inline-block rounded-circle bg-success"
                                  style="width: 14px; height: 14px;"></span>
                        </div>
                        <div>
                            <div class="fw-semibold">Starts</div>
                            <div><?= htmlspecialchars($startAt) ?></div>
                        </div>
                    </div>

                    <!-- Ends -->
                    <div class="d-flex align-items-start mb-4">
                        <div class="me-3 mt-1">
                            <span class="d-inline-block rounded-circle bg-primary"
                                  style="width: 14px; height: 14px;"></span>
                        </div>
                        <div>
                            <div class="fw-semibold">Ends</div>
                            <div><?= htmlspecialchars($endAt) ?></div>
                        </div>
                    </div>

                    <hr>

                    <!-- Description -->
                    <h6 class="text-uppercase text-muted small mb-2">Description</h6>
                    <?php if (trim($desc) !== ''): ?>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($desc)) ?></p>
                    <?php else: ?>
                        <p class="mb-0 text-muted fst-italic">No description provided.</p>
                    <?php endif; ?>
                </div>
            </div>

            <p class="text-muted small mt-3 mb-0">
                Review completed? You can edit this event.
            </p>
        </div>

        <!-- Status / Quick facts -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted small mb-3">Status</h6>

                    <div class="mb-3">
                        <span class="badge bg-<?= $badgeClass ?> px-3 py-2">
                            <?= htmlspecialchars($statusRaw ?: 'Unknown Status') ?>
                        </span>
                    </div>

                    <h6 class="text-uppercase text-muted small mb-2">Creation Details</h6>

                    <dl class="row mb-0 small">
                        <dt class="col-5">Event ID</dt>
                        <dd class="col-7"><?= htmlspecialchars($eventID) ?></dd>

                        <dt class="col-5">Owner Account</dt>
                        <dd class="col-7"><?= htmlspecialchars($ownerID) ?> - <?= htmlspecialchars($ownerName) ?></dd>

                        <dt class="col-5">Created</dt>
                        <dd class="col-7"><?= htmlspecialchars($dateCreated) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom actions -->
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="/admin/election-event" class="btn btn-outline-secondary">Back</a>
        <a href="/admin/election-event/edit/<?= urlencode($eventID) ?>" class="btn btn-primary">Edit</a>
    </div>
</div>

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>
