<?php
$_title = 'View Statistical Data';
$roleUpper = strtoupper($_SESSION['role'] ?? '');

if ($roleUpper === 'NOMINEE') {
  require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} elseif ($roleUpper === 'STUDENT') {
  require_once __DIR__ . '/../StudentView/studentHeader.php';
} elseif ($roleUpper === 'ADMIN') {
  require_once __DIR__ . '/../AdminView/adminHeader.php';
}

// Totals for this race (all faculties combined)
$eligibleTotal = isset($turnout['eligible']) ? (int) $turnout['eligible'] : 0;
$ballotsCast = isset($turnout['ballotsSubmitted']) ? (int) $turnout['ballotsSubmitted'] : 0;
$currentTurnout = isset($turnout['turnoutPercent'])
  ? (float) $turnout['turnoutPercent']
  : ($eligibleTotal > 0 ? round(($ballotsCast / $eligibleTotal) * 100, 2) : 0.0);

$facultyChartLabels = [];
$facultyChartPercents = [];

if (!empty($turnoutByFaculty)) {
  foreach ($turnoutByFaculty as $row) {
    $facultyChartLabels[] = $row['facultyName'];

    $votes = (int) ($row['voted'] ?? 0);
    // percentage of *total eligible for this race* (same denominator as KPI 16.67%)
    $facultyChartPercents[] = ($eligibleTotal > 0)
      ? round(($votes / $eligibleTotal) * 100, 2)
      : 0.0;
  }
}

// Session / selection flags
$sessionStatus = $selectedSessionStatus ?? '';
$isLive = !empty($isLive);
$isFinal = !empty($isFinal);

$hasElection = !empty($selectedElectionID);
$hasSession = !empty($selectedSessionID);
$hasRace = !empty($selectedRaceID);

$fieldErrors = $fieldErrors ?? [];

// Derived “no data” flags for dropdowns
$noSessionAvailable = $hasElection && empty($voteSessions);
$noRaceAvailable = $hasSession && empty($races);

// Disable logic for dropdowns
$disableSessionSelect = !$hasElection || $noSessionAvailable;
$disableRaceSelect = !$hasSession || $noRaceAvailable;

// --- NEW: detect selected race seat type + faculty (for chart behaviour) ---
$selectedSeatType = '';
$selectedFacultyName = '';

if ($hasSession && $hasRace && !empty($races)) {
  foreach ($races as $r) {
    if ((int) $r['raceID'] === (int) $selectedRaceID) {
      $selectedSeatType = strtoupper($r['seatType'] ?? '');
      $selectedFacultyName = $r['facultyName'] ?? '';
      break;
    }
  }
}

// For donut chart (faculty rep)
$notVoted = max($eligibleTotal - $ballotsCast, 0);
?>

<?php if ($isLive): ?>
  <!-- auto-refresh every 15s for LIVE view -->
  <meta http-equiv="refresh" content="15">
<?php endif; ?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
  .stats-layout {
    max-width: 1200px;
    margin: 0 auto;
  }

  .stats-card {
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06);
  }

  .stats-panel-title {
    font-weight: 600;
    font-size: 1.05rem;
  }

  .mini-label {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #6b7280;
  }

  .kpi-number {
    font-size: 1.8rem;
    font-weight: 600;
  }

  .badge-pill {
    border-radius: 999px;
    padding: 0.2rem 0.75rem;
    font-size: 0.78rem;
  }

  .info-banner {
    border-left: 4px solid #3b82f6;
    background: #eff6ff;
  }

  .footer-updated {
    font-size: 0.75rem;
    color: #9ca3af;
    text-align: center;
    margin-top: 1.25rem;
  }

  .breakdown-table th,
  .breakdown-table td {
    border-left: 1px solid #dee2e6;
  }

  .breakdown-table th:first-child,
  .breakdown-table td:first-child {
    border-left: none;
  }
</style>

