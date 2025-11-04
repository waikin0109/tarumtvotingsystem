<?php
$_title = "Campaign Materials Application";
require_once __DIR__ . '/../AdminView/adminHeader.php';
?>

<div>
  <div class="container-fluid d-flex justify-content-between align-items-center mb-4">
    <div class="row w-100">
      <div class="col-sm-6">
        <h2>Campaign Materials Application</h2>
      </div>
      <div class="col-sm-6">
        <a href="/campaign-material/create">
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
              $id = (string)($material['materialsApplicationID'] ?? '');
            ?>
              <tr class="clickable-row" data-href="/campaign-material/view/<?= urlencode($id) ?>">
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($material['materialsTitle'] ?? '') ?></td>
                <td><?= htmlspecialchars($material['fullName'] ?? '') ?></td>
                <td><?= htmlspecialchars($material['electionEventTitle'] ?? '') ?></td>
                <td><?= htmlspecialchars($material['materialsApplicationStatus'] ?? '') ?></td>
                <td onclick="event.stopPropagation()">
                  <a href="/campaign-material/edit/<?= urlencode($id) ?>" class="btn btn-sm btn-warning">Edit</a>
                  <form method="POST" action="/campaign-material/accept/<?= urlencode($id) ?>" class="d-inline"
                        onsubmit="return confirm('Accept this campaign material?');">
                    <button type="submit" class="btn btn-sm btn-success">Accept</button>
                  </form>
                  <form method="POST" action="/campaign-material/reject/<?= urlencode($id) ?>" class="d-inline"
                        onsubmit="return confirm('Reject this campaign material?');">
                    <button type="submit" class="btn btn-sm btn-danger">Reject</button>
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



<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>
