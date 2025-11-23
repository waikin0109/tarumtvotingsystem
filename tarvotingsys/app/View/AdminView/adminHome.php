<?php
$_title = 'Admin Home';
require_once __DIR__ . '/../AdminView/adminHeader.php';

// Safe defaults
$dashboardStats  = isset($dashboardStats) && is_array($dashboardStats) ? $dashboardStats : [];
$recentElections = isset($recentElections) && is_array($recentElections) ? $recentElections : [];

$stats = array_merge([
    'totalElectionEvents'        => 0,
    'ongoingElectionEvents'      => 0,
    'totalNomineeApplications'   => 0,
    'pendingNomineeApplications' => 0,
    'pendingScheduleLocations'   => 0,
    'pendingCampaignMaterials'   => 0,
], $dashboardStats);
?>


<div class="container-fluid mt-4 mb-5">

  <!-- Page header -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
    <div>
      <h2 class="mb-0">Admin Dashboard</h2>
      <p class="text-muted small mb-0">
        Overview of TARUMT SRC election activities and quick access to key modules.
      </p>
    </div>
  </div>

  <!-- Summary cards row -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body">
          <p class="text-muted small mb-1">Total Election Events</p>
          <h3 class="mb-0"><?= (int)$stats['totalElectionEvents'] ?></h3>
        </div>
        <div class="card-footer bg-transparent border-0 pt-0">
          <a href="/admin/election-event" class="small text-decoration-none">
            Manage election events &raquo;
          </a>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body">
          <p class="text-muted small mb-1">Ongoing Elections</p>
          <h3 class="mb-0"><?= (int)$stats['ongoingElectionEvents'] ?></h3>
        </div>
        <div class="card-footer bg-transparent border-0 pt-0">
          <a href="/admin/election-event?status=ONGOING" class="small text-decoration-none">
            View ongoing events &raquo;
          </a>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body">
          <p class="text-muted small mb-1">Nominee Applications</p>
          <h3 class="mb-0"><?= (int)$stats['totalNomineeApplications'] ?></h3>
          <p class="mb-0 mt-1 small text-muted">
            Pending: <strong><?= (int)$stats['pendingNomineeApplications'] ?></strong>
          </p>
        </div>
        <div class="card-footer bg-transparent border-0 pt-0">
          <a href="/admin/nominee-application?status=PENDING" class="small text-decoration-none">
            Review pending applications &raquo;
          </a>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body">
          <p class="text-muted small mb-1">Pending Admin Actions</p>
          <h3 class="mb-0">
            <?= (int)$stats['pendingScheduleLocations'] + (int)$stats['pendingCampaignMaterials'] ?>
          </h3>
          <p class="mb-0 mt-1 small text-muted">
            Schedules: <strong><?= (int)$stats['pendingScheduleLocations'] ?></strong><br>
            Campaign materials: <strong><?= (int)$stats['pendingCampaignMaterials'] ?></strong>
          </p>
        </div>
        <div class="card-footer bg-transparent border-0 pt-0">
          <a href="/admin/schedule-location?status=PENDING" class="small text-decoration-none me-2">
            Schedule &amp; Location &raquo;
          </a>
          <a href="/admin/campaign-material?status=PENDING" class="small text-decoration-none">
            Campaign Materials &raquo;
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Main content: Quick links + Recent elections -->
  <div class="row g-4">
    <!-- Quick access panel -->
    <div class="col-lg-4">
      <div class="card h-100 shadow-sm">
        <div class="card-header bg-white">
          <h5 class="mb-0">Quick Access</h5>
        </div>
        <div class="card-body">
          <div class="list-group list-group-flush">
            <a href="/admin/election-event" class="list-group-item list-group-item-action">
              Election Events
              <span class="float-end text-muted small">&raquo;</span>
            </a>
            <a href="/admin/rule" class="list-group-item list-group-item-action">
              Rules &amp; Regulations
              <span class="float-end text-muted small">&raquo;</span>
            </a>
            <a href="/admin/nominee-application" class="list-group-item list-group-item-action">
              Nominee Registration Applications
              <span class="float-end text-muted small">&raquo;</span>
            </a>
            <a href="/admin/schedule-location" class="list-group-item list-group-item-action">
              Schedule &amp; Location – Event Applications
              <span class="float-end text-muted small">&raquo;</span>
            </a>
            <a href="/admin/campaign-material" class="list-group-item list-group-item-action">
              Campaign Materials – Applications
              <span class="float-end text-muted small">&raquo;</span>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent election events -->
    <div class="col-lg-8">
      <div class="card h-100 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Recent Election Events</h5>
          <a href="/admin/election-event" class="small text-decoration-none">
            View all &raquo;
          </a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="col-sm-4">Event Name</th>
                  <th class="col-sm-2">Status</th>
                  <th class="col-sm-3">Start Date</th>
                  <th class="col-sm-3">End Date</th>
                </tr>
              </thead>
              <tbody>
              <?php if (empty($recentElections)): ?>
                <tr>
                  <td colspan="4" class="text-center text-muted py-4">
                    No recent election events found.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($recentElections as $event):
                  $statusRaw = strtoupper(trim($event['status'] ?? ''));
                  $badgeClass = match ($statusRaw) {
                    'PENDING'   => 'bg-secondary',
                    'ONGOING'   => 'bg-warning',
                    'COMPLETED' => 'bg-success',
                    default     => 'bg-secondary',
                  };
                ?>
                  <tr class="clickable-row"
                      data-href="/admin/election-event/view/<?= urlencode($event['electionID'] ?? '') ?>">
                    <td><?= htmlspecialchars($event['title'] ?? '') ?></td>
                    <td>
                      <span class="badge <?= $badgeClass ?>">
                        <?= htmlspecialchars($statusRaw ?: 'UNKNOWN') ?>
                      </span>
                    </td>
                    <td><?= htmlspecialchars($event['electionStartDate'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($event['electionEndDate'] ?? '-') ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <div class="card-footer bg-white small text-muted">
          This panel shows the most recently created or updated election events.
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Clickable rows for recent elections -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.clickable-row').forEach(row => {
    row.addEventListener('click', e => {
      if (e.target.closest('a, button, input, select, textarea, label, form')) return;
      window.location.href = row.dataset.href;
    });
  });
});
</script>

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>
