<?php
$_title = 'Rule Creation';
require_once __DIR__ . '/../AdminView/adminHeader.php';
?>

<div class="container mt-4">
    <h2>Create Rule</h2>

    <form action="/admin/rule/create" method="POST" id="ruleForm">
        <!-- Rule Title -->
        <div class="mb-3">
            <label for="ruleTitle" class="form-label">Rule Title</label>
            <input type="text" 
                   class="form-control <?= !empty($fieldErrors['ruleTitle']) ? 'is-invalid' : '' ?>" 
                   id="ruleTitle" 
                   name="ruleTitle"
                   value="<?= htmlspecialchars($ruleCreationData['ruleTitle'] ?? '') ?>">
            <?php if (!empty($fieldErrors['ruleTitle'])): ?>
                <div class="invalid-feedback">
                    <?= htmlspecialchars(implode(' ', $fieldErrors['ruleTitle'])) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Rule Content -->
        <div class="mb-3">
            <label for="content" class="form-label">Rule Content</label>
            <textarea class="form-control <?= !empty($fieldErrors['content']) ? 'is-invalid' : '' ?>" 
                      id="content" 
                      name="content" 
                      rows="3"><?= htmlspecialchars($ruleCreationData['content'] ?? '') ?></textarea>
            <?php if (!empty($fieldErrors['content'])): ?>
                <div class="invalid-feedback">
                    <?= htmlspecialchars(implode(' ', $fieldErrors['content'])) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Associated Election Event (same UX as Campaign Material) -->
<div class="mb-3">
  <label class="form-label">Associated Election Event</label>
  <div class="position-relative">
    <input
      type="text"
      class="form-control<?= !empty($fieldErrors['electionID']) ? ' is-invalid' : '' ?>"
      id="ruleElectionSearch"
      placeholder="Search event…"
      autocomplete="off"
      value="<?php
        // Prefill the visible text only if there is an existing selection
        $prefill = '';
        $selId = $ruleCreationData['electionID'] ?? ($ruleData['electionID'] ?? 0);
        if (!empty($selId) && !empty($electionEvents)) {
          foreach ($electionEvents as $ev) {
            if ((int)($ev['electionID'] ?? 0) === (int)$selId) {
              $prefill = (string)($ev['title'] ?? '');
              break;
            }
          }
        }
        echo htmlspecialchars($prefill);
      ?>"
    >

    <input type="hidden" name="electionID" id="electionID"
           value="<?= (int)($ruleCreationData['electionID'] ?? ($ruleData['electionID'] ?? 0)) ?>">

    <div id="ruleElectionList" class="dropdown-menu w-100 p-0" style="max-height:240px;overflow:auto;">
      <?php
        $printed = 0;
        foreach (($electionEvents ?? []) as $ev):
          $status = (string)($ev['status'] ?? '');
          // Only Pending / Ongoing (case-insensitive)
          if (!in_array(strtolower($status), ['pending','ongoing'], true)) continue;
          $printed++;
      ?>
        <button type="button"
                class="dropdown-item"
                data-id="<?= (int)$ev['electionID'] ?>"
                data-text="<?= htmlspecialchars($ev['title'] ?? '') ?>"
                data-keywords="<?= htmlspecialchars(strtolower(($ev['title'] ?? '').' '.($ev['electionID'] ?? '').' '.$status)) ?>">
          <?= htmlspecialchars($ev['title'] ?? '') ?>
        </button>
      <?php endforeach; ?>

      <?php if ($printed === 0): ?>
        <div class="px-3 py-2 text-muted">No eligible events (Pending/Ongoing) found.</div>
      <?php endif; ?>
    </div>

    <?php if (!empty($fieldErrors['electionID'])): ?>
      <div class="invalid-feedback d-block"><?= htmlspecialchars(implode(' ', $fieldErrors['electionID'])) ?></div>
    <?php endif; ?>
  </div>
  <small class="text-muted">Only events with status Pending / Ongoing are available.</small>
</div>



        <button type="submit" class="btn btn-primary">Create Rule</button>
    </form>
</div>

<script>
(function(){
  // Reusable mini "searchable dropdown" like your Campaign Material field
  const makeDropdown = (input, menu) => {
    const filter = () => {
      const q = (input.value || '').toLowerCase().trim();
      const items = menu.querySelectorAll('.dropdown-item');
      items.forEach(btn => {
        const text = ((btn.dataset.text || btn.textContent) || '').toLowerCase();
        const keys = (btn.dataset.keywords ? btn.dataset.keywords.toLowerCase() : text);
        const show = !q || text.includes(q) || keys.includes(q);
        btn.style.display = show ? '' : 'none';
      });
      menu.classList.add('show');
    };

    input.addEventListener('focus', () => { menu.classList.add('show'); filter(); });
    input.addEventListener('input', filter);

    document.addEventListener('click', (e) => {
      if (!menu.contains(e.target) && !input.contains(e.target)) {
        menu.classList.remove('show');
      }
    });
  };

  const input  = document.getElementById('ruleElectionSearch');
  const menu   = document.getElementById('ruleElectionList');
  const hidden = document.getElementById('electionID');

  if (input && menu && hidden) {
    makeDropdown(input, menu);

    menu.addEventListener('click', (e) => {
      const btn = e.target.closest('button.dropdown-item');
      if (!btn) return;
      input.value  = btn.dataset.text || '';
      hidden.value = btn.dataset.id || '';
      menu.classList.remove('show');
    });
  }
})();
</script>



<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>
