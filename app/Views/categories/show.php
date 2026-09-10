<?php
/** @var array $category */
/** @var array $services */
$pageTitle = $category['name'];
?>
<div class="container">
  <p><a href="<?= url('/') ?>">&larr; All categories</a></p>
  <h1 class="section-title"><?= e($category['name']) ?></h1>
  <p class="section-sub"><?= e($category['description']) ?></p>

  <?php if (empty($services)): ?>
    <div class="empty-state">No services available under this category yet.</div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($services as $service): ?>
        <a class="service-card" href="<?= url('/service/' . $service['slug']) ?>">
          <h3><?= e($service['name']) ?></h3>
          <div class="meta"><?= (int) $service['duration_minutes'] ?> mins</div>
          <div class="price"><?= money((float) $service['price']) ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
