<?php
$_title = "View Election Event Details";
require_once __DIR__ . '/../AdminView/adminHeader.php';
?>

<div class="container mt-4">
    <h2>Election Event Details</h2>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($electionEventData['title'] ?? '') ?></h5>
            <p class="card-text"><strong>Event ID:</strong> <?= htmlspecialchars($electionEventData['electionID'] ?? '') ?></p>
            <p class="card-text"><strong>Description:</strong> <?= nl2br(htmlspecialchars($electionEventData['description'] ?? '')) ?></p>

            <?php $startDateTime = !empty($electionEventData['startDate'])? date('Y-m-d H:i', strtotime($electionEventData['startDate'])): '';
            $endDateTime = !empty($electionEventData['endDate'])? date('Y-m-d H:i', strtotime($electionEventData['endDate'])): '';?>
            <p class="card-text">
                <strong>Start Date:</strong> <?= htmlspecialchars($startDateTime) ?>
            </p>
            <p class="card-text">
                <strong>End Date:</strong> <?= htmlspecialchars($endDateTime) ?>
            </p>
            <p class="card-text"><strong>Date Created:</strong> <?= htmlspecialchars($electionEventData['dateCreated'] ?? '') ?></p>
            <p class="card-text"><strong>Status:</strong> <?= htmlspecialchars($electionEventData['status'] ?? '') ?></p>
            <p class="card-text"><strong>Account ID:</strong> <?= htmlspecialchars($electionEventData['accountID'] ?? '') ?></p>
        </div>
            
            <a href="/election-event/edit/<?= urlencode($electionEventData['electionID'] ?? '') ?>" class="btn btn-primary">Edit Event</a>
            <a href="/election-event" class="btn btn-secondary">Back to Events List</a>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>