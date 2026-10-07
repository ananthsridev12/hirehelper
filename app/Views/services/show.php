<?php
/** @var array $service */
/** @var array $rating */
$pageTitle = $service['name'];
$user = current_user();
$avg = $rating['avg_rating'] !== null ? round((float) $rating['avg_rating'], 1) : null;
?>
<div class="container">
  <p>
    <a href="<?= url('/') ?>">Categories</a> &rsaquo;
    <a href="<?= url('/category/' . $service['category_slug']) ?>"><?= e($service['category_name']) ?></a>
  </p>

  <div class="service-detail">
    <div>
      <?php if (!empty($service['image_path'])): ?>
        <img class="service-hero-image" src="<?= asset($service['image_path']) ?>" alt="<?= e($service['name']) ?>">
      <?php endif; ?>
      <h1 class="section-title"><?= e($service['name']) ?></h1>
      <?php if ($avg !== null): ?>
        <p class="rating"><?= icon('star', 'icon') ?> <?= $avg ?> &middot; <?= (int) $rating['review_count'] ?> reviews</p>
      <?php endif; ?>
      <div class="card">
        <h3>About this service</h3>
        <p><?= nl2br(e($service['description'])) ?></p>
        <p class="form-hint">Estimated duration: <?= (int) $service['duration_minutes'] ?> minutes</p>
      </div>
      <div class="card">
        <h3><?= icon('shield', 'icon') ?> Verified professionals, guaranteed</h3>
        <p class="form-hint">Every professional on HireHelper is background-checked and rated by real customers. Not happy with the job? Tell us from your booking page and we'll make it right.</p>
      </div>
    </div>

    <div class="card booking-box">
      <div class="price"><?= money((float) $service['price']) ?></div>
      <p class="form-hint">Price may vary based on inspection.</p>
      <?php if (!$user): ?>
        <a class="btn btn-block" href="<?= url('/login') ?>">Log in to book</a>
      <?php elseif ($user['role'] !== 'customer'): ?>
        <p class="form-hint">Only customer accounts can book services.</p>
      <?php else: ?>
        <a class="btn btn-block" href="<?= url('/book/' . $service['slug']) ?>">Book Now</a>
      <?php endif; ?>
    </div>
  </div>
</div>
