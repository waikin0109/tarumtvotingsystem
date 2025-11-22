<?php
$_title = 'Campaign Material Lists';
require_once __DIR__ . '/../NomineeView/nomineeHeader.php';

// Safe defaults
$campaignMaterials = $campaignMaterials ?? [];
$search            = $search       ?? '';
$filterStatus      = $filterStatus ?? '';
?>

<div class="container-fluid mt-4 mb-5">

  <!-- Header + Apply button (same layout style) -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
    <div>
      <h2 class="mb-0">Campaign Materials – My Applications</h2>
      <p class="text-muted small mb-0">
        View and manage your submitted campaign materials for each election event.
      </p>
    </div>

    <div class="text-md-end">
      <a href="/nominee/campaign-material/create" class="btn btn-primary mx-1">
        Apply (+)
      </a>
    </div>
  </div>

  <!-- Search + filter bar -->
  <div class="card mb-4">
    <div class="card-body">
      <form class="row g-2 align-items-end" method="get" action="/nominee/campaign-material">
        <div class="col-md-5">
          <label for="q" class="form-label mb-1">Search By Title/Election Event</label>
          <input
            type="text"
            id="q"
            name="q"
            class="form-control"
            placeholder="Search by Campaign Material Title / Election Event..."
            value="<?= htmlspecialchars($search) ?>"
          >
        </div>

        <div class="col-md-3">
          <label for="status" class="form-label mb-1">Application Status</label>
          <select id="status" name="status" class="form-select">
            <option value="">All statuses</option>
            <option value="PENDING"   <?= $filterStatus === 'PENDING'   ? 'selected' : '' ?>>Pending</option>
            <option value="APPROVED"  <?= $filterStatus === 'APPROVED'  ? 'selected' : '' ?>>Approved</option>
            <option value="REJECTED"  <?= $filterStatus === 'REJECTED'  ? 'selected' : '' ?>>Rejected</option>
          </select>
        </div>

        <div class="col-md-4 text-md-end">
          <button type="submit" class="btn btn-outline-primary me-2">
            Search
          </button>
          <?php if ($search !== '' || $filterStatus !== ''): ?>
            <a href="/nominee/campaign-material" class="btn btn-link text-decoration-none">
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
              <th class="col-sm-4">Campaign Material Title</th>
              <th class="col-sm-3">Election Event</th>
              <th class="col-sm-2">Status</th>
              <th class="col-sm-2">Actions</th>
            </tr>
          </thead>

          <tbody>
            <?php if (empty($campaignMaterials)): ?>
              <tr>
                <td colspan="5" class="text-center text-muted py-4">
                  <?php if ($search !== '' || $filterStatus !== ''): ?>
                    No campaign materials matched your filters.
                  <?php else: ?>
                    No campaign materials found.
                  <?php endif; ?>
                </td>
              </tr>
            <?php else: ?>
              <?php
                // Row numbering with paging support (same pattern as scheduleLocation)
                $startNumber = isset($pager)
                    ? (($pager->page - 1) * $pager->limit) + 1
                    : 1;
              ?>

              <?php foreach ($campaignMaterials as $index => $campaignMaterial):
                $materialId = (int)($campaignMaterial['materialsApplicationID'] ?? 0);
                $status     = (string)($campaignMaterial['materialsApplicationStatus'] ?? '');
                $statusUp   = strtoupper($status);

                $badgeClass = 'bg-secondary';
                if ($statusUp === 'APPROVED') {
                  $badgeClass = 'bg-success';
                } elseif ($statusUp === 'REJECTED') {
                  $badgeClass = 'bg-danger';
                } elseif ($statusUp === 'PENDING') {
                  $badgeClass = 'bg-warning text-dark';
                }
              ?>
                <tr class="clickable-row"
                    data-href="/nominee/campaign-material/view/<?= urlencode((string)$materialId) ?>">
                  <td><?= $startNumber + $index ?></td>
                  <td><?= htmlspecialchars($campaignMaterial['materialsTitle'] ?? '') ?></td>
                  <td><?= htmlspecialchars($campaignMaterial['electionEventTitle'] ?? '—') ?></td>
                  <td>
                    <span class="badge <?= $badgeClass ?>">
                      <?= htmlspecialchars($status ?: 'UNKNOWN') ?>
                    </span>
                  </td>
                  <td class="text-nowrap" onclick="event.stopPropagation()">
                    <a href="/nominee/campaign-material/view/<?= urlencode((string)$materialId) ?>"
                       class="btn btn-sm btn-secondary">
                      View
                    </a>
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
          of <strong><?= $pager->item_count ?></strong> campaign materials
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

<?php require_once __DIR__ . '/../NomineeView/nomineeFooter.php'; ?>
