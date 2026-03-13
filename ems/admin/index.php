<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

// ── Stats ─────────────────────────────────────────────────────────
$totalEvents    = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$upcomingEvents = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status='upcoming'")->fetchColumn();
$totalUsers     = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalBookings  = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetchColumn();
$totalCategories= (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$cancelledCount = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='cancelled'")->fetchColumn();

// Revenue estimate
$revenue = $pdo->query("
    SELECT COALESCE(SUM(b.tickets * e.price), 0)
    FROM bookings b JOIN events e ON e.id = b.event_id
    WHERE b.status='confirmed'
")->fetchColumn();

// Recent bookings
$recentBookings = $pdo->query("
    SELECT b.*, u.name AS user_name, e.title AS event_title, e.event_date
    FROM bookings b
    JOIN users u  ON u.id = b.user_id
    JOIN events e ON e.id = b.event_id
    ORDER BY b.booked_at DESC
    LIMIT 8
")->fetchAll();

// Recent events
$recentEvents = $pdo->query("
    SELECT e.*, c.name AS cat_name, c.icon AS cat_icon,
           (SELECT COALESCE(SUM(tickets),0) FROM bookings b WHERE b.event_id=e.id AND b.status='confirmed') AS booked
    FROM events e
    LEFT JOIN categories c ON c.id = e.category_id
    ORDER BY e.created_at DESC
    LIMIT 5
")->fetchAll();

$pageTitle   = 'Dashboard';
$pageHeading = 'Dashboard';
include __DIR__ . '/../includes/admin_header.php';
?>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon purple">🗓</div>
    <div>
      <div class="stat-label">Total Events</div>
      <div class="stat-value"><?= $totalEvents ?></div>
      <div style="font-size:.78rem;color:var(--green);margin-top:.2rem;">↑ <?= $upcomingEvents ?> upcoming</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue">👥</div>
    <div>
      <div class="stat-label">Registered Users</div>
      <div class="stat-value"><?= $totalUsers ?></div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green">🎟</div>
    <div>
      <div class="stat-label">Confirmed Bookings</div>
      <div class="stat-value"><?= $totalBookings ?></div>
      <div style="font-size:.78rem;color:var(--red);margin-top:.2rem;">↓ <?= $cancelledCount ?> cancelled</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow">💰</div>
    <div>
      <div class="stat-label">Est. Revenue</div>
      <div class="stat-value" style="font-size:1.4rem;">₹<?= number_format($revenue, 0) ?></div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red">🏷</div>
    <div>
      <div class="stat-label">Categories</div>
      <div class="stat-value"><?= $totalCategories ?></div>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;" class="dashboard-grid">

  <!-- Recent Bookings -->
  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
      <h4>Recent Bookings</h4>
      <a href="<?= ADMIN_PATH ?>/attendees.php" class="btn btn-ghost btn-sm">View all</a>
    </div>
    <div class="table-wrap">
      <?php if (empty($recentBookings)): ?>
        <div style="padding:2rem;text-align:center;color:var(--txt3);">No bookings yet.</div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>User</th>
              <th>Event</th>
              <th>Tickets</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentBookings as $b): ?>
              <tr>
                <td class="td-primary"><?= htmlspecialchars($b['user_name']) ?></td>
                <td style="max-width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <?= htmlspecialchars($b['event_title']) ?>
                </td>
                <td><?= $b['tickets'] ?></td>
                <td>
                  <span class="badge <?= $b['status']==='confirmed' ? 'badge-green' : ($b['status']==='cancelled' ? 'badge-red' : 'badge-yellow') ?>">
                    <?= ucfirst($b['status']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Events -->
  <div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
      <h4>Recent Events</h4>
      <a href="<?= ADMIN_PATH ?>/event_form.php" class="btn btn-primary btn-sm">+ New</a>
    </div>
    <div style="display:flex;flex-direction:column;gap:0;">
      <?php if (empty($recentEvents)): ?>
        <div style="padding:2rem;text-align:center;color:var(--txt3);">No events yet. <a href="<?= ADMIN_PATH ?>/event_form.php">Create one!</a></div>
      <?php else: ?>
        <?php foreach ($recentEvents as $e): ?>
          <div style="display:flex;align-items:center;gap:.75rem;padding:.9rem 1.25rem;border-bottom:1px solid var(--border2);">
            <span style="font-size:1.3rem;"><?= $e['cat_icon'] ?? '🎪' ?></span>
            <div style="flex:1;min-width:0;">
              <div style="font-size:.875rem;font-weight:600;color:var(--txt);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?= htmlspecialchars($e['title']) ?>
              </div>
              <div style="font-size:.78rem;color:var(--txt3);">
                <?= date('M j, Y', strtotime($e['event_date'])) ?> · <?= $e['booked'] ?>/<?= $e['capacity'] ?> booked
              </div>
            </div>
            <span class="badge <?= $e['status']==='upcoming' ? 'badge-blue' : ($e['status']==='ongoing'?'badge-green':'badge-gray') ?>">
              <?= ucfirst($e['status']) ?>
            </span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div class="card-footer">
      <a href="<?= ADMIN_PATH ?>/events.php" style="font-size:.85rem;">View all events →</a>
    </div>
  </div>

</div>

<style>
@media(max-width:800px){.dashboard-grid{grid-template-columns:1fr;}}
</style>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
