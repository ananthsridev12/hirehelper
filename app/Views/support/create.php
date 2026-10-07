<?php
/** @var array $errors */
$pageTitle = 'Contact Support';
?>
<div class="container" style="max-width:520px;">
  <h1 class="section-title">Contact Support</h1>
  <p class="section-sub">Have a question or ran into a problem? Send us a message.</p>

  <div class="card">
    <?php foreach ($errors as $error): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endforeach; ?>
    <form method="post" action="<?= url('/support') ?>">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="subject">Subject</label>
        <input type="text" id="subject" name="subject" required>
      </div>
      <div class="form-group">
        <label for="message">Message</label>
        <textarea id="message" name="message" required></textarea>
      </div>
      <button type="submit" class="btn btn-block">Send</button>
    </form>
  </div>
</div>
