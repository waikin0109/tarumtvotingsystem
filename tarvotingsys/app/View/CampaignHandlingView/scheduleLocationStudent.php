<?php
$_title = 'Schedule Location Event';
$roleUpper = strtoupper($_SESSION['role'] ?? '');

if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} elseif ($roleUpper === 'STUDENT')  {
    require_once __DIR__ . '/../StudentView/studentHeader.php';
}

$viewBase = ($roleUpper === 'NOMINEE') ? '/nominee/schedule-location/schedule/view/' : '/student/schedule-location/schedule/view/';

?>

<div>
    <div class="container-fluid d-flex justify-content-between align-items-center mb-4">
        <div class="row w-100">
        <div class="col-sm-8">
            <h2>Nominees' Campaign Timetable</h2>
        </div>
        </div>
        <?php if ($roleUpper == 'NOMINEE'): ?>
            <div class="col-sm-4 d-flex justify-content-end">
            <a href="/nominee/schedule-location/create" class="mx-2">
                <button class="btn btn-primary">Apply (+)</button>
            </a>
            </div>
        <?php endif; ?>
    </div>
    <div class="bg-light">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="col-sm-1">No. </th>
                            <th class="col-sm-11">Nominee Campaign Timetable Election Event</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if(empty($scheduleLocations)): ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted">No schedule location found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($scheduleLocations as $index => $scheduleLocation): ?>
                                <tr class="clickable-row" data-href="<?= htmlspecialchars($viewBase . (int)$scheduleLocation['electionID']) ?>">
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($scheduleLocation['event_name'] ?? '') ?></td>
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