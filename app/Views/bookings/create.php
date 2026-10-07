<?php
/** @var array $service */
/** @var array $addresses */
/** @var array $errors */
$pageTitle = 'Book ' . $service['name'];
$slots = ['09:00 AM - 11:00 AM', '11:00 AM - 01:00 PM', '02:00 PM - 04:00 PM', '04:00 PM - 06:00 PM', '06:00 PM - 08:00 PM'];
?>
<div class="container">
  <p><a href="<?= url('/service/' . $service['slug']) ?>">&larr; Back to service</a></p>
  <div class="service-detail">
    <div>
      <h1 class="section-title">Book: <?= e($service['name']) ?></h1>
      <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
      <?php endforeach; ?>

      <?php if (empty($addresses)): ?>
        <div class="card">
          <p>You don't have a saved address yet. Please add one before booking.</p>
          <a class="btn" href="<?= url('/account/addresses') ?>">Add an address</a>
        </div>
      <?php else: ?>
        <form method="post" action="<?= url('/book/' . $service['slug']) ?>" class="card">
          <?= csrf_field() ?>

          <div class="form-group">
            <label for="address_id">Deliver service at</label>
            <select id="address_id" name="address_id" required>
              <?php foreach ($addresses as $address): ?>
                <option value="<?= (int) $address['id'] ?>">
                  <?= e($address['label']) ?> - <?= e($address['line1']) ?>, <?= e($address['city']) ?> <?= e($address['pincode']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-hint"><a href="<?= url('/account/addresses') ?>">Manage addresses</a></div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="scheduled_date">Preferred date</label>
              <input type="date" id="scheduled_date" name="scheduled_date" min="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
              <label for="scheduled_time_slot">Preferred time slot</label>
              <select id="scheduled_time_slot" name="scheduled_time_slot" required>
                <?php foreach ($slots as $slot): ?>
                  <option value="<?= e($slot) ?>"><?= e($slot) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="notes">Notes for the professional (optional)</label>
            <textarea id="notes" name="notes" placeholder="e.g. gate code, specific issue details"></textarea>
          </div>

          <div class="form-group">
            <label for="coupon_code">Coupon code (optional)</label>
            <div class="coupon-row">
              <div class="form-group">
                <input type="text" id="coupon_code" name="coupon_code" placeholder="e.g. FIRST100" style="text-transform:uppercase;">
              </div>
            </div>
            <div class="form-hint">Try <strong>FIRST100</strong> or <strong>SAVE20</strong>.</div>
          </div>

          <button type="submit" class="btn btn-block">Confirm Booking</button>
        </form>
      <?php endif; ?>
    </div>

    <div class="card booking-box">
      <h3><?= e($service['name']) ?></h3>
      <div class="price"><?= money((float) $service['price']) ?></div>
      <p class="form-hint">Duration: ~<?= (int) $service['duration_minutes'] ?> mins</p>
      <p class="form-hint">Payment: pay the professional after the service is completed.</p>
    </div>
  </div>
</div>
