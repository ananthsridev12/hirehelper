<?php
/** @var array $zones */
/** @var array $errors */
$pageTitle = 'Service Areas';
?>
<div class="page-header">
  <h1 class="section-title">Service Areas</h1>
</div>

<?php foreach ($errors as $error): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="card">
  <p class="form-hint" style="margin-top:0;">
    <strong>If this list is empty, every pincode is serviceable</strong> — this is opt-in. Add pincodes here
    only once you want to start restricting bookings to areas you actually cover.
  </p>
  <h3>Add pincode</h3>
  <form method="post" action="<?= url('/admin/zones') ?>" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
    <?= csrf_field() ?>
    <div class="form-group" style="margin-bottom:0;">
      <label for="pincode">Pincode</label>
      <input type="text" id="pincode" name="pincode" required>
    </div>
    <div class="form-group" style="margin-bottom:0;">
      <label for="city">City (optional)</label>
      <input type="text" id="city" name="city">
    </div>
    <button type="submit" class="btn">Add</button>
  </form>
</div>

<div class="card table-wrap">
  <table>
    <thead><tr><th>Pincode</th><th>City</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($zones as $zone): ?>
        <tr>
          <td><?= e($zone['pincode']) ?></td>
          <td><?= e($zone['city']) ?></td>
          <td><span class="badge badge-<?= $zone['is_active'] ? 'paid' : 'unpaid' ?>"><?= $zone['is_active'] ? 'Active' : 'Inactive' ?></span></td>
          <td style="white-space:nowrap;">
            <form method="post" action="<?= url('/admin/zones/' . $zone['id'] . '/toggle') ?>" style="display:inline;">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-sm btn-outline"><?= $zone['is_active'] ? 'Deactivate' : 'Activate' ?></button>
            </form>
            <form method="post" action="<?= url('/admin/zones/' . $zone['id'] . '/delete') ?>" style="display:inline;" data-confirm="Remove this pincode?">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-sm btn-danger">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($zones)): ?>
        <tr><td colspan="4">No pincodes added yet — all areas are serviceable.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
