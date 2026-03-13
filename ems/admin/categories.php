<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$errors  = [];
$editCat = null;

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    verifyCsrf();
    $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([(int)$_POST['delete_id']]);
    setFlash('success', 'Category deleted.');
    header('Location: ' . ADMIN_PATH . '/categories.php');
    exit;
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_cat'])) {
    verifyCsrf();
    $name  = trim($_POST['cat_name']  ?? '');
    $color = trim($_POST['cat_color'] ?? '#6366f1');
    $icon  = trim($_POST['cat_icon']  ?? '📅');
    $cid   = (int)($_POST['cat_id']   ?? 0);

    if (!$name) $errors[] = 'Category name is required.';

    if (empty($errors)) {
        if ($cid > 0) {
            $pdo->prepare("UPDATE categories SET name=?,color=?,icon=? WHERE id=?")->execute([$name,$color,$icon,$cid]);
            setFlash('success', 'Category updated.');
        } else {
            $pdo->prepare("INSERT INTO categories (name,color,icon) VALUES (?,?,?)")->execute([$name,$color,$icon]);
            setFlash('success', 'Category created.');
        }
        header('Location: ' . ADMIN_PATH . '/categories.php');
        exit;
    }
}

// Load for edit
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editCat = $stmt->fetch();
}

// All categories with event count
$cats = $pdo->query("
    SELECT c.*, COUNT(e.id) AS event_count
    FROM categories c
    LEFT JOIN events e ON e.category_id = c.id
    GROUP BY c.id
    ORDER BY c.name
")->fetchAll();

$presetColors = ['#6366f1','#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#84cc16','#f97316'];
$presetIcons  = ['🎵','💻','⚽','🎨','💼','🍕','🎭','📚','🏋','✈️','🎮','🏥','🎤','🌿','📷'];

$pageTitle   = 'Categories';
$pageHeading = 'Categories';
include __DIR__ . '/../includes/admin_header.php';
?>

<div style="display:grid;grid-template-columns:1fr 380px;gap:1.5rem;align-items:start;" class="cat-grid">

  <!-- List -->
  <div>
    <div class="page-header">
      <div>
        <h2 class="page-title">All Categories</h2>
        <p class="page-subtitle"><?= count($cats) ?> categories</p>
      </div>
    </div>

    <?php if (empty($cats)): ?>
      <div class="empty-state card" style="padding:3rem;">
        <div class="empty-icon">🏷</div>
        <h3>No categories yet</h3>
        <p>Add your first category using the form.</p>
      </div>
    <?php else: ?>
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Icon</th><th>Name</th><th>Color</th><th>Events</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php foreach ($cats as $c): ?>
                <tr>
                  <td style="font-size:1.4rem;text-align:center;"><?= $c['icon'] ?></td>
                  <td class="td-primary"><?= htmlspecialchars($c['name']) ?></td>
                  <td>
                    <div style="display:flex;align-items:center;gap:.5rem;">
                      <div style="width:22px;height:22px;border-radius:6px;background:<?= htmlspecialchars($c['color']) ?>;"></div>
                      <span style="font-size:.78rem;color:var(--txt3);"><?= htmlspecialchars($c['color']) ?></span>
                    </div>
                  </td>
                  <td>
                    <span class="badge badge-accent"><?= $c['event_count'] ?></span>
                  </td>
                  <td>
                    <div style="display:flex;gap:.4rem;">
                      <a href="<?= ADMIN_PATH ?>/categories.php?edit=<?= $c['id'] ?>" class="btn btn-ghost btn-sm">✏️ Edit</a>
                      <?php if ($c['event_count'] == 0): ?>
                        <form method="POST" onsubmit="return confirm('Delete this category?');">
                          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                          <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
                          <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                        </form>
                      <?php else: ?>
                        <span class="btn btn-ghost btn-sm" style="opacity:.4;cursor:not-allowed;" title="Has events">🗑</span>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Form -->
  <div>
    <div class="card" style="position:sticky;top:80px;">
      <div class="card-header">
        <h4><?= $editCat ? '✏️ Edit Category' : '➕ Add Category' ?></h4>
      </div>
      <div class="card-body">
        <?php if (!empty($errors)): ?>
          <div class="alert alert-error">❌ <?= htmlspecialchars($errors[0]) ?></div>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="save_cat"  value="1">
          <input type="hidden" name="cat_id"    value="<?= $editCat['id'] ?? 0 ?>">

          <div class="form-group">
            <label class="form-label">Category Name *</label>
            <input type="text" name="cat_name" class="form-control"
                   placeholder="e.g. Technology"
                   value="<?= htmlspecialchars($editCat['name'] ?? '') ?>" required>
          </div>

          <!-- Icon picker -->
          <div class="form-group">
            <label class="form-label">Icon</label>
            <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;margin-bottom:.5rem;">
              <?php foreach ($presetIcons as $ico): ?>
                <span onclick="document.getElementById('cat_icon_input').value='<?= $ico ?>';document.getElementById('iconPreview').textContent='<?= $ico ?>';"
                      style="cursor:pointer;font-size:1.4rem;padding:.2rem;border-radius:6px;transition:background .2s;"
                      onmouseover="this.style.background='var(--border)'" onmouseout="this.style.background='transparent'">
                  <?= $ico ?>
                </span>
              <?php endforeach; ?>
            </div>
            <div style="display:flex;align-items:center;gap:.75rem;">
              <input type="text" name="cat_icon" id="cat_icon_input" class="form-control"
                     value="<?= htmlspecialchars($editCat['icon'] ?? '📅') ?>"
                     oninput="document.getElementById('iconPreview').textContent=this.value"
                     style="max-width:120px;">
              <span id="iconPreview" style="font-size:2rem;"><?= $editCat['icon'] ?? '📅' ?></span>
            </div>
          </div>

          <!-- Color picker -->
          <div class="form-group">
            <label class="form-label">Color</label>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.5rem;">
              <?php foreach ($presetColors as $clr): ?>
                <div onclick="document.getElementById('cat_color_input').value='<?= $clr ?>';document.getElementById('colorPreview').style.background='<?= $clr ?>';"
                     style="width:28px;height:28px;border-radius:8px;background:<?= $clr ?>;cursor:pointer;border:2px solid transparent;transition:all .2s;"
                     onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">
                </div>
              <?php endforeach; ?>
            </div>
            <div style="display:flex;align-items:center;gap:.75rem;">
              <input type="color" name="cat_color" id="cat_color_input" class="form-control"
                     value="<?= htmlspecialchars($editCat['color'] ?? '#6366f1') ?>"
                     oninput="document.getElementById('colorPreview').style.background=this.value"
                     style="max-width:80px;height:42px;padding:.3rem;cursor:pointer;">
              <div id="colorPreview" style="width:38px;height:38px;border-radius:10px;background:<?= $editCat['color'] ?? '#6366f1' ?>;"></div>
              <input type="text" id="colorText"
                     value="<?= htmlspecialchars($editCat['color'] ?? '#6366f1') ?>"
                     style="max-width:100px;" class="form-control"
                     oninput="document.getElementById('cat_color_input').value=this.value;document.getElementById('colorPreview').style.background=this.value;">
            </div>
          </div>

          <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary"><?= $editCat ? '💾 Save' : '➕ Add Category' ?></button>
            <?php if ($editCat): ?>
              <a href="<?= ADMIN_PATH ?>/categories.php" class="btn btn-ghost">Cancel</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>

<style>@media(max-width:800px){.cat-grid{grid-template-columns:1fr;}}</style>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
