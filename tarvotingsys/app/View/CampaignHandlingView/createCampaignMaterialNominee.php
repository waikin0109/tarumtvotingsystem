<?php
$_title = 'Campaign Material Lists';
require_once __DIR__ . '/../NomineeView/nomineeHeader.php';

/**
 * expects:
 * $elections (list of ['electionID','title'])
 * $nominees (list of ['nomineeID','fullName','studentID']) for selected election (optional)
 * $errors (list), $fieldErrors (array), $old (array)
 */
function invalid(array $fe, string $code){ return !empty($fe[$code]) ? ' is-invalid' : ''; }

$old = $old ?? [];
$fieldErrors = $fieldErrors ?? [];
$errors = $errors ?? [];
?>
<div class="container mt-4 mb-5">
  <h2>Apply Campaign Material</h2>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0">
      <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul></div>
  <?php endif; ?>

  <form action="/nominee/campaign-material/create" method="POST" id="appForm" novalidate enctype="multipart/form-data">
    <!-- Election Event Selection -->
<div class="mb-3">
    <label class="form-label">Election Event</label>
    <div class="position-relative">
        <select name="electionID" class="form-select<?= invalid($fieldErrors, 'electionID') ?>" required>
            <option value="">Select Election Event</option>
            <?php foreach ($elections as $ev): ?>
                <option value="<?= (int)$ev['electionID'] ?>" 
                    <?= isset($old['electionID']) && (int)$ev['electionID'] === (int)$old['electionID'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($ev['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($fieldErrors['electionID'])): ?>
            <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['electionID'])) ?></div>
        <?php endif; ?>
    </div>
</div>


    <!-- Title -->
    <div class="mb-3">
      <label class="form-label" for="materialsTitle">Title</label>
      <input type="text" name="materialsTitle" id="materialsTitle"
             class="form-control<?= invalid($fieldErrors,'materialsTitle') ?>"
             value="<?= htmlspecialchars($old['materialsTitle'] ?? '') ?>" required>
      <?php if (!empty($fieldErrors['materialsTitle'])): ?>
        <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['materialsTitle'])) ?></div>
      <?php endif; ?>
    </div>

    <!-- Type -->
    <div class="mb-3">
      <label class="form-label" for="materialsType">Type</label>
      <select name="materialsType" id="materialsType"
              class="form-select<?= invalid($fieldErrors,'materialsType') ?>" required>
        <option value="">-- Select --</option>
        <option value="PHYSICAL" <?= (isset($old['materialsType']) && $old['materialsType']==='PHYSICAL')?'selected':''; ?>>PHYSICAL</option>
        <option value="DIGITAL"  <?= (isset($old['materialsType']) && $old['materialsType']==='DIGITAL')?'selected':''; ?>>DIGITAL</option>
      </select>
      <?php if (!empty($fieldErrors['materialsType'])): ?>
        <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['materialsType'])) ?></div>
      <?php endif; ?>
    </div>

    <!-- Desc -->
    <div class="mb-3">
      <label class="form-label" for="materialsDesc">Description</label>
      <textarea name="materialsDesc" id="materialsDesc" rows="4" class="form-control"><?= htmlspecialchars($old['materialsDesc'] ?? '') ?></textarea>
      <?php if (!empty($fieldErrors['materialsDesc'])): ?>
        <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['materialsDesc'])) ?></div>
      <?php endif; ?>
    </div>

    <!-- Quantity -->
    <div class="mb-3">
      <label class="form-label" for="materialsQuantity">Quantity</label>
      <input type="number" min="1" step="1"
             name="materialsQuantity" id="materialsQuantity"
             class="form-control<?= invalid($fieldErrors,'materialsQuantity') ?>"
             value="<?= htmlspecialchars($old['materialsQuantity'] ?? '') ?>" required>
      <?php if (!empty($fieldErrors['materialsQuantity'])): ?>
        <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['materialsQuantity'])) ?></div>
      <?php endif; ?>
    </div>

    <!-- Files (multiple; additive selection that doesn't wipe previous) -->
    <div class="mb-4">
      <label class="form-label">Files / Images</label>
      <div class="d-flex flex-wrap gap-2 mb-2" id="fileChips"></div>
      <input type="file" id="materialsFiles" name="materialsFiles[]" multiple class="form-control" accept="image/*,.pdf,.doc,.docx,.ppt,.pptx">
      <!-- A hidden input will be used automatically by the browser on submit.
           We will replace the input’s FileList using DataTransfer to keep previous selections. -->
      <div class="form-text">You can click again to add more files; previous selections are kept.</div>
      <?php if (!empty($fieldErrors['materialsFiles'])): ?>
        <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['materialsFiles'])) ?></div>
      <?php endif; ?>
    </div>

    <div class="d-flex justify-content-center gap-3">
      <button type="submit" class="btn btn-primary px-4">Submit</button>
      <a href="/admin/campaign-material" class="btn btn-outline-secondary px-4">Cancel</a>
    </div>
  </form>
