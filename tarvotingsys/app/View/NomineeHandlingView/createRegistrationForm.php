<?php
$_title = "Create Election Registration Form";
require_once __DIR__ . '/../AdminView/adminHeader.php';
?>

<div class="container mt-4">
    <h2>Create Election Registration Form</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="/election-registration-form/create" method="POST">
        <div class="mb-3">
            <label for="registrationFormTitle" class="form-label">Registration Form Title</label>
            <input type="text" 
                   class="form-control <?= !empty($fieldErrors['registrationFormTitle']) ? 'is-invalid' : '' ?>" 
                   id="registrationFormTitle" 
                   name="registrationFormTitle"
                   value="<?= htmlspecialchars($registrationFormData['registrationFormTitle'] ?? '') ?>">
            <?php if (!empty($fieldErrors['registrationFormTitle'])): ?>
                <div class="invalid-feedback">
                    <?= htmlspecialchars(implode(' ', $fieldErrors['registrationFormTitle'])) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="registrationStartDate" class="form-label">Registration Start Date</label>
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

            <label for="registrationStartTime" class="form-label mt-2">Registration Start Time</label>
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

        <div class="mb-3">
            <label for="registrationEndDate" class="form-label">Registration End Date</label>
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

            <label for="registrationEndTime" class="form-label mt-2">Registration End Time</label>
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

        <div class="mb-3">
            <label for="associatedElectionEvent" class="form-label">Associated Election Event</label>
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

        <!-- Registration Form Attributes -->
        <div class="mb-3">
            <label class="form-label">Registration Form Attributes</label>
            <?php $old = $registrationFormData['attributes'] ?? []; $is = fn($k)=> in_array($k,$old,true)?'checked':''; ?>
            <div id="registrationFormAttributes" class="<?= !empty($fieldErrors['attributes']) ? 'is-invalid' : '' ?>">
                <div class="form-check">
                <input class="form-check-input" type="checkbox" name="attributes[]" value="cgpa" id="attr_cgpa" <?= $is('cgpa')?>>
                <label class="form-check-label" for="attr_cgpa">CGPA</label>
                </div>
                <div class="form-check">
                <input class="form-check-input" type="checkbox" name="attributes[]" value="reason" id="attr_reason" <?= $is('reason')?>>
                <label class="form-check-label" for="attr_reason">Reason for Participation</label>
                </div>
                <div class="form-check">
                <input class="form-check-input" type="checkbox" name="attributes[]" value="achievements" id="attr_achievements" <?= $is('achievements')?>>
                <label class="form-check-label" for="attr_achievements">Achievements / Awards</label>
                </div>
                <div class="form-check">
                <input class="form-check-input" type="checkbox" name="attributes[]" value="behaviorReport" id="attr_behavior" <?= $is('behaviorReport')?>>
                <label class="form-check-label" for="attr_behavior">Behavior Report</label>
                </div>
            </div>
            <?php if (!empty($fieldErrors['attributes'])): ?>
                <div class="invalid-feedback d-block">
                <?= htmlspecialchars(implode(' ', $fieldErrors['attributes'])) ?>
                </div>
            <?php endif; ?>
        </div>



        <button type="submit" class="btn btn-primary" id="createRegistrationFormBtn">Create Registration Form</button>
    </form>
</div>


<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>