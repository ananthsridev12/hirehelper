<?php
/** @var array $booking */
$net = (float) $booking['price'] - (float) $booking['discount_amount'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Invoice #<?= (int) $booking['id'] ?> - HireHelper</title>
  <style>
    body { font-family: -apple-system, Arial, sans-serif; color: #1e2333; max-width: 640px; margin: 40px auto; padding: 0 20px; }
    .brand { font-size: 1.4rem; font-weight: 800; margin-bottom: 4px; }
    .brand span { color: #4f46e5; }
    table { width: 100%; border-collapse: collapse; margin-top: 24px; }
    th, td { text-align: left; padding: 10px 0; border-bottom: 1px solid #e4e7ec; }
    th { color: #667085; font-size: 0.8rem; text-transform: uppercase; }
    .totals td { border-bottom: none; }
    .totals .label { color: #667085; }
    .grand { font-weight: 800; font-size: 1.2rem; border-top: 2px solid #1e2333; }
    .meta { color: #667085; font-size: 0.9rem; margin: 16px 0; }
    .print-btn { margin: 20px 0; padding: 10px 18px; border-radius: 8px; border: none; background: #4f46e5; color: #fff; font-weight: 600; cursor: pointer; }
    @media print { .print-btn { display: none; } }
  </style>
</head>
<body>
  <div class="brand">Hire<span>Helper</span></div>
  <p class="meta">Invoice for Booking #<?= (int) $booking['id'] ?> &middot; <?= e(date('d M Y', strtotime($booking['created_at']))) ?></p>

  <button class="print-btn" onclick="window.print()">Print / Save as PDF</button>

  <p><strong>Billed to:</strong> <?= e($booking['customer_name']) ?> (<?= e($booking['customer_phone']) ?>)</p>
  <p><strong>Service address:</strong> <?= e($booking['line1']) ?>, <?= e($booking['city']) ?> - <?= e($booking['pincode']) ?></p>
  <p><strong>Professional:</strong> <?= $booking['provider_name'] ? e($booking['provider_name']) : 'Not assigned' ?></p>

  <table>
    <thead><tr><th>Description</th><th>Scheduled</th><th style="text-align:right;">Amount</th></tr></thead>
    <tbody>
      <tr>
        <td><?= e($booking['service_name']) ?><br><span class="meta" style="margin:0;"><?= e($booking['category_name']) ?></span></td>
        <td><?= e($booking['scheduled_date']) ?><br><?= e($booking['scheduled_time_slot']) ?></td>
        <td style="text-align:right;"><?= money((float) $booking['price']) ?></td>
      </tr>
    </tbody>
    <tfoot class="totals">
      <?php if ((float) $booking['discount_amount'] > 0): ?>
        <tr><td colspan="2" class="label">Discount</td><td style="text-align:right;">-<?= money((float) $booking['discount_amount']) ?></td></tr>
      <?php endif; ?>
      <tr class="grand"><td colspan="2">Total</td><td style="text-align:right;"><?= money($net) ?></td></tr>
      <tr><td colspan="2" class="label">Payment status</td><td style="text-align:right;"><?= e(ucfirst($booking['payment_status'])) ?></td></tr>
    </tfoot>
  </table>

  <p class="meta">This is a system-generated invoice and does not require a signature. Questions? Contact support from your HireHelper account.</p>
</body>
</html>
