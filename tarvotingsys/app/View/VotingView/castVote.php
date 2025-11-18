<?php
$_title = 'Cast Vote';
// Reuse adminHeader which adapts to role
require_once __DIR__ . '/../AdminView/adminHeader.php';

/**
 * Variables expected from controller:
 * $electionTitle, $sessionName, $sessionTypeLabel, $sessionId
 * $races (with nested 'nominees')
 * $oldSelections (raceID => [nomineeID,...])
 * $selectionErrors (raceID => [messages])
 */

$oldSelections = $oldSelections ?? [];
$selectionErrors = $selectionErrors ?? [];

// Split races into Faculty Rep and Campus Wide buckets
$facultyRepRaces = [];
$campusWideRaces = [];
$roleUpper = strtoupper($_SESSION['role'] ?? '');
$backUrl = ($roleUpper === 'ADMIN') ? '/vote-session' : '/vote-session/public';


foreach ($races as $race) {
  if (strtoupper($race['seatType']) === 'FACULTY_REP') {
    $facultyRepRaces[] = $race;
  } else {
    $campusWideRaces[] = $race;
  }
}

function race_has_error(array $selectionErrors, int $raceId): bool
{
  return !empty($selectionErrors[$raceId]);
}

function race_error_text(array $selectionErrors, int $raceId): string
{
  if (empty($selectionErrors[$raceId])) {
    return '';
  }
  return htmlspecialchars(implode(' ', $selectionErrors[$raceId]), ENT_QUOTES, 'UTF-8');
}
?>

<style>
  .cast-layout {
    max-width: 1300px;
  }

  .race-card {
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
  }

  .candidate-card {
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    padding: 0.75rem;
    cursor: pointer;
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
    height: 100%;
  }

  .candidate-card:hover {
    box-shadow: 0 4px 10px rgba(15, 23, 42, 0.06);
    border-color: #4f46e5;
  }

  .candidate-selected {
    border-color: #2563eb;
    box-shadow: 0 0 0 1px #2563eb;
  }

.candidate-photo {
  width: 90px;
  height: 90px;
  border-radius: 8px;
  background-color: #e5e7eb;
  object-fit: cover;
  border: 1px solid #d1d5db;
}

  .race-header-title {
    font-weight: 600;
    font-size: 1.1rem;
  }

  .race-subtitle {
    font-size: 0.9rem;
    color: #6b7280;
  }

  .selection-hint {
    font-size: 0.85rem;
    color: #6b7280;
  }

  .race-error {
    font-size: 0.85rem;
  }
</style>

