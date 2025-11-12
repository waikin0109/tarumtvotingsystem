<?php
$_title = "Registration Applications Accepted List";
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

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Final Nominee Application Lists</h2>
    <a href="<?= $backLink ?>" class="btn btn-outline-secondary">Back to List</a>
  </div>

  <div class="container-fluid mb-5">
    <div class="bg-light">
      <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
          <thead class="table-light">
            <tr>
              <th>No.</th>
              <th>Student Name</th>
              <th>Student ID</th>
              <th>loginID</th>
              <th>Program</th>
              <th>Intake Year</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($acceptedCandidates)): ?>
              <tr>
                <td colspan="6" class="text-center text-muted">No accepted applications to display.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($acceptedCandidates as $i => $row): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= htmlspecialchars($row['fullName'] ?? '') ?></td>
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
