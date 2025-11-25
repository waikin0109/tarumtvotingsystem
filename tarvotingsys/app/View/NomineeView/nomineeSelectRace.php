<?php

$_title = 'Choose Race & Seat Type';
require_once __DIR__ . '/../NomineeView/nomineeHeader.php';

// Election label: use title from query if present, else fallback to ID
$electionTitle = $availableRaces[0]['electionTitle'] ?? null;
$electionLabel = $electionTitle
    ? htmlspecialchars($electionTitle)
    : 'Election #' . htmlspecialchars($nomineeBasic['electionID'] ?? '');
?>

<div class="container-fluid mt-4 mb-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h2 class="mb-1">Choose Race &amp; Seat Type</h2>
            <p class="text-muted mb-0">
                Select the race and seat type you wish to contest for this election.
                You can only choose <strong>one</strong> race for this election.
            </p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">
                Election:
                <span class="fw-semibold">
                    <?= $electionLabel ?>
                </span>
            </h5>

            <p class="small text-muted mb-3">
                Your faculty will restrict which <strong>Faculty Representative</strong> races are available.
                You may also see <strong>Campus Wide</strong> races that are open to all faculties.
                This choice will apply to <strong>all voting sessions</strong> for this election
                (early and main), where the race is included.
            </p>

            <form action="/nominee/select-race" method="POST">

                <!-- Optional: keep electionID as hidden for controller convenience -->
                <input type="hidden" name="electionID" value="<?= (int) ($nomineeBasic['electionID'] ?? 0) ?>">

                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:8%">Select</th>
                                <th style="width:20%">Seat Type</th>
                                <th style="width:32%">Race Title</th>
                                <th style="width:20%">Faculty</th>
                                <th style="width:20%">Seat Info</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($availableRaces)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        No races are currently available for your election and faculty.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($availableRaces as $race): ?>
                                    <?php
                                    $seatTypeRaw = $race['seatType'] ?? '';
                                    $seatTypeLabel = $seatTypeRaw !== ''
                                        ? str_replace('_', ' ', $seatTypeRaw)
                                        : '';

                                    $facultyDisplay = '-';
                                    if (!empty($race['facultyName'])) {
                                        $facultyDisplay = $race['facultyName'];
                                    }

                                    $raceID = (int) ($race['raceID'] ?? 0);
                                    ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="radio" name="raceID" value="<?= $raceID ?>"
                                                <?= ($raceID === (int) $currentRaceID) ? 'checked' : '' ?> required>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= htmlspecialchars($seatTypeLabel) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($race['raceTitle'] ?? '') ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($facultyDisplay) ?>
                                        </td>
                                        <td>
                                            <div class="small text-muted">
                                                Seats: <?= (int) ($race['seatCount'] ?? 0) ?>,
                                                Max selectable: <?= (int) ($race['maxSelectable'] ?? 0) ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <a href="/nominee/profile" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Profile
                    </a>

                    <button type="submit" class="btn btn-primary" <?= empty($availableRaces) ? 'disabled' : '' ?>>
                        <i class="bi bi-check-circle"></i>
                        Save Race Selection
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../NomineeView/nomineeFooter.php';
?>