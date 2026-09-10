<?php
/** @var array $errors */
/** @var array $old */
$pageTitle = 'Log in';
?>
<div class="container">
  <div class="card auth-card">
    <h1 class="section-title">Log in</h1>
    <?php foreach ($errors as $error): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endforeach; ?>
    <form method="post" action="<?= url('/login') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" required autofocus>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-block">Log in</button>
    </form>
    <p class="form-hint" style="margin-top:16px;">Don't have an account? <a href="<?= url('/register') ?>">Sign up</a></p>
  </div>
</div>
