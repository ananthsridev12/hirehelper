<?php
/** @var array $tickets */
$pageTitle = 'Support';
?>
<h1 class="section-title">Support Tickets</h1>

<div class="card table-wrap">
  <table>
    <thead><tr><th>From</th><th>Subject</th><th>Message</th><th>Booking</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($tickets as $t): ?>
        <tr>
          <td><?= e($t['user_name']) ?><br><span class="form-hint"><?= e($t['user_email']) ?></span></td>
          <td><?= e($t['subject']) ?></td>
          <td style="max-width:320px;"><?= nl2br(e($t['message'])) ?></td>
          <td><?= $t['booking_id'] ? '<a href="' . url('/bookings/' . $t['booking_id']) . '">#' . (int) $t['booking_id'] . '</a>' : '-' ?></td>
          <td><span class="badge badge-<?= e($t['status']) ?>"><?= e(ucfirst($t['status'])) ?></span></td>
          <td>
            <?php if ($t['status'] === 'open'): ?>
              <form method="post" action="<?= url('/admin/support/' . $t['id'] . '/resolve') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline">Mark Resolved</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($tickets)): ?>
        <tr><td colspan="6">No support tickets yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