<div class="container-fluid mt-4 mb-5">

  <!-- Header row -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 class="mb-1">Real-Time Turnout</h2>

      <?php if (!empty($selectedElectionTitle) || !empty($sessionName)): ?>
        <div class="text-muted">
          <?= htmlspecialchars($selectedElectionTitle ?? '') ?>
          <?php if (!empty($sessionName)): ?>
            — <span class="fw-semibold"><?= htmlspecialchars($sessionName) ?></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="text-end">
      <?php if ($sessionStatus === 'OPEN'): ?>
        <span class="badge bg-primary badge-pill mb-1">SESSION OPEN</span>
      <?php elseif ($sessionStatus === 'CLOSED'): ?>
        <span class="badge bg-secondary badge-pill mb-1">SESSION CLOSED</span>
      <?php endif; ?>

      <?php if ($isLive): ?>
        <div><span class="badge bg-warning text-dark badge-pill">LIVE / UNOFFICIAL</span></div>
      <?php endif; ?>

      <?php if (!empty($lastUpdated)): ?>
        <div class="small text-muted mt-1">
          Last updated:
          <strong><?= htmlspecialchars($lastUpdated) ?></strong>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Info banner -->
  <div class="card mb-4 stats-card info-banner">
    <div class="card-body py-3 d-flex align-items-center">
      <div class="me-2">
        <span class="badge bg-primary rounded-circle" style="width: 10px; height: 10px;">&nbsp;</span>
      </div>
      <div>
        <strong>Live Dashboard (Unofficial).</strong>
        <span class="text-muted">
          These numbers show ballots received in real-time. They do not reveal candidate rankings.
          Turnout figures become final only when the voting session is closed.
        </span>
      </div>
    </div>
  </div>

  <!-- Filters row -->
  <div class="card mb-4 stats-card">
    <div class="card-body">
      <form id="filterForm" class="row g-3 align-items-end" method="get" action="/statistics">
        <!-- Election -->
        <div class="col-md-4">
          <label class="form-label mini-label" for="electionID">Election</label>
          <select id="electionID" name="electionID" class="form-select">
            <option value="">-- Select Election Event --</option>
            <?php if (!empty($elections)): ?>
              <?php foreach ($elections as $e): ?>
                <option value="<?= (int) $e['electionID'] ?>" <?= $hasElection && (int) $selectedElectionID === (int) $e['electionID'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($e['title']) ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <!-- Vote Session -->
        <div class="col-md-4">
          <label class="form-label mini-label" for="voteSessionID">Vote Session</label>
          <select id="voteSessionID" name="voteSessionID" class="form-select" <?= $disableSessionSelect ? 'disabled' : '' ?>>
            <option value="">-- Select Vote Session --</option>

            <?php if ($hasElection && !empty($voteSessions)): ?>
              <?php foreach ($voteSessions as $vs): ?>
                <?php $label = $vs['voteSessionName'] . ' (' . $vs['voteSessionType'] . ')'; ?>
                <option value="<?= (int) $vs['voteSessionID'] ?>" <?= $hasSession && (int) $selectedSessionID === (int) $vs['voteSessionID'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($label) ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <!-- Race -->
        <div class="col-md-4">
          <label class="form-label mini-label" for="raceID">Race</label>
          <select id="raceID" name="raceID" class="form-select" <?= $disableRaceSelect ? 'disabled' : '' ?>>
            <option value="">-- Select Race --</option>

            <?php if ($hasSession && !empty($races)): ?>
              <?php foreach ($races as $r): ?>
                <?php
                $raceLabel = $r['raceTitle'];
                if (!empty($r['facultyName'])) {
                  $raceLabel .= ' - ' . $r['facultyName'];
                }
                ?>
                <option value="<?= (int) $r['raceID'] ?>" <?= $hasRace && (int) $selectedRaceID === (int) $r['raceID'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($raceLabel) ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>
      </form>

      <!-- field + availability messages shown BELOW the row of fields -->
      <?php if (
        !empty($fieldErrors['electionID']) ||
        !empty($fieldErrors['voteSessionID']) ||
        !empty($fieldErrors['raceID']) ||
        $noSessionAvailable ||
        ($noRaceAvailable && $hasSession)
      ): ?>
        <div class="mt-3">
          <?php if (!empty($fieldErrors['electionID'])): ?>
            <div class="text-danger small">
              <?= htmlspecialchars($fieldErrors['electionID']) ?>
            </div>
          <?php endif; ?>

          <?php if ($noSessionAvailable): ?>
            <div class="text-danger small">
              No vote session is available for this election event.
            </div>
          <?php elseif (!empty($fieldErrors['voteSessionID'])): ?>
            <div class="text-danger small">
              <?= htmlspecialchars($fieldErrors['voteSessionID']) ?>
            </div>
          <?php endif; ?>

          <?php if ($noRaceAvailable && $hasSession): ?>
            <div class="text-danger small">
              No race is available for this vote session.
            </div>
          <?php elseif (!empty($fieldErrors['raceID'])): ?>
            <div class="text-danger small">
              <?= htmlspecialchars($fieldErrors['raceID']) ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>

  <?php if ($hasElection && $hasSession && $hasRace && $isLive): ?>
    <!-- KPI row -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="card stats-card h-100">
          <div class="card-body">
            <div class="mini-label mb-2">Eligible Voters</div>
            <div class="kpi-number mb-0"><?= number_format($eligibleTotal) ?></div>
            <div class="text-muted small">Registered active admin, students & nominees</div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card stats-card h-100">
          <div class="card-body">
            <div class="mini-label mb-2">Ballots Cast</div>
            <div class="kpi-number mb-0"><?= number_format($ballotsCast) ?></div>
            <div class="text-muted small">Ballots received by server</div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card stats-card h-100">
          <div class="card-body">
            <div class="mini-label mb-2">Current Turnout</div>
            <div class="kpi-number mb-0"><?= number_format($currentTurnout, 2) ?>%</div>
            <div class="text-muted small">Percentage of total eligible for this race</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main rows: chart on top, breakdown below -->
    <div class="row g-4">

      <!-- Turnout chart -->
      <div class="col-12">
        <div class="card stats-card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="stats-panel-title">
                <?php if ($selectedSeatType === 'FACULTY_REP'): ?>
                  Turnout – <?= htmlspecialchars($selectedFacultyName ?: 'Faculty Representative', ENT_QUOTES, 'UTF-8') ?>
                <?php else: ?>
                  Turnout by Faculty
                <?php endif; ?>
              </div>
              <?php if ($isLive): ?>
                <span class="badge bg-light text-muted border">Real-time</span>
              <?php endif; ?>
            </div>

            <?php if ($selectedSeatType === 'FACULTY_REP'): ?>
              <?php if ($eligibleTotal <= 0): ?>
                <p class="text-muted mb-0">No turnout data is available for this race yet.</p>
              <?php else: ?>
                <p class="text-muted small mb-2">
                  Voted vs not voted for this faculty representative race.
                </p>
                <div style="height: 280px;">
                  <canvas id="turnoutDonutChart"></canvas>
                </div>
              <?php endif; ?>
            <?php else: ?>
              <?php if (empty($turnoutByFaculty)): ?>
                <p class="text-muted mb-0">No turnout data is available for this vote session yet.</p>
              <?php else: ?>
                <div style="height: 320px;">
                  <canvas id="turnoutByFacultyChart"></canvas>
                </div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Breakdown table (full row under the chart) -->
      <div class="col-12">
        <div class="card stats-card h-100">
          <div class="card-body">
            <div class="stats-panel-title mb-3">Breakdown</div>

            <?php if (empty($turnoutByFaculty)): ?>
              <p class="text-muted mb-0">No faculty breakdown is available.</p>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 breakdown-table">
                  <thead class="table-light">
                    <tr>
                      <th>Faculty</th>
                      <th class="text-end">Voted</th>
                      <th class="text-end">Percentage (%)</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($turnoutByFaculty as $row): ?>
                      <?php
                      $voted = (int) $row['voted'];
                      $pctOfTotal = ($eligibleTotal > 0)
                        ? round(($voted / $eligibleTotal) * 100, 2)
                        : 0.0;
                      ?>
                      <tr>
                        <td><?= htmlspecialchars($row['facultyName']) ?></td>
                        <td class="text-end"><?= number_format($voted) ?></td>
                        <td class="text-end"><?= number_format($pctOfTotal, 2) ?>%</td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  <?php else: ?>
    <!-- Hint when nothing fully selected OR session not live -->
    <div class="alert alert-light border text-muted">
      <?php if ($hasElection && $hasSession && $hasRace && !$isLive): ?>
        This live turnout dashboard is only available while the vote session is <strong>OPEN</strong>.
        Please check the Official Final Results page for closed sessions.
      <?php else: ?>
        Please select an <strong>Election Event</strong>, then a <strong>Vote Session</strong>,
        and finally a <strong>Race</strong> to view live turnout statistics.
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="footer-updated">
    SYSTEM LAST UPDATED: <?= htmlspecialchars($lastUpdated ?? '-') ?>
  </div>

</div>

<?php if ($hasElection && $hasSession && $hasRace): ?>
  <script>
    (function () {
      <?php if ($selectedSeatType === 'FACULTY_REP'): ?>
        // Donut: Voted vs Not voted (faculty rep)
        const donutEl = document.getElementById('turnoutDonutChart');
        if (!donutEl) return;

        const donutCtx = donutEl.getContext('2d');
        const donutLabels = ['Voted', 'Not voted'];
        const donutData = [<?= (int) $ballotsCast ?>, <?= (int) $notVoted ?>];
        const donutTotal = donutData.reduce((a, b) => a + b, 0);

        new Chart(donutCtx, {
          type: 'doughnut',
          data: {
            labels: donutLabels,
            datasets: [{
              data: donutData,
              backgroundColor: ['#4f46e5', '#e5e7eb'],
              borderColor: '#ffffff',
              borderWidth: 2
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
              legend: {
                position: 'bottom'
              },
              tooltip: {
                callbacks: {
                  label: function (context) {
                    const label = context.label || '';
                    const value = context.parsed !== undefined ? context.parsed : 0;
                    const pct = donutTotal > 0 ? (value / donutTotal * 100) : 0;
                    return label + ': ' + value + ' (' + pct.toFixed(2) + '%)';
                  }
                }
              }
            }
          }
        });
      <?php elseif (!empty($turnoutByFaculty)): ?>
        // Original: Turnout by Faculty (pie)
        const labels = <?= json_encode($facultyChartLabels, JSON_UNESCAPED_UNICODE) ?>;
        const values = <?= json_encode($facultyChartPercents) ?>;

        if (!labels.length) return;

        const ctx = document.getElementById('turnoutByFacultyChart').getContext('2d');

        const baseColors = [
          '#4f46e5', '#0ea5e9', '#22c55e', '#f97316',
          '#e11d48', '#a855f7', '#14b8a6', '#facc15'
        ];
        const backgroundColors = labels.map((_, idx) => baseColors[idx % baseColors.length]);

        new Chart(ctx, {
          type: 'pie',
          data: {
            labels: labels,
            datasets: [{
              data: values,
              backgroundColor: backgroundColors,
              borderColor: '#ffffff',
              borderWidth: 2
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'right'
              },
              tooltip: {
                callbacks: {
                  label: function (context) {
                    const label = context.label || '';
                    const value = context.parsed !== undefined ? context.parsed : 0;
                    return label + ': ' + value.toFixed(2) + '% of total eligible';
                  }
                }
              }
            }
          }
        });
      <?php endif; ?>
    })();
  </script>
<?php endif; ?>

<!-- JS to reset dropdowns when changing election / session / race -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('filterForm');
    const electionInput = document.getElementById('electionID');
    const sessionInput = document.getElementById('voteSessionID');
    const raceInput = document.getElementById('raceID');

    if (!form) return;

    if (electionInput) {
      electionInput.addEventListener('change', function () {
        if (sessionInput) sessionInput.selectedIndex = 0;
        if (raceInput) raceInput.selectedIndex = 0;
        form.submit();
      });
    }

    if (sessionInput) {
      sessionInput.addEventListener('change', function () {
        if (raceInput) raceInput.selectedIndex = 0;
        form.submit();
      });
    }

    if (raceInput) {
      raceInput.addEventListener('change', function () {
        form.submit();
      });
    }
  });
</script>

<?php
if ($roleUpper === 'NOMINEE') {
  require_once __DIR__ . '/../NomineeView/nomineeFooter.php';
} elseif ($roleUpper === 'STUDENT') {
  require_once __DIR__ . '/../StudentView/studentFooter.php';
} else {
  require_once __DIR__ . '/../AdminView/adminFooter.php';
}
?>