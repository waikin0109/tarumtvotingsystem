<?php
$_title = 'Edit Announcement';
require_once __DIR__ . '/../AdminView/adminHeader.php';

$announcement = $announcement ?? ['id' => 0, 'title' => '', 'content' => '', 'status' => 'DRAFT', 'publishAtLocal' => ''];
$attachments = $attachments ?? [];
$fieldErrors = $fieldErrors ?? [];
$id = (int) ($announcement['id'] ?? 0);
$status = strtoupper($announcement['status'] ?? 'DRAFT');

// default publish mode on edit: keep as draft
$publishMode = $_POST['publishMode'] ?? 'draft';
$publishAtLocal = $_POST['publishAtLocal'] ?? ($announcement['publishAtLocal'] ?? '');
?>

<style>
    .file-wrapper {
        position: relative
    }

    .file-overlay {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        left: 138px;
        right: 12px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        pointer-events: none;
        color: #6c757d;
        display: none;
        font-size: .95rem
    }

    #filePicker.has-files {
        color: transparent
    }

    #filePicker::file-selector-button {
        width: 130px
    }
</style>

<div class="container mt-4 mb-5">
    <div class="d-flex align-items-center justify-content-between">
        <h2 class="mb-3">Edit Announcement</h2>
        <span
            class="badge <?= $status === 'DRAFT' ? 'bg-secondary' : ($status === 'SCHEDULED' ? 'bg-warning text-dark' : 'bg-success') ?>">
            <?= htmlspecialchars($status) ?>
        </span>
    </div>

    <form action="/announcement/edit/<?= $id ?>" method="POST" enctype="multipart/form-data" id="editForm" novalidate>
        <!-- Title -->
        <div class="mb-3">
            <label for="title" class="form-label">Title</label>
            <input type="text" id="title" name="title"
                class="form-control <?= !empty($fieldErrors['title']) ? 'is-invalid' : '' ?>"
                value="<?= htmlspecialchars($announcement['title'] ?? '') ?>">
            <?php if (!empty($fieldErrors['title'])): ?>
                <div class="invalid-feedback">
                    <?= htmlspecialchars(implode(' ', $fieldErrors['title'])) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Content -->
        <div class="mb-3">
            <label for="content" class="form-label">Content</label>
            <textarea id="content" name="content" rows="6"
                class="form-control <?= !empty($fieldErrors['content']) ? 'is-invalid' : '' ?>"><?= htmlspecialchars($announcement['content'] ?? '') ?></textarea>
            <?php if (!empty($fieldErrors['content'])): ?>
                <div class="invalid-feedback">
                    <?= htmlspecialchars(implode(' ', $fieldErrors['content'])) ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($status === 'DRAFT'): ?>
            <fieldset class="mb-3">
                <legend class="fs-6">Publish</legend>

                <div class="form-check">
                    <input class="form-check-input" type="radio" name="publishMode" id="pmDraft" value="draft"
                        <?= $publishMode === 'draft' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="pmDraft">Save as Draft</label>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="radio" name="publishMode" id="pmNow" value="now"
                        <?= $publishMode === 'now' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="pmNow">Publish Now</label>
                </div>

                <div class="form-check d-flex align-items-center gap-2 mt-2">
                    <input class="form-check-input" type="radio" name="publishMode" id="pmSchedule" value="schedule"
                        <?= $publishMode === 'schedule' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="pmSchedule">Publish At</label>

                    <input type="datetime-local" id="publishAtLocal" name="publishAtLocal" style="max-width:270px;"
                        class="form-control <?= !empty($fieldErrors['publishAt']) ? 'is-invalid' : '' ?>"
                        value="<?= htmlspecialchars($publishAtLocal) ?>" <?= $publishMode === 'schedule' ? '' : 'disabled' ?>>
                    <input type="hidden" id="publishAt" name="publishAt" value="">
                </div>

                <?php if (!empty($fieldErrors['publishAt'])): ?>
                    <div id="publishAtServerErr" class="invalid-feedback d-block">
                        <?= htmlspecialchars(implode(' ', $fieldErrors['publishAt'])) ?>
                    </div>
                <?php endif; ?>
            </fieldset>
        <?php endif; ?>

        <!-- Existing attachments -->
        <?php if (!empty($attachments)): ?>
            <div class="mb-3">
                <label class="form-label">Current Attachment(s)</label>
                <div class="list-group" id="existingAttachments">
                    <?php foreach ($attachments as $f):
                        $attachId = (int) ($f['attachmentID'] ?? 0);
                        $fileName = $f['original'] ?? ($f['stored'] ?? ('file-' . $attachId));
                        $hidId = 'rm-' . $attachId;
                        ?>
                        <div class="list-group-item d-flex align-items-center justify-content-between"
                            data-row-id="<?= $attachId ?>">
                            <div class="me-3 attach-name">
                                <a href="<?= htmlspecialchars($f['fileUrl']) ?>" target="_blank" rel="noopener">
                                    <?= htmlspecialchars($fileName) ?>
                                </a>
                                <?php if (!empty($f['fileType'])): ?>
                                    <small class="text-muted ms-2">(<?= htmlspecialchars($f['fileType']) ?>)</small>
                                <?php endif; ?>
                            </div>

                            <!-- Hidden checkbox that Save will submit -->
                            <input type="checkbox" class="d-none" id="<?= $hidId ?>" name="remove_ids[]"
                                value="<?= $attachId ?>">

                            <!-- Toggle button to mark/unmark for deletion -->
                            <button type="button" class="btn btn-sm btn-outline-danger" data-toggle-remove="<?= $hidId ?>"
                                aria-pressed="false" title="Mark this file to be removed on Save">
                                Delete
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-text">Tip: Click “Delete” to mark files. They will be removed when you press Save. Click
                    again to undo.</div>
            </div>
        <?php endif; ?>

        <!-- Add new attachments -->
        <div class="mb-3">
            <label class="form-label">Add Attachment(s)</label>

            <div class="file-wrapper">
                <input type="file" id="filePicker"
                    class="form-control <?= !empty($fieldErrors['attachments']) ? 'is-invalid' : '' ?>" multiple
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" aria-describedby="fileOverlay">
                <span id="fileOverlay" class="file-overlay" aria-live="polite">No file chosen</span>
            </div>

            <!-- hidden master input that actually submits -->
            <input type="file" id="attachments" name="attachments[]" class="d-none" multiple>

            <?php if (!empty($fieldErrors['attachments'])): ?>
                <div class="invalid-feedback d-block">
                    <ul class="mb-0">
                        <?php foreach ($fieldErrors['attachments'] as $m): ?>
                            <li><?= htmlspecialchars($m) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div id="attachmentList" class="list-group mt-2 d-none"></div>
            <div class="form-text">You can select multiple files now and click again to add more. Click “Delete” to
                remove a
                file.</div>
        </div>

        <div class="d-flex justify-content-center gap-3">
            <a href="/announcements" class="btn btn-outline-secondary px-4">Cancel</a>
            <button type="submit" class="btn btn-primary px-4">Save</button>
        </div>
    </form>

    <?php if (!empty($attachments)): ?>
        <!-- Standalone delete forms (outside the main form) -->
        <?php foreach ($attachments as $f):
            $attachId = (int) ($f['attachmentID'] ?? 0);
            $formId = 'del-att-' . $attachId;
            ?>
            <form id="<?= $formId ?>" method="post" action="/announcement/attachment/delete">
                <input type="hidden" name="attachment_id" value="<?= $attachId ?>">
                <input type="hidden" name="announcement_id" value="<?= $id ?>">
                <input type="hidden" name="return_to" value="/announcement/edit/<?= $id ?>">
            </form>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Attachments UI + Publish sync -->
