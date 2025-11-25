<?php
/** @var array $election */
/** @var array $summary */
/** @var array $byFaculty */

$_title = 'Early Vote Status';
require_once __DIR__ . '/../AdminView/adminHeader.php';

$totalEligible = (int) $summary['totalEligible'];
$totalEarly = (int) $summary['totalEarly'];
$totalMain = (int) $summary['totalMain'];

$overallEarlyPercent = (float) $summary['overallEarlyPercent'];
$overallMainPercent = (float) $summary['overallMainPercent'];

// Chart data
$facLabels = array_map(fn($r) => $r['facultyCode'], $byFaculty);
$earlySeries = array_map(fn($r) => (float) $r['earlyPercent'], $byFaculty);
$mainSeries = array_map(fn($r) => (float) $r['mainPercent'], $byFaculty);
$totalTurnoutPct = array_map(fn($r) => (float) $r['turnoutPercent'], $byFaculty);
?>

<style>
    @media print {

        /* Hide admin header stuff */
        .navbar,
        #sidebar,
        #profileToggle,
        #profileActions {
            display: none !important;
        }

        body {
            margin: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Remove flex so the main content is not squeezed, but
     keep a reasonable max width so graphs don't stretch */
        .d-flex {
            display: block !important;
        }

        #content {
            margin: 0 auto !important;
            /* center on page */
            max-width: 1200px !important;
            /* similar to your screen width */
            width: auto !important;
            /* do NOT force full page width */
        }

        /* Optional: keep charts from growing too tall */
        .card .chartjs-render-monitor,
        .card canvas {
            max-height: 420px !important;
        }

        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        .card,
        .table-responsive {
            page-break-inside: avoid;
        }

        /* Center the chart cards on the page and keep them a bit narrower */
        .chart-card {
            max-width: 900px;
            /* adjust if you want wider/narrower */
            margin-left: auto !important;
            margin-right: auto !important;
        }

        /* Center the canvas itself inside the card */
        .chart-card canvas {
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
    }
</style>


<div class="container-fluid mt-4 mb-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h2 class="mb-1">
                Early Vote Status – <?= htmlspecialchars($election['title'] ?? 'Election') ?>
            </h2>
            <p class="text-muted mb-0">
                Monitor early voting completion and compare early vs main turnout by faculty.
            </p>
        </div>
    </div>

    <!-- KPI Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Voters Eligible for Early</div>
                    <div class="fs-3 fw-semibold"><?= $totalEligible ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Early Ballots Cast</div>
                    <div class="fs-3 fw-semibold"><?= $totalEarly ?></div>
                    <div class="text-muted small mt-1">
                        Overall Early Turnout: <?= number_format($overallEarlyPercent, 2) ?>%
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Main Ballots Cast</div>
                    <div class="fs-3 fw-semibold"><?= $totalMain ?></div>
                    <div class="text-muted small mt-1">
                        Overall Main Turnout: <?= number_format($overallMainPercent, 2) ?>%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts row -->
    <div class="row mb-4">
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm h-100 chart-card">
                <div class="card-header bg-white">
                    <strong>Faculty – Early vs Main Turnout (%)</strong>
                </div>
                <div class="card-body">
                    <canvas id="earlyMainBar" style="max-height: 320px;"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm h-100 chart-card">
                <div class="card-header bg-white">
                    <strong>Overall Turnout Split</strong>
                </div>
                <div class="card-body">
                    <canvas id="overallSplitPie" style="max-height: 320px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail table with "alerts" feel -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <strong>Faculty Early vs Main Details</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Faculty</th>
                            <th class="text-end">Eligible</th>
                            <th class="text-end">Early Cast</th>
                            <th class="text-end">Early %</th>
                            <th class="text-end">Main Cast</th>
                            <th class="text-end">Main %</th>
                            <th class="text-end">Total Turnout %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($byFaculty)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No data available.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($byFaculty as $row): ?>
                                <?php
                                $eligible = (int) $row['eligible'];
                                $earlyCast = (int) $row['earlyCast'];
                                $mainCast = (int) $row['mainCast'];
                                $earlyPct = (float) $row['earlyPercent'];
                                $mainPct = (float) $row['mainPercent'];
                                $totalPct = (float) $row['turnoutPercent'];

                                $lowEarly = $earlyPct < 30 && $eligible > 0;
                                ?>
                                <tr class="<?= $lowEarly ? 'table-warning' : '' ?>">
                                    <td>
                                        <?= htmlspecialchars($row['facultyCode']) ?>
                                        <small class="d-block text-muted">
                                            <?= htmlspecialchars($row['facultyName']) ?>
                                        </small>
                                    </td>
                                    <td class="text-end"><?= $eligible ?></td>
                                    <td class="text-end"><?= $earlyCast ?></td>
                                    <td class="text-end"><?= number_format($earlyPct, 2) ?>%</td>
                                    <td class="text-end"><?= $mainCast ?></td>
                                    <td class="text-end"><?= number_format($mainPct, 2) ?>%</td>
                                    <td class="text-end"><?= number_format($totalPct, 2) ?>%</td>
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
            class="btn btn-outline-secondary px-4 d-print-none">Back</a>
        <button type="button" class="btn btn-primary px-4 d-print-none" onclick="window.print()">
            <i class="bi bi-printer"></i> Print / Save as PDF
        </button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function () {
        const facLabels = <?= json_encode($facLabels) ?>;
        const earlySeries = <?= json_encode($earlySeries) ?>;
        const mainSeries = <?= json_encode($mainSeries) ?>;

        const barCtx = document.getElementById('earlyMainBar').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: facLabels,
                datasets: [
                    {
                        label: 'Early %',
                        data: earlySeries
                    },
                    {
                        label: 'Main %',
                        data: mainSeries
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });

        const pieCtx = document.getElementById('overallSplitPie').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: ['Early', 'Main'],
                datasets: [{
                    data: [<?= $totalEarly ?>, <?= $totalMain ?>]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    })();
</script>