<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Checkout">
  <title>Checkout</title>

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
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
            <li class="breadcrumb-item active" aria-current="page">Checkout</li>
          </ol>
        </nav>

        <div class="row g-4">
          <div class="col-lg-7">
            <div class="promo-box text-start">
              <h2 class="promo-title">Billing Details</h2>
              <!-- TODO: Hook up billing form to backend later -->
              <form class="row g-3 mt-3" aria-label="Billing form">
                <div class="col-md-6">
                  <label class="form-label" for="firstName">First Name</label>
                  <input class="form-control" id="firstName" type="text" placeholder="John" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="lastName">Last Name</label>
                  <input class="form-control" id="lastName" type="text" placeholder="Doe" required>
                </div>
                <div class="col-12">
                  <label class="form-label" for="email">Email</label>
                  <input class="form-control" id="email" type="email" placeholder="john@example.com" required>
                </div>
                <div class="col-12">
                  <label class="form-label" for="address">Address</label>
                  <input class="form-control" id="address" type="text" placeholder="123 Main St" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="city">City</label>
                  <input class="form-control" id="city" type="text" placeholder="San Francisco" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label" for="state">State</label>
                  <input class="form-control" id="state" type="text" placeholder="CA" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label" for="zip">ZIP</label>
                  <input class="form-control" id="zip" type="text" placeholder="94107" required>
                </div>
                <div class="col-12">
                  <label class="form-label">Payment Method</label>
                  <div class="d-flex flex-column gap-2">
                    <label class="d-flex align-items-center gap-2">
                      <input type="radio" name="payment" checked> Credit Card (Visa / MasterCard)
                    </label>
                    <label class="d-flex align-items-center gap-2">
                      <input type="radio" name="payment"> PayPal
                    </label>
                    <label class="d-flex align-items-center gap-2">
                      <input type="radio" name="payment"> Cash on Delivery
                    </label>
                  </div>
                </div>
                <div class="col-12 d-flex justify-content-end">
                  <a class="btn btn-primary rounded-pill" href="order-success.php">Place Order</a>
                </div>
              </form>
            </div>
          </div>

          <div class="col-lg-5">
            <div class="promo-box">
              <h2 class="promo-title">Order Summary</h2>
              <div class="mt-3" data-checkout-summary></div>
              <hr>
              <div class="d-flex justify-content-between align-items-center">
                <strong>Total</strong>
                <strong data-cart-total>$0</strong>
              </div>
              <a class="btn btn-primary rounded-pill w-100 mt-3" href="order-success.php">Confirm and Pay</a>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- ================= FOOTER ================= -->
  <?php include __DIR__ . '/../partials/footer.php'; ?>