<script>
    (function () {
        // ====== Attachments UX ======
        const MAX_TOTAL = 40 * 1024 * 1024, MAX_FILES = 20, PER_FILE_MAX = 10 * 1024 * 1024;
        const picker = document.getElementById('filePicker');
        const overlay = document.getElementById('fileOverlay');
        const master = document.getElementById('attachments');
        const list = document.getElementById('attachmentList');
        let filesStore = [];

        const fmt = b => b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : b >= 1024 ? Math.max(1, Math.round(b / 1024)) + ' KB' : b + ' B';
        const esc = s => s.replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        function updateOverlay() {
            if (!filesStore.length) { picker.classList.remove('has-files'); overlay.style.display = 'none'; overlay.textContent = 'No file chosen'; return; }
            const total = filesStore.reduce((a, f) => a + f.size, 0);
            overlay.textContent = `${filesStore.length} file${filesStore.length !== 1 ? 's' : ''} selected (total ${fmt(total)})`;
            picker.classList.add('has-files'); overlay.style.display = 'block';
        }
        function renderList() {
            list.innerHTML = ''; if (!filesStore.length) { list.classList.add('d-none'); return; }
            list.classList.remove('d-none');
            filesStore.forEach((f, i) => {
                const row = document.createElement('div');
                row.className = 'list-group-item d-flex justify-content-between align-items-center';
                row.innerHTML = `<div>${esc(f.name)} <span class="text-muted">(${fmt(f.size)})</span></div>
                     <button type="button" class="btn btn-sm btn-outline-danger" data-remove="${i}" aria-label="Remove">Delete</button>`;
                list.appendChild(row);
            });
        }
        function rebuild() {
            const dt = new DataTransfer(); filesStore.forEach(f => dt.items.add(f)); master.files = dt.files;
            renderList(); updateOverlay();
        }
        if (picker) {
            picker.addEventListener('change', () => {
                const incoming = Array.from(picker.files || []); if (!incoming.length) return;
                const tooBig = incoming.find(f => f.size > PER_FILE_MAX);
                if (tooBig) { alert(`"${tooBig.name}" exceeds 10 MB.`); picker.value = ''; return; }
                const merged = filesStore.slice(); const keys = new Set(merged.map(f => `${f.name}|${f.size}|${f.lastModified}`));
                for (const f of incoming) { const k = `${f.name}|${f.size}|${f.lastModified}`; if (!keys.has(k)) { merged.push(f); keys.add(k); } }
                if (merged.length > MAX_FILES) { alert(`Max ${MAX_FILES} files.`); picker.value = ''; return; }
                const totalAfter = merged.reduce((a, f) => a + f.size, 0);
                if (totalAfter > MAX_TOTAL) { alert('Total exceeds 40 MB.'); picker.value = ''; return; }
                filesStore = merged; picker.value = ''; rebuild();
            });
        }
        if (list) {
            list.addEventListener('click', e => {
                const btn = e.target.closest('[data-remove]'); if (!btn) return;
                const i = +btn.getAttribute('data-remove'); if (Number.isNaN(i)) return;
                filesStore.splice(i, 1); rebuild();
            });
        }
        updateOverlay();

        // ====== Publish toggling ======
        const pmDraft = document.getElementById('pmDraft');
        const pmNow = document.getElementById('pmNow');
        const pmSchedule = document.getElementById('pmSchedule');
        const localInput = document.getElementById('publishAtLocal');
        const mysqlHidden = document.getElementById('publishAt');

        function toMysql(dt) { return dt ? dt.replace('T', ' ') + ':00' : ''; }

        function toggleSchedule() {
            if (!localInput) return;
            const on = pmSchedule && pmSchedule.checked;
            localInput.disabled = !on;

            if (on) {
                const d = new Date(); d.setSeconds(0, 0);
                const pad = n => String(n).padStart(2, '0');
                localInput.min =
                    `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
            }
            localInput.classList.remove('is-invalid');
            const err = document.getElementById('publishAtClientErr'); if (err) err.remove();
        }

        if (pmDraft) pmDraft.addEventListener('change', toggleSchedule);
        if (pmNow) pmNow.addEventListener('change', toggleSchedule);
        if (pmSchedule) pmSchedule.addEventListener('change', toggleSchedule);
        toggleSchedule();

        if (localInput) {
            localInput.addEventListener('input', function () {
                mysqlHidden.value = toMysql(localInput.value);
                localInput.classList.remove('is-invalid');
                const err = document.getElementById('publishAtClientErr'); if (err) err.remove();
            });
        }

        document.getElementById('editForm').addEventListener('submit', function () {
            if (localInput && mysqlHidden) {
                mysqlHidden.value = (pmSchedule && pmSchedule.checked) ? toMysql(localInput.value || '') : '';
            }
        });
    })();
</script>

<script>
    (function () {
        // Toggle "mark for deletion" for existing attachments
        const container = document.getElementById('existingAttachments');
        if (!container) return;

        container.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-toggle-remove]');
            if (!btn) return;

            const hidId = btn.getAttribute('data-toggle-remove');
            const chk = document.getElementById(hidId);
            const row = btn.closest('.list-group-item');
            if (!chk || !row) return;

            chk.checked = !chk.checked;

            // Visual feedback
            btn.setAttribute('aria-pressed', chk.checked ? 'true' : 'false');
            btn.classList.toggle('btn-danger', chk.checked);
            btn.classList.toggle('btn-outline-danger', !chk.checked);
            btn.textContent = chk.checked ? 'Undo' : 'Delete';

            // Strike-through the filename when marked
            row.querySelector('.attach-name')?.classList.toggle('text-decoration-line-through', chk.checked);
            row.classList.toggle('opacity-75', chk.checked);
        });
    })();
</script>

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>