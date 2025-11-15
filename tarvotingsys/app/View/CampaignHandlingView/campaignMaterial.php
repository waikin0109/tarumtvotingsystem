<?php
$_title = "Campaign Materials Application";
require_once __DIR__ . '/../AdminView/adminHeader.php';

/** @var array $campaignMaterials */

// KL timezone + current time for comparisons
$tz  = new DateTimeZone('Asia/Kuala_Lumpur');
$now = new DateTime('now', $tz);
?>
<div>
  <div class="container-fluid d-flex justify-content-between align-items-center mb-4">
    <div class="row w-100">
      <div class="col-sm-6">
        <h2>Campaign Materials Application</h2>
      </div>
      <div class="col-sm-6">
        <a href="/admin/campaign-material/create">
          <button class="btn btn-primary mx-2 me-5 position-absolute end-0">Create (+)</button>
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
              <th class="col-sm-1">No.</th>
              <th class="col-sm-3">Materials Title</th>
              <th class="col-sm-2">Nominee Applicant</th>
              <th class="col-sm-2">Election Event</th>
              <th class="col-sm-2">Status</th>
              <th class="col-sm-2">Action</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($campaignMaterials)): ?>
            <tr>
              <td colspan="6" class="text-center text-muted">No campaign materials found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($campaignMaterials as $index => $material):
              $id     = (string)($material['materialsApplicationID'] ?? '');
              $title  = (string)($material['materialsTitle'] ?? '');
              $nom    = (string)($material['fullName'] ?? '');
              $event  = (string)($material['electionEventTitle'] ?? '');
              $status = (string)($material['materialsApplicationStatus'] ?? '');
              $endStr = $material['electionEndDate'] ?? null; // must be selected in model
              $endAt  = $endStr ? new DateTime($endStr, $tz) : null;
              $closed = $endAt && ($now > $endAt); // disable buttons if ended
            ?>
              <tr class="clickable-row" data-href="/admin/campaign-material/view/<?= urlencode($id) ?>">
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($title) ?></td>
                <td><?= htmlspecialchars($nom) ?></td>
                <td>
                  <?= htmlspecialchars($event) ?>
                </td>
                <td>
                  <?php
                    $badge = 'bg-secondary';
                    $s = strtoupper($status);
                    if ($s === 'APPROVED' || $s === 'ACCEPTED') $badge = 'bg-success';
                    elseif ($s === 'REJECTED' || $s === 'DENIED') $badge = 'bg-danger';
                    elseif ($s === 'PENDING') $badge = 'bg-warning text-dark';
                  ?>
                  <span class="badge <?= $badge ?>"><?= htmlspecialchars($status) ?></span>
                </td>
                <td onclick="event.stopPropagation()">
                  <a href="/admin/campaign-material/edit/<?= urlencode($id) ?>"
                     class="btn btn-sm btn-warning <?= $closed ? 'disabled' : '' ?>"
                     <?= $closed ? 'aria-disabled="true" tabindex="-1" title="Disabled after election end"' : '' ?>>
                    Edit
                  </a>

                  <form method="POST" action="/admin/campaign-material/accept/<?= urlencode($id) ?>" class="d-inline"
                        onsubmit="return <?= $closed ? 'false' : 'confirm(\'Approve this campaign material?\')' ?>;">
                    <button type="submit" class="btn btn-sm btn-success"
                            <?= $closed ? 'disabled title="Disabled after election end"' : '' ?>>
                      Approve
                    </button>
                  </form>

                  <form method="POST" action="/admin/campaign-material/reject/<?= urlencode($id) ?>" class="d-inline"
                        onsubmit="return <?= $closed ? 'false' : 'confirm(\'Reject this campaign material?\')' ?>;">
                    <button type="submit" class="btn btn-sm btn-danger"
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
  </div>
</div>

<!-- Clickable Row -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.clickable-row').forEach(row => {
    row.addEventListener('click', e => {
      // Ignore clicks on interactive elements so forms/links work
      if (e.target.closest('a, button, input, select, textarea, label, form')) return;
      window.location.href = row.dataset.href;
    });
  });

  // Extra safety: stop row-click bubbling from action controls
  document.querySelectorAll('.clickable-row .btn, .clickable-row form')
    .forEach(el => el.addEventListener('click', e => e.stopPropagation()));
});
</script>

<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>
