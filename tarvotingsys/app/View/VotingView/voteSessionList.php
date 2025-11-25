<?php
$_title = 'Voting Form';
require_once __DIR__ . '/../AdminView/adminHeader.php';

// safety guards
if (!isset($voteSessions) || !is_array($voteSessions))
  $voteSessions = [];

$isAdmin = strtoupper($_SESSION['role'] ?? '') === 'ADMIN';
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
    default => 'bg-light text-dark'
  };
}
?>

<div class="container-fluid mt-4 mb-5">

  <!-- Header + Create Button -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
    <div>
      <h2 class="mb-0">Voting Session</h2>
      <p class="text-muted small mb-0">
        Manage all voting sessions in the TARUMT voting system.
      </p>
    </div>
    <div>
      <a href="/vote-session/create" class="btn btn-primary">
        Create Voting Session (+)
      </a>
    </div>
  </div>

  <!-- Search Bar  -->
  <div class="card mb-4">
    <div class="card-body">
      <form class="row g-2 align-items-end" method="get" action="/vote-session">
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
            <a href="/vote-session" class="btn btn-link text-decoration-none">
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
              <th scope="col-sm-1">Status</th>
              <th scope="col-sm-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($voteSessions)): ?>
              <tr>
                <td colspan="6" class="text-center text-muted">No voting sessions found.</td>
              </tr>
            <?php else: ?>
              <?php
              $startNumber = isset($pager)
                ? (($pager->page - 1) * $pager->limit) + 1
                : 1;

              foreach ($voteSessions as $index => $s):
                $id = (int) ($s['VoteSessionID'] ?? $s['voteSessionID'] ?? 0);
                $name = $s['VoteSessionName'] ?? $s['voteSessionName'] ?? '';
                $eTitle = $s['ElectionTitle'] ?? $s['electionTitle'] ?? '';
                $status = strtoupper($s['VoteSessionStatus'] ?? $s['voteSessionStatus'] ?? '');
                $startAt = $s['StartAt'] ?? $s['startAt'] ?? $s['VoteSessionStartAt'] ?? null;
                $start = $startAt ? date('Y-m-d H:i:s', strtotime($startAt)) : '';
                $hasVoted = !empty($s['HasVoted']);
                ?>

                <tr class="clickable-row" data-href="/vote-session/details/<?= $id ?>">
                  <td><?= $startNumber + $index ?></td>

                  <td><?= htmlspecialchars($name) ?></td>

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
                          <a class="btn btn-sm btn-success" href="/ballot/start/<?= $id ?>">Vote</a>
                        <?php else: ?>
                          <button class="btn btn-sm btn-outline-secondary" disabled>Voted</button>
                        <?php endif; ?>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pager row -->
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

<script>
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.clickable-row').forEach(row => {
      row.addEventListener('click', e => {
        if (e.target.closest('a, button, input, select, textarea, label, form')) return;
        window.location.href = row.dataset.href;
      });
    });

    document.querySelectorAll('.clickable-row .btn, .clickable-row form')
      .forEach(el => el.addEventListener('click', e => e.stopPropagation()));

    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
  });
</script>

<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>