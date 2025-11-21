<?php
$_title = "Final Nominee Application List";
$roleUpper = strtoupper($_SESSION['role'] ?? '');

if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} elseif ($roleUpper === 'STUDENT') {
    require_once __DIR__ . '/../StudentView/studentHeader.php';
} elseif ($roleUpper === 'ADMIN') {
    require_once __DIR__ . '/../AdminView/adminHeader.php';
}

$backLink = match ($roleUpper) {
    'ADMIN'   => '/admin/nominee-application',
    'STUDENT' => '/student/nominee-final-list',
    'NOMINEE' => '/nominee/nominee-final-list',
    default   => '/login'
};

/** Ensure the variable is an array */
$acceptedCandidates = (isset($acceptedCandidates) && is_array($acceptedCandidates)) ? $acceptedCandidates : [];
?>

<div class="container-fluid mt-4 mb-5">

    <!-- Page header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-0">Final Nominee Application List</h2>
            <p class="text-muted small mb-0">
                Official list of nominees whose applications have been <strong>published</strong> for this election event.
            </p>
        </div>
        <div>
            <a href="<?= $backLink ?>" class="btn btn-outline-secondary">
                Back to List
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <!-- Context banner -->
            <div class="alert alert-info d-flex align-items-center small mb-4">
                <span class="badge bg-primary me-2">OFFICIAL</span>
                <span>
                    This page shows the final, confirmed nominees.
                </span>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:5%;">No.</th>
                            <th style="width:30%;">Student Name</th>
                            <th style="width:10%;">Student ID</th>
                            <th style="width:20%;">Login ID</th>
                            <th style="width:20%;">Program</th>
                            <th style="width:15%;">Intake Year</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($acceptedCandidates)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No accepted applications to display.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php
                        $currentAccountId = (int)($_SESSION['accountID'] ?? 0);
                        foreach ($acceptedCandidates as $i => $row):
                            $isCurrentUser = isset($row['accountID']) && (int)$row['accountID'] === $currentAccountId;
                        ?>
                            <tr<?= $isCurrentUser ? ' class="table-success"' : '' ?>>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <?= htmlspecialchars($row['fullName'] ?? '') ?>
                                    <?php if ($isCurrentUser): ?>
                                        <span class="badge bg-success ms-2">You</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int)($row['studentID'] ?? 0) ?></td>
                                <td><?= htmlspecialchars($row['loginID'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['program'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['intakeYear'] ?? '') ?></td>
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
} elseif ($roleUpper === 'STUDENT') {
    require_once __DIR__ . '/../StudentView/studentFooter.php';
} else {
    require_once __DIR__ . '/../AdminView/adminFooter.php';
}
?>
