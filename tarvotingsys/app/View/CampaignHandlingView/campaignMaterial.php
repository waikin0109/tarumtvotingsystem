<?php
$_title = "Campaign Materials Application";
require_once __DIR__ . '/../AdminView/adminHeader.php';

/** @var array $campaignMaterials */

// KL timezone + current time for comparisons
$tz  = new DateTimeZone('Asia/Kuala_Lumpur');
$now = new DateTime('now', $tz);

// Safe defaults for search + filter + paging
$campaignMaterials = $campaignMaterials ?? [];
$search            = $search       ?? '';
$filterStatus      = $filterStatus ?? '';
?>

<div class="container-fluid mt-4 mb-5">

  <!-- Header + Create button (same style as scheduleLocation) -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
    <div>
      <h2 class="mb-0">Campaign Materials – Applications</h2>
      <p class="text-muted small mb-0">
        Review and manage campaign materials submitted by nominees for each election event.
      </p>
    </div>

    <div class="text-md-end">
      <a href="/admin/campaign-material/create" class="btn btn-primary mx-1">
        Create (+)
      </a>
    </div>
  </div>

  <!-- Search + filter bar (mirrors scheduleLocation) -->
  <div class="card mb-4">
    <div class="card-body">
      <form class="row g-2 align-items-end" method="get" action="/admin/campaign-material">
        <div class="col-md-5">
          <label for="q" class="form-label mb-1">Search By Title/Nominee/Election Event</label>
          <input
            type="text"
            id="q"
            name="q"
            class="form-control"
            placeholder="Search by Materials Title / Nominee / Election Event..."
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
            <a href="/admin/campaign-material" class="btn btn-link text-decoration-none">
              Reset
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- Card + Table (same structure as scheduleLocation) -->
  <div class="card mb-4" style="box-shadow:0 0.1rem 1rem rgba(0,0,0,.15);">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="col-sm-1">No.</th>
              <th class="col-sm-3">Materials Title</th>
              <th class="col-sm-2">Nominee Applicant</th>
              <th class="col-sm-2">Election Event</th>
              <th class="col-sm-1">Status</th>
              <th class="col-sm-3">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($campaignMaterials)): ?>
            <tr>
              <td colspan="6" class="text-center text-muted py-4">
                <?php if ($search !== '' || $filterStatus !== ''): ?>
                  No campaign materials matched your filters.
                <?php else: ?>
                  No campaign materials found.
                <?php endif; ?>
              </td>
            </tr>
          <?php else: ?>
            <?php
            // Row numbering with paging support (same as scheduleLocation)
            $startNumber = isset($pager)
                ? (($pager->page - 1) * $pager->limit) + 1
                : 1;
            ?>

            <?php foreach ($campaignMaterials as $index => $material):
              $id     = (string)($material['materialsApplicationID'] ?? '');
              $title  = (string)($material['materialsTitle'] ?? '');
              $nom    = (string)($material['fullName'] ?? '');
              $event  = (string)($material['electionEventTitle'] ?? '');
              $status = (string)($material['materialsApplicationStatus'] ?? '');
              $endStr = $material['electionEndDate'] ?? null; // must be selected in model
              $endAt  = $endStr ? new DateTime($endStr, $tz) : null;
              $closed = $endAt && ($now > $endAt); // disable buttons if ended

              // Badge class (same logic as your original file)
              $badge = 'bg-secondary';
              $s = strtoupper($status);
              if ($s === 'APPROVED') {
                $badge = 'bg-success';
              } elseif ($s === 'REJECTED') {
                $badge = 'bg-danger';
              } elseif ($s === 'PENDING') {
                $badge = 'bg-warning text-dark';
              }
            ?>
              <tr class="clickable-row" data-href="/admin/campaign-material/view/<?= urlencode($id) ?>">
                <td><?= $startNumber + $index ?></td>
                <td><?= htmlspecialchars($title) ?></td>
                <td><?= htmlspecialchars($nom) ?></td>
                <td><?= htmlspecialchars($event) ?></td>
                <td>
                  <span class="badge <?= $badge ?>">
                    <?= htmlspecialchars($status ?: 'UNKNOWN') ?>
                  </span>
                </td>
                <td class="text-nowrap" onclick="event.stopPropagation()">
                  <a href="/admin/campaign-material/edit/<?= urlencode($id) ?>"
                     class="btn btn-sm btn-warning me-1 <?= $closed ? 'disabled' : '' ?>"
                     <?= $closed ? 'aria-disabled="true" tabindex="-1" title="Disabled after election end"' : '' ?>>
                    Edit
                  </a>

                  <form method="POST"
                        action="/admin/campaign-material/accept/<?= urlencode($id) ?>"
                        class="d-inline"
                        onsubmit="return <?= $closed ? 'false' : 'confirm(\'Approve this campaign material?\')' ?>;">
                    <button type="submit"
                            class="btn btn-sm btn-success me-1"
                            <?= $closed ? 'disabled title="Disabled after election end"' : '' ?>>
                      Approve
                    </button>
                  </form>

                  <form method="POST"
                        action="/admin/campaign-material/reject/<?= urlencode($id) ?>"
                        class="d-inline"
                        onsubmit="return <?= $closed ? 'false' : 'confirm(\'Reject this campaign material?\')' ?>;">
                    <button type="submit"
                            class="btn btn-sm btn-danger"
                            <?= $closed ? 'disabled title="Disabled after election end"' : '' ?>>
                      Reject
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pager row (same as scheduleLocation) -->
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
          of <strong><?= $pager->item_count ?></strong> campaign materials applications
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

<!-- Clickable Row (same script pattern as scheduleLocation) -->
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
