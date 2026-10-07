<?php
/** @var array $services */
/** @var array $categories */
/** @var array $errors */
$pageTitle = 'Services';
?>
<div class="page-header">
  <h1 class="section-title">Services</h1>
</div>

<?php foreach ($errors as $error): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="card">
  <h3>Add service</h3>
  <?php if (empty($categories)): ?>
    <p class="form-hint">Create a category first before adding services.</p>
  <?php else: ?>
    <form method="post" action="<?= url('/admin/services') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="form-row">
        <div class="form-group">
          <label for="name">Name</label>
          <input type="text" id="name" name="name" required>
        </div>
        <div class="form-group">
          <label for="category_id">Category</label>
          <select id="category_id" name="category_id" required>
            <?php foreach ($categories as $category): ?>
              <option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="price">Price (₹)</label>
          <input type="number" step="0.01" id="price" name="price" required>
        </div>
        <div class="form-group">
          <label for="duration_minutes">Duration (minutes)</label>
          <input type="number" id="duration_minutes" name="duration_minutes" value="60">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="description">Description</label>
          <textarea id="description" name="description"></textarea>
        </div>
        <div class="form-group">
          <label for="image">Image (optional)</label>
          <input type="file" id="image" name="image" accept="image/*">
        </div>
      </div>
      <button type="submit" class="btn">Add Service</button>
    </form>
  <?php endif; ?>
</div>

<div class="card table-wrap">
  <table>
    <thead>
      <tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($services as $service): ?>
        <tr>
          <td>
            <?php if (!empty($service['image_path'])): ?>
              <img class="table-thumb" src="<?= asset($service['image_path']) ?>" alt="">
            <?php else: ?>
              <span class="form-hint">&mdash;</span>
            <?php endif; ?>
          </td>
          <td><?= e($service['name']) ?></td>
          <td><?= e($service['category_name']) ?></td>
          <td><?= money((float) $service['price']) ?></td>
          <td><span class="badge badge-<?= $service['is_active'] ? 'paid' : 'unpaid' ?>"><?= $service['is_active'] ? 'Active' : 'Inactive' ?></span></td>
          <td style="white-space:nowrap;">
            <form method="post" action="<?= url('/admin/services/' . $service['id'] . '/toggle') ?>" style="display:inline;">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-sm btn-outline"><?= $service['is_active'] ? 'Deactivate' : 'Activate' ?></button>
            </form>
            <form method="post" action="<?= url('/admin/services/' . $service['id'] . '/delete') ?>" style="display:inline;" data-confirm="Delete this service?">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-sm btn-danger">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
