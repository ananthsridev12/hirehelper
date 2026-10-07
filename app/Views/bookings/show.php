<?php
/** @var array $booking */
/** @var array|null $review */
/** @var float|null $distanceKm */
/** @var array $messages */
/** @var array $slots */
$pageTitle = 'Booking #' . $booking['id'];
$user = current_user();
$isCustomer = $user && (int) $user['id'] === (int) $booking['customer_id'];
$isProvider = $user && (int) $user['id'] === (int) $booking['provider_id'];
$isAdmin = $user && $user['role'] === 'admin';
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
    <p>
      <strong>Price:</strong> <?= money((float) $booking['price'] - (float) $booking['discount_amount']) ?>
      <?php if ((float) $booking['discount_amount'] > 0): ?>
        <span class="form-hint"><del><?= money((float) $booking['price']) ?></del> (<?= money((float) $booking['discount_amount']) ?> off applied)</span>
      <?php endif; ?>
      <span class="badge badge-<?= e($booking['payment_status']) ?>"><?= e(ucfirst($booking['payment_status'])) ?></span>
    </p>
    <?php if (!empty($booking['notes'])): ?>
      <p><strong>Notes:</strong> <?= nl2br(e($booking['notes'])) ?></p>
    <?php endif; ?>
    <?php if ($isCustomer || $isAdmin): ?>
      <a href="<?= url('/bookings/' . $booking['id'] . '/invoice') ?>" target="_blank" class="btn btn-sm btn-outline"><?= icon('calendar') ?> View / print invoice</a>
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

  <?php if (!empty($booking['before_photo_path']) || !empty($booking['after_photo_path'])): ?>
    <div class="card">
      <h3>Job photos</h3>
      <div class="job-photos">
        <?php if (!empty($booking['before_photo_path'])): ?>
          <figure><img src="<?= asset($booking['before_photo_path']) ?>" alt="Before"><figcaption>Before</figcaption></figure>
        <?php endif; ?>
        <?php if (!empty($booking['after_photo_path'])): ?>
          <figure><img src="<?= asset($booking['after_photo_path']) ?>" alt="After"><figcaption>After</figcaption></figure>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

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
    <div class="card">
      <h3>Need to change something?</h3>
      <form method="post" action="<?= url('/bookings/' . $booking['id'] . '/reschedule') ?>" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:12px;">
        <?= csrf_field() ?>
        <div class="form-group" style="margin-bottom:0;">
          <label for="scheduled_date">New date</label>
          <input type="date" id="scheduled_date" name="scheduled_date" min="<?= date('Y-m-d') ?>" value="<?= e($booking['scheduled_date']) ?>" required>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label for="scheduled_time_slot">New slot</label>
          <select id="scheduled_time_slot" name="scheduled_time_slot" required>
            <?php foreach ($slots as $slot): ?>
              <option value="<?= e($slot) ?>" <?= $slot === $booking['scheduled_time_slot'] ? 'selected' : '' ?>><?= e($slot) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-outline">Reschedule</button>
      </form>
      <form method="post" action="<?= url('/bookings/' . $booking['id'] . '/cancel') ?>" data-confirm="Cancel this booking?" style="display:inline;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger">Cancel Booking</button>
      </form>
    </div>
  <?php endif; ?>

  <?php if ($isProvider && $booking['status'] === 'offered'): ?>
    <div class="card">
      <h3>New job offer</h3>
      <p>Review the details above, then accept or decline. This was matched to you automatically &mdash; no fee either way.</p>
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
      <p>Ask the customer for their start code, optionally snap a "before" photo, then start.</p>
      <form method="post" action="<?= url('/provider/bookings/' . $booking['id'] . '/start') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-row">
          <div class="form-group">
            <label for="start_otp">Start code</label>
            <input type="text" id="start_otp" name="start_otp" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" required>
          </div>
          <div class="form-group">
            <label for="before_photo">Before photo (optional)</label>
            <input type="file" id="before_photo" name="before_photo" accept="image/*">
          </div>
        </div>
        <button type="submit" class="btn">Start Job</button>
      </form>
    </div>
    <form method="post" action="<?= url('/provider/bookings/' . $booking['id'] . '/notify-delay') ?>" style="margin-bottom:16px;">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-outline btn-sm">Running late? Notify customer</button>
    </form>
  <?php endif; ?>

  <?php if ($isProvider && $booking['status'] === 'in_progress'): ?>
    <div class="card">
      <h3>Finish the job</h3>
      <form method="post" action="<?= url('/provider/bookings/' . $booking['id'] . '/complete') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group">
          <label for="after_photo">After photo (optional)</label>
          <input type="file" id="after_photo" name="after_photo" accept="image/*">
        </div>
        <button type="submit" class="btn">Mark Completed</button>
      </form>
    </div>
  <?php endif; ?>

  <?php if ($isCustomer && $booking['status'] === 'completed'): ?>
    <div class="card">
      <?php if ($review): ?>
        <h3>Your review</h3>
        <p class="rating"><?= str_repeat('★', (int) $review['rating']) . str_repeat('☆', 5 - (int) $review['rating']) ?></p>
        <p><?= nl2br(e($review['comment'])) ?></p>
        <?php if (!empty($review['photo_path'])): ?>
          <img class="review-photo" src="<?= asset($review['photo_path']) ?>" alt="Review photo">
        <?php endif; ?>
      <?php else: ?>
        <h3>Rate this service</h3>
        <form method="post" action="<?= url('/bookings/' . $booking['id'] . '/review') ?>" enctype="multipart/form-data">
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
          <div class="form-group">
            <label for="photo">Add a photo (optional)</label>
            <input type="file" id="photo" name="photo" accept="image/*">
          </div>
          <button type="submit" class="btn">Submit Review</button>
        </form>
      <?php endif; ?>
    </div>
    <form method="post" action="<?= url('/bookings/' . $booking['id'] . '/rebook') ?>" style="margin-bottom:16px;">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-outline"><?= icon('calendar') ?> Book this again</button>
    </form>
  <?php endif; ?>

  <?php if (in_array($booking['status'], ['assigned', 'in_progress', 'completed'], true) && ($isCustomer || $isProvider)): ?>
    <div class="card">
      <h3><?= icon('message', 'icon') ?> Messages</h3>
      <div class="chat-thread">
        <?php if (empty($messages)): ?>
          <p class="form-hint">No messages yet. Say hello!</p>
        <?php endif; ?>
        <?php foreach ($messages as $m): ?>
          <?php $mine = (int) $m['sender_id'] === (int) $user['id']; ?>
          <div class="chat-bubble <?= $mine ? 'mine' : 'theirs' ?>">
            <?= e($m['message']) ?>
            <span class="meta"><?= $mine ? 'You' : e($m['sender_name']) ?> &middot; <?= e(date('d M, g:i a', strtotime($m['created_at']))) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <form method="post" action="<?= url('/bookings/' . $booking['id'] . '/messages') ?>" class="chat-form">
        <?= csrf_field() ?>
        <input type="text" name="message" placeholder="Type a message..." maxlength="1000" required>
        <button type="submit" class="btn btn-sm">Send</button>
      </form>
    </div>
  <?php endif; ?>

  <?php if ($isCustomer): ?>
    <details class="card">
      <summary style="cursor:pointer;font-weight:600;">Having a problem with this booking?</summary>
      <form method="post" action="<?= url('/bookings/' . $booking['id'] . '/report-issue') ?>" style="margin-top:12px;">
        <?= csrf_field() ?>
        <div class="form-group">
          <textarea name="message" placeholder="Describe the issue and we'll look into it." required></textarea>
        </div>
        <button type="submit" class="btn btn-outline">Report an issue</button>
      </form>
    </details>
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
