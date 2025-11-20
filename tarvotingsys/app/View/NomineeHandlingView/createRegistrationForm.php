<?php
$_title = "Create Election Registration Form";
require_once __DIR__ . '/../AdminView/adminHeader.php';

// Safety guard
if (!isset($electionEvents) || !is_array($electionEvents)) {
    $electionEvents = [];
}
?>

<div class="container-fluid mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Create Election Registration Form</h2>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="/admin/election-registration-form/create" method="POST">
                <!-- Basic info -->
                <div class="mb-3">
                    <label for="registrationFormTitle" class="form-label">
                        Registration Form Title <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           class="form-control <?= !empty($fieldErrors['registrationFormTitle']) ? 'is-invalid' : '' ?>"
                           id="registrationFormTitle"
                           name="registrationFormTitle"
                           placeholder="e.g. SRC Nominee Application Form 2026/2027"
                           value="<?= htmlspecialchars($registrationFormData['registrationFormTitle'] ?? '') ?>">
                    <?php if (!empty($fieldErrors['registrationFormTitle'])): ?>
                        <div class="invalid-feedback">
                            <?= htmlspecialchars(implode(' ', $fieldErrors['registrationFormTitle'])) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Associated Election Event -->
                <div class="mb-4">
                    <label for="electionID" class="form-label">
                        Associated Election Event <span class="text-danger">*</span>
                    </label>
                    <select class="form-select <?= !empty($fieldErrors['electionID']) ? 'is-invalid' : '' ?>"
                            id="electionID"
                            name="electionID">
                        <option value="">Select an election event</option>
                        <?php foreach ($electionEvents as $event): ?>
                            <option value="<?= htmlspecialchars($event['electionID']) ?>"
                                <?= (isset($registrationFormData['electionID']) && $registrationFormData['electionID'] == $event['electionID']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($event['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($fieldErrors['electionID'])): ?>
                        <div class="invalid-feedback">
                            <?= htmlspecialchars(implode(' ', $fieldErrors['electionID'])) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Registration period -->
                <h5 class="mb-3">Registration Period</h5>
                <div class="row g-3 mb-4">
                    <!-- Start -->
                    <div class="col-md-6">
                        <label for="registrationStartDate" class="form-label">
                            Start Date <span class="text-danger">*</span>
                        </label>
                        <input type="date"
                               class="form-control <?= !empty($fieldErrors['registrationStartDate']) ? 'is-invalid' : '' ?>"
                               id="registrationStartDate"
                               name="registrationStartDate"
                               value="<?= htmlspecialchars($registrationFormData['registrationStartDate'] ?? '') ?>">
                        <?php if (!empty($fieldErrors['registrationStartDate'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars(implode(' ', $fieldErrors['registrationStartDate'])) ?>
                            </div>
                        <?php endif; ?>

                        <label for="registrationStartTime" class="form-label mt-2">
                            Start Time <span class="text-danger">*</span>
                        </label>
                        <input type="time"
                               class="form-control <?= !empty($fieldErrors['registrationStartTime']) ? 'is-invalid' : '' ?>"
                               id="registrationStartTime"
                               name="registrationStartTime"
                               value="<?= htmlspecialchars($registrationFormData['registrationStartTime'] ?? '') ?>">
                        <?php if (!empty($fieldErrors['registrationStartTime'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars(implode(' ', $fieldErrors['registrationStartTime'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- End -->
                    <div class="col-md-6">
                        <label for="registrationEndDate" class="form-label">
                            End Date <span class="text-danger">*</span>
                        </label>
                        <input type="date"
                               class="form-control <?= !empty($fieldErrors['registrationEndDate']) ? 'is-invalid' : '' ?>"
                               id="registrationEndDate"
                               name="registrationEndDate"
                               value="<?= htmlspecialchars($registrationFormData['registrationEndDate'] ?? '') ?>">
                        <?php if (!empty($fieldErrors['registrationEndDate'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars(implode(' ', $fieldErrors['registrationEndDate'])) ?>
                            </div>
                        <?php endif; ?>

                        <label for="registrationEndTime" class="form-label mt-2">
                            End Time <span class="text-danger">*</span>
                        </label>
                        <input type="time"
                               class="form-control <?= !empty($fieldErrors['registrationEndTime']) ? 'is-invalid' : '' ?>"
                               id="registrationEndTime"
                               name="registrationEndTime"
                               value="<?= htmlspecialchars($registrationFormData['registrationEndTime'] ?? '') ?>">
                        <?php if (!empty($fieldErrors['registrationEndTime'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars(implode(' ', $fieldErrors['registrationEndTime'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Registration Form Attributes -->
                <h5 class="mb-3">Registration Form Attributes</h5>
                <div class="mb-3">
                    <?php
                    $old = $registrationFormData['attributes'] ?? [];
                    $is  = fn($k) => in_array($k, $old, true) ? 'checked' : '';
                    ?>
                    <div id="registrationFormAttributes"
                         class="p-3 border rounded <?= !empty($fieldErrors['attributes']) ? 'border-danger' : '' ?>">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="attributes[]"
                                   value="cgpa"
                                   id="attr_cgpa" <?= $is('cgpa') ?>>
                            <label class="form-check-label" for="attr_cgpa">
                                CGPA
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="attributes[]"
                                   value="reason"
                                   id="attr_reason" <?= $is('reason') ?>>
                            <label class="form-check-label" for="attr_reason">
                                Reason for Participation
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="attributes[]"
                                   value="achievements"
                                   id="attr_achievements" <?= $is('achievements') ?>>
                            <label class="form-check-label" for="attr_achievements">
                                Achievements / Awards
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="attributes[]"
                                   value="behaviorReport"
                                   id="attr_behavior" <?= $is('behaviorReport') ?>>
                            <label class="form-check-label" for="attr_behavior">
                                Behavior Report
                            </label>
                        </div>
                    </div>
                    <?php if (!empty($fieldErrors['attributes'])): ?>
                        <div class="invalid-feedback d-block">
                            <?= htmlspecialchars(implode(' ', $fieldErrors['attributes'])) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="d-flex justify-content-end gap-2 pt-2">
                    <a href="/admin/election-registration-form" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" id="createRegistrationFormBtn">
                        Create Registration Form
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>
