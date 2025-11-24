<?php
$_title = 'Report List';
require_once __DIR__ . '/../AdminView/adminHeader.php';

// safety guards
if (!isset($reports) || !is_array($reports)) {
  $reports = [];
}

$roleUpper = strtoupper($_SESSION['role'] ?? '');
$isAdmin   = ($roleUpper === 'ADMIN');

function report_type_label(string $type): string
{
  $t = strtoupper($type);
  return match ($t) {
    'TURNOUT'           => 'Turnout Summary',
    'RESULTS_SUMMARY'   => 'Official Results (All Races)',
    'RACE_BREAKDOWN'    => 'Results by Faculty / Campus',
    'EARLY_VOTE_STATUS' => 'Early Vote Status',
    default             => $type,
  };
}

function report_type_badge(string $type): string
{
  $t = strtoupper($type);
  return match ($t) {
    'TURNOUT'           => 'bg-primary',
    'RESULTS_SUMMARY'   => 'bg-success',
    'RACE_BREAKDOWN'    => 'bg-info text-dark',
    'EARLY_VOTE_STATUS' => 'bg-secondary',
    default             => 'bg-light text-dark',
  };
}
?>

<div class="container-fluid mt-4 mb-5">
  <!-- Header row -->
  <div class="container-fluid mb-4">
    <div class="row w-100 align-items-center">
      <div class="col-sm-6">
        <h2 class="mb-0">Report History</h2>
      </div>
      <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
        <a href="/admin/reports/generator" class="btn btn-outline-primary">
          Open Report Generator
        </a>
      </div>
    </div>
  </div>

  <!-- Table -->
  <div class="container-fluid mb-5">
    <div class="bg-light">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th scope="col-sm-1">No.</th>
              <th scope="col-sm-3">Report Name</th>
              <th scope="col-sm-3">Election Event</th>
              <th scope="col-sm-2">Report Type</th>
              <th scope="col-sm-1">Output Format</th>
              <th scope="col-sm-2">Generated At</th>
              <th scope="col-sm-1">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($reports)): ?>
            <tr>
              <td colspan="7" class="text-center text-muted">
                No reports have been generated yet.
              </td>
            </tr>
          <?php else:
            $no = 1;
            foreach ($reports as $r):
              $id        = (int)($r['reportID'] ?? 0);
              $name      = $r['reportName'] ?? '';
              $election  = $r['electionTitle'] ?? ('Election #' . ($r['electionID'] ?? '-'));
              $type      = $r['reportType'] ?? '';
              $url       = $r['reportUrl'] ?? '';
              $generated = $r['reportGeneratedAt'] ?? '';
              $generatedFmt = $generated ? date('Y.m.d H:i', strtotime($generated)) : '';

              // --- derive view URL, download URL, and format from stored reportUrl ---
              $viewUrl      = $url;
              $downloadUrl  = $url;
              $formatLabel  = 'PDF';

              if (!empty($url)) {
                $parsed = parse_url($url);
                $path   = $parsed['path'] ?? '';
                $query  = $parsed['query'] ?? '';

                $params = [];
                if ($query) {
                  parse_str($query, $params);
                }

                // determine format
                if (!empty($params['format'])) {
                  $formatLabel = strtoupper($params['format']);
                }

                // view URL = without download flag
                $viewParams = $params;
                unset($viewParams['download']);
                $viewQuery = http_build_query($viewParams);
                $viewUrl   = $path . ($viewQuery ? ('?' . $viewQuery) : '');

                // download URL = ensure download=1
                $downloadParams = $params;
                $downloadParams['download'] = 1;
                $downloadQuery = http_build_query($downloadParams);
                $downloadUrl   = $path . ($downloadQuery ? ('?' . $downloadQuery) : '');
              }
            ?>
            <tr>
              <td><?= $no++ ?></td>

              <td>
                <?php if (!empty($viewUrl)): ?>
                  <a href="<?= htmlspecialchars($viewUrl) ?>" target="_blank" rel="noopener">
                    <?= htmlspecialchars($name) ?>
                  </a>
                <?php else: ?>
                  <?= htmlspecialchars($name) ?>
                <?php endif; ?>
              </td>

              <td><?= htmlspecialchars($election) ?></td>

              <td>
                <?php if ($type): ?>
                  <span class="badge <?= report_type_badge($type) ?>">
                    <?= htmlspecialchars(report_type_label($type)) ?>
                  </span>
                <?php endif; ?>
              </td>

              <td><?= htmlspecialchars($formatLabel) ?></td>

              <td><?= htmlspecialchars($generatedFmt) ?></td>

              <td class="text-nowrap">
                <div class="d-flex flex-wrap gap-2 justify-content-start">
                  <?php if ($downloadUrl): ?>
                    <a class="btn btn-sm btn-outline-primary"
                       href="<?= htmlspecialchars($downloadUrl) ?>"
                       target="_blank" rel="noopener">
                      Download
                    </a>
                  <?php else: ?>
                    <button class="btn btn-sm btn-outline-secondary" disabled>
                      No File
                    </button>
                  <?php endif; ?>

                  <?php if ($isAdmin): ?>
                    <form class="d-inline" method="post"
                          action="/admin/reports/delete"
                          onsubmit="return confirm('Delete this report record? This will not affect election results.');">
                      <input type="hidden" name="reportID" value="<?= $id ?>">
                      <button class="btn btn-sm btn-outline-danger">
                        Delete
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>
