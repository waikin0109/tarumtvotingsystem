<?php
$_title = "Student's Nominee Application";
require_once __DIR__ . '/../AdminView/adminHeader.php';

/** @var array $na         // header & fixed fields
 *  @var array $showAttrs  // [ ['code','label','value'], ... ]
 *  @var array $documents  // rows from academicdocument
 */
$subId = (int)($na['applicationSubmissionID'] ?? 0);
$docBaseUrl = "/uploads/academic_document/" . $subId . "/";
?>

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Nominee Application Details</h2>
    <a href="/nominee-application" class="btn btn-outline-secondary">Back to List</a>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title">
        <?= htmlspecialchars(($na['registrationFormTitle'] ?? '') . ' (ID ' . ($na['registrationFormID'] ?? '') . ')') ?>
      </h5>

      <div class="row g-3">
        <div class="col-md-6">
          <p class="mb-1"><strong>Student:</strong>
            <?= htmlspecialchars(($na['student_fullname'] ?? '') . ' (Student ID ' . ($na['studentID'] ?? '') . ')') ?>
          </p>
          <p class="mb-1"><strong>Submitted Date:</strong>
            <?= htmlspecialchars($na['submittedDate'] ?? '') ?>
          </p>
        </div>
        <div class="col-md-6">
          <p class="mb-1"><strong>Application Status:</strong>
            <span class="badge
              <?= ($na['applicationStatus'] ?? '') === 'ACCEPTED' ? 'bg-success' :
                  (($na['applicationStatus'] ?? '') === 'REJECTED' ? 'bg-danger' : 'bg-secondary') ?>">
              <?= htmlspecialchars($na['applicationStatus'] ?? '') ?>
            </span>
          </p>
          <p class="mb-1"><strong>Admin Handler Name:</strong>
            <?= htmlspecialchars($na['admin_fullname'] ?? 'N/A') ?>
          </p>
        </div>
      </div>
    </div>
  </div>

  <!-- Dynamic fields (6) -->
  <div class="card mb-4">
    <div class="card-header"><strong>Submitted Information</strong></div>
    <div class="card-body">
      <?php if (empty($showAttrs)): ?>
        <p class="text-muted mb-0">No attributes were configured for this registration form.</p>
      <?php else: ?>
        <dl class="row mb-0">
          <?php foreach ($showAttrs as $f): ?>
            <dt class="col-sm-3"><?= htmlspecialchars($f['label']) ?></dt>
            <dd class="col-sm-9">
              <?php
                $val = $f['value'];
                if ($f['code'] === 'cgpa' && $val !== null && $val !== '') {
                  echo htmlspecialchars(number_format((float)$val, 2));
                } else {
                  // Achievements/Reason may be long text; keep formatting
                  echo $val === null || $val === '' ? '<span class="text-muted">—</span>'
                       : '<div class="white-space-pre-wrap">'.nl2br(htmlspecialchars((string)$val)).'</div>';
                }
              ?>
            </dd>
          <?php endforeach; ?>
        </dl>
      <?php endif; ?>
    </div>
  </div>

  <!-- Documents (7) -->
  <div class="card mb-5">
    <div class="card-header"><strong>Submitted Documents</strong></div>
    <div class="card-body">
      <?php if (empty($documents)): ?>
        <p class="text-muted mb-0">No documents uploaded.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 60px;">#</th>
                <th>File</th>
                <th style="width: 240px;">Category</th>
                <th style="width: 160px;">Preview</th>
                <th style="width: 140px;">Download</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $i = 1;
              foreach ($documents as $doc):
                $fname = (string)($doc['academicFilename'] ?? '');
                // Categorise by filename prefix (matches your save rules)
                $lower = strtolower($fname);
                if (str_starts_with($lower, 'cgpa_')) $cat = 'CGPA';
                elseif (str_starts_with($lower, 'achievement_')) $cat = 'Achievements';
                elseif (str_starts_with($lower, 'behaviorreport_')) $cat = 'Behavior Report';
                else $cat = 'Other';

                $url = $docBaseUrl . rawurlencode($fname);
              ?>
              <tr>
                <td><?= $i++ ?></td>
                <td class="text-break"><?= htmlspecialchars($fname) ?></td>
                <td><?= htmlspecialchars($cat) ?></td>
                <td>
                  <img src="<?= htmlspecialchars($url) ?>" alt="preview" class="img-thumbnail" style="max-height: 120px;">
                </td>
                <td>
                  <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener">Open</a>
                  <a class="btn btn-sm btn-primary ms-2" href="<?= htmlspecialchars($url) ?>" download>Download</a>
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
