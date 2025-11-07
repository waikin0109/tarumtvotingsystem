<?php
$_title = 'Election Event Creation';
require_once __DIR__ . '/../AdminView/adminHeader.php';
?>

<div class="container mt-4">
    <h2>Create Election Event</h2>

    <form action="/admin/election-event/create" method="POST">
        <div class="mb-3">
            <label for="electionEventName" class="form-label">Election Event Name</label>
            <input type="text" 
                   class="form-control <?= !empty($fieldErrors['electionEventName']) ? 'is-invalid' : '' ?>" 
                   id="electionEventName" 
                   name="electionEventName"
                   value="<?= htmlspecialchars($electionEventCreationData['electionEventName'] ?? '') ?>">
            <?php if (!empty($fieldErrors['electionEventName'])): ?>
                <div class="invalid-feedback">
                    <?= htmlspecialchars(implode(' ', $fieldErrors['electionEventName'])) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="electionEventDescription" class="form-label">Event Description</label>
            <textarea class="form-control <?= !empty($fieldErrors['electionEventDescription']) ? 'is-invalid' : '' ?>" 
                      id="electionEventDescription" 
                      name="electionEventDescription" 
                      rows="3"><?= htmlspecialchars($electionEventCreationData['electionEventDescription'] ?? '') ?></textarea>
            <?php if (!empty($fieldErrors['electionEventDescription'])): ?>
                <div class="invalid-feedback">
                    <?= htmlspecialchars(implode(' ', $fieldErrors['electionEventDescription'])) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="electionEventStartDate" class="form-label">Event Start Date</label>
            <input type="date" 
                   class="form-control <?= !empty($fieldErrors['electionEventStartDate']) ? 'is-invalid' : '' ?>" 
                   id="electionEventStartDate" 
                   name="electionEventStartDate"
                   value="<?= htmlspecialchars($electionEventCreationData['electionEventStartDate'] ?? '') ?>">
            <?php if (!empty($fieldErrors['electionEventStartDate'])): ?>
                <div class="invalid-feedback">
                    <?= htmlspecialchars(implode(' ', $fieldErrors['electionEventStartDate'])) ?>
                </div>
            <?php endif; ?>

            <label for="electionEventStartTime" class="form-label mt-2">Event Start Time</label>
            <input type="time" 
                   class="form-control <?= !empty($fieldErrors['electionEventStartTime']) ? 'is-invalid' : '' ?>" 
                   id="electionEventStartTime" 
                   name="electionEventStartTime"
                   value="<?= htmlspecialchars($electionEventCreationData['electionEventStartTime'] ?? '') ?>">
            <?php if (!empty($fieldErrors['electionEventStartTime'])): ?>
                <div class="invalid-feedback">
                    <?= htmlspecialchars(implode(' ', $fieldErrors['electionEventStartTime'])) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="electionEventEndDate" class="form-label">Event End Date</label>
            <input type="date" 
                   class="form-control <?= !empty($fieldErrors['electionEventEndDate']) ? 'is-invalid' : '' ?>" 
                   id="electionEventEndDate" 
                   name="electionEventEndDate"
                   value="<?= htmlspecialchars($electionEventCreationData['electionEventEndDate'] ?? '') ?>">
            <?php if (!empty($fieldErrors['electionEventEndDate'])): ?>
                <div class="invalid-feedback">
                    <?= htmlspecialchars(implode(' ', $fieldErrors['electionEventEndDate'])) ?>
                </div>
            <?php endif; ?>

            <label for="electionEventEndTime" class="form-label mt-2">Event End Time</label>
            <input type="time" 
                   class="form-control <?= !empty($fieldErrors['electionEventEndTime']) ? 'is-invalid' : '' ?>" 
                   id="electionEventEndTime" 
                   name="electionEventEndTime"
                   value="<?= htmlspecialchars($electionEventCreationData['electionEventEndTime'] ?? '') ?>">
            <?php if (!empty($fieldErrors['electionEventEndTime'])): ?>
                <div class="invalid-feedback">
                    <?= htmlspecialchars(implode(' ', $fieldErrors['electionEventEndTime'])) ?>
                </div>
            <?php endif; ?>
        </div>
        
        <button type="submit" class="btn btn-primary">Create Event</button>
    </form>
</div>

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>