<div class="container my-3 cast-layout">
  <h2 class="mb-2">
    Cast Vote – <?= htmlspecialchars($sessionTypeLabel ?? '', ENT_QUOTES, 'UTF-8') ?>
  </h2>
  <p class="text-muted mb-3">
    <?= htmlspecialchars($electionTitle ?? '', ENT_QUOTES, 'UTF-8') ?> —
    <?= htmlspecialchars($sessionName ?? '', ENT_QUOTES, 'UTF-8') ?>
  </p>

  <form method="post" action="/ballot/cast/<?= (int) $sessionId ?>" id="castVoteForm">
    <?php if (!empty($facultyRepRaces)): ?>
      <div class="card race-card mb-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
              <div class="race-header-title">Faculty Representative – Choose up to 1</div>
              <div class="race-subtitle">
                You may vote for up to <strong>one</strong> candidate for your faculty.
              </div>
            </div>
          </div>

          <?php foreach ($facultyRepRaces as $race): ?>
            <?php
            $rid = (int) $race['raceID'];
            $raceTitle = $race['raceTitle'] ?? 'Faculty Representative';
            ?>
            <div class="mb-2">
              <div class="fw-semibold mb-1">
                <?= htmlspecialchars($raceTitle, ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($race['facultyName'])): ?>
                  (<?= htmlspecialchars($race['facultyName'], ENT_QUOTES, 'UTF-8') ?>)
                <?php endif; ?>
              </div>

              <?php if (empty($race['nominees'])): ?>
                <p class="text-muted fst-italic mb-3">No nominees have been published for this race yet.</p>
              <?php else: ?>
                <div class="row g-3 mb-1">
                  <?php foreach ($race['nominees'] as $nominee): ?>
                    <?php
                    $nid = (int) $nominee['nomineeID'];
                    $isChecked = in_array($nid, $oldSelections[$rid] ?? [], true);
                    ?>
                    <div class="col-md-4">
                      <label
                        class="candidate-card d-flex gap-3 align-items-start <?= $isChecked ? 'candidate-selected' : '' ?>">
                        <input type="radio" class="form-check-input mt-2 candidate-input" name="selections[<?= $rid ?>][]"
                          value="<?= $nid ?>" data-race-title="<?= htmlspecialchars($raceTitle, ENT_QUOTES, 'UTF-8') ?>"
                          data-seat-type="FACULTY_REP"
                          data-candidate-name="<?= htmlspecialchars($nominee['fullName'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                          style="margin-right: .5rem;" <?= $isChecked ? 'checked' : '' ?>>
                        <?php if (!empty($nominee['profilePhotoURL'])): ?>
                          <img src="/uploads/profile/<?= htmlspecialchars($nominee['profilePhotoURL'], ENT_QUOTES, 'UTF-8') ?>"
                            alt="Photo" class="candidate-photo mt-1">
                        <?php else: ?>
                          <div class="candidate-photo mt-1 d-flex align-items-center justify-content-center">
                            <span class="text-muted small">No Photo</span>
                          </div>
                        <?php endif; ?>
                        <div>
                          <div class="fw-semibold">
                            <?= htmlspecialchars($nominee['fullName'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                          </div>
                          <?php if (!empty($nominee['manifesto'])): ?>
                            <div class="small text-muted mt-1" style="max-height: 4.5rem; overflow: hidden;">
                              <?= nl2br(htmlspecialchars($nominee['manifesto'], ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                          <?php endif; ?>
                        </div>
                      </label>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <?php if (race_has_error($selectionErrors, $rid)): ?>
                <div class="text-danger race-error mb-2">
                  <?= race_error_text($selectionErrors, $rid) ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <div class="selection-hint mt-2">
            You must select <strong>zero or one</strong> Faculty Representative.
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($campusWideRaces)): ?>
      <div class="card race-card mb-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
              <div class="race-header-title">Campus Wide Representative – Choose up to 4</div>
              <div class="race-subtitle">
                You may vote for up to <strong>four</strong> candidates from across the campus.
              </div>
            </div>
          </div>

          <?php foreach ($campusWideRaces as $race): ?>
            <?php
            $rid = (int) $race['raceID'];
            $raceTitle = $race['raceTitle'] ?? 'Campus Wide Representative';
            $maxSel = (int) $race['maxSelectable'];
            ?>
            <div class="mb-2">
              <div class="fw-semibold mb-1">
                <?= htmlspecialchars($raceTitle, ENT_QUOTES, 'UTF-8') ?>
              </div>

              <?php if (empty($race['nominees'])): ?>
                <p class="text-muted fst-italic mb-3">No nominees have been published for this race yet.</p>
              <?php else: ?>
                <div class="row g-3 mb-1">
                  <?php foreach ($race['nominees'] as $nominee): ?>
                    <?php
                    $nid = (int) $nominee['nomineeID'];
                    $isChecked = in_array($nid, $oldSelections[$rid] ?? [], true);
                    ?>
                    <div class="col-md-4">
                      <label
                        class="candidate-card d-flex gap-3 align-items-start <?= $isChecked ? 'candidate-selected' : '' ?>">
                        <input type="checkbox" class="form-check-input mt-2 candidate-input campus-wide-input"
                          name="selections[<?= $rid ?>][]" value="<?= $nid ?>" data-race-id="<?= $rid ?>"
                          data-max-select="<?= $maxSel ?>"
                          data-race-title="<?= htmlspecialchars($raceTitle, ENT_QUOTES, 'UTF-8') ?>"
                          data-seat-type="CAMPUS_WIDE"
                          data-candidate-name="<?= htmlspecialchars($nominee['fullName'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                          style="margin-right: .5rem;" <?= $isChecked ? 'checked' : '' ?>>
                        <?php if (!empty($nominee['profilePhotoURL'])): ?>
                          <img src="/uploads/profile/<?= htmlspecialchars($nominee['profilePhotoURL'], ENT_QUOTES, 'UTF-8') ?>"
                            alt="Photo" class="candidate-photo mt-1">
                        <?php else: ?>
                          <div class="candidate-photo mt-1 d-flex align-items-center justify-content-center">
                            <span class="text-muted small">No Photo</span>
                          </div>
                        <?php endif; ?>
                        <div>
                          <div class="fw-semibold">
                            <?= htmlspecialchars($nominee['fullName'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                          </div>
                          <?php if (!empty($nominee['manifesto'])): ?>
                            <div class="small text-muted mt-1" style="max-height: 4.5rem; overflow: hidden;">
                              <?= nl2br(htmlspecialchars($nominee['manifesto'], ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                          <?php endif; ?>
                        </div>
                      </label>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <?php if (race_has_error($selectionErrors, $rid)): ?>
                <div class="text-danger race-error mb-2">
                  <?= race_error_text($selectionErrors, $rid) ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <div class="selection-hint mt-2">
            You may choose fewer than the maximum allowed, but not more.
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- If somehow no races at all -->
    <?php if (empty($facultyRepRaces) && empty($campusWideRaces)): ?>
      <div class="alert alert-info">
        There are no races available to vote in this session yet.
      </div>
    <?php endif; ?>

    <div class="d-flex justify-content-end align-items-center mb-5">
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-secondary" id="previewBtn">
          Preview
        </button>
        <button type="submit" class="btn btn-primary">
          Submit Ballot
        </button>
      </div>
    </div>

  </form>
</div>

<!-- Simple preview modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Ballot Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted">Please review your selections before submitting.</p>
        <div id="previewContent"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          Close
        </button>
        <button type="button" class="btn btn-primary" id="confirmSubmitBtn">
          Confirm &amp; Submit
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  // Highlight selected candidate cards
  (function () {
    document.querySelectorAll('.candidate-input').forEach(function (input) {
      input.addEventListener('change', function () {
        const label = this.closest('.candidate-card');

        if (this.type === 'radio') {
          // Clear all radios in same group
          document
            .querySelectorAll('input[name="' + this.name + '"]')
            .forEach(function (r) {
              const l = r.closest('.candidate-card');
              if (l) l.classList.remove('candidate-selected');
            });
        }

        if (label) {
          if (this.checked) {
            label.classList.add('candidate-selected');
          } else if (this.type === 'checkbox') {
            label.classList.remove('candidate-selected');
          }
        }
      });
    });

    // Enforce max selections for campus wide per race
    document.querySelectorAll('.campus-wide-input').forEach(function (input) {
      input.addEventListener('change', function () {
        const raceId = this.dataset.raceId;
        const maxSel = parseInt(this.dataset.maxSelect || '0', 10);
        if (!raceId || !maxSel) return;

        const checkboxes = document.querySelectorAll('.campus-wide-input[data-race-id="' + raceId + '"]');
        const checked = Array.from(checkboxes).filter(function (c) { return c.checked; });

        if (checked.length > maxSel) {
          // Undo and warn
          this.checked = false;
          const label = this.closest('.candidate-card');
          if (label) label.classList.remove('candidate-selected');
          alert('You can select up to ' + maxSel + ' candidates for this race.');
        }
      });
    });

    // Preview modal
    const previewBtn = document.getElementById('previewBtn');
    const previewModal = document.getElementById('previewModal');
    const previewBody = document.getElementById('previewContent');
    const confirmBtn = document.getElementById('confirmSubmitBtn');
    const form = document.getElementById('castVoteForm');

    if (previewBtn && previewModal && previewBody && confirmBtn && form) {
      const bsModal = new bootstrap.Modal(previewModal);

      previewBtn.addEventListener('click', function () {
        const selected = document.querySelectorAll('.candidate-input:checked');
        if (!selected.length) {
          previewBody.innerHTML = '<p class="text-muted mb-0">You have not selected any candidates yet.</p>';
          bsModal.show();
          return;
        }

        const groups = {};

        selected.forEach(function (input) {
          const seatType = input.dataset.seatType || 'Other';
          const raceTitle = input.dataset.raceTitle || 'Race';
          const candidateName = input.dataset.candidateName || 'Candidate';

          const key = seatType + '|' + raceTitle;
          if (!groups[key]) {
            groups[key] = {
              seatType: seatType,
              raceTitle: raceTitle,
              candidates: []
            };
          }
          groups[key].candidates.push(candidateName);
        });

        let html = '';
        Object.keys(groups).forEach(function (key) {
          const g = groups[key];
          html += '<div class="mb-3">';
          html += '<div class="fw-semibold">' + g.raceTitle + '</div>';
          html += '<ul class="mb-0">';
          g.candidates.forEach(function (c) {
            html += '<li>' + c + '</li>';
          });
          html += '</ul>';
          html += '</div>';
        });

        previewBody.innerHTML = html;
        bsModal.show();
      });

      confirmBtn.addEventListener('click', function () {
        bsModal.hide();
        form.submit();
      });
    }
  })();
</script>

<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>