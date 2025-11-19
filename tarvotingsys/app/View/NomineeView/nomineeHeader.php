<?php

// Retrieve session data (set from LoginController)
$accountLoggedInId   = $_SESSION['accountID'] ?? '';
$roleId              = $_SESSION['roleID'] ?? null;
$nommineeLoggedInId  = is_scalar($roleId) ? (string)$roleId : ''; 
$fullName            = $_SESSION['fullName'] ?? 'Guest';
$role                = $_SESSION['role'] ?? 'User';
$annLink             = ($role === 'NOMINEE') ? '/announcements' : '/announcements/public';

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

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

    <!-- Bootstrap Icons (for sidebar icons) -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous"></script>

    <!-- Tab Title -->
    <title><?php echo $_title ?? 'TARUMTVS' ?></title>
    <link rel="stylesheet" type="text/css" href="/css/app.css">

    <!-- Override active menu color to yellow only for nominee -->
    <style>
        #sidebar .list-group-item.active-menu {
            background-color: #ffc107;
            color: #212529;
            font-weight: 500;
        }
        #sidebar .list-group-item.active-menu i {
            color: #212529;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-warning shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="/nominee/home">
                <img src="/image/tarucLogoSmall.png"
                     alt="TAR UMT logo"
                     class="d-inline-block align-text-top me-2 img-fluid"
                     style="width: 30px; height:auto;">
                <span class="fw-semibold text-dark">TARUMT Voting System</span>
            </a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <small class="text-dark"><?= htmlspecialchars($role) ?> Portal</small>
                    </li>
                </ul>
            </div>
            
            

            <!-- Mobile sidebar toggle -->
            <button class="navbar-toggler" type="button" id="btnSidebarToggle">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <!-- Flash Message Setup Here -->
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
                <div class="position-fixed top-0 start-50 translate-middle-x mt-3 w-100"
                     style="max-width: 600px; z-index: 2000;">
                    <div class="alert <?= $alertClass ?> alert-dismissible fade show shadow-lg text-center" 
                        id="flash-message-<?= $type ?>" 
                        role="alert">
                        <?= htmlspecialchars($message) ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <script>
                // Auto-dismiss all flash messages after 3 seconds
                setTimeout(() => {
                    document.querySelectorAll('[id^="flash-message-"]').forEach(flash => {
                        flash.classList.remove('show');
                        flash.classList.add('fade');
                        setTimeout(() => flash.remove(), 500);
                    });
                }, 3000);
            </script>

            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>
    </nav>

    <!-- Sidebar backdrop for mobile -->
    <div id="sidebar-backdrop"></div>

    <!-- Sidebar and Content wrapper -->
    <div class="d-flex layout-wrapper">
        <!-- Sidebar -->
        <div>
            <aside id="sidebar"
                class="bg-light position-relative start-0 overflow-auto border-end border-white border-1"
                style="height:calc(100vh - 56px);">
                <div class="position-sticky pb-5">

                    <div class="menu-title">Election Setup</div>
                    <div class="list-group list-group-flush">
                        <a href="/nominee/election-registration-form"
                           class="list-group-item list-group-item-action <?= $currentPath === '/nominee/election-registration-form' ? 'active-menu' : '' ?>">
                            <i class="bi bi-ui-checks-grid"></i>
                            <span>Election Registration</span>
                        </a>
                        <a href="/nominee/rule"
                           class="list-group-item list-group-item-action <?= $currentPath === '/nominee/rule' ? 'active-menu' : '' ?>">
                            <i class="bi bi-card-text"></i>
                            <span>Rules &amp; Regulations</span>
                        </a>
                        <a href="/nominee/nominee-final-list"
                           class="list-group-item list-group-item-action <?= $currentPath === '/nominee/nominee-final-list' ? 'active-menu' : '' ?>">
                            <i class="bi bi-people"></i>
                            <span>Nominees' Final List</span>
                        </a>
                        <a href="/nominee/schedule-location"
                           class="list-group-item list-group-item-action <?= $currentPath === '/nominee/schedule-location' ? 'active-menu' : '' ?>">
                            <i class="bi bi-geo-alt"></i>
                            <span>Schedule &amp; Location</span>
                        </a>
                        <a href="/nominee/campaign-material"
                           class="list-group-item list-group-item-action <?= $currentPath === '/nominee/campaign-material' ? 'active-menu' : '' ?>">
                            <i class="bi bi-megaphone"></i>
                            <span>Campaign Materials</span>
                        </a>
                        <a href="<?= htmlspecialchars($annLink) ?>"
                           class="list-group-item list-group-item-action <?= $currentPath === $annLink ? 'active-menu' : '' ?>">
                            <i class="bi bi-bell"></i>
                            <span>Announcement</span>
                        </a>
                    </div>

                    <div class="menu-title mt-3">Voting &amp; Results</div>
                    <div class="list-group list-group-flush">
                        <a href="/nominee/cast-vote"
                           class="list-group-item list-group-item-action <?= $currentPath === '/nominee/cast-vote' ? 'active-menu' : '' ?>">
                            <i class="bi bi-box-arrow-in-right"></i>
                            <span>Cast Voting</span>
                        </a>
                        <a href="/nominee/voting-result"
                           class="list-group-item list-group-item-action <?= $currentPath === '/nominee/voting-result' ? 'active-menu' : '' ?>">
                            <i class="bi bi-bar-chart-line"></i>
                            <span>Voting Result</span>
                        </a>
                    </div>
                </div>

                <!-- Profile area fixed at bottom (same as you had) -->
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
                        <a href="/logout" class="btn btn-sm btn-outline-danger w-100">Logout</a>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Scripts: sidebar + profile toggle -->
        <script>
            $(function () {
                // Sidebar toggle for mobile
                const $sidebar = $('#sidebar');
                const $backdrop = $('#sidebar-backdrop');

                $('#btnSidebarToggle').on('click', function () {
                    $sidebar.toggleClass('show');
                    $backdrop.toggleClass('show');
                });

                $backdrop.on('click', function () {
                    $sidebar.removeClass('show');
                    $backdrop.removeClass('show');
                });

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

        <!-- Main content -->
        <main id="content" class="m-3 w-100">
