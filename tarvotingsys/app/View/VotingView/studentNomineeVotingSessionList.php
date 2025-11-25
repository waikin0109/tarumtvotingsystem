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

$search = $search ?? '';

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
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
    <div>
      <h2 class="mb-0">Voting Sessions</h2>
      <p class="text-muted small mb-0">
        View available voting sessions and cast your vote.
      </p>
    </div>
  </div>

  <!-- Search Bar -->
  <div class="card mb-4">
    <div class="card-body">
      <form class="row g-2 align-items-end" method="get" action="">
        <div class="col-md-6">
          <label for="q" class="form-label mb-1">Search by Session Name</label>
          <input type="text" id="q" name="q" class="form-control" placeholder="Search voting sessions"
            value="<?= htmlspecialchars($search) ?>">
        </div>

        <div class="col-md-6 text-md-end">
          <button type="submit" class="btn btn-outline-primary me-2">
            Search
          </button>

          <?php if ($search !== ''): ?>
            <a href="<?= strtok($_SERVER['REQUEST_URI'], '?') ?>" class="btn btn-link text-decoration-none">
              Reset
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- Table -->
  <div class="card mb-4" style="box-shadow:0 0.1rem 1rem rgba(0,0,0,.15);">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0">
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
            $startNumber = isset($pager)
              ? (($pager->page - 1) * $pager->limit) + 1
              : 1;

            $printed = 0;

            foreach ($voteSessions as $index => $s):
              // hide sessions that model marks as not visible
              $visibleToVoters = $s['VisibleToVoters'] ?? null;
              if ($visibleToVoters === false) {
                continue;
              }

              $id = (int) ($s['VoteSessionID'] ?? $s['voteSessionID'] ?? 0);
              $name = $s['VoteSessionName'] ?? $s['voteSessionName'] ?? '';
              $eTitle = $s['ElectionTitle'] ?? $s['electionTitle'] ?? '';
              $status = strtoupper($s['VoteSessionStatus'] ?? $s['voteSessionStatus'] ?? '');

              $startAt = $s['StartAt'] ?? $s['startAt'] ?? $s['VoteSessionStartAt'] ?? null;
              $start = $startAt ? date('Y-m-d H:i:s', strtotime($startAt)) : '';

              $endAt = $s['EndAt'] ?? $s['endAt'] ?? $s['VoteSessionEndAt'] ?? null;
              $end = $endAt ? date('Y-m-d H:i:s', strtotime($endAt)) : '';

              $hasVoted = !empty($s['HasVoted']);
              $canVote = ($status === 'OPEN');
              $printed++;
              ?>
              <tr>
                <td><?= $startNumber + $index ?></td>
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
                        <button class="btn btn-sm btn-danger" disabled>
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

            <?php if ($printed === 0): ?>
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

    <!-- Pager row  -->
    <?php if (isset($pager) && $pager->page_count > 1): ?>
      <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div class="text-muted small">
          <?php
          $from = ($pager->item_count === 0)
            ? 0
            : (($pager->page - 1) * $pager->limit) + 1;
          $to = ($pager->page - 1) * $pager->limit + $pager->count;
          ?>
          Showing <strong><?= $from ?></strong> to <strong><?= $to ?></strong>
          of <strong><?= $pager->item_count ?></strong> voting sessions
        </div>
        <div>
          <?php
          $href = http_build_query(['q' => $search]);
          $pager->html($href, "class='pagination-wrapper'");
          ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php
if ($roleUpper === 'NOMINEE') {
  require_once __DIR__ . '/../NomineeView/nomineeFooter.php';
} elseif ($roleUpper === 'STUDENT') {
  require_once __DIR__ . '/../StudentView/studentFooter.php';
}
?>