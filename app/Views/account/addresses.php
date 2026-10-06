<?php
/** @var array $addresses */
/** @var array $errors */
/** @var string $googleMapsKey */
$pageTitle = 'My Addresses';
?>
<div class="container">
  <h1 class="section-title">My Addresses</h1>

  <?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endforeach; ?>

  <div class="service-detail">
    <div>
      <?php if (empty($addresses)): ?>
        <div class="empty-state">No saved addresses yet.</div>
      <?php else: ?>
        <?php foreach ($addresses as $address): ?>
          <div class="card">
            <div class="page-header">
              <h4 style="margin:0;"><?= e($address['label']) ?> <?= $address['is_default'] ? '<span class="badge badge-assigned">Default</span>' : '' ?></h4>
              <form method="post" action="<?= url('/account/addresses/' . $address['id'] . '/delete') ?>" data-confirm="Remove this address?">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline">Remove</button>
              </form>
            </div>
            <p>
              <?= e($address['line1']) ?><?= $address['line2'] ? ', ' . e($address['line2']) : '' ?><br>
              <?= e($address['city']) ?>, <?= e($address['state']) ?> - <?= e($address['pincode']) ?><br>
              Phone: <?= e($address['phone']) ?>
            </p>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="card">
      <h3>Add new address</h3>

      <button type="button" id="use-location-btn" class="btn btn-outline btn-block" style="margin-bottom:12px;">
        <?= icon('map-pin') ?> Use my current GPS location
      </button>
      <p id="location-status" class="form-hint" style="margin-top:-6px;margin-bottom:12px;"></p>
      <?php if ($googleMapsKey): ?>
        <div id="address-map" style="height:200px;border-radius:8px;margin-bottom:12px;background:var(--color-primary-light);"></div>
      <?php endif; ?>

      <form method="post" action="<?= url('/account/addresses') ?>" id="address-form">
        <?= csrf_field() ?>
        <input type="hidden" name="lat" id="lat">
        <input type="hidden" name="lng" id="lng">
        <div class="form-group">
          <label for="label">Label</label>
          <input type="text" id="label" name="label" placeholder="Home, Office..." value="Home">
        </div>
        <div class="form-group">
          <label for="line1">Address line 1</label>
          <input type="text" id="line1" name="line1" required>
        </div>
        <div class="form-group">
          <label for="line2">Address line 2 (optional)</label>
          <input type="text" id="line2" name="line2">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="city">City</label>
            <input type="text" id="city" name="city" required>
          </div>
          <div class="form-group">
            <label for="state">State</label>
            <input type="text" id="state" name="state">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="pincode">Pincode</label>
            <input type="text" id="pincode" name="pincode" required>
          </div>
          <div class="form-group">
            <label for="phone">Contact phone</label>
            <input type="tel" id="phone" name="phone" required>
          </div>
        </div>
        <div class="form-group checkbox-row">
          <input type="checkbox" id="is_default" name="is_default" value="1">
          <label for="is_default" style="margin:0;font-weight:400;">Set as default</label>
        </div>
        <button type="submit" class="btn btn-block">Save Address</button>
      </form>
    </div>
  </div>
</div>

<?php if ($googleMapsKey): ?>
  <script>window.HIREHELPER_MAPS_KEY = <?= json_encode($googleMapsKey) ?>;</script>
  <script src="https://maps.googleapis.com/maps/api/js?key=<?= urlencode($googleMapsKey) ?>&libraries=places&callback=initAddressMap" async defer></script>
<?php endif; ?>
<script src="<?= asset('js/address-map.js') ?>"></script>
