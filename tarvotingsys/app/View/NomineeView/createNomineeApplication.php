<?php
$_title = "Create Nominee Application (Admin)";
require_once __DIR__ . '/../AdminView/adminHeader.php';

/** expects:
 * $forms (list), $selectedForm (int), $renderAttrs (array), $errors, $fieldErrors, $old, $students (list)
 */
function invalid(array $fe, string $code){ return !empty($fe[$code]) ? ' is-invalid' : ''; }
?>
<div class="container mt-4"><h2>Create Nominee Application</h2>

  <form action="/admin/nominee-application/create" method="POST" id="appForm" novalidate enctype="multipart/form-data">

    <!-- Select Registration Form -->
    <div class="mb-3">
      <label for="registrationFormID" class="form-label">Registration Form</label>
      <select class="form-select <?= invalid($fieldErrors,'registrationFormID') ?>" id="registrationFormID" name="registrationFormID" required>
        <option value="">-- Select a Registration Form --</option>
        <?php foreach ($forms as $f): ?>
          <option value="<?= (int)$f['registrationFormID'] ?>"
            <?= ((int)$selectedForm === (int)$f['registrationFormID']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($f['registrationFormTitle'].' (ID '.$f['registrationFormID'].')') ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (!empty($fieldErrors['registrationFormID'])): ?>
        <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['registrationFormID'])) ?></div>
      <?php endif; ?>
    </div>

    <!-- Student (searchable dropdown by name, scrollable, submits numeric studentID) -->
    <div class="mb-3">
      <label for="studentName" class="form-label">Student</label>

      <div class="position-relative">
        <input
          type="text"
          class="form-control<?= invalid($fieldErrors,'studentID') ?>"
          id="studentName"
          placeholder="Type a name or ID…"
          autocomplete="off"
          value="<?= htmlspecialchars($old['studentName'] ?? '') ?>"
          required
        >
        <!-- dropdown -->
        <div id="studentDropdown" class="dropdown-menu w-100 p-0" style="max-height: 200px; overflow-y: auto;">
          <?php foreach (($students ?? []) as $s): ?>
            <button type="button"
                    class="dropdown-item d-flex justify-content-between align-items-center"
                    data-id="<?= (int)$s['studentID'] ?>"
                    data-text="<?= htmlspecialchars($s['fullName'].' (ID '.$s['studentID'].')') ?>">
              <span><?= htmlspecialchars($s['fullName']) ?></span>
              <small class="text-muted">ID <?= (int)$s['studentID'] ?></small>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Hidden numeric studentID actually submitted to PHP -->
      <input type="hidden" name="studentID" id="studentID"
             value="<?= htmlspecialchars((string)($old['studentID'] ?? '')) ?>">

      <?php if (!empty($fieldErrors['studentID'])): ?>
        <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['studentID'])) ?></div>
      <?php endif; ?>
      <div class="form-text">Start typing the student’s name (or ID). Select from the list. The list shows ~5 rows and scrolls for more.</div>
    </div>

    <!-- Dynamic attributes -->
    <div id="dynamic-fields">
      <?php foreach ($renderAttrs as $a):
            $code = $a['code']; $type = $a['type']; $label = $a['label'];
            $v = $old['fields'][$code] ?? ($type==='checkbox' ? 0 : '');
      ?>
        <div class="mb-3">
          <label class="form-label" for="f-<?= $code ?>"><?= htmlspecialchars($label) ?></label>

          <?php if ($type === 'number'): ?>
            <input type="number" step="0.01" min="0" max="4"
                   class="form-control<?= invalid($fieldErrors, $code) ?>"
                   id="f-<?= $code ?>" name="fields[<?= $code ?>]"
                   value="<?= htmlspecialchars((string)$v) ?>">

          <?php elseif ($type === 'textarea'): ?>
            <textarea id="f-<?= $code ?>" name="fields[<?= $code ?>]" rows="4"
                      class="form-control<?= invalid($fieldErrors, $code) ?>"><?= htmlspecialchars((string)$v) ?></textarea>

          <?php elseif ($type === 'checkbox'): ?>
            <div class="form-check">
              <input class="form-check-input<?= invalid($fieldErrors, $code) ?>" type="checkbox"
                     id="f-<?= $code ?>" name="fields[<?= $code ?>]" <?= ($v==1)?'checked':'' ?>>
              <label class="form-check-label" for="f-<?= $code ?>">Yes</label>
            </div>

          <?php else: ?>
            <input type="text" class="form-control<?= invalid($fieldErrors, $code) ?>"
                   id="f-<?= $code ?>" name="fields[<?= $code ?>]"
                   value="<?= htmlspecialchars((string)$v) ?>">
          <?php endif; ?>

          <?php if ($code === 'cgpa'): ?>
            <div class="mt-2">
              <label class="form-label small">Upload CGPA proof (JPG/JPEG only)</label>
              <input type="file" accept=".jpg,.jpeg"
                     class="form-control<?= invalid($fieldErrors,'cgpa_file') ?>" name="uploads[cgpa]">
              <?php if (!empty($fieldErrors['cgpa_file'])): ?>
                <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['cgpa_file'])) ?></div>
              <?php endif; ?>
            </div>
          <?php elseif ($code === 'achievements'): ?>
            <div class="mt-2">
              <label class="form-label small">Upload achievement documents (JPG/JPEG only, multiple allowed)</label>
              <input type="file" accept=".jpg,.jpeg" multiple
                     class="form-control<?= invalid($fieldErrors,'achievements_files') ?>" name="uploads[achievements][]">
              <?php if (!empty($fieldErrors['achievements_files'])): ?>
                <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['achievements_files'])) ?></div>
              <?php endif; ?>
            </div>
          <?php elseif ($code === 'behaviorreport'): ?>
            <div class="mt-2">
              <label class="form-label small">Upload behavior report (JPG/JPEG only)</label>
              <input type="file" accept=".jpg,.jpeg"
                     class="form-control<?= invalid($fieldErrors,'behaviorreport_file') ?>" name="uploads[behaviorreport]">
              <?php if (!empty($fieldErrors['behaviorreport_file'])): ?>
                <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['behaviorreport_file'])) ?></div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($fieldErrors[$code])): ?>
            <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors[$code])) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <button type="submit" class="btn btn-primary">Create Application</button>
    <a class="btn btn-outline-secondary" href="/admin/nominee-application/create">Reset</a>
  </form>
