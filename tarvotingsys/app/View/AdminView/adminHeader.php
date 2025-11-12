<?php

// Retrieve session data (set from LoginController)
$accountLoggedInId = $_SESSION['accountID'] ?? '';
$roleId = $_SESSION['roleID'] ?? null;
$adminLoggedInId = is_scalar($roleId) ? (string)$roleId : ''; 
$fullName = $_SESSION['fullName'] ?? 'Guest';
$role = $_SESSION['role'] ?? 'User';
$annLink = ($role === 'ADMIN') ? '/announcements' : '/announcements/public';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Jquery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous"></script>

    <!-- Tab Title -->
    <title><?php echo $_title ?? 'TARUMTVS' ?></title>
</head>

<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand ms-1" href="#">TARUMTVS Admin</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#">Notifications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Settings</a>
                    </li>
                </ul>
            </div>
        </div>
        <?php if (!empty($_SESSION['flash'])): ?>
            <?php foreach ($_SESSION['flash'] as $type => $message): ?>
                <?php
                    // Map flash types to Bootstrap alert classes
                    $alertClass = match($type) {
                        'success' => 'alert-success',
                        'error', 'fail' => 'alert-danger',
                        'warning' => 'alert-warning',
                        'info' => 'alert-info',
                        default => 'alert-secondary'
                    };
                ?>
                <!-- Flash message at top-center -->
                <div class="position-fixed top-0 start-50 translate-middle-x mt-3 w-100" style="max-width: 600px; z-index: 2000;">
                    <div class="alert <?= $alertClass ?> alert-dismissible fade show shadow-lg text-center" 
                        id="flash-message-<?= $type ?>" 
                        role="alert">
                        <?= htmlspecialchars($message) ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <script>
                // Auto-dismiss all flash messages after 5 seconds
                setTimeout(() => {
                    document.querySelectorAll('[id^="flash-message-"]').forEach(flash => {
                        flash.classList.remove('show'); // start fade-out
                        flash.classList.add('fade');
                        setTimeout(() => flash.remove(), 500); // remove from DOM after fade
                    });
                }, 3000);
            </script>

            <?php unset($_SESSION['flash']); // clear all after showing ?>
        <?php endif; ?>



    </nav>

    <!-- Sidebar and Content wrapper -->
    <div class="d-flex">
        <!-- Sidebar (fixed to left, always visible) -->
        <div>
            <aside id="sidebar"
                class="bg-light position-relative start-0 overflow-auto border-end border-white border-1"
                style="height:calc(100vh - 56px); z-index:1020;">
                <div class="position-sticky pb-5">
                    <div class="list-group list-group-flush">
                        <a href="/admin/election-event" class="list-group-item list-group-item-action">Election Event</a>
                        <a href="/admin/election-registration-form" class="list-group-item list-group-item-action">Election Registration Form</a>
                        <a href="/admin/rule" class="list-group-item list-group-item-action">Rules & Regulations</a>
                        <a href="/admin/nominee-application" class="list-group-item list-group-item-action">Nominees' Registration</a>
                        <a href="/schedule-location" class="list-group-item list-group-item-action">Schedule & Location</a>
                        <a href="/campaign-material" class="list-group-item list-group-item-action">Campaign Materials</a>
                        <a href="<?= $annLink ?>" class="list-group-item list-group-item-action">Announcement</a>
                        <a href="#" class="list-group-item list-group-item-action">Cast Voting</a>
                        <a href="#" class="list-group-item list-group-item-action">Voting Result</a>
                        <a href="#" class="list-group-item list-group-item-action">Report</a>
                    </div>
                </div>

                <!-- Profile area fixed at bottom -->
                <div class="position-absolute bottom-0 start-0 end-0 border-top border-black border-1"
                    style="background:#f8f9fa; padding:10px;">
                    <div id="profileToggle" style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <img src="https://via.placeholder.com/40" alt="avatar"
                            style="width:40px;height:40px;border-radius:50%;">
                        <div style="flex:1;">
                            <div style="font-weight:600;"><?= htmlspecialchars($fullName) ?></div>
                            <div style="font-size:12px;color:#6c757d;"><?= htmlspecialchars($role) ?></div>

                        </div>
                        <div id="profileCaret" style="transition: transform .2s;">▾</div>
                    </div>

                    <div id="profileActions" style="display:none; margin-top:10px;">
                        <button id="btnProfile" class="btn btn-sm btn-outline-primary w-100 mb-1">Profile</button>
                        <!-- <button id="btnLogout" class="btn btn-sm btn-outline-danger w-100">Logout</button> -->
                        <a href="/logout" class="btn btn-sm btn-outline-danger w-100">Logout</a>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Profile toggle script (sidebar toggle removed) -->
        <script>
            $(function () {
                // Profile toggle
                let profileOpen = false;
                $("#profileToggle").on("click", function (e) {
                    e.preventDefault();
                    $("#profileActions").stop(true, true).slideToggle(200);
                    profileOpen = !profileOpen;
                    $("#profileCaret").css("transform", profileOpen ? "rotate(180deg)" : "rotate(0deg)");
                });

                // Close profile actions when clicking outside
                $(document).on("click", function (e) {
                    if (profileOpen && !$(e.target).closest("#profileToggle, #profileActions").length) {
                        $("#profileActions").stop(true, true).slideUp(200);
                        $("#profileCaret").css("transform", "rotate(0deg)");
                        profileOpen = false;
                    }
                });
            });
        </script>


        <!-- Main content placeholder (shifted right to accommodate fixed sidebar) -->
        <main id="content" class="m-3 w-100">