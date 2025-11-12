<?php
$_title = "Publish Final Nominee Applications";
require_once __DIR__ . '/../AdminView/adminHeader.php';
?>

<div class="container mt-4">
    <h2>Publish Final Nominee Applications</h2>

    <!-- POST form (keeps your publish action) -->
    <form action="/admin/nominee-application/publish" method="POST" id="publishForm"
          onsubmit="return confirm('Are you sure you want to publish the nominee applications for the selected election event?');"
          novalidate>
        <div class="mb-3">
            <label class="form-label">Select Election Event to Publish Nominees For:</label>
            <select name="electionEventID"
                    class="form-select <?= !empty($fieldErrors['electionEventID']) ? 'is-invalid' : '' ?>">
                <option value="">-- Select Election Event --</option>
                <?php foreach ($electionEvents as $event): ?>
                    <option value="<?= (int)$event['electionID'] ?>"
                        <?= (
                            ((int)($selectedEventId ?? 0) === (int)$event['electionID']) ||
                            ((int)($old['electionEventID'] ?? 0) === (int)$event['electionID'])
                        ) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($event['title']) ?> (ID: <?= (int)$event['electionID'] ?>)
                    </option>

                <?php endforeach; ?>
            </select>
            <?php if (!empty($fieldErrors['electionEventID'])): ?>
                <div class="invalid-feedback">
                    <?= htmlspecialchars($fieldErrors['electionEventID']) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="text-danger">
                  <?php foreach ($errors as $e): ?><?= htmlspecialchars($e) ?><br/><?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary">Publish Nominees</button>
    </form>

    <!-- ====================== INSERT THIS PREVIEW BLOCK BELOW (outside the POST form) ====================== -->

    <!-- Tiny GET form used only for preview reload -->
    <form method="GET" class="mt-2" id="previewForm">
        <input type="hidden" name="electionEventID" value="">
    </form>

    <script>
      // auto-preview when selecting an event (GET reload)
      (function () {
        const select = document.querySelector('select[name="electionEventID"]');
        const previewForm = document.getElementById('previewForm');
        if (!select || !previewForm) return;
        select.addEventListener('change', function () {
          previewForm.querySelector('input[name="electionEventID"]').value = this.value || '';
          previewForm.submit();
        });
      })();
    </script>

    <?php if (!empty($selectedEventId)): ?>
      <hr>
      <h5 class="mt-3">Accepted candidates</h5>
      <?php if (empty($acceptedCandidates)): ?>
        <div class="text-muted">No accepted applications for this event.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Student Name</th>
                <th>Student ID</th>
                <th>loginID</th>
                <th>Program</th>
                <th>Intake Year</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($acceptedCandidates as $i => $row): ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td><?= htmlspecialchars($row['fullName']) ?></td>
                  <td><?= (int)$row['studentID'] ?></td>
                  <td><?= htmlspecialchars($row['loginID']) ?></td>
                  <td><?= htmlspecialchars($row['program']) ?></td>
                  <td><?= htmlspecialchars($row['intakeYear']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>
