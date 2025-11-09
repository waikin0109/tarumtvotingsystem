<?php
$_title = 'Rules & Regulations';
$roleUpper = strtoupper($_SESSION['role'] ?? '');

if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} elseif ($roleUpper === 'STUDENT')  {
    require_once __DIR__ . '/../StudentView/studentHeader.php';
}

$viewBase = ($roleUpper === 'NOMINEE') ? '/nominee/rule/view/' : '/student/rule/view/';
?>


<div>
    <div class="container-fluid d-flex justify-content-between align-items-center mb-4">
        <div class="row w-100">
            <div>
                <h2>Rules & Regulations</h2>
            </div>
        </div>
    </div>

    <div class="container-fluid mb-5">
        <div class="bg-light">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="col-sm-1">No. </th>
                            <th class="col-sm-5">Rule Title</th>
                            <th class="col-sm-4">Election Event</th>
                            <th class="col-sm-2">Election Status</th> 
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($rules)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No rules found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rules as $index => $rule): ?>
                                <?php
                                    $statusRaw   = $rule['event_status'] ?? '';
                                    $statusLower = strtolower($statusRaw);
                                    $isCompleted = ($statusLower === 'completed');

                                    $badgeClass = match ($statusLower) {
                                    'pending'   => 'bg-warning',
                                    'ongoing'   => 'bg-success',
                                    'completed' => 'bg-secondary',
                                    default     => 'bg-light text-dark'
                                    };
                                ?>
                                <tr class="clickable-row" data-href="<?= $viewBase . urlencode($rule['ruleID'] ?? '') ?>">
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($rule['ruleTitle'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($rule['event_name'] ?? '—') ?></td>
                                    <td>
                                        <?php if ($statusRaw !== ''): ?>
                                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($statusRaw) ?></span>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div> 
    </div>                   
</div>

<!-- Clickable Row -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Row click
  document.querySelectorAll('.clickable-row').forEach(row => {
    row.addEventListener('click', e => {
      if (e.target.closest('a, button, input, select, textarea, label, form')) return;
      window.location.href = row.dataset.href;
    });
  });
});
</script>

<?php
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeFooter.php';
} elseif ($roleUpper === 'STUDENT')  {
    require_once __DIR__ . '/../StudentView/studentFooter.php';
}
?>