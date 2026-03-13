<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    verifyCsrf();
    $del = $pdo->prepare("DELETE FROM events WHERE id=?");
    $del->execute([(int)$_POST['delete_id']]);
    setFlash('success', 'Event deleted.');
    header('Location: ' . ADMIN_PATH . '/events.php');
    exit;
}

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    verifyCsrf();
    $eid    = (int)$_POST['event_id'];
    $status = $_POST['new_status'];
    $allowed = ['upcoming','ongoing','completed','cancelled'];
    if (in_array($status, $allowed)) {
        $pdo->prepare("UPDATE events SET status=? WHERE id=?")->execute([$status, $eid]);
        setFlash('success', 'Event status updated.');
    }
    header('Location: ' . ADMIN_PATH . '/events.php');
    exit;
}

// Filters
$search = trim($_GET['q'] ?? '');
$filter = trim($_GET['status'] ?? '');
$cat    = (int)($_GET['cat'] ?? 0);
$page   = max(1, (int)($_GET['page'] ?? 1));
$per    = 12;
$offset = ($page - 1) * $per;

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$where  = ["1=1"];
$params = [];

if ($search) { $where[] = "e.title LIKE ?"; $params[] = "%$search%"; }
if ($filter)  { $where[] = "e.status = ?";  $params[] = $filter; }
if ($cat > 0) { $where[] = "e.category_id = ?"; $params[] = $cat; }

$where = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) FROM events e WHERE $where");
$total->execute($params);
$totalCount = (int)$total->fetchColumn();
$totalPages = max(1, ceil($totalCount / $per));

$stmt = $pdo->prepare("
    SELECT e.*, c.name AS cat_name, c.color AS cat_color, c.icon AS cat_icon,
           (SELECT COALESCE(SUM(tickets),0) FROM bookings b WHERE b.event_id=e.id AND b.status='confirmed') AS booked
    FROM events e
    LEFT JOIN categories c ON c.id = e.category_id
    WHERE $where
    ORDER BY e.event_date DESC
    LIMIT $per OFFSET $offset
");
$stmt->execute($params);
$events = $stmt->fetchAll();

$pageTitle   = 'Events';
$pageHeading = 'Events';
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="page-header">
  <div>
    <h2 class="page-title">All Events</h2>
    <p class="page-subtitle"><?= $totalCount ?> event<?= $totalCount!==1?'s':'' ?> total</p>
  </div>
  <a href="<?= ADMIN_PATH ?>/event_form.php" class="btn btn-primary">+ Create Event</a>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:1.5rem;padding:1rem 1.25rem;">
  <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" class="form-control" placeholder="🔍 Search events…"
           value="<?= htmlspecialchars($search) ?>" style="max-width:250px;">
    <select name="status" class="form-control" style="max-width:160px;">
      <option value="">All Status</option>
      <option value="upcoming"  <?= $filter==='upcoming'  ?'selected':'' ?>>Upcoming</option>
      <option value="ongoing"   <?= $filter==='ongoing'   ?'selected':'' ?>>Ongoing</option>
      <option value="completed" <?= $filter==='completed' ?'selected':'' ?>>Completed</option>
      <option value="cancelled" <?= $filter==='cancelled' ?'selected':'' ?>>Cancelled</option>
    </select>
    <select name="cat" class="form-control" style="max-width:180px;">
      <option value="0">All Categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $cat==$c['id']?'selected':'' ?>><?= $c['icon'] ?> <?= htmlspecialchars($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-ghost">Filter</button>
    <?php if ($search || $filter || $cat): ?>
      <a href="<?= ADMIN_PATH ?>/events.php" class="btn btn-ghost">Clear</a>
    <?php endif; ?>
  </form>
</div>

<?php if (empty($events)): ?>
  <div class="empty-state card" style="padding:3rem;">
    <div class="empty-icon">🗓</div>
    <h3>No events found</h3>
    <p>Create your first event to get started.</p>
    <a href="<?= ADMIN_PATH ?>/event_form.php" class="btn btn-primary mt-2">+ Create Event</a>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Event</th>
            <th>Category</th>
            <th>Date</th>
            <th>Location</th>
            <th>Bookings</th>
            <th>Price</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($events as $e):
            $statusColors = [
              'upcoming'  => 'badge-blue',
              'ongoing'   => 'badge-green',
              'completed' => 'badge-gray',
              'cancelled' => 'badge-red',
            ];
          ?>
            <tr>
              <td style="color:var(--txt3);"><?= $e['id'] ?></td>
              <td>
                <div style="font-weight:600;color:var(--txt);max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <?= htmlspecialchars($e['title']) ?>
                </div>
              </td>
              <td>
                <?php if ($e['cat_name']): ?>
                  <span class="badge badge-gray"><?= $e['cat_icon'] ?> <?= htmlspecialchars($e['cat_name']) ?></span>
                <?php else: ?>
                  <span style="color:var(--txt3);">—</span>
                <?php endif; ?>
              </td>
              <td style="white-space:nowrap;"><?= date('M j, Y', strtotime($e['event_date'])) ?></td>
              <td style="max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($e['location']) ?></td>
              <td>
                <span style="color:var(--accent);font-weight:600;"><?= $e['booked'] ?></span>
                <span style="color:var(--txt3);">/<?= $e['capacity'] ?></span>
              </td>
              <td><?= (float)$e['price'] === 0.0 ? '<span style="color:var(--green);">Free</span>' : '₹'.number_format($e['price'],2) ?></td>
              <td>
                <!-- Status Change -->
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="change_status" value="1">
                  <input type="hidden" name="event_id" value="<?= $e['id'] ?>">
                  <select name="new_status" class="form-control" style="padding:.25rem .5rem;font-size:.78rem;width:auto;height:auto;"
                          onchange="this.form.submit()">
                    <option value="upcoming"  <?= $e['status']==='upcoming' ?'selected':'' ?>>Upcoming</option>
                    <option value="ongoing"   <?= $e['status']==='ongoing'  ?'selected':'' ?>>Ongoing</option>
                    <option value="completed" <?= $e['status']==='completed'?'selected':'' ?>>Completed</option>
                    <option value="cancelled" <?= $e['status']==='cancelled'?'selected':'' ?>>Cancelled</option>
                  </select>
                </form>
              </td>
              <td>
                <div style="display:flex;gap:.4rem;">
                  <a href="<?= ADMIN_PATH ?>/event_form.php?id=<?= $e['id'] ?>" class="btn btn-ghost btn-sm" title="Edit">✏️</a>
                  <a href="<?= BASE_URL ?>/event_detail.php?id=<?= $e['id'] ?>" target="_blank" class="btn btn-ghost btn-sm" title="View">👁</a>
                  <form method="POST" onsubmit="return confirm('Delete this event and all its bookings?');">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="delete_id" value="<?= $e['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm" title="Delete">🗑</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">‹</a>
      <?php endif; ?>
      <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
        <?php if ($i == $page): ?>
          <span class="active"><?= $i ?></span>
        <?php else: ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">›</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
