<?php
$_title = 'Cast Vote';

$roleUpper = strtoupper($_SESSION['role'] ?? '');

if ($roleUpper === 'NOMINEE') {
  require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} elseif ($roleUpper === 'STUDENT') {
  require_once __DIR__ . '/../StudentView/studentHeader.php';
} elseif ($roleUpper === 'ADMIN') {
  require_once __DIR__ . '/../AdminView/adminHeader.php';
}

$oldSelections    = $oldSelections ?? [];
$selectionErrors  = $selectionErrors ?? [];

// Split races into Faculty Rep and Campus Wide buckets
$facultyRepRaces  = [];
$campusWideRaces  = [];
$backUrl          = ($roleUpper === 'ADMIN') ? '/vote-session' : '/vote-session/public';

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
  width: 100%;
  max-width: 100%;
  margin: 0;
}

  .race-card {
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
  }

  .race-header-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: .75rem;
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

  .candidate-card {
    position: relative;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    padding: 0.85rem 0.85rem 0.85rem 2.6rem;
    cursor: pointer;
    transition: box-shadow 0.15s ease, border-color 0.15s ease, transform 0.1s ease;
    height: 100%;
    background-color: #ffffff;
  }

  .candidate-card:hover {
    box-shadow: 0 4px 10px rgba(15, 23, 42, 0.06);
    border-color: #4f46e5;
    transform: translateY(-1px);
  }

  .candidate-selected {
    border-color: #2563eb;
    box-shadow: 0 0 0 1px #2563eb;
  }

  /* exact same size for all nominee images / no-photo boxes */
  .candidate-photo {
    width: 100px;
    height: 100px;
    border-radius: 10px;
    background-color: #e5e7eb;
    object-fit: cover;
    border: 1px solid #d1d5db;
  }

  .candidate-photo.flex-shrink-0 {
    flex-shrink: 0;
  }

  .candidate-input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
  }

  .candidate-check {
    position: absolute;
    top: 0.9rem;
    left: 0.9rem;
    width: 18px;
    height: 18px;
    border-radius: 999px;
    border: 2px solid #d1d5db;
    background-color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #2563eb;
    transition: background-color 0.15s ease, border-color 0.15s ease, transform 0.1s ease;
  }

  .candidate-card.candidate-selected .candidate-check {
    background-color: #2563eb;
    border-color: #2563eb;
    color: #ffffff;
    transform: scale(1.05);
  }

  .candidate-name {
    font-weight: 600;
    margin-bottom: 0.15rem;
  }

  .candidate-meta {
    font-size: 0.8rem;
    color: #9ca3af;
  }

  /* show FULL manifesto text – no truncation */
  .candidate-manifesto {
    font-size: 0.9rem;
    color: #4b5563;
    line-height: 1.4;
  }

  @media (max-width: 576px) {
    .candidate-card {
      padding-left: 2.4rem;
    }
    .candidate-photo {
      width: 80px;
      height: 80px;
    }
  }

   .preview-race-card {
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    padding: 0.9rem 1rem;
    margin-bottom: 0.75rem;
    background-color: #ffffff;
  }

  .preview-race-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.4rem;
  }

  .preview-race-title {
    font-weight: 600;
    font-size: 0.95rem;
  }

  .preview-seat-pill {
    font-size: 0.75rem;
    padding: 0.1rem 0.55rem;
    border-radius: 999px;
  }

  .preview-seat-pill.faculty {
    background-color: #dbeafe;
    color: #1d4ed8;
  }

  .preview-seat-pill.campus {
    background-color: #dcfce7;
    color: #15803d;
  }

  .preview-race-footer {
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 0.3rem;
  }
</style>

