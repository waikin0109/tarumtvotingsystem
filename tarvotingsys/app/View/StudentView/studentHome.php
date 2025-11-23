<?php
$_title = 'Student Home';
require_once __DIR__ . '/../StudentView/studentHeader.php';

// Safe defaults
$studentStats = $studentStats ?? [
    'ongoingElectionEvents'   => 0,
    'completedElectionEvents' => 0,
];

$recentElections = $recentElections ?? [];
$fullName        = $_SESSION['fullName'] ?? 'Student';
?>

<div class="container-fluid mt-4 mb-5">

  <!-- Welcome header -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
    <div>
      <h2 class="mb-1">Welcome, <?= htmlspecialchars($fullName) ?></h2>
      <p class="text-muted small mb-0">
        This is your student dashboard. Check election information, rules,
        nominee lists, and campaign schedules in one place.
      </p>
    </div>
  </div>

  <!-- Top stats cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Ongoing Elections</div>
          <div class="h3 mb-2"><?= (int)$studentStats['ongoingElectionEvents'] ?></div>
          <p class="small text-muted mb-2">
            Elections that are currently in progress and may be open for voting.
          </p>
          <a href="/student/nominee-final-list" class="small text-decoration-none">
            View nominees &raquo;
          </a>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Completed Elections</div>
          <div class="h3 mb-2"><?= (int)$studentStats['completedElectionEvents'] ?></div>
          <p class="small text-muted mb-2">
            Elections that have already ended. You can still view nominees and rules.
          </p>
          <a href="/student/rule" class="small text-decoration-none">
            View rules &raquo;
          </a>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Become a Nominee</div>
          <div class="h3 mb-2">
            <i class="bi bi-person-badge"></i>
          </div>
          <p class="small text-muted mb-2">
            Interested to stand as a candidate? Browse available registration forms.
          </p>
          <a href="/student/election-registration-form" class="btn btn-sm btn-primary">
            Go to Registration
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick access cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h5 class="card-title mb-2">Rules &amp; Regulations</h5>
          <p class="card-text small text-muted mb-3">
            Read the official election rules for each election event before voting.
          </p>
          <a href="/student/rule" class="btn btn-sm btn-outline-primary">
            View Rules
          </a>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h5 class="card-title mb-2">Nominees' Final Lists</h5>
          <p class="card-text small text-muted mb-3">
            See the final confirmed candidate lists for ongoing and upcoming elections.
          </p>
          <a href="/student/nominee-final-list" class="btn btn-sm btn-outline-primary">
            View Nominees
          </a>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h5 class="card-title mb-2">Campaign Timetable</h5>
          <p class="card-text small text-muted mb-3">
            Check when and where campaign activities and debates will be held.
          </p>
          <a href="/student/schedule-location" class="btn btn-sm btn-outline-primary">
            View Timetable
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent election events table -->
  <div class="card mb-4" style="box-shadow:0 0.1rem 1rem rgba(0,0,0,.15);">
    <div class="card-header bg-white border-0">
      <h5 class="mb-0">Latest Election Events</h5>
      <p class="text-muted small mb-0">
        Recently created or updated elections in the system.
      </p>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="col-sm-4">Election</th>
              <th class="col-sm-2">Status</th>
              <th class="col-sm-4">Period</th>
              <th class="col-sm-2 text-end">Action</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($recentElections)): ?>
            <tr>
              <td colspan="4" class="text-center text-muted py-4">
                No election events available yet.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($recentElections as $election): ?>
              <?php
                $title  = $election['title'] ?? '—';
                $status = strtoupper(trim($election['status'] ?? ''));
                $start  = $election['electionStartDate'] ?? null;
                $end    = $election['electionEndDate']   ?? null;

                $badgeClass = match ($status) {
                    'PENDING'   => 'bg-secondary',
                    'ONGOING'   => 'bg-warning',
                    'COMPLETED' => 'bg-success',
                    default     => 'bg-secondary',
                };

                $periodText = '—';
                if ($start && $end) {
                    $periodText = htmlspecialchars($start) . ' to ' . htmlspecialchars($end);
                }
              ?>
              <tr>
                <td><?= htmlspecialchars($title) ?></td>
                <td>
                  <?php if ($status): ?>
                    <span class="badge <?= $badgeClass ?>">
                      <?= htmlspecialchars($status) ?>
                    </span>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
                <td><?= $periodText ?></td>
                <td class="text-end">
                  <!-- Use nominee-final-list search to jump to this election's nominees -->
                  <a href="/student/nominee-final-list?q=<?= urlencode($title) ?>"
                     class="btn btn-sm btn-outline-secondary">
                    View Nominees
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?php
require_once __DIR__ . '/../StudentView/studentFooter.php';
?>