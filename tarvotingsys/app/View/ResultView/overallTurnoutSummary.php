<?php
/** @var array $summaryData */
/** @var array $byFaculty */
/** @var array $timeline */
/** @var int|null $selectedSessionID */

$_title = 'Turnout Report';
require_once __DIR__ . '/../AdminView/adminHeader.php';

$eligibleTotal = (int) ($summaryData['eligibleTotal'] ?? 0);
$ballotsCast = (int) ($summaryData['ballotsCast'] ?? 0);
$turnoutPercent = (float) ($summaryData['turnoutPercent'] ?? 0.0);

// Aggregate early/main across faculties
$totalEarly = 0;
$totalMain = 0;
foreach ($byFaculty as $row) {
    $totalEarly += (int) $row['earlyCast'];
    $totalMain += (int) $row['mainCast'];
}

$earlyPercent = $eligibleTotal > 0 ? round(($totalEarly / $eligibleTotal) * 100, 2) : 0.0;
$mainPercent = $eligibleTotal > 0 ? round(($totalMain / $eligibleTotal) * 100, 2) : 0.0;

// Prepare chart data
$facultyLabels = array_map(fn($r) => $r['facultyCode'], $byFaculty);
$facultyTurnout = array_map(fn($r) => $r['turnoutPercent'], $byFaculty);

$timeLabels = array_map(fn($r) => $r['timeSlot'], $timeline);
$timeVotes = array_map(fn($r) => (int) $r['ballotsCast'], $timeline);
?>
<div class="container-fluid mt-4 mb-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h2 class="mb-1">
                Turnout Report – <?= htmlspecialchars($summaryData['electionTitle'] ?? 'Election') ?>
            </h2>
            <p class="text-muted mb-0">
                Overall participation for the selected election
                <?php if ($selectedSessionID): ?>
                    (Session #<?= (int) $selectedSessionID ?>)
                <?php else: ?>
                    (All sessions)
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- KPI Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Eligible Voters</div>
                    <div class="fs-3 fw-semibold"><?= $eligibleTotal ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Ballots Cast</div>
                    <div class="fs-3 fw-semibold"><?= $ballotsCast ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Turnout %</div>
                    <div class="fs-3 fw-semibold"><?= number_format($turnoutPercent, 2) ?>%</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Early vs Main (overall)</div>
                    <div class="fw-semibold mb-1">
                        Early: <?= $totalEarly ?> (<?= number_format($earlyPercent, 2) ?>%)
                    </div>
                    <div class="fw-semibold">
                        Main: <?= $totalMain ?> (<?= number_format($mainPercent, 2) ?>%)
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row mb-4">
        <!-- Turnout by faculty -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <strong>Turnout by Faculty (%)</strong>
                </div>
                <div class="card-body">
                    <canvas id="facultyTurnoutChart" style="max-height: 320px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Turnout over time -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <strong>Turnout Over Time</strong>
                </div>
                <div class="card-body">
                    <canvas id="timelineChart" style="max-height: 320px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <strong>Faculty Turnout Details</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Faculty</th>
                            <th class="text-end">Eligible</th>
                            <th class="text-end">Ballots Cast</th>
                            <th class="text-end">Turnout %</th>
                            <th class="text-end">Early %</th>
                            <th class="text-end">Main %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($byFaculty)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No turnout data available.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($byFaculty as $row): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($row['facultyCode']) ?>
                                        <small class="d-block text-muted">
                                            <?= htmlspecialchars($row['facultyName']) ?>
                                        </small>
                                    </td>
                                    <td class="text-end"><?= (int) $row['eligible'] ?></td>
                                    <td class="text-end"><?= (int) $row['ballotsCast'] ?></td>
                                    <td class="text-end"><?= number_format($row['turnoutPercent'], 2) ?>%</td>
                                    <td class="text-end"><?= number_format($row['earlyPercent'], 2) ?>%</td>
                                    <td class="text-end"><?= number_format($row['mainPercent'], 2) ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
        const facultyLabels = <?= json_encode($facultyLabels) ?>;
        const facultyTurnout = <?= json_encode($facultyTurnout) ?>;

        const facultyCtx = document.getElementById('facultyTurnoutChart').getContext('2d');
        new Chart(facultyCtx, {
            type: 'bar',
            data: {
                labels: facultyLabels,
                datasets: [{
                    label: 'Turnout %',
                    data: facultyTurnout
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });

        const timeLabels = <?= json_encode($timeLabels) ?>;
        const timeVotes = <?= json_encode($timeVotes) ?>;
        const timeCtx = document.getElementById('timelineChart').getContext('2d');

        new Chart(timeCtx, {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [{
                    label: 'Ballots Cast',
                    data: timeVotes,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    })();
</script>