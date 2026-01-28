<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Create an account">
  <title>Register</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <link rel="stylesheet" href="../assets/css/style-refactored.css">
  <link rel="stylesheet" href="../assets/css/cart.css">
</head>

  <!-- Header intentionally removed for auth pages -->
  <body> <!-- auth-layout -->

  <main>
    <section class="min-vh-100 d-flex align-items-center">
      <div class="container d-flex justify-content-center">
        <div class="promo-box" style="max-width: 520px; width: 100%;">
          <h1 class="section-title--centered text-center mb-3">Create Account</h1>
          <p class="section-subtitle text-center">Join us for faster checkout and order tracking.</p>
          <?php \App\Core\Views::partial('partials.flash');
                $old = \App\Helpers\Session::getFlash('old', []);
          ?>

          <form class="mt-4" aria-label="Register form" method="post" action="/register">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label" for="regName">Full Name</label>
                <input class="form-control" id="regName" name="name" type="text" placeholder="John Doe" required value="<?= htmlspecialchars($old['name'] ?? '') ?>">
              </div>
              <div class="col-12">
                <label class="form-label" for="regEmail">Email</label>
                <input class="form-control" id="regEmail" name="email" type="email" placeholder="you@example.com" required value="<?= htmlspecialchars($old['email'] ?? '') ?>">
              </div>
              <div class="col-12">
                <label class="form-label" for="regPassword">Password</label>
                <input class="form-control" id="regPassword" name="password" type="password" placeholder="••••••••" required>
              </div>
              <div class="col-12">
                <label class="form-label" for="regConfirm">Confirm Password</label>
                <input class="form-control" id="regConfirm" name="password_confirmation" type="password" placeholder="••••••••" required>
              </div>
              <div class="col-12">
                <label class="d-flex align-items-center gap-2 mb-0">
                  <input type="checkbox" required> I agree to the Terms and Privacy Policy
                </label>
              </div>
              <div class="col-12">
                <button class="btn btn-primary rounded-pill w-100" type="submit">Create Account</button>
              </div>
            </div>
          </form>
          <p class="text-center text-muted mt-3 mb-0">Already have an account? <a href="/login">Login</a></p>
        </div>
      </div>
    </section>
  </main>

  <!-- Footer intentionally removed for auth pages -->



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/main.js"></script>
  <script src="../assets/js/cart.js"></script>
</body>
</html>
