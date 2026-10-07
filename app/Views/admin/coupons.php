<?php
/** @var array $coupons */
/** @var array $errors */
$pageTitle = 'Coupons';
?>
<div class="page-header">
  <h1 class="section-title">Coupons</h1>
</div>

<?php foreach ($errors as $error): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="card">
  <h3>Create coupon</h3>
  <form method="post" action="<?= url('/admin/coupons') ?>">
    <?= csrf_field() ?>
    <div class="form-row">
      <div class="form-group">
        <label for="code">Code</label>
        <input type="text" id="code" name="code" placeholder="SAVE20" required style="text-transform:uppercase;">
      </div>
      <div class="form-group">
        <label for="discount_type">Type</label>
        <select id="discount_type" name="discount_type">
          <option value="flat">Flat amount (₹)</option>
          <option value="percent">Percent (%)</option>
        </select>
      </div>
      <div class="form-group">
        <label for="discount_value">Value</label>
        <input type="number" step="0.01" id="discount_value" name="discount_value" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="max_discount">Max discount (₹, for percent coupons)</label>
        <input type="number" step="0.01" id="max_discount" name="max_discount">
      </div>
      <div class="form-group">
        <label for="min_booking_amount">Minimum booking amount (₹)</label>
        <input type="number" step="0.01" id="min_booking_amount" name="min_booking_amount" value="0">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="usage_limit">Usage limit (leave blank = unlimited)</label>
        <input type="number" id="usage_limit" name="usage_limit">
      </div>
      <div class="form-group">
        <label for="expires_at">Expires on (leave blank = never)</label>
        <input type="date" id="expires_at" name="expires_at">
      </div>
    </div>
    <button type="submit" class="btn">Create Coupon</button>
  </form>
</div>

<div class="card table-wrap">
  <table>
    <thead><tr><th>Code</th><th>Discount</th><th>Min. order</th><th>Used</th><th>Expires</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($coupons as $coupon): ?>
        <tr>
          <td><strong><?= e($coupon['code']) ?></strong></td>
          <td><?= $coupon['discount_type'] === 'percent' ? (int) $coupon['discount_value'] . '%' : money((float) $coupon['discount_value']) ?></td>
          <td><?= money((float) $coupon['min_booking_amount']) ?></td>
          <td><?= (int) $coupon['used_count'] ?><?= $coupon['usage_limit'] ? ' / ' . (int) $coupon['usage_limit'] : '' ?></td>
          <td><?= $coupon['expires_at'] ? e($coupon['expires_at']) : 'Never' ?></td>
          <td><span class="badge badge-<?= $coupon['is_active'] ? 'paid' : 'unpaid' ?>"><?= $coupon['is_active'] ? 'Active' : 'Inactive' ?></span></td>
          <td>
            <form method="post" action="<?= url('/admin/coupons/' . $coupon['id'] . '/toggle') ?>">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-sm btn-outline"><?= $coupon['is_active'] ? 'Deactivate' : 'Activate' ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($coupons)): ?>
        <tr><td colspan="7">No coupons yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
