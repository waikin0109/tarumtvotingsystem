<?php
$_title = "Edit Schedule Location Application";
require_once __DIR__ . '/../AdminView/adminHeader.php';

/**
 * expects:
 * $scheduleLocationData = ['eventApplicationID','electionTitle','nomineeLabel']
 * $errors (list), $fieldErrors (array), $old (array: eventName,eventType,desiredDateTime)
 */
$errors = $errors ?? [];
$fieldErrors = $fieldErrors ?? [];
$old = $old ?? ['eventName'=>'','eventType'=>'','desiredDateTime'=>''];

function invalid(array $fe, string $code){ return !empty($fe[$code]) ? ' is-invalid' : ''; }
?>

<div class="container mt-4">
  <h2>Edit Schedule Location Application</h2>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0">
      <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul></div>
  <?php endif; ?>

  <form action="/schedule-location/edit/<?= (int)($scheduleLocationData['eventApplicationID'] ?? 0) ?>" method="POST">

    <!-- Election Event (display only) -->
    <div class="mb-3">
      <label class="form-label">Election Event</label>
      <input type="text" class="form-control" value="<?= htmlspecialchars($scheduleLocationData['electionTitle'] ?? '') ?>" disabled>
    </div>

    <!-- Nominee (display only) -->
    <div class="mb-3">
      <label class="form-label">Nominee</label>
      <input type="text" class="form-control" value="<?= htmlspecialchars($scheduleLocationData['nomineeLabel'] ?? '') ?>" disabled>
    </div>

    <!-- Event Name -->
    <div class="mb-3">
      <label class="form-label" for="eventName">Event Name <span class="text-danger">*</span></label>
      <input type="text" name="eventName" id="eventName" maxlength="255"
             class="form-control<?= invalid($fieldErrors,'eventName') ?>"
             value="<?= htmlspecialchars($old['eventName'] ?? '') ?>" required>
      <?php if (!empty($fieldErrors['eventName'])): ?>
        <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['eventName'])) ?></div>
      <?php endif; ?>
    </div>

    <!-- Event Type -->
    <div class="mb-3">
      <label class="form-label" for="eventType">Event Type <span class="text-danger">*</span></label>
      <select name="eventType" id="eventType" class="form-select<?= invalid($fieldErrors,'eventType') ?>" required>
        <option value="">-- Select --</option>
        <option value="CAMPAIGN" <?= (($old['eventType'] ?? '')==='CAMPAIGN')?'selected':''; ?>>CAMPAIGN</option>
        <option value="DEBATE"   <?= (($old['eventType'] ?? '')==='DEBATE')  ?'selected':''; ?>>DEBATE</option>
      </select>
      <?php if (!empty($fieldErrors['eventType'])): ?>
        <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['eventType'])) ?></div>
      <?php endif; ?>
    </div>

    <!-- Desired Date & Time -->
    <div class="mb-3">
      <label class="form-label" for="desiredDateTime">Desired Date & Time <span class="text-danger">*</span></label>
      <input type="datetime-local" name="desiredDateTime" id="desiredDateTime"
             class="form-control<?= invalid($fieldErrors,'desiredDateTime') ?>"
             value="<?= htmlspecialchars($old['desiredDateTime'] ?? '') ?>" required>
      <?php if (!empty($fieldErrors['desiredDateTime'])): ?>
        <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['desiredDateTime'])) ?></div>
      <?php endif; ?>
      <div class="form-text">Must be after registration closes, in the future, and not after the election end time.</div>
    </div>

    <div class="d-flex justify-content-center gap-3">
      <a href="/schedule-location" class="btn btn-outline-secondary px-4">Cancel</a>
      <button type="submit" class="btn btn-primary px-4">Save</button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>
