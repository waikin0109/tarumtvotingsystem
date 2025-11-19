<?php
$_title = 'Election Event Editing';
require_once __DIR__ . '/../AdminView/adminHeader.php';
?>

<div class="container-fluid mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Edit Election Event</h2>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="/admin/election-event/edit/<?= urlencode($electionEventData['electionID'] ?? '') ?>" method="POST">
                <!-- Event basic info -->
                <div class="mb-3">
                    <label for="electionEventName" class="form-label">
                        Election Event Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           class="form-control <?= !empty($fieldErrors['electionEventName']) ? 'is-invalid' : '' ?>"
                           id="electionEventName"
                           name="electionEventName"
                           placeholder="e.g. SRC Election 2026/2027"
                           value="<?= htmlspecialchars($electionEventData['title'] ?? '') ?>">
                    <?php if (!empty($fieldErrors['electionEventName'])): ?>
                        <div class="invalid-feedback">
                            <?= htmlspecialchars(implode(' ', $fieldErrors['electionEventName'])) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="electionEventDescription" class="form-label">
                        Event Description <span class="text-danger">*</span>
                    </label>
                    <textarea
                        class="form-control <?= !empty($fieldErrors['electionEventDescription']) ? 'is-invalid' : '' ?>"
                        id="electionEventDescription"
                        name="electionEventDescription"
                        rows="4"
                        placeholder="Briefly describe the purpose, scope, and important notes for this election."
                    ><?= htmlspecialchars($electionEventData['description'] ?? '') ?></textarea>
                    <?php if (!empty($fieldErrors['electionEventDescription'])): ?>
                        <div class="invalid-feedback">
                            <?= htmlspecialchars(implode(' ', $fieldErrors['electionEventDescription'])) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Schedule -->
                <h5 class="mb-3">Election Event Schedule</h5>
                <div class="row g-3 mb-2">
                    <!-- Start -->
                    <div class="col-md-6">
                        <label for="electionEventStartDate" class="form-label">
                            Start Date <span class="text-danger">*</span>
                        </label>
                        <input type="date"
                               class="form-control <?= !empty($fieldErrors['electionEventStartDate']) ? 'is-invalid' : '' ?>"
                               id="electionEventStartDate"
                               name="electionEventStartDate"
                               value="<?= htmlspecialchars($electionEventData['startDate'] ?? '') ?>">
                        <?php if (!empty($fieldErrors['electionEventStartDate'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars(implode(' ', $fieldErrors['electionEventStartDate'])) ?>
                            </div>
                        <?php endif; ?>

                        <label for="electionEventStartTime" class="form-label mt-2">
                            Start Time <span class="text-danger">*</span>
                        </label>
                        <input type="time"
                               class="form-control <?= !empty($fieldErrors['electionEventStartTime']) ? 'is-invalid' : '' ?>"
                               id="electionEventStartTime"
                               name="electionEventStartTime"
                               value="<?= htmlspecialchars($electionEventData['startTime'] ?? '') ?>">
                        <?php if (!empty($fieldErrors['electionEventStartTime'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars(implode(' ', $fieldErrors['electionEventStartTime'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- End -->
                    <div class="col-md-6">
                        <label for="electionEventEndDate" class="form-label">
                            End Date <span class="text-danger">*</span>
                        </label>
                        <input type="date"
                               class="form-control <?= !empty($fieldErrors['electionEventEndDate']) ? 'is-invalid' : '' ?>"
                               id="electionEventEndDate"
                               name="electionEventEndDate"
                               value="<?= htmlspecialchars($electionEventData['endDate'] ?? '') ?>">
                        <?php if (!empty($fieldErrors['electionEventEndDate'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars(implode(' ', $fieldErrors['electionEventEndDate'])) ?>
                            </div>
                        <?php endif; ?>

                        <label for="electionEventEndTime" class="form-label mt-2">
                            End Time <span class="text-danger">*</span>
                        </label>
                        <input type="time"
                               class="form-control <?= !empty($fieldErrors['electionEventEndTime']) ? 'is-invalid' : '' ?>"
                               id="electionEventEndTime"
                               name="electionEventEndTime"
                               value="<?= htmlspecialchars($electionEventData['endTime'] ?? '') ?>">
                        <?php if (!empty($fieldErrors['electionEventEndTime'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars(implode(' ', $fieldErrors['electionEventEndTime'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex justify-content-end gap-2 pt-2">
                    <a href="/admin/election-event" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Update Election Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>
