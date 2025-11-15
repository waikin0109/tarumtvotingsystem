<?php
$_title = 'Campaign Material Lists';
require_once __DIR__ . '/../NomineeView/nomineeHeader.php';
?>

<div>
    <div class="container-fluid d-flex justify-content-between align-items-center mb-4">
        <div class="col-sm-6">
            <h2>Campaign Material Lists</h2>
        </div>
        <div class="col-sm-6">
            <a href="/nominee/campaign-material/create">
            <button class="btn btn-primary mx-2 me-5 position-absolute end-0">Apply (+)</button>
            </a>
        </div>
    </div>

    <div class="container-fluid mb-5">
        <div class="bg-light">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="col-sm-1">No. </th>
                            <th class="col-sm-5">Campaign Material Title</th>
                            <th class="col-sm-4">Election Event</th>
                            <th class="col-sm-2">Action</th> 
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($campaignMaterials)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No Campaign Materials found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($campaignMaterials as $index => $campaignMaterial): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($campaignMaterial['materialsTitle'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($campaignMaterial['electionEventTitle'] ?? '—') ?></td>
                                    <td class="text-nowrap">
                                        <?php $materialId = (int)($campaignMaterial['materialsApplicationID'] ?? 0); ?>
                                        <a href="/nominee/campaign-material/view/<?= urlencode((string)$materialId) ?>"
                                        class="btn btn-sm btn-secondary">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div> 
    </div>                   
</div>

<?php
require_once __DIR__ . '/../NomineeView/nomineeFooter.php';
?>

