<?php
/** @var array $bookings */
/** @var array|null $profile */
/** @var array $rating */
$pageTitle = 'Provider Dashboard';
$avg = $rating['avg_rating'] !== null ? round((float) $rating['avg_rating'], 1) : null;
?>
<div class="container">
  <div class="page-header">
    <h1 class="section-title">My Dashboard</h1>
    <div style="display:flex;align-items:center;gap:12px;">
      <?php if ($profile && !empty($profile['photo_path'])): ?>
        <img class="avatar" src="<?= asset($profile['photo_path']) ?>" alt="My photo">
      <?php endif; ?>
      <a class="btn btn-sm btn-outline" href="<?= url('/provider/profile') ?>">Edit Profile</a>
    </div>
  </div>

  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-icon"><?= icon('calendar') ?></div>
      <div><div class="value"><?= count($bookings) ?></div><div class="label">Total jobs</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><?= icon('star') ?></div>
      <div><div class="value"><?= $avg !== null ? $avg : '-' ?></div><div class="label"><?= (int) $rating['review_count'] ?> reviews</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><?= icon('shield') ?></div>
      <div><div class="value"><?= $profile && $profile['is_verified'] ? 'Verified' : 'Pending' ?></div><div class="label">Verification status</div></div>
    </div>
  </div>

  <?php if ($profile && !$profile['is_verified']): ?>
    <div class="alert alert-error">Your profile is awaiting admin verification. You can still receive jobs once verified.</div>
  <?php endif; ?>

  <h2 class="section-title">My Jobs</h2>
  <?php if (empty($bookings)): ?>
    <div class="empty-state">No jobs assigned yet.</div>
  <?php else: ?>
    <div class="card">
      <?php foreach ($bookings as $booking): ?>
        <div class="booking-row">
          <div>
            <h4><?= e($booking['service_name']) ?></h4>
            <div class="meta">
              <?= e($booking['scheduled_date']) ?> &middot; <?= e($booking['scheduled_time_slot']) ?> &middot;
              <?= e($booking['city']) ?>
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
