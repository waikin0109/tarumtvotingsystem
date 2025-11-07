<?php
$_title = 'Election Registration Form';
require_once __DIR__ . '/../AdminView/adminHeader.php';
?>

<div>
    <div class="container-fluid d-flex justify-content-between align-items-center mb-4">
        <div class="row w-100">
            <div class="col-sm-6">
                <h2>Election Registration Form</h2>
            </div>
            <div class="col-sm-6">
                <a href="/election-registration-form/create"><button class="btn btn-primary mx-2 me-5 position-absolute end-0">Create (+)</button></a>
            </div>
        </div>
    </div>

    <div class="container-fluid mb-5">
        <div class="bg-light">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="col-sm-1">No.</th>
                            <th class="col-sm-5">Registration Form</th>
                            <th class="col-sm-4">Election Event</th>
                            <th class="col-sm-2">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($registrationForms)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No registration forms found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($registrationForms as $index => $form): ?>
                                <tr class="clickable-row" data-href="/election-registration-form/view/<?= urlencode($form['registrationFormID'] ?? '') ?>">
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($form['registrationFormTitle'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($form['event_name'] ?? '—') ?></td>
                                    <td onclick="event.stopPropagation()">
                                        <a href="/election-registration-form/edit/<?= urlencode($form['registrationFormID'] ?? '') ?>" class="btn btn-sm btn-warning">Edit</a>
                                        <form method="POST" action="/election-registration-form/delete/<?= urlencode($form['registrationFormID'] ?? '') ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this registration form?');">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
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

<!-- Clickable Row -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.clickable-row').forEach(row => {
    row.addEventListener('click', e => {
      // Ignore clicks on interactive elements so forms/links work
      if (e.target.closest('a, button, input, select, textarea, label, form')) return;
      window.location.href = row.dataset.href;
    });
  });

  // Extra safety: stop row-click bubbling from action controls
  document.querySelectorAll('.clickable-row .btn, .clickable-row form')
    .forEach(el => el.addEventListener('click', e => e.stopPropagation()));
});
</script>

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>