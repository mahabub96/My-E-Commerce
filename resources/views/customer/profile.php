<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Profile">
  <title>Profile</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <link rel="stylesheet" href="../assets/css/style-refactored.css">
  <link rel="stylesheet" href="../assets/css/cart.css">
</head>
<body>
  <!-- ================= NAVBAR ================= -->
  <?php include __DIR__ . '/../partials/header.php'; ?>

  <main>
    <section class="py-5">
      <div class="container">
        <div class="page-hero">
          <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
              <h1 class="page-hero__title">Profile</h1>
              <p class="page-hero__subtitle">Manage your personal info and orders.</p>
            </div>
            <a class="btn btn-primary rounded-pill" href="checkout.php">Go to Checkout</a>
          </div>
        </div>

        <div class="row g-4">
          <div class="col-lg-4">
            <div class="feature-card h-100">
              <div class="d-flex align-items-center gap-3 mb-3">
                <div class="feature-card__icon"><i class="bi bi-person"></i></div>
                <div>
                  <h3 class="feature-card__title">John Doe</h3>
                  <p class="feature-card__text mb-0">john@example.com</p>
                </div>
              </div>
              <p class="feature-card__text mb-2">Address: 123 Main St, San Francisco, CA</p>
              <p class="feature-card__text mb-0">Phone: (555) 123-4567</p>
            </div>
          </div>

          <div class="col-lg-8">
            <div class="feature-card">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="feature-card__title mb-0">Order History</h3>
                <a class="btn btn-outline-primary rounded-pill btn-sm" href="orders.php">View All</a>
              </div>
              <div class="table-responsive">
                <!-- TODO: Replace with real orders from database -->
                <table class="table align-middle mb-0">
                  <thead>
                    <tr>
                      <th scope="col">Order ID</th>
                      <th scope="col">Date</th>
                      <th scope="col">Total</th>
                      <th scope="col">Status</th>
                      <th scope="col">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>#EHYA-918273</td>
                      <td>Jan 10, 2026</td>
                      <td>$1,739</td>
                      <td><span class="badge bg-success">Processing</span></td>
                      <td><a class="btn btn-outline-primary btn-sm rounded-pill" href="order-success.php">View</a></td>
                    </tr>
                    <tr>
                      <td>#EHYA-918200</td>
                      <td>Dec 28, 2025</td>
                      <td>$299</td>
                      <td><span class="badge bg-secondary">Delivered</span></td>
                      <td><a class="btn btn-outline-primary btn-sm rounded-pill" href="order-success.php">View</a></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- ================= FOOTER ================= -->
  <?php include __DIR__ . '/../partials/footer.php'; ?>
