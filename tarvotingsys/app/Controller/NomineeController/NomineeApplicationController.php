<?php

namespace Controller\NomineeController;

use Model\NomineeModel\NomineeApplicationModel;
use Model\NomineeHandlingModel\RegistrationFormModel;
use Model\NomineeHandlingModel\AcademicDocumentModel;
use Model\StudentModel\StudentModel;
use Model\VotingModel\ElectionEventModel;
use FileHelper;

class NomineeApplicationController
{
    private NomineeApplicationModel $nomineeApplicationModel;
    private RegistrationFormModel $registrationFormModel;
    private AcademicDocumentModel $academicDocumentModel;
    private StudentModel $studentModel;
    private ElectionEventModel $electionEventModel;
    private FileHelper $fileHelper;

    public function __construct()
    {
        $this->nomineeApplicationModel = new NomineeApplicationModel();
        $this->registrationFormModel   = new RegistrationFormModel();
        $this->academicDocumentModel   = new AcademicDocumentModel();
        $this->studentModel            = new StudentModel();
        $this->electionEventModel      = new ElectionEventModel();
        $this->fileHelper              = new FileHelper('nominee_application'); // keep NomineeView location
    }

    public function listNomineeApplications()
    {
        $nomineeApplications = $this->nomineeApplicationModel->getAllNomineeApplications();
        $filePath = $this->fileHelper->getFilePath('NomineeApplicationList');

        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }

    /** Map attribute name -> input type for dynamic fields */
    private function typeFor(string $attrName): string {
        $n = strtolower($attrName);
        if ($n === 'cgpa') return 'number';
        if ($n === 'behaviorreport') return 'checkbox';
        if (in_array($n, ['achievements','reason'], true)) return 'textarea';
        return 'text';
    }

    // -------------------- Show create page (NomineeView) --------------------
    public function createNomineeApplication()
    {
        $forms = $this->registrationFormModel->listOpenForms();

        $errors = [];
        $fieldErrors = [];
        $old = [];

        // This is the only variable you should use in this method
        $selectedForm = (int)($_GET['registrationFormID'] ?? 0);

        $renderAttrs = [];
        if ($selectedForm > 0) {
            $attrNames = $this->registrationFormModel->getAttributesByFormId($selectedForm);
            $subCols   = $this->registrationFormModel->getSubmissionColumns();

            foreach ($attrNames as $name) {
                $code = strtolower($name);
                if (isset($subCols[$code])) {
                    $renderAttrs[] = [
                        'code'  => $code,
                        'label' => ucwords(str_replace(['_','-'],' ', $name)),
                        'type'  => $this->typeFor($code),
                    ];
                }
            }
        }

        // (Optional banner data for the view; safe to keep or remove)
        $registrationOpen = $selectedForm > 0 ? $this->registrationFormModel->isRegistrationOpen($selectedForm) : true;
        $regWindow        = $selectedForm > 0 ? $this->registrationFormModel->getRegWindowByFormId($selectedForm) : null;

        $students = $this->studentModel->getAllStudents();

        $filePath = $this->fileHelper->getFilePath('CreateNomineeApplication');
        if ($filePath && file_exists($filePath)) {
            include $filePath; // exposes $forms, $selectedForm, $renderAttrs, $students, $registrationOpen, $regWindow
        } else {
            echo "View file not found.";
        }
    }


