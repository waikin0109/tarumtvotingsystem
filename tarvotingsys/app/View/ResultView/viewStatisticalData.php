<?php
// Title + header according to role
$_title   = 'View Statistical Data';
// $roleUpper = strtoupper($_SESSION['role'] ?? '');

// if ($roleUpper === 'ADMIN') {
//     require_once __DIR__ . '/../AdminView/adminHeader.php';
// } elseif ($roleUpper === 'NOMINEE') {
//     require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
// } elseif ($roleUpper === 'STUDENT') {
//     require_once __DIR__ . '/../StudentView/studentHeader.php';
require_once __DIR__ . '/../AdminView/adminHeader.php';
// }
?>

<style>
  .stats-layout {
    max-width: 1100px;
    margin: 0 auto;
  }
  .stats-card {
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
  }
  .stats-panel-title {
    font-weight: 600;
    font-size: 1.05rem;
  }
  .stats-panel {
    min-height: 220px;
  }
  .mini-label {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #6b7280;
  }
  .winner-badge {
    font-size: 0.75rem;
    border-radius: 999px;
    padding: 0.1rem 0.6rem;
  }
  .winner-row {
    background: #ecfdf3;
  }
</style>

<?php if (!empty($isLive) && $isLive): ?>
  <!-- auto-refresh every 10s for LIVE view -->
  <meta http-equiv="refresh" content="10">
<?php endif; ?>

