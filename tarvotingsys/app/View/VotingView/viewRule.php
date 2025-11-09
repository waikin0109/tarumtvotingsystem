<?php
$_title = "View Rule Details";
$roleUpper = strtoupper($_SESSION['role'] ?? '');

if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} elseif ($roleUpper === 'STUDENT') {
    require_once __DIR__ . '/../StudentView/studentHeader.php';
} elseif ($roleUpper === 'ADMIN') {
    require_once __DIR__ . '/../AdminView/adminHeader.php';
}

$backLink = match ($roleUpper) {
    'ADMIN'   => '/admin/rule',
    'STUDENT' => '/student/rule',
    'NOMINEE' => '/nominee/rule',
    default   => '/login'
};

// ---- SAFETY GUARDS (put right after adminHeader.php include)
if (!isset($electionEvents) || !is_array($electionEvents)) {
    $electionEvents = [];
}

$selectedElectionId = $ruleData['electionID'] ?? null;

// If controller didn't pass $election_name, try to infer it from $ruleData or $electionEvents
if (!isset($election_name) || $election_name === '' || $election_name === null) {
    // Prefer a joined column if your getRuleById joined the event title
    if (!empty($ruleData['event_name'])) {
        $election_name = $ruleData['event_name'];
    } elseif ($selectedElectionId !== null) {
        // Fallback: find title from events list
        $found = null;
        foreach ($electionEvents as $ev) {
            if ((string)($ev['electionID'] ?? '') === (string)$selectedElectionId) {
                $found = $ev['title'] ?? null;
                break;
            }
        }
        $election_name = $found ?: 'Select an event';
    } else {
        $election_name = 'Select an event';
    }
}

?>

<div class="container mt-4">
    <h2>Rule Details</h2>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($ruleData['ruleTitle'] ?? '') ?></h5>
            <p class="card-text"><strong>Rule ID:</strong> <?= htmlspecialchars($ruleData['ruleID'] ?? '') ?></p>
            <p class="card-text"><strong>Content:</strong> <?= nl2br(htmlspecialchars($ruleData['content'] ?? '')) ?></p>
            <p class="card-text"><strong>Associated Election Event:</strong> <?= htmlspecialchars($election_name ?? '—') ?></p>
            <p class="card-text"><strong>Date Created:</strong> <?= htmlspecialchars($ruleData['dateCreated'] ?? '') ?></p>
        </div>
        <div class="card-footer">
            <?php if ($roleUpper === 'ADMIN'): ?>
                <a href="/admin/rule/edit/<?= urlencode($ruleData['ruleID'] ?? '') ?>" class="btn btn-primary">Edit Rule</a>
            <?php endif; ?>
            <a href="<?= $backLink ?>" class="btn btn-secondary">Back to Rules List</a>
        </div>
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
