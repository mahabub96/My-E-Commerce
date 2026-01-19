<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Order success">
  <title>Order Success</title>

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
      <div class="container text-center">
        <div class="promo-box">
          <div class="row justify-content-center">
            <div class="col-lg-8">
              <div class="d-flex justify-content-center mb-3">
                <div class="feature-card__icon" style="width:64px;height:64px;">
                  <i class="bi bi-check-lg"></i>
                </div>
              </div>
              <h1 class="section-title--centered">Order Placed Successfully</h1>
              <p class="section-subtitle">Thank you for your purchase. We sent the receipt and delivery details to your email.</p>

              <!-- TODO: show dynamic order details here -->
              <div class="row g-3 text-start mt-4">
                <div class="col-md-6">
                  <div class="feature-card">
                    <h3 class="feature-card__title">Order Summary</h3>
                    <p class="feature-card__text mb-2">Order ID: #EHYA-918273</p>
                    <p class="feature-card__text mb-2">Payment: Credit Card</p>
                    <p class="feature-card__text mb-0">Status: Processing</p>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="feature-card">
                    <h3 class="feature-card__title">Shipping</h3>
                    <p class="feature-card__text mb-2">John Doe</p>
                    <p class="feature-card__text mb-0">123 Main St, San Francisco, CA</p>
                  </div>
                </div>
              </div>

              <div class="mt-4 d-flex justify-content-center gap-2">
                <a class="btn btn-primary rounded-pill" href="shop.php">Continue Shopping</a>
                <a class="btn btn-outline-primary rounded-pill" href="profile.php">View Orders</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- ================= FOOTER ================= -->
  <?php include __DIR__ . '/../partials/footer.php'; ?>