<div class="container my-4 stats-layout">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 class="mb-1">View Statistical Data</h2>

      <?php if (!empty($isLive) && $isLive): ?>
        <span class="badge bg-danger me-2">[LIVE] Real-Time Result (Unofficial)</span>
        <small class="text-muted d-block">
          Numbers may change until the voting session is CLOSED.
        </small>
      <?php elseif (!empty($isFinal) && $isFinal): ?>
        <span class="badge bg-success me-2">[FINAL] Official Result – Certified</span>
        <small class="text-muted d-block">
          These results are final for this voting session.
        </small>
      <?php endif; ?>
    </div>

    <div class="text-end">
      <?php if (!empty($lastUpdated)): ?>
        <div class="small text-muted">
          Last updated:
          <strong><?= htmlspecialchars($lastUpdated) ?></strong>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Filters row (same layout as your screenshot) -->
  <div class="card mb-4 stats-card">
    <div class="card-body">
      <form class="row g-3 align-items-end" method="get" action="/statistics">
        <!-- Election -->
        <div class="col-md-4">
          <label class="form-label mini-label">Election</label>
          <select name="electionID" class="form-select" onchange="this.form.submit()">
            <?php if (empty($elections)): ?>
              <option value="">No elections</option>
            <?php else: ?>
              <?php foreach ($elections as $e): ?>
                <option value="<?= (int) $e['electionID'] ?>"
                  <?= (isset($selectedElectionID) && (int)$selectedElectionID === (int)$e['electionID']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($e['title']) ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <!-- Vote Session -->
        <div class="col-md-4">
          <label class="form-label mini-label">Vote Session</label>
          <select name="voteSessionID" class="form-select" onchange="this.form.submit()">
            <?php if (empty($voteSessions)): ?>
              <option value="">No sessions</option>
            <?php else: ?>
              <?php foreach ($voteSessions as $vs): ?>
                <?php
                  $label = $vs['voteSessionName'] . ' (' . $vs['voteSessionType'] . ')';
                ?>
                <option value="<?= (int) $vs['voteSessionID'] ?>"
                  <?= (isset($selectedSessionID) && (int)$selectedSessionID === (int)$vs['voteSessionID']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($label) ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>

        <!-- Race -->
        <div class="col-md-4">
          <label class="form-label mini-label">Race</label>
          <select name="raceID" class="form-select" onchange="this.form.submit()">
            <?php if (empty($races)): ?>
              <option value="">No races</option>
            <?php else: ?>
              <?php foreach ($races as $r): ?>
                <?php
                  $raceLabel = $r['raceTitle'];
                  if (!empty($r['facultyName'])) {
                      $raceLabel .= ' - ' . $r['facultyName'];
                  }
                ?>
                <option value="<?= (int) $r['raceID'] ?>"
                  <?= (isset($selectedRaceID) && (int)$selectedRaceID === (int)$r['raceID']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($raceLabel) ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>
      </form>
    </div>
  </div>

  <!-- 4 panels (2 x 2) -->
  <div class="row g-4">

    <!-- Votes by Nominee -->
    <div class="col-md-6">
      <div class="card stats-card stats-panel">
        <div class="card-body">
          <div class="stats-panel-title mb-1">Votes by Nominee</div>
          <small class="text-muted">
            <?php if (!empty($isLive) && $isLive): ?>
              Votes so far (unofficial).
            <?php elseif (!empty($isFinal) && $isFinal): ?>
              Final, official tally.
            <?php endif; ?>
          </small>
          <hr>

          <?php if (empty($votesByNominee)): ?>
            <p class="text-muted mb-0">No votes recorded for this race yet.</p>
          <?php else: ?>
            <?php
              $totalVotesRace = array_sum(array_map(fn($row) => (int)$row['votes'], $votesByNominee));
              $maxVotes       = 0;
              foreach ($votesByNominee as $row) {
                  $v = (int) $row['votes'];
                  if ($v > $maxVotes) {
                      $maxVotes = $v;
                  }
              }
            ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Nominee</th>
                    <th class="text-end">Votes</th>
                    <th class="text-end">% in race</th>
                    <?php if (!empty($isFinal) && $isFinal): ?>
                      <th class="text-center">Result</th>
                    <?php endif; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($votesByNominee as $row): ?>
                    <?php
                      $votes = (int) $row['votes'];
                      $pct   = $totalVotesRace > 0 ? round(($votes / $totalVotesRace) * 100, 1) : 0;
                      $isWinnerRow = (!empty($isFinal) && $isFinal && $votes === $maxVotes && $votes > 0);
                    ?>
                    <tr class="<?= $isWinnerRow ? 'winner-row' : '' ?>">
                      <td><?= htmlspecialchars($row['fullName']) ?></td>
                      <td class="text-end"><?= $votes ?></td>
                      <td class="text-end"><?= $pct ?>%</td>
                      <?php if (!empty($isFinal) && $isFinal): ?>
                        <td class="text-center">
                          <?php if ($isWinnerRow): ?>
                            <span class="badge bg-success winner-badge">Elected</span>
                          <?php elseif ($votes === 0): ?>
                            <span class="badge bg-light text-muted winner-badge">0 votes</span>
                          <?php else: ?>
                            <span class="badge bg-secondary winner-badge">Not elected</span>
                          <?php endif; ?>
                        </td>
                      <?php endif; ?>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Turnout -->
    <div class="col-md-6">
      <div class="card stats-card stats-panel">
        <div class="card-body">
          <div class="stats-panel-title mb-2">Turnout</div>

          <?php if (!empty($selectedRace)): ?>
            <?php
              $seatType  = strtoupper($selectedRace['seatType'] ?? '');
              $facName   = $selectedRace['facultyName'] ?? '';
            ?>
            <small class="text-muted d-block mb-2">
              <?php if ($seatType === 'FACULTY_REP' && $facName): ?>
                Race type: Faculty Representative – <?= htmlspecialchars($facName) ?> only
              <?php else: ?>
                Race type: Campus-wide / non-faculty specific
              <?php endif; ?>
            </small>
          <?php endif; ?>

          <?php if (!$turnout): ?>
            <p class="text-muted mb-0">Turnout data is not available for this session.</p>
          <?php else: ?>
            <p>
              Total eligible voters (admin, students and nominees):
              <strong><?= (int) $turnout['eligible'] ?></strong>
            </p>
            <p>Ballots submitted: <strong><?= (int) $turnout['ballotsSubmitted'] ?></strong></p>
            <p>Turnout: <strong><?= $turnout['turnoutPercent'] ?>%</strong></p>

            <?php
              $tp = min(100, max(0, $turnout['turnoutPercent']));
            ?>
            <div class="progress" role="progressbar" aria-valuenow="<?= $tp ?>" aria-valuemin="0" aria-valuemax="100">
              <div class="progress-bar" style="width: <?= $tp ?>%"></div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Nominee Summary -->
    <div class="col-md-6">
      <div class="card stats-card stats-panel">
        <div class="card-body">
          <div class="stats-panel-title mb-3">Nominee Summary</div>

          <?php if (empty($votesByNominee)): ?>
            <p class="text-muted mb-0">No nominee data for this race yet.</p>
          <?php else: ?>
            <ol class="mb-0">
              <?php foreach ($votesByNominee as $row): ?>
                <li>
                  <?= htmlspecialchars($row['fullName']) ?> –
                  <strong><?= (int) $row['votes'] ?> votes</strong>
                </li>
              <?php endforeach; ?>
            </ol>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Ballots Submitted Over Time -->
    <div class="col-md-6">
      <div class="card stats-card stats-panel">
        <div class="card-body">
          <div class="stats-panel-title mb-3">Ballots Submitted Over Time</div>

          <?php if (empty($ballotsOverTime)): ?>
            <p class="text-muted mb-0">No ballots submitted yet.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Hour</th>
                    <th class="text-end">Ballots Submitted</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($ballotsOverTime as $row): ?>
                    <tr>
                      <td><?= htmlspecialchars($row['bucket']) ?></td>
                      <td class="text-end"><?= (int) $row['count'] ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div><!-- /row -->

</div>
