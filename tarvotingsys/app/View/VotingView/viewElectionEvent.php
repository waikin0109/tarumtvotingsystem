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

            <?php
            // Prefer the full DATETIME columns returned by the model:
            $startAt = !empty($electionEventData['electionStartDate'])
            ? date('Y-m-d H:i', strtotime($electionEventData['electionStartDate']))
            : '';

            $endAt = !empty($electionEventData['electionEndDate'])
            ? date('Y-m-d H:i', strtotime($electionEventData['electionEndDate']))
            : '';
            ?>
            <p class="card-text">
            <strong>Start Date & Time:</strong> <?= htmlspecialchars($startAt) ?>
            </p>
            <p class="card-text">
            <strong>End Date Time:</strong> <?= htmlspecialchars($endAt) ?>
            </p>

            <p class="card-text"><strong>Date Created:</strong> <?= htmlspecialchars($electionEventData['dateCreated'] ?? '') ?></p>
            <p class="card-text"><strong>Status:</strong> <?= htmlspecialchars($electionEventData['status'] ?? '') ?></p>
            <p class="card-text">
                <strong>Created By:</strong>
                <?= htmlspecialchars(($electionEventData['accountID'] ?? '')) ?>
                <?php if (!empty($electionEventData['creatorName'])): ?>
                    - <?= htmlspecialchars($electionEventData['creatorName']) ?>
                <?php endif; ?>
            </p>

        </div>
            
            <a href="/admin/election-event/edit/<?= urlencode($electionEventData['electionID'] ?? '') ?>" class="btn btn-primary">Edit Event</a>
            <a href="/admin/election-event" class="btn btn-secondary">Back to Events List</a>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>