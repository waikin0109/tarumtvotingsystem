<?php
$_title = 'Official Final Results';
$roleUpper = strtoupper($_SESSION['role'] ?? '');

if ($roleUpper === 'NOMINEE') {
  require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} elseif ($roleUpper === 'STUDENT') {
  require_once __DIR__ . '/../StudentView/studentHeader.php';
} elseif ($roleUpper === 'ADMIN') {
  require_once __DIR__ . '/../AdminView/adminHeader.php';
}

$isClosed = !empty($selectedSessionStatus) && $selectedSessionStatus === 'CLOSED';
$isCertified = !empty($isCertified);

// ---------------- Safe defaults ----------------
$overallTurnout = (isset($overallTurnout) && is_array($overallTurnout))
  ? array_merge(
    [
      'eligible' => 0,
      'ballotsCast' => 0,
      'turnoutPercent' => 0.0,
      'validBallots' => 0,
    ],
    $overallTurnout
  )
  : [
    'eligible' => 0,
    'ballotsCast' => 0,
    'turnoutPercent' => 0.0,
    'validBallots' => 0,
  ];

// For campus-wide KPIs we just reuse overall turnout
$campusWideTurnout = (isset($campusWideTurnout) && is_array($campusWideTurnout))
  ? array_merge(
    [
      'eligible' => 0,
      'ballotsCast' => 0,
      'turnoutPercent' => 0.0,
      'validBallots' => 0,
    ],
    $campusWideTurnout
  )
  : $overallTurnout;

// From controller
$raceTurnoutSummary = (isset($raceTurnoutSummary) && is_array($raceTurnoutSummary)) ? $raceTurnoutSummary : [];
$turnoutByFaculty = (isset($turnoutByFaculty) && is_array($turnoutByFaculty)) ? $turnoutByFaculty : [];
$campusWideTurnoutByRace = (isset($campusWideTurnoutByRace) && is_array($campusWideTurnoutByRace)) ? $campusWideTurnoutByRace : [];
$raceResults = isset($raceResults) && is_array($raceResults) ? $raceResults : [];

$hasElection = !empty($selectedElectionID);
$hasSession = !empty($selectedSessionID);

/* ------------ Build per-race candidate chart payload (vote share) ----------- */
$raceChartPayload = [];
if (!empty($raceResults)) {
  foreach ($raceResults as $block) {
    $race = $block['race'];
    $candidates = $block['candidates'] ?? [];
    $raceId = (int) ($race['raceID'] ?? 0);

    if ($raceId <= 0 || empty($candidates)) {
      continue;
    }

    $labels = [];
    $votes = [];
    foreach ($candidates as $cand) {
      $labels[] = $cand['fullName'] ?? 'Unknown';
      $votes[] = (int) ($cand['votes'] ?? 0);
    }

    $raceChartPayload[] = [
      'raceID' => $raceId,
      'raceTitle' => $race['raceTitle'] ?? 'Race',
      'labels' => $labels,
      'votes' => $votes,
    ];
  }
}

/* ---------------------- Split races by seat type ---------------------------- */
$facultyRepRaces = [];
$campusWideRaces = [];

if (!empty($raceResults)) {
  foreach ($raceResults as $block) {
    $seatType = strtoupper($block['race']['seatType'] ?? '');
    if ($seatType === 'FACULTY_REP') {
      $facultyRepRaces[] = $block;
    } else {
      $campusWideRaces[] = $block;
    }
  }
}

/* -------- TOP PIE: build summary ONLY from faculty-rep races --------------- */
$facultyRepSummary = [];
$facultyTurnoutCharts = [];

