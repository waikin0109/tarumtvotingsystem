<?php
$_title = "Apply Campaign Event";
require_once __DIR__ . '/../NomineeView/nomineeHeader.php';

if (!function_exists('invalid')) {
  function invalid(array $fe, string $code){ return !empty($fe[$code]) ? ' is-invalid' : ''; }
}
?>

<div class="container mt-4 mb-5">
  <h2>Apply for Campaign Event</h2>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0">
      <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul></div>
  <?php endif; ?>

  <form action="/nominee/schedule-location/create" method="POST" id="scheduleForm" novalidate>
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
      <small class="text-muted">
        Only elections where you are a <b>PUBLISHED</b> nominee and registration has closed will appear.
      </small>
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
      <a href="/nominee/schedule-location" class="btn btn-outline-secondary px-4">Cancel</a>
      <button type="submit" class="btn btn-primary px-4">Submit</button>
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
      hide(helpId);
    });

    input.addEventListener('input', () => {
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
      const haveText = input.value.trim() !== '';
      const haveId   = hiddenId ? !!$(hiddenId).value : false;
      if (haveText && !haveId) show(helpId);
    });

    document.addEventListener('click', (e) => {
      if (!menu.contains(e.target) && !input.contains(e.target)) menu.classList.remove('show');
    });
  };

  // Election dropdown
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
    });
  }

  // Basic time validation on submit
  const form = $('scheduleForm');
  if (form) {
    form.addEventListener('submit', (e) => {
      let ok = true;

      if (electionInput && electionID && !electionID.value) {
        show('electionHelp', 'The event is not available. Please select from the list.');
        electionInput.focus();
        ok = false;
      }

      const startEl = $('desiredStartDateTime');
      const endEl   = $('desiredEndDateTime');
      const setInlineError = (el, msg) => {
        el.classList.add('is-invalid');
        let fb = el.parentElement.querySelector('.invalid-feedback');
        if (!fb) {
          fb = document.createElement('div');
          fb.className = 'invalid-feedback d-block';
          el.parentElement.appendChild(fb);
        }
        fb.textContent = msg;
      };
      const clearInlineError = (el) => {
        el.classList.remove('is-invalid');
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
})();
</script>

<?php
require_once __DIR__ . '/../NomineeView/nomineeFooter.php';
?>
