<?php
$_title = "View Campaign Material";
require_once __DIR__ . '/../AdminView/adminHeader.php';

/** @var array $campaign  // from controller: id,eventTitle,nomineeName,title,type,desc,qty,status,adminID,badgeClass,docBaseUrl
 *  @var array $docs      // list: idx, filename, url, isImage
 */
?>
<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Campaign Material Details</h2>
    <div class="d-flex gap-2">
      <a href="/campaign-material" class="btn btn-outline-secondary">Back to List</a>
      <a href="/campaign-material/edit/<?= (int)$campaign['id'] ?>" class="btn btn-primary">Edit</a>
    </div>
  </div>

  <!-- Header Card -->
  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title mb-3"><?= htmlspecialchars($campaign['eventTitle']) ?></h5>

      <div class="row g-3">
        <div class="col-md-6">
          <p class="mb-1"><strong>Nominee:</strong> <?= htmlspecialchars($campaign['nomineeName']) ?></p>
          <p class="mb-1"><strong>Title:</strong> <?= htmlspecialchars($campaign['title']) ?></p>
          <p class="mb-1"><strong>Type:</strong> <?= htmlspecialchars($campaign['type']) ?></p>
          <p class="mb-1"><strong>Quantity:</strong> <?= htmlspecialchars((string)$campaign['qty']) ?></p>
        </div>
        <div class="col-md-6">
          <p class="mb-1">
            <strong>Status:</strong>
            <span class="badge <?= htmlspecialchars($campaign['badgeClass']) ?>">
              <?= htmlspecialchars($campaign['status']) ?>
            </span>
          </p>
          <p class="mb-1">
            <strong>Admin Handler:</strong>
            <?= empty($campaign['adminID']) ? '<span class="text-muted">N/A</span>' : (int)$campaign['adminID'] ?>
          </p>
        </div>
      </div>
    </div>
  </div>

  <!-- Description -->
  <div class="card mb-4">
    <div class="card-header"><strong>Description</strong></div>
    <div class="card-body">
      <?php if (($campaign['desc'] ?? '') === ''): ?>
        <p class="text-muted mb-0">—</p>
      <?php else: ?>
        <div class="white-space-pre-wrap"><?= nl2br(htmlspecialchars($campaign['desc'])) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Existing Files -->
  <div class="card mb-5">
    <div class="card-header"><strong>Existing Files</strong></div>
    <div class="card-body">
      <?php if (empty($docs)): ?>
        <p class="text-muted mb-0">No files uploaded.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:60px;">#</th>
                <th>Filename</th>
                <th style="width:180px;">Preview</th>
                <th style="width:200px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($docs as $row): ?>
                <tr>
                  <td><?= (int)$row['idx'] ?></td>
                  <td class="text-break"><?= htmlspecialchars($row['filename']) ?></td>
                  <td class="text-center">
                    <?php if (!empty($row['isImage'])): ?>
                      <img src="<?= htmlspecialchars($row['url']) ?>" alt="preview" class="img-thumbnail" style="max-height:120px;">
                    <?php else: ?>
                      <span class="text-muted">No preview</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($row['url']) ?>" target="_blank" rel="noopener">Open</a>
                    <a class="btn btn-sm btn-primary ms-2" href="<?= htmlspecialchars($row['url']) ?>" download>Download</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>
