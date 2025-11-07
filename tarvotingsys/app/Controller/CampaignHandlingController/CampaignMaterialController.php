<?php
namespace Controller\CampaignHandlingController;

use Model\CampaignHandlingModel\CampaignMaterialModel;
use FileHelper;

class CampaignMaterialController {
    private CampaignMaterialModel $campaignMaterialModel;
    private FileHelper $fileHelper;

    public function __construct()
    {
        $this->campaignMaterialModel = new CampaignMaterialModel();
        $this->fileHelper            = new FileHelper('campaign_material');
    }

    public function listCampaignMaterials()
    {
        $campaignMaterials = $this->campaignMaterialModel->getAllCampaignMaterials();
        $filePath = $this->fileHelper->getFilePath('CampaignMaterialList');

        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }

    // --------------------- Create --------------------- //
    public function createCampaignMaterial()
    {
        $errors = [];
        $fieldErrors = [];
        $old = [];

        // Preload eligible elections for the dropdown
        $elections = $this->campaignMaterialModel->getEligibleElections();

        // If user already selected an election (old state), load nominees for that election
        $nominees = [];
        if (!empty($_GET['electionID']) && ctype_digit($_GET['electionID'])) {
            $old['electionID'] = (int)$_GET['electionID'];
            $nominees = $this->campaignMaterialModel->getEligibleNomineesByElection($old['electionID']);
        }

        $filePath = $this->fileHelper->getFilePath('CreateCampaignMaterial');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }

