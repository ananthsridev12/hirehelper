<?php
/** @var array $bookings */
/** @var string|null $status */
/** @var array $eligibleProviders */
$pageTitle = 'Bookings';
$statuses = ['pending', 'offered', 'assigned', 'in_progress', 'completed', 'cancelled'];
?>
<div class="page-header">
  <h1 class="section-title">Bookings</h1>
  <div>
    <a href="<?= url('/admin/bookings') ?>" class="btn btn-sm <?= !$status ? '' : 'btn-outline' ?>">All</a>
    <?php foreach ($statuses as $s): ?>
      <a href="<?= url('/admin/bookings?status=' . $s) ?>" class="btn btn-sm <?= $status === $s ? '' : 'btn-outline' ?>"><?= e(ucwords(str_replace('_', ' ', $s))) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="card table-wrap">
  <table>
    <thead>
      <tr><th>#</th><th>Service</th><th>Customer</th><th>Date</th><th>Status</th><th>Professional</th></tr>
    </thead>
    <tbody>
      <?php foreach ($bookings as $booking): ?>
        <tr>
          <td><a href="<?= url('/bookings/' . $booking['id']) ?>">#<?= (int) $booking['id'] ?></a></td>
          <td><?= e($booking['service_name']) ?></td>
          <td><?= e($booking['customer_name']) ?></td>
          <td><?= e($booking['scheduled_date']) ?><br><span class="form-hint"><?= e($booking['scheduled_time_slot']) ?></span></td>
          <td><span class="badge badge-<?= e($booking['status']) ?>"><?= e(ucwords(str_replace('_', ' ', $booking['status']))) ?></span></td>
          <td>
            <?php if ($booking['status'] === 'pending'): ?>
              <?php $options = $eligibleProviders[$booking['category_id']] ?? []; ?>
              <?php if (empty($options)): ?>
                <span class="form-hint">No eligible providers</span>
              <?php else: ?>
                <form method="post" action="<?= url('/admin/bookings/' . $booking['id'] . '/assign') ?>" style="display:flex;gap:6px;">
                  <?= csrf_field() ?>
                  <select name="provider_id" required>
                    <option value="">Assign to...</option>
                    <?php foreach ($options as $provider): ?>
                      <option value="<?= (int) $provider['id'] ?>">
                        <?= e($provider['name']) ?><?= isset($provider['distance_km']) && $provider['distance_km'] !== null ? ' (' . round($provider['distance_km'], 1) . ' km)' : '' ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn btn-sm">Offer</button>
                </form>
              <?php endif; ?>
            <?php elseif ($booking['status'] === 'offered'): ?>
              <?= e($booking['provider_name']) ?> <span class="form-hint">(awaiting response)</span>
            <?php else: ?>
              <?= e($booking['provider_name'] ?? '-') ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($bookings)): ?>
        <tr><td colspan="6">No bookings found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
