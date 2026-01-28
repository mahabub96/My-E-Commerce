<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Contact us">
  <title>Contact</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <link rel="stylesheet" href="../assets/css/style-refactored.css">
  <link rel="stylesheet" href="../assets/css/cart.css">
</head>
<body style="background-color: #eaf7fb;">
  <!-- ================= NAVBAR ================= -->
  <?php include __DIR__ . '/../partials/header.php'; ?>

  <main>
    <section class="py-5">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-md-10 col-lg-8">
            <h1 class="mb-3">Contact Us</h1>
            <p class="text-muted">Have a question or feedback? Fill the form and we'll get back to you within 1–2 business days.</p>

            <form class="row g-3 mt-4" aria-label="Contact form">
              <div class="col-md-6">
                <label class="form-label" for="contactName">Name</label>
                <input class="form-control" id="contactName" type="text" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="contactEmail">Email</label>
                <input class="form-control" id="contactEmail" type="email" required>
              </div>
              <div class="col-12">
                <label class="form-label" for="contactSubject">Subject</label>
                <input class="form-control" id="contactSubject" type="text" required>
              </div>
              <div class="col-12">
                <label class="form-label" for="contactMessage">Message</label>
                <textarea class="form-control" id="contactMessage" rows="6" required></textarea>
              </div>
              <div class="col-12 d-grid">
                <button class="btn btn-primary rounded-pill" type="submit">Send Message</button>
              </div>
            </form>

            <div class="mt-5">
              <h5>Other ways to reach us</h5>
              <ul class="list-unstyled">
                <li>Email: <a href="mailto:support@example.com">support@example.com</a></li>
                <li>Phone: <a href="tel:+123456789">+1 234 567 89</a></li>
              </ul>
            </div>

          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- ================= FOOTER ================= -->
  <?php include __DIR__ . '/../partials/footer.php'; ?>