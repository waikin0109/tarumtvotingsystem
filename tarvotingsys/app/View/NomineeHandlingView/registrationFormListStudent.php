<?php
$_title = 'Election Registration';
$roleUpper = strtoupper($_SESSION['role'] ?? '');

if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} elseif ($roleUpper === 'STUDENT')  {
    require_once __DIR__ . '/../StudentView/studentHeader.php';
}

$viewBase = ($roleUpper === 'NOMINEE') ? '/nominee/election-registration-form/view/' : '/student/election-registration-form/view/';
$registerBase = ($roleUpper === 'NOMINEE') ? '/nominee/election-registration-form/register/' : '/student/election-registration-form/register/';
?>

<div>
    <div class="container-fluid d-flex justify-content-between align-items-center mb-4">
        <div class="row w-100">
            <div>
                <h2>Election Registration</h2>
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
                            <th class="col-sm-5">Registration Form Title</th>
                            <th class="col-sm-4">Election Event</th>
                            <th class="col-sm-2">Action</th> 
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($registrationForms)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No Registration Form found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($registrationForms as $index => $registrationForm): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($registrationForm['registrationFormTitle'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($registrationForm['event_name'] ?? '—') ?></td>
                                    <td class="text-nowrap">
                                        <?php
                                            $formId = (int)($registrationForm['registrationFormID'] ?? 0);
                                            $mine   = $myAppsByForm[$formId] ?? null;
                                        ?>

                                        <?php if ($mine): ?>
                                            <a href="<?= $viewBase . (int)$mine['nomineeApplicationID'] ?>"
                                                class="btn btn-sm btn-secondary">View</a>
                                        <?php else: ?>
                                            <a href="<?= $registerBase . urlencode((string)$formId) ?>"
                                                class="btn btn-sm btn-warning">Register</a>
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

<?php
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeFooter.php';
} elseif ($roleUpper === 'STUDENT')  {
    require_once __DIR__ . '/../StudentView/studentFooter.php';
}
?>