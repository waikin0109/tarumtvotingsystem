<?php
$_title = 'Results - ' . htmlspecialchars($sessionName);
require_once __DIR__ . '/../AdminView/adminHeader.php';
?>

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 class="mb-0">
        Results: <?= htmlspecialchars($sessionName) ?>
      </h2>
      <div class="text-muted">
        Election: <?= htmlspecialchars($electionTitle) ?> |
        Status: <?= htmlspecialchars($sessionStatus) ?>
      </div>
    </div>
  </div>

  <?php if (empty($races)): ?>
    <div class="alert alert-info">
      No votes have been recorded yet for this session.
    </div>
  <?php else: ?>
    <?php foreach ($races as $race): ?>
      <div class="card mb-4">
        <div class="card-header">
          <strong><?= htmlspecialchars($race['raceTitle']) ?></strong>
          <small class="text-muted ms-2">
            (<?= htmlspecialchars($race['seatType']) ?>
            <?php if (!empty($race['facultyName'])): ?>
              - <?= htmlspecialchars($race['facultyName']) ?>
            <?php endif; ?>)
          </small>
        </div>
        <div class="card-body">
          <p class="text-muted mb-2">
            Total votes in this race: <?= (int) $race['totalVotes'] ?>
          </p>
          <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width: 50%">Candidate</th>
                  <th style="width: 25%">Votes</th>
                  <th style="width: 25%">% of race</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($race['candidates'] as $cand):
                    $votes = (int) $cand['votes'];
                    $pct = $race['totalVotes'] > 0
                        ? round(($votes / $race['totalVotes']) * 100, 1)
                        : 0;
                ?>
                  <tr>
                    <td><?= htmlspecialchars($cand['fullName']) ?></td>
                    <td><?= $votes ?></td>
                    <td><?= $pct ?>%</td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
