<?php
$_title = "Create Schedule Location";
require_once __DIR__ . '/../AdminView/adminHeader.php';

// Optional: if you moved invalid() to a shared include, remove this.
if (!function_exists('invalid')) {
  function invalid(array $fe, string $code){ return !empty($fe[$code]) ? ' is-invalid' : ''; }
}
?>

<div class="container mt-4 mb-5">
  <h2>Create Schedule Location</h2>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0">
      <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul></div>
  <?php endif; ?>

  <form action="/admin/schedule-location/create" method="POST" id="scheduleForm" novalidate>
    <!-- Election (searchable) -->
    <div class="mb-3">
      <label class="form-label">Election Event <span class="text-danger">*</span></label>
      <div class="position-relative">
        <input type="text" class="form-control<?= invalid($fieldErrors,'electionID') ?>"
               id="electionSearch" placeholder="Search event…"
               autocomplete="off"
               value="<?php
                 if (!empty($old['electionID'])) {
                   foreach ($elections as $ev) {
                     if ((int)$ev['electionID'] === (int)$old['electionID']) { echo htmlspecialchars($ev['title']); break; }
                   }
                 }
               ?>">
        <input type="hidden" name="electionID" id="electionID" value="<?= (int)($old['electionID'] ?? 0) ?>">

        <div id="electionList" class="dropdown-menu w-100 p-0" style="max-height:240px;overflow:auto;">
          <?php foreach ($elections as $ev): ?>
            <button type="button" class="dropdown-item"
                    data-id="<?= (int)$ev['electionID'] ?>"
                    data-text="<?= htmlspecialchars($ev['title']) ?>"
                    data-keywords="<?= htmlspecialchars(strtolower($ev['title'])) ?>">
              <?= htmlspecialchars($ev['title']) ?>
            </button>
          <?php endforeach; ?>
        </div>
        <?php if (!empty($fieldErrors['electionID'])): ?>
          <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['electionID'])) ?></div>
        <?php endif; ?>
        <div id="electionHelp" class="text-danger small d-none">
        The event is not available. Please select from the list.
        </div>
      </div>
      <small class="text-muted">Only elections with registration closed and at least one <b>PUBLISHED</b> nominee appear.</small>
    </div>

    <!-- Nominee (searchable, scoped to election) -->
    <div class="mb-3">
      <label class="form-label">Nominee <span class="text-danger">*</span></label>
      <div class="position-relative">
        <input type="text" class="form-control<?= invalid($fieldErrors,'nomineeID') ?>"
               id="nomineeSearch" placeholder="Search nominee by name or ID…"
               autocomplete="off"
               value="<?php
                 if (!empty($old['nomineeID'])) {
                   foreach ($nominees as $n) {
                     if ((int)$n['nomineeID'] === (int)$old['nomineeID']) {
                       $label = $n['display'] ?? ($n['fullName'].' ('.$n['nomineeID'].')');
                       echo htmlspecialchars($label); break;
                     }
                   }
                 }
               ?>"
               <?= empty($old['electionID']) ? 'disabled' : '' ?>>
        <input type="hidden" name="nomineeID" id="nomineeID" value="<?= (int)($old['nomineeID'] ?? 0) ?>">

        <div id="nomineeList" class="dropdown-menu w-100 p-0" style="max-height:240px;overflow:auto; <?= empty($old['electionID']) ? 'pointer-events:none;opacity:.6;' : '' ?>">
          <?php foreach ($nominees as $n):
            $label = $n['display'] ?? ($n['fullName'].' ('.$n['nomineeID'].')');
            $keys  = strtolower(($n['fullName'] ?? '').' '.$n['nomineeID'].' '.$label);
          ?>
            <button type="button" class="dropdown-item d-flex justify-content-between"
                    data-id="<?= (int)$n['nomineeID'] ?>"
                    data-text="<?= htmlspecialchars($label) ?>"
                    data-keywords="<?= htmlspecialchars($keys) ?>">
              <span><?= htmlspecialchars($n['fullName'] ?? $label) ?></span>
              <small class="text-muted">ID <?= (int)$n['nomineeID'] ?></small>
            </button>
          <?php endforeach; ?>
        </div>
        <?php if (!empty($fieldErrors['nomineeID'])): ?>
          <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['nomineeID'])) ?></div>
        <?php endif; ?>
        <div id="nomineeHelp" class="text-danger small d-none">
        The name is not available. Please select a nominee from the list.
        </div>

      </div>
      <small class="text-muted">Nominees listed belong only to the selected election.</small>
    </div>

    <!-- Event Name -->
    <div class="mb-3">
      <label class="form-label" for="eventName">Event Name <span class="text-danger">*</span></label>
      <input type="text" name="eventName" id="eventName" maxlength="255"
             class="form-control<?= invalid($fieldErrors,'eventName') ?>"
             value="<?= htmlspecialchars($old['eventName'] ?? '') ?>" required>
      <?php if (!empty($fieldErrors['eventName'])): ?>
        <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['eventName'])) ?></div>
      <?php endif; ?>
    </div>

    <!-- Event Type -->
    <div class="mb-3">
      <label class="form-label" for="eventType">Event Type <span class="text-danger">*</span></label>
      <select name="eventType" id="eventType" class="form-select<?= invalid($fieldErrors,'eventType') ?>" required>
        <option value="">-- Select --</option>
        <option value="CAMPAIGN" <?= (($old['eventType'] ?? '')==='CAMPAIGN')?'selected':''; ?>>CAMPAIGN</option>
        <option value="DEBATE"   <?= (($old['eventType'] ?? '')==='DEBATE')  ?'selected':''; ?>>DEBATE</option>
      </select>
      <?php if (!empty($fieldErrors['eventType'])): ?>
        <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['eventType'])) ?></div>
      <?php endif; ?>
    </div>

    <!-- Desired Start Date & Time -->
    <div class="mb-3">
      <label class="form-label" for="desiredStartDateTime">Desired Start Date & Time <span class="text-danger">*</span></label>
      <input type="datetime-local" name="desiredStartDateTime" id="desiredStartDateTime"
            class="form-control<?= invalid($fieldErrors,'desiredStartDateTime') ?>"
            value="<?= htmlspecialchars($old['desiredStartDateTime'] ?? '') ?>" required>
      <?php if (!empty($fieldErrors['desiredStartDateTime'])): ?>
        <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['desiredStartDateTime'])) ?></div>
      <?php endif; ?>
      <div class="form-text">Must be after registration closing and in the future.</div>
    </div>

    <!-- Desired End Date & Time -->
    <div class="mb-3">
      <label class="form-label" for="desiredEndDateTime">Desired End Date & Time <span class="text-danger">*</span></label>
      <input type="datetime-local" name="desiredEndDateTime" id="desiredEndDateTime"
            class="form-control<?= invalid($fieldErrors,'desiredEndDateTime') ?>"
            value="<?= htmlspecialchars($old['desiredEndDateTime'] ?? '') ?>" required>
      <?php if (!empty($fieldErrors['desiredEndDateTime'])): ?>
        <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['desiredEndDateTime'])) ?></div>
      <?php endif; ?>
      <div class="form-text">Must be at least 1 hour after start and not after the election end.</div>
    </div>


    <div class="d-flex justify-content-center gap-3">
      <a href="/admin/schedule-location" class="btn btn-outline-secondary px-4">Cancel</a>
      <button type="submit" class="btn btn-primary px-4">Create</button>
    </div>
  </form>
