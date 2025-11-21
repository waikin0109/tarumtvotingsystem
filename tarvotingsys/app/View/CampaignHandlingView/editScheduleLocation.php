<?php
$_title = "Edit Schedule Location Application";
require_once __DIR__ . '/../AdminView/adminHeader.php';

/**
 * expects:
 * $scheduleLocationData = ['eventApplicationID','electionTitle','nomineeLabel']
 * $errors (list)
 * $fieldErrors (array keys: eventName,eventType,desiredStartDateTime,desiredEndDateTime)
 * $old (array: eventName,eventType,desiredStartDateTime,desiredEndDateTime)
 */
$errors      = $errors      ?? [];
$fieldErrors = $fieldErrors ?? [];
$old = $old ?? [
    'eventName'            => '',
    'eventType'            => '',
    'desiredStartDateTime' => '',
    'desiredEndDateTime'   => '',
];

if (!function_exists('invalid')) {
    function invalid(array $fe, string $code){ return !empty($fe[$code]) ? ' is-invalid' : ''; }
}
?>

<div class="container-fluid mt-4 mb-5">
    <!-- Page header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Edit Schedule Location Application</h2>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="/admin/schedule-location/edit/<?= (int)($scheduleLocationData['eventApplicationID'] ?? 0) ?>"
                  method="POST"
                  id="editScheduleForm"
                  novalidate>

                <!-- Section: Election & Nominee (display only) -->
                <h5 class="mb-3">Election Event &amp; Nominee</h5>

                <!-- Election Event -->
                <div class="mb-3">
                    <label class="form-label">Election Event</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= htmlspecialchars($scheduleLocationData['electionTitle'] ?? '') ?>"
                        disabled
                    >
                </div>

                <!-- Nominee -->
                <div class="mb-4">
                    <label class="form-label">Nominee</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= htmlspecialchars($scheduleLocationData['nomineeLabel'] ?? '') ?>"
                        disabled
                    >
                </div>

                <!-- Section: Event Details -->
                <h5 class="mb-3">Event Details</h5>

                <!-- Event Name -->
                <div class="mb-3">
                    <label class="form-label" for="eventName">
                        Event Name <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        name="eventName"
                        id="eventName"
                        maxlength="255"
                        class="form-control<?= invalid($fieldErrors,'eventName') ?>"
                        value="<?= htmlspecialchars($old['eventName'] ?? '') ?>"
                        required
                    >
                    <?php if (!empty($fieldErrors['eventName'])): ?>
                        <div class="invalid-feedback d-block">
                            <?= htmlspecialchars(implode(' ', $fieldErrors['eventName'])) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Event Type -->
                <div class="mb-3">
                    <label class="form-label" for="eventType">
                        Event Type <span class="text-danger">*</span>
                    </label>
                    <select
                        name="eventType"
                        id="eventType"
                        class="form-select<?= invalid($fieldErrors,'eventType') ?>"
                        required
                    >
                        <option value="">-- Select --</option>
                        <option value="CAMPAIGN" <?= (($old['eventType'] ?? '')==='CAMPAIGN') ? 'selected' : ''; ?>>
                            CAMPAIGN
                        </option>
                        <option value="DEBATE"   <?= (($old['eventType'] ?? '')==='DEBATE')   ? 'selected' : ''; ?>>
                            DEBATE
                        </option>
                    </select>
                    <?php if (!empty($fieldErrors['eventType'])): ?>
                        <div class="invalid-feedback d-block">
                            <?= htmlspecialchars(implode(' ', $fieldErrors['eventType'])) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Desired Start Date & Time -->
                <div class="mb-3">
                    <label class="form-label" for="desiredStartDateTime">
                        Desired Start Date &amp; Time <span class="text-danger">*</span>
                    </label>
                    <input
                        type="datetime-local"
                        name="desiredStartDateTime"
                        id="desiredStartDateTime"
                        class="form-control<?= invalid($fieldErrors,'desiredStartDateTime') ?>"
                        value="<?= htmlspecialchars($old['desiredStartDateTime'] ?? '') ?>"
                        required
                    >
                    <?php if (!empty($fieldErrors['desiredStartDateTime'])): ?>
                        <div class="invalid-feedback d-block">
                            <?= htmlspecialchars(implode(' ', $fieldErrors['desiredStartDateTime'])) ?>
                        </div>
                    <?php endif; ?>
                    <div class="form-text">
                        Must be after registration closes and in the future.
                    </div>
                </div>

                <!-- Desired End Date & Time -->
                <div class="mb-3">
                    <label class="form-label" for="desiredEndDateTime">
                        Desired End Date &amp; Time <span class="text-danger">*</span>
                    </label>
                    <input
                        type="datetime-local"
                        name="desiredEndDateTime"
                        id="desiredEndDateTime"
                        class="form-control<?= invalid($fieldErrors,'desiredEndDateTime') ?>"
                        value="<?= htmlspecialchars($old['desiredEndDateTime'] ?? '') ?>"
                        required
                    >
                    <?php if (!empty($fieldErrors['desiredEndDateTime'])): ?>
                        <div class="invalid-feedback d-block">
                            <?= htmlspecialchars(implode(' ', $fieldErrors['desiredEndDateTime'])) ?>
                        </div>
                    <?php endif; ?>
                    <div class="form-text">
                        Must be at least 1 hour after start and not after the election end time.
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex justify-content-end gap-2 pt-2">
                    <a href="/admin/schedule-location" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Save Changes
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
(function(){
    const $ = (id) => document.getElementById(id);

    const form    = $('editScheduleForm');
    const startEl = $('desiredStartDateTime');
    const endEl   = $('desiredEndDateTime');

    const setInlineError = (el, msg) => {
        if (!el) return;
        el.classList.add('is-invalid');
        let fb = el.parentElement.querySelector('.invalid-feedback');
        if (!fb) {
            fb = document.createElement('div');
            fb.className = 'invalid-feedback d-block';
            el.parentElement.appendChild(fb);
        }
        fb.textContent = msg;
    };

    const clearInlineError = (el) => {
        if (!el) return;
        el.classList.remove('is-invalid');
        // Do not remove server-side messages; only clear text of auto-added ones if needed
    };

    if (form) {
        form.addEventListener('submit', (e) => {
            let ok = true;

            if (startEl && endEl) {
                clearInlineError(startEl);
                clearInlineError(endEl);

                const sVal = startEl.value;
                const eVal = endEl.value;

                if (!sVal) {
                    setInlineError(startEl, 'Start is required.');
                    ok = false;
                }
                if (!eVal) {
                    setInlineError(endEl, 'End is required.');
                    ok = false;
                }

                if (sVal && eVal) {
                    const s = new Date(sVal);
                    const d = new Date(eVal);
                    const oneHourMs = 60 * 60 * 1000;

                    if (d - s < oneHourMs) {
                        setInlineError(endEl, 'End time must be at least 1 hour after start.');
                        ok = false;
                    }
                }
            }

            if (!ok) e.preventDefault();
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>
