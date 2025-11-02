<?php
$errors = $errors ?? [];
$oldData = $oldData ?? [];
$oldLoginID = htmlspecialchars($oldData['loginID'] ?? '', ENT_QUOTES, 'UTF-8');

function invalidClass(array $errors, string $field): string
{
  return !empty($errors[$field]) ? ' is-invalid' : '';
}
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

  <title>Login</title>

</head>

<body>

  <div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;padding:2rem 1rem;">
    <div class="w-100" style="max-width: 520px;">
      <!-- Rounded panel -->
      <div class="mx-auto" style="background:#f3f4f6;border-radius:20px;box-shadow:0 12px 30px rgba(0,0,0,.15);
        border:1px solid rgba(0,0,0,.05);padding:2rem;">
        <!-- Header -->
        <div class="d-flex justify-content-center mb-4">
          <img class="tarumt-logo mb-2 img-fluid" src="image/tarumtLogo.jpg" alt="TAR UMT logo">
        </div>

        <!-- Title -->
        <h2 class="h5 text-center fw-semibold mb-4">Please Enter Your Information</h2>

        <?php if (!empty($errors['global'])): ?>
          <div id="globalError" class="alert alert-danger py-2">
            <?php foreach ($errors['global'] as $msg): ?>
              <div><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Form -->
        <form id="loginForm" method="POST" action="/login" novalidate class="mx-auto" style="max-width:460px;">
          <!-- Login ID -->
          <div class="input-group mb-3" style="border-radius:18px;">
            <span class="input-group-text" id="loginIdLabel"
              style="background:#fff;min-width:100px;border-right:1px solid #ced4da;justify-content:flex-start;font-weight:500;">
              Login ID
            </span>
            <input type="text" class="form-control <?= !empty($errors['loginID']) ? ' is-invalid' : ''; ?>"
              value="<?= $oldLoginID ?>" name="loginID" id="loginID" placeholder="e.g. 2402578" autocomplete="username"
              required inputmode="numeric" pattern="\d{7}" maxlength="7" style="background:#fff;">
            <!-- unified login ID error element (server+client share this) -->
            <div id="loginIDError" class="invalid-feedback d-block mb-2"
              style="<?= empty($errors['loginID']) ? 'display:none;' : '' ?>">
              <?= !empty($errors['loginID'][0]) ? htmlspecialchars($errors['loginID'][0], ENT_QUOTES, 'UTF-8') : '' ?>
            </div>

          </div>

          <!-- Password -->
          <div class="input-group mb-4" style="border-radius:18px;">
            <span class="input-group-text" id="passwordLabel"
              style="background:#fff;min-width:100px;border-right:1px solid #ced4da;justify-content:flex-start;font-weight:500;color:#495057;">
              Password
            </span>
            <input type="password" class="form-control <?= !empty($errors['password']) ? ' is-invalid' : ''; ?>"
              name="password" id="password" placeholder="" autocomplete="current-password" required
              style="background:#fff;">

            <div id="passwordError" class="invalid-feedback d-block mb-3"
              style="<?= empty($errors['password']) ? 'display:none;' : '' ?>">
              <?= !empty($errors['password'][0]) ? htmlspecialchars($errors['password'][0], ENT_QUOTES, 'UTF-8') : '' ?>
            </div>

          </div>

          <div class="text-center">
            <button type="submit" class="btn"
              style="border-radius:9px;padding:0.5rem 2rem;background:#fff;border:1px solid #ced4da;">
              Login
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    $(function () {
      const $loginID = $('#loginID');
      const $loginErr = $('#loginIDError');
      const $password = $('#password');
      const $passErr = $('#passwordError');
      const $globalErr = $('#globalError');
      const re7 = /^\d{7}$/;

      function hide($el) { $el.text('').removeClass('d-block').hide(); }
      function show($el, msg) { $el.text(msg).addClass('d-block').show(); }

      // add suppressGlobal to avoid hiding the banner on initial load
      function validateLoginID(suppressGlobal = false) {
        const v = $loginID.val().trim();
        if (v === '') {
          $loginID.removeClass('is-valid').addClass('is-invalid');
          if (!suppressGlobal && $globalErr.length) $globalErr.hide();
          show($loginErr, 'Login ID is required.');
          return false;
        }
        if (!re7.test(v)) {
          $loginID.removeClass('is-valid').addClass('is-invalid');
          if (!suppressGlobal && $globalErr.length) $globalErr.hide();
          show($loginErr, 'Login ID must be exactly 7 digits.');
          return false;
        }
        $loginID.removeClass('is-invalid').addClass('is-valid');
        hide($loginErr);
        if (!suppressGlobal && $globalErr.length) $globalErr.hide();
        return true;
      }

      function validatePassword(suppressGlobal = false) {
        const v = $password.val().trim();
        if (v === '') {
          $password.removeClass('is-valid').addClass('is-invalid');
          if (!suppressGlobal && $globalErr.length) $globalErr.hide();
          show($passErr, 'Password is required.');
          return false;
        }
        $password.removeClass('is-invalid').addClass('is-valid');
        hide($passErr);
        if (!suppressGlobal && $globalErr.length) $globalErr.hide();
        return true;
      }

      //Initial pass: clear field errors if valid, but DO NOT hide the global banner
      validateLoginID(true);
      validatePassword(true);

      // On user interaction, validate and hide global if they change something
      $loginID.on('input blur', () => validateLoginID(false));
      $password.on('input blur', () => validatePassword(false));

      $('#loginForm').on('submit', function (e) {
        const ok = validateLoginID(false) && validatePassword(false);
        if (!ok) { e.preventDefault(); e.stopPropagation(); }
      });
    });
  </script>

</body>

</html>