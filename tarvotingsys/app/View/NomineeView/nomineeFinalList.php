<?php
$_title = 'Nominee Lists';
$roleUpper = strtoupper($_SESSION['role'] ?? '');

// Check user role and load respective headers
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} elseif ($roleUpper === 'STUDENT')  {
    require_once __DIR__ . '/../StudentView/studentHeader.php';
}

$viewBase = ($roleUpper === 'NOMINEE') ? '/nominee/nominee-final-list/view/' : '/student/nominee-final-list/view/';

// Filter nominee applications to include only those with 'PUBLISHED' status
$publishedNomineeApplications = array_filter($nomineeApplications, function($nomineeApplication) {
    return isset($nomineeApplication['applicationStatus']) && $nomineeApplication['applicationStatus'] === 'PUBLISHED';
});

// Remove duplicate election events by using the event name or election ID as a key
$uniqueElectionEvents = [];
foreach ($publishedNomineeApplications as $nomineeApplication) {
    $eventName = $nomineeApplication['event_name'] ?? '';
    if (!isset($uniqueElectionEvents[$eventName])) {
        $uniqueElectionEvents[$eventName] = $nomineeApplication;
    }
}

$uniqueNomineeApplications = array_values($uniqueElectionEvents); // Re-index the array
?>

<div>
    <div class="container-fluid d-flex justify-content-between align-items-center mb-4">
        <div class="row w-100">
            <div>
                <h2>Nominees' Lists</h2>
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
                            <th class="col-sm-11">Related Election Event Final Lists</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($uniqueNomineeApplications)): ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted">No Published Nominee Application Forms.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($uniqueNomineeApplications as $index => $nomineeApplication): ?>
                                <tr class="clickable-row" data-href="<?= $viewBase . urlencode($nomineeApplication['electionID'] ?? '') ?>">
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($nomineeApplication['event_name'] ?? '—') ?></td>
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
