<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Admin login">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <title>Admin Login</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/volt.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-app d-flex align-items-center justify-content-center" style="background:#f5f6fa;">
  <div class="admin-card" style="max-width: 420px; width: 100%;">
    <div class="text-center mb-3">
      <h1 class="h4 mb-1">Admin Panel</h1>
      <p class="text-muted mb-0">Sign in to manage the store</p>
    </div>
    <?php \App\Core\Views::partial('partials.flash'); ?>
    <form class="mt-3" aria-label="Admin login form" method="post" action="/admin/login">
      <input type="hidden" name="_token" value="<?= csrf_token() ?>">
      <div class="mb-3">
        <label class="form-label" for="adminEmail">Email</label>
        <input class="form-control" id="adminEmail" name="email" type="email" placeholder="admin@example.com" required>
      </div>
      <div class="mb-3">
        <label class="form-label" for="adminPassword">Password</label>
        <input class="form-control" id="adminPassword" name="password" type="password" placeholder="••••••••" required>
      </div>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <label class="d-flex align-items-center gap-2 mb-0"><input type="checkbox" name="remember"> Remember me</label>
        <a class="link-primary" href="#">Forgot password?</a>
      </div>
      <button class="btn btn-primary w-100" type="submit">Login</button>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/assets/js/admin.js"></script>
</body>
</html>
