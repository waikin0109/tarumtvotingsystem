<?php
$_title = 'Announcement';
$roleUpper = strtoupper($_SESSION['role'] ?? '');

// Header / footer includes based on role
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
} elseif ($roleUpper === 'STUDENT') {
    require_once __DIR__ . '/../StudentView/studentHeader.php';
}

/** Expects $announcement:
 * ['id','title','content','senderName','publishedAt','attachments'=>[...]]
 */
$att = $announcement['attachments'] ?? [];
$raw = (string) ($announcement['content'] ?? '');

/* Split content into paragraphs by blank lines (2+ newlines) */
$paragraphs = preg_split("/(\r?\n){2,}/", trim($raw)) ?: [];

/* Separate image vs non-image attachments */
$images = [];
$files = [];
foreach ($att as $f) {
  if (stripos($f['fileType'] ?? '', 'image/') === 0)
    $images[] = $f;
  else
    $files[] = $f;
}
?>

<div class="container-fluid mt-4 mb-5">
  <!-- Title + meta -->
  <h3 class="mb-1"><?= htmlspecialchars($announcement['title'] ?? 'Announcement') ?></h3>
  <div class="text-muted mb-4">
    Published: <?= htmlspecialchars($announcement['publishedAt'] ?? '-') ?>
    · By <?= htmlspecialchars($announcement['senderName'] ?? 'Unknown') ?>
  </div>

  <!-- Content -->
  <div class="ann-body mb-4">
    <?php if ($paragraphs): ?>
      <?php foreach ($paragraphs as $p): ?>
        <p style="margin-bottom:.6rem; line-height:1.55;"><?= nl2br(htmlspecialchars(trim($p))) ?></p>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="text-muted" style="margin-bottom:.6rem; line-height:1.55;">No content.</p>
    <?php endif; ?>
  </div>

  <?php if (!empty($att)): ?>
    <!-- Non-image files (as chips) -->
    <?php if (!empty($files)): ?>
      <div class="mb-2 fw-semibold">Attachment(s):</div>
      <div class="mb-3">
        <?php foreach ($files as $f): ?>
          <a class="btn btn-sm btn-outline-primary" style="display:inline-block; margin:.15rem .35rem .15rem 0;"
            href="<?= htmlspecialchars($f['fileUrl']) ?>" target="_blank" rel="noopener">
            <?= htmlspecialchars($f['original'] ?? 'file') ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Image gallery -->
    <?php if (!empty($images)): ?>
      <?php if (empty($files)): ?>
        <div class="mb-2 fw-semibold">Attachment(s):</div>
      <?php endif; ?>
      <div class="mt-2" style="
          display:grid;
          grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));
          gap:12px;
        ">
        <?php foreach ($images as $img): ?>
          <figure style="display:flex; flex-direction:column; margin:0;">
            <a href="<?= htmlspecialchars($img['fileUrl']) ?>" target="_blank" rel="noopener" style="display:block;">
              <img src="<?= htmlspecialchars($img['fileUrl']) ?>" alt="<?= htmlspecialchars($img['original'] ?? 'image') ?>"
                style="
                  width:100%;
                  height:auto;
                  max-height:420px;
                  object-fit:contain;
                  border-radius:.5rem;
                  box-shadow:0 2px 8px rgba(0,0,0,.06);
                  background:#fff;
                ">
            </a>
            <figcaption title="<?= htmlspecialchars($img['original'] ?? '') ?>" style="
                margin-top:.35rem;
                font-size:.875rem;
                color:#6c757d;
                white-space:nowrap;
                overflow:hidden;
                text-overflow:ellipsis;
              ">
              <?= htmlspecialchars($img['original'] ?? '') ?>
            </figcaption>
          </figure>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <div class="mt-4">
    <a href="/announcements/public" class="btn btn-outline-secondary">Back</a>
  </div>
</div>

<?php
if ($roleUpper === 'NOMINEE') {
    require_once __DIR__ . '/../NomineeView/nomineeFooter.php';
} elseif ($roleUpper === 'STUDENT') {
    require_once __DIR__ . '/../StudentView/studentFooter.php';
}
?>