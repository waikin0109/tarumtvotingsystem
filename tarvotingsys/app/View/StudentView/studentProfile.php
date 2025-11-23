<?php
/** @var array $profile */

$_title = 'My Profile';
require_once __DIR__ . '/../StudentView/studentHeader.php';

// Decide which picture to show (session > DB > default)
$profilePhotoURL = $_SESSION['profilePhotoURL'] ?? ($profile['profilePhotoURL'] ?? '');
$profileImageSrc = $profilePhotoURL !== '' ? $profilePhotoURL : '/image/defaultUserImage.jpg';
?>

<div class="container-fluid mt-4 mb-5">
    <!-- Page title + subtle subtitle -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h2 class="mb-1">My Profile</h2>
            <p class="text-muted mb-0">View your account information, programme details, and manage your password.</p>
        </div>
    </div>

    <div class="row">
        <!-- Left: Photo + quick info -->
        <div class="col-lg-4 mb-3">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <img src="<?= htmlspecialchars($profileImageSrc) ?>"
                             alt="Profile Photo"
                             class="rounded-circle border"
                             style="width:130px;height:130px;object-fit:cover;">
                    </div>

                    <h5 class="card-title mb-1">
                        <?= htmlspecialchars($profile['fullName'] ?? '') ?>
                    </h5>
                    <p class="text-muted mb-2 small">
                        <?= htmlspecialchars($profile['program'] ?? 'Student') ?>
                    </p>

                    <p class="small mb-1">
                        <i class="bi bi-person-badge"></i>
                        <strong>Login ID:</strong> <?= htmlspecialchars($profile['loginID'] ?? '') ?>
                    </p>
                    <p class="small mb-1">
                        <i class="bi bi-card-text"></i>
                        <strong>Student ID:</strong> <?= htmlspecialchars($profile['studentID'] ?? '') ?>
                    </p>
                    <p class="small mb-3">
                        <i class="bi bi-shield-lock"></i>
                        <strong>Role:</strong> <?= htmlspecialchars($profile['role'] ?? '') ?>
                    </p>

                    <hr>

                    <!-- Change photo form -->
                    <form action="/student/profile/update-photo" method="POST" enctype="multipart/form-data" class="text-start">
                        <div class="mb-2">
                            <label for="profilePhoto" class="form-label mb-1">Change profile photo</label>
                            <input type="file"
                                   name="profilePhoto"
                                   id="profilePhoto"
                                   class="form-control form-control-sm"
                                   accept="image/*"
                                   required>
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
                        <span class="badge <?= ($profile['status'] ?? '') === 'ACTIVE' ? 'bg-success' : 'bg-secondary' ?>">
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
                            <button class="nav-link active"
                                    id="overview-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#overview"
                                    type="button"
                                    role="tab"
                                    aria-controls="overview"
                                    aria-selected="true">
                                <i class="bi bi-person-lines-fill"></i> Overview
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link"
                                    id="security-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#security"
                                    type="button"
                                    role="tab"
                                    aria-controls="security"
                                    aria-selected="false">
                                <i class="bi bi-lock-fill"></i> Security
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content" id="profileTabsContent">
                        <!-- Overview tab -->
                        <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
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
                                    <h6 class="text-muted mb-2">Student Information</h6>
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

                            <div class="alert alert-light border small mb-0">
                                <i class="bi bi-info-circle"></i>
                                These details are read-only in this page. If your official information (name, programme, intake year) is incorrect, please contact the administration office.
                            </div>
                        </div>

                        <!-- Security tab -->
                        <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                            <h6 class="mb-3 text-muted">Change Password</h6>
                            <form action="/student/profile/update-password" method="POST" autocomplete="off" class="mb-3">
                                <div class="mb-3">
                                    <label for="currentPassword" class="form-label">Current Password</label>
                                    <input type="password"
                                           name="currentPassword"
                                           id="currentPassword"
                                           class="form-control"
                                           required>
                                </div>

                                <div class="mb-3">
                                    <label for="newPassword" class="form-label">New Password</label>
                                    <input type="password"
                                           name="newPassword"
                                           id="newPassword"
                                           class="form-control"
                                           required>
                                    <div class="form-text">
                                        Use at least 8 characters. Do not share your password with anyone.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                    <input type="password"
                                           name="confirmPassword"
                                           id="confirmPassword"
                                           class="form-control"
                                           required>
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
                    </div> <!-- tab-content -->
                </div> <!-- card-body -->
            </div> <!-- card -->
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../StudentView/studentFooter.php';
?>
