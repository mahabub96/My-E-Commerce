<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Order details">
  <title>Order Details</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/volt.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-app d-flex">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="flex-grow-1">
    <header class="admin-topbar navbar navbar-light px-3">
      <button class="btn btn-outline-secondary d-lg-none" type="button" data-admin-toggle><i class="bi bi-list"></i></button>
      <span class="navbar-brand mb-0">Order #1322</span>
      <div class="ms-auto d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" type="button">Print</button>
        <button class="btn btn-primary btn-sm" type="button">Update Status</button>
      </div>
    </header>

    <main class="admin-main container">
      <div class="row g-3">
        <div class="col-lg-8">
          <div class="admin-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h5 class="mb-0">Items</h5>
              <span class="badge bg-success">Paid</span>
            </div>
            <div class="table-responsive">
              <!-- TODO: Replace items with actual order items -->
              <table class="table align-middle mb-0">
                <thead>
                  <tr>
                    <th scope="col">Product</th>
                    <th scope="col">SKU</th>
                    <th scope="col">Qty</th>
                    <th scope="col">Price</th>
                    <th scope="col" class="text-end">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Asus Zenbook UX-430 US</td>
                    <td>SKU-0001</td>
                    <td>1</td>
                    <td>$1,299</td>
                    <td class="text-end">$1,299</td>
                  </tr>
                  <tr>
                    <td>JBL Go 3 Speaker</td>
                    <td>SKU-0100</td>
                    <td>2</td>
                    <td>$49</td>
                    <td class="text-end">$98</td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <th colspan="4" class="text-end">Subtotal</th>
                    <th class="text-end">$1,397</th>
                  </tr>
                  <tr>
                    <th colspan="4" class="text-end">Shipping</th>
                    <th class="text-end">$25</th>
                  </tr>
                  <tr>
                    <th colspan="4" class="text-end">Total</th>
                    <th class="text-end">$1,422</th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="admin-card mb-3">
            <h6 class="mb-2">Customer</h6>
            <p class="mb-1 fw-semibold">Jane Doe</p>
            <p class="mb-1">jane.doe@email.com</p>
            <p class="mb-0">+1 555 123 4567</p>
          </div>
          <div class="admin-card mb-3">
            <h6 class="mb-2">Shipping Address</h6>
            <p class="mb-0">123 Market Street<br>San Francisco, CA 94103<br>United States</p>
          </div>
          <div class="admin-card">
            <h6 class="mb-3">Status</h6>
            <div class="d-flex flex-column gap-2">
              <select class="form-select">
                <option selected>Paid</option>
                <option>Pending</option>
                <option>Shipped</option>
                <option>Delivered</option>
              </select>
              <textarea class="form-control" rows="2" placeholder="Internal note"></textarea>
              <div class="d-flex gap-2 justify-content-end">
                <button class="btn btn-outline-secondary" type="button">Cancel</button>
                <button class="btn btn-primary" type="button">Save</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <?php include __DIR__ . '/partials/footer.php'; ?>