</div>

<script>
(function(){
  // Simple searchable dropdowns
  const makeDropdown = (input, menu) => {
    input.addEventListener('focus', () => { menu.classList.add('show'); });
    input.addEventListener('input', () => {
      const q = input.value.toLowerCase().trim();
      [...menu.querySelectorAll('.dropdown-item')].forEach(btn => {
        const text = (btn.dataset.text || btn.textContent).toLowerCase();
        const keys = (btn.dataset.keywords || text);
        btn.style.display = (text.includes(q) || keys.includes(q)) ? '' : 'none';
      });
      menu.classList.add('show');
    });
    document.addEventListener('click', (e) => {
      if (!menu.contains(e.target) && !input.contains(e.target)) menu.classList.remove('show');
    });
  };

  // Election dropdown
  const electionInput = document.getElementById('electionSearch');
  const electionList  = document.getElementById('electionList');
  const electionID    = document.getElementById('electionID');
  if (electionInput && electionList) {
    makeDropdown(electionInput, electionList);
    electionList.addEventListener('click', (e) => {
      const btn = e.target.closest('button.dropdown-item');
      if (!btn) return;
      electionInput.value = btn.dataset.text;
      electionID.value    = btn.dataset.id;
      electionList.classList.remove('show');
      // Reload nominees for this election by refreshing with query param
      const params = new URLSearchParams(location.search);
      params.set('electionID', btn.dataset.id);
      window.location.href = location.pathname + '?' + params.toString();
    });
  }

  // Nominee dropdown (client-side filter only; nominees are server populated for the selected election)
  const nomineeInput = document.getElementById('nomineeSearch');
  const nomineeList  = document.getElementById('nomineeList');
  const nomineeID    = document.getElementById('nomineeID');
  if (nomineeInput && nomineeList) {
    makeDropdown(nomineeInput, nomineeList);
    nomineeList.addEventListener('click', (e) => {
      const btn = e.target.closest('button.dropdown-item');
      if (!btn) return;
      nomineeInput.value = btn.dataset.text;
      nomineeID.value    = btn.dataset.id;
      nomineeList.classList.remove('show');
    });
  }

  // Multiple file input that keeps previous files
  const fileInput = document.getElementById('materialsFiles');
  const chips = document.getElementById('fileChips');
  const dt = new DataTransfer();

  const renderChips = () => {
    chips.innerHTML = '';
    [...dt.files].forEach((f, idx) => {
      const chip = document.createElement('span');
      chip.className = 'badge rounded-pill text-bg-secondary';
      chip.style.userSelect = 'none';
      chip.innerHTML = `
        ${f.name}
        <button type="button" class="btn-close btn-close-white ms-2" aria-label="Remove" data-idx="${idx}"></button>
      `;
      chips.appendChild(chip);
    });
  };

  chips.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-close');
    if (!btn) return;
    const i = parseInt(btn.dataset.idx, 10);
    // Remove file index i from DataTransfer
    const keep = new DataTransfer();
    [...dt.files].forEach((f, idx) => { if (idx !== i) keep.items.add(f); });
    dt.items.clear();
    [...keep.files].forEach(f => dt.items.add(f));
    fileInput.files = dt.files;
    renderChips();
  });

  fileInput.addEventListener('change', () => {
    [...fileInput.files].forEach(f => dt.items.add(f));
    fileInput.files = dt.files;
    renderChips();
  });
})();
</script>

<?php
require_once __DIR__ . '/../NomineeView/nomineeFooter.php';
?>