<?php
$_title = "Edit Nominee Application";
require_once __DIR__ . '/../AdminView/adminHeader.php';

/**
 * Expects:
 * $nomineeApplicationData (array)
 * $renderAttrs (array of [code,label,type])
 * $errors (list), $fieldErrors (array), $old (array)
 * $documents (list)
 */
function invalid(array $fe, string $code){ return !empty($fe[$code]) ? ' is-invalid' : ''; }
$na = $nomineeApplicationData ?? [];

/** Categorize file based on stored filename prefix */
function cat_from_filename(string $fname): string {
    $n = strtolower($fname);
    if (str_starts_with($n, 'cgpa_')) return 'cgpa';
    if (str_starts_with($n, 'achievement_')) return 'achievements';
    if (str_starts_with($n, 'behaviorreport_')) return 'behaviorreport';
    return 'other';
}
?>

<div class="container-fluid mt-4 mb-5">
    <!-- Page header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Edit Nominee Application</h2>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="/admin/nominee-application/edit/<?= (int)$na['nomineeApplicationID'] ?>"
                  method="POST"
                  id="appForm"
                  novalidate
                  enctype="multipart/form-data">

                <!-- Section: Registration Form & Student -->
                <h5 class="mb-3">Registration Form & Applicant</h5>

                <!-- Registration Form (read-only) -->
                <div class="mb-3">
                    <label class="form-label">Registration Form</label>
                    <div class="form-control-plaintext">
                        <?= htmlspecialchars(($na['registrationFormTitle'] ?? '') . ' (ID ' . $na['registrationFormID'] . ')') ?>
                    </div>
                    <input type="hidden" name="registrationFormID" value="<?= (int)$na['registrationFormID'] ?>">
                </div>

                <!-- Student -->
                <div class="mb-4">
                    <label class="form-label">Student</label>
                    <div class="form-control-plaintext">
                        <?= htmlspecialchars(($na['student_fullname'] ?? '') . ' (Student ID ' . $na['studentID'] . ')') ?>
                    </div>
                    <input type="hidden" name="studentID" value="<?= (int)$na['studentID'] ?>">
                </div>

                <!-- Section: Application Details -->
                <h5 class="mb-3">Application Details</h5>

                <!-- Dynamic attributes -->
                <div id="dynamic-fields">
                    <?php foreach ($renderAttrs as $a):
                        $code  = $a['code'];
                        $type  = $a['type'];
                        $label = $a['label'];
                        $v     = $old['fields'][$code] ?? ($type === 'checkbox' ? 0 : '');
                    ?>
                        <div class="mb-3">
                            <label class="form-label" for="f-<?= htmlspecialchars($code) ?>">
                                <?= htmlspecialchars($label) ?>
                            </label>

                            <?php if ($type === 'number'): ?>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="4"
                                    class="form-control<?= invalid($fieldErrors, $code) ?>"
                                    id="f-<?= $code ?>"
                                    name="fields[<?= $code ?>]"
                                    value="<?= htmlspecialchars((string)$v) ?>"
                                >

                            <?php elseif ($type === 'textarea'): ?>
                                <textarea
                                    id="f-<?= $code ?>"
                                    name="fields[<?= $code ?>]"
                                    rows="4"
                                    class="form-control<?= invalid($fieldErrors, $code) ?>"
                                ><?= htmlspecialchars((string)$v) ?></textarea>

                            <?php elseif ($type === 'checkbox'): ?>
                                <div class="form-check">
                                    <input
                                        class="form-check-input<?= invalid($fieldErrors, $code) ?>"
                                        type="checkbox"
                                        id="f-<?= $code ?>"
                                        name="fields[<?= $code ?>]"
                                        <?= ((int)$v === 1) ? 'checked' : '' ?>
                                    >
                                    <label class="form-check-label" for="f-<?= $code ?>">Yes</label>
                                </div>

                            <?php else: ?>
                                <input
                                    type="text"
                                    class="form-control<?= invalid($fieldErrors, $code) ?>"
                                    id="f-<?= $code ?>"
                                    name="fields[<?= $code ?>]"
                                    value="<?= htmlspecialchars((string)$v) ?>"
                                >
                            <?php endif; ?>

                            <!-- Upload blocks per attribute -->
                            <?php if ($code === 'cgpa'): ?>
                                <div class="mt-2">
                                    <label class="form-label small">
                                        Upload CGPA proof (JPG/JPEG only) — optional (keeps old unless you delete below)
                                    </label>
                                    <input
                                        type="file"
                                        accept=".jpg,.jpeg"
                                        class="form-control<?= invalid($fieldErrors,'cgpa_file') ?>"
                                        name="uploads[cgpa]"
                                    >
                                    <?php if (!empty($fieldErrors['cgpa_file'])): ?>
                                        <div class="invalid-feedback d-block">
                                            <?= htmlspecialchars(implode(' ', $fieldErrors['cgpa_file'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                            <?php elseif ($code === 'achievements'): ?>
                                <div class="mt-2">
                                    <label class="form-label small">
                                        Upload additional achievement documents (JPG/JPEG only, multiple allowed)
                                    </label>
                                    <input
                                        type="file"
                                        accept=".jpg,.jpeg"
                                        multiple
                                        class="form-control<?= invalid($fieldErrors,'achievements_files') ?>"
                                        name="uploads[achievements][]"
                                    >
                                    <?php if (!empty($fieldErrors['achievements_files'])): ?>
                                        <div class="invalid-feedback d-block">
                                            <?= htmlspecialchars(implode(' ', $fieldErrors['achievements_files'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                            <?php elseif ($code === 'behaviorreport'): ?>
                                <div class="mt-2">
                                    <label class="form-label small">
                                        Upload behavior report (JPG/JPEG only) — optional
                                    </label>
                                    <input
                                        type="file"
                                        accept=".jpg,.jpeg"
                                        class="form-control<?= invalid($fieldErrors,'behaviorreport_file') ?>"
                                        name="uploads[behaviorreport]"
                                    >
                                    <?php if (!empty($fieldErrors['behaviorreport_file'])): ?>
                                        <div class="invalid-feedback d-block">
                                            <?= htmlspecialchars(implode(' ', $fieldErrors['behaviorreport_file'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($fieldErrors[$code])): ?>
                                <div class="invalid-feedback d-block">
                                    <?= htmlspecialchars(implode(' ', $fieldErrors[$code])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Section: Existing Documents -->
                <h5 class="mb-3 mt-4">Existing Documents</h5>

                <div class="mb-4">
                    <?php if (!empty($documents)): ?>
                        <div class="list-group">
                            <?php foreach ($documents as $doc): ?>
                                <label class="list-group-item d-flex align-items-center justify-content-between">
                                    <span class="me-3">
                                        <?= htmlspecialchars($doc['academicFilename']) ?>
                                    </span>
                                    <span class="d-flex align-items-center gap-3">
                                        <a class="btn btn-sm btn-outline-secondary"
                                           href="/uploads/academic_document/<?= (int)$na['applicationSubmissionID'] ?>/<?= rawurlencode($doc['academicFilename']) ?>"
                                           target="_blank" rel="noopener">
                                            View
                                        </a>
                                        <div class="form-check m-0">
                                            <input
                                                class="form-check-input doc-del"
                                                type="checkbox"
                                                data-category="<?= htmlspecialchars(cat_from_filename($doc['academicFilename'])) ?>"
                                                id="del-<?= (int)$doc['academicID'] ?>"
                                                name="delete_docs[]"
                                                value="<?= (int)$doc['academicID'] ?>"
                                            >
                                            <label class="form-check-label" for="del-<?= (int)$doc['academicID'] ?>">
                                                Delete
                                            </label>
                                        </div>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="form-control-plaintext text-muted">
                            No documents uploaded yet.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="d-flex justify-content-end gap-2 pt-2">
                    <a class="btn btn-outline-secondary" href="/admin/nominee-application">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    function qsa(sel){ return Array.from(document.querySelectorAll(sel)); }

    function countChecked(cat){
        return qsa('.doc-del[data-category="'+cat+'"]:checked').length;
    }
    function countTotal(cat){
        return qsa('.doc-del[data-category="'+cat+'"]').length;
    }
    function hasNewUpload(cat){
        if (cat === 'achievements')
            return (document.querySelector('input[name="uploads[achievements][]"]')?.files?.length || 0) > 0;
        if (cat === 'cgpa')
            return (document.querySelector('input[name="uploads[cgpa]"]')?.files?.length || 0) > 0;
        if (cat === 'behaviorreport')
            return (document.querySelector('input[name="uploads[behaviorreport]"]')?.files?.length || 0) > 0;
        return false;
    }

    qsa('.doc-del').forEach(function(chk){
        chk.addEventListener('change', function(){
            const cat    = this.dataset.category || 'other';
            const total  = countTotal(cat);
            const checked = countChecked(cat);

            // block deleting the last file unless a replacement upload is chosen
            if (total > 0 && checked === total && !hasNewUpload(cat)) {
                this.checked = false;
                alert('You must keep at least one ' + cat + ' document or upload a replacement first.');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>
