<?php
/** @var array $stats */
/** @var array $trend */
$pageTitle = 'Dashboard';
$maxCount = max(1, max(array_column($trend, 'count')));
$chartWidth = 560;
$chartHeight = 140;
$barGap = 14;
$barWidth = ($chartWidth - $barGap * (count($trend) - 1)) / count($trend);
?>
<h1 class="section-title">Dashboard</h1>
<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-icon"><?= icon('user') ?></div>
    <div><div class="value"><?= (int) $stats['customers'] ?></div><div class="label">Customers</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><?= icon('shield') ?></div>
    <div><div class="value"><?= (int) $stats['providers'] ?></div><div class="label">Providers</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><?= icon('calendar') ?></div>
    <div><div class="value"><?= (int) $stats['bookings_total'] ?></div><div class="label">Total bookings</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><?= icon('clock') ?></div>
    <div><div class="value"><?= (int) $stats['bookings_pending'] ?></div><div class="label">Pending assignment</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><?= icon('tag') ?></div>
    <div><div class="value"><?= money((float) $stats['revenue_completed']) ?></div><div class="label">Revenue (completed)</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><?= icon('message') ?></div>
    <div><div class="value"><?= (int) $stats['open_tickets'] ?></div><div class="label">Open support tickets</div></div>
  </div>
</div>

<div class="card">
  <h3>Bookings, last 7 days</h3>
  <svg class="trend-chart" viewBox="0 0 <?= $chartWidth ?> <?= $chartHeight + 24 ?>" preserveAspectRatio="xMinYMin meet">
    <?php foreach ($trend as $i => $day): ?>
      <?php
        $barHeight = $day['count'] > 0 ? max(4, ($day['count'] / $maxCount) * $chartHeight) : 2;
        $x = $i * ($barWidth + $barGap);
        $y = $chartHeight - $barHeight;
      ?>
      <rect class="bar" x="<?= round($x, 1) ?>" y="<?= round($y, 1) ?>" width="<?= round($barWidth, 1) ?>" height="<?= round($barHeight, 1) ?>" rx="4"></rect>
      <text class="axis-label" x="<?= round($x + $barWidth / 2, 1) ?>" y="<?= $chartHeight + 16 ?>" text-anchor="middle"><?= e($day['label']) ?></text>
    <?php endforeach; ?>
  </svg>
</div>

<div class="card">
  <p>Manage <a href="<?= url('/admin/bookings?status=pending') ?>">pending bookings</a> (<a href="<?= url('/admin/bookings/export') ?>">export CSV</a>), review new <a href="<?= url('/admin/providers') ?>">provider signups</a>, update <a href="<?= url('/admin/categories') ?>">categories</a> and <a href="<?= url('/admin/services') ?>">services</a>, manage <a href="<?= url('/admin/coupons') ?>">coupons</a> and <a href="<?= url('/admin/zones') ?>">service areas</a>, or check the <a href="<?= url('/admin/support') ?>">support inbox</a>.</p>
</div>
