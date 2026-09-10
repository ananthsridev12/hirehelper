<?php
/** @var array $bookings */
$pageTitle = 'My Bookings';
?>
<div class="container">
  <h1 class="section-title">My Bookings</h1>

  <?php if (empty($bookings)): ?>
    <div class="empty-state">
      <p>You haven't booked any services yet.</p>
      <a class="btn" href="<?= url('/') ?>">Browse services</a>
    </div>
  <?php else: ?>
    <div class="card">
      <?php foreach ($bookings as $booking): ?>
        <div class="booking-row">
          <div>
            <h4><?= e($booking['service_name']) ?></h4>
            <div class="meta">
              <?= e($booking['scheduled_date']) ?> &middot; <?= e($booking['scheduled_time_slot']) ?>
              <?php if ($booking['provider_name']): ?> &middot; Pro: <?= e($booking['provider_name']) ?><?php endif; ?>
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:12px;">
            <span class="badge badge-<?= e($booking['status']) ?>"><?= e(ucwords(str_replace('_', ' ', $booking['status']))) ?></span>
            <a class="btn btn-sm btn-outline" href="<?= url('/bookings/' . $booking['id']) ?>">View</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
