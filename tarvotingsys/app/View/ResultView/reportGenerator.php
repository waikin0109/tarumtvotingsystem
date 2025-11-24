<?php
// app/View/ReportView/reportGenerator.php

$_title = 'Report Generator';
$roleUpper = strtoupper($_SESSION['role'] ?? '');

// Admin-only guard
if ($roleUpper !== 'ADMIN') {
    set_flash('fail', 'You are not allowed to access the report generator.');
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/../AdminView/adminHeader.php';

/**
 * Expected data (from controller):
 * - $elections:     completed elections
 * - $voteSessions:  closed vote sessions for completed elections
 * - $races:         races for completed elections
 * - $old:           previous form values (optional)
 * - $errors:        global error messages (optional)
 * - $fieldErrors:   per-field errors: ['electionID'=>..., 'voteSessionID'=>..., 'raceID'=>...]
 */

$old = $old ?? [];
$errors = $errors ?? [];
$fieldErrors = $fieldErrors ?? [];
$elections = $elections ?? [];
$voteSessions = $voteSessions ?? [];
$races = $races ?? [];

$selectedElectionID = $old['electionID'] ?? '';
$selectedSessionID = $old['voteSessionID'] ?? '';
$selectedRaceID = $old['raceID'] ?? '';
$reportType = $old['reportType'] ?? 'overall_turnout';
$outputFormat = $old['outputFormat'] ?? 'PDF';

function checkedRT(string $current, string $value): string
{
    return $current === $value ? 'checked' : '';
}

function selectedOpt(string $current, string $value): string
{
    return $current === $value ? 'selected' : '';
}

/* ---------- availability + disable flags (like viewStatisticalData) -------- */

$hasElection = !empty($selectedElectionID);
$hasSession = !empty($selectedSessionID);
$hasRace = !empty($selectedRaceID);

// Is there at least one CLOSED session for this election?
$noSessionAvailable = false;
if ($hasElection) {
    $found = false;
    foreach ($voteSessions as $vs) {
        if ((int) $vs['electionID'] === (int) $selectedElectionID) {
            $found = true;
            break;
        }
    }
    $noSessionAvailable = !$found;
}

// Is there at least one race for this election + session?
$noRaceAvailable = false;
if ($hasSession) {
    $found = false;
    foreach ($races as $r) {
        if (
            (int) $r['electionID'] === (int) $selectedElectionID &&
            (int) $r['voteSessionID'] === (int) $selectedSessionID
        ) {
            $found = true;
            break;
        }
    }
    $noRaceAvailable = !$found;
}

// Disable behaviour:
// - Session disabled if no election or that election has no sessions
// - Race disabled if no session or that session has no races
$disableSessionSelect = !$hasElection || $noSessionAvailable;
$disableRaceSelect = !$hasSession || $noRaceAvailable;
?>

<div class="container-fluid mt-4 mb-5">

    <!-- Page title + info -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-0">Report Generator</h2>
            <small class="text-muted">
                Generate official turnout, results summary, race breakdown, and early-vote reports for completed
                elections.
            </small>
        </div>
        <div>
            <span class="badge bg-secondary me-1">Admin Only</span>
            <span class="badge bg-info text-dark">Data is read-only</span>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $msg): ?>
                    <li><?= htmlspecialchars($msg) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="/admin/reports/generate" method="POST" class="card shadow-sm">
        <div class="card-body">

            <!-- 1. Filters row -->
            <h5 class="card-title mb-3">Filters</h5>
            <div class="row g-3 mb-4">

                <!-- Election Event -->
                <div class="col-md-4">
                    <label for="electionID" class="form-label fw-semibold">
                        Election Event <span class="text-danger">*</span>
                    </label>
                    <select name="electionID" id="electionID" class="form-select" required>
                        <option value="">-- Select Election Event --</option>
                        <?php if (!empty($elections)): ?>
                            <?php foreach ($elections as $e): ?>
                                <option value="<?= (int) $e['electionID'] ?>" <?= selectedOpt((string) $selectedElectionID, (string) $e['electionID']) ?>>
                                    <?= htmlspecialchars($e['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div class="form-text">
                        Only completed elections will appear here.
                    </div>
                    <?php if (!empty($fieldErrors['electionID'])): ?>
                        <div class="text-danger small mt-1">
                            <?= htmlspecialchars($fieldErrors['electionID']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Vote Session -->
                <div class="col-md-4">
                    <label for="voteSessionID" class="form-label fw-semibold">
                        Vote Session
                    </label>
                    <select name="voteSessionID" id="voteSessionID" class="form-select" <?= $disableSessionSelect ? 'disabled' : '' ?>>
                        <option value="">-- All Sessions --</option>
                        <?php foreach ($voteSessions as $vs): ?>
                            <option value="<?= (int) $vs['voteSessionID'] ?>" data-election="<?= (int) $vs['electionID'] ?>"
                                <?= selectedOpt((string) $selectedSessionID, (string) $vs['voteSessionID']) ?>>
                                <?= htmlspecialchars($vs['voteSessionName']) ?>
                                (<?= htmlspecialchars($vs['voteSessionType']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                        Only closed sessions of the selected election are available.
                    </div>

                    <?php if ($noSessionAvailable && $hasElection): ?>
                        <div class="text-danger small mt-1">
                            No closed vote session is available for the selected election.
                        </div>
                    <?php elseif (!empty($fieldErrors['voteSessionID'])): ?>
                        <div class="text-danger small mt-1">
                            <?= htmlspecialchars($fieldErrors['voteSessionID']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Race / Contest -->
                <div class="col-md-4">
                    <label for="raceID" class="form-label fw-semibold">
                        Race / Contest
                    </label>
                    <select name="raceID" id="raceID" class="form-select" <?= $disableRaceSelect ? 'disabled' : '' ?>>
                        <option value="">-- All Races --</option>
                        <?php if (!empty($races)): ?>
                            <?php foreach ($races as $r): ?>
                                <option value="<?= (int) $r['raceID'] ?>" data-election="<?= (int) $r['electionID'] ?>"
                                    data-session="<?= (int) $r['voteSessionID'] ?>" <?= selectedOpt((string) $selectedRaceID, (string) $r['raceID']) ?>>
                                    <?= htmlspecialchars($r['raceName']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div class="form-text">
                        This list is filtered by the selected election event and vote session.
                    </div>

                    <?php if ($noRaceAvailable && $hasSession): ?>
                        <div class="text-danger small mt-1">
                            No race is available for the selected vote session.
                        </div>
                    <?php elseif (!empty($fieldErrors['raceID'])): ?>
                        <div class="text-danger small mt-1">
                            <?= htmlspecialchars($fieldErrors['raceID']) ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div><!-- /row filters -->

            <hr>

            <!-- 2. Report Type selection (Turnout & Results only) -->
            <h5 class="mb-3">Report Type</h5>

            <div class="row g-3">
                <div class="col-12">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold">Turnout &amp; Results</span>
                            <span class="badge bg-success">On-screen + Download</span>
                        </div>

                        <!-- overall_turnout -->
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="reportType" id="rt_overall_turnout"
                                value="overall_turnout" <?= checkedRT($reportType, 'overall_turnout') ?>>
                            <label class="form-check-label" for="rt_overall_turnout">
                                <span class="fw-semibold">Overall Turnout Summary</span><br>
                                <small class="text-muted">
                                    Election name, session, eligible voters, ballots cast, turnout %.
                                    Shows on a summary page and can be exported.
                                </small>
                            </label>
                        </div>

                        <!-- official_results_all -->
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="reportType" id="rt_official_results_all"
                                value="official_results_all" <?= checkedRT($reportType, 'official_results_all') ?>>
                            <label class="form-check-label" for="rt_official_results_all">
                                <span class="fw-semibold">Official Results by Race (All Races)</span><br>
                                <small class="text-muted">
                                    For each race: candidate list, total votes, and winners highlighted.
                                    Used as the main “Official Final Results” view.
                                </small>
                            </label>
                        </div>

                        <!-- results_by_faculty -->
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="reportType" id="rt_results_by_faculty"
                                value="results_by_faculty" <?= checkedRT($reportType, 'results_by_faculty') ?>>
                            <label class="form-check-label" for="rt_results_by_faculty">
                                <span class="fw-semibold">Results by Faculty / Campus</span><br>
                                <small class="text-muted">
                                    Same race but broken down by faculty/campus (results by precinct style).
                                    Shown as a detailed page &amp; export.
                                </small>
                            </label>
                        </div>

                        <!-- NEW: early_vote_status -->
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="reportType" id="rt_early_vote_status"
                                value="early_vote_status" <?= checkedRT($reportType, 'early_vote_status') ?>>
                            <label class="form-check-label" for="rt_early_vote_status">
                                <span class="fw-semibold">Early Vote Status (Early vs Main)</span><br>
                                <small class="text-muted">
                                    Compares early vs main ballots and turnout % by faculty.
                                    For this report, only the <strong>Election Event</strong> is used;
                                    vote session and race filters are ignored by the system.
                                </small>
                            </label>
                        </div>

                    </div>
                </div>
            </div><!-- /row report types -->

            <hr class="my-4">

            <!-- 3. Output format + action -->
            <div class="row align-items-center">
                <div class="col-md-4 mb-3 mb-md-0">
                    <label for="outputFormat" class="form-label fw-semibold">
                        Output Format
                    </label>
                    <select name="outputFormat" id="outputFormat" class="form-select">
                        <option value="PDF" <?= selectedOpt($outputFormat, 'PDF') ?>>PDF</option>
                        <option value="CSV" <?= selectedOpt($outputFormat, 'CSV') ?>>CSV</option>
                        <option value="XLSX" <?= selectedOpt($outputFormat, 'XLSX') ?>>Excel (XLSX)</option>
                    </select>
                    <div class="form-text">
                        All reports can be exported to PDF, CSV, or Excel.
                    </div>
                </div>

                <!-- <div class="col-md-8 text-md-end">
                    <small class="text-muted d-block mb-2">
                        When you generate an on-screen report, you will be redirected
                        to the corresponding summary/details page. For download-only reports,
                        a file download will start automatically.
                    </small>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-file-earmark-bar-graph me-1"></i> Generate Report
                    </button>
                </div> -->
            </div>

        </div><!-- /card-body -->
    </form>

            <div class="d-flex justify-content-center gap-3 mt-4">
            <a href="<?= htmlspecialchars($backUrl ?? '/admin/reports/list') ?>"
                class="btn btn-outline-secondary px-4">Back</a>
            <button type="submit" class="btn btn-primary px-4">Generate Report</button>
        </div>
</div>

<script>
    (function () {
        const electionSelect = document.getElementById('electionID');
        const voteSessionSelect = document.getElementById('voteSessionID');
        const raceSelect = document.getElementById('raceID');

        if (!electionSelect || !voteSessionSelect || !raceSelect) return;

        function filterVoteSessions() {
            const eid = electionSelect.value;

            Array.from(voteSessionSelect.options).forEach(opt => {
                if (!opt.value) { // "-- All Sessions --"
                    opt.hidden = false;
                    return;
                }
                const optElection = opt.getAttribute('data-election');
                opt.hidden = !!eid && optElection !== eid;
            });

            if (voteSessionSelect.selectedOptions.length &&
                voteSessionSelect.selectedOptions[0].hidden) {
                voteSessionSelect.value = '';
            }
        }

        function filterRaces() {
            const eid = electionSelect.value;
            const vsid = voteSessionSelect.value;

            Array.from(raceSelect.options).forEach(opt => {
                if (!opt.value) { // "-- All Races --"
                    opt.hidden = false;
                    return;
                }
                const raceEid = opt.getAttribute('data-election');
                const raceVs = opt.getAttribute('data-session');

                let show = true;
                if (eid && raceEid !== eid) {
                    show = false;
                }
                if (vsid && raceVs && raceVs !== vsid) {
                    show = false;
                }
                opt.hidden = !show;
            });

            if (raceSelect.selectedOptions.length &&
                raceSelect.selectedOptions[0].hidden) {
                raceSelect.value = '';
            }
        }

        function updateDisabledStates() {
            const eid = electionSelect.value;
            const vsid = voteSessionSelect.value;

            const hasElection = !!eid;
            const hasSession = !!vsid;

            let anyVsVisible = false;
            Array.from(voteSessionSelect.options).forEach(opt => {
                if (opt.value && !opt.hidden) anyVsVisible = true;
            });

            voteSessionSelect.disabled = !hasElection || !anyVsVisible;

            let anyRaceVisible = false;
            Array.from(raceSelect.options).forEach(opt => {
                if (opt.value && !opt.hidden) anyRaceVisible = true;
            });

            raceSelect.disabled = !hasSession || !anyRaceVisible;

            if (!hasElection) {
                voteSessionSelect.value = '';
                raceSelect.value = '';
            }
            if (!hasSession) {
                raceSelect.value = '';
            }
        }

        electionSelect.addEventListener('change', function () {
            filterVoteSessions();
            filterRaces();
            updateDisabledStates();
        });

        voteSessionSelect.addEventListener('change', function () {
            filterRaces();
            updateDisabledStates();
        });

        // initial state on page load
        filterVoteSessions();
        filterRaces();
        updateDisabledStates();
    })();
</script>

<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>