<?php
use App\Helpers\Session;

/*
 * Flash partial - renders one-time flash messages.
 * Keys supported: success, error, warning, info, errors (array of field errors)
 */
Session::start();
$types = ['success' => 'alert-success', 'error' => 'alert-danger', 'warning' => 'alert-warning', 'info' => 'alert-info'];
$flash = $_SESSION['_flash'] ?? [];
$errors = $flash['errors'] ?? null;

$hasAny = false;
foreach (array_keys($types) as $k) {
  if (array_key_exists($k, $flash) && $flash[$k] !== null && $flash[$k] !== '') {
    $hasAny = true;
    break;
  }
}
if (!$hasAny && $errors === null) return; ?>

<div class="container mt-3">
  <div id="flash-container">
    <?php foreach ($types as $key => $class):
      $msg = $flash[$key] ?? null;
      if ($msg !== null && $msg !== ''): ?>
        <div class="alert <?= $class ?> alert-dismissible fade show" role="alert">
          <?= htmlspecialchars((string)$msg, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif;
    endforeach; ?>

    <?php if (is_array($errors) && !empty($errors)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
          <?php foreach ($errors as $f => $m): ?>
            <li><?= htmlspecialchars((string)$m, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></li>
          <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
// Auto-dismiss flash after 4 seconds
document.addEventListener('DOMContentLoaded', function() {
  const container = document.getElementById('flash-container');
  if (!container) return;
  setTimeout(() => {
    container.querySelectorAll('.alert').forEach(a => {
      // Use Bootstrap's JS if available
      try { var bsAlert = bootstrap.Alert.getOrCreateInstance(a); bsAlert.close(); } catch (e) { a.remove(); }
    });
  }, 4000);
});
</script>

<?php
// Clear flashes after rendering
unset($_SESSION['_flash']);
?>
