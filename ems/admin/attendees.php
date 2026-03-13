<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_booking_status'])) {
    verifyCsrf();
    $bid    = (int)$_POST['booking_id'];
    $status = $_POST['new_status'];
    if (in_array($status, ['confirmed','cancelled','pending'])) {
        $pdo->prepare("UPDATE bookings SET status=? WHERE id=?")->execute([$status, $bid]);
        setFlash('success', 'Booking status updated.');
    }
    header('Location: ' . ADMIN_PATH . '/attendees.php?' . http_build_query(array_diff_key($_GET, ['page'=>1])));
    exit;
}

// Filters
$search   = trim($_GET['q']      ?? '');
$eventId  = (int)($_GET['event'] ?? 0);
$status   = trim($_GET['status'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$per      = 20;
$offset   = ($page - 1) * $per;

// Events dropdown
$events = $pdo->query("SELECT id, title FROM events ORDER BY event_date DESC")->fetchAll();

// Build query
$where  = ["1=1"];
$params = [];

if ($search) {
    $where[]  = "(u.name LIKE ? OR u.email LIKE ? OR e.title LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($eventId > 0) { $where[] = "b.event_id = ?"; $params[] = $eventId; }
if ($status)       { $where[] = "b.status = ?";   $params[] = $status; }

$where = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN users u ON u.id=b.user_id JOIN events e ON e.id=b.event_id WHERE $where");
$total->execute($params);
$totalCount = (int)$total->fetchColumn();
$totalPages = max(1, ceil($totalCount / $per));

$stmt = $pdo->prepare("
    SELECT b.*, u.name AS user_name, u.email AS user_email,
           e.title AS event_title, e.event_date, c.icon AS cat_icon
    FROM bookings b
    JOIN users u   ON u.id  = b.user_id
    JOIN events e  ON e.id  = b.event_id
    LEFT JOIN categories c ON c.id = e.category_id
    WHERE $where
    ORDER BY b.booked_at DESC
    LIMIT $per OFFSET $offset
");
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Summary stats
$confirmedCount = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetchColumn();
$cancelledCount = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='cancelled'")->fetchColumn();
$pendingCount   = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();

$pageTitle   = 'Attendees';
$pageHeading = 'Attendees';
include __DIR__ . '/../includes/admin_header.php';
?>

<!-- Mini stats -->
<div style="display:flex;gap:1rem;margin-bottom:1.75rem;flex-wrap:wrap;">
  <div class="card" style="flex:1;min-width:150px;padding:1rem 1.25rem;display:flex;align-items:center;gap:.75rem;">
    <span style="font-size:1.3rem;">🎟</span>
    <div><div style="font-size:1.4rem;font-weight:800;color:var(--green);"><?= $confirmedCount ?></div><div style="font-size:.75rem;color:var(--txt3);">Confirmed</div></div>
  </div>
  <div class="card" style="flex:1;min-width:150px;padding:1rem 1.25rem;display:flex;align-items:center;gap:.75rem;">
    <span style="font-size:1.3rem;">⏳</span>
    <div><div style="font-size:1.4rem;font-weight:800;color:var(--yellow);"><?= $pendingCount ?></div><div style="font-size:.75rem;color:var(--txt3);">Pending</div></div>
  </div>
  <div class="card" style="flex:1;min-width:150px;padding:1rem 1.25rem;display:flex;align-items:center;gap:.75rem;">
    <span style="font-size:1.3rem;">❌</span>
    <div><div style="font-size:1.4rem;font-weight:800;color:var(--red);"><?= $cancelledCount ?></div><div style="font-size:.75rem;color:var(--txt3);">Cancelled</div></div>
  </div>
  <div class="card" style="flex:1;min-width:150px;padding:1rem 1.25rem;display:flex;align-items:center;gap:.75rem;">
    <span style="font-size:1.3rem;">📊</span>
    <div><div style="font-size:1.4rem;font-weight:800;color:var(--accent);"><?= $confirmedCount + $pendingCount ?></div><div style="font-size:.75rem;color:var(--txt3);">Total Active</div></div>
  </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:1.5rem;padding:1rem 1.25rem;">
  <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" class="form-control" placeholder="🔍 Search name, email, event…"
           value="<?= htmlspecialchars($search) ?>" style="max-width:260px;">
    <select name="event" class="form-control" style="max-width:220px;">
      <option value="0">All Events</option>
      <?php foreach ($events as $ev): ?>
        <option value="<?= $ev['id'] ?>" <?= $eventId==$ev['id']?'selected':'' ?>>
          <?= htmlspecialchars(mb_strimwidth($ev['title'], 0, 35, '…')) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="form-control" style="max-width:160px;">
      <option value="">All Status</option>
      <option value="confirmed" <?= $status==='confirmed'?'selected':'' ?>>Confirmed</option>
      <option value="pending"   <?= $status==='pending'  ?'selected':'' ?>>Pending</option>
      <option value="cancelled" <?= $status==='cancelled'?'selected':'' ?>>Cancelled</option>
    </select>
    <button type="submit" class="btn btn-ghost">Filter</button>
    <?php if ($search || $eventId || $status): ?>
      <a href="<?= ADMIN_PATH ?>/attendees.php" class="btn btn-ghost">Clear</a>
    <?php endif; ?>
    <span style="margin-left:auto;font-size:.85rem;color:var(--txt3);"><?= $totalCount ?> result<?= $totalCount!==1?'s':'' ?></span>
  </form>
</div>

<?php if (empty($bookings)): ?>
  <div class="empty-state card" style="padding:3rem;">
    <div class="empty-icon">👥</div>
    <h3>No bookings found</h3>
    <p>Bookings will appear here once users register for events.</p>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Attendee</th>
            <th>Email</th>
            <th>Event</th>
            <th>Date</th>
            <th>Tickets</th>
            <th>Booked At</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
            <tr>
              <td style="color:var(--txt3);"><?= $b['id'] ?></td>
              <td class="td-primary"><?= htmlspecialchars($b['user_name']) ?></td>
              <td style="font-size:.82rem;color:var(--txt3);"><?= htmlspecialchars($b['user_email']) ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:.4rem;max-width:180px;">
                  <span><?= $b['cat_icon'] ?? '🎪' ?></span>
                  <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:.875rem;color:var(--txt2);">
                    <?= htmlspecialchars($b['event_title']) ?>
                  </span>
                </div>
              </td>
              <td style="white-space:nowrap;font-size:.82rem;"><?= date('M j, Y', strtotime($b['event_date'])) ?></td>
              <td style="text-align:center;font-weight:600;color:var(--accent);"><?= $b['tickets'] ?></td>
              <td style="font-size:.78rem;color:var(--txt3);white-space:nowrap;"><?= date('M j, g:i A', strtotime($b['booked_at'])) ?></td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="change_booking_status" value="1">
                  <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                  <select name="new_status" class="form-control"
                          style="padding:.2rem .45rem;font-size:.78rem;width:auto;height:auto;min-width:105px;"
                          onchange="this.form.submit()">
                    <option value="confirmed" <?= $b['status']==='confirmed'?'selected':'' ?>>✅ Confirmed</option>
                    <option value="pending"   <?= $b['status']==='pending'  ?'selected':'' ?>>⏳ Pending</option>
                    <option value="cancelled" <?= $b['status']==='cancelled'?'selected':'' ?>>❌ Cancelled</option>
                  </select>
                </form>
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