</div>

<script>
(function(){
  const $ = (id) => document.getElementById(id);
  const show = (id, msg) => { const el = $(id); if (!el) return; if (msg) el.textContent = msg; el.classList.remove('d-none'); };
  const hide = (id) => { const el = $(id); if (el) el.classList.add('d-none'); };

  const makeDropdown = (input, menu, hiddenId, helpId) => {
    if (!input || !menu) return;

    input.addEventListener('focus', () => {
      menu.classList.add('show');
      // don’t show help while focusing/typing
      hide(helpId);
    });

    input.addEventListener('input', () => {
      // User is typing: clear hidden id and hide help
      if (hiddenId) $(hiddenId).value = '';
      hide(helpId);

      const q = input.value.toLowerCase().trim();
      [...menu.querySelectorAll('.dropdown-item')].forEach(btn => {
        const text = (btn.dataset.text || btn.textContent).toLowerCase();
        const keys = (btn.dataset.keywords || text);
        btn.style.display = (text.includes(q) || keys.includes(q)) ? '' : 'none';
      });
      menu.classList.add('show');
    });

    input.addEventListener('blur', () => {
      // If user left typed text but didn’t select an item, show help
      const haveText = input.value.trim() !== '';
      const haveId   = hiddenId ? !!$(hiddenId).value : false;
      if (haveText && !haveId) show(helpId);
    });

    document.addEventListener('click', (e) => {
      if (!menu.contains(e.target) && !input.contains(e.target)) menu.classList.remove('show');
    });
  };

  // Election
  const electionInput = $('electionSearch');
  const electionList  = $('electionList');
  const electionID    = $('electionID');

  if (electionInput && electionList && electionID) {
    makeDropdown(electionInput, electionList, 'electionID', 'electionHelp');

    electionList.addEventListener('click', (e) => {
      const btn = e.target.closest('button.dropdown-item'); if (!btn) return;
      electionInput.value = btn.dataset.text;
      electionID.value    = btn.dataset.id;
      electionList.classList.remove('show');
      hide('electionHelp');

      // reload to repopulate nominees
      const url = new URL(window.location.href);
      url.searchParams.set('electionID', btn.dataset.id);
      const en = $('eventName')?.value || '';
      const et = $('eventType')?.value || '';
      const ds = $('desiredStartDateTime')?.value || '';
      const de = $('desiredEndDateTime')?.value   || '';
      if (en) url.searchParams.set('eventName', en);
      if (et) url.searchParams.set('eventType', et);
      if (ds) url.searchParams.set('desiredStartDateTime', ds);
      if (de) url.searchParams.set('desiredEndDateTime', de);
      window.location.href = url.toString();
    });
  }

  // Nominee
  const nomineeInput = $('nomineeSearch');
  const nomineeList  = $('nomineeList');
  const nomineeID    = $('nomineeID');

  if (nomineeInput && nomineeList && nomineeID) {
    makeDropdown(nomineeInput, nomineeList, 'nomineeID', 'nomineeHelp');

    nomineeList.addEventListener('click', (e) => {
      const btn = e.target.closest('button.dropdown-item'); if (!btn) return;
      nomineeInput.value = btn.dataset.text;
      nomineeID.value    = btn.dataset.id;
      nomineeList.classList.remove('show');
      hide('nomineeHelp');
    });
  }

  // Final submit guard
  const form = $('scheduleForm');
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

      // Light client validation for start/end
      const startEl = $('desiredStartDateTime');
      const endEl   = $('desiredEndDateTime');
      const clearInlineError = (el) => { el.classList.remove('is-invalid'); };
      const setInlineError   = (el, msg) => {
        el.classList.add('is-invalid');
        // show a simple next-sibling feedback if present
        let fb = el.parentElement.querySelector('.invalid-feedback');
        if (!fb) {
          fb = document.createElement('div');
          fb.className = 'invalid-feedback d-block';
          el.parentElement.appendChild(fb);
        }
        fb.textContent = msg;
      };

      if (startEl && endEl) {
        clearInlineError(startEl); clearInlineError(endEl);
        const sVal = startEl.value, eVal = endEl.value;
        if (!sVal) { setInlineError(startEl, 'Start is required.'); ok = false; }
        if (!eVal) { setInlineError(endEl, 'End is required.'); ok = false; }
        if (sVal && eVal) {
          const s = new Date(sVal), d = new Date(eVal);
          const oneHourMs = 60 * 60 * 1000;
          if (d - s < oneHourMs) {
            setInlineError(endEl, 'End time must be at least 1 hour after start.');
            ok = false;
          }
        }
      }

      if (!ok) e.preventDefault();
    });
  }


  // Sync once on page load (so help doesn’t stick)
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