    public function storeCreateCampaignMaterial()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->createCampaignMaterial();
            return;
        }

        // Collect + sanitize
        $electionID        = isset($_POST['electionID']) ? (int)$_POST['electionID'] : 0;
        $nomineeID         = isset($_POST['nomineeID']) ? (int)$_POST['nomineeID'] : 0;
        $materialsTitle    = trim($_POST['materialsTitle'] ?? '');
        $materialsType     = $_POST['materialsType'] ?? '';
        $materialsDesc     = trim($_POST['materialsDesc'] ?? '');
        $materialsQuantity = isset($_POST['materialsQuantity']) ? (int)$_POST['materialsQuantity'] : 0;

        $old = [
            'electionID'        => $electionID,
            'nomineeID'         => $nomineeID,
            'materialsTitle'    => $materialsTitle,
            'materialsType'     => $materialsType,
            'materialsDesc'     => $materialsDesc,
            'materialsQuantity' => $materialsQuantity,
        ];
        $errors = [];
        $fieldErrors = [];

        // Validate
        if ($electionID <= 0)             { $fieldErrors['electionID'][] = 'Please select an election.'; }
        if ($nomineeID <= 0)              { $fieldErrors['nomineeID'][] = 'Please select a nominee.'; }
        if ($materialsTitle === '')       { $fieldErrors['materialsTitle'][] = 'Title is required.'; }
        if (!in_array($materialsType, ['PHYSICAL','DIGITAL'], true)) {
            $fieldErrors['materialsType'][] = 'Invalid type.';
        }
        if ($materialsDesc === '')        { $fieldErrors['materialsDesc'][] = 'Description is required.'; }
        if ($materialsQuantity <= 0)      { $fieldErrors['materialsQuantity'][] = 'Quantity must be a positive integer.'; }

        // ---------- FILES: REQUIRE AT LEAST ONE, VALIDATE EACH ----------
        $files = $_FILES['materialsFiles'] ?? null;
        $hasAtLeastOne = false;
        $allowedExt = ['jpg','jpeg','png','gif','pdf','doc','docx','ppt','pptx'];
        $maxBytesPerFile = 10 * 1024 * 1024; // 10 MB

        if ($files && is_array($files['name'] ?? null)) {
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                $err = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
                if ($err === UPLOAD_ERR_NO_FILE) continue;

                if ($err !== UPLOAD_ERR_OK) {
                    $errors[] = 'One of the uploaded files failed to upload.';
                    continue;
                }

                $tmpPath  = $files['tmp_name'][$i] ?? '';
                $origName = $files['name'][$i] ?? '';
                if (!is_uploaded_file($tmpPath)) {
                    $errors[] = "Invalid file: {$origName}";
                    continue;
                }

                // size
                $size = filesize($tmpPath);
                if ($size === false || $size <= 0) {
                    $errors[] = "File {$origName} is empty or unreadable.";
                    continue;
                }
                if ($size > $maxBytesPerFile) {
                    $errors[] = "File {$origName} exceeds 10 MB.";
                    continue;
                }

                // extension
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt, true)) {
                    $errors[] = "File {$origName} has an unsupported type. Allowed: " . strtoupper(implode(', ', $allowedExt));
                    continue;
                }

                $hasAtLeastOne = true;
            }
        }

        if (!$hasAtLeastOne) {
            $fieldErrors['materialsFiles'][] = 'Please upload at least one file.';
        }

        // Server-side eligibility re-check
        if ($electionID > 0) {
            $okElections = array_column($this->campaignMaterialModel->getEligibleElections(), 'electionID');
            if (!in_array($electionID, $okElections)) {
                $fieldErrors['electionID'][] = 'This election is not eligible for campaign materials (registration not ended or no published nominees).';
            }
        }
        if ($electionID > 0 && $nomineeID > 0) {
            $okNominees   = $this->campaignMaterialModel->getEligibleNomineesByElection($electionID);
            $okNomineeIDs = array_column($okNominees, 'nomineeID');
            if (!in_array($nomineeID, $okNomineeIDs)) {
                $fieldErrors['nomineeID'][] = 'Selected nominee is not eligible for this election.';
            }
        }

        // If invalid -> re-render
        if (!empty($fieldErrors) || !empty($errors)) {
            $elections = $this->campaignMaterialModel->getEligibleElections();
            $nominees  = $electionID ? $this->campaignMaterialModel->getEligibleNomineesByElection($electionID) : [];
            $filePath  = $this->fileHelper->getFilePath('CreateCampaignMaterial');
            include $filePath;
            return;
        }

        // Save HEADER ONLY
        $data = [
            'electionID'        => $electionID,
            'nomineeID'         => $nomineeID,
            'materialsTitle'    => $materialsTitle,
            'materialsType'     => $materialsType,
            'materialsDesc'     => $materialsDesc ?: null,
            'materialsQuantity' => $materialsQuantity,
            'adminID'           => $_SESSION['adminID'] ?? null, // if applicable
        ];

        // IMPORTANT: your Model should have the updated single-arg method
        $appID = $this->campaignMaterialModel->createCampaignMaterial($data);
        if ($appID <= 0) {
            \set_flash('danger', 'Failed to create campaign material.');
            $elections = $this->campaignMaterialModel->getEligibleElections();
            $nominees  = $electionID ? $this->campaignMaterialModel->getEligibleNomineesByElection($electionID) : [];
            $errors[]  = 'Unexpected error while saving.';
            $filePath  = $this->fileHelper->getFilePath('CreateCampaignMaterial');
            include $filePath;
            return;
        }

        // Now that we have the ID, save files to disk and rows to campaignmaterialsdocument
        $this->saveCampaignUploads($appID, $_FILES['materialsFiles'] ?? null);

        \set_flash('success', 'Campaign material application submitted (PENDING).');
        header('Location: /campaign-material');
    }

    /**
     * Find next available integer for filenames like "campaign_material_<n>.*" in a folder.
     */
    private function nextCampaignCounter(string $dir): int
    {
        $max = 0;
        if (is_dir($dir)) {
            foreach (glob($dir . '/campaign_material_*.{jpg,jpeg,png,gif,pdf,doc,docx,ppt,pptx}', GLOB_BRACE) as $p) {
                $bn = basename($p);
                if (preg_match('/^campaign_material_(\d+)\.(?:jpe?g|png|gif|pdf|docx?|pptx?)$/i', $bn, $m)) {
                    $n = (int)$m[1];
                    if ($n > $max) $max = $n;
                }
            }
        }
        return $max + 1;
    }


    /**
     * Move each uploaded file into /public/uploads/campaign_material/{appID}/
     * and insert a row into campaignmaterialsdocument.
     * Stores just the filename; when rendering links, prefix with /uploads/campaign_material/{appID}/
     */
    private function saveCampaignUploads(int $appID, ?array $files): void
    {
        if (!$files || !is_array($files['name'] ?? null)) return;

        // Resolve /public path
        $publicBase = realpath(__DIR__ . '/../../public');
        if ($publicBase === false) {
            $publicBase = dirname(__DIR__, 3) . '/public';
        }
        $targetDir = rtrim($publicBase, DIRECTORY_SEPARATOR) . '/uploads/campaign_material/' . $appID;
        if (!is_dir($targetDir)) { @mkdir($targetDir, 0775, true); }

        $names = $files['name'];
        $tmps  = $files['tmp_name'];
        $errs  = $files['error'];

        // Get the next starting counter (so if there are existing files, we continue)
        $counter = $this->nextCampaignCounter($targetDir);

        for ($i = 0, $count = count($names); $i < $count; $i++) {
            if (($errs[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
            $tmp  = $tmps[$i] ?? '';
            $orig = $names[$i] ?? '';
            if (!is_uploaded_file($tmp)) continue;

            // Keep the original extension (fallback to 'bin' if none)
            $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION)) ?: 'bin';

            // Build sequential filename: campaign_material_1.ext, campaign_material_2.ext, ...
            $fname = "campaign_material_{$counter}.{$ext}";
            $dest  = $targetDir . '/' . $fname;

            if (@move_uploaded_file($tmp, $dest)) {
                $this->campaignMaterialModel->insertDocument($appID, $fname);
                $counter++; // increment for the next file
            }
        }
    }

    // --------------------- Edit --------------------- //
    // Display Edit Campaign Material
    public function editCampaignMaterial($materialsApplicationID)
    {
        $id = (int)$materialsApplicationID;

        $campaignMaterial = $this->campaignMaterialModel->getCampaignMaterialById($id);
        if (!$campaignMaterial) {
            \set_flash('danger', 'Campaign material not found.');
            header('Location: /campaign-material');
            return;
        }
        $documents = $this->campaignMaterialModel->getDocumentsByApplication($id);

        $errors = [];
        $fieldErrors = [];

        $filePath = $this->fileHelper->getFilePath('EditCampaignMaterial');
        if ($filePath && file_exists($filePath)) {
            include $filePath; // exposes: $campaignMaterial, $documents, $errors, $fieldErrors
        } else {
            echo "View file not found.";
        }
    }

    // Edit Campaign Material + Validation
    public function storeEditCampaignMaterial($materialsApplicationID)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->editCampaignMaterial($materialsApplicationID);
            return;
        }

        $id               = (int)$materialsApplicationID;
        $header           = $this->campaignMaterialModel->getCampaignMaterialById($id);
        if (!$header) {
            \set_flash('danger', 'Campaign material not found.');
            header('Location: /campaign-material');
            return;
        }

        // Collect + sanitize (election/nominee not editable here; show only)
        $materialsTitle    = trim($_POST['materialsTitle'] ?? '');
        $materialsType     = $_POST['materialsType'] ?? '';
        $materialsDesc     = trim($_POST['materialsDesc'] ?? '');
        $materialsQuantity = isset($_POST['materialsQuantity']) ? (int)$_POST['materialsQuantity'] : 0;

        $errors = [];
        $fieldErrors = [];

        // Required validations
        if ($materialsTitle === '')       { $fieldErrors['materialsTitle'][]    = 'Title is required.'; }
        if (!in_array($materialsType, ['PHYSICAL','DIGITAL'], true)) {
            $fieldErrors['materialsType'][] = 'Invalid type.';
        }
        if ($materialsDesc === '')        { $fieldErrors['materialsDesc'][]     = 'Description is required.'; }
        if ($materialsQuantity <= 0)      { $fieldErrors['materialsQuantity'][] = 'Quantity must be a positive integer.'; }

        // Existing docs & requested deletions
        $existingDocs = $this->campaignMaterialModel->getDocumentsByApplication($id);
        $deleteIds    = array_map('intval', $_POST['delete_docs'] ?? []);

        // Files to add (optional but total must be >=1 after delete+add)
        $files           = $_FILES['materialsFiles'] ?? null;
        $allowedExt      = ['jpg','jpeg','png','gif','pdf','doc','docx','ppt','pptx'];
        $maxBytesPerFile = 10 * 1024 * 1024; // 10MB

        $addingCount = 0;
        if ($files && is_array($files['name'] ?? null)) {
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                $err = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
                if ($err === UPLOAD_ERR_NO_FILE) continue;

                if ($err !== UPLOAD_ERR_OK) { $errors[] = 'One of the uploaded files failed to upload.'; continue; }

                $tmp  = $files['tmp_name'][$i] ?? '';
                $name = $files['name'][$i] ?? '';
                if (!is_uploaded_file($tmp)) { $errors[] = "Invalid file: {$name}"; continue; }

                $size = filesize($tmp);
                if ($size === false || $size <= 0) { $errors[] = "File {$name} is empty or unreadable."; continue; }
                if ($size > $maxBytesPerFile)      { $errors[] = "File {$name} exceeds 10 MB."; continue; }

                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt, true)) {
                    $errors[] = "File {$name} has an unsupported type. Allowed: " . strtoupper(implode(', ', $allowedExt));
                    continue;
                }
                $addingCount++;
            }
        }

        // After delete+add, must still have >= 1 file
        $remaining = max(0, count($existingDocs) - count($deleteIds)) + $addingCount;
        if ($remaining < 1) {
            $fieldErrors['materialsFiles'][] = 'Please keep at least one file (delete fewer or upload a replacement).';
        }

        if (!empty($fieldErrors) || !empty($errors)) {
            $campaignMaterial = $header;
            $documents        = $existingDocs;
            $filePath         = $this->fileHelper->getFilePath('EditCampaignMaterial');
            include $filePath;
            return;
        }

        // Update header
        $ok = $this->campaignMaterialModel->updateCampaignMaterial($id, [
            'materialsTitle'    => $materialsTitle,
            'materialsType'     => $materialsType,
            'materialsDesc'     => $materialsDesc,
            'materialsQuantity' => $materialsQuantity,
            'adminID'           => $_SESSION['adminID'] ?? null, // optional: track editor
        ]);
        if (!$ok) {
            \set_flash('danger', 'Failed to update campaign material.');
            $this->editCampaignMaterial($id);
            return;
        }

        // Delete selected docs (disk + DB)
        if (!empty($deleteIds)) {
            $this->removeCampaignDocsFromDiskAndDb($id, $deleteIds);
        }

        // Add new files (sequential names)
        $this->saveCampaignUploads($id, $_FILES['materialsFiles'] ?? null);

        \set_flash('success', 'Campaign material updated successfully.');
        header('Location: /campaign-material');
    }

    /**
     * Delete selected documents belonging to this application from disk and DB.
     */
    private function removeCampaignDocsFromDiskAndDb(int $materialsApplicationID, array $docIds): void
    {
        // Resolve /public path
        $publicBase = realpath(__DIR__ . '/../../public');
        if ($publicBase === false) {
            $publicBase = dirname(__DIR__, 3) . '/public';
        }
        $baseDir = rtrim($publicBase, DIRECTORY_SEPARATOR) . '/uploads/campaign_material/' . $materialsApplicationID;

        foreach ($docIds as $docId) {
            $doc = $this->campaignMaterialModel->findDocument((int)$docId);
            if (!$doc) continue;
            if ((int)$doc['materialsApplicationID'] !== $materialsApplicationID) continue;

            $file = $baseDir . '/' . $doc['materialsFilename'];
            if (is_file($file)) { @unlink($file); }
            $this->campaignMaterialModel->deleteDocument((int)$docId);
        }
    }

    // --------------------- View --------------------- //
    public function viewCampaignMaterial($materialsApplicationID)
    {
        $id = (int)$materialsApplicationID;

        $cm = $this->campaignMaterialModel->getCampaignMaterialById($id);
        if (!$cm) {
            \set_flash('danger', 'Campaign material not found.');
            header('Location: /campaign-material');
            return;
        }

        // Load docs
        $documents = $this->campaignMaterialModel->getDocumentsByApplication($id);

        // Build view-model
        $vm = [
            'id'             => $id,
            'eventTitle'     => (string)($cm['electionEventTitle'] ?? ''),
            'nomineeName'    => (string)($cm['nomineeFullName'] ?? ''),
            'title'          => (string)($cm['materialsTitle'] ?? ''),
            'type'           => (string)($cm['materialsType'] ?? ''),
            'desc'           => (string)($cm['materialsDesc'] ?? ''),
            'qty'            => (int)($cm['materialsQuantity'] ?? 0),
            'status'         => (string)($cm['materialsApplicationStatus'] ?? 'PENDING'),
            'adminID'        => $cm['adminID'] ?? null,
            'badgeClass'     => $this->badgeClass((string)($cm['materialsApplicationStatus'] ?? 'PENDING')),
            'docBaseUrl'     => "/uploads/campaign_material/{$id}/",
        ];

        // Normalize document rows for the table (url + preview flag already computed)
        $docRows = [];
        $i = 1;
        foreach ($documents as $d) {
            $fname = (string)($d['materialsFilename'] ?? '');
            $url   = $vm['docBaseUrl'] . rawurlencode($fname);
            $docRows[] = [
                'idx'      => $i++,
                'filename' => $fname,
                'url'      => $url,
                'isImage'  => $this->isImageFile($fname),
            ];
        }

        // Render
        $filePath = $this->fileHelper->getFilePath('ViewCampaignMaterial');
        if ($filePath && file_exists($filePath)) {
            // Expose only the data the view needs:
            $campaign = $vm;
            $docs     = $docRows;
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }

    /** Map status to badge class */
    private function badgeClass(string $status): string
    {
        $s = strtoupper($status);
        return match ($s) {
            'APPROVED' => 'bg-success',
            'REJECTED' => 'bg-danger',
            'PENDING'  => 'bg-warning text-dark',
            default    => 'bg-secondary',
        };
    }

    /** Detect image by extension */
    private function isImageFile(string $fname): bool
    {
        $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg','jpeg','png','gif','webp'], true);
    }


    // --------------------- Accept / Reject --------------------- //
    public function acceptCampaignMaterial($materialsApplicationID)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /campaign-material");
            return;
        }

        $this->campaignMaterialModel->acceptCampaignMaterial($materialsApplicationID,1);
        \set_flash('success', 'Campaign Material accepted successfully.');
        header("Location: /campaign-material");
    }

    public function rejectCampaignMaterial($materialsApplicationID)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /campaign-material'");
            return;
        }

        $this->campaignMaterialModel->rejectCampaignMaterial($materialsApplicationID,1);
        \set_flash('success', 'Campaign Material rejected successfully.');
        header("Location: /campaign-material");
    }

}
