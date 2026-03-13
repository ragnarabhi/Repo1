<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

requireUser();
$u = currentUser();

// Handle cancel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    verifyCsrf();
    $bid = (int)$_POST['booking_id'];
    $upd = $pdo->prepare("UPDATE bookings SET status='cancelled' WHERE id=? AND user_id=?");
    $upd->execute([$bid, $u['id']]);
    setFlash('success', 'Booking cancelled successfully.');
    header('Location: ' . BASE_URL . '/user_dashboard.php');
    exit;
}

// Fetch bookings
$stmt = $pdo->prepare("
    SELECT b.*, e.title, e.event_date, e.event_time, e.location,
           e.price, e.status AS event_status,
           c.color AS cat_color, c.icon AS cat_icon, c.name AS cat_name
    FROM bookings b
    JOIN events e ON e.id = b.event_id
    LEFT JOIN categories c ON c.id = e.category_id
    WHERE b.user_id = ?
    ORDER BY e.event_date DESC
");
$stmt->execute([$u['id']]);
$bookings = $stmt->fetchAll();

$upcoming  = array_filter($bookings, fn($b) => in_array($b['event_status'], ['upcoming','ongoing']) && $b['status'] === 'confirmed');
$past      = array_filter($bookings, fn($b) => $b['event_status'] === 'completed' || $b['status'] === 'cancelled');

$pageTitle = 'My Bookings';
include __DIR__ . '/includes/header.php';
?>

<section class="section-sm">
  <div class="container-sm">

    <!-- Profile Header -->
    <div class="card" style="margin-bottom:2rem;padding:1.75rem;display:flex;align-items:center;gap:1.25rem;flex-wrap:wrap;">
      <div class="avatar" style="width:56px;height:56px;font-size:1.3rem;flex-shrink:0;"><?= strtoupper(substr($u['name'],0,1)) ?></div>
      <div style="flex:1;">
        <h2 style="margin-bottom:.2rem;"><?= htmlspecialchars($u['name']) ?></h2>
        <p style="font-size:.875rem;"><?= htmlspecialchars($u['email']) ?></p>
      </div>
      <div style="display:flex;gap:1.5rem;text-align:center;">
        <div>
          <div style="font-size:1.5rem;font-weight:800;color:var(--accent);"><?= count($bookings) ?></div>
          <div style="font-size:.75rem;color:var(--txt3);">Total</div>
        </div>
        <div>
          <div style="font-size:1.5rem;font-weight:800;color:var(--green);"><?= count($upcoming) ?></div>
          <div style="font-size:.75rem;color:var(--txt3);">Upcoming</div>
        </div>
      </div>
      <a href="<?= BASE_URL ?>/logout.php" class="btn btn-ghost btn-sm">↩ Logout</a>
    </div>

    <!-- Upcoming -->
    <div style="margin-bottom:2rem;">
      <h3 style="margin-bottom:1rem;">🎟 Upcoming Events</h3>

      <?php if (empty($upcoming)): ?>
        <div class="card" style="padding:2rem;text-align:center;">
          <div style="font-size:2rem;margin-bottom:.75rem;">🗓</div>
          <p style="color:var(--txt2);">No upcoming bookings.</p>
          <a href="<?= BASE_URL ?>" class="btn btn-primary btn-sm mt-2">Browse Events</a>
        </div>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:.85rem;">
          <?php foreach ($upcoming as $b):
            $color = $b['cat_color'] ?? '#6366f1';
          ?>
            <div class="booking-item">
              <div class="booking-icon" style="background:<?= $color ?>22;">
                <span><?= $b['cat_icon'] ?? '🎪' ?></span>
              </div>
              <div style="flex:1;min-width:0;">
                <div style="font-weight:600;color:var(--txt);margin-bottom:.2rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <a href="<?= BASE_URL ?>/event_detail.php?id=<?= $b['event_id'] ?>" style="color:var(--txt);"><?= htmlspecialchars($b['title']) ?></a>
                </div>
                <div style="font-size:.82rem;color:var(--txt3);display:flex;gap:.75rem;flex-wrap:wrap;">
                  <span>📅 <?= date('M j, Y', strtotime($b['event_date'])) ?></span>
                  <span>📍 <?= htmlspecialchars($b['location']) ?></span>
                  <span>🎟 <?= $b['tickets'] ?> ticket<?= $b['tickets']>1?'s':'' ?></span>
                </div>
              </div>
              <div style="text-align:right;flex-shrink:0;">
                <span class="badge badge-green" style="display:block;margin-bottom:.5rem;">✓ Confirmed</span>
                <?php if ($b['event_status'] === 'upcoming'): ?>
                  <form method="POST" onsubmit="return confirm('Cancel this booking?');">
                    <input type="hidden" name="csrf_token"  value="<?= csrfToken() ?>">
                    <input type="hidden" name="cancel_booking" value="1">
                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Past / Cancelled -->
    <?php if (!empty($past)): ?>
      <div>
        <h3 style="margin-bottom:1rem;">📋 Past & Cancelled</h3>
        <div style="display:flex;flex-direction:column;gap:.75rem;">
          <?php foreach ($past as $b):
            $canceled = $b['status'] === 'cancelled';
          ?>
            <div class="booking-item" style="opacity:<?= $canceled ? '.6' : '.85' ?>;">
              <div class="booking-icon" style="background:var(--border2);">
                <span><?= $b['cat_icon'] ?? '🎪' ?></span>
              </div>
              <div style="flex:1;min-width:0;">
                <div style="font-weight:600;color:var(--txt2);margin-bottom:.2rem;"><?= htmlspecialchars($b['title']) ?></div>
                <div style="font-size:.82rem;color:var(--txt3);">
                  📅 <?= date('M j, Y', strtotime($b['event_date'])) ?> · <?= $b['tickets'] ?> ticket<?= $b['tickets']>1?'s':'' ?>
                </div>
              </div>
              <span class="badge <?= $canceled ? 'badge-red' : 'badge-gray' ?>">
                <?= $canceled ? '✕ Cancelled' : '✓ Attended' ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <div style="margin-top:2rem;text-align:center;">
      <a href="<?= BASE_URL ?>" class="btn btn-primary">🎪 Discover More Events</a>
    </div>

  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
