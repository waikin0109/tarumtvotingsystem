<?php
$_title = 'Voting Form';
require_once __DIR__ . '/../AdminView/adminHeader.php';

// safety guards
if (!isset($voteSessions) || !is_array($voteSessions))
  $voteSessions = [];

$isAdmin = strtoupper($_SESSION['role'] ?? '') === 'ADMIN';

function badge_class(string $status): string
{
  $s = strtoupper($status);
  return match ($s) {
    'OPEN' => 'bg-success',
    'SCHEDULED' => 'bg-warning text-dark',
    'DRAFT' => 'bg-info text-dark',
    'CLOSED' => 'bg-secondary',
    'CANCELLED' => 'bg-danger',
    default => 'bg-light text-dark'
  };
}
?>

<div class="container-fluid mt-4 mb-5">
  <div class="container-fluid mb-4">
    <div class="row w-100 align-items-center">
      <div class="col-sm-6">
        <h2 class="mb-0">Voting Form</h2>
      </div>
      <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
        <a href="/vote-session/create" class="btn btn-primary">
          Create (+)
        </a>
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
              <th scope="col-sm-1">Status</th>
              <th scope="col-sm-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($voteSessions)): ?>
              <tr>
                <td colspan="6" class="text-center text-muted">No voting sessions found.</td>
              </tr>
            <?php else:
              $no = 1;
              foreach ($voteSessions as $s):
                $id = (int) ($s['VoteSessionID'] ?? $s['voteSessionID'] ?? 0);
                $name = $s['VoteSessionName'] ?? $s['voteSessionName'] ?? '';
                $eTitle = $s['ElectionTitle'] ?? $s['electionTitle'] ?? '';
                $status = strtoupper($s['VoteSessionStatus'] ?? $s['voteSessionStatus'] ?? '');
                $startAt = $s['StartAt'] ?? $s['startAt'] ?? $s['VoteSessionStartAt'] ?? null;
                $start = $startAt ? date('Y.m.d H:i', strtotime($startAt)) : '';
                $hasVoted = !empty($s['HasVoted']);
                ?>
                <tr>
                  <td><?= $no++ ?></td>

                  <td>
                    <a href="/vote-session/details/<?= $id ?>">
                      <?= htmlspecialchars($name) ?>
                    </a>
                  </td>

                  <td><?= htmlspecialchars($eTitle) ?></td>

                  <td><?= htmlspecialchars($start) ?></td>

                  <td>
                    <?php if ($status): ?>
                      <span class="badge <?= badge_class($status) ?>">
                        <?= htmlspecialchars($status) ?>
                      </span>
                    <?php endif; ?>
                  </td>

                  <td class="text-nowrap">
                    <div class="d-flex flex-wrap gap-2 justify-content-start">
                      <?php
                      // Normalize status
                      $status = strtoupper($status);

                      // Only admins can operate these actions on this page
                      $canUpdate = $isAdmin && ($status === 'DRAFT');
                      $canDelete = $isAdmin && ($status === 'DRAFT');
                      $canSchedule = $isAdmin && ($status === 'DRAFT');
                      $canUnschedule = $isAdmin && ($status === 'SCHEDULED');
                      $canCancel = $isAdmin && ($status === 'SCHEDULED');
                      $canVoteButton = ($status === 'OPEN') && !$hasVoted;
                      ?>

                      <?php if ($canUpdate): ?>
                        <a class="btn btn-sm btn-outline-primary" href="/vote-session/edit/<?= $id ?>">Update</a>
                      <?php endif; ?>

                      <?php if ($canDelete): ?>
                        <form class="d-inline" method="post" action="/vote-session/delete"
                          onsubmit="return confirm('Delete this voting session? This cannot be undone.');">
                          <input type="hidden" name="vote_session_id" value="<?= $id ?>">
                          <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                      <?php endif; ?>

                      <?php if ($canSchedule): ?>
                        <form class="d-inline" method="post" action="/vote-session/schedule"
                          onsubmit="return confirm('Schedule this voting session?');">
                          <input type="hidden" name="vote_session_id" value="<?= $id ?>">
                          <button class="btn btn-sm btn-outline-success">Schedule</button>
                        </form>
                      <?php endif; ?>

                      <?php if ($canUnschedule): ?>
                        <form class="d-inline" method="post" action="/vote-session/unschedule"
                          onsubmit="return confirm('Unschedule this voting session? It will become draft.');">
                          <input type="hidden" name="vote_session_id" value="<?= $id ?>">
                          <button class="btn btn-sm btn-outline-secondary">Unschedule</button>
                        </form>
                      <?php endif; ?>

                      <?php if ($canCancel): ?>
                        <form class="d-inline" method="post" action="/vote-session/cancel"
                          onsubmit="return confirm('Cancel this voting session?');">
                          <input type="hidden" name="vote_session_id" value="<?= $id ?>">
                          <button class="btn btn-sm btn-outline-warning">Cancel</button>
                        </form>
                      <?php endif; ?>

                      <?php if ($status === 'OPEN'): ?>
                        <?php if ($canVoteButton): ?>
                          <!-- admin has NOT voted yet -->
                          <a class="btn btn-sm btn-success" href="/ballot/start/<?= $id ?>">Vote</a>
                        <?php else: ?>
                          <!-- admin already voted -->
                          <button class="btn btn-sm btn-outline-secondary" disabled>Voted</button>
                        <?php endif; ?>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>