<?php
/** @var array $stats */
$pageTitle = 'Dashboard';
?>
<h1 class="section-title">Dashboard</h1>
<div class="stat-grid">
  <div class="stat-card">
    <div class="value"><?= (int) $stats['customers'] ?></div>
    <div class="label">Customers</div>
  </div>
  <div class="stat-card">
    <div class="value"><?= (int) $stats['providers'] ?></div>
    <div class="label">Providers</div>
  </div>
  <div class="stat-card">
    <div class="value"><?= (int) $stats['bookings_total'] ?></div>
    <div class="label">Total bookings</div>
  </div>
  <div class="stat-card">
    <div class="value"><?= (int) $stats['bookings_pending'] ?></div>
    <div class="label">Pending assignment</div>
  </div>
  <div class="stat-card">
    <div class="value"><?= money((float) $stats['revenue_completed']) ?></div>
    <div class="label">Revenue (completed)</div>
  </div>
</div>

<div class="card">
  <p>Manage <a href="<?= url('/admin/bookings?status=pending') ?>">pending bookings</a>, review new <a href="<?= url('/admin/providers') ?>">provider signups</a>, or update <a href="<?= url('/admin/categories') ?>">categories</a> and <a href="<?= url('/admin/services') ?>">services</a>.</p>
</div>
