<?php
$_title = 'Edit Campaign Material';
require_once __DIR__ . '/../AdminView/adminHeader.php';

// Helpers
function invalid(array $fe, string $key){ return !empty($fe[$key]) ? ' is-invalid' : ''; }
?>
<div class="container mt-4 mb-5">
  <h2 class="mb-3">Edit Campaign Material</h2>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0">
      <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul></div>
  <?php endif; ?>

  <form action="/campaign-material/edit/<?= (int)$campaignMaterial['materialsApplicationID'] ?>" method="POST" enctype="multipart/form-data" id="appForm" novalidate>
    <!-- Election (display only) -->
    <div class="mb-3">
      <label class="form-label">Election Event</label>
      <div class="form-control-plaintext fw-semibold">
        <?= htmlspecialchars($campaignMaterial['electionEventTitle'] ?? '—') ?>
      </div>
    </div>

    <!-- Nominee (display only) -->
    <div class="mb-3">
      <label class="form-label">Nominee</label>
      <div class="form-control-plaintext">
        <?= htmlspecialchars(($campaignMaterial['nomineeFullName'] ?? '—') . ' (Nominee ID ' . (int)$campaignMaterial['nomineeID'] . ')') ?>
      </div>
    </div>

    <!-- Title -->
    <div class="mb-3">
      <label for="materialsTitle" class="form-label">Title</label>
      <input type="text" class="form-control<?= invalid($fieldErrors,'materialsTitle') ?>" id="materialsTitle" name="materialsTitle"
             value="<?= htmlspecialchars($campaignMaterial['materialsTitle'] ?? '') ?>" required>
      <?php if (!empty($fieldErrors['materialsTitle'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($fieldErrors['materialsTitle'][0]) ?></div>
      <?php endif; ?>
    </div>

    <!-- Type -->
    <div class="mb-3">
      <label for="materialsType" class="form-label">Type</label>
      <select class="form-select<?= invalid($fieldErrors,'materialsType') ?>" id="materialsType" name="materialsType" required>
        <?php
          $cur = $campaignMaterial['materialsType'] ?? '';
          $opts = ['PHYSICAL'=>'PHYSICAL','DIGITAL'=>'DIGITAL'];
          foreach ($opts as $v=>$t):
        ?>
          <option value="<?= $v ?>" <?= $cur===$v?'selected':'' ?>><?= $t ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (!empty($fieldErrors['materialsType'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($fieldErrors['materialsType'][0]) ?></div>
      <?php endif; ?>
    </div>

    <!-- Description -->
    <div class="mb-3">
      <label for="materialsDesc" class="form-label">Description</label>
      <textarea class="form-control<?= invalid($fieldErrors,'materialsDesc') ?>" id="materialsDesc" name="materialsDesc" rows="4" required><?= htmlspecialchars($campaignMaterial['materialsDesc'] ?? '') ?></textarea>
      <?php if (!empty($fieldErrors['materialsDesc'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($fieldErrors['materialsDesc'][0]) ?></div>
      <?php endif; ?>
    </div>

    <!-- Quantity -->
    <div class="mb-3">
      <label for="materialsQuantity" class="form-label">Quantity</label>
      <input type="number" class="form-control<?= invalid($fieldErrors,'materialsQuantity') ?>" id="materialsQuantity" name="materialsQuantity" min="1" step="1"
             value="<?= (int)($campaignMaterial['materialsQuantity'] ?? 1) ?>" required>
      <?php if (!empty($fieldErrors['materialsQuantity'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($fieldErrors['materialsQuantity'][0]) ?></div>
      <?php endif; ?>
    </div>

    <!-- Existing files (with view + delete) -->
    <div class="mb-3">
    <label class="form-label">Existing Files</label>
    <?php if (!empty($documents)): ?>
        <div class="list-group">
        <?php foreach ($documents as $d): ?>
            <?php
            // Build the public URL to the file
            $fileUrl = '/uploads/campaign_material/' . (int)$campaignMaterial['materialsApplicationID'] . '/' . rawurlencode($d['materialsFilename']);
            $docId   = (int)$d['materialsID']; // <— uses alias from the Model
            ?>
            <label class="list-group-item d-flex align-items-center justify-content-between">
            <span class="me-3"><?= htmlspecialchars($d['materialsFilename']) ?></span>
            <span class="d-flex align-items-center gap-3">
                <a class="btn btn-sm btn-outline-secondary" href="<?= $fileUrl ?>" target="_blank" rel="noopener">View</a>
                <div class="form-check m-0">
                <input class="form-check-input cm-del" type="checkbox"
                        value="<?= $docId ?>" id="del<?= $docId ?>" name="delete_docs[]">
                <label class="form-check-label" for="del<?= $docId ?>">Delete</label>
                </div>
            </span>
            </label>
        <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-muted">No files uploaded yet.</div>
    <?php endif; ?>
    </div>


    <!-- Add new files -->
    <div class="mb-3">
      <label for="materialsFiles" class="form-label">Add Files</label>
      <input type="file" class="form-control<?= invalid($fieldErrors,'materialsFiles') ?>" id="materialsFiles" name="materialsFiles[]" multiple
             accept="image/*,.pdf,.doc,.docx,.ppt,.pptx">
      <?php if (!empty($fieldErrors['materialsFiles'])): ?>
        <div class="invalid-feedback d-block"><?= htmlspecialchars($fieldErrors['materialsFiles'][0]) ?></div>
      <?php endif; ?>
      <div class="form-text">You must have at least one file after deleting and adding.</div>
    </div>

    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary">Save changes</button>
      <a href="/campaign-material" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const delChecks = Array.from(document.querySelectorAll('.cm-del'));
  const fileInput = document.getElementById('materialsFiles');

  function totalFilesCount() {
    return delChecks.length;
  }
  function checkedCount() {
    return delChecks.filter(c => c.checked).length;
  }
  function hasNewUploads() {
    return (fileInput?.files?.length || 0) > 0;
  }

  delChecks.forEach(chk => {
    chk.addEventListener('change', function () {
      // Prevent deleting the LAST remaining file unless replacement is selected
      if (totalFilesCount() > 0 && checkedCount() === totalFilesCount() && !hasNewUploads()) {
        this.checked = false;
        alert('You must keep at least one file or upload a replacement first.');
      }
    });
  });
});
</script>

