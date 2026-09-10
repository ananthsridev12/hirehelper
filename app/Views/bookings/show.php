<?php
/** @var array $booking */
/** @var array|null $review */
$pageTitle = 'Booking #' . $booking['id'];
$user = current_user();
$isCustomer = $user && (int) $user['id'] === (int) $booking['customer_id'];
$isProvider = $user && (int) $user['id'] === (int) $booking['provider_id'];
?>
<div class="container">
  <p><a href="<?= url($isProvider ? '/provider/dashboard' : '/bookings') ?>">&larr; Back</a></p>

  <div class="page-header">
    <h1 class="section-title">Booking #<?= (int) $booking['id'] ?></h1>
    <span class="badge badge-<?= e($booking['status']) ?>"><?= e(ucwords(str_replace('_', ' ', $booking['status']))) ?></span>
  </div>

  <div class="card">
    <h3><?= e($booking['service_name']) ?></h3>
    <p class="form-hint"><?= e($booking['category_name']) ?></p>
    <p><strong>Scheduled:</strong> <?= e($booking['scheduled_date']) ?> &middot; <?= e($booking['scheduled_time_slot']) ?></p>
    <p><strong>Price:</strong> <?= money((float) $booking['price']) ?>
      <span class="badge badge-<?= e($booking['payment_status']) ?>"><?= e(ucfirst($booking['payment_status'])) ?></span>
    </p>
    <?php if (!empty($booking['notes'])): ?>
      <p><strong>Notes:</strong> <?= nl2br(e($booking['notes'])) ?></p>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3>Address</h3>
    <p>
      <?= e($booking['address_label']) ?><br>
      <?= e($booking['line1']) ?><?= $booking['line2'] ? ', ' . e($booking['line2']) : '' ?><br>
      <?= e($booking['city']) ?>, <?= e($booking['state']) ?> - <?= e($booking['pincode']) ?><br>
      Phone: <?= e($booking['address_phone']) ?>
    </p>
  </div>

  <div class="card">
    <h3>People</h3>
    <p><strong>Customer:</strong> <?= e($booking['customer_name']) ?> (<?= e($booking['customer_phone']) ?>)</p>
    <p><strong>Professional:</strong> <?= $booking['provider_name'] ? e($booking['provider_name']) . ' (' . e($booking['provider_phone']) . ')' : 'Not yet assigned' ?></p>
  </div>

  <?php if ($isCustomer && in_array($booking['status'], ['pending', 'assigned'], true)): ?>
    <form method="post" action="<?= url('/bookings/' . $booking['id'] . '/cancel') ?>" data-confirm="Cancel this booking?">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-danger">Cancel Booking</button>
    </form>
  <?php endif; ?>

  <?php if ($isProvider && $booking['status'] === 'assigned'): ?>
    <form method="post" action="<?= url('/provider/bookings/' . $booking['id'] . '/status') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="status" value="in_progress">
      <button type="submit" class="btn">Start Job</button>
    </form>
  <?php endif; ?>

  <?php if ($isProvider && $booking['status'] === 'in_progress'): ?>
    <form method="post" action="<?= url('/provider/bookings/' . $booking['id'] . '/status') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="status" value="completed">
      <button type="submit" class="btn">Mark Completed</button>
    </form>
  <?php endif; ?>

  <?php if ($isCustomer && $booking['status'] === 'completed'): ?>
    <div class="card">
      <?php if ($review): ?>
        <h3>Your review</h3>
        <p class="rating"><?= str_repeat('★', (int) $review['rating']) . str_repeat('☆', 5 - (int) $review['rating']) ?></p>
        <p><?= nl2br(e($review['comment'])) ?></p>
      <?php else: ?>
        <h3>Rate this service</h3>
        <form method="post" action="<?= url('/bookings/' . $booking['id'] . '/review') ?>">
          <?= csrf_field() ?>
          <div class="form-group">
            <div class="star-input">
              <input type="radio" id="star5" name="rating" value="5"><label for="star5">★</label>
              <input type="radio" id="star4" name="rating" value="4"><label for="star4">★</label>
              <input type="radio" id="star3" name="rating" value="3"><label for="star3">★</label>
              <input type="radio" id="star2" name="rating" value="2"><label for="star2">★</label>
              <input type="radio" id="star1" name="rating" value="1" checked><label for="star1">★</label>
            </div>
          </div>
          <div class="form-group">
            <textarea name="comment" placeholder="How was your experience?"></textarea>
          </div>
          <button type="submit" class="btn">Submit Review</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
