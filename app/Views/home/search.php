<?php
/** @var string $query */
/** @var array $results */
$pageTitle = 'Search';
?>
<div class="container">
  <h1 class="section-title">Search results for &ldquo;<?= e($query) ?>&rdquo;</h1>

  <?php if ($query === ''): ?>
    <p class="section-sub">Type something in the search box above to find a service.</p>
  <?php elseif (empty($results)): ?>
    <div class="empty-state">No services matched &ldquo;<?= e($query) ?>&rdquo;. Try a different term, like "AC" or "cleaning".</div>
  <?php else: ?>
    <p class="section-sub"><?= count($results) ?> service(s) found.</p>
    <div class="grid">
      <?php foreach ($results as $service): ?>
        <a class="service-card" href="<?= url('/service/' . $service['slug']) ?>">
          <?php if (!empty($service['image_path'])): ?>
            <img class="thumb" src="<?= asset($service['image_path']) ?>" alt="<?= e($service['name']) ?>">
          <?php else: ?>
            <div class="thumb-fallback"><?= icon('home') ?></div>
          <?php endif; ?>
          <div class="body">
            <h3><?= e($service['name']) ?></h3>
            <div class="meta"><?= e($service['category_name']) ?> &middot; <?= (int) $service['duration_minutes'] ?> mins</div>
            <div class="price"><?= money((float) $service['price']) ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
