<?php
$_title = 'Edit Registration Form';
require_once __DIR__ . '/../AdminView/adminHeader.php';
?>

<div class="container mt-4">
    <h2>Edit Registration Form</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="/election-registration-form/edit/<?= htmlspecialchars($registrationFormEditionData['registrationFormID']) ?>" method="POST">
        <!-- Title -->
        <div class="mb-3">
            <label for="registrationFormTitle" class="form-label">Registration Form Title</label>
            <input type="text"
                   class="form-control <?= !empty($fieldErrors['registrationFormTitle']) ? 'is-invalid' : '' ?>"
                   id="registrationFormTitle"
                   name="registrationFormTitle"
                   value="<?= htmlspecialchars($registrationFormEditionData['registrationFormTitle'] ?? '') ?>">
            <?php if (!empty($fieldErrors['registrationFormTitle'])): ?>
                <div class="invalid-feedback">
                    <?= htmlspecialchars(implode(' ', $fieldErrors['registrationFormTitle'])) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Start -->
        <div class="mb-3">
            <label for="registrationStartDate" class="form-label">Registration Start Date</label>
            <input type="date"
                   class="form-control <?= !empty($fieldErrors['registrationStartDate']) ? 'is-invalid' : '' ?>"
                   id="registrationStartDate"
                   name="registrationStartDate"
                   value="<?= htmlspecialchars($registrationFormEditionData['registrationStartDate'] ?? '') ?>">
            <?php if (!empty($fieldErrors['registrationStartDate'])): ?>
                <div class="invalid-feedback">
                    <?= htmlspecialchars(implode(' ', $fieldErrors['registrationStartDate'])) ?>
                </div>
            <?php endif; ?>

            <label for="registrationStartTime" class="form-label mt-2">Registration Start Time</label>
            <input type="time"
                   class="form-control <?= !empty($fieldErrors['registrationStartTime']) ? 'is-invalid' : '' ?>"
                   id="registrationStartTime"
                   name="registrationStartTime"
                   value="<?= htmlspecialchars($registrationFormEditionData['registrationStartTime'] ?? '') ?>">
            <?php if (!empty($fieldErrors['registrationStartTime'])): ?>
                <div class="invalid-feedback">
                    <?= htmlspecialchars(implode(' ', $fieldErrors['registrationStartTime'])) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- End -->
        <div class="mb-3">
            <label for="registrationEndDate" class="form-label">Registration End Date</label>
            <input type="date"
                   class="form-control <?= !empty($fieldErrors['registrationEndDate']) ? 'is-invalid' : '' ?>"
                   id="registrationEndDate"
                   name="registrationEndDate"
                   value="<?= htmlspecialchars($registrationFormEditionData['registrationEndDate'] ?? '') ?>">
            <?php if (!empty($fieldErrors['registrationEndDate'])): ?>
                <div class="invalid-feedback">
                    <?= htmlspecialchars(implode(' ', $fieldErrors['registrationEndDate'])) ?>
                </div>
            <?php endif; ?>

            <label for="registrationEndTime" class="form-label mt-2">Registration End Time</label>
            <input type="time"
                   class="form-control <?= !empty($fieldErrors['registrationEndTime']) ? 'is-invalid' : '' ?>"
                   id="registrationEndTime"
                   name="registrationEndTime"
                   value="<?= htmlspecialchars($registrationFormEditionData['registrationEndTime'] ?? '') ?>">
            <?php if (!empty($fieldErrors['registrationEndTime'])): ?>
                <div class="invalid-feedback">
                    <?= htmlspecialchars(implode(' ', $fieldErrors['registrationEndTime'])) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Election Event -->
        <div class="mb-3">
            <label for="electionID" class="form-label">Associated Election Event</label>
            <select class="form-select <?= !empty($fieldErrors['electionID']) ? 'is-invalid' : '' ?>"
                    id="electionID"
                    name="electionID">
                <option value="">Select an election event</option>
                <?php foreach ($electionEvents as $event): ?>
                    <option value="<?= htmlspecialchars($event['electionID']) ?>"
                        <?= (isset($registrationFormEditionData['electionID']) && (string)$registrationFormEditionData['electionID'] === (string)$event['electionID']) ? 'selected' : '' ?>>
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

        <!-- Attributes -->
        <div class="mb-3">
            <label class="form-label">Registration Form Attributes</label>
            <?php
                $selectedEditionAttributes = $selectedEditionAttributes ?? ($registrationFormEditionData['attributes'] ?? []);
                $selectedEditionAttributes = is_array($selectedEditionAttributes) ? $selectedEditionAttributes : [];
                $isChecked = fn($key) => in_array($key, $selectedEditionAttributes, true) ? 'checked' : '';
            ?>
            <div id="registrationFormAttributes" class="<?= !empty($fieldErrors['attributes']) ? 'is-invalid' : '' ?>">
                <?php foreach ($registrationFormEditionAttributes as $attr): ?>
                    <div class="form-check">
                        <input class="form-check-input"
                               type="checkbox"
                               name="attributes[]"
                               id="attr_<?= htmlspecialchars($attr['key']) ?>"
                               value="<?= htmlspecialchars($attr['key']) ?>"
                               <?= $isChecked($attr['key']) ?>>
                        <label class="form-check-label" for="attr_<?= htmlspecialchars($attr['key']) ?>">
                            <?= htmlspecialchars($attr['label']) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($fieldErrors['attributes'])): ?>
                <div class="invalid-feedback d-block">
                    <?= htmlspecialchars(implode(' ', $fieldErrors['attributes'])) ?>
                </div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary">Update Registration Form</button>
    </form>
</div>

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>