if (!empty($facultyRepRaces)) {
  foreach ($facultyRepRaces as $block) {
    $race = $block['race'];

    $eligible = (int) ($race['turnoutEligible'] ?? 0);
    $cast = (int) ($race['turnoutBallotsSubmitted'] ?? 0);
    $pct = (float) ($race['turnoutPercent'] ?? 0.0);

    $labelBase = !empty($race['facultyName'])
      ? $race['facultyName'] . ' (Faculty Rep)'
      : ($race['raceTitle'] ?? 'Faculty Rep race');

    $label = sprintf('%s (%.2f%%)', $labelBase, $pct);

    $tooltipText = sprintf(
      '%s: %d of %d voters (%.2f%% turnout)',
      $labelBase,
      $cast,
      $eligible,
      $pct
    );

    $facultyRepSummary[] = [
      'label' => $label,
      'value' => $pct,
      'tooltip' => $tooltipText,
    ];

    $facultyTurnoutCharts[] = [
      'raceID' => (int) ($race['raceID'] ?? 0),
      'facultyName' => !empty($race['facultyName'])
        ? $race['facultyName']
        : ($race['raceTitle'] ?? 'Faculty'),
      'eligible' => $eligible,
      'voted' => $cast,
      'turnoutPercent' => $pct,
    ];
  }
}

/* -------- Campus-wide voters by faculty slices (use turnoutByFaculty) ------ */
$campusFacultySlices = [];
$campusEligibleTotal = (int) ($overallTurnout['eligible'] ?? 0);

if (!empty($turnoutByFaculty)) {
  foreach ($turnoutByFaculty as $row) {
    $facultyName = trim($row['facultyName'] ?? '');
    if ($facultyName === '') {
      continue;
    }

    $eligible = (int) ($row['eligible'] ?? 0);
    $voted = (int) ($row['voted'] ?? 0);

    // Ignore faculties with zero everything
    if ($eligible <= 0 && $voted <= 0) {
      continue;
    }

    $campusPercent = ($campusEligibleTotal > 0)
      ? round(($voted / $campusEligibleTotal) * 100, 2)
      : 0.0;

    $campusFacultySlices[] = [
      'label' => $facultyName,
      'eligible' => $eligible,
      'voted' => $voted,
      'campusPercent' => $campusPercent,
    ];
  }
}
?>

<style>
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
</style>

