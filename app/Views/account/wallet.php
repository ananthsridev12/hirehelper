<?php
/** @var array $user */
/** @var array $history */
$pageTitle = 'My Wallet';
?>
<div class="container" style="max-width:640px;">
  <h1 class="section-title">My Wallet</h1>

  <div class="wallet-balance-card">
    <div class="label">Available balance</div>
    <div class="amount"><?= money((float) $user['wallet_balance']) ?></div>
    <div class="referral-box">
      <div>
        <div style="font-size:0.85rem;opacity:0.9;">Share your referral code</div>
        <code><?= e($user['referral_code']) ?></code>
      </div>
      <div style="font-size:0.85rem;text-align:right;">Both of you get <?= money(\App\Models\User::REFERRAL_BONUS) ?><br>when they sign up</div>
    </div>
  </div>

  <div class="card">
    <h3>History</h3>
    <?php if (empty($history)): ?>
      <p class="form-hint">No wallet activity yet.</p>
    <?php else: ?>
      <?php foreach ($history as $tx): ?>
        <div class="wallet-tx-row">
          <div>
            <?= e($tx['reason']) ?>
            <div class="form-hint" style="margin:0;"><?= e(date('d M Y, g:i a', strtotime($tx['created_at']))) ?></div>
          </div>
          <div class="<?= $tx['amount'] >= 0 ? 'amount-credit' : 'amount-debit' ?>">
            <?= $tx['amount'] >= 0 ? '+' : '' ?><?= money((float) $tx['amount']) ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
