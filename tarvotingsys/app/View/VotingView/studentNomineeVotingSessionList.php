<?php
$_title = 'Voting Sessions';
$roleUpper = strtoupper($_SESSION['role'] ?? '');

// Header / footer includes based on role
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} elseif ($roleUpper === 'STUDENT') {
    require_once __DIR__ . '/../StudentView/studentHeader.php';
}

// safety guard
if (!isset($voteSessions) || !is_array($voteSessions)) {
  $voteSessions = [];
}

function badge_class(string $status): string
{
  $s = strtoupper($status);
  return match ($s) {
    'OPEN' => 'bg-success',
    'SCHEDULED' => 'bg-warning text-dark',
    'DRAFT' => 'bg-info text-dark',
    'CLOSED' => 'bg-secondary',
    'CANCELLED' => 'bg-danger',
    default => 'bg-light text-dark',
  };
}
?>

<div class="container-fluid mt-4 mb-5">
  <div class="container-fluid d-flex justify-content-between align-items-center mb-4">
    <div class="row w-100">
      <div class="col-sm-6">
        <h2>Voting Sessions</h2>
      </div>
    </div>
  </div>

  <div class="container-fluid mb-5">
    <div class="bg-light">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th scope="col-sm-1">No.</th>
              <th scope="col-sm-3">Session Name</th>
              <th scope="col-sm-3">Election</th>
              <th scope="col-sm-2">Start Date</th>
              <th scope="col-sm-2">End Date</th>
              <th scope="col-sm-1">Status</th>
              <th scope="col-sm-1">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $no = 1;
            foreach ($voteSessions as $s):

              // If model added flags (from listForStudentNominee), hide sessions not visible to voters
              $visibleToVoters = $s['VisibleToVoters'] ?? null;
              if ($visibleToVoters === false) {
                continue;
              }

              $id = (int) ($s['VoteSessionID'] ?? $s['voteSessionID'] ?? 0);
              $name = $s['VoteSessionName'] ?? $s['voteSessionName'] ?? '';
              $eTitle = $s['ElectionTitle'] ?? $s['electionTitle'] ?? '';
              $status = strtoupper($s['VoteSessionStatus'] ?? $s['voteSessionStatus'] ?? '');

              $startAt = $s['StartAt'] ?? $s['startAt'] ?? $s['VoteSessionStartAt'] ?? null;
              $start = $startAt ? date('Y.m.d H:i', strtotime($startAt)) : '';

              $endAt = $s['EndAt'] ?? $s['endAt'] ?? $s['VoteSessionEndAt'] ?? null;
              $end = $endAt ? date('Y.m.d H:i', strtotime($endAt)) : '';

              $hasVoted = !empty($s['HasVoted']);
              // For students/nominees: only OPEN can vote
              $canVote = ($status === 'OPEN');
              ?>
              <tr>
                <td><?= $no++ ?></td>

                <td><?= htmlspecialchars($name) ?></td>

                <td><?= htmlspecialchars($eTitle) ?></td>

                <td><?= htmlspecialchars($start) ?></td>

                <td><?= htmlspecialchars($end) ?></td>

                <td>
                  <?php if ($status): ?>
                    <span class="badge <?= badge_class($status) ?>">
                      <?= htmlspecialchars($status) ?>
                    </span>
                  <?php endif; ?>
                </td>

                <td class="text-nowrap">
                  <div class="d-flex flex-wrap gap-2 justify-content-start">
                    <?php if ($status === 'OPEN'): ?>
                      <?php if (!$hasVoted): ?>
                        <a class="btn btn-sm btn-success" href="/ballot/start/<?= $id ?>">
                          Vote
                        </a>
                      <?php else: ?>
                        <button class="btn btn-sm btn-outline-secondary" disabled>
                          Voted
                        </button>
                      <?php endif; ?>
                    <?php else: ?>
                      <?php if ($status === 'CLOSED'): ?>
                        <span class="text-muted small">Voting closed</span>
                      <?php else: ?>
                        <span class="text-muted small">Not available</span>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>

            <?php if ($no === 1): // nothing printed ?>
              <tr>
                <td colspan="7" class="text-center text-muted">
                  No voting sessions available.
                </td>
              </tr>
            <?php endif; ?>

          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeFooter.php';
} elseif ($roleUpper === 'STUDENT') {
    require_once __DIR__ . '/../StudentView/studentFooter.php';
}
?>