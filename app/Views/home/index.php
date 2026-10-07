<?php
/** @var array $categories */
$pageTitle = 'Home';
?>
<section class="hero">
  <div class="container">
    <h1>Home services, done right.</h1>
    <p>Book trusted, verified professionals for cleaning, repairs, salon and more &mdash; at your doorstep.</p>
    <form class="hero-search" method="get" action="<?= url('/search') ?>">
      <input type="text" name="q" placeholder="Try &ldquo;AC repair&rdquo; or &ldquo;home cleaning&rdquo;">
      <button type="submit">Search</button>
    </form>
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
          <?php if (!empty($category['image_path'])): ?>
            <img class="thumb" src="<?= asset($category['image_path']) ?>" alt="<?= e($category['name']) ?>">
          <?php else: ?>
            <div class="thumb-fallback"><?= icon($category['icon']) ?></div>
          <?php endif; ?>
          <div class="body">
            <h3><?= e($category['name']) ?></h3>
            <p><?= e($category['description']) ?></p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
