<?php
$_title = 'Schedule & Location List';
require_once __DIR__ . '/../AdminView/adminHeader.php';

$scheduleLocations = $scheduleLocations ?? [];
$search            = $search       ?? '';
$filterStatus      = $filterStatus ?? '';
?>

<div class="container-fluid mt-4 mb-5">

  <!-- Header + buttons (same style as nomineeApplication) -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
    <div>
      <h2 class="mb-0">Schedule &amp; Location – Event Applications</h2>
      <p class="text-muted small mb-0">
        Review and manage nominee event applications, including schedules and locations.
      </p>
    </div>

    <div class="text-md-end">
      <a href="/admin/schedule-location/schedule" class="btn btn-outline-primary mx-1">
        Schedule
      </a>
      <a href="/admin/schedule-location/create" class="btn btn-primary mx-1">
        Create (+)
      </a>
    </div>
  </div>

  <!-- Search + filter bar -->
  <div class="card mb-4">
    <div class="card-body">
      <form class="row g-2 align-items-end" method="get" action="/admin/schedule-location">
        <div class="col-md-5">
          <label for="q" class="form-label mb-1">Search By Event/Election Event/Nominee</label>
          <input
            type="text"
            id="q"
            name="q"
            class="form-control"
            placeholder="Search by Event / Election / Nominee..."
            value="<?= htmlspecialchars($search) ?>"
          >
        </div>

        <div class="col-md-3">
          <label for="status" class="form-label mb-1">Application Status</label>
          <select id="status" name="status" class="form-select">
            <option value="">All statuses</option>
            <option value="PENDING"  <?= $filterStatus === 'PENDING'  ? 'selected' : '' ?>>Pending</option>
            <option value="ACCEPTED" <?= $filterStatus === 'ACCEPTED' ? 'selected' : '' ?>>Accepted</option>
            <option value="REJECTED" <?= $filterStatus === 'REJECTED' ? 'selected' : '' ?>>Rejected</option>
          </select>
        </div>

        <div class="col-md-4 text-md-end">
          <button type="submit" class="btn btn-outline-primary me-2">
            Search
          </button>
          <?php if ($search !== '' || $filterStatus !== ''): ?>
            <a href="/admin/schedule-location" class="btn btn-link text-decoration-none">
              Reset
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- Card + Table -->
  <div class="card mb-4" style="box-shadow:0 0.1rem 1rem rgba(0,0,0,.15);">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="col-sm-1">No.</th>
              <th class="col-sm-2">Event Name</th>
              <th class="col-sm-2">Related Election Event</th>
              <th class="col-sm-2">Nominee Name</th>
              <th class="col-sm-2">Admin Name</th>
              <th class="col-sm-1">Status</th>
              <th class="col-sm-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($scheduleLocations)): ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-4">
                  <?php if ($search !== '' || $filterStatus !== ''): ?>
                    No schedule &amp; location applications matched your filters.
                  <?php else: ?>
                    No schedule &amp; location applications found.
                  <?php endif; ?>
                </td>
              </tr>
            <?php else: ?>
              <?php
              // Row numbering with paging support
              $startNumber = isset($pager)
                  ? (($pager->page - 1) * $pager->limit) + 1
                  : 1;
              ?>
              <?php foreach ($scheduleLocations as $index => $sl):
                $id     = urlencode($sl['eventApplicationID'] ?? '');
                $status = strtoupper($sl['eventApplicationStatus'] ?? '');
                $badge  = match ($status) {
                  'ACCEPTED' => 'bg-success',
                  'REJECTED' => 'bg-danger',
                  'PENDING'  => 'bg-warning text-dark',
                  default    => 'bg-secondary',
                };
              ?>
                <tr class="clickable-row"
                    data-href="/admin/schedule-location/view/<?= $id ?>">
                  <td><?= $startNumber + $index ?></td>
                  <td><?= htmlspecialchars($sl['eventName'] ?? '') ?></td>
                  <td><?= htmlspecialchars($sl['election_event'] ?? '') ?></td>
                  <td><?= htmlspecialchars($sl['nominee_fullName'] ?? '') ?></td>
                  <td><?= htmlspecialchars($sl['admin_fullName'] ?? '-') ?></td>
                  <td>
                    <span class="badge <?= $badge ?>">
                      <?= htmlspecialchars($status ?: 'UNKNOWN') ?>
                    </span>
                  </td>
                  <td class="text-nowrap" onclick="event.stopPropagation()">
                    <?php if ($status === 'PENDING'): ?>
                      <a href="/admin/schedule-location/edit/<?= $id ?>"
                         class="btn btn-sm btn-warning me-1">
                        Edit
                      </a>
                    <?php else: ?>
                      <a href="/admin/schedule-location/view-schedule"
                         class="btn btn-sm btn-info">
                        View schedule
                      </a>
                    <?php endif; ?>
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
            $to   = ($pager->page - 1) * $pager->limit + $pager->count;
          ?>
          Showing <strong><?= $from ?></strong> to <strong><?= $to ?></strong>
          of <strong><?= $pager->item_count ?></strong> schedule &amp; location applications
        </div>
        <div>
          <?php
            // Keep current filters in pager links
            $href = http_build_query([
              'q'      => $search,
              'status' => $filterStatus,
            ]);
            $pager->html($href, "class='pagination-wrapper'");
          ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Clickable Row -->
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
});
</script>

<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>
