<?php
$_title = "View Registration Form";
require_once __DIR__ . '/../AdminView/adminHeader.php';
?>

<div class="container mt-4">
    <h2>Registration Form Details</h2>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($registrationFormData['registrationFormTitle'] ?? '') ?></h5>
            <p class="card-text"><strong>Registration Form ID:</strong> <?= htmlspecialchars($registrationFormData['registrationFormID'] ?? '') ?></p>
            <p class="card-text"><strong>Associated Election Event:</strong> <?= htmlspecialchars($registrationFormData['event_name'] ?? 'N/A') ?></p>

            <?php
            $startDateTime = !empty($registrationFormData['registerStartDate'])? date('Y-m-d H:i', strtotime($registrationFormData['registerStartDate'])): '';
            $endDateTime = !empty($registrationFormData['registerEndDate'])? date('Y-m-d H:i', strtotime($registrationFormData['registerEndDate'])): '';
            ?>

            <p class="card-text"><strong>Registration Form Attributes:</strong>
                <?php
                if (!empty($registrationFormAttributes)) {
                    echo htmlspecialchars(implode(', ', $registrationFormAttributes));
                } else {
                    echo 'None';
                }
                ?>
            </p>

            <p class="card-text">
                <strong>Registration Start Date:</strong> <?= htmlspecialchars($startDateTime) ?>
            </p>
            <p class="card-text">
                <strong>Registration End Date:</strong> <?= htmlspecialchars($endDateTime) ?>
            </p>

            <p class="card-text"><strong>Date Created:</strong> <?= htmlspecialchars($registrationFormData['dateCreated'] ?? '') ?></p>

            <?php
            $adminId   = $registrationFormData['adminID']   ?? '';
            $adminName = $registrationFormData['admin_name'] ?? '';
            $createdBy = trim($adminId) !== '' 
                ? ($adminName !== '' ? "{$adminId} - {$adminName}" : (string)$adminId)
                : ($adminName !== '' ? $adminName : 'Unknown');
            ?>
            <p class="card-text">
            <strong>Created By: </strong> <?= htmlspecialchars($createdBy) ?>
            </p>
        </div>

        <div class="card-footer">
            <a href="/admin/election-registration-form/edit/<?= urlencode($registrationFormData['registrationFormID'] ?? '') ?>" class="btn btn-primary">Edit Registration Form</a>
            <a href="/admin/election-registration-form" class="btn btn-secondary">Back to Registration Forms List</a>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>