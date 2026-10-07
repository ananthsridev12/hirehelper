<?php
/** @var array $categories */
/** @var array $errors */
/** @var array $old */
$pageTitle = 'Sign up';
$role = $old['role'] ?? 'customer';
?>
<div class="container">
  <div class="card auth-card" style="max-width:520px;">
    <h1 class="section-title">Create your account</h1>
    <?php foreach ($errors as $error): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endforeach; ?>
    <form method="post" action="<?= url('/register') ?>">
      <?= csrf_field() ?>

      <div class="form-group">
        <label>I want to</label>
        <div class="form-row">
          <label class="checkbox-row"><input type="radio" name="role" value="customer" <?= $role === 'customer' ? 'checked' : '' ?> onclick="document.getElementById('provider-fields').hidden = true;"> Book services</label>
          <label class="checkbox-row"><input type="radio" name="role" value="provider" <?= $role === 'provider' ? 'checked' : '' ?> onclick="document.getElementById('provider-fields').hidden = false;"> Offer services (become a professional)</label>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="name">Full name</label>
          <input type="text" id="name" name="name" value="<?= e($old['name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label for="phone">Phone</label>
          <input type="tel" id="phone" name="phone" value="<?= e($old['phone'] ?? '') ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" minlength="8" required>
        <div class="form-hint">At least 8 characters.</div>
      </div>

      <div class="form-group">
        <label for="referral_code">Referral code (optional)</label>
        <input type="text" id="referral_code" name="referral_code" value="<?= e($old['referral_code'] ?? '') ?>" style="text-transform:uppercase;">
        <div class="form-hint">Got a code from a friend? You'll both get a wallet bonus.</div>
      </div>

      <div id="provider-fields" <?= $role === 'provider' ? '' : 'hidden' ?>>
        <div class="form-group">
          <label for="city">City you serve</label>
          <input type="text" id="city" name="city" value="<?= e($old['city'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Service categories</label>
          <div class="checkbox-grid">
            <?php foreach ($categories as $category): ?>
              <label><input type="checkbox" name="categories[]" value="<?= (int) $category['id'] ?>"> <?= e($category['name']) ?></label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-block">Create account</button>
    </form>
    <p class="form-hint" style="margin-top:16px;">Already have an account? <a href="<?= url('/login') ?>">Log in</a></p>
  </div>
</div>
