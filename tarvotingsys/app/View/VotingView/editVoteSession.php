<?php
$_title = 'Edit Voting Session';
require_once __DIR__ . '/../AdminView/adminHeader.php';

$old = $old ?? ['electionID' => '', 'sessionName' => '', 'sessionType' => '', 'startAtLocal' => '', 'endAtLocal' => '', 'races' => []];
$fieldErrors = $fieldErrors ?? [];
$raceRowErrors = $fieldErrors['races_by_row'] ?? [];
$faculties = $faculties ?? [];
$elections = $elections ?? [];

function race_err_has(array $errs, int $idx, string $field): bool
{
    return !empty($errs[$idx][$field]);
}

function race_err_msgs(array $errs, int $idx, string $field): string
{
    return !empty($errs[$idx][$field]) ? htmlspecialchars(implode(' ', $errs[$idx][$field])) : '';
}
?>

<style>
  .fieldset { border:1px solid #dee2e6; border-radius:.5rem; padding:1rem 1.25rem; background:#fff; }
  .fieldset legend { font-size:1rem; font-weight:600; }
  .race-card { border:1px dashed #ced4da; border-radius:.75rem; padding:1rem; background:#fafafa; }
  .race-label { font-weight:600; }
  .muted-help { font-size:.875rem; color:#6c757d; }
</style>

<div class="container-fluid mt-4 mb-5">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="mb-0">Edit Voting Session</h2>
    </div>

    <form action="/vote-session/edit/<?= $id ?>" method="POST" id="editForm" novalidate>
         <input type="hidden" name="voteSessionID" value="<?= (int)$id ?>">
        <!-- Session Details -->
        <fieldset class="fieldset mb-4">
            <legend>Session Details</legend>

            <div class="row g-3 align-items-start">
                <!-- Election Title -->
                <div class="col-md-6">
                    <label class="form-label">Election Title</label>
                    <select name="electionID" class="form-select <?= !empty($fieldErrors['electionID']) ? 'is-invalid' : '' ?>" required>
                        <option value="">-- Select an election --</option>
                        <?php foreach ($elections as $e): ?>
                            <option value="<?= (int)$e['electionID'] ?>" <?= (string)$old['electionID'] === (string)$e['electionID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['title']) ?> (<?= htmlspecialchars($e['status']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($fieldErrors['electionID'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars(implode(' ', $fieldErrors['electionID'])) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Session Name -->
                <div class="col-md-6">
                    <label class="form-label">Session Name</label>
                    <input type="text" name="sessionName" class="form-control <?= !empty($fieldErrors['sessionName']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['sessionName']) ?>" placeholder="e.g., Early Voting">
                    <?php if (!empty($fieldErrors['sessionName'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars(implode(' ', $fieldErrors['sessionName'])) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Session Type -->
                <div class="col-md-6">
                    <label class="form-label">Session Type</label>
                    <select name="sessionType" class="form-select <?= !empty($fieldErrors['sessionType']) ? 'is-invalid' : '' ?>">
                        <option value="">-- Select session type --</option>
                        <option value="EARLY" <?= ($old['sessionType'] ?? '') === 'EARLY' ? 'selected' : ''; ?>>EARLY</option>
                        <option value="MAIN" <?= ($old['sessionType'] ?? '') === 'MAIN' ? 'selected' : ''; ?>>MAIN</option>
                    </select>
                    <?php if (!empty($fieldErrors['sessionType'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars(implode(' ', $fieldErrors['sessionType'])) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Start Date and Time -->
                <div class="col-md-3">
                    <label class="form-label">Start Date and Time</label>
                    <input type="datetime-local" name="startAtLocal"
                           class="form-control <?= !empty($fieldErrors['startAt']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['startAtLocal']) ?>">
                    <?php if (!empty($fieldErrors['startAt'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars(implode(' ', $fieldErrors['startAt'])) ?></div>
                    <?php endif; ?>
                </div>

                <!-- End Date and Time -->
                <div class="col-md-3">
                    <label class="form-label">End Date and Time</label>
                    <input type="datetime-local" name="endAtLocal"
                           class="form-control <?= !empty($fieldErrors['endAt']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['endAtLocal']) ?>">
                    <?php if (!empty($fieldErrors['endAt'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars(implode(' ', $fieldErrors['endAt'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </fieldset>

        <!-- Races -->
        <fieldset class="fieldset mb-4" id="racesFieldset">
            <legend>Races Included</legend>

            <div id="raceList" class="d-flex flex-column gap-3">
                <?php if (empty($old['races'])): // seed one blank row with 0/0 ?>
                    <?php $old['races'] = [['title' => '', 'seatType' => '', 'facultyID' => '', 'seatCount' => 0, 'maxSelectable' => 0]]; ?>
                <?php endif; ?>

                <?php foreach ($old['races'] as $idx => $r): ?>
                    <div class="race-card" data-index="<?= $idx ?>">
                         <input type="hidden"
               name="races[<?= $idx ?>][raceID]"
               value="<?= isset($r['raceID']) ? (int)$r['raceID'] : 0 ?>">

                        <div class="mb-3">
                            <label class="form-label race-label">Race Title</label>
                            <input type="text"
                                   name="races[<?= $idx ?>][title]"
                                   class="form-control <?= race_err_has($raceRowErrors, $idx, 'title') ? 'is-invalid' : '' ?>"
                                   value="<?= htmlspecialchars($r['title'] ?? '') ?>"
                                   placeholder="e.g., Faculty Representative">
                            <?php if (race_err_has($raceRowErrors, $idx, 'title')): ?>
                                <div class="invalid-feedback"><?= race_err_msgs($raceRowErrors, $idx, 'title') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label race-label">Seat Type</label>
                                <select class="form-select seat-type <?= race_err_has($raceRowErrors, $idx, 'seatType') ? 'is-invalid' : '' ?>"
                                        name="races[<?= $idx ?>][seatType]">
                                    <option value="">-- Select seat type --</option>
                                    <option value="FACULTY_REP" <?= ($r['seatType'] ?? '') === 'FACULTY_REP' ? 'selected' : '' ?>>FACULTY_REP</option>
                                    <option value="CAMPUS_WIDE" <?= ($r['seatType'] ?? '') === 'CAMPUS_WIDE' ? 'selected' : '' ?>>CAMPUS_WIDE</option>
                                </select>
                                <?php if (race_err_has($raceRowErrors, $idx, 'seatType')): ?>
                                    <div class="invalid-feedback"><?= race_err_msgs($raceRowErrors, $idx, 'seatType') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-4 faculty-col">
                                <label class="form-label race-label">Faculty</label>
                                <select class="form-select faculty-select <?= race_err_has($raceRowErrors, $idx, 'facultyID') ? 'is-invalid' : '' ?>"
                                        name="races[<?= $idx ?>][facultyID]">
                                    <option value="">-- Select faculty --</option>
                                    <?php foreach ($faculties as $f): ?>
                                        <option value="<?= (int)$f['facultyID'] ?>"
                                                <?= (string)($r['facultyID'] ?? '') === (string)$f['facultyID'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($f['facultyName']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (race_err_has($raceRowErrors, $idx, 'facultyID')): ?>
                                    <div class="invalid-feedback"><?= race_err_msgs($raceRowErrors, $idx, 'facultyID') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label race-label">Seats</label>
                                <input type="number" min="0" max="10"
                                       class="form-control seat-count <?= race_err_has($raceRowErrors, $idx, 'seatCount') ? 'is-invalid' : '' ?>"
                                       name="races[<?= $idx ?>][seatCount]"
                                       value="<?= isset($r['seatCount']) ? (int)$r['seatCount'] : 0 ?>">
                                <?php if (race_err_has($raceRowErrors, $idx, 'seatCount')): ?>
                                    <div class="invalid-feedback"><?= race_err_msgs($raceRowErrors, $idx, 'seatCount') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label race-label">Max Select</label>
                                <input type="number" min="0" max="10"
                                       class="form-control max-select <?= race_err_has($raceRowErrors, $idx, 'maxSelectable') ? 'is-invalid' : '' ?>"
                                       name="races[<?= $idx ?>][maxSelectable]"
                                       value="<?= isset($r['maxSelectable']) ? (int)$r['maxSelectable'] : 0 ?>">
                                <?php if (race_err_has($raceRowErrors, $idx, 'maxSelectable')): ?>
                                    <div class="invalid-feedback"><?= race_err_msgs($raceRowErrors, $idx, 'maxSelectable') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-3">
                            <?php $onlyOne = count($old['races']) <= 1; ?>
                            <button type="button"
                                    class="btn btn-outline-danger btn-sm remove-race"
                                    <?= $onlyOne ? 'disabled aria-disabled="true" title="At least one race is required"' : '' ?>>
                                Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" id="addRace" class="btn btn-outline-primary mt-3">Add Race</button>
        </fieldset>

        <div class="d-flex justify-content-center gap-3">
            <a href="/vote-session" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary px-4">Save</button>
        </div>

        <input type="hidden" name="startAt" id="startAt">
        <input type="hidden" name="endAt" id="endAt">
    </form>
</div>

<script>
    (function () {
        function clampMaxSelectableToSeatCount(card, allowZero) {
            const seatCountInput = card.querySelector('.seat-count');
            const maxSelInput = card.querySelector('.max-select');
            if (!seatCountInput || !maxSelInput) return;

            const minBase = allowZero ? 0 : 1;
            const sc = Math.max(minBase, parseInt(seatCountInput.value || String(minBase), 10));
            let ms = Math.max(minBase, parseInt(maxSelInput.value || String(minBase), 10));
            if (ms > sc) ms = sc;
            seatCountInput.value = sc;
            maxSelInput.value = ms;
        }

        function updateFacultyVisibility(card) {
            const seatType = card.querySelector('.seat-type').value;
            const facultyCol = card.querySelector('.faculty-col');
            const facultySel = card.querySelector('.faculty-select');
            const seatCount = card.querySelector('.seat-count');
            const maxSel = card.querySelector('.max-select');

            seatCount.removeAttribute('readonly');
            maxSel.removeAttribute('readonly');
            seatCount.removeAttribute('disabled');
            maxSel.removeAttribute('disabled');

            if (seatType === 'FACULTY_REP') {
                facultyCol.style.display = '';
                seatCount.value = 1;
                maxSel.value = 1;
                seatCount.setAttribute('readonly', 'readonly');
                maxSel.setAttribute('readonly', 'readonly');
                clampMaxSelectableToSeatCount(card, false);
            } else if (seatType === 'CAMPUS_WIDE') {
                facultyCol.style.display = 'none';
                if (facultySel) facultySel.value = '';
                seatCount.value = 4;
                maxSel.value = 4;
                seatCount.setAttribute('readonly', 'readonly');
                maxSel.setAttribute('readonly', 'readonly');
                clampMaxSelectableToSeatCount(card, false);
            } else {
                facultyCol.style.display = 'none';
                if (facultySel) facultySel.value = '';
                clampMaxSelectableToSeatCount(card, true); // keep 0/0 while placeholder
            }
        }

        function updateRemoveButtons() {
            const cards = document.querySelectorAll('#raceList .race-card');
            const disable = cards.length <= 1;
            document.querySelectorAll('#raceList .remove-race').forEach(btn => {
                btn.disabled = disable;
                btn.classList.toggle('disabled', disable);
                btn.setAttribute('aria-disabled', disable ? 'true' : 'false');
                if (disable) btn.title = 'At least one race is required';
                else btn.removeAttribute('title');
            });
        }

        // init
        document.querySelectorAll('.race-card').forEach(updateFacultyVisibility);
        updateRemoveButtons();

        // dynamic changes
        document.getElementById('raceList').addEventListener('change', function (e) {
            const card = e.target.closest('.race-card');
            if (!card) return;

            if (e.target.classList.contains('seat-type')) updateFacultyVisibility(card);

            if (e.target.classList.contains('seat-count') || e.target.classList.contains('max-select')) {
                const seatType = card.querySelector('.seat-type')?.value || '';
                if (seatType === '') {
                    const seatCount = card.querySelector('.seat-count');
                    const maxSel = card.querySelector('.max-select');
                    const sc = Math.max(0, parseInt(seatCount.value || '0', 10));
                    let ms = Math.max(0, parseInt(maxSel.value || '0', 10));
                    if (ms > sc) ms = sc;
                    seatCount.value = sc;
                    maxSel.value = ms;
                }
            }
        });

        // add new race card
        document.getElementById('addRace').addEventListener('click', function () {
            const list = document.getElementById('raceList');
            const idx = list.querySelectorAll('.race-card').length;
            const tmpl = `
              <div class="race-card" data-index="${idx}">
               <input type="hidden" name="races[${idx}][raceID]" value="">
                <div class="mb-3">
                  <label class="form-label race-label">Race Title</label>
                  <input type="text" name="races[${idx}][title]" maxlength="100" class="form-control"
                         placeholder="e.g., Faculty Representative">
                </div>
                <div class="row g-3 align-items-end">
                  <div class="col-md-4">
                    <label class="form-label race-label">Seat Type</label>
                    <select class="form-select seat-type" name="races[${idx}][seatType]">
                      <option value="">-- Select seat type --</option>
                      <option value="FACULTY_REP">FACULTY_REP</option>
                      <option value="CAMPUS_WIDE">CAMPUS_WIDE</option>
                    </select>
                  </div>
                  <div class="col-md-4 faculty-col" style="display:none;">
                    <label class="form-label race-label">Faculty</label>
                    <select class="form-select faculty-select" name="races[${idx}][facultyID]">
                      <option value="">-- Select faculty --</option>
                      <?php foreach ($faculties as $f): ?>
                        <option value="<?= (int)$f['facultyID'] ?>"><?= htmlspecialchars($f['facultyName']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label race-label">Seats</label>
                    <input type="number" min="0" max="10" class="form-control seat-count"
                           name="races[${idx}][seatCount]" value="0">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label race-label">Max Select</label>
                    <input type="number" min="0" max="10" class="form-control max-select"
                           name="races[${idx}][maxSelectable]" value="0">
                  </div>
                </div>
                <div class="mt-3">
                  <button type="button" class="btn btn-outline-danger btn-sm remove-race">Delete</button>
                </div>
              </div>`;
            list.insertAdjacentHTML('beforeend', tmpl);
            const newCard = list.querySelector('.race-card:last-child');
            updateFacultyVisibility(newCard);
            updateRemoveButtons();
        });

        // remove a race card
        document.getElementById('raceList').addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-race')) {
                const card = e.target.closest('.race-card');
                card.parentNode.removeChild(card);
                updateRemoveButtons();
            }
        });

        // convert datetime-local to MySQL before submit
        document.getElementById('editForm').addEventListener('submit', function () {
            const s = document.querySelector('input[name="startAtLocal"]').value;
            const e = document.querySelector('input[name="endAtLocal"]').value;
            document.getElementById('startAt').value = s ? s.replace('T',' ') + ':00' : '';
            document.getElementById('endAt').value = e ? e.replace('T',' ') + ':00' : '';
        });
    })();
</script>

<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>