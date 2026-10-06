<?php
/** @var array $booking */
/** @var array|null $review */
/** @var float|null $distanceKm */
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

  <?php if ($isCustomer && $booking['status'] === 'assigned' && $booking['start_otp']): ?>
    <div class="card" style="border-color:var(--color-primary);">
      <h3>Your start code</h3>
      <p>Share this code with your professional when they arrive — it confirms they're on the right job.</p>
      <p style="font-size:1.8rem;font-weight:800;letter-spacing:0.1em;"><?= e($booking['start_otp']) ?></p>
    </div>
  <?php endif; ?>

  <?php if ($isCustomer && $booking['status'] === 'in_progress'): ?>
    <div class="card">
      <h3>Job in progress</h3>
      <?php if ($distanceKm !== null): ?>
        <p>Your professional is about <strong><?= round($distanceKm, 1) ?> km</strong> from your address (last updated from their device).</p>
      <?php else: ?>
        <p class="form-hint">Live distance will appear here once the professional's app starts sharing location.</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($isCustomer && in_array($booking['status'], ['pending', 'offered', 'assigned'], true)): ?>
    <form method="post" action="<?= url('/bookings/' . $booking['id'] . '/cancel') ?>" data-confirm="Cancel this booking?">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-danger">Cancel Booking</button>
    </form>
  <?php endif; ?>

  <?php if ($isProvider && $booking['status'] === 'offered'): ?>
    <div class="card">
      <h3>New job offer</h3>
      <p>Review the details above, then accept or decline.</p>
      <div style="display:flex;gap:10px;">
        <form method="post" action="<?= url('/provider/bookings/' . $booking['id'] . '/accept') ?>">
          <?= csrf_field() ?>
          <button type="submit" class="btn">Accept</button>
        </form>
        <form method="post" action="<?= url('/provider/bookings/' . $booking['id'] . '/reject') ?>" data-confirm="Decline this job?">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-outline">Decline</button>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($isProvider && $booking['status'] === 'assigned'): ?>
    <div class="card">
      <h3>Start the job</h3>
      <p>Ask the customer for their start code and enter it below.</p>
      <form method="post" action="<?= url('/provider/bookings/' . $booking['id'] . '/start') ?>" style="display:flex;gap:10px;align-items:flex-end;">
        <?= csrf_field() ?>
        <div class="form-group" style="margin-bottom:0;">
          <label for="start_otp">Start code</label>
          <input type="text" id="start_otp" name="start_otp" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" required style="width:120px;">
        </div>
        <button type="submit" class="btn">Start Job</button>
      </form>
    </div>
  <?php endif; ?>

  <?php if ($isProvider && $booking['status'] === 'in_progress'): ?>
    <form method="post" action="<?= url('/provider/bookings/' . $booking['id'] . '/complete') ?>">
      <?= csrf_field() ?>
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

<?php if ($isProvider && $booking['status'] === 'in_progress'): ?>
<script>
(function () {
  // Pings this provider's location every 45s while this job's page is
  // open, so the customer's page above can show a live distance. The
  // Flutter app does the same thing in the background via geolocator.
  if (!navigator.geolocation) return;
  function ping() {
    navigator.geolocation.getCurrentPosition(function (pos) {
      var body = new URLSearchParams();
      body.set('_csrf', document.querySelector('input[name="_csrf"]').value);
      body.set('lat', pos.coords.latitude);
      body.set('lng', pos.coords.longitude);
      fetch('<?= url('/provider/location-ping') ?>', { method: 'POST', body: body }).catch(function () {});
    });
  }
  ping();
  setInterval(ping, 45000);
})();
</script>
<?php endif; ?>
