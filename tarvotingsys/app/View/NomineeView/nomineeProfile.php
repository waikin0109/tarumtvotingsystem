<?php

$_title = 'My Nominee Profile';
require_once __DIR__ . '/../NomineeView/nomineeHeader.php';

$profilePhotoURL = $_SESSION['profilePhotoURL'] ?? ($profile['profilePhotoURL'] ?? '');
$profileImageSrc = $profilePhotoURL !== '' ? $profilePhotoURL : '/image/defaultUserImage.jpg';

// Helper for seat type label
$seatTypeLabel = $profile['seatType'] ?? '';
if ($seatTypeLabel !== '') {
    $seatTypeLabel = str_replace('_', ' ', $seatTypeLabel);
}

$hasManifesto = !empty($profile['manifesto']);
$manifestoRaw = $profile['manifesto'] ?? '';
$manifestoText = ltrim($manifestoRaw);  // left-trim only
?>

<div class="container-fluid mt-4 mb-5">
    <!-- Page title + subtle subtitle -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h2 class="mb-1">My Nominee Profile</h2>
            <p class="text-muted mb-0">
                View your nominee information, election details, and manage your password.
            </p>
        </div>
    </div>

    <div class="row">
        <!-- Left: Photo + quick info -->
        <div class="col-lg-4 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <img src="<?= htmlspecialchars($profileImageSrc) ?>" alt="Profile Photo"
                            class="rounded-circle border" style="width:130px;height:130px;object-fit:cover;">
                    </div>

                    <h5 class="card-title mb-1">
                        <?= htmlspecialchars($profile['fullName'] ?? '') ?>
                    </h5>
                    <p class="text-muted mb-2 small">
                        <?= htmlspecialchars($profile['program'] ?? 'Student') ?>
                    </p>

                    <p class="small mb-1">
                        <i class="bi bi-person-badge"></i>
                        <strong>Login ID:</strong>
                        <?= htmlspecialchars($profile['loginID'] ?? '') ?>
                    </p>
                    <p class="small mb-1">
                        <i class="bi bi-card-text"></i>
                        <strong>Nominee ID:</strong>
                        <?= htmlspecialchars($profile['nomineeID'] ?? '') ?>
                    </p>
                    <p class="small mb-1">
                        <i class="bi bi-card-checklist"></i>
                        <strong>Student ID:</strong>
                        <?= htmlspecialchars($profile['studentID'] ?? '-') ?>
                    </p>
                    <p class="small mb-3">
                        <i class="bi bi-shield-lock"></i>
                        <strong>Role:</strong>
                        <?= htmlspecialchars($profile['role'] ?? '') ?>
                    </p>

                    <hr>

                    <!-- Change photo form -->
                    <form action="/nominee/profile/update-photo" method="POST" enctype="multipart/form-data"
                        class="text-start">
                        <div class="mb-2">
                            <label for="profilePhoto" class="form-label mb-1">Change profile photo</label>
                            <input type="file" name="profilePhoto" id="profilePhoto"
                                class="form-control form-control-sm" accept="image/*" required>
                            <div class="form-text">Max 2MB, JPG or PNG.</div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-upload"></i> Update Photo
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Small card: quick meta -->
            <div class="card mt-3 shadow-sm">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Account Status</h6>
                    <p class="mb-1">
                        <strong>Status:</strong>
                        <span
                            class="badge <?= ($profile['status'] ?? '') === 'ACTIVE' ? 'bg-success' : 'bg-secondary' ?>">
                            <?= htmlspecialchars($profile['status'] ?? 'UNKNOWN') ?>
                        </span>
                    </p>
                    <p class="mb-0 small text-muted">
                        Last login:<br>
                        <span class="fw-semibold">
                            <?= htmlspecialchars($profile['lastLoginAt'] ?? 'Never') ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Right: Tabs for info + security -->
        <div class="col-lg-8 mb-3">
            <div class="card shadow-sm">
                <div class="card-header border-bottom-0 pb-0">
                    <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab"
                                data-bs-target="#overview" type="button" role="tab" aria-controls="overview"
                                aria-selected="true">
                                <i class="bi bi-person-lines-fill"></i> Overview
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security"
                                type="button" role="tab" aria-controls="security" aria-selected="false">
                                <i class="bi bi-lock-fill"></i> Security
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content" id="profileTabsContent">
                        <!-- Overview tab -->
                        <div class="tab-pane fade show active" id="overview" role="tabpanel"
                            aria-labelledby="overview-tab">
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-2">Account Information</h6>
                                    <p class="mb-1 small text-uppercase text-muted">Full Name</p>
                                    <p class="fw-semibold"><?= htmlspecialchars($profile['fullName'] ?? '') ?></p>

                                    <p class="mb-1 small text-uppercase text-muted">Email</p>
                                    <p class="fw-semibold"><?= htmlspecialchars($profile['email'] ?? '-') ?></p>

                                    <p class="mb-1 small text-uppercase text-muted">Phone Number</p>
                                    <p class="fw-semibold"><?= htmlspecialchars($profile['phoneNumber'] ?? '-') ?></p>

                                    <p class="mb-1 small text-uppercase text-muted">Gender</p>
                                    <p class="fw-semibold"><?= htmlspecialchars($profile['gender'] ?? '-') ?></p>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-2">Nominee &amp; Student Information</h6>
                                    <p class="mb-1 small text-uppercase text-muted">Programme</p>
                                    <p class="fw-semibold"><?= htmlspecialchars($profile['program'] ?? '-') ?></p>

                                    <p class="mb-1 small text-uppercase text-muted">Intake Year</p>
                                    <p class="fw-semibold"><?= htmlspecialchars($profile['intakeYear'] ?? '-') ?></p>

                                    <p class="mb-1 small text-uppercase text-muted">Faculty</p>
                                    <p class="fw-semibold">
                                        <?= htmlspecialchars($profile['facultyCode'] ?? '') ?>
                                        <?= ($profile['facultyCode'] ?? '') && ($profile['facultyName'] ?? '') ? ' - ' : '' ?>
                                        <?= htmlspecialchars($profile['facultyName'] ?? '') ?>
                                    </p>
                                </div>
                            </div>

                            <hr>
                            <!-- Election + Race row -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-2">Election Information</h6>
                                    <p class="mb-1 small text-uppercase text-muted">Election</p>
                                    <p class="fw-semibold">
                                        <?= htmlspecialchars($profile['electionTitle'] ?? '-') ?>
                                    </p>

                                    <p class="mb-1 small text-uppercase text-muted">Election Status</p>
                                    <p class="fw-semibold">
                                        <?= htmlspecialchars($profile['electionStatus'] ?? '-') ?>
                                    </p>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <h6 class="text-muted mb-2">Race Information</h6>

                                    <p class="mb-1 small text-uppercase text-muted">Race Title</p>
                                    <p class="fw-semibold">
                                        <?= htmlspecialchars($profile['raceTitle'] ?? 'Not assigned yet') ?>
                                    </p>

                                    <p class="mb-1 small text-uppercase text-muted">Seat Type</p>
                                    <p class="fw-semibold mb-2">
                                        <?= htmlspecialchars($seatTypeLabel !== '' ? $seatTypeLabel : '-') ?>
                                    </p>

                                    <!-- Button directly under race info -->
                                    <a href="/nominee/select-race" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-flag"></i> Choose / Change Race &amp; Seat Type
                                    </a>
                                </div>
                            </div>

                            <!-- Manifesto block -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="text-muted mb-0">Manifesto</h6>

                                    <?php if ($hasManifesto): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i> Submitted
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-exclamation-circle me-1"></i> Not submitted
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- READ-ONLY DISPLAY -->
                                <div id="manifestoDisplay">
                                    <?php if ($hasManifesto): ?>
                                        <div class="border rounded p-3 bg-light small text-start" style="min-height:60px;">
                                            <?= nl2br(htmlspecialchars($manifestoText)) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-light border small d-flex align-items-center mb-0">
                                            <i class="bi bi-info-circle me-2"></i>
                                            <span>You have not submitted a manifesto yet in this system.</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- EDIT BUTTON -->
                                <div class="mt-2">
                                    <button type="button" id="editManifestoBtn" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil-square"></i>
                                        <?= $hasManifesto ? 'Update Manifesto' : 'Create Manifesto' ?>
                                    </button>
                                </div>

                                <!-- EDIT FORM (HIDDEN BY DEFAULT) -->
                                <form action="/nominee/profile/update-manifesto" method="POST" id="manifestoForm"
                                    class="mt-3 d-none">
                                    <div class="mb-2">
                                        <label for="manifesto" class="form-label small text-muted">
                                            Manifesto (maximum 2000 characters)
                                        </label>

                                        <textarea name="manifesto" id="manifesto"
                                            class="form-control form-control-sm text-start" rows="6" maxlength="2000"
                                            required><?php echo htmlspecialchars($manifestoText); ?></textarea>

                                        <div class="d-flex justify-content-between align-items-center mt-1">
                                            <div class="form-text small">
                                                Explain your goals, promises, and priorities as a nominee.
                                            </div>
                                            <div class="small text-muted text-end">
                                                <span id="manifestoCounter">0</span>/2000
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary me-2"
                                            id="cancelManifestoBtn">
                                            Cancel
                                        </button>
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="bi bi-save"></i> Save Manifesto
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="alert alert-light border small mb-0">
                                <i class="bi bi-info-circle"></i>
                                These details are read-only in this page. If any official information is incorrect,
                                please contact the election administrator.
                            </div>
                        </div>

                        <!-- Security tab -->
                        <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                            <h6 class="mb-3 text-muted">Change Password</h6>
                            <form action="/nominee/profile/update-password" method="POST" autocomplete="off"
                                class="mb-3">
                                <div class="mb-3">
                                    <label for="currentPassword" class="form-label">Current Password</label>
                                    <input type="password" name="currentPassword" id="currentPassword"
                                        class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label for="newPassword" class="form-label">New Password</label>
                                    <input type="password" name="newPassword" id="newPassword" class="form-control"
                                        required>
                                    <div class="form-text">
                                        Use at least 8 characters. Do not share your password with anyone.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirmPassword" id="confirmPassword"
                                        class="form-control" required>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Update Password
                                </button>
                            </form>

                            <div class="alert alert-warning small mb-0">
                                <i class="bi bi-exclamation-triangle"></i>
                                After changing your password, remember to log out from shared or public computers.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var editBtn = document.getElementById('editManifestoBtn');
        var cancelBtn = document.getElementById('cancelManifestoBtn');
        var display = document.getElementById('manifestoDisplay');
        var form = document.getElementById('manifestoForm');
        var textarea = document.getElementById('manifesto');
        var counter = document.getElementById('manifestoCounter');

        function updateCount() {
            if (!textarea || !counter) return;
            counter.textContent = textarea.value.length;
        }

        if (textarea) {
            textarea.addEventListener('input', updateCount);
            updateCount();
        }

        if (editBtn && form && display) {
            editBtn.addEventListener('click', function () {
                // enter edit mode
                form.classList.remove('d-none');
                display.classList.add('d-none');
                editBtn.classList.add('d-none');

                if (textarea) {
                    textarea.focus();
                }
            });
        }

        if (cancelBtn && form && display && editBtn) {
            cancelBtn.addEventListener('click', function () {
                // cancel edit: just go back to view mode
                form.classList.add('d-none');
                display.classList.remove('d-none');
                editBtn.classList.remove('d-none');
            });
        }
    });
</script>

<?php
require_once __DIR__ . '/../NomineeView/nomineeFooter.php';
?>