<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Admin dashboard">
  <title>Admin Dashboard</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/volt.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-app d-flex">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="flex-grow-1">
    <header class="admin-topbar navbar navbar-light px-3">
      <button class="btn btn-outline-secondary d-lg-none" type="button" data-admin-toggle>
        <i class="bi bi-list"></i>
      </button>
      <span class="navbar-brand mb-0">Dashboard</span>
    </header>

    <main class="admin-main container">
      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <div class="admin-card admin-stat">
            <div>
              <p class="text-muted mb-1">Revenue</p>
              <h3 class="mb-0">$84,200</h3>
            </div>
            <span class="badge bg-success">+12%</span>
          </div>
        </div>
        <div class="col-md-4">
          <div class="admin-card admin-stat">
            <div>
              <p class="text-muted mb-1">Orders</p>
              <h3 class="mb-0">1,204</h3>
            </div>
            <span class="badge bg-success">+5%</span>
          </div>
        </div>
        <div class="col-md-4">
          <div class="admin-card admin-stat">
            <div>
              <p class="text-muted mb-1">Customers</p>
              <h3 class="mb-0">4,812</h3>
            </div>
            <span class="badge bg-secondary">Stable</span>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-7">
          <div class="admin-card">
            <h5 class="mb-3">Recent Orders</h5>
            <div class="table-responsive">
              <!-- TODO: Replace table rows with real orders from DB -->
              <table class="table admin-table align-middle mb-0">
                <thead>
                  <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>#918273</td>
                    <td>John Doe</td>
                    <td>Jan 10</td>
                    <td>$1,739</td>
                    <td><span class="badge bg-success">Processing</span></td>
                  </tr>
                  <tr>
                    <td>#918200</td>
                    <td>Alice Lee</td>
                    <td>Dec 28</td>
                    <td>$299</td>
                    <td><span class="badge bg-secondary">Delivered</span></td>
                  </tr>
                  <tr>
                    <td>#918199</td>
                    <td>Martin K.</td>
                    <td>Dec 26</td>
                    <td>$529</td>
                    <td><span class="badge bg-warning text-dark">Pending</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="admin-card">
            <h5 class="mb-3">Top Categories</h5>
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between align-items-center">Laptops <span class="badge bg-primary">45%</span></li>
              <li class="list-group-item d-flex justify-content-between align-items-center">Audio <span class="badge bg-primary">25%</span></li>
              <li class="list-group-item d-flex justify-content-between align-items-center">Home Appliances <span class="badge bg-primary">18%</span></li>
              <li class="list-group-item d-flex justify-content-between align-items-center">Health <span class="badge bg-primary">12%</span></li>
            </ul>
          </div>
        </div>
      </div>
    </main>
  </div>

  <?php include __DIR__ . '/partials/footer.php'; ?>