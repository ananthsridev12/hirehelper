<?php
/** @var string $content */
$user = current_user();
$path = \App\Core\Url::current();
$navItems = [
    '/admin' => 'Dashboard',
    '/admin/bookings' => 'Bookings',
    '/admin/categories' => 'Categories',
    '/admin/services' => 'Services',
    '/admin/providers' => 'Providers',
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= isset($pageTitle) ? e($pageTitle) . ' - Admin - HireHelper' : 'Admin - HireHelper' ?></title>
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>
  <header class="site-header">
    <div class="container">
      <a class="brand" href="<?= url('/admin') ?>">Hire<span>Helper</span> Admin</a>
      <div class="nav-user">
        <span><?= e($user['name']) ?></span>
        <a href="<?= url('/') ?>" class="btn btn-outline btn-sm">View site</a>
        <form method="post" action="<?= url('/logout') ?>" style="margin:0;">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-outline btn-sm"><?= icon('logout') ?></button>
        </form>
      </div>
    </div>
  </header>

  <div class="admin-shell">
    <aside class="admin-sidebar">
      <?php foreach ($navItems as $href => $label): ?>
        <a href="<?= url($href) ?>" class="<?= $path === $href ? 'active' : '' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </aside>
    <div class="admin-content">
      <?php foreach (\App\Core\Flash::all() as $type => $messages): ?>
        <?php foreach ($messages as $message): ?>
          <div class="alert alert-<?= e($type) ?>"><?= e($message) ?></div>
        <?php endforeach; ?>
      <?php endforeach; ?>
      <?= $content ?>
    </div>
  </div>

  <script src="<?= asset('js/main.js') ?>"></script>
</body>
</html>
