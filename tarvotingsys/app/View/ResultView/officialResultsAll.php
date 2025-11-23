<?php
/** @var array $election */
/** @var array $racesResults */
/** @var array|null $turnoutSummary */

$_title = 'Results Summary';
require_once __DIR__ . '/../AdminView/adminHeader.php';

$totalRaces  = count($racesResults);
$seatsFilled = 0;

foreach ($racesResults as $race) {
    $seatsFilled += (int) ($race['seatCount'] ?? 0);
}

$turnoutPercent = $turnoutSummary['turnoutPercent'] ?? null;

// Prepare chart payload for JS (votes per candidate by race)
$chartPayload = [];
foreach ($racesResults as $race) {
    $raceID   = (int) $race['raceID'];
    $labels   = [];
    $votes    = [];

    foreach ($race['candidates'] as $cand) {
        $labels[] = $cand['fullName'] . ' (' . ($cand['facultyCode'] ?? '-') . ')';
        $votes[]  = (int) $cand['votes'];
    }

    $chartPayload[$raceID] = [
        'labels' => $labels,
        'votes'  => $votes,
    ];
}

$initialRaceID = $racesResults[0]['raceID'] ?? null;
?>
<div class="container-fluid mt-4 mb-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h2 class="mb-1">
                Results Summary – <?= htmlspecialchars($election['title'] ?? 'Election') ?>
            </h2>
            <p class="text-muted mb-0">
                Official results by race, including winners and vote distribution.
            </p>
        </div>
    </div>

    <!-- KPI cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Races</div>
                    <div class="fs-3 fw-semibold"><?= $totalRaces ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Seats Filled</div>
                    <div class="fs-3 fw-semibold"><?= $seatsFilled ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Overall Turnout</div>
                    <?php if ($turnoutPercent !== null): ?>
                        <div class="fs-3 fw-semibold"><?= htmlspecialchars($turnoutPercent) ?>%</div>
                    <?php else: ?>
                        <div class="fs-6 text-muted">N/A</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content: left table + right chart -->
    <div class="row">
        <!-- Left: Race overview table -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <strong>Race Overview</strong>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                            <tr>
                                <th>Race</th>
                                <th>Seat Type</th>
                                <th>Seats</th>
                                <th>Winner(s)</th>
                                <th>Session</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($racesResults)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No results available for this election.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($racesResults as $race): ?>
                                    <?php
                                    $winners = array_filter($race['candidates'], function ($c) {
                                        return !empty($c['isWinner']);
                                    });
                                    $winnerNames = array_map(function ($c) {
                                        $fac = $c['facultyCode'] ?? '-';
                                        return $c['fullName'] . ' (' . $fac . ')';
                                    }, $winners);

                                    $seatType = $race['seatType'] === 'FACULTY_REP'
                                        ? 'Faculty Representative'
                                        : 'Campus-wide';
                                    ?>
                                    <tr data-race-id="<?= (int) $race['raceID'] ?>" class="race-row">
                                        <td><?= htmlspecialchars($race['raceTitle']) ?></td>
                                        <td><?= htmlspecialchars($seatType) ?></td>
                                        <td><?= (int) $race['seatCount'] ?></td>
                                        <td><?= htmlspecialchars(implode(', ', $winnerNames) ?: 'N/A') ?></td>
                                        <td>
                                            <?= htmlspecialchars($race['voteSessionName'] ?? '') ?>
                                            <span class="badge bg-light text-secondary border ms-1">
                                                <?= htmlspecialchars($race['voteSessionType'] ?? '') ?>
                                            </span>
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

        <!-- Right: candidate breakdown for selected race -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>Selected Race – Candidate Breakdown</strong>
                    <div class="ms-2">
                        <select id="raceSelect" class="form-select form-select-sm">
                            <?php foreach ($racesResults as $race): ?>
                                <option value="<?= (int) $race['raceID'] ?>"
                                    <?= $race['raceID'] == $initialRaceID ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($race['raceTitle']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="raceChart" style="max-height: 320px;"></canvas>
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

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function () {
        const chartPayload = <?= json_encode($chartPayload) ?>;
        let currentRaceId = <?= json_encode($initialRaceID) ?>;

        const ctx = document.getElementById('raceChart').getContext('2d');

        function buildDataset(raceId) {
            const data = chartPayload[raceId] || {labels: [], votes: []};
            return {
                labels: data.labels,
                datasets: [{
                    label: 'Votes',
                    data: data.votes
                }]
            };
        }

        let raceChart = new Chart(ctx, {
            type: 'bar',
            data: buildDataset(currentRaceId),
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {display: false}
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {precision: 0}
                    }
                }
            }
        });

        const selectEl = document.getElementById('raceSelect');
        if (selectEl) {
            selectEl.addEventListener('change', function () {
                const raceId = this.value;
                const ds = buildDataset(raceId);
                raceChart.data.labels = ds.labels;
                raceChart.data.datasets[0].data = ds.datasets[0].data;
                raceChart.update();
            });
        }

        // Also allow clicking on table rows to switch race
        document.querySelectorAll('.race-row').forEach(function (row) {
            row.addEventListener('click', function () {
                const rid = this.getAttribute('data-race-id');
                selectEl.value = rid;
                const ds = buildDataset(rid);
                raceChart.data.labels = ds.labels;
                raceChart.data.datasets[0].data = ds.datasets[0].data;
                raceChart.update();
            });
        });
    })();
</script>
