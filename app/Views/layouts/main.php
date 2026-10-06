<?php
/** @var string $content */
$user = current_user();
$unreadCount = $user ? (new \App\Models\Notification())->unreadCount((int) $user['id']) : 0;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= isset($pageTitle) ? e($pageTitle) . ' - HireHelper' : 'HireHelper - Home services at your doorstep' ?></title>
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>
  <header class="site-header">
    <div class="container">
      <a class="brand" href="<?= url('/') ?>">Hire<span>Helper</span></a>
      <nav class="main-nav">
        <?php if ($user): ?>
          <?php if ($user['role'] === 'customer'): ?>
            <a href="<?= url('/bookings') ?>">My Bookings</a>
            <a href="<?= url('/account/addresses') ?>">Addresses</a>
          <?php elseif ($user['role'] === 'provider'): ?>
            <a href="<?= url('/provider/dashboard') ?>">Dashboard</a>
          <?php elseif ($user['role'] === 'admin'): ?>
            <a href="<?= url('/admin') ?>">Admin</a>
          <?php endif; ?>
          <a href="<?= url('/notifications') ?>">Notifications<?= $unreadCount > 0 ? ' <span class="badge-role">' . $unreadCount . '</span>' : '' ?></a>
          <div class="nav-user">
            <span><?= e($user['name']) ?></span>
            <span class="badge-role"><?= e($user['role']) ?></span>
            <form method="post" action="<?= url('/logout') ?>" style="margin:0;">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-outline btn-sm"><?= icon('logout', 'icon') ?></button>
            </form>
          </div>
        <?php else: ?>
          <a href="<?= url('/login') ?>">Log in</a>
          <a class="btn btn-sm" href="<?= url('/register') ?>">Sign up</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <main>
    <div class="container" style="padding-top:20px;">
      <?php foreach (\App\Core\Flash::all() as $type => $messages): ?>
        <?php foreach ($messages as $message): ?>
          <div class="alert alert-<?= e($type) ?>"><?= e($message) ?></div>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </div>
    <?= $content ?>
  </main>

  <footer class="site-footer">
    <div class="container">
      &copy; <?= date('Y') ?> HireHelper. Home services, on demand. &middot;
      <a href="<?= url('/privacy') ?>">Privacy</a> &middot;
      <a href="<?= url('/terms') ?>">Terms</a>
    </div>
  </footer>

  <script src="<?= asset('js/main.js') ?>"></script>
</body>
</html>
