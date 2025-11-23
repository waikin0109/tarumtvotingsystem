<?php
$_title = 'Ballot Start';
// Currently we reuse the adminHeader for all roles.
// It already adapts links based on $_SESSION['role'].
require_once __DIR__ . '/../AdminView/adminHeader.php';
?>

<style>
  .ballot-layout {
    max-width: 1300px;
  }

  .ballot-card {
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
  }

  .badge-pill {
    border-radius: 999px;
    padding: 0.15rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .badge-session-type {
    background: #e3f5e1;
    color: #17612f;
  }

  .badge-status-open {
    background: #16a34a;
    color: #ffffff;
  }

  .ballot-section-title {
    font-weight: 600;
    font-size: 1.15rem;
  }

  .ballot-warning {
    background: #fef3c7;
    border-left: 4px solid #facc15;
  }

  /* Optional: give a bit more breathing room on very small screens */
  @media (max-width: 576px) {
    .ballot-layout {
      padding-left: 0.5rem;
      padding-right: 0.5rem;
    }
  }
</style>

<div class="container-fluid mt-4 mb-5">
  <h2 class="mb-2">Ballot Start</h2>
  <p class="text-muted mb-4">
    Please read the information below before you begin voting.
  </p>

  <!-- Session info card -->
  <div class="card ballot-card mb-4">
    <div class="card-body">
      <h4 class="card-title mb-3">
        <?= htmlspecialchars($electionTitle ?? '') ?>
      </h4>

      <div class="mb-3">
        <span class="badge badge-pill badge-session-type me-2">
          <?= htmlspecialchars($sessionTypeLabel ?? '') ?>
        </span>
        <span class="badge badge-pill badge-status-open">
          <?= htmlspecialchars($status ?? 'OPEN') ?>
        </span>
      </div>

      <p class="mb-1">
        <strong>Session Name:</strong>
        <?= htmlspecialchars($sessionName ?? '') ?>
      </p>
      <p class="mb-1">
        <strong>Starts:</strong>
        <?= htmlspecialchars($startFormatted ?? '') ?>
      </p>
      <p class="mb-0">
        <strong>Ends:</strong>
        <?= htmlspecialchars($endFormatted ?? '') ?>
      </p>
    </div>
  </div>

  <!-- You will vote for -->
  <div class="card ballot-card mb-4">
    <div class="card-body">
      <p class="ballot-section-title mb-3">You will vote for</p>

      <?php
      // Detect what types of races exist in this voting session
      $hasFacultyRep  = false;
      $hasCampusWide  = false;

      if (isset($races) && is_array($races)) {
          foreach ($races as $race) {
              $type = strtoupper($race['seatType'] ?? '');

              if ($type === 'FACULTY_REP') {
                  $hasFacultyRep = true;
              } elseif ($type === 'CAMPUS_WIDE') {
                  $hasCampusWide = true;
              }
          }
      }

      // Fallback: if no types detected, show both lines so page is not empty
      if (!$hasFacultyRep && !$hasCampusWide) {
          $hasFacultyRep = true;
          $hasCampusWide = true;
      }
      ?>

      <ul class="mb-2">
        <?php if ($hasFacultyRep): ?>
          <li>1 Faculty Representative for your faculty.</li>
        <?php endif; ?>

        <?php if ($hasCampusWide): ?>
          <li>Up to 4 Campus-wide Representatives.</li>
        <?php endif; ?>
      </ul>

      <p class="text-muted mb-0">
        The exact races and candidates will be shown on the next page.
      </p>
    </div>
  </div>

  <!-- Before you start -->
  <div class="card ballot-card mb-4">
    <div class="card-body">
      <p class="ballot-section-title mb-3">Before you start</p>
      <ul class="mb-3">
        <li>You can review your selections before submitting.</li>
        <li>Once you submit your ballot, you cannot change your vote.</li>
        <li>Make sure you have a stable internet connection.</li>
        <li>Estimated time to complete: about 3 to 5 minutes.</li>
      </ul>

      <div class="p-3 ballot-warning rounded-3">
        <strong>⚠ Important:</strong>
        <span class="ms-1">Do not refresh or close the browser while you are voting.</span>
      </div>
    </div>
  </div>

  <!-- Actions -->
  <div class="d-flex justify-content-between align-items-center mb-5">
    <a href="<?= htmlspecialchars($backUrl ?? '/vote-session/public') ?>" class="btn btn-outline-secondary">
      &larr; Back to Voting Sessions
    </a>

    <!-- Start ballot: now posts to /ballot/start -->
    <form method="post" action="/ballot/start" id="startBallotForm" class="mb-0">
      <input type="hidden" name="voteSessionID" value="<?= (int)$sessionId ?>">
      <button type="submit" class="btn btn-primary" id="startBallotBtn">
        Start Ballot &raquo;
      </button>
    </form>
  </div>

</div>

<script>
  // Confirm before starting ballot
  (function () {
    const btn  = document.getElementById('startBallotBtn');
    const form = document.getElementById('startBallotForm');
    if (!btn || !form) return;

    btn.addEventListener('click', function (e) {
      const ok = confirm(
        'You are about to start your ballot. ' +
        'Please do not refresh or close your browser while voting. Continue?'
      );
      if (!ok) {
        e.preventDefault(); // stop submitting form
      }
      // if ok === true, form submits normally to /ballot/start
    });
  })();
</script>


<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>
