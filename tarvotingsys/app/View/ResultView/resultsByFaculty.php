<?php
/** @var array $election */
/** @var array $rows */

$_title = 'Race Breakdown';
require_once __DIR__ . '/../AdminView/adminHeader.php';

if (!empty($rows)) {
    $raceMeta   = $rows[0];
    $raceTitle  = $raceMeta['raceTitle'];
    $seatType   = $raceMeta['seatType'] === 'FACULTY_REP'
        ? 'Faculty Representative'
        : 'Campus-wide';
    $seatCount  = (int) $raceMeta['seatCount'];
} else {
    $raceTitle  = 'Unknown Race';
    $seatType   = '-';
    $seatCount  = 0;
}

// Sort candidates by votes desc
$candidates = [];
$totalVotes = 0;
foreach ($rows as $row) {
    $candidates[] = [
        'nomineeID'   => (int) $row['nomineeID'],
        'fullName'    => $row['fullName'],
        'facultyCode' => $row['facultyCode'],
        'facultyName' => $row['facultyName'],
        'votes'       => (int) $row['votes'],
    ];
    $totalVotes += (int) $row['votes'];
}

usort($candidates, function ($a, $b) {
    return $b['votes'] <=> $a['votes'];
});

// Mark winners by seatCount
foreach ($candidates as $idx => $cand) {
    $candidates[$idx]['rank']     = $idx + 1;
    $candidates[$idx]['isWinner'] = ($idx < $seatCount);
}

// Prepare data for charts
$chartLabels = array_map(function ($c) {
    return $c['fullName'] . ' (' . ($c['facultyCode'] ?? '-') . ')';
}, $candidates);
$chartVotes = array_map(function ($c) {
    return $c['votes'];
}, $candidates);

?>
<div class="container-fluid mt-4 mb-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h2 class="mb-1">
                Race Breakdown – <?= htmlspecialchars($raceTitle) ?>
            </h2>
            <p class="text-muted mb-0">
                Detailed candidate results for <?= htmlspecialchars($election['title'] ?? 'Election') ?>.
            </p>
        </div>
    </div>

    <!-- Top info card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body row">
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="text-muted small">Race</div>
                <div class="fw-semibold"><?= htmlspecialchars($raceTitle) ?></div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="text-muted small">Seat Type</div>
                <div class="fw-semibold"><?= htmlspecialchars($seatType) ?></div>
            </div>
            <div class="col-md-2 mb-3 mb-md-0">
                <div class="text-muted small">Seats</div>
                <div class="fw-semibold"><?= $seatCount ?></div>
            </div>
            <div class="col-md-2 mb-3 mb-md-0">
                <div class="text-muted small">Total Candidates</div>
                <div class="fw-semibold"><?= count($candidates) ?></div>
            </div>
            <div class="col-md-2">
                <div class="text-muted small">Total Votes</div>
                <div class="fw-semibold"><?= $totalVotes ?></div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Candidate table -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <strong>Candidate Results</strong>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Candidate</th>
                                <th>Faculty</th>
                                <th class="text-end">Votes</th>
                                <th class="text-end">Vote %</th>
                                <th class="text-center">Elected</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($candidates)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No results available for this race.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($candidates as $cand): ?>
                                    <?php
                                    $votePercent = $totalVotes > 0
                                        ? round(($cand['votes'] / $totalVotes) * 100, 2)
                                        : 0.0;
                                    ?>
                                    <tr class="<?= $cand['isWinner'] ? 'table-success' : '' ?>">
                                        <td><?= $cand['rank'] ?></td>
                                        <td><?= htmlspecialchars($cand['fullName']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($cand['facultyCode'] ?? '-') ?>
                                            <small class="text-muted d-block">
                                                <?= htmlspecialchars($cand['facultyName'] ?? '') ?>
                                            </small>
                                        </td>
                                        <td class="text-end"><?= $cand['votes'] ?></td>
                                        <td class="text-end"><?= number_format($votePercent, 2) ?>%</td>
                                        <td class="text-center">
                                            <?php if ($cand['isWinner']): ?>
                                                <span class="badge bg-success">Elected</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border">Not elected</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <strong>Votes per Candidate (Bar)</strong>
                </div>
                <div class="card-body">
                    <canvas id="candidateBarChart" style="max-height: 260px;"></canvas>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <strong>Vote Share % (Pie)</strong>
                </div>
                <div class="card-body">
                    <canvas id="candidatePieChart" style="max-height: 260px;"></canvas>
                </div>
            </div>
        </div>
    </div>

        <div class="d-flex justify-content-center gap-3 mt-4">
        <a href="<?= htmlspecialchars($backUrl ?? '/admin/reports/list') ?>"
            class="btn btn-outline-secondary px-4">Back</a>
        <a href="<?= htmlspecialchars($downloadUrl ?? '#') ?>" class="btn btn-primary px-4">Download
            (<?= htmlspecialchars($currentFormat ?? 'PDF') ?>)</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function () {
        const labels = <?= json_encode($chartLabels) ?>;
        const votes  = <?= json_encode($chartVotes) ?>;

        const barCtx = document.getElementById('candidateBarChart').getContext('2d');
        const pieCtx = document.getElementById('candidatePieChart').getContext('2d');

        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Votes',
                    data: votes
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {legend: {display: false}},
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {precision: 0}
                    }
                }
            }
        });

        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: votes
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    })();
</script>
