<?php
$_title = "View Schedule Location Application Details";
$roleUpper = strtoupper($_SESSION['role'] ?? '');

if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} elseif ($roleUpper === 'STUDENT') {
    require_once __DIR__ . '/../StudentView/studentHeader.php';
} elseif ($roleUpper === 'ADMIN') {
    require_once __DIR__ . '/../AdminView/adminHeader.php';
}

$backLink = match ($roleUpper) {
    'ADMIN'   => '/admin/schedule-location',
    'STUDENT' => '/student/schedule-location',
    'NOMINEE' => '/nominee/schedule-location',
    default   => '/login'
};
?>

<div class="container mt-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="mb-0">Schedule Location Application</h2>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <dl class="row mb-0">
        <dt class="col-sm-4">Event Name</dt>
        <dd class="col-sm-8"><?= htmlspecialchars($schedule['eventName']) ?></dd>

        <dt class="col-sm-4">Related Election</dt>
        <dd class="col-sm-8"><?= htmlspecialchars($schedule['electionTitle']) ?></dd>

        <dt class="col-sm-4">Event Type</dt>
        <dd class="col-sm-8"><?= htmlspecialchars($schedule['eventType']) ?></dd>

        <dt class="col-sm-4">Desired Start</dt>
        <dd class="col-sm-8"><?= htmlspecialchars($schedule['desiredStart'] ?: '—') ?></dd>

        <dt class="col-sm-4">Desired End</dt>
        <dd class="col-sm-8"><?= htmlspecialchars($schedule['desiredEnd'] ?: '—') ?></dd>

        <dt class="col-sm-4">Submitted At</dt>
        <dd class="col-sm-8"><?= htmlspecialchars($schedule['submittedAt'] ?: '—') ?></dd>

        <dt class="col-sm-4">Event Application Status</dt>
        <dd class="col-sm-8">
          <span class="badge <?= htmlspecialchars($schedule['badgeClass']) ?>">
            <?= htmlspecialchars($schedule['status']) ?>
          </span>
        </dd>

        <dt class="col-sm-4">Admin Name</dt>
        <dd class="col-sm-8"><?= htmlspecialchars($schedule['adminName']) ?></dd>

        <dt class="col-sm-4">Nominee Name</dt>
        <dd class="col-sm-8"><?= htmlspecialchars($schedule['nomineeName']) ?></dd>
      </dl>
    </div>
  </div>

  <div class="mt-3 d-flex gap-2">
    <a href="<?= $backLink ?>" class="btn btn-outline-secondary">Back</a>
    <?php if ($roleUpper == 'NOMINEE'): ?>
      <a href="/admin/schedule-location/edit/<?= (int)$schedule['id'] ?>" class="btn btn-primary">Edit</a>
    <?php endif; ?>
  </div>
</div>

<?php
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeFooter.php';
} elseif ($roleUpper === 'STUDENT') {
    require_once __DIR__ . '/../StudentView/studentFooter.php';
} else {
    require_once __DIR__ . '/../AdminView/adminFooter.php';
}
?>
