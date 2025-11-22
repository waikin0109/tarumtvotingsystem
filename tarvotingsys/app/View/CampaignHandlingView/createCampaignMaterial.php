<?php
$_title = "Apply Campaign Material";
require_once __DIR__ . '/../AdminView/adminHeader.php';

/**
 * expects:
 * $elections (list of ['electionID','title'])
 * $nominees (list of ['nomineeID','fullName','studentID']) for selected election (optional)
 * $errors (list), $fieldErrors (array), $old (array)
 */
if (!function_exists('invalid')) {
    function invalid(array $fe, string $code){ return !empty($fe[$code]) ? ' is-invalid' : ''; }
}

$old         = $old ?? [];
$fieldErrors = $fieldErrors ?? [];
$errors      = $errors ?? [];
$elections   = $elections ?? [];
$nominees    = $nominees ?? [];
?>

<div class="container-fluid mt-4 mb-5">
    <!-- Page header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Apply Campaign Material</h2>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="/admin/campaign-material/create"
                  method="POST"
                  id="appForm"
                  novalidate
                  enctype="multipart/form-data">

                <!-- Section: Election & Nominee -->
                <h5 class="mb-3">Election Event &amp; Nominee</h5>

                <!-- Election (searchable) -->
                <div class="mb-3">
                    <label class="form-label">
                        Election Event <span class="text-danger">*</span>
                    </label>
                    <div class="position-relative">
                        <input
                            type="text"
                            class="form-control<?= invalid($fieldErrors,'electionID') ?>"
                            id="electionSearch"
                            placeholder="Search event…"
                            autocomplete="off"
                            value="<?php
                                if (!empty($old['electionID'])) {
                                    foreach ($elections as $ev) {
                                        if ((int)$ev['electionID'] === (int)$old['electionID']) {
                                            echo htmlspecialchars($ev['title']);
                                            break;
                                        }
                                    }
                                }
                            ?>"
                        >
                        <input type="hidden"
                               name="electionID"
                               id="electionID"
                               value="<?= (int)($old['electionID'] ?? 0) ?>">

                        <div id="electionList"
                             class="dropdown-menu w-100 p-0"
                             style="max-height:240px;overflow:auto;">
                            <?php foreach ($elections as $ev):
                                $keys = strtolower($ev['title'] ?? '');
                            ?>
                                <button type="button"
                                        class="dropdown-item"
                                        data-id="<?= (int)$ev['electionID'] ?>"
                                        data-text="<?= htmlspecialchars($ev['title']) ?>"
                                        data-keywords="<?= htmlspecialchars($keys) ?>">
                                    <?= htmlspecialchars($ev['title']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <?php if (!empty($fieldErrors['electionID'])): ?>
                            <div class="invalid-feedback d-block">
                                <?= htmlspecialchars(implode(' ', $fieldErrors['electionID'])) ?>
                            </div>
                        <?php endif; ?>

                        <div id="electionHelp" class="text-danger small d-none">
                            The event is not available. Please select from the list.
                        </div>
                    </div>
                    <small class="text-muted">
                        Only events with registration closed and <b>PUBLISHED</b> nominees appear.
                    </small>
                </div>

                <!-- Nominee (depends on election) -->
                <div class="mb-4">
                    <label class="form-label">
                        Nominee <span class="text-danger">*</span>
                    </label>
                    <div class="position-relative">
                        <input
                            type="text"
                            class="form-control<?= invalid($fieldErrors,'nomineeID') ?>"
                            id="nomineeSearch"
                            placeholder="Search nominee by name or student ID…"
                            autocomplete="off"
                            value="<?php
                                if (!empty($old['nomineeID']) && !empty($nominees)) {
                                    foreach ($nominees as $n) {
                                        if ((int)$n['nomineeID'] === (int)$old['nomineeID']) {
                                            echo htmlspecialchars(
                                                $n['fullName'] . ' (ID ' . $n['studentID'] . ')'
                                            );
                                            break;
                                        }
                                    }
                                }
                            ?>"
                            <?= empty($old['electionID']) ? 'disabled' : '' ?>
                        >
                        <input type="hidden"
                               name="nomineeID"
                               id="nomineeID"
                               value="<?= (int)($old['nomineeID'] ?? 0) ?>">

                        <div id="nomineeList"
                             class="dropdown-menu w-100 p-0"
                             style="max-height:240px;overflow:auto; <?= empty($old['electionID']) ? 'pointer-events:none;opacity:.6;' : '' ?>">
                            <?php foreach ($nominees as $n):
                                $text = $n['fullName'] . ' (ID ' . $n['studentID'] . ')';
                                $keys = strtolower($n['fullName'] . ' ' . $n['studentID'] . ' ' . $text);
                            ?>
                                <button type="button"
                                        class="dropdown-item d-flex justify-content-between"
                                        data-id="<?= (int)$n['nomineeID'] ?>"
                                        data-text="<?= htmlspecialchars($text) ?>"
                                        data-keywords="<?= htmlspecialchars($keys) ?>">
                                    <span><?= htmlspecialchars($n['fullName']) ?></span>
                                    <small class="text-muted">ID <?= (int)$n['studentID'] ?></small>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <?php if (!empty($fieldErrors['nomineeID'])): ?>
                            <div class="invalid-feedback d-block">
                                <?= htmlspecialchars(implode(' ', $fieldErrors['nomineeID'])) ?>
                            </div>
                        <?php endif; ?>

                        <div id="nomineeHelp" class="text-danger small d-none">
                            The name is not available. Please select a nominee from the list.
                        </div>
                    </div>
                    <small class="text-muted">
                        Only nominees with <b>PUBLISHED</b> applications for the selected event.
                    </small>
                </div>

                <!-- Section: Materials Details -->
                <h5 class="mb-3">Campaign Materials Details</h5>

                <!-- Title -->
                <div class="mb-3">
                    <label class="form-label" for="materialsTitle">
                        Title <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           name="materialsTitle"
                           id="materialsTitle"
                           class="form-control<?= invalid($fieldErrors,'materialsTitle') ?>"
                           value="<?= htmlspecialchars($old['materialsTitle'] ?? '') ?>"
                           required>
                    <?php if (!empty($fieldErrors['materialsTitle'])): ?>
                        <div class="invalid-feedback d-block">
                            <?= htmlspecialchars(implode(' ', $fieldErrors['materialsTitle'])) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Type -->
                <div class="mb-3">
                    <label class="form-label" for="materialsType">
                        Type <span class="text-danger">*</span>
                    </label>
                    <select name="materialsType"
                            id="materialsType"
                            class="form-select<?= invalid($fieldErrors,'materialsType') ?>"
                            required>
                        <option value="">-- Select --</option>
                        <option value="PHYSICAL" <?= (($old['materialsType'] ?? '') === 'PHYSICAL') ? 'selected' : ''; ?>>
                            PHYSICAL
                        </option>
                        <option value="DIGITAL"  <?= (($old['materialsType'] ?? '') === 'DIGITAL')  ? 'selected' : ''; ?>>
                            DIGITAL
                        </option>
                    </select>
                    <?php if (!empty($fieldErrors['materialsType'])): ?>
                        <div class="invalid-feedback d-block">
                            <?= htmlspecialchars(implode(' ', $fieldErrors['materialsType'])) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <label class="form-label" for="materialsDesc">Description</label>
                    <textarea name="materialsDesc"
                              id="materialsDesc"
                              rows="4"
                              class="form-control<?= invalid($fieldErrors,'materialsDesc') ?>"><?= htmlspecialchars($old['materialsDesc'] ?? '') ?></textarea>
                    <?php if (!empty($fieldErrors['materialsDesc'])): ?>
                        <div class="invalid-feedback d-block">
                            <?= htmlspecialchars(implode(' ', $fieldErrors['materialsDesc'])) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quantity -->
                <div class="mb-3">
                    <label class="form-label" for="materialsQuantity">
                        Quantity <span class="text-danger">*</span>
                    </label>
                    <input type="number"
                           min="1"
                           step="1"
                           name="materialsQuantity"
                           id="materialsQuantity"
                           class="form-control<?= invalid($fieldErrors,'materialsQuantity') ?>"
                           value="<?= htmlspecialchars($old['materialsQuantity'] ?? '') ?>"
                           required>
                    <?php if (!empty($fieldErrors['materialsQuantity'])): ?>
                        <div class="invalid-feedback d-block">
                            <?= htmlspecialchars(implode(' ', $fieldErrors['materialsQuantity'])) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Files (multiple; additive selection that doesn't wipe previous) -->
                <div class="mb-4">
                    <label class="form-label">Files / Images</label>
                    <div class="d-flex flex-wrap gap-2 mb-2" id="fileChips"></div>

                    <input type="file"
                           id="materialsFiles"
                           name="materialsFiles[]"
                           multiple
                           class="form-control"
                           accept="image/*,.pdf,.doc,.docx,.ppt,.pptx">

                    <div class="form-text">
                        You can click again to add more files; previous selections are kept.
                    </div>

                    <?php if (!empty($fieldErrors['materialsFiles'])): ?>
                        <div class="invalid-feedback d-block">
                            <?= htmlspecialchars(implode(' ', $fieldErrors['materialsFiles'])) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="d-flex justify-content-end gap-2 pt-2">
                    <a href="/admin/campaign-material" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <a href="/admin/campaign-material/create" class="btn btn-outline-secondary">
                        Reset
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Submit Application
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
  const $ = (id) => document.getElementById(id);

  const show = (id, msg) => {
    const el = $(id);
    if (!el) return;
    if (msg) el.textContent = msg;
    el.classList.remove('d-none');
  };

  const hide = (id) => {
    const el = $(id);
    if (el) el.classList.add('d-none');
  };

  // Generic searchable dropdown
  const makeDropdown = (input, menu, hiddenId, helpId) => {
    if (!input || !menu) return;

    input.addEventListener('focus', () => {
      menu.classList.add('show');
      if (helpId) hide(helpId);
    });

    input.addEventListener('input', () => {
      if (hiddenId && $(hiddenId)) $(hiddenId).value = '';
      if (helpId) hide(helpId);

      const q = input.value.toLowerCase().trim();
      [...menu.querySelectorAll('.dropdown-item')].forEach(btn => {
        const text = (btn.dataset.text || btn.textContent).toLowerCase();
        const keys = (btn.dataset.keywords || text);
        btn.style.display = (text.includes(q) || keys.includes(q)) ? '' : 'none';
      });
      menu.classList.add('show');
    });

    input.addEventListener('blur', () => {
      const haveText = input.value.trim() !== '';
      const haveId   = hiddenId ? !!$(hiddenId).value : false;
      if (helpId && haveText && !haveId) show(helpId);
    });

    document.addEventListener('click', (e) => {
      if (!menu.contains(e.target) && !input.contains(e.target)) {
        menu.classList.remove('show');
      }
    });
  };

  // Election dropdown
  const electionInput = $('electionSearch');
  const electionList  = $('electionList');
  const electionID    = $('electionID');

  if (electionInput && electionList && electionID) {
    makeDropdown(electionInput, electionList, 'electionID', 'electionHelp');

    electionList.addEventListener('click', (e) => {
      const btn = e.target.closest('button.dropdown-item');
      if (!btn) return;

      electionInput.value = btn.dataset.text;
      electionID.value    = btn.dataset.id;
      electionList.classList.remove('show');
      hide('electionHelp');

      // Reload nominees for this election by refreshing with query param.
      const url = new URL(window.location.href);
      url.searchParams.set('electionID', btn.dataset.id);

      // Preserve already-filled fields if possible
      const mt = $('materialsTitle')?.value || '';
      const ty = $('materialsType')?.value  || '';
      const md = $('materialsDesc')?.value  || '';
      const mq = $('materialsQuantity')?.value || '';

      if (mt) url.searchParams.set('materialsTitle', mt);
      if (ty) url.searchParams.set('materialsType', ty);
      if (md) url.searchParams.set('materialsDesc', md);
      if (mq) url.searchParams.set('materialsQuantity', mq);

      window.location.href = url.toString();
    });
  }

  // Nominee dropdown
  const nomineeInput = $('nomineeSearch');
  const nomineeList  = $('nomineeList');
  const nomineeID    = $('nomineeID');

  if (nomineeInput && nomineeList && nomineeID) {
    makeDropdown(nomineeInput, nomineeList, 'nomineeID', 'nomineeHelp');

    nomineeList.addEventListener('click', (e) => {
      const btn = e.target.closest('button.dropdown-item');
      if (!btn) return;
      nomineeInput.value = btn.dataset.text;
      nomineeID.value    = btn.dataset.id;
      nomineeList.classList.remove('show');
      hide('nomineeHelp');
    });
  }

  // Multiple file input that keeps previous files
  const fileInput = $('materialsFiles');
  const chips     = $('fileChips');
  const dt        = new DataTransfer();

  const renderChips = () => {
    if (!chips) return;
    chips.innerHTML = '';
    [...dt.files].forEach((f, idx) => {
      const chip = document.createElement('span');
      chip.className = 'badge rounded-pill text-bg-secondary';
      chip.style.userSelect = 'none';
      chip.innerHTML = `
        ${f.name}
        <button type="button"
                class="btn-close btn-close-white ms-2"
                aria-label="Remove"
                data-idx="${idx}"></button>
      `;
      chips.appendChild(chip);
    });
  };

  if (chips) {
    chips.addEventListener('click', (e) => {
      const btn = e.target.closest('.btn-close');
      if (!btn) return;
      const i = parseInt(btn.dataset.idx, 10);
      const keep = new DataTransfer();
      [...dt.files].forEach((f, idx) => {
        if (idx !== i) keep.items.add(f);
      });
      dt.items.clear();
      [...keep.files].forEach(f => dt.items.add(f));
      if (fileInput) fileInput.files = dt.files;
      renderChips();
    });
  }

  if (fileInput) {
    fileInput.addEventListener('change', () => {
      [...fileInput.files].forEach(f => dt.items.add(f));
      fileInput.files = dt.files;
      renderChips();
    });
  }

  // Final submit guard
  const form = $('appForm');
  if (form) {
    form.addEventListener('submit', (e) => {
      let ok = true;

      if (electionInput && electionID && !electionID.value) {
        show('electionHelp', 'The event is not available. Please select from the list.');
        electionInput.focus();
        ok = false;
      }
      if (nomineeInput && nomineeID && !nomineeID.value) {
        show('nomineeHelp', 'The name is not available. Please select a nominee from the list.');
        if (ok) nomineeInput.focus();
        ok = false;
      }

      if (!ok) e.preventDefault();
    });
  }

  // Sync help visibility on load
  window.addEventListener('DOMContentLoaded', () => {
    if (electionInput && electionID) {
      if (!electionInput.value.trim() || electionID.value) hide('electionHelp');
    }
    if (nomineeInput && nomineeID) {
      if (!nomineeInput.value.trim() || nomineeID.value) hide('nomineeHelp');
    }
  });
})();
</script>

<?php require_once __DIR__ . '/../AdminView/adminFooter.php'; ?>