</div>

<!-- Keep it simple: reload the page with the selected registrationFormID -->
<script>
document.getElementById('registrationFormID')?.addEventListener('change', function(){
  const id = this.value;
  if(!id){ window.location = '/admin/nominee-application/create'; return; }
  window.location = '/admin/nominee-application/create?registrationFormID=' + encodeURIComponent(id);
});

/** Student dropdown logic (no library) */
(function(){
  const input = document.getElementById('studentName');
  const hidden = document.getElementById('studentID');
  const menu = document.getElementById('studentDropdown');
  const items = Array.from(menu.querySelectorAll('.dropdown-item'));

  let filtered = items.slice();
  let activeIndex = -1;

  function openMenu(){
    if (!menu.classList.contains('show')) {
      menu.classList.add('show');
    }
  }
  function closeMenu(){
    menu.classList.remove('show');
    activeIndex = -1;
    highlightActive();
  }
  function highlightActive() {
    filtered.forEach((btn, i) => {
      btn.classList.toggle('active', i === activeIndex);
    });
    // ensure active stays in view
    if (activeIndex >= 0 && filtered[activeIndex]) {
      const el = filtered[activeIndex];
      const top = el.offsetTop;
      const bottom = top + el.offsetHeight;
      if (menu.scrollTop > top) menu.scrollTop = top;
      else if (menu.scrollTop + menu.clientHeight < bottom) menu.scrollTop = bottom - menu.clientHeight;
    }
  }
  function renderFilter() {
    const q = input.value.trim().toLowerCase();
    filtered = [];
    items.forEach(btn => {
      const text = (btn.getAttribute('data-text') || btn.textContent || '').toLowerCase();
      const idText = (btn.getAttribute('data-id') || '').toLowerCase();
      const ok = !q || text.includes(q) || idText.includes(q);
      btn.style.display = ok ? '' : 'none';
      if (ok) filtered.push(btn);
    });
    // show menu if we have any result
    if (filtered.length > 0) openMenu(); else closeMenu();
    // reset active
    activeIndex = (filtered.length > 0) ? 0 : -1;
    highlightActive();
  }
  function choose(btn){
    if (!btn) return;
    const id = btn.getAttribute('data-id') || '';
    const display = btn.getAttribute('data-text') || btn.textContent || '';
    input.value = display;
    hidden.value = id;
    input.classList.remove('is-invalid');
    closeMenu();
  }

  // events
  input.addEventListener('input', function(){
    hidden.value = ''; // clear until a valid pick
    renderFilter();
  });
  input.addEventListener('focus', function(){
    renderFilter();
  });
  input.addEventListener('blur', function(){
    // small delay so click on item works before closing
    setTimeout(closeMenu, 120);
  });

  // Keyboard navigation
  input.addEventListener('keydown', function(e){
    if (!menu.classList.contains('show')) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (filtered.length) {
        activeIndex = (activeIndex + 1) % filtered.length;
        highlightActive();
      }
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (filtered.length) {
        activeIndex = (activeIndex - 1 + filtered.length) % filtered.length;
        highlightActive();
      }
    } else if (e.key === 'Enter') {
      if (activeIndex >= 0 && filtered[activeIndex]) {
        e.preventDefault();
        choose(filtered[activeIndex]);
      }
    } else if (e.key === 'Escape') {
      closeMenu();
    }
  });

  // Mouse choose
  items.forEach(btn => {
    btn.addEventListener('mousedown', function(e){
      e.preventDefault(); // prevent input blur before we set value
      choose(btn);
    });
  });
})();
</script>

<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>
