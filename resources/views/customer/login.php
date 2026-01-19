<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Login to your account">
  <title>Login</title>

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
          <h1 class="section-title--centered text-center mb-3">Welcome Back</h1>
          <p class="section-subtitle text-center">Log in to manage your orders and checkout faster.</p>
          <form class="mt-4" aria-label="Login form">
            <div class="mb-3">
              <label class="form-label" for="loginEmail">Email</label>
              <input class="form-control" id="loginEmail" type="email" placeholder="you@example.com" required>
            </div>
            <div class="mb-3">
              <label class="form-label" for="loginPassword">Password</label>
              <input class="form-control" id="loginPassword" type="password" placeholder="••••••••" required>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <label class="d-flex align-items-center gap-2 mb-0">
                <input type="checkbox"> Remember me
              </label>
              <a href="forgot-password.php" class="link-primary">Forgot password?</a>
            </div>
            <button class="btn btn-primary rounded-pill w-100" type="submit">Login</button>
          </form>
          <p class="text-center text-muted mt-3 mb-0">New here? <a href="register.php">Create an account</a></p>
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
