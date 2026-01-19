<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Reset your password">
  <title>Reset Password</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style-refactored.css">
</head>
<body>
  <!-- Auth pages do not show header/footer -->
  <main>
    <section class="min-vh-100 d-flex align-items-center">
      <div class="container d-flex justify-content-center">
        <div class="promo-box" style="max-width:520px; width:100%;">
          <h1 class="section-title--centered text-center mb-3">Reset Password</h1>
          <p class="text-muted text-center">Enter your email and we'll send a link to reset your password.</p>
          <form class="mt-4">
            <div class="mb-3">
              <label class="form-label" for="fpEmail">Email</label>
              <input class="form-control" id="fpEmail" type="email" required>
            </div>
            <button class="btn btn-primary rounded-pill w-100" type="submit">Send Reset Link</button>
            <p class="text-center text-muted mt-3 mb-0">Remembered? <a href="login.php">Login</a></p>
          </form>
        </div>
      </div>
    </section>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
