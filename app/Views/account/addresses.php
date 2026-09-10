<?php
/** @var array $addresses */
/** @var array $errors */
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
      <form method="post" action="<?= url('/account/addresses') ?>">
        <?= csrf_field() ?>
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
