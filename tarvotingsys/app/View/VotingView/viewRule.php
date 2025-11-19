<?php
$_title = "View Rule Details";
$roleUpper = strtoupper($_SESSION['role'] ?? '');

// ---- Header by role ----
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} elseif ($roleUpper === 'STUDENT') {
    require_once __DIR__ . '/../StudentView/studentHeader.php';
} elseif ($roleUpper === 'ADMIN') {
    require_once __DIR__ . '/../AdminView/adminHeader.php';
}

// ---- Back link by role ----
$backLink = match ($roleUpper) {
    'ADMIN'   => '/admin/rule',
    'STUDENT' => '/student/rule',
    'NOMINEE' => '/nominee/rule',
    default   => '/login'
};

// ---- SAFETY GUARDS (in case controller didn't pass things) ----
if (!isset($electionEvents) || !is_array($electionEvents)) {
    $electionEvents = [];
}

$selectedElectionId = $ruleData['electionID'] ?? null;

// Try to get election event name
if (!isset($election_name) || $election_name === '' || $election_name === null) {
    if (!empty($ruleData['event_name'])) {
        // If your getRuleById joined the event title as event_name
        $election_name = $ruleData['event_name'];
    } elseif ($selectedElectionId !== null) {
        $found = null;
        foreach ($electionEvents as $ev) {
            if ((string)($ev['electionID'] ?? '') === (string)$selectedElectionId) {
                $found = $ev['title'] ?? null;
                break;
            }
        }
        $election_name = $found ?: 'No associated event';
    } else {
        $election_name = 'No associated event';
    }
}

// ---- Safe formatting ----
$createdRaw  = $ruleData['dateCreated'] ?? null;
$dateCreated = $createdRaw ? date('Y-m-d', strtotime($createdRaw)) : '-';

$ruleID    = $ruleData['ruleID'] ?? '-';
$ruleTitle = $ruleData['ruleTitle'] ?? 'Untitled Rule';
$content   = $ruleData['content'] ?? '';
?>

<div class="container-fluid mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Rule Details</h2>
    </div>
    <!-- Header card (similar style to election event view) -->
    <div class="card border-0 mb-4 shadow-sm" style="border-radius: 1.5rem;">
        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">

            <!-- Left side: title + chips -->
            <div class="mb-3 mb-md-0">
                <h2 class="mt-1 mb-2">
                    <?= htmlspecialchars($ruleTitle) ?>
                </h2>

                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                        Rule ID: <?= htmlspecialchars($ruleID) ?>
                    </span>
                    <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                        Election Event: <?= htmlspecialchars($election_name) ?>
                    </span>
                </div>
            </div>

            <!-- Right side: created info -->
            <div class="text-md-end ms-md-4">
                <div class="small text-muted">
                    <div class="fw-semibold">Created</div>
                    <div><?= htmlspecialchars($dateCreated) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content row -->
    <div class="row g-4">

        <!-- Main content -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title mb-3">Rule Content</h5>

                    <?php if (trim($content) !== ''): ?>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($content)) ?></p>
                    <?php else: ?>
                        <p class="mb-0 text-muted fst-italic">No content provided for this rule.</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($roleUpper === 'ADMIN'): ?>
                <p class="text-muted small mt-3 mb-0">
                    Need to update this rule? Use the <strong>Edit Rule</strong> button below.
                </p>
            <?php else: ?>
                <p class="text-muted small mt-3 mb-0">
                    These rules apply to the selected election event. Please read them carefully before you participate.
                </p>
            <?php endif; ?>
        </div>

        <!-- Side card: quick info -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted small mb-3">Rule Information</h6>

                    <dl class="row mb-0 small">
                        <dt class="col-5">Rule ID</dt>
                        <dd class="col-7"><?= htmlspecialchars($ruleID) ?></dd>

                        <dt class="col-5">Election Event</dt>
                        <dd class="col-7"><?= htmlspecialchars($election_name) ?></dd>

                        <dt class="col-5">Created</dt>
                        <dd class="col-7"><?= htmlspecialchars($dateCreated) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom actions -->
    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="<?= htmlspecialchars($backLink) ?>" class="btn btn-outline-secondary">Back</a>
        <?php if ($roleUpper === 'ADMIN'): ?>
            <a href="/admin/rule/edit/<?= urlencode($ruleID) ?>" class="btn btn-primary">Edit Rule</a>
        <?php endif; ?>
    </div>
</div>

<?php
// ---- Footer by role ----
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeFooter.php';
} elseif ($roleUpper === 'STUDENT') {
    require_once __DIR__ . '/../StudentView/studentFooter.php';
} else {
    require_once __DIR__ . '/../AdminView/adminFooter.php';
}
?>
