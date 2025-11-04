<?php
$_title = "View Election Event Details";
require_once __DIR__ . '/../AdminView/adminHeader.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Nominee Application Details</h2>
        <a href="/nominee-application" class="btn btn-outline-secondary">Back to List</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">
                <?= htmlspecialchars(($campaignMaterial['']));