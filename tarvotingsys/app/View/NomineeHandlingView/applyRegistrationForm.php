<?php
$_title = "Apply as Nominee";
$roleUpper = strtoupper($_SESSION['role'] ?? '');
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} elseif ($roleUpper === 'STUDENT')  {
    require_once __DIR__ . '/../StudentView/studentHeader.php';
}

$registerBase = ($roleUpper === 'NOMINEE') ? '/nominee/election-registration-form/register/' : '/student/election-registration-form/register/';

$backLink = match ($roleUpper) {
    'STUDENT' => '/student/election-registration-form',
    'NOMINEE' => '/nominee/election-registration-form',
    default   => '/login'
};

function invalid(array $fe, string $code){ return !empty($fe[$code]) ? ' is-invalid' : ''; }

// expects: $form, $renderAttrs, $errors, $fieldErrors, $old, $registrationOpen, $regWindow
?>
<div class="container mt-4">
  <h2>Apply as Nominee</h2>

  <?php if (!empty($regWindow)): ?>
    <div class="alert alert-info py-2">
      Registration window:
      <strong><?= htmlspecialchars(date('Y-m-d H:i', strtotime($regWindow['registerStartDate'] ?? ''))) ?></strong>
      to
      <strong><?= htmlspecialchars(date('Y-m-d H:i', strtotime($regWindow['registerEndDate'] ?? ''))) ?></strong>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form action="<?= $registerBase . urlencode($form['registrationFormID']) ?>" method="POST" enctype="multipart/form-data" novalidate>

    <!-- Dynamic attributes (same rendering rules) -->
    <div id="dynamic-fields">
      <?php foreach ($renderAttrs as $a):
            $code = $a['code']; $type = $a['type']; $label = $a['label'];
            $v = $old['fields'][$code] ?? ($type==='checkbox' ? 0 : '');
      ?>
        <div class="mb-3">
          <label class="form-label" for="f-<?= $code ?>"><?= htmlspecialchars($label) ?></label>

          <?php if ($type === 'number'): ?>
            <input type="number" step="0.01" min="0" max="4"
                   class="form-control<?= invalid($fieldErrors, $code) ?>"
                   id="f-<?= $code ?>" name="fields[<?= $code ?>]"
                   value="<?= htmlspecialchars((string)$v) ?>">

          <?php elseif ($type === 'textarea'): ?>
            <textarea id="f-<?= $code ?>" name="fields[<?= $code ?>]" rows="4"
                      class="form-control<?= invalid($fieldErrors, $code) ?>"><?= htmlspecialchars((string)$v) ?></textarea>

          <?php elseif ($type === 'checkbox'): ?>
            <div class="form-check">
              <input class="form-check-input<?= invalid($fieldErrors, $code) ?>" type="checkbox"
                     id="f-<?= $code ?>" name="fields[<?= $code ?>]" <?= ($v==1)?'checked':'' ?>>
              <label class="form-check-label" for="f-<?= $code ?>">Yes</label>
            </div>

          <?php else: ?>
            <input type="text" class="form-control<?= invalid($fieldErrors, $code) ?>"
                   id="f-<?= $code ?>" name="fields[<?= $code ?>]"
                   value="<?= htmlspecialchars((string)$v) ?>">
          <?php endif; ?>

          <!-- Attachments: same policy as admin page -->
          <?php if ($code === 'cgpa'): ?>
            <div class="mt-2">
              <label class="form-label small">Upload CGPA proof (JPG/JPEG only)</label>
              <input type="file" accept=".jpg,.jpeg"
                     class="form-control<?= invalid($fieldErrors,'cgpa_file') ?>" name="uploads[cgpa]">
              <?php if (!empty($fieldErrors['cgpa_file'])): ?>
                <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['cgpa_file'])) ?></div>
              <?php endif; ?>
            </div>
          <?php elseif ($code === 'achievements'): ?>
            <div class="mt-2">
              <label class="form-label small">Upload achievement documents (JPG/JPEG only, multiple allowed)</label>
              <input type="file" accept=".jpg,.jpeg" multiple
                     class="form-control<?= invalid($fieldErrors,'achievements_files') ?>" name="uploads[achievements][]">
              <?php if (!empty($fieldErrors['achievements_files'])): ?>
                <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['achievements_files'])) ?></div>
              <?php endif; ?>
            </div>
          <?php elseif ($code === 'behaviorreport'): ?>
            <div class="mt-2">
              <label class="form-label small">Upload behavior report (JPG/JPEG only)</label>
              <input type="file" accept=".jpg,.jpeg"
                     class="form-control<?= invalid($fieldErrors,'behaviorreport_file') ?>" name="uploads[behaviorreport]">
              <?php if (!empty($fieldErrors['behaviorreport_file'])): ?>
                <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['behaviorreport_file'])) ?></div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($fieldErrors[$code])): ?>
            <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors[$code])) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <button type="submit" class="btn btn-primary">Submit Application</button>
    <a class="btn btn-outline-secondary" href="<?= $backLink ?>">Cancel</a>
  </form>
</div>

<?php
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeFooter.php';
} elseif ($roleUpper === 'STUDENT') {
    require_once __DIR__ . '/../StudentView/studentFooter.php';
}
?>