<div class="container-fluid mt-4 mb-5 cast-layout">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
    <div>
      <h2 class="mb-1">
        Cast Vote – <?= htmlspecialchars($sessionTypeLabel ?? '', ENT_QUOTES, 'UTF-8') ?>
      </h2>
      <p class="text-muted mb-0">
        <?= htmlspecialchars($electionTitle ?? '', ENT_QUOTES, 'UTF-8') ?> —
        <?= htmlspecialchars($sessionName ?? '', ENT_QUOTES, 'UTF-8') ?>
      </p>
    </div>
  </div>

  <form method="post" action="/ballot/cast/<?= (int) $sessionId ?>" id="castVoteForm">
    <?php if (!empty($facultyRepRaces)): ?>
      <div class="card race-card mb-4">
        <div class="card-body">
          <div class="race-header-bar">
            <div>
              <div class="race-header-title">Faculty Representative – Choose up to 1</div>
              <div class="race-subtitle">
                You may vote for up to <strong>one</strong> candidate for your faculty.
              </div>
            </div>
          </div>

          <?php foreach ($facultyRepRaces as $race): ?>
            <?php
            $rid       = (int) $race['raceID'];
            $raceTitle = $race['raceTitle'] ?? 'Faculty Representative';
            ?>
            <div class="mb-3">
              <div class="fw-semibold mb-2">
                <?= htmlspecialchars($raceTitle, ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($race['facultyName'])): ?>
                  <span class="text-muted">
                    (<?= htmlspecialchars($race['facultyName'], ENT_QUOTES, 'UTF-8') ?>)
                  </span>
                <?php endif; ?>
              </div>

              <?php if (empty($race['nominees'])): ?>
                <p class="text-muted fst-italic mb-3">No nominees have been published for this race yet.</p>
              <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-1">
                  <?php foreach ($race['nominees'] as $nominee): ?>
                    <?php
                    $nid       = (int) $nominee['nomineeID'];
                    $isChecked = in_array($nid, $oldSelections[$rid] ?? [], true);
                    $photo     = trim($nominee['profilePhotoURL'] ?? '');
                    ?>
                    <div class="col">
                      <label class="candidate-card d-flex gap-3 align-items-start <?= $isChecked ? 'candidate-selected' : '' ?>">
                        <input
                          type="radio"
                          class="candidate-input candidate-input-radio"
                          name="selections[<?= $rid ?>][]"
                          value="<?= $nid ?>"
                          data-race-title="<?= htmlspecialchars($raceTitle, ENT_QUOTES, 'UTF-8') ?>"
                          data-seat-type="FACULTY_REP"
                          data-candidate-name="<?= htmlspecialchars($nominee['fullName'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                          <?= $isChecked ? 'checked' : '' ?>
                        >
                        <span class="candidate-check"><?= $isChecked ? '✓' : '' ?></span>

                        <?php if ($photo !== ''): ?>
                          <img src="<?= htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') ?>" alt="Photo" class="candidate-photo mt-1 flex-shrink-0">
                        <?php else: ?>
                          <div class="candidate-photo mt-1 d-flex align-items-center justify-content-center flex-shrink-0">
                            <span class="text-muted small">No Photo</span>
                          </div>
                        <?php endif; ?>

                        <div>
                          <div class="candidate-name">
                            <?= htmlspecialchars($nominee['fullName'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                          </div>
                          <div class="candidate-manifesto mt-1">
                            <?php if (!empty($nominee['manifesto'])): ?>
                              <?= nl2br(htmlspecialchars($nominee['manifesto'], ENT_QUOTES, 'UTF-8')) ?>
                            <?php else: ?>
                              <span class="candidate-meta">No manifesto provided.</span>
                            <?php endif; ?>
                          </div>
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

          <div class="selection-hint mt-1">
            You must select <strong>zero or one</strong> Faculty Representative.
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($campusWideRaces)): ?>
      <div class="card race-card mb-4">
        <div class="card-body">
          <div class="race-header-bar">
            <div>
              <div class="race-header-title">Campus Wide Representative – Choose up to 4</div>
              <div class="race-subtitle">
                You may vote for up to <strong>four</strong> candidates from across the campus.
              </div>
            </div>
          </div>

          <?php foreach ($campusWideRaces as $race): ?>
            <?php
            $rid       = (int) $race['raceID'];
            $raceTitle = $race['raceTitle'] ?? 'Campus Wide Representative';
            $maxSel    = (int) $race['maxSelectable'];
            ?>
            <div class="mb-3">
              <div class="fw-semibold mb-2">
                <?= htmlspecialchars($raceTitle, ENT_QUOTES, 'UTF-8') ?>
              </div>

              <?php if (empty($race['nominees'])): ?>
                <p class="text-muted fst-italic mb-3">No nominees have been published for this race yet.</p>
              <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-1">
                  <?php foreach ($race['nominees'] as $nominee): ?>
                    <?php
                    $nid       = (int) $nominee['nomineeID'];
                    $isChecked = in_array($nid, $oldSelections[$rid] ?? [], true);
                    $photo     = trim($nominee['profilePhotoURL'] ?? '');
                    ?>
                    <div class="col">
                      <label class="candidate-card d-flex gap-3 align-items-start <?= $isChecked ? 'candidate-selected' : '' ?>">
                        <input
                          type="checkbox"
                          class="candidate-input campus-wide-input candidate-input-checkbox"
                          name="selections[<?= $rid ?>][]"
                          value="<?= $nid ?>"
                          data-race-id="<?= $rid ?>"
                          data-max-select="<?= $maxSel ?>"
                          data-race-title="<?= htmlspecialchars($raceTitle, ENT_QUOTES, 'UTF-8') ?>"
                          data-seat-type="CAMPUS_WIDE"
                          data-candidate-name="<?= htmlspecialchars($nominee['fullName'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                          <?= $isChecked ? 'checked' : '' ?>
                        >
                        <span class="candidate-check"><?= $isChecked ? '✓' : '' ?></span>

                        <?php if ($photo !== ''): ?>
                          <img src="<?= htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') ?>" alt="Photo" class="candidate-photo mt-1 flex-shrink-0">
                        <?php else: ?>
                          <div class="candidate-photo mt-1 d-flex align-items-center justify-content-center flex-shrink-0">
                            <span class="text-muted small">No Photo</span>
                          </div>
                        <?php endif; ?>

                        <div>
                          <div class="candidate-name">
                            <?= htmlspecialchars($nominee['fullName'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                          </div>
                          <div class="candidate-manifesto mt-1">
                            <?php if (!empty($nominee['manifesto'])): ?>
                              <?= nl2br(htmlspecialchars($nominee['manifesto'], ENT_QUOTES, 'UTF-8')) ?>
                            <?php else: ?>
                              <span class="candidate-meta">No manifesto provided.</span>
                            <?php endif; ?>
                          </div>
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

          <div class="selection-hint mt-1">
            You may choose fewer than the maximum allowed, but not more.
          </div>
        </div>
      </div>
    <?php endif; ?>

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

<!-- Preview modal -->
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
  (function () {
    // Highlight selected candidate cards + update custom tick
    document.querySelectorAll('.candidate-input').forEach(function (input) {
      input.addEventListener('change', function () {
        const label = this.closest('.candidate-card');

        if (this.type === 'radio') {
          document
            .querySelectorAll('input[name="' + this.name + '"]')
            .forEach(function (r) {
              const card = r.closest('.candidate-card');
              if (!card) return;
              card.classList.remove('candidate-selected');
              const check = card.querySelector('.candidate-check');
              if (check) check.textContent = '';
            });
        }

        if (label) {
          const check = label.querySelector('.candidate-check');
          if (this.checked) {
            label.classList.add('candidate-selected');
            if (check) check.textContent = '✓';
          } else if (this.type === 'checkbox') {
            label.classList.remove('candidate-selected');
            if (check) check.textContent = '';
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
          this.checked = false;
          const label = this.closest('.candidate-card');
          if (label) {
            label.classList.remove('candidate-selected');
            const check = label.querySelector('.candidate-check');
            if (check) check.textContent = '';
          }
          alert('You can select up to ' + maxSel + ' candidates for this race.');
        }
      });
    });

    // Preview modal
    const previewBtn   = document.getElementById('previewBtn');
    const previewModal = document.getElementById('previewModal');
    const previewBody  = document.getElementById('previewContent');
    const confirmBtn   = document.getElementById('confirmSubmitBtn');
    const form         = document.getElementById('castVoteForm');

    if (previewBtn && previewModal && previewBody && confirmBtn && form) {
      const bsModal = new bootstrap.Modal(previewModal);

      previewBtn.addEventListener('click', function () {
        const selected = document.querySelectorAll('.candidate-input:checked');
        if (!selected.length) {
          previewBody.innerHTML = '<p class="text-muted mb-0">You have not selected any candidates yet.</p>';
          bsModal.show();
          return;
        }

        // Group selections by seatType + raceTitle
        const groups = {};
        selected.forEach(function (input) {
          const seatType      = input.dataset.seatType || 'OTHER';
          const raceTitle     = input.dataset.raceTitle || 'Race';
          const candidateName = input.dataset.candidateName || 'Candidate';
          const key           = seatType + '|' + raceTitle;

          if (!groups[key]) {
            groups[key] = {
              seatType: seatType,
              raceTitle: raceTitle,
              candidates: []
            };
          }
          groups[key].candidates.push(candidateName);
        });

        function formatSeatType(seatType) {
          switch (seatType) {
            case 'FACULTY_REP':
              return 'Faculty Representative';
            case 'CAMPUS_WIDE':
              return 'Campus Wide Representative';
            default:
              return 'Other';
          }
        }

        function seatTypeClass(seatType) {
          if (seatType === 'FACULTY_REP') return 'faculty';
          if (seatType === 'CAMPUS_WIDE') return 'campus';
          return '';
        }

        let html = '';
        Object.keys(groups).forEach(function (key) {
          const g = groups[key];
          const count = g.candidates.length;
          const seatLabel = formatSeatType(g.seatType);
          const pillClass = seatTypeClass(g.seatType);

          html += '<div class="preview-race-card">';
          html += '  <div class="preview-race-header">';
          html += '    <div class="preview-race-title">' + g.raceTitle + '</div>';
          html += '    <span class="preview-seat-pill ' + pillClass + '">' + seatLabel + '</span>';
          html += '  </div>';
          html += '  <ul class="mb-0 ps-3">';
          g.candidates.forEach(function (c) {
            html += '    <li>' + c + '</li>';
          });
          html += '  </ul>';
          html += '  <div class="preview-race-footer">';
          html += '    You selected ' + count + ' candidate' + (count > 1 ? 's' : '') + ' for this race.';
          html += '  </div>';
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

<?php
if ($roleUpper === 'NOMINEE') {
  require_once __DIR__ . '/../NomineeView/nomineeFooter.php';
} elseif ($roleUpper === 'STUDENT') {
  require_once __DIR__ . '/../StudentView/studentFooter.php';
} else {
  require_once __DIR__ . '/../AdminView/adminFooter.php';
}
?>
