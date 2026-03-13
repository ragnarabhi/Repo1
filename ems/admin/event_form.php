<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$id    = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;
$errors = [];

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Load event for edit
$ev = [
    'title'       => '',
    'description' => '',
    'category_id' => '',
    'event_date'  => '',
    'event_time'  => '09:00',
    'location'    => '',
    'capacity'    => 100,
    'price'       => 0,
    'status'      => 'upcoming',
];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id=?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { header('Location: ' . ADMIN_PATH . '/events.php'); exit; }
    $ev = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $ev['title']       = trim($_POST['title']       ?? '');
    $ev['description'] = trim($_POST['description'] ?? '');
    $ev['category_id'] = (int)($_POST['category_id']?? 0) ?: null;
    $ev['event_date']  = trim($_POST['event_date']  ?? '');
    $ev['event_time']  = trim($_POST['event_time']  ?? '');
    $ev['location']    = trim($_POST['location']    ?? '');
    $ev['capacity']    = max(1, (int)($_POST['capacity']  ?? 100));
    $ev['price']       = max(0, (float)($_POST['price']   ?? 0));
    $ev['status']      = $_POST['status'] ?? 'upcoming';

    if (!$ev['title'])      $errors[] = 'Title is required.';
    if (!$ev['event_date']) $errors[] = 'Event date is required.';
    if (!$ev['location'])   $errors[] = 'Location is required.';

    if (empty($errors)) {
        if ($isEdit) {
            $sql = "UPDATE events SET title=?,description=?,category_id=?,event_date=?,event_time=?,location=?,capacity=?,price=?,status=? WHERE id=?";
            $pdo->prepare($sql)->execute([
                $ev['title'], $ev['description'], $ev['category_id'],
                $ev['event_date'], $ev['event_time'], $ev['location'],
                $ev['capacity'], $ev['price'], $ev['status'], $id
            ]);
            setFlash('success', 'Event updated successfully.');
        } else {
            $sql = "INSERT INTO events (title,description,category_id,event_date,event_time,location,capacity,price,status) VALUES (?,?,?,?,?,?,?,?,?)";
            $pdo->prepare($sql)->execute([
                $ev['title'], $ev['description'], $ev['category_id'],
                $ev['event_date'], $ev['event_time'], $ev['location'],
                $ev['capacity'], $ev['price'], $ev['status']
            ]);
            setFlash('success', 'Event created successfully! 🎉');
        }
        header('Location: ' . ADMIN_PATH . '/events.php');
        exit;
    }
}

$pageTitle   = $isEdit ? 'Edit Event' : 'New Event';
$pageHeading = $isEdit ? 'Edit Event' : 'New Event';
include __DIR__ . '/../includes/admin_header.php';
?>

<div style="max-width:800px;">
  <div class="page-header">
    <div>
      <h2 class="page-title"><?= $isEdit ? '✏️ Edit Event' : '➕ Create Event' ?></h2>
      <p class="page-subtitle"><?= $isEdit ? 'Update event details below.' : 'Fill in the details to create a new event.' ?></p>
    </div>
    <a href="<?= ADMIN_PATH ?>/events.php" class="btn btn-ghost">← Back</a>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
      ❌ Please fix the following:<br>
      <?php foreach ($errors as $e): ?>
        &nbsp;&nbsp;• <?= htmlspecialchars($e) ?><br>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

    <div class="card">
      <div class="card-header"><h4>Basic Info</h4></div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label">Event Title *</label>
          <input type="text" name="title" class="form-control"
                 placeholder="e.g. Tech Summit 2025"
                 value="<?= htmlspecialchars($ev['title']) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="5"
                    placeholder="Tell attendees what this event is about…"><?= htmlspecialchars($ev['description']) ?></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-control">
              <option value="">— No Category —</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $ev['category_id'] == $c['id'] ? 'selected' : '' ?>>
                  <?= $c['icon'] ?> <?= htmlspecialchars($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <option value="upcoming"  <?= $ev['status']==='upcoming'  ?'selected':'' ?>>Upcoming</option>
              <option value="ongoing"   <?= $ev['status']==='ongoing'   ?'selected':'' ?>>Ongoing</option>
              <option value="completed" <?= $ev['status']==='completed' ?'selected':'' ?>>Completed</option>
              <option value="cancelled" <?= $ev['status']==='cancelled' ?'selected':'' ?>>Cancelled</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="card" style="margin-top:1.25rem;">
      <div class="card-header"><h4>Date, Time & Location</h4></div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Event Date *</label>
            <input type="date" name="event_date" class="form-control"
                   value="<?= htmlspecialchars($ev['event_date']) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Event Time</label>
            <input type="time" name="event_time" class="form-control"
                   value="<?= htmlspecialchars($ev['event_time']) ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Location / Venue *</label>
          <input type="text" name="location" class="form-control"
                 placeholder="e.g. Convention Center, New Delhi"
                 value="<?= htmlspecialchars($ev['location']) ?>" required>
        </div>
      </div>
    </div>

    <div class="card" style="margin-top:1.25rem;">
      <div class="card-header"><h4>Tickets & Pricing</h4></div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Total Capacity</label>
            <input type="number" name="capacity" class="form-control" min="1" max="100000"
                   value="<?= (int)$ev['capacity'] ?>">
            <div class="form-hint">Maximum number of attendees.</div>
          </div>
          <div class="form-group">
            <label class="form-label">Ticket Price (₹)</label>
            <input type="number" name="price" class="form-control" min="0" step="0.01"
                   value="<?= number_format((float)$ev['price'], 2, '.', '') ?>"
                   placeholder="0.00 for free">
            <div class="form-hint">Set 0 for a free event.</div>
          </div>
        </div>
      </div>
    </div>

    <div style="display:flex;gap:.75rem;margin-top:1.5rem;flex-wrap:wrap;">
      <button type="submit" class="btn btn-primary btn-lg">
        <?= $isEdit ? '💾 Save Changes' : '🚀 Create Event' ?>
      </button>
      <a href="<?= ADMIN_PATH ?>/events.php" class="btn btn-ghost btn-lg">Cancel</a>
      <?php if ($isEdit): ?>
        <a href="<?= BASE_URL ?>/event_detail.php?id=<?= $id ?>" target="_blank" class="btn btn-ghost btn-lg">👁 Preview</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
