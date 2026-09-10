<?php
/** @var array $categories */
$pageTitle = 'Home';
?>
<section class="hero">
  <div class="container">
    <h1>Home services, done right.</h1>
    <p>Book trusted professionals for cleaning, repairs, salon and more &mdash; at your doorstep.</p>
  </div>
</section>

<div class="container">
  <h2 class="section-title">What are you looking for?</h2>
  <p class="section-sub">Browse services by category and book in a few clicks.</p>

  <?php if (empty($categories)): ?>
    <div class="empty-state">No categories available yet. Please check back soon.</div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($categories as $category): ?>
        <a class="category-card" href="<?= url('/category/' . $category['slug']) ?>">
          <?= icon($category['icon']) ?>
          <h3><?= e($category['name']) ?></h3>
          <p><?= e($category['description']) ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
