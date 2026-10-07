<?php
/** @var array|null $profile */
/** @var array $errors */
$pageTitle = 'My Profile';
?>
<div class="container" style="max-width:520px;">
  <h1 class="section-title">My Profile</h1>

  <div class="card">
    <?php foreach ($errors as $error): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <div style="display:flex;align-items:center;gap:16px;margin-bottom:16px;">
      <?php if ($profile && !empty($profile['photo_path'])): ?>
        <img class="avatar avatar-lg" src="<?= asset($profile['photo_path']) ?>" alt="Profile photo">
      <?php else: ?>
        <div class="avatar-fallback" style="width:96px;height:96px;"><?= icon('user') ?></div>
      <?php endif; ?>
      <div>
        <span class="badge badge-<?= $profile && $profile['is_verified'] ? 'paid' : 'pending' ?>"><?= $profile && $profile['is_verified'] ? 'Verified' : 'Pending verification' ?></span>
      </div>
    </div>

    <form method="post" action="<?= url('/provider/profile') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="photo">Profile photo</label>
        <input type="file" id="photo" name="photo" accept="image/*">
      </div>
      <div class="form-group">
        <label for="experience_years">Years of experience</label>
        <input type="number" id="experience_years" name="experience_years" min="0" max="60" value="<?= e((string) ($profile['experience_years'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label for="bio">About you</label>
        <textarea id="bio" name="bio" placeholder="Tell customers a bit about your experience..."><?= e($profile['bio'] ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn btn-block">Save Profile</button>
    </form>
  </div>
</div>
