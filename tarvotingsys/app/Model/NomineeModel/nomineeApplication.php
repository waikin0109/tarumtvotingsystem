<?php
$_title = 'Nominee Registration Form List';
require_once __DIR__ . '/../AdminView/adminHeader.php';
?>

<div class="container mt-4">
    <h2>Election Event Ongoing</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    

    