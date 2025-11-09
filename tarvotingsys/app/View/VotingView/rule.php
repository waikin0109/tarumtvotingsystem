<?php
$_title = 'Rules & Regulations';
require_once __DIR__ . '/../AdminView/adminHeader.php';
?>

<div>
  <div class="container-fluid d-flex justify-content-between align-items-center mb-4">
    <div class="row w-100">
      <div class="col-sm-6">
        <h2>Rules & Regulations</h2>
      </div>
      <div class="col-sm-6">
        <a href="/admin/rule/create">
          <button class="btn btn-primary mx-2 me-5 position-absolute end-0">Create (+)</button>
        </a>
      </div>
    </div>
  </div>

  <div class="container-fluid mb-5">
    <div class="bg-light">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                <th>No.</th>
                <th>Rule Title</th>
                <th>Election Event</th>
                <th>Election Status</th> 
                <th>Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php if (empty($rules)): ?>
                <tr>
                <td colspan="5" class="text-center text-muted">No rules found.</td>
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
                <tr class="clickable-row" data-href="/admin/rule/view/<?= urlencode($rule['ruleID'] ?? '') ?>">
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
                    <td class="text-nowrap" onclick="event.stopPropagation()">
                    <a
                        href="<?= $isCompleted ? 'javascript:void(0)' : '/admin/rule/edit/'.urlencode($rule['ruleID'] ?? '') ?>"
                        class="btn btn-sm btn-warning me-2 <?= $isCompleted ? 'disabled' : '' ?>"
                        <?= $isCompleted ? 'tabindex="-1" aria-disabled="true" data-bs-toggle="tooltip" title="Disabled: event completed"' : '' ?>>Edit</a>
                    <form method="POST"
                            action="/admin/rule/delete/<?= urlencode($rule['ruleID'] ?? '') ?>"
                            class="d-inline"
                            onsubmit="return <?= $isCompleted ? 'false' : 'confirm(\'Are you sure you want to delete this rule?\')' ?>;">
                        <button type="submit"
                                class="btn btn-sm btn-danger <?= $isCompleted ? 'disabled' : '' ?>"
                                <?= $isCompleted ? 'disabled aria-disabled="true" data-bs-toggle="tooltip" title="Disabled: event completed"' : '' ?>>Delete</button>
                    </form>
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

<!-- Clickable Row + Tooltips -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Row click
  document.querySelectorAll('.clickable-row').forEach(row => {
    row.addEventListener('click', e => {
      if (e.target.closest('a, button, input, select, textarea, label, form')) return;
      window.location.href = row.dataset.href;
    });
  });

  // Prevent row navigation from action controls
  document.querySelectorAll('.clickable-row .btn, .clickable-row form')
    .forEach(el => el.addEventListener('click', e => e.stopPropagation()));

  // Enable Bootstrap tooltips for disabled actions
  if (window.bootstrap && bootstrap.Tooltip) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
      new bootstrap.Tooltip(el);
    });
  }
});
</script>

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>