    // -------------------- Handle submit (NomineeView) --------------------
    public function storeNomineeApplication()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->createNomineeApplication();
            return;
        }

        $registrationFormID = (int)($_POST['registrationFormID'] ?? 0);
        $studentID          = (int)($_POST['studentID'] ?? 0);

        $errors = [];
        $fieldErrors = [];

        if ($registrationFormID <= 0) $errors[] = 'Please select a registration form.';
        if ($studentID <= 0)          $fieldErrors['studentID'][] = 'Please enter a valid Student ID.';

        $form = $registrationFormID ? $this->registrationFormModel->getRegistrationFormById($registrationFormID) : null;
        if (!$form) $errors[] = 'Selected registration form not found.';
        $electionID = $form ? (int)$form['electionID'] : 0;

        // ➕ NEW: student cannot apply to the same election event twice
        if ($studentID > 0 && $electionID > 0) {
            if ($this->nomineeApplicationModel->hasAppliedForEvent($studentID, $electionID)) {
                $fieldErrors['studentID'][] = 'This student has already applied for this election event.';
            }
        }

        $attrNames = $registrationFormID ? $this->registrationFormModel->getAttributesByFormId($registrationFormID) : [];
        $subCols   = $this->registrationFormModel->getSubmissionColumns();

        // 1) Collect & normalize posted field values
        $clean = [];
        foreach ($attrNames as $name) {
            $code = strtolower($name);
            if (!isset($subCols[$code])) continue; // only accept known columns

            $type = $this->typeFor($code);
            $raw  = $_POST['fields'][$code] ?? null;

            if ($type === 'checkbox') {
                $clean[$code] = isset($raw) ? 1 : 0;
            } else {
                $val = is_string($raw) ? trim($raw) : '';
                if ($type === 'number' && $code === 'cgpa') {
                    $clean[$code] = ($val !== '' && is_numeric($val)) ? (float)$val : $val;
                } else {
                    $clean[$code] = $val;
                }
            }
        }

        // 2) Required validations for inputs & files
        $uploads = $_FILES['uploads'] ?? [];

        if (empty($attrNames)) {
            $errors[] = 'Please select a registration form that has attributes.';
        }

        foreach ($attrNames as $name) {
            $code = strtolower($name);

            // Input presence/rules
            if ($code === 'cgpa') {
                if ($clean['cgpa'] === '' || $clean['cgpa'] === null) {
                    $fieldErrors['cgpa'][] = 'CGPA is required.';
                } else {
                    if (!is_numeric($clean['cgpa'])) {
                        $fieldErrors['cgpa'][] = 'CGPA must be a number.';
                    } else {
                        $f = (float)$clean['cgpa'];
                        if ($f < 0.00 || $f > 4.00) {
                            $fieldErrors['cgpa'][] = 'CGPA must be between 0.00 and 4.00.';
                        }
                    }
                }
            } elseif ($code === 'reason') {
                if (($clean['reason'] ?? '') === '') {
                    $fieldErrors['reason'][] = 'Reason is required.';
                }
            } elseif ($code === 'achievements') {
                if (($clean['achievements'] ?? '') === '') {
                    $fieldErrors['achievements'][] = 'Achievements are required.';
                }
            } elseif ($code === 'behaviorreport') {
                if (($clean['behaviorreport'] ?? 0) != 1) {
                    $fieldErrors['behaviorreport'][] = 'Behavior report confirmation is required.';
                }
            }

            // File presence
            if ($code === 'cgpa') {
                if (!$this->hasSingleUpload($uploads, 'cgpa')) {
                    $fieldErrors['cgpa_file'][] = 'CGPA proof file is required.';
                }
            } elseif ($code === 'achievements') {
                if ($this->countMultiUploads($uploads, 'achievements') < 1) {
                    $fieldErrors['achievements_files'][] = 'At least one achievement document is required.';
                }
            } elseif ($code === 'behaviorreport') {
                if (!$this->hasSingleUpload($uploads, 'behaviorreport')) {
                    $fieldErrors['behaviorreport_file'][] = 'Behavior report file is required.';
                }
            }
        }

        // ✳️ JPEG-only validation (preflight) — single keys
        $singleKeys = ['cgpa' => 'cgpa_file', 'behaviorreport' => 'behaviorreport_file'];
        foreach ($singleKeys as $key => $errKey) {
            if (isset($uploads['name'][$key]) && ($uploads['error'][$key] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $file = [
                    'name'     => $uploads['name'][$key],
                    'type'     => $uploads['type'][$key] ?? '',
                    'tmp_name' => $uploads['tmp_name'][$key] ?? '',
                    'error'    => $uploads['error'][$key],
                    'size'     => $uploads['size'][$key] ?? 0,
                ];
                if (!$this->isJpegUpload($file)) {
                    $fieldErrors[$errKey][] = 'Only JPG/JPEG files are allowed.';
                }
            }
        }
        // ✳️ JPEG-only validation — achievements (multi)
        if (isset($uploads['name']['achievements']) && is_array($uploads['name']['achievements'])) {
            foreach ($uploads['name']['achievements'] as $i => $n) {
                if (($uploads['error']['achievements'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
                $file = [
                    'name'     => $n,
                    'type'     => $uploads['type']['achievements'][$i] ?? '',
                    'tmp_name' => $uploads['tmp_name']['achievements'][$i] ?? '',
                    'error'    => $uploads['error']['achievements'][$i],
                    'size'     => $uploads['size']['achievements'][$i] ?? 0,
                ];
                if (!$this->isJpegUpload($file)) {
                    $fieldErrors['achievements_files'][] = 'Only JPG/JPEG files are allowed for achievements.';
                    break;
                }
            }
        }

        // If validation fails -> re-render with old input
        if (!empty($errors) || !empty($fieldErrors)) {
            $forms = $this->registrationFormModel->listOpenForms();
            $selectedForm = $registrationFormID;

            $renderAttrs = [];
            foreach ($attrNames as $name) {
                $code = strtolower($name);
                if (isset($subCols[$code])) {
                    $renderAttrs[] = [
                        'code'  => $code,
                        'label' => ucwords(str_replace(['_','-'],' ', $name)),
                        'type'  => $this->typeFor($code),
                    ];
                }
            }

            $old = [
                'studentID' => $studentID,
                'fields'    => $_POST['fields'] ?? [],
            ];

            $filePath = $this->fileHelper->getFilePath('CreateNomineeApplication');
            if ($filePath && file_exists($filePath)) {
                include $filePath;
            } else {
                echo "View file not found.";
            }
            return;
        }

        // 3) Save to DB (header + submission)
        $ids = $this->nomineeApplicationModel->createNomineeApplication(
            $studentID,
            $registrationFormID,
            $electionID,
            $clean
        );
        $appId        = (int)$ids['nomineeApplicationID'];
        $submissionId = (int)$ids['applicationSubmissionID'];

        if ($appId <= 0 || $submissionId <= 0) {
            $errors[] = 'Failed to create application. Please try again.';

            $forms = $this->registrationFormModel->listOpenForms();
            $selectedForm = $registrationFormID;

            $renderAttrs = [];
            foreach ($attrNames as $name) {
                $code = strtolower($name);
                if (isset($subCols[$code])) {
                    $renderAttrs[] = [
                        'code'  => $code,
                        'label' => ucwords(str_replace(['_','-'],' ', $name)),
                        'type'  => $this->typeFor($code),
                    ];
                }
            }

            $old = [
                'studentID' => $studentID,
                'fields'    => $_POST['fields'] ?? [],
            ];

            $filePath = $this->fileHelper->getFilePath('CreateNomineeApplication');
            if ($filePath && file_exists($filePath)) {
                include $filePath;
            } else {
                echo "View file not found.";
            }
            return;
        }

        // 4) Handle uploads -> /public/uploads/academic_document/{submissionId}/
        $uploads = $_FILES['uploads'] ?? [];
        $publicBase = realpath(__DIR__ . '/../../public');
        if ($publicBase === false) {
            $publicBase = dirname(__DIR__, 3) . '/public';
        }
        $targetDir = rtrim($publicBase, DIRECTORY_SEPARATOR) . '/uploads/academic_document/' . $submissionId;
        if (!is_dir($targetDir)) { @mkdir($targetDir, 0775, true); }

        $uniqueName = function (string $prefix, int $counter, string $ext): string {
            return "{$prefix}_{$counter}." . strtolower($ext);
        };

        $pickSingle = function(array $uploads, string $key): ?array {
            if (!isset($uploads['name'][$key])) return null;
            if (($uploads['error'][$key] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
            return [
                'name'     => $uploads['name'][$key],
                'type'     => $uploads['type'][$key] ?? '',
                'tmp_name' => $uploads['tmp_name'][$key] ?? '',
                'error'    => $uploads['error'][$key],
                'size'     => $uploads['size'][$key] ?? 0,
            ];
        };

        $pickMultiple = function(array $uploads, string $key): array {
            $out = [];
            if (!isset($uploads['name'][$key]) || !is_array($uploads['name'][$key])) return $out;
            foreach ($uploads['name'][$key] as $i => $n) {
                if (($uploads['error'][$key][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
                $out[] = [
                    'name'     => $n,
                    'type'     => $uploads['type'][$key][$i] ?? '',
                    'tmp_name' => $uploads['tmp_name'][$key][$i] ?? '',
                    'error'    => $uploads['error'][$key][$i],
                    'size'     => $uploads['size'][$key][$i] ?? 0,
                ];
            }
            return $out;
        };

        // ✳️ JPEG-only + auto-increment save
        $saveOne = function(array $file, string $prefix) use ($targetDir, $submissionId) {
            if (!$this->isJpegUpload($file)) return;
            $counter = $this->nextCounter($targetDir, $prefix);
            $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION)) ?: 'jpg';
            $stored = "{$prefix}_{$counter}.{$ext}";
            $dest   = $targetDir . '/' . $stored;

            if (@move_uploaded_file($file['tmp_name'], $dest)) {
                // Insert: academicdocument(academicFilename, applicationSubmissionID)
                $this->academicDocumentModel->insert($submissionId, $stored);
            }
        };

        if ($cgpa = $pickSingle($uploads, 'cgpa')) {
            $saveOne($cgpa, 'cgpa');
        }

        $achFiles = $pickMultiple($uploads, 'achievements');
        foreach ($achFiles as $f) {
            $saveOne($f, 'achievement');
        }

        if ($br = $pickSingle($uploads, 'behaviorreport')) {
            $saveOne($br, 'behaviorReport');
        }

        header("Location: /nominee-application");
    }

    /** Single-file key present? */
    private function hasSingleUpload(array $uploads, string $key): bool {
        if (!isset($uploads['name'][$key])) return false;
        return ($uploads['error'][$key] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
    }

    /** Count files in a multi-file key */
    private function countMultiUploads(array $uploads, string $key): int {
        if (!isset($uploads['name'][$key]) || !is_array($uploads['name'][$key])) return 0;
        $cnt = 0;
        foreach ($uploads['error'][$key] as $err) {
            if ($err === UPLOAD_ERR_OK) $cnt++;
        }
        return $cnt;
    }

    // -------------------- Edit Nominee Application --------------------
    public function editNomineeApplication($nomineeApplicationID)
    {
        $naId = (int)$nomineeApplicationID;
        $errors = [];
        $fieldErrors = [];

        $header = $this->nomineeApplicationModel->getNomineeApplicationById($naId);
        if (!$header) {
            echo "Nominee Application not found.";
            return;
        }

        // Attributes for this form
        $registrationFormID = (int)$header['registrationFormID'];

        if (!$this->registrationFormModel->isRegistrationOpen($registrationFormID)) {
            \set_flash('warning', 'Registration is closed. Editing is not allowed.');
            header("Location: /nominee-application");
            return;
        }


        $attrNames = $this->registrationFormModel->getAttributesByFormId($registrationFormID);
        $subCols   = $this->registrationFormModel->getSubmissionColumns();

        // Submission row (if any)
        $submission = null;
        $applicationSubmissionID = (int)($header['applicationSubmissionID'] ?? 0);
        if ($applicationSubmissionID > 0) {
            $submission = $this->nomineeApplicationModel->getSubmissionById($applicationSubmissionID);
        } else {
            // In case legacy rows exist without submission; keep null-safe defaults
            $submission = ['cgpa'=>null,'reason'=>'','achievements'=>'','behaviorReport'=>0];
        }

        // Documents
        $documents = $this->academicDocumentModel->listBySubmissionId((int)($submission['applicationSubmissionID'] ?? 0));

        // Build dynamic fields descriptor
        $renderAttrs = [];
        foreach ($attrNames as $name) {
            $code = strtolower($name);
            if (isset($subCols[$code])) {
                $renderAttrs[] = [
                    'code'  => $code,
                    'label' => ucwords(str_replace(['_','-'],' ', $name)),
                    'type'  => $this->typeFor($code),
                ];
            }
        }

        // Old values (pre-fill)
        $old = [
            'studentID' => (int)$header['studentID'],
            'fields'    => [
                'cgpa'           => $submission['cgpa'] ?? '',
                'reason'         => $submission['reason'] ?? '',
                'achievements'   => $submission['achievements'] ?? '',
                'behaviorreport' => (int)($submission['behaviorReport'] ?? 0), // normalized to lowercase key for view
            ],
        ];

        $nomineeApplicationData = [
            'nomineeApplicationID' => $naId,
            'registrationFormID'   => $registrationFormID,
            'registrationFormTitle'=> $header['registrationFormTitle'] ?? '',
            'student_fullname'     => $header['student_fullname'] ?? '',
            'studentID'            => (int)$header['studentID'],
            'electionID'           => (int)$header['electionID'],
            'applicationSubmissionID' => (int)($submission['applicationSubmissionID'] ?? 0),
        ];

        $filePath = $this->fileHelper->getFilePath('EditNomineeApplication');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }

    /** ---------- EDIT (POST) ---------- */
    public function editStoreNomineeApplication($nomineeApplicationID)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->editNomineeApplication($nomineeApplicationID);
            return;
        }

        $naId = (int)$nomineeApplicationID;
        $errors = [];
        $fieldErrors = [];

        $header = $this->nomineeApplicationModel->getNomineeApplicationById($naId);
        if (!$header) {
            echo "Nominee Application not found.";
            return;
        }

        $registrationFormID = (int)$header['registrationFormID'];

        if (!$this->registrationFormModel->isRegistrationOpen($registrationFormID)) {
            \set_flash('warning', 'Registration is closed. Editing is not allowed.');
            header("Location: /nominee-application");
            return;
        }


        $electionID         = (int)$header['electionID'];
        $studentID          = (int)$header['studentID'];
        $applicationSubmissionID = (int)($header['applicationSubmissionID'] ?? 0);

        // Attributes
        $attrNames = $this->registrationFormModel->getAttributesByFormId($registrationFormID);
        $subCols   = $this->registrationFormModel->getSubmissionColumns();

        // Normalize to lowercase codes once and reuse
        $activeAttrCodes = array_map(static function($n){ return strtolower($n); }, $attrNames);

        // Helper: is this attribute present in current form?
        $hasAttr = static function(string $code) use ($activeAttrCodes): bool {
            return in_array(strtolower($code), $activeAttrCodes, true);
        };

        // Collect posted field values (normalize)
        $clean = [];
        foreach ($attrNames as $name) {
            $code = strtolower($name);
            if (!isset($subCols[$code])) continue;
            $type = $this->typeFor($code);
            $raw  = $_POST['fields'][$code] ?? null;
            if ($type === 'checkbox') {
                $clean[$code] = isset($raw) ? 1 : 0;
            } else {
                $val = is_string($raw) ? trim($raw) : '';
                if ($code === 'cgpa') {
                    $clean[$code] = ($val !== '' && is_numeric($val)) ? (float)$val : $val;
                } else {
                    $clean[$code] = $val;
                }
            }
        }

        // Validate inputs similarly to create
        foreach ($attrNames as $name) {
            $code = strtolower($name);

            if ($code === 'cgpa') {
                if ($clean['cgpa'] === '' || $clean['cgpa'] === null) {
                    $fieldErrors['cgpa'][] = 'CGPA is required.';
                } else {
                    if (!is_numeric($clean['cgpa'])) {
                        $fieldErrors['cgpa'][] = 'CGPA must be a number.';
                    } else {
                        $f = (float)$clean['cgpa'];
                        if ($f < 0.00 || $f > 4.00) {
                            $fieldErrors['cgpa'][] = 'CGPA must be between 0.00 and 4.00.';
                        }
                    }
                }
            } elseif ($code === 'reason') {
                if (($clean['reason'] ?? '') === '') {
                    $fieldErrors['reason'][] = 'Reason is required.';
                }
            } elseif ($code === 'achievements') {
                if (($clean['achievements'] ?? '') === '') {
                    $fieldErrors['achievements'][] = 'Achievements are required.';
                }
            } elseif ($code === 'behaviorreport') {
                if (($clean['behaviorreport'] ?? 0) != 1) {
                    $fieldErrors['behaviorreport'][] = 'Behavior report confirmation is required.';
                }
            }
        }

        // If validation fails -> re-render (note: file type errors added below will be handled later)
        if (!empty($errors) || !empty($fieldErrors)) {
            $documents = $this->academicDocumentModel->listBySubmissionId($applicationSubmissionID);

            // Build renderAttrs for view
            $renderAttrs = [];
            foreach ($attrNames as $name) {
                $code = strtolower($name);
                if (isset($subCols[$code])) {
                    $renderAttrs[] = [
                        'code'  => $code,
                        'label' => ucwords(str_replace(['_','-'],' ', $name)),
                        'type'  => $this->typeFor($code),
                    ];
                }
            }

            $old = [
                'studentID' => $studentID,
                'fields'    => $_POST['fields'] ?? [],
            ];

            $nomineeApplicationData = [
                'nomineeApplicationID' => $naId,
                'registrationFormID'   => $registrationFormID,
                'registrationFormTitle'=> $header['registrationFormTitle'] ?? '',
                'student_fullname'     => $header['student_fullname'] ?? '',
                'studentID'            => $studentID,
                'electionID'           => $electionID,
                'applicationSubmissionID' => $applicationSubmissionID,
            ];

            $filePath = $this->fileHelper->getFilePath('EditNomineeApplication');
            if ($filePath && file_exists($filePath)) {
                include $filePath;
            } else {
                echo "View file not found.";
            }
            return;
        }

        // Update submission row
        if ($applicationSubmissionID <= 0) {
            // Safety: if for some reason there is no submission row yet, create one minimal & reuse your create logic
            // But normally your create flow already inserted it; here we assume it exists.
        }
        $this->nomineeApplicationModel->updateSubmission($applicationSubmissionID, $clean);

        // ---- SAFETY: do not allow deleting the last file per category ----
        $deleteIds = array_map('intval', $_POST['delete_docs'] ?? []);
        $documents = $this->academicDocumentModel->listBySubmissionId($applicationSubmissionID);

        $existing = $this->countByCategory($documents);
        $toDelete = $this->countDeletionsByCategory($applicationSubmissionID, $deleteIds);

        $uploads  = $_FILES['uploads'] ?? [];

        // ✳️ JPEG-only validation (preflight) — only for attributes present in this form
        $singleKeys = [
            'cgpa'           => 'cgpa_file',
            'behaviorreport' => 'behaviorreport_file',
        ];
        foreach ($singleKeys as $key => $errKey) {
            if (!$hasAttr($key)) continue; // <-- NEW: skip if attr not in this form
            if (isset($uploads['name'][$key]) && ($uploads['error'][$key] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $file = [
                    'name'     => $uploads['name'][$key],
                    'type'     => $uploads['type'][$key] ?? '',
                    'tmp_name' => $uploads['tmp_name'][$key] ?? '',
                    'error'    => $uploads['error'][$key],
                    'size'     => $uploads['size'][$key] ?? 0,
                ];
                if (!$this->isJpegUpload($file)) {
                    $fieldErrors[$errKey][] = 'Only JPG/JPEG files are allowed.';
                }
            }
        }
        // Multi-file 'achievements'
        if ($hasAttr('achievements') && isset($uploads['name']['achievements']) && is_array($uploads['name']['achievements'])) {
            foreach ($uploads['name']['achievements'] as $i => $n) {
                if (($uploads['error']['achievements'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
                $file = [
                    'name'     => $n,
                    'type'     => $uploads['type']['achievements'][$i] ?? '',
                    'tmp_name' => $uploads['tmp_name']['achievements'][$i] ?? '',
                    'error'    => $uploads['error']['achievements'][$i],
                    'size'     => $uploads['size']['achievements'][$i] ?? 0,
                ];
                if (!$this->isJpegUpload($file)) {
                    $fieldErrors['achievements_files'][] = 'Only JPG/JPEG files are allowed for achievements.';
                    break;
                }
            }
        }


        // What the user is adding now (limited to active attributes)
        $adding = [
            'cgpa'           => $hasAttr('cgpa')           && $this->hasSingleUpload($uploads, 'cgpa') ? 1 : 0,
            'achievements'   => $hasAttr('achievements')   ? $this->countMultiUploads($uploads, 'achievements') : 0,
            'behaviorreport' => $hasAttr('behaviorreport') && $this->hasSingleUpload($uploads, 'behaviorreport') ? 1 : 0,
        ];

        // Enforce "at least one doc" only for categories present in this form
        $requiredCats = array_values(array_filter(
            ['cgpa','achievements','behaviorreport'],
            fn($c) => $hasAttr($c)
        ));

        // Recompute $documents/$existing/$toDelete if needed (already computed above)
        foreach ($requiredCats as $cat) {
            $after = ($existing[$cat] ?? 0) - ($toDelete[$cat] ?? 0) + ($adding[$cat] ?? 0);
            if ($after < 1) {
                if ($cat === 'cgpa') {
                    $fieldErrors['cgpa_file'][] = 'You must keep at least one CGPA document. Upload a replacement before deleting the last one.';
                } elseif ($cat === 'achievements') {
                    $fieldErrors['achievements_files'][] = 'You must keep at least one Achievement document. Upload a replacement before deleting the last one.';
                } elseif ($cat === 'behaviorreport') {
                    $fieldErrors['behaviorreport_file'][] = 'You must keep at least one Behavior Report. Upload a replacement before deleting the last one.';
                }
            }
        }


        if (!empty($errors) || !empty($fieldErrors)) {
            // Rebuild view state and re-render Edit page
            $documents = $this->academicDocumentModel->listBySubmissionId($applicationSubmissionID);

            // Build renderAttrs
            $renderAttrs = [];
            foreach ($attrNames as $name) {
                $code = strtolower($name);
                if (isset($subCols[$code])) {
                    $renderAttrs[] = [
                        'code'  => $code,
                        'label' => ucwords(str_replace(['_','-'],' ', $name)),
                        'type'  => $this->typeFor($code),
                    ];
                }
            }

            $old = [
                'studentID' => $studentID,
                'fields'    => $_POST['fields'] ?? [],
            ];

            $nomineeApplicationData = [
                'nomineeApplicationID'   => $naId,
                'registrationFormID'     => $registrationFormID,
                'registrationFormTitle'  => $header['registrationFormTitle'] ?? '',
                'student_fullname'       => $header['student_fullname'] ?? '',
                'studentID'              => $studentID,
                'electionID'             => $electionID,
                'applicationSubmissionID'=> $applicationSubmissionID,
            ];

            $filePath = $this->fileHelper->getFilePath('EditNomineeApplication');
            if ($filePath && file_exists($filePath)) {
                include $filePath;
            } else {
                echo "View file not found.";
            }
            return;
        }

        // If passes, proceed with delete + new uploads
        if (!empty($deleteIds)) {
            $this->removeDocumentsFromDiskAndDb($applicationSubmissionID, $deleteIds);
        }
        $this->processUploads($applicationSubmissionID, $uploads);

        \set_flash('success', 'Nominee Application updated successfully.');
        header("Location: /nominee-application");

    }

    // ---------- helpers for files ----------

    /** Save new uploads to disk & DB (same rules as create) */
    private function processUploads(int $submissionId, array $uploads): void
    {
        $publicBase = realpath(__DIR__ . '/../../public');
        if ($publicBase === false) {
            $publicBase = dirname(__DIR__, 3) . '/public';
        }
        $targetDir = rtrim($publicBase, DIRECTORY_SEPARATOR) . '/uploads/academic_document/' . $submissionId;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }

        $uniqueName = function (string $prefix, int $counter, string $ext): string {
            return "{$prefix}_{$counter}." . strtolower($ext);
        };

        $pickSingle = function(array $uploads, string $key): ?array {
            if (!isset($uploads['name'][$key])) return null;
            if (($uploads['error'][$key] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
            return [
                'name' => $uploads['name'][$key],
                'type' => $uploads['type'][$key] ?? '',
                'tmp_name' => $uploads['tmp_name'][$key] ?? '',
                'error' => $uploads['error'][$key],
                'size' => $uploads['size'][$key] ?? 0,
            ];
        };

        $pickMultiple = function(array $uploads, string $key): array {
            $out = [];
            if (!isset($uploads['name'][$key]) || !is_array($uploads['name'][$key])) return $out;
            foreach ($uploads['name'][$key] as $i => $n) {
                if (($uploads['error'][$key][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
                $out[] = [
                    'name' => $n,
                    'type' => $uploads['type'][$key][$i] ?? '',
                    'tmp_name' => $uploads['tmp_name'][$key][$i] ?? '',
                    'error' => $uploads['error'][$key][$i],
                    'size' => $uploads['size'][$key][$i] ?? 0,
                ];
            }
            return $out;
        };

        // ✳️ JPEG-only + auto-increment save
        $saveOne = function(array $file, string $prefix) use ($targetDir, $submissionId) {
            if (!$this->isJpegUpload($file)) return;
            $counter = $this->nextCounter($targetDir, $prefix);
            $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION)) ?: 'jpg';
            $stored = "{$prefix}_{$counter}.{$ext}";
            $dest   = $targetDir . '/' . $stored;
            if (@move_uploaded_file($file['tmp_name'], $dest)) {
                $this->academicDocumentModel->insert($submissionId, $stored);
            }
        };

        // Save new files if any were chosen (optional on edit)
        if ($cgpa = $pickSingle($uploads, 'cgpa')) {
            // Optional: we don't auto-delete previous cgpa file(s); user should tick delete to remove old ones.
            $saveOne($cgpa, 'cgpa');
        }

        $achFiles = $pickMultiple($uploads, 'achievements');
        foreach ($achFiles as $f) {
            $saveOne($f, 'achievement');
        }

        if ($br = $pickSingle($uploads, 'behaviorreport')) {
            $saveOne($br, 'behaviorReport');
        }
    }

    /** Delete selected documents from disk + DB safely */
    private function removeDocumentsFromDiskAndDb(int $submissionId, array $academicIds): void
    {
        $publicBase = realpath(__DIR__ . '/../../public');
        if ($publicBase === false) {
            $publicBase = dirname(__DIR__, 3) . '/public';
        }
        $baseDir = rtrim($publicBase, DIRECTORY_SEPARATOR) . '/uploads/academic_document/' . $submissionId;

        foreach ($academicIds as $docId) {
            $doc = $this->academicDocumentModel->findOne($docId);
            if (!$doc) continue;
            if ((int)$doc['applicationSubmissionID'] !== $submissionId) continue;

            $file = $baseDir . '/' . $doc['academicFilename'];
            if (is_file($file)) {
                @unlink($file);
            }
            $this->academicDocumentModel->delete($docId);
        }
    }

    /** Map stored filename -> logical category */
    private function categoryFromFilename(string $fname): string
    {
        $n = strtolower($fname);
        if (str_starts_with($n, 'cgpa_')) return 'cgpa';
        if (str_starts_with($n, 'achievement_')) return 'achievements';
        if (str_starts_with($n, 'behaviorreport_')) return 'behaviorreport';
        return 'other';
    }

    /** Count existing docs per category from a list (listBySubmissionId result) */
    private function countByCategory(array $documents): array
    {
        $cnt = ['cgpa'=>0, 'achievements'=>0, 'behaviorreport'=>0, 'other'=>0];
        foreach ($documents as $d) {
            $cat = $this->categoryFromFilename($d['academicFilename'] ?? '');
            if (!isset($cnt[$cat])) $cnt[$cat] = 0;
            $cnt[$cat]++;
        }
        return $cnt;
    }

    /** Count how many deletions per category (safeguard wrong submission IDs) */
    private function countDeletionsByCategory(int $submissionId, array $deleteIds): array
    {
        $cnt = ['cgpa'=>0, 'achievements'=>0, 'behaviorreport'=>0, 'other'=>0];
        foreach ($deleteIds as $id) {
            $doc = $this->academicDocumentModel->findOne((int)$id);
            if (!$doc) continue;
            if ((int)$doc['applicationSubmissionID'] !== $submissionId) continue;
            $cat = $this->categoryFromFilename($doc['academicFilename'] ?? '');
            if (!isset($cnt[$cat])) $cnt[$cat] = 0;
            $cnt[$cat]++;
        }
        return $cnt;
    }

    /** Strong JPEG check: extension + MIME via finfo (fallback to $_FILES['type']). */
    private function isJpegUpload(array $file): bool
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return false;

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg'], true)) return false;

        $tmp = $file['tmp_name'] ?? '';
        if (!is_file($tmp)) return false;

        $mime = null;
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            if ($f) {
                $mime = finfo_file($f, $tmp) ?: null;
                finfo_close($f);
            }
        }
        if ($mime === null) $mime = $file['type'] ?? '';
        return strtolower((string)$mime) === 'image/jpeg';
    }

    /** Find next available counter for "<prefix>_N.jpg" in a directory. */
    private function nextCounter(string $dir, string $prefix): int
    {
        $max = 0;
        if (is_dir($dir)) {
            foreach (glob($dir . '/' . $prefix . '_*.{jpg,jpeg}', GLOB_BRACE) as $p) {
                $bn = basename($p);
                if (preg_match('/^' . preg_quote($prefix,'/') . '_(\d+)\.(jpe?g)$/i', $bn, $m)) {
                    $n = (int)$m[1];
                    if ($n > $max) $max = $n;
                }
            }
        }
        return $max + 1;
    }

    // -------------------------------- View Nominee Applications --------------------------------
    public function viewNomineeApplication($nomineeApplicationID)
    {
        $naId = (int)$nomineeApplicationID;

        // 1) Header (form title, student, dates, status, admin id, etc.)
        $header = $this->nomineeApplicationModel->getNomineeApplicationById($naId);
        if (!$header) {
            echo "Nominee Application not found.";
            return;
        }

        // 2) Admin handler full name (or N/A)
        $adminFullname = $this->nomineeApplicationModel->getAdminFullnameByAdminId(
            isset($header['adminID']) ? (int)$header['adminID'] : null
        );

        // 3) Submission (dynamic fields)
        $submission = $this->nomineeApplicationModel->getSubmissionByAppId($naId) ?? [];

        // 4) Attributes for this registration form (what to show)
        $registrationFormID = (int)$header['registrationFormID'];
        $attrNames = $this->registrationFormModel->getAttributesByFormId($registrationFormID); // returns ['cgpa','achievements','behaviorReport',...]
        $showAttrs = [];
        foreach ($attrNames as $attrName) {
            // DB column names are exactly as stored in registrationformattribute.attributeName
            $col = $attrName; // e.g., 'cgpa', 'reason', 'achievements', 'behaviorReport'
            $label = preg_replace('/([a-z])([A-Z])/', '$1 $2', $attrName); // split camelCase => "behavior Report"
            $label = ucwords(str_replace(['_','-'], ' ', $label));
            $value = $submission[$col] ?? null;

            // Normalise boolean-ish display for behaviorReport (tinyint(1))
            if ($col === 'behaviorReport') {
                $value = ((int)($value ?? 0) === 1) ? 'Yes' : 'No';
            }

            // Keep only the 4 supported keys in your submission table
            if (in_array($col, ['cgpa','reason','achievements','behaviorReport'], true)) {
                $showAttrs[] = ['code' => $col, 'label' => $label, 'value' => $value];
            }
        }

        // 5) Documents for this submission
        $applicationSubmissionID = (int)($submission['applicationSubmissionID'] ?? 0);
        $documents = $applicationSubmissionID > 0
            ? $this->academicDocumentModel->listBySubmissionId($applicationSubmissionID)
            : [];

        // 6) Pack view data
        $na = [
            'registrationFormTitle' => $header['registrationFormTitle'] ?? '',
            'registrationFormID'    => (int)$header['registrationFormID'],
            'student_fullname'      => $header['student_fullname'] ?? '',
            'studentID'             => (int)$header['studentID'],
            'submittedDate'         => $header['submittedDate'] ?? '',
            'applicationStatus'     => $header['applicationStatus'] ?? '',
            'admin_fullname'        => $adminFullname ?? 'N/A',
            'applicationSubmissionID'=> $applicationSubmissionID,
        ];

        // 7) Render
        $filePath = $this->fileHelper->getFilePath('ViewNomineeApplication');
        if ($filePath && file_exists($filePath)) {
            // Make these available in the view:
            // $na, $showAttrs, $documents
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }

    // Accept / Reject Nominee Application
    public function acceptNomineeApplication($nomineeApplicationID)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /nominee-application'");
            return;
        }

        $this->nomineeApplicationModel->acceptNomineeApplication($nomineeApplicationID, 1);
        \set_flash('success', 'Nominee Application accepted successfully.');
        header("Location: /nominee-application");
    }

    public function rejectNomineeApplication($nomineeApplicationID)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /nominee-application'");
            return;
        }

        $this->nomineeApplicationModel->rejectNomineeApplication($nomineeApplicationID, 1);
        \set_flash('success', 'Nominee Application rejected successfully.');
        header("Location: /nominee-application");
    }

    // -------------------------------- Publish Nominee Applications --------------------------------
    public function publishNomineeApplications()
    {
        $errors = [];
        $fieldErrors = [];
        $old = [];

        // Use your ElectionEventModel to populate the select
        $electionEvents = $this->electionEventModel->getAllElectionEvents() ?: [];

        // Optional preview (?electionEventID=...)
        $selectedEventId = (int)($_GET['electionEventID'] ?? 0);
        $acceptedCandidates = [];
        if ($selectedEventId > 0) {
            $acceptedCandidates = $this->nomineeApplicationModel
                ->getAcceptedApplicationsByElection($selectedEventId);
        }

        $filePath = $this->fileHelper->getFilePath('PublishNomineeApplications');
        if ($filePath && file_exists($filePath)) {
            include $filePath; // expects: $electionEvents, $selectedEventId, $acceptedCandidates, $errors, $fieldErrors, $old
        } else {
            echo "View file not found.";
        }
    }

    public function publishStoreNomineeApplications()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->publishNomineeApplications();
            return;
        }

        $errors = [];
        $fieldErrors = [];

        $electionEventID = (int)($_POST['electionEventID'] ?? 0);
        if ($electionEventID <= 0) {
            $fieldErrors['electionEventID'] = 'Please select a valid election event.';
        } else {
            // Pre-publish checks
            // PENDING applications check
            $pending = $this->nomineeApplicationModel->countPendingByElection($electionEventID);
            if ($pending > 0) {
                $errors[] = "You still have students in PENDING! ($pending)";
            }

            // ACCEPTED applications check
            $check = $this->nomineeApplicationModel->getAcceptedApplicationsByElection($electionEventID);
            if (empty($check)) {
                $errors[] = 'No ACCEPTED applications found for the selected event.';
            }
        }

        if (!empty($errors) || !empty($fieldErrors)) {
            $electionEvents = $this->electionEventModel->getAllElectionEvents() ?: [];
            $selectedEventId = $electionEventID;
            $acceptedCandidates = $check ?? [];

            $filePath = $this->fileHelper->getFilePath('PublishNomineeApplications');
            if ($filePath && file_exists($filePath)) {
                include $filePath;
            } else {
                echo "View file not found.";
            }
            return;
        }

        // Transactional publish
        $ok = $this->nomineeApplicationModel->publishNomineeApplications($electionEventID);
        if (!$ok) {
            \set_flash('danger', 'Publish failed. Please try again.');
            $this->publishNomineeApplications();
            return;
        }

        \set_flash('success', 'Nominee Applications published successfully.');
        header("Location: /nominee-application");
    }

    // -------------------------------- View Published Nominee Applications --------------------------------
    public function finalizePublishNomineeApplications($id)
    {
        $electionEventID = (int)$id;

        // If you want PUBLISHED (after publish), use this:
        $acceptedCandidates = $electionEventID > 0
            ? ($this->nomineeApplicationModel->getPublishedApplicationsByElection($electionEventID) ?: [])
            : [];

        $filePath = $this->fileHelper->getFilePath('ViewPublishNomineeApplications');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }





}
