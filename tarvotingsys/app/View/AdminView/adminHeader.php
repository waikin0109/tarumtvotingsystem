<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Jquery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

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
    </nav>

    <!-- Sidebar and Content wrapper -->
    <div class="d-flex">
        <!-- Sidebar (fixed to left, always visible) -->
        <aside id="sidebar" class="bg-light" style="width:250px; position:fixed; top:56px; left:0; height:calc(100vh - 56px); overflow:auto; border-right:1px solid #e9ecef; z-index:1020;">
            <div class="position-sticky pb-5">
                <div class="list-group list-group-flush">
                    <a href="#" class="list-group-item list-group-item-action">Election Event</a>
                    <a href="#" class="list-group-item list-group-item-action">Election Registration Form</a>
                    <a href="#" class="list-group-item list-group-item-action">Rules & Regulations</a>
                    <a href="#" class="list-group-item list-group-item-action">Nominees' Registration</a>
                    <a href="#" class="list-group-item list-group-item-action">Schedule & Location</a>
                    <a href="#" class="list-group-item list-group-item-action">Announcement</a>
                    <a href="#" class="list-group-item list-group-item-action">Cast Voting</a>
                    <a href="#" class="list-group-item list-group-item-action">Voting Result</a>
                    <a href="#" class="list-group-item list-group-item-action">Report</a>
                </div>
            </div>

            <!-- Profile area fixed at bottom -->
            <div style="position:absolute; bottom:0; left:0; right:0; border-top:1px solid #e9ecef; background:#f8f9fa; padding:10px;">
                <div id="profileToggle" style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                    <img src="https://via.placeholder.com/40" alt="avatar" style="width:40px;height:40px;border-radius:50%;">
                    <div style="flex:1;">
                        <div style="font-weight:600;">Simon</div>
                        <div style="font-size:12px;color:#6c757d;">Administrator</div>
                    </div>
                    <div id="profileCaret" style="transition: transform .2s;">▾</div>
                </div>

                <div id="profileActions" style="display:none; margin-top:10px;">
                    <button id="btnProfile" class="btn btn-sm btn-outline-primary w-100 mb-1">Profile</button>
                    <button id="btnLogout" class="btn btn-sm btn-outline-danger w-100">Logout</button>
                </div>
            </div>
        </aside>

        <!-- Main content placeholder (shifted right to accommodate fixed sidebar) -->
        <main id="content" class="flex-grow-1 p-4" style="margin-left:250px; padding-top:1rem;">
            <!-- Page content goes here -->
        </main>
    </div>

    <!-- Profile toggle script (sidebar toggle removed) -->
    <script>
        $(function() {
            // Profile toggle
            let profileOpen = false;
            $("#profileToggle").on("click", function(e) {
                e.preventDefault();
                $("#profileActions").stop(true, true).slideToggle(200);
                profileOpen = !profileOpen;
                $("#profileCaret").css("transform", profileOpen ? "rotate(180deg)" : "rotate(0deg)");
            });

            // Close profile actions when clicking outside
            $(document).on("click", function(e){
                if (profileOpen && !$(e.target).closest("#profileToggle, #profileActions").length) {
                    $("#profileActions").stop(true, true).slideUp(200);
                    $("#profileCaret").css("transform", "rotate(0deg)");
                    profileOpen = false;
                }
            });
        });
    </script>
