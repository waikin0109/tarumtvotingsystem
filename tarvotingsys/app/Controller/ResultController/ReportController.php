<?php

namespace Controller\ResultController;

use Model\ResultModel\ReportModel;
use FileHelper;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf as PdfWriter;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class ReportController
{
    private ReportModel $reportModel;
    private FileHelper $fileHelper;

    public function __construct()
    {
        $this->reportModel = new ReportModel();
        $this->fileHelper = new FileHelper('report'); // View/ReportView
    }

    private function requireAdmin(): void
    {
        $role = strtoupper($_SESSION['role'] ?? '');
        if ($role !== 'ADMIN') {
            set_flash('fail', 'You are not allowed to access the report module.');
            header('Location: /login');
            exit;
        }
    }

    // GET /admin/reports/generator
    public function showGenerator(): void
    {
        $this->requireAdmin();

        // Only COMPLETED elections
        $elections = $this->reportModel->getCompletedElections();
        // All CLOSED sessions that belong to COMPLETED elections
        $voteSessions = $this->reportModel->getClosedVoteSessionsForCompletedElections();
        // All races for COMPLETED elections (each knows its electionID + voteSessionID)
        $races = $this->reportModel->getAllRaces();

        $old = [];
        $errors = [];

        $view = $this->fileHelper->getFilePath('ReportGenerator');
        if (!$view) {
            echo "ReportGenerator view not found in FileHelper.";
            return;
        }
        require $view;
    }

    /* ==================== POST: /admin/reports/generate ==================== */

    public function generate(): void
    {
        $this->requireAdmin();

        $electionID = isset($_POST['electionID']) ? (int) $_POST['electionID'] : 0;
        $voteSessionID = isset($_POST['voteSessionID']) ? (int) $_POST['voteSessionID'] : 0;
        $raceID = isset($_POST['raceID']) ? (int) $_POST['raceID'] : 0;
        $reportType = trim($_POST['reportType'] ?? '');
        $outputFormat = strtoupper(trim($_POST['outputFormat'] ?? 'PDF'));

        $allowedTypes = [
            'overall_turnout',
            'official_results_all',
            'results_by_faculty',
            'early_vote_status',
        ];
        $allowedFormats = ['PDF', 'CSV'];

        $errors = [];

        // 1) Basic validation
        if ($electionID <= 0) {
            $errors[] = 'Please select a valid Election Event.';
        }
        if (!in_array($reportType, $allowedTypes, true)) {
            $errors[] = 'Please select a valid report type.';
        }
        if (!in_array($outputFormat, $allowedFormats, true)) {
            $errors[] = 'Unknown output format selected.';
        }

        // 2) Election must exist
        $election = null;
        if ($electionID > 0) {
            $election = $this->reportModel->getElectionById($electionID);
            if (!$election) {
                $errors[] = 'Selected Election Event does not exist.';
            }
        }

        // 3) Session / Race consistency
        if ($voteSessionID > 0 && $electionID > 0) {
            if (!$this->reportModel->isVoteSessionInElection($voteSessionID, $electionID)) {
                $errors[] = 'Selected Vote Session does not belong to the selected Election Event.';
            }
        }

        if (in_array($reportType, ['results_by_faculty'], true)) {
            if ($raceID <= 0) {
                $errors[] = 'Please select a race for the results by faculty report.';
            } elseif (
                !$this->reportModel->isRaceInElectionAndSession(
                    $raceID,
                    $electionID,
                    $voteSessionID > 0 ? $voteSessionID : null
                )
            ) {
                $errors[] = 'Selected Race does not match the Election / Vote Session.';
            }
        }

        $old = [
            'electionID' => $electionID,
            'voteSessionID' => $voteSessionID,
            'raceID' => $raceID,
            'reportType' => $reportType,
            'outputFormat' => $outputFormat,
        ];

        if (!empty($errors)) {
            // reload generator with errors & old values
            $elections = $this->reportModel->getCompletedElections();
            $voteSessions = $this->reportModel->getClosedVoteSessionsForCompletedElections();
            $races = $this->reportModel->getAllRaces();

            $view = $this->fileHelper->getFilePath('ReportGenerator');
            if (!$view) {
                echo "ReportGenerator view not found in FileHelper.";
                return;
            }
            require $view;
            return;
        }

        // Map generator type -> `report.reportType` enum
        $enumType = $this->reportModel->mapGeneratorTypeToEnum($reportType);
        $isPdf = ($outputFormat === 'PDF');
        $baseFormat = $outputFormat; // always one of PDF/CSV/XLSX here

        /* =================== BRANCH BY REPORT TYPE =================== */

        // -------- 1) Overall turnout ----------
        if ($reportType === 'overall_turnout') {
            $viewQuery = [
                'electionID' => $electionID,
                'format' => $baseFormat,
            ];
            if ($voteSessionID > 0) {
                $viewQuery['voteSessionID'] = $voteSessionID;
            }

            // URL saved into report history (ALWAYS on-screen URL, no download flag)
            $viewUrl = '/admin/reports/overall-turnout?' . http_build_query($viewQuery);
            $name = 'Overall Turnout – ' . $election['title'];

            $this->reportModel->insertReportRecord($electionID, $name, $enumType, $viewUrl);

            // Redirect behaviour: PDF -> view page, CSV/XLSX -> direct download
            if ($isPdf) {
                $redirectUrl = $viewUrl;
            } else {
                $downloadQuery = $viewQuery;
                $downloadQuery['download'] = 1;
                $redirectUrl = '/admin/reports/overall-turnout?' . http_build_query($downloadQuery);
            }

            header('Location: ' . $redirectUrl);
            exit;
        }

        // -------- 2) Official results (all races) ----------
        if ($reportType === 'official_results_all') {
            $viewQuery = [
                'electionID' => $electionID,
                'format' => $baseFormat,
            ];
            if ($voteSessionID > 0) {
                $viewQuery['voteSessionID'] = $voteSessionID;
            }

            $viewUrl = '/admin/reports/official-results?' . http_build_query($viewQuery);
            $name = 'Official Results by Race – ' . $election['title'];

            $this->reportModel->insertReportRecord($electionID, $name, $enumType, $viewUrl);

            if ($isPdf) {
                $redirectUrl = $viewUrl;
            } else {
                $downloadQuery = $viewQuery;
                $downloadQuery['download'] = 1;
                $redirectUrl = '/admin/reports/official-results?' . http_build_query($downloadQuery);
            }

            header('Location: ' . $redirectUrl);
            exit;
        }

        // -------- 3) Results by faculty / campus ----------
        if ($reportType === 'results_by_faculty') {
            $viewQuery = [
                'electionID' => $electionID,
                'raceID' => $raceID,
                'format' => $baseFormat,
            ];
            if ($voteSessionID > 0) {
                $viewQuery['voteSessionID'] = $voteSessionID;
            }

            $viewUrl = '/admin/reports/results-by-faculty?' . http_build_query($viewQuery);
            $name = 'Results by Faculty – ' . $election['title'] . " (Race #{$raceID})";

            $this->reportModel->insertReportRecord($electionID, $name, $enumType, $viewUrl);

            if ($isPdf) {
                $redirectUrl = $viewUrl;
            } else {
                $downloadQuery = $viewQuery;
                $downloadQuery['download'] = 1;
                $redirectUrl = '/admin/reports/results-by-faculty?' . http_build_query($downloadQuery);
            }

            header('Location: ' . $redirectUrl);
            exit;
        }

        // -------- 4) Early vote status ----------
        if ($reportType === 'early_vote_status') {
            $viewQuery = [
                'electionID' => $electionID,
                'format' => $baseFormat,
            ];

            $viewUrl = '/admin/reports/early-vote-status?' . http_build_query($viewQuery);
            $name = 'Early Vote Status – ' . $election['title'];

            $this->reportModel->insertReportRecord($electionID, $name, $enumType, $viewUrl);

            if ($isPdf) {
                $redirectUrl = $viewUrl;
            } else {
                $downloadQuery = $viewQuery;
                $downloadQuery['download'] = 1;
                $redirectUrl = '/admin/reports/early-vote-status?' . http_build_query($downloadQuery);
            }

            header('Location: ' . $redirectUrl);
            exit;
        }

        set_flash('fail', 'Unsupported report type.');
        header('Location: /admin/reports/generator');
        exit;
    }

    /* =================== ON-SCREEN REPORT PAGES =================== */
    public function overallTurnoutPage(): void
    {
        $this->requireAdmin();

        $electionID = isset($_GET['electionID']) ? (int) $_GET['electionID'] : 0;
        $voteSessionID = isset($_GET['voteSessionID']) ? (int) $_GET['voteSessionID'] : 0;
        $format = strtoupper(trim($_GET['format'] ?? ''));
        $download = isset($_GET['download']) && $_GET['download'] == '1';

        if ($electionID <= 0) {
            set_flash('fail', 'Invalid election selected.');
            header('Location: /admin/reports/generator');
            exit;
        }

        $summary = $this->reportModel->getOverallTurnoutSummary(
            $electionID,
            $voteSessionID ?: null
        );
        if (!$summary) {
            set_flash('fail', 'Unable to build turnout summary for the selected election.');
            header('Location: /admin/reports/generator');
            exit;
        }

        // extra detail: by faculty + timeline
        $turnoutByFaculty = $this->reportModel->getTurnoutByFaculty(
            $electionID,
            $voteSessionID ?: null
        );
        $turnoutTimeline = $this->reportModel->getTurnoutTimeline(
            $electionID,
            $voteSessionID ?: null
        );

        // if generator requested a file, export instead of HTML
        if ($download && in_array($format, ['CSV', 'PDF'], true)) {
            $rows = [
                [
                    'Election Title' => $summary['electionTitle'],
                    'Session' => $voteSessionID ? ('Session #' . $voteSessionID) : 'ALL SESSIONS',
                    'Eligible Voters' => $summary['eligibleTotal'],
                    'Ballots Cast' => $summary['ballotsCast'],
                    'Turnout %' => $summary['turnoutPercent'],
                ]
            ];
            $this->exportTabular('overall_turnout', $rows, $format);
            return;
        }

        // otherwise show normal HTML page
        $view = $this->fileHelper->getFilePath('OverallTurnoutSummary');
        if (!$view) {
            echo "<pre>";
            print_r($summary);
            print_r($turnoutByFaculty);
            print_r($turnoutTimeline);
            echo "</pre>";
            return;
        }

        // make vars visible in view
        $summaryData = $summary;
        $byFaculty = $turnoutByFaculty;
        $timeline = $turnoutTimeline;
        $selectedSessionID = $voteSessionID ?: null;

        // buttons: Back + Download
        $currentFormat = in_array($format, ['CSV', 'PDF'], true) ? $format : 'PDF';
        $downloadParams = [
            'electionID' => $electionID,
            'format' => $currentFormat,
            'download' => 1,
        ];
        if ($voteSessionID > 0) {
            $downloadParams['voteSessionID'] = $voteSessionID;
        }
        $downloadUrl = '/admin/reports/overall-turnout?' . http_build_query($downloadParams);
        $backUrl = '/admin/reports/list';

        require $view;
    }

    // GET /admin/reports/official-results
    public function officialResultsPage(): void
    {
        $this->requireAdmin();

        $electionID = isset($_GET['electionID']) ? (int) $_GET['electionID'] : 0;
        $voteSessionID = isset($_GET['voteSessionID']) ? (int) $_GET['voteSessionID'] : 0;
        $format = strtoupper(trim($_GET['format'] ?? ''));
        $download = isset($_GET['download']) && $_GET['download'] == '1';

        if ($electionID <= 0) {
            set_flash('fail', 'Invalid election selected.');
            header('Location: /admin/reports/generator');
            exit;
        }

        $election = $this->reportModel->getElectionById($electionID);
        if (!$election) {
            set_flash('fail', 'Election not found.');
            header('Location: /admin/reports/generator');
            exit;
        }

        $racesResults = $this->reportModel->getOfficialResultsAll(
            $electionID,
            $voteSessionID ?: null
        );

        $turnoutSummary = $this->reportModel->getOverallTurnoutSummary(
            $electionID,
            $voteSessionID ?: null
        );

        if ($download && in_array($format, ['CSV', 'PDF'], true)) {
            $rows = [];
            foreach ($racesResults as $race) {
                foreach ($race['candidates'] as $cand) {
                    $rows[] = [
                        'Race' => $race['raceTitle'],
                        'Seat Type' => $race['seatType'],
                        'Seats' => $race['seatCount'],
                        'Session ID' => $race['voteSessionID'],
                        'Session Name' => $race['voteSessionName'],
                        'Session Type' => $race['voteSessionType'],
                        'Candidate' => $cand['fullName'],
                        'Faculty Code' => $cand['facultyCode'],
                        'Faculty Name' => $cand['facultyName'],
                        'Votes' => $cand['votes'],
                        'Winner' => $cand['isWinner'] ? 'YES' : 'NO',
                    ];
                }
            }
            $this->exportTabular('official_results_all_races', $rows, $format);
            return;
        }

        $view = $this->fileHelper->getFilePath('OfficialResultsAll');
        if (!$view) {
            echo "<pre>";
            print_r($election);
            print_r($racesResults);
            print_r($turnoutSummary);
            echo "</pre>";
            return;
        }

        // Back + Download buttons
        $currentFormat = in_array($format, ['CSV', 'PDF'], true) ? $format : 'PDF';
        $downloadParams = [
            'electionID' => $electionID,
            'format' => $currentFormat,
            'download' => 1,
        ];
        if ($voteSessionID > 0) {
            $downloadParams['voteSessionID'] = $voteSessionID;
        }
        $downloadUrl = '/admin/reports/official-results?' . http_build_query($downloadParams);
        $backUrl = '/admin/reports/list';

        require $view;
    }

    // GET /admin/reports/results-by-faculty
    public function resultsByFacultyPage(): void
    {
        $this->requireAdmin();

        $electionID = isset($_GET['electionID']) ? (int) $_GET['electionID'] : 0;
        $raceID = isset($_GET['raceID']) ? (int) $_GET['raceID'] : 0;
        $format = strtoupper(trim($_GET['format'] ?? ''));
        $download = isset($_GET['download']) && $_GET['download'] == '1';

        if ($electionID <= 0 || $raceID <= 0) {
            set_flash('fail', 'Please provide both election and race.');
            header('Location: /admin/reports/generator');
            exit;
        }

        $election = $this->reportModel->getElectionById($electionID);
        if (!$election) {
            set_flash('fail', 'Election not found.');
            header('Location: /admin/reports/generator');
            exit;
        }

        $rows = $this->reportModel->getResultsByFaculty($electionID, $raceID);

        if ($download && in_array($format, ['CSV', 'PDF'], true)) {
            $this->exportTabular('results_by_faculty', $rows, $format);
            return;
        }

        $view = $this->fileHelper->getFilePath('ResultsByFaculty');
        if (!$view) {
            echo "<pre>";
            print_r($election);
            print_r($rows);
            echo "</pre>";
            return;
        }

        // Back + Download buttons
        $currentFormat = in_array($format, ['CSV', 'PDF'], true) ? $format : 'PDF';
        $downloadParams = [
            'electionID' => $electionID,
            'raceID' => $raceID,
            'format' => $currentFormat,
            'download' => 1,
        ];
        $downloadUrl = '/admin/reports/results-by-faculty?' . http_build_query($downloadParams);
        $backUrl = '/admin/reports/list';

        require $view;
    }

    // GET /admin/reports/early-vote-status
    public function earlyVoteStatusPage(): void
    {
        $this->requireAdmin();

        $electionID = isset($_GET['electionID']) ? (int) $_GET['electionID'] : 0;
        $format = strtoupper(trim($_GET['format'] ?? ''));
        $download = isset($_GET['download']) && $_GET['download'] == '1';

        if ($electionID <= 0) {
            set_flash('fail', 'Invalid election selected.');
            header('Location: /admin/reports/generator');
            exit;
        }

        $election = $this->reportModel->getElectionById($electionID);
        if (!$election) {
            set_flash('fail', 'Election not found.');
            header('Location: /admin/reports/generator');
            exit;
        }

        // reuse turnout by faculty (with earlyCast & mainCast)
        $byFaculty = $this->reportModel->getTurnoutByFaculty($electionID, null);

        $totalEligible = 0;
        $totalEarly = 0;
        $totalMain = 0;

        foreach ($byFaculty as $row) {
            $totalEligible += (int) $row['eligible'];
            $totalEarly += (int) $row['earlyCast'];
            $totalMain += (int) $row['mainCast'];
        }

        $overallEarlyPercent = $totalEligible > 0
            ? round(($totalEarly / $totalEligible) * 100, 2)
            : 0.0;

        $overallMainPercent = $totalEligible > 0
            ? round(($totalMain / $totalEligible) * 100, 2)
            : 0.0;

        if ($download && in_array($format, ['CSV', 'PDF'], true)) {
            $rows = [];
            foreach ($byFaculty as $row) {
                $eligible = (int) $row['eligible'];
                $earlyCast = (int) $row['earlyCast'];
                $mainCast = (int) $row['mainCast'];
                $totalCast = $earlyCast + $mainCast;

                $earlyPercent = $eligible > 0 ? round(($earlyCast / $eligible) * 100, 2) : 0.0;
                $mainPercent = $eligible > 0 ? round(($mainCast / $eligible) * 100, 2) : 0.0;
                $totalPercent = $eligible > 0 ? round(($totalCast / $eligible) * 100, 2) : 0.0;

                $rows[] = [
                    'Faculty Code' => $row['facultyCode'],
                    'Faculty Name' => $row['facultyName'],
                    'Eligible' => $eligible,
                    'Early Cast' => $earlyCast,
                    'Early %' => $earlyPercent,
                    'Main Cast' => $mainCast,
                    'Main %' => $mainPercent,
                    'Total Cast' => $totalCast,
                    'Total Turnout %' => $totalPercent,
                ];
            }

            $this->exportTabular('early_vote_status', $rows, $format);
            return;
        }

        $summary = [
            'totalEligible' => $totalEligible,
            'totalEarly' => $totalEarly,
            'overallEarlyPercent' => $overallEarlyPercent,
            'totalMain' => $totalMain,
            'overallMainPercent' => $overallMainPercent,
        ];

        $view = $this->fileHelper->getFilePath('EarlyVoteStatus');
        if (!$view) {
            echo "<pre>";
            print_r($election);
            print_r($summary);
            print_r($byFaculty);
            echo "</pre>";
            return;
        }

        // Back + Download buttons
        $currentFormat = in_array($format, ['CSV', 'PDF'], true) ? $format : 'PDF';
        $downloadParams = [
            'electionID' => $electionID,
            'format' => $currentFormat,
            'download' => 1,
        ];
        $downloadUrl = '/admin/reports/early-vote-status?' . http_build_query($downloadParams);
        $backUrl = '/admin/reports/list';

        require $view;
    }

    /* ========================= EXPORT HELPERS ========================= */
    private function exportTabular(string $baseFilename, array $rows, string $format): void
    {
        $format = strtoupper($format);
        if (!in_array($format, ['CSV', 'PDF'], true)) {
            $format = 'CSV'; // safe default
        }

        // If no data, output an empty row with a message
        if (empty($rows)) {
            $rows = [['message' => 'No data available for this report.']];
        }

        $headers = array_keys($rows[0]);

        // --------- common: build storage path in app/public/reports ----------
        // __DIR__ = app/Controller/ResultController
        $reportsDir = realpath(__DIR__ . '/../../public');
        if ($reportsDir === false) {
            // fallback: try to create the directory
            $reportsDir = __DIR__ . '/../../public';
        }
        $reportsDir .= '/reports';

        if (!is_dir($reportsDir)) {
            mkdir($reportsDir, 0777, true);
        }

        $timestamp = date('Ymd_His');
        $fileName = $baseFilename . '_' . $timestamp . '.' . strtolower($format);
        $filePath = $reportsDir . DIRECTORY_SEPARATOR . $fileName;

        // ---------- CSV: write to disk, then stream ----------
        if ($format === 'CSV') {
            // 1) write CSV to file
            $fp = fopen($filePath, 'w');
            if ($fp === false) {
                die('Unable to create CSV file at ' . $filePath);
            }
            fputcsv($fp, $headers);
            foreach ($rows as $row) {
                fputcsv($fp, array_values($row));
            }
            fclose($fp);

            // 2) stream to browser
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            readfile($filePath);
            exit;
        }

        // ---------- PDF: use Spreadsheet + mPDF, save to disk, then stream ----------
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Basic page setup for nicer PDF
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4);
        $spreadsheet->getDefaultStyle()->getFont()
            ->setName('Arial')
            ->setSize(10);

        // Header row
        $colIndex = 1;
        foreach ($headers as $head) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex); // A, B, C...
            $sheet->setCellValue($colLetter . '1', $head);
            $colIndex++;
        }

        // Make header bold + a bit taller
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true);
        $sheet->getRowDimension(1)->setRowHeight(18);

        // Data rows
        $rowIndex = 2;
        foreach ($rows as $row) {
            $colIndex = 1;
            foreach ($headers as $head) {
                $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                $value = $row[$head] ?? '';
                $sheet->setCellValue($colLetter . $rowIndex, $value);
                $colIndex++;
            }
            $rowIndex++;
        }

        // Auto-size all columns for better readability
        for ($i = 1; $i <= count($headers); $i++) {
            $colLetter = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Optional: wrap text
        $sheet->getStyle(
            'A1:' . $lastCol . ($rowIndex - 1)
        )->getAlignment()->setWrapText(true);

        // 1) save PDF to disk
        $writer = new PdfWriter($spreadsheet);
        $writer->save($filePath);

        // 2) stream the saved PDF to browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        readfile($filePath);
        exit;
    }

    /**
     * Show history of generated reports.
     * Route example: GET /admin/reports/list
     */
    public function reportListPage(): void
    {
        $role = strtoupper($_SESSION['role'] ?? '');
        if ($role !== 'ADMIN') {
            set_flash('fail', 'You are not allowed to view the report history.');
            header('Location: /login');
            exit;
        }

        $reports = $this->reportModel->getAllReports();

        $view = $this->fileHelper->getFilePath('ReportList');
        if (!$view) {
            echo 'ReportList view not found in FileHelper.';
            return;
        }

        // make $reports visible in the view
        require $view;
    }

    /**
     * Delete a report record.
     * Route example: POST /admin/reports/delete
     */
    public function deleteReport(): void
    {
        $role = strtoupper($_SESSION['role'] ?? '');
        if ($role !== 'ADMIN') {
            set_flash('fail', 'You are not allowed to perform this action.');
            header('Location: /login');
            exit;
        }

        $id = isset($_POST['reportID']) ? (int) $_POST['reportID'] : 0;
        if ($id <= 0) {
            set_flash('fail', 'Invalid report selected.');
            header('Location: /admin/reports/list');
            exit;
        }

        $ok = $this->reportModel->deleteReportById($id);

        if ($ok) {
            set_flash('success', 'Report record deleted successfully.');
        } else {
            set_flash('fail', 'Failed to delete report record. Please try again.');
        }

        header('Location: /admin/reports/list');
        exit;
    }
}