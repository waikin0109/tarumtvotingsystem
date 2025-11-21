<?php
$_title = "Publish Final Nominee Applications";
require_once __DIR__ . '/../AdminView/adminHeader.php';
?>

<div class="container-fluid mt-4 mb-5">
    <!-- Page header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Publish Final Nominee Applications</h2>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <!-- Section: Select Election Event -->
            <h5 class="mb-3">Select Election Event</h5>

            <!-- POST form (keeps your publish action) -->
            <form action="/admin/nominee-application/publish"
                  method="POST"
                  id="publishForm"
                  onsubmit="return confirm('Are you sure you want to publish the nominee applications for the selected election event?');"
                  novalidate>

                <div class="mb-3">
                    <label for="electionEventID" class="form-label">
                        Election Event <span class="text-danger">*</span>
                    </label>
                    <select
                        id="electionEventID"
                        name="electionEventID"
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
                        <div class="text-danger small mt-2">
                            <?php foreach ($errors as $e): ?>
                                <?= htmlspecialchars($e) ?><br>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="d-flex justify-content-end gap-2 pt-2">
                    <a href="/admin/nominee-application" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Publish Nominees
                    </button>
                </div>
            </form>

            <!-- Tiny GET form used only for preview reload -->
            <form method="GET" class="mt-2" id="previewForm">
                <input type="hidden" name="electionEventID" value="">
            </form>

            <script>
            // auto-preview when selecting an event (GET reload)
            (function () {
                const select = document.getElementById('electionEventID');
                const previewForm = document.getElementById('previewForm');
                if (!select || !previewForm) return;

                select.addEventListener('change', function () {
                    previewForm.querySelector('input[name="electionEventID"]').value = this.value || '';
                    previewForm.submit();
                });
            })();
            </script>

            <!-- Preview accepted candidates -->
            <?php if (!empty($selectedEventId)): ?>
                <hr class="mt-4">
                <h5 class="mt-3 mb-2">Accepted Candidates Preview</h5>

                <?php if (empty($acceptedCandidates)): ?>
                    <div class="text-muted">
                        No accepted applications for this event.
                    </div>
                <?php else: ?>
                    <div class="table-responsive mt-2">
                        <table class="table table-bordered table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:5%;">#</th>
                                    <th style="width:30%;">Student Name</th>
                                    <th style="width:10%;">Student ID</th>
                                    <th style="width:20%;">Login ID</th>
                                    <th style="width:20%;">Program</th>
                                    <th style="width:15%;">Intake Year</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($acceptedCandidates as $i => $row): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
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
    </div>
</div>

<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>