<div class="container-fluid mt-4 mb-5">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 class="mb-1">Official Final Results</h2>

      <?php if (!empty($selectedElectionTitle) || !empty($sessionName)): ?>
        <div class="text-muted">
          <?= htmlspecialchars($selectedElectionTitle ?? '') ?>
          <?php if (!empty($sessionName)): ?>
            — <span class="fw-semibold"><?= htmlspecialchars($sessionName) ?></span>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="text-muted small">
          Please select an election event and a vote session to view certified results.
        </div>
      <?php endif; ?>
    </div>

    <div class="text-end">
      <?php if ($isClosed): ?>
        <span class="badge bg-secondary rounded-pill mb-1">SESSION CLOSED</span><br>
      <?php endif; ?>

      <?php if ($isCertified): ?>
        <span class="badge bg-success rounded-pill mb-1">RESULT CERTIFIED</span><br>
      <?php endif; ?>
    </div>
  </div>

  <!-- Certification notice -->
  <div class="alert alert-success border rounded-3 mb-4">
    <strong>Certified Results.</strong>
    These results have been verified and approved by the Election Committee.
    The data on this page is static and does not update in real time.
  </div>

  <!-- Filters row -->
  <div class="card mb-4 shadow-sm">
    <div class="card-body">
      <form class="row g-3 align-items-end" method="get" action="/results">
        <div class="col-md-6">
          <label for="electionID" class="form-label text-uppercase small text-muted mb-1">Election Event</label>
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

        <div class="col-md-6">
          <label for="voteSessionID" class="form-label text-uppercase small text-muted mb-1">Vote Session</label>
          <select id="voteSessionID" name="voteSessionID" class="form-select" <?= !$hasElection ? 'disabled' : '' ?>>
            <option value="">-- Select Closed Session --</option>
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
      </form>

      <?php if ($hasElection && empty($voteSessions)): ?>
        <div class="text-danger small mt-2">
          No <strong>CLOSED</strong> vote session is available for this election event.
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($hasElection && $hasSession && $isClosed): ?>

    <?php if (!empty($facultyRepRaces) || !empty($campusWideRaces)): ?>

      <!-- ======================== FACULTY REP RACES ====================== -->
      <?php if (!empty($facultyRepRaces)): ?>
        <h4 class="mt-3 mb-2">Faculty Representative Races</h4>

        <?php foreach ($facultyRepRaces as $block): ?>
          <?php
          $race = $block['race'];
          $candidates = $block['candidates'];
          $raceTitle = $race['raceTitle'] ?? 'Race';
          $seatType = strtoupper($race['seatType'] ?? '');
          $faculty = $race['facultyName'] ?? null;
          $seatCount = (int) ($race['seatCount'] ?? 0);
          $raceStatus = $race['raceStatus'] ?? 'FINAL';
          $tieMeta = $race['tieMeta'] ?? null;
          $raceId = (int) ($race['raceID'] ?? 0);

          $raceEligible = (int) ($race['turnoutEligible'] ?? 0);
          $raceCast = (int) ($race['turnoutBallotsSubmitted'] ?? 0);
          $racePct = (float) ($race['turnoutPercent'] ?? 0.0);
          ?>
          <div class="card mb-4 shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <h5 class="mb-1"><?= htmlspecialchars($raceTitle) ?></h5>
                  <div class="small text-muted">
                    Seat type: <strong><?= htmlspecialchars($seatType) ?></strong>
                    <?php if (!empty($faculty)): ?>
                      · Faculty: <strong><?= htmlspecialchars($faculty) ?></strong>
                    <?php endif; ?>
                    <?php if ($seatCount > 0): ?>
                      · Seats: <strong><?= $seatCount ?></strong>
                    <?php endif; ?>
                  </div>

                  <?php if ($raceEligible > 0): ?>
                    <div class="small text-muted mt-1">
                      Turnout (this faculty race):
                      <strong><?= number_format($raceCast) ?></strong>
                      of
                      <strong><?= number_format($raceEligible) ?></strong>
                      voters
                      (<?= number_format($racePct, 2) ?>%)
                    </div>
                  <?php endif; ?>
                </div>
              </div>

              <?php if ($raceEligible > 0): ?>
                <div class="row g-3 mb-3">
                  <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                      <div class="card-body">
                        <div class="mini-label mb-1">Eligible Voters (This Faculty)</div>
                        <div class="kpi-number mb-0"><?= number_format($raceEligible) ?></div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                      <div class="card-body">
                        <div class="mini-label mb-1">Ballots Cast (This Race)</div>
                        <div class="kpi-number mb-0"><?= number_format($raceCast) ?></div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                      <div class="card-body">
                        <div class="mini-label mb-1">Turnout (This Faculty Race)</div>
                        <div class="kpi-number mb-0"><?= number_format($racePct, 2) ?>%</div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="row g-3 mb-3">
                  <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                      <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <h6 class="mb-0">Turnout (Voted vs Not yet voted)</h6>
                          <span class="badge bg-light text-muted border">Faculty-level</span>
                        </div>

                        <div style="height:260px">
                          <canvas id="facultyTurnoutChart_<?= $raceId ?>"></canvas>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-lg-6 col-md-6">
                    <div class="card shadow-sm h-100">
                      <div class="card-body">
                        <h6 class="mb-2">Eligible vs Ballots Cast (This Faculty Race)</h6>
                        <div style="height:260px">
                          <canvas id="eligibleCastFacultyChart_<?= $raceId ?>"></canvas>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endif; ?>

              <?php if ($raceStatus === 'TIE_BREAK_REQUIRED' && !empty($tieMeta)): ?>
                <div class="alert alert-warning small mb-3">
                  <strong>Tie-break required.</strong>
                  There is a tie for remaining
                  <strong><?= (int) $tieMeta['seatsRemaining'] ?></strong>
                  of <strong><?= (int) $tieMeta['seatsTotal'] ?></strong> seat(s)
                  at <strong><?= (int) $tieMeta['tiedVote'] ?></strong> vote(s).
                  Tie-break will be conducted according to SRC rules and recorded by the Returning Officer.
                </div>
              <?php elseif ($raceStatus === 'NO_CANDIDATE'): ?>
                <div class="alert alert-light border small mb-3">
                  No candidates were registered for this race.
                </div>
              <?php endif; ?>

              <?php if (empty($candidates)): ?>
                <p class="text-muted mb-0">No candidates were registered for this race.</p>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-sm align-middle table-bordered mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Candidate</th>
                        <th>Faculty</th>
                        <th class="text-end">Votes</th>
                        <th class="text-end">%</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($candidates as $cand): ?>
                        <?php
                        $isWinner = !empty($cand['isWinner']);
                        $isTieGroup = !empty($cand['isTieGroup']);
                        $rowClass = $isWinner ? 'table-success' : ($isTieGroup ? 'table-warning' : '');
                        ?>
                        <tr class="<?= $rowClass ?>">
                          <td>
                            <?= htmlspecialchars($cand['fullName'] ?? 'Unknown') ?>
                            <?php if ($isWinner): ?>
                              <span class="badge bg-success ms-2">Winner</span>
                            <?php elseif ($isTieGroup && $raceStatus === 'TIE_BREAK_REQUIRED'): ?>
                              <span class="badge bg-warning text-dark ms-2">Tie-break group</span>
                            <?php endif; ?>
                          </td>
                          <td><?= htmlspecialchars($cand['facultyName'] ?? ($faculty ?? '-')) ?></td>
                          <td class="text-end"><?= number_format((int) ($cand['votes'] ?? 0)) ?></td>
                          <td class="text-end"><?= number_format((float) ($cand['percent'] ?? 0), 2) ?>%</td>
                          <td>
                            <?php if ($isWinner): ?>
                              <span class="text-success fw-semibold">Elected</span>
                            <?php elseif ($isTieGroup && $raceStatus === 'TIE_BREAK_REQUIRED'): ?>
                              <span class="text-warning fw-semibold">Tie-break pending</span>
                            <?php else: ?>
                              <span class="text-muted">Not elected</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>

                <div class="mt-3">
                  <h6 class="small text-muted mb-1">Vote share (candidates)</h6>
                  <div style="height:220px">
                    <canvas id="raceChart_<?= $raceId ?>"></canvas>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <!-- ===================== CAMPUS-WIDE RACES ======================== -->
      <?php if (!empty($campusWideRaces)): ?>
        <h4 class="mt-4 mb-2">Campus-wide Representative Races</h4>

        <!-- KPI row (campus-wide turnout) -->
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="card shadow-sm h-100">
              <div class="card-body">
                <div class="text-uppercase small text-muted mb-1">Eligible Voters (Campus-wide)</div>
                <div class="fs-4 fw-semibold mb-0"><?= number_format($campusWideTurnout['eligible'] ?? 0) ?></div>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card shadow-sm h-100">
              <div class="card-body">
                <div class="text-uppercase small text-muted mb-1">Ballots Cast (Campus-wide)</div>
                <div class="fs-4 fw-semibold mb-0"><?= number_format($campusWideTurnout['ballotsCast'] ?? 0) ?></div>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card shadow-sm h-100">
              <div class="card-body">
                <div class="text-uppercase small text-muted mb-1">Turnout (Campus-wide)</div>
                <div class="fs-4 fw-semibold mb-0"><?= number_format($campusWideTurnout['turnoutPercent'] ?? 0, 2) ?>%</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Campus-wide charts row -->
        <div class="row g-3 mb-4">
          <div class="col-lg-6">
            <div class="card shadow-sm h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h6 class="mb-0">Turnout in This Session (Campus-wide)</h6>
                  <span class="badge bg-light text-muted border">Campus-level</span>
                </div>
                <div style="height:260px">
                  <canvas id="campusTurnoutDonut"></canvas>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="card shadow-sm h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h6 class="mb-0">Voters by Faculty (Campus-wide)</h6>
                  <span class="badge bg-light text-muted border">Campus-level</span>
                </div>
                <div style="height:260px">
                  <canvas id="votersByFacultyCampusWideChart"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Campus-wide race cards (candidates & winners) -->
        <?php foreach ($campusWideRaces as $block): ?>
          <?php
          $race = $block['race'];
          $candidates = $block['candidates'];
          $raceTitle = $race['raceTitle'] ?? 'Race';
          $seatType = strtoupper($race['seatType'] ?? '');
          $faculty = $race['facultyName'] ?? null;
          $seatCount = (int) ($race['seatCount'] ?? 0);
          $raceStatus = $race['raceStatus'] ?? 'FINAL';
          $tieMeta = $race['tieMeta'] ?? null;
          $raceId = (int) ($race['raceID'] ?? 0);

          $raceEligible = (int) ($race['turnoutEligible'] ?? 0);
          $raceCast = (int) ($race['turnoutBallotsSubmitted'] ?? 0);
          $racePct = (float) ($race['turnoutPercent'] ?? 0.0);
          ?>
          <div class="card mb-4 shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <h5 class="mb-1"><?= htmlspecialchars($raceTitle) ?></h5>
                  <div class="small text-muted">
                    Seat type: <strong><?= htmlspecialchars($seatType) ?></strong>
                    <?php if (!empty($faculty)): ?>
                      · Faculty: <strong><?= htmlspecialchars($faculty) ?></strong>
                    <?php endif; ?>
                    <?php if ($seatCount > 0): ?>
                      · Seats: <strong><?= $seatCount ?></strong>
                    <?php endif; ?>
                  </div>

                  <?php if ($raceEligible > 0): ?>
                    <div class="small text-muted mt-1">
                      Turnout (campus-wide race):
                      <strong><?= number_format($raceCast) ?></strong>
                      of
                      <strong><?= number_format($raceEligible) ?></strong>
                      voters
                      (<?= number_format($racePct, 2) ?>%)
                    </div>
                  <?php endif; ?>
                </div>
              </div>

              <?php if ($raceStatus === 'TIE_BREAK_REQUIRED' && !empty($tieMeta)): ?>
                <div class="alert alert-warning small mb-3">
                  <strong>Tie-break required.</strong>
                  There is a tie for remaining
                  <strong><?= (int) $tieMeta['seatsRemaining'] ?></strong>
                  of <strong><?= (int) $tieMeta['seatsTotal'] ?></strong> seat(s)
                  at <strong><?= (int) $tieMeta['tiedVote'] ?></strong> vote(s).
                  Tie-break will be conducted according to SRC rules and recorded by the Returning Officer.
                </div>
              <?php elseif ($raceStatus === 'NO_CANDIDATE'): ?>
                <div class="alert alert-light border small mb-3">
                  No candidates were registered for this race.
                </div>
              <?php endif; ?>

              <?php if (empty($candidates)): ?>
                <p class="text-muted mb-0">No candidates were registered for this race.</p>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-sm align-middle table-bordered mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Candidate</th>
                        <th>Faculty</th>
                        <th class="text-end">Votes</th>
                        <th class="text-end">%</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($candidates as $cand): ?>
                        <?php
                        $isWinner = !empty($cand['isWinner']);
                        $isTieGroup = !empty($cand['isTieGroup']);
                        $rowClass = $isWinner ? 'table-success' : ($isTieGroup ? 'table-warning' : '');
                        ?>
                        <tr class="<?= $rowClass ?>">
                          <td>
                            <?= htmlspecialchars($cand['fullName'] ?? 'Unknown') ?>
                            <?php if ($isWinner): ?>
                              <span class="badge bg-success ms-2">Winner</span>
                            <?php elseif ($isTieGroup && $raceStatus === 'TIE_BREAK_REQUIRED'): ?>
                              <span class="badge bg-warning text-dark ms-2">Tie-break group</span>
                            <?php endif; ?>
                          </td>
                          <td><?= htmlspecialchars($cand['facultyName'] ?? ($faculty ?? '-')) ?></td>
                          <td class="text-end"><?= number_format((int) ($cand['votes'] ?? 0)) ?></td>
                          <td class="text-end"><?= number_format((float) ($cand['percent'] ?? 0), 2) ?>%</td>
                          <td>
                            <?php if ($isWinner): ?>
                              <span class="text-success fw-semibold">Elected</span>
                            <?php elseif ($isTieGroup && $raceStatus === 'TIE_BREAK_REQUIRED'): ?>
                              <span class="text-warning fw-semibold">Tie-break pending</span>
                            <?php else: ?>
                              <span class="text-muted">Not elected</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>

                <div class="mt-3">
                  <h6 class="small text-muted mb-1">Vote share (candidates)</h6>
                  <div style="height:220px">
                    <canvas id="raceChart_<?= $raceId ?>"></canvas>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

    <?php else: ?>
      <div class="alert alert-light border">
        No races were found for this vote session.
      </div>
    <?php endif; ?>

  <?php else: ?>
    <div class="alert alert-light border">
      Please select an <strong>Election Event</strong> and a
      <strong>Closed Vote Session</strong> to view official final results.
    </div>
  <?php endif; ?>

