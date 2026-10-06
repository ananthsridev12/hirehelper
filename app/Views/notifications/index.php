<?php
/** @var array $notifications */
$pageTitle = 'Notifications';
?>
<div class="container">
  <div class="page-header">
    <h1 class="section-title">Notifications</h1>
    <?php if (!empty($notifications)): ?>
      <form method="post" action="<?= url('/notifications/mark-read') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-sm btn-outline">Mark all read</button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (empty($notifications)): ?>
    <div class="empty-state">No notifications yet.</div>
  <?php else: ?>
    <div class="card">
      <?php foreach ($notifications as $n): ?>
        <div class="booking-row">
          <div>
            <h4><?= e($n['title']) ?><?= $n['is_read'] ? '' : ' <span class="badge badge-assigned">New</span>' ?></h4>
            <div class="meta"><?= e($n['body']) ?></div>
          </div>
          <div style="display:flex;align-items:center;gap:12px;">
            <?php if ($n['booking_id']): ?>
              <a class="btn btn-sm btn-outline" href="<?= url('/bookings/' . $n['booking_id']) ?>">View booking</a>
            <?php endif; ?>
            <span class="meta"><?= e(date('d M, g:i a', strtotime($n['created_at']))) ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
