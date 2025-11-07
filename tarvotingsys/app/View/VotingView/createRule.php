<?php
$_title = 'Rule Creation';
require_once __DIR__ . '/../AdminView/adminHeader.php';

// ---- SAFETY GUARDS (put right after adminHeader.php include)
if (!isset($electionEvents) || !is_array($electionEvents)) {
    $electionEvents = [];
}

$selectedElectionId = $ruleData['electionID'] ?? null;

// If controller didn't pass $election_name, try to infer it from $ruleData or $electionEvents
if (!isset($election_name) || $election_name === '' || $election_name === null) {
    // Prefer a joined column if your getRuleById joined the event title
    if (!empty($ruleData['event_name'])) {
        $election_name = $ruleData['event_name'];
    } elseif ($selectedElectionId !== null) {
        // Fallback: find title from events list
        $found = null;
        foreach ($electionEvents as $ev) {
            if ((string)($ev['electionID'] ?? '') === (string)$selectedElectionId) {
                $found = $ev['title'] ?? null;
                break;
            }
        }
        $election_name = $found ?: 'Select an event';
    } else {
        $election_name = 'Select an event';
    }
}
?>

<div class="container mt-4">
    <h2>Create Rule</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="/rule/create" method="POST" id="ruleForm">
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

        <!-- Associated Election Event -->
        <div class="mb-3">
            <label for="electionID" class="form-label">Associated Election Event</label>

            <!-- Bootstrap Dropdown (custom scrollable) -->
            <div class="dropdown w-100">
                <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" 
                        type="button" 
                        id="dropdownElectionEvent" 
                        data-bs-toggle="dropdown" 
                        aria-expanded="false">
                    <?= htmlspecialchars($election_name) ?>
                </button>

                <ul class="dropdown-menu w-100" 
                    aria-labelledby="dropdownElectionEvent" 
                    style="max-height: 200px; overflow-y: auto;">
                    <?php foreach ($electionEvents as $event): ?>
                        <?php if (!in_array($event['status'], ['Pending', 'Ongoing', 'Upcoming'])) continue; ?>
                        <li>
                            <a class="dropdown-item" 
                               href="#" 
                               data-id="<?= htmlspecialchars($event['electionID']) ?>" 
                               data-name="<?= htmlspecialchars($event['title']) ?>">
                               <?= htmlspecialchars($event['electionID']) ?> - 
                               <?= htmlspecialchars($event['title']) ?> 
                                (<?= htmlspecialchars($event['status']) ?>)
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Hidden field to actually send selected electionID -->
            <input type="hidden" name="electionID" id="electionID" 
                   value="<?= htmlspecialchars($ruleCreationData['electionID'] ?? '') ?>">

            <?php if (!empty($fieldErrors['electionID'])): ?>
                <div class="invalid-feedback d-block">
                    <?= htmlspecialchars(implode(' ', $fieldErrors['electionID'])) ?>
                </div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary">Create Rule</button>
    </form>
</div>

<script>
document.querySelectorAll('.dropdown-item').forEach(item => {
    item.addEventListener('click', e => {
        e.preventDefault();
        const id = item.getAttribute('data-id');
        const name = item.getAttribute('data-name');
        const button = document.getElementById('dropdownElectionEvent');
        const hidden = document.getElementById('electionID');

        // update button label and hidden input
        button.textContent = name;
        hidden.value = id;
    });
});
</script>

<?php
require_once __DIR__ . '/../AdminView/adminFooter.php';
?>