</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form[action="/results"]');
    const electionInput = document.getElementById('electionID');
    const sessionInput = document.getElementById('voteSessionID');

    if (form && electionInput) {
      electionInput.addEventListener('change', function () {
        if (sessionInput) sessionInput.selectedIndex = 0;
        form.submit();
      });
    }

    if (form && sessionInput) {
      sessionInput.addEventListener('change', function () {
        form.submit();
      });
    }

    function buildColors(count) {
      const palette = [
        '#4F46E5', '#22C55E', '#EAB308', '#F97316', '#EC4899',
        '#0EA5E9', '#6366F1', '#10B981', '#F59E0B', '#EF4444'
      ];
      const colors = [];
      for (let i = 0; i < count; i++) {
        colors.push(palette[i % palette.length]);
      }
      return colors;
    }

    /* -------- Turnout by Faculty Rep races – top pie --------- */
    const raceTurnoutData = <?= json_encode(array_values($facultyRepSummary), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const raceCanvas = document.getElementById('turnoutByRaceChart');

    if (raceCanvas && raceTurnoutData.length > 0) {
      const labels = raceTurnoutData.map(r => r.label);
      const data = raceTurnoutData.map(r => r.value);

      new Chart(raceCanvas.getContext('2d'), {
        type: 'pie',
        data: {
          labels: labels,
          datasets: [{
            data: data,
            backgroundColor: buildColors(labels.length),
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'right' },
            tooltip: {
              callbacks: {
                label: function (context) {
                  const idx = context.dataIndex;
                  const item = raceTurnoutData[idx];
                  return item.tooltip || `${labels[idx]}: ${data[idx]}% turnout`;
                }
              }
            }
          },
          animation: { duration: 800 }
        }
      });
    }

    /* ------------- Faculty-specific turnout doughnut + bar ------------- */
    const facultyTurnoutData = <?= json_encode($facultyTurnoutCharts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    facultyTurnoutData.forEach(function (entry) {
      const doughnutCanvas = document.getElementById('facultyTurnoutChart_' + entry.raceID);
      const barCanvas = document.getElementById('eligibleCastFacultyChart_' + entry.raceID);
      if (!entry.eligible) return;

      const voted = entry.voted || 0;
      const eligibleF = entry.eligible || 0;
      const notVoted = Math.max(eligibleF - voted, 0);

      if (doughnutCanvas) {
        new Chart(doughnutCanvas.getContext('2d'), {
          type: 'doughnut',
          data: {
            labels: ['Voted', 'Not yet voted'],
            datasets: [{
              data: [voted, notVoted],
              backgroundColor: buildColors(2),
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'right' }
            },
            animation: { duration: 800 }
          }
        });
      }

      if (barCanvas && (eligibleF > 0 || voted > 0)) {
        new Chart(barCanvas.getContext('2d'), {
          type: 'bar',
          data: {
            labels: ['Eligible', 'Ballots Cast'],
            datasets: [{
              data: [eligibleF, voted],
              backgroundColor: buildColors(2),
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: { beginAtZero: true, ticks: { precision: 0 } }
            },
            plugins: { legend: { display: false } },
            animation: { duration: 800 }
          }
        });
      }
    });

    /* ------------------- Per-race candidate vote share ------------------ */
    const raceChartData = <?= json_encode($raceChartPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    raceChartData.forEach(function (race) {
      const canvas = document.getElementById('raceChart_' + race.raceID);
      if (!canvas || !race.labels || race.labels.length === 0) return;

      const useBar = race.labels.length > 6;
      new Chart(canvas.getContext('2d'), {
        type: useBar ? 'bar' : 'pie',
        data: {
          labels: race.labels,
          datasets: [{
            data: race.votes,
            backgroundColor: buildColors(race.labels.length),
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: useBar ? 'bottom' : 'right' }
          },
          scales: useBar ? {
            y: { beginAtZero: true, ticks: { precision: 0 } }
          } : {},
          animation: { duration: 800 }
        }
      });
    });

    /* ---------------- Campus-wide Charts ---------------- */
    const campusEligible = <?= (int) ($campusWideTurnout['eligible'] ?? 0) ?>;
    const campusCast = <?= (int) ($campusWideTurnout['ballotsCast'] ?? 0) ?>;
    const campusFacultyRaw = <?= json_encode($campusFacultySlices, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    // Donut: Voted vs Not yet voted (campus-wide)
    const campusTurnoutCanvas = document.getElementById('campusTurnoutDonut');
    if (campusTurnoutCanvas && campusEligible > 0) {
      const notYetVoted = Math.max(campusEligible - campusCast, 0);

      new Chart(campusTurnoutCanvas.getContext('2d'), {
        type: 'doughnut',
        data: {
          labels: ['Voted', 'Not yet voted'],
          datasets: [{
            data: [campusCast, notYetVoted],
            backgroundColor: buildColors(2),
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'right' }
          },
          animation: { duration: 800 }
        }
      });
    }

    // Pie: voters by faculty (campus-wide)
    const campusFacCanvas = document.getElementById('votersByFacultyCampusWideChart');
    if (campusFacCanvas && campusFacultyRaw.length > 0) {
      const labels = campusFacultyRaw.map(r => r.label || 'Unknown faculty');
      const data = campusFacultyRaw.map(r => r.voted || 0);

      new Chart(campusFacCanvas.getContext('2d'), {
        type: 'pie',
        data: {
          labels: labels,
          datasets: [{
            data: data,
            backgroundColor: buildColors(labels.length),
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'right' },
            tooltip: {
              callbacks: {
                label: function (context) {
                  const idx = context.dataIndex;
                  const fac = campusFacultyRaw[idx];
                  const name = fac.label || 'Unknown faculty';
                  const votes = fac.voted || 0;
                  const pct = (fac.campusPercent ?? 0).toFixed(2);
                  return `${name}: ${votes} voters (${pct}% of campus eligible voters)`;
                }
              }
            }
          },
          animation: { duration: 800 }
        }
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