<?php
/** @var array $providers */
$pageTitle = 'Providers';
?>
<h1 class="section-title">Providers</h1>

<div class="card table-wrap">
  <table>
    <thead>
      <tr><th>Name</th><th>Contact</th><th>City</th><th>Verified</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($providers as $provider): ?>
        <tr>
          <td><?= e($provider['name']) ?></td>
          <td><?= e($provider['email']) ?><br><?= e($provider['phone']) ?></td>
          <td><?= e($provider['city']) ?></td>
          <td><span class="badge badge-<?= $provider['is_verified'] ? 'paid' : 'pending' ?>"><?= $provider['is_verified'] ? 'Verified' : 'Pending' ?></span></td>
          <td><span class="badge badge-<?= $provider['status'] === 'active' ? 'paid' : 'unpaid' ?>"><?= e(ucfirst($provider['status'])) ?></span></td>
          <td style="white-space:nowrap;">
            <form method="post" action="<?= url('/admin/providers/' . $provider['id'] . '/verify') ?>" style="display:inline;">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-sm btn-outline"><?= $provider['is_verified'] ? 'Unverify' : 'Verify' ?></button>
            </form>
            <form method="post" action="<?= url('/admin/providers/' . $provider['id'] . '/suspend') ?>" style="display:inline;">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-sm btn-danger"><?= $provider['status'] === 'active' ? 'Suspend' : 'Reactivate' ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($providers)): ?>
        <tr><td colspan="6">No providers have registered yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
