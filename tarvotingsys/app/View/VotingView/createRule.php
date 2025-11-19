<?php
$_title = 'Rule Creation';
require_once __DIR__ . '/../AdminView/adminHeader.php';
?>

<div class="container-fluid mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Create Rule</h2>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="/admin/rule/create" method="POST" id="ruleForm">
                <!-- Rule Title -->
                <div class="mb-3">
                    <label for="ruleTitle" class="form-label">
                        Rule Title <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control <?= !empty($fieldErrors['ruleTitle']) ? 'is-invalid' : '' ?>" 
                           id="ruleTitle" 
                           name="ruleTitle"
                           placeholder="e.g. Campaigning rules, nomination eligibility..."
                           value="<?= htmlspecialchars($ruleCreationData['ruleTitle'] ?? '') ?>">
                    <?php if (!empty($fieldErrors['ruleTitle'])): ?>
                        <div class="invalid-feedback">
                            <?= htmlspecialchars(implode(' ', $fieldErrors['ruleTitle'])) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Rule Content -->
                <div class="mb-4">
                    <label for="content" class="form-label">
                        Rule Content <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control <?= !empty($fieldErrors['content']) ? 'is-invalid' : '' ?>" 
                              id="content" 
                              name="content" 
                              rows="4"
                              placeholder="Describe the rule in clear, formal wording."><?= htmlspecialchars($ruleCreationData['content'] ?? '') ?></textarea>
                    <?php if (!empty($fieldErrors['content'])): ?>
                        <div class="invalid-feedback">
                            <?= htmlspecialchars(implode(' ', $fieldErrors['content'])) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Associated Election Event (same UX as Campaign Material, styled like Event form) -->
                <h5 class="mb-3">Associated Election Event</h5>
                <div class="mb-3">
                    <label class="form-label">
                        Link to Election Event <span class="text-danger">*</span>
                    </label>
                    <div class="position-relative">
                        <input
                            type="text"
                            class="form-control<?= !empty($fieldErrors['electionID']) ? ' is-invalid' : '' ?>"
                            id="ruleElectionSearch"
                            placeholder="Search election event…"
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
                            <div class="invalid-feedback d-block">
                                <?= htmlspecialchars(implode(' ', $fieldErrors['electionID'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted">
                        Only election events with status <strong>Pending</strong> or <strong>Ongoing</strong> are available.
                    </small>
                </div>

                <!-- Actions -->
                <div class="d-flex justify-content-end gap-2 pt-3">
                    <a href="/admin/rule" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Create Rule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
  // Reusable mini "searchable dropdown"
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

    input.addEventListener('focus', () => { 
      menu.classList.add('show'); 
      filter(); 
    });
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
