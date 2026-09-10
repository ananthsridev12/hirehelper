<?php
/** @var array $categories */
/** @var array $errors */
$pageTitle = 'Categories';
$icons = ['home', 'clock', 'check', 'star', 'user', 'calendar', 'map-pin', 'phone', 'plus'];
?>
<div class="page-header">
  <h1 class="section-title">Categories</h1>
</div>

<?php foreach ($errors as $error): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<div class="card">
  <h3>Add category</h3>
  <form method="post" action="<?= url('/admin/categories') ?>">
    <?= csrf_field() ?>
    <div class="form-row">
      <div class="form-group">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" required>
      </div>
      <div class="form-group">
        <label for="icon">Icon</label>
        <select id="icon" name="icon">
          <?php foreach ($icons as $icon): ?>
            <option value="<?= e($icon) ?>"><?= e($icon) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="sort_order">Sort order</label>
        <input type="number" id="sort_order" name="sort_order" value="0">
      </div>
    </div>
    <div class="form-group">
      <label for="description">Description</label>
      <input type="text" id="description" name="description">
    </div>
    <button type="submit" class="btn">Add Category</button>
  </form>
</div>

<div class="card table-wrap">
  <table>
    <thead>
      <tr><th>Name</th><th>Slug</th><th>Status</th><th>Order</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($categories as $category): ?>
        <tr>
          <td><?= e($category['name']) ?></td>
          <td><?= e($category['slug']) ?></td>
          <td><span class="badge badge-<?= $category['is_active'] ? 'paid' : 'unpaid' ?>"><?= $category['is_active'] ? 'Active' : 'Inactive' ?></span></td>
          <td><?= (int) $category['sort_order'] ?></td>
          <td style="white-space:nowrap;">
            <form method="post" action="<?= url('/admin/categories/' . $category['id'] . '/toggle') ?>" style="display:inline;">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-sm btn-outline"><?= $category['is_active'] ? 'Deactivate' : 'Activate' ?></button>
            </form>
            <form method="post" action="<?= url('/admin/categories/' . $category['id'] . '/delete') ?>" style="display:inline;" data-confirm="Delete this category?">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-sm btn-danger">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
