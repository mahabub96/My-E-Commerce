# CLEANUP & Integration Checklist

This file contains a compact checklist and code snippets to help you move these PHP views into your backend project and verify everything works.

1) Copy or move files
   - Move `resources/views/*` into the view directory your backend uses (if different).
   - Ensure `resources/views/assets/*` is reachable by your webserver (or copy to the backend's public/static folder).

2) Verify asset paths
   - Open a page and check browser devtools Network tab for 404s (CSS/JS/image). If you see missing assets, either:
     - Copy `resources/views/assets` into your backend public folder, or
     - Update the `../assets/...` references in the views to the correct public path (small search/replace).

3) Wiring dynamic data
   - Replace `<!-- TODO -->` markers by echoing variables or loops from your controllers. Example for products:
     <?php foreach ($products as $p): ?>
       <div class="product-card"><?php echo htmlspecialchars($p['name']); ?></div>
     <?php endforeach; ?>

4) Header and admin sidebar
   - Header active logic is server-side in `partials/header.php` (no extra JS needed).
   - Admin sidebar selects active item server-side (`admin/partials/sidebar.php`). If your routing uses route names (not filename paths), update the matching logic in these partials accordingly.

5) Small smoke-test
   - Start your backend server and visit: `/`, `/shop`, `/product`, `/admin/dashboard`, `/admin/products`.
   - Verify header active highlighting and admin sidebar active highlighting.

6) Optional: Add an automated sync or build step
   - If you plan to keep `frontend/assets` as the canonical source, add a sync step in your build pipeline to copy changes into the view assets folder.

If you want, I can help create the small scripts to copy assets or add a short example controller snippet for your backend framework (specify the framework and I’ll prepare one).