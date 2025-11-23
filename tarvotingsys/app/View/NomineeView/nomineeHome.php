<?php
$_title    = 'Nominee Home';
$roleUpper = strtoupper($_SESSION['role'] ?? 'NOMINEE');

require_once __DIR__ . '/../NomineeView/nomineeHeader.php';

// Safe defaults
$nomineeApplicationStats = $nomineeApplicationStats ?? [
    'total'     => 0,
    'pending'   => 0,
    'accepted'  => 0,
    'rejected'  => 0,
    'published' => 0,
];

$campaignMaterialStats = $campaignMaterialStats ?? [
    'total'   => 0,
    'pending' => 0,
    'approved'=> 0,
    'rejected'=> 0,
];

$electionStats = $electionStats ?? [
    'as_nominee' => 0,
];

$fullName = $_SESSION['fullName'] ?? 'Nominee';
?>

<div class="container-fluid mt-4 mb-5">

  <!-- Welcome header -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
    <div>
      <h2 class="mb-1">Welcome, <?= htmlspecialchars($fullName) ?></h2>
      <p class="text-muted small mb-0">
        This is your nominee dashboard. View your election status, applications,
        campaign materials, and schedules in one place.
      </p>
    </div>
  </div>

  <!-- Stats cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Elections where you are a nominee</div>
          <div class="h3 mb-2"><?= (int)$electionStats['as_nominee'] ?></div>
          <a href="/nominee/nominee-final-list" class="small text-decoration-none">
            View nominee lists &raquo;
          </a>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">My Nominee Applications</div>
          <div class="h3 mb-2"><?= (int)$nomineeApplicationStats['total'] ?></div>
          <div class="small text-muted">
            Pending: <strong><?= (int)$nomineeApplicationStats['pending'] ?></strong><br>
            Accepted: <strong><?= (int)$nomineeApplicationStats['accepted'] ?></strong><br>
            Rejected: <strong><?= (int)$nomineeApplicationStats['rejected'] ?></strong><br>
            Published: <strong><?= (int)$nomineeApplicationStats['published'] ?></strong>
          </div>
          <a href="/nominee/election-registration-form" class="small text-decoration-none d-block mt-1">
            Go to election registration &raquo;
          </a>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">My Campaign Materials</div>
          <div class="h3 mb-2"><?= (int)$campaignMaterialStats['total'] ?></div>
          <div class="small text-muted">
            Pending: <strong><?= (int)$campaignMaterialStats['pending'] ?></strong><br>
            Approved: <strong><?= (int)$campaignMaterialStats['approved'] ?></strong><br>
            Rejected: <strong><?= (int)$campaignMaterialStats['rejected'] ?></strong>
          </div>
          <a href="/nominee/campaign-material" class="small text-decoration-none d-block mt-1">
            Manage campaign materials &raquo;
          </a>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Campaign Timetable</div>
          <div class="h3 mb-2">
            <i class="bi bi-calendar3"></i>
          </div>
          <div class="small text-muted">
            View campaign and debate events scheduled for your elections.
          </div>
          <a href="/nominee/schedule-location" class="small text-decoration-none d-block mt-1">
            View schedule &raquo;
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick links -->
  <div class="row g-3">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h5 class="card-title mb-2">Election Registration</h5>
          <p class="card-text small text-muted mb-3">
            Browse available registration forms and apply to be a nominee in upcoming elections.
          </p>
          <a href="/nominee/election-registration-form" class="btn btn-sm btn-primary">
            Go to Registration
          </a>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h5 class="card-title mb-2">Rules &amp; Regulations</h5>
          <p class="card-text small text-muted mb-3">
            Review the election rules and regulations for each event.
          </p>
          <a href="/nominee/rule" class="btn btn-sm btn-outline-primary">
            View Rules
          </a>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h5 class="card-title mb-2">Campaign Materials</h5>
          <p class="card-text small text-muted mb-3">
            Submit and track approval for your posters, banners, and online campaign content.
          </p>
          <a href="/nominee/campaign-material" class="btn btn-sm btn-outline-primary">
            Manage Materials
          </a>
        </div>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../NomineeView/nomineeFooter.php'; ?>
