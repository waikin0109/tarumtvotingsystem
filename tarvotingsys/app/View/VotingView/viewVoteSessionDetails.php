<?php
$_title = 'Voting Session Details';
require_once __DIR__ . '/../AdminView/adminHeader.php';
?>

<div class="container mt-3">
    <h2>Voting Session Details</h2>

    <?php if (isset($voteSession)): ?>
        <div class="card mb-4">
            <div class="card-body">
                <p><strong>Vote Session Name:</strong> <?= htmlspecialchars($voteSession['voteSessionName']) ?></p>
                <p><strong>Seat Type:</strong> <?= htmlspecialchars($voteSession['voteSessionType']) ?></p>
                <p><strong>Start Time:</strong> <?= htmlspecialchars($voteSession['voteSessionStartAt']) ?></p>
                <p><strong>End Time:</strong> <?= htmlspecialchars($voteSession['voteSessionEndAt']) ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($voteSession['voteSessionStatus']) ?></p>
                <p><strong>Election Title:</strong> <?= htmlspecialchars($voteSession['ElectionTitle']) ?></p>
                <p><strong>Election Start Date:</strong> <?= htmlspecialchars($voteSession['electionStartDate']) ?></p>
                <p><strong>Election End Date:</strong> <?= htmlspecialchars($voteSession['electionEndDate']) ?></p>
            </div>
        </div>

        <h4>Races Included</h4>
        <?php if (!empty($races)): ?>
            <div class="list-group">
                <?php foreach ($races as $race): ?>
                    <div class="list-group-item">
                        <p><strong>Race Title:</strong> <?= htmlspecialchars($race['title']) ?></p>
                        <p><strong>Seat Type:</strong> <?= htmlspecialchars($race['seatType']) ?></p>
                        <?php if ($race['facultyName']): ?>
                            <p><strong>Faculty:</strong> <?= htmlspecialchars($race['facultyName']) ?></p>
                        <?php endif; ?>
                        <p><strong>Seats:</strong> <?= htmlspecialchars($race['seatCount']) ?></p>
                        <p><strong>Max Selectable:</strong> <?= htmlspecialchars($race['maxSelectable']) ?></p>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No races are available for this voting session.</p>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-warning">Voting session details not available.</div>
    <?php endif; ?>
    <div class="mt-4">
        <a href="/vote-session" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>