<?php
$_title = 'Create Announcement';
require_once __DIR__ . '/../AdminView/adminHeader.php';

$announcementCreationData = $announcementCreationData ?? [
  'title' => '',
  'content' => '',
  'publishMode' => 'draft',
  'publishAtLocal' => ''
];
$fieldErrors = $fieldErrors ?? [];
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

<div class="container-fluid mt-4 mb-5">
  <h2>Create Announcement</h2>

  <form action="/announcement/store" method="POST" enctype="multipart/form-data" id="annForm" class="mt-3" novalidate>
    <!-- Title -->
    <div class="mb-3">
      <label for="title" class="form-label">Title</label>
      <input type="text" id="title" name="title"
        class="form-control <?= !empty($fieldErrors['title']) ? 'is-invalid' : '' ?>"
        value="<?= htmlspecialchars($announcementCreationData['title'] ?? '') ?>">
      <?php if (!empty($fieldErrors['title'])): ?>
        <div id="titleServerErr" class="invalid-feedback">
          <?= htmlspecialchars(implode(' ', $fieldErrors['title'])) ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Content -->
    <div class="mb-3">
      <label for="content" class="form-label">Content</label>
      <textarea id="content" name="content" rows="5"
        class="form-control <?= !empty($fieldErrors['content']) ? 'is-invalid' : '' ?>"><?= htmlspecialchars($announcementCreationData['content'] ?? '') ?></textarea>
      <?php if (!empty($fieldErrors['content'])): ?>
        <div id="contentServerErr" class="invalid-feedback">
          <?= htmlspecialchars(implode(' ', $fieldErrors['content'])) ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Publish -->
    <fieldset class="mb-3">
      <legend class="fs-6">Publish</legend>

      <div class="form-check">
        <input class="form-check-input" type="radio" name="publishMode" id="pmDraft" value="draft"
          <?= ($announcementCreationData['publishMode'] ?? 'draft') === 'draft' ? 'checked' : '' ?>>
        <label class="form-check-label" for="pmDraft">Save as Draft</label>
      </div>

      <div class="form-check">
        <input class="form-check-input" type="radio" name="publishMode" id="pmNow" value="now"
          <?= ($announcementCreationData['publishMode'] ?? '') === 'now' ? 'checked' : '' ?>>
        <label class="form-check-label" for="pmNow">Publish Now</label>
      </div>

      <div class="form-check d-flex align-items-center gap-2 mt-2">
        <input class="form-check-input" type="radio" name="publishMode" id="pmSchedule" value="schedule"
          <?= ($announcementCreationData['publishMode'] ?? '') === 'schedule' ? 'checked' : '' ?>>
        <label class="form-check-label" for="pmSchedule">Publish At</label>

        <!-- visible picker -->
        <input type="datetime-local" id="publishAtLocal" name="publishAtLocal" style="max-width:270px;"
          class="form-control <?= !empty($fieldErrors['publishAt']) ? 'is-invalid' : '' ?>"
          value="<?= htmlspecialchars($announcementCreationData['publishAtLocal'] ?? '') ?>"
          <?= ($announcementCreationData['publishMode'] ?? 'draft') === 'schedule' ? '' : 'disabled' ?>>
        <!-- hidden mysql-ready -->
        <input type="hidden" id="publishAt" name="publishAt" value="">
        <small id="publishPreview" class="text-muted ms-2"></small>
      </div>

      <?php if (!empty($fieldErrors['publishAt'])): ?>
        <div id="publishAtServerErr" class="invalid-feedback d-block">
          <?= htmlspecialchars(implode(' ', $fieldErrors['publishAt'])) ?>
        </div>
      <?php endif; ?>
    </fieldset>

    <div class="mb-3">
      <label class="form-label">Attachments</label>
      <div class="file-wrapper">
        <input type="file" id="filePicker" class="form-control" multiple
          accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" aria-describedby="fileOverlay">
        <span id="fileOverlay" class="file-overlay" aria-live="polite">No file chosen</span>
      </div>

      <input type="file" id="attachments" name="attachments[]" class="d-none" multiple>
      <div id="attachmentList" class="list-group mt-2 d-none"></div>
      <div class="form-text">You can select multiple files now, and click again to add more. Click “Delete” to remove a
        file.
      </div>
    </div>

    <div class="d-flex justify-content-center gap-3 mt-4">
      <a href="/announcements" class="btn btn-outline-secondary px-4">Cancel</a>
      <button type="submit" class="btn btn-primary px-4">Create</button>
    </div>
  </form>
</div>

<script>
  (function () {
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
    list.addEventListener('click', e => {
      const btn = e.target.closest('[data-remove]'); if (!btn) return;
      const i = +btn.getAttribute('data-remove'); if (Number.isNaN(i)) return;
      filesStore.splice(i, 1); rebuild();
    });
    updateOverlay();
  })();
</script>

<script>
  (function () {
    const pmDraft = document.getElementById('pmDraft');
    const pmNow = document.getElementById('pmNow');
    const pmSchedule = document.getElementById('pmSchedule');
    const localInput = document.getElementById('publishAtLocal'); // yyyy-MM-ddTHH:mm
    const mysqlHidden = document.getElementById('publishAt');      // yyyy-MM-dd HH:mm:ss

    // helper: to MySQL DATETIME
    function toMysql(dt) { return dt ? dt.replace('T', ' ') + ':00' : ''; }

    // enable/disable the picker WITHOUT pre-filling a value
    function toggleSchedule() {
      const on = pmSchedule.checked;
      localInput.disabled = !on;

      if (on) {
        // allow only future times, but do NOT set a value
        const d = new Date(); d.setSeconds(0, 0);
        const pad = n => String(n).padStart(2, '0');
        localInput.min =
          `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;

        // clear current values so user must choose
        // localInput.value = '';
        // mysqlHidden.value = '';
      } else {
        mysqlHidden.value = '';
      }

      // clear any client-side invalid state (if left over)
      localInput.classList.remove('is-invalid');
      const err = document.getElementById('publishAtClientErr');
      if (err) err.remove();
    }

    // keep hidden field in sync when user picks a value
    localInput.addEventListener('input', function () {
      mysqlHidden.value = toMysql(localInput.value);
      localInput.classList.remove('is-invalid');
      const err = document.getElementById('publishAtClientErr');
      if (err) err.remove();
    });

    [pmDraft, pmNow, pmSchedule].forEach(r => r && r.addEventListener('change', toggleSchedule));
    toggleSchedule(); // set initial state

    document.getElementById('annForm').addEventListener('submit', function () {
      const err = document.getElementById('publishAtClientErr');
      if (err) err.remove();
      localInput.classList.remove('is-invalid');

      // sync hidden field (empty when not scheduling)
      mysqlHidden.value = pmSchedule.checked ? toMysql(localInput.value || '') : '';
    });
  })();
</script>

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>