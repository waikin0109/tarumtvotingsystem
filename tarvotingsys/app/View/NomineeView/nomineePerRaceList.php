<?php

$_title = 'View Nominees';

$roleUpper = strtoupper($_SESSION['role'] ?? '');

if ($roleUpper === 'ADMIN') {
    require_once __DIR__ . '/../AdminView/adminHeader.php';
} elseif ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} else {
    require_once __DIR__ . '/../StudentView/studentHeader.php';
}

$selectedElectionID = $selectedElectionID ?? (int) ($_GET['electionID'] ?? 0);
$selectedRaceID = $selectedRaceID ?? (int) ($_GET['raceID'] ?? 0);

$elections = $elections ?? [];
$races = $races ?? [];
$nominees = $nominees ?? [];
?>

<style>
    .nominee-layout {
        width: 100%;
        max-width: 100%;
    }

    .nominee-wrapper-card {
        border-radius: 18px;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        padding: 24px 24px 28px;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.04);
    }

    .nominee-card {
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
    }

    .nominee-avatar {
        width: 96px;
        height: 96px;
        border-radius: 12px;
        object-fit: cover;
        background: #e5e7eb;
    }

    .nominee-meta {
        font-size: 0.85rem;
        line-height: 1.4;
    }

    .nominee-manifesto {
        font-size: 0.9rem;
        color: #4b5563;
        line-height: 1.5;
        max-height: none;
        overflow: visible;
    }

    .nominee-section-title {
        font-size: 1.8rem;
        font-weight: 700;
    }

    @media (max-width: 767.98px) {
        .nominee-wrapper-card {
            padding: 18px;
        }
    }
</style>

<div class="container-fluid mt-4 mb-5">
    <div class="nominee-layout mx-auto">

        <!-- Page Title -->
        <div class="mb-3">
            <h2 class="nominee-section-title mb-1">View Nominees</h2>
            <p class="text-muted mb-0">
                Browse nominees by election and race. Select an election to get started.
            </p>
        </div>

        <div class="nominee-wrapper-card mt-3">

            <!-- Filters row -->
            <form method="get" class="row g-3 mb-4">
                <!-- Election -->
                <div class="col-md-6">
                    <label for="electionID" class="form-label fw-semibold">Election</label>
                    <select id="electionID" name="electionID" class="form-select" onchange="this.form.submit()">
                        <option value="">Select election</option>
                        <?php foreach ($elections as $e): ?>
                            <?php
                            $id = (int) ($e['electionID'] ?? 0);
                            $title = htmlspecialchars($e['title'] ?? '');
                            ?>
                            <option value="<?= $id ?>" <?= $id === $selectedElectionID ? 'selected' : '' ?>>
                                <?= $title ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Race -->
                <div class="col-md-6">
                    <label for="raceID" class="form-label fw-semibold">Race</label>
                    <select id="raceID" name="raceID" class="form-select" <?= $selectedElectionID ? '' : 'disabled' ?>
                        onchange="this.form.submit()">
                        <option value="">All races</option>
                        <?php foreach ($races as $r): ?>
                            <?php
                            $rid = (int) ($r['raceID'] ?? 0);
                            $rname = htmlspecialchars($r['raceTitle'] ?? '');
                            ?>
                            <option value="<?= $rid ?>" <?= $rid === $selectedRaceID ? 'selected' : '' ?>>
                                <?= $rname ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <!-- Nominees grid -->
            <?php if ($selectedElectionID === 0): ?>
                <div class="text-center text-muted py-2">
                    Please select an election to view nominees.
                </div>
            <?php elseif (empty($nominees)): ?>
                <div class="text-center text-muted py-2">
                    No nominees found for the selected filters.
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($nominees as $n): ?>
                        <?php
                        $nomineeID = (int) ($n['nomineeID'] ?? 0);
                        $fullName = htmlspecialchars($n['fullName'] ?? 'Unknown');
                        $facultyName = htmlspecialchars($n['facultyName'] ?? '');
                        $facultyCode = htmlspecialchars($n['facultyCode'] ?? '');
                        $programme = htmlspecialchars($n['programmeName'] ?? '');
                        $programmeCode = htmlspecialchars($n['programmeCode'] ?? '');
                        $raceTitle = htmlspecialchars($n['raceTitle'] ?? '');
                        $seatType = htmlspecialchars(str_replace('_', ' ', $n['seatType'] ?? ''));
                        $photoURL = $n['profilePhotoURL'] ?? '';
                        $photoURL = $photoURL !== '' ? $photoURL : '/image/defaultUserImage.jpg';

                        $rawManifesto = trim((string) ($n['manifesto'] ?? ''));
                        $hasManifesto = $rawManifesto !== '';
                        $manifestoHtml = $hasManifesto
                            ? nl2br(htmlspecialchars($rawManifesto))
                            : 'No manifesto provided.';
                        ?>
                        <div class="col-md-6">
                            <div class="card nominee-card h-100">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <!-- Picture -->
                                        <div class="me-3">
                                            <img src="<?= htmlspecialchars($photoURL) ?>" alt="Nominee photo"
                                                class="nominee-avatar">
                                        </div>

                                        <!-- Text -->
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <h5 class="mb-0"><?= $fullName ?></h5>
                                                <?php if ($raceTitle !== ''): ?>
                                                    <span class="badge bg-light text-dark border">
                                                        <?= $raceTitle ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="nominee-meta text-muted mb-2">
                                                <?php if ($facultyName !== ''): ?>
                                                    <div>
                                                        <?= $facultyName ?>
                                                        <?= $facultyCode ? " ({$facultyCode})" : '' ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($programme !== ''): ?>
                                                    <div>
                                                        <?= $programme ?>
                                                        <?= $programmeCode ? " ({$programmeCode})" : '' ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($seatType !== ''): ?>
                                                    <div>
                                                        <span class="text-secondary">
                                                            Seat type:
                                                        </span>
                                                        <?= $seatType ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <p
                                                class="nominee-manifesto mb-0 <?= $hasManifesto ? '' : 'text-muted fst-italic' ?>">
                                                <?= $manifestoHtml ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

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