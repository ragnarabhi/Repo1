<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL); exit; }

$stmt = $pdo->prepare("
    SELECT e.*, c.name AS cat_name, c.color AS cat_color, c.icon AS cat_icon
    FROM events e
    LEFT JOIN categories c ON c.id = e.category_id
    WHERE e.id = ?
");
$stmt->execute([$id]);
$ev = $stmt->fetch();
if (!$ev) { header('Location: ' . BASE_URL); exit; }

// Booked count & seats left
$bookedCount = (int)$pdo->prepare("SELECT COUNT(*) FROM bookings WHERE event_id=? AND status='confirmed'")
                  ->execute([$id]) ? $pdo->prepare("SELECT SUM(tickets) FROM bookings WHERE event_id=? AND status='confirmed'") : 0;
$stmt2 = $pdo->prepare("SELECT COALESCE(SUM(tickets),0) FROM bookings WHERE event_id=? AND status='confirmed'");
$stmt2->execute([$id]);
$totalBooked = (int)$stmt2->fetchColumn();
$spotsLeft   = max(0, $ev['capacity'] - $totalBooked);

// Check if user already booked
$userBooked  = false;
$userBooking = null;
if (isUserLoggedIn()) {
    $u = currentUser();
    $stmt3 = $pdo->prepare("SELECT * FROM bookings WHERE user_id=? AND event_id=?");
    $stmt3->execute([$u['id'], $id]);
    $userBooking = $stmt3->fetch();
    $userBooked  = (bool)$userBooking;
}

// ── Handle Booking ────────────────────────────────────────────────
$bookError   = '';
$bookSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    if (!isUserLoggedIn()) {
        setFlash('info', 'Please login to book this event.');
        header('Location: ' . BASE_URL . '/user_login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    verifyCsrf();

    $tickets = max(1, min(10, (int)($_POST['tickets'] ?? 1)));
    $u       = currentUser();

    if ($userBooked) {
        $bookError = 'You have already booked this event.';
    } elseif ($ev['status'] === 'cancelled') {
        $bookError = 'This event has been cancelled.';
    } elseif ($ev['status'] === 'completed') {
        $bookError = 'This event has already ended.';
    } elseif ($spotsLeft < $tickets) {
        $bookError = "Only $spotsLeft spot(s) remaining.";
    } else {
        try {
            $ins = $pdo->prepare("INSERT INTO bookings (user_id, event_id, tickets, status) VALUES (?,?,?,'confirmed')");
            $ins->execute([$u['id'], $id, $tickets]);
            setFlash('success', "🎉 Booking confirmed! You registered $tickets ticket(s) for \"{$ev['title']}\".");
            header('Location: ' . BASE_URL . '/event_detail.php?id=' . $id);
            exit;
        } catch (PDOException $e) {
            $bookError = 'Booking failed. You may have already booked this event.';
        }
    }
}

$color   = $ev['cat_color'] ?? '#6366f1';
$icon    = $ev['cat_icon']  ?? '🎪';
$free    = (float)$ev['price'] === 0.0;

$statusLabels = [
  'upcoming'  => ['badge-blue',  'Upcoming'],
  'ongoing'   => ['badge-green', 'Live 🔴'],
  'completed' => ['badge-gray',  'Ended'],
  'cancelled' => ['badge-red',   'Cancelled'],
];
[$sBadge, $sLabel] = $statusLabels[$ev['status']] ?? ['badge-gray', $ev['status']];

$pageTitle = $ev['title'];
include __DIR__ . '/includes/header.php';
?>

<!-- Event Hero Banner -->
<div class="event-hero" style="--banner-from:<?= $color ?>66;--banner-to:#0a0a14;">
  <div class="event-hero-bg"></div>
  <div class="event-hero-overlay"></div>
  <span style="position:relative;z-index:2;font-size:4.5rem;"><?= $icon ?></span>
</div>

<div class="container" style="padding-top:2rem;padding-bottom:3rem;">
  <div class="event-detail-grid">

    <!-- Left: Info -->
    <div>
      <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1rem;">
        <span class="badge <?= $sBadge ?>"><?= $sLabel ?></span>
        <?php if ($ev['cat_name']): ?>
          <span class="badge badge-accent"><?= $ev['cat_icon'] ?> <?= htmlspecialchars($ev['cat_name']) ?></span>
        <?php endif; ?>
      </div>

      <h1 style="margin-bottom:1rem;"><?= htmlspecialchars($ev['title']) ?></h1>

      <ul class="event-info-list" style="margin-bottom:2rem;">
        <li><span class="info-icon">📅</span><span><?= date('l, F j, Y', strtotime($ev['event_date'])) ?> at <?= date('g:i A', strtotime($ev['event_time'])) ?></span></li>
        <li><span class="info-icon">📍</span><span><?= htmlspecialchars($ev['location']) ?></span></li>
        <li><span class="info-icon">👥</span><span>Capacity: <?= number_format($ev['capacity']) ?> &nbsp;·&nbsp; <?= $spotsLeft ?> spots remaining</span></li>
        <li><span class="info-icon">💰</span><span><?= $free ? 'Free Event' : '₹' . number_format($ev['price'], 2) . ' per ticket' ?></span></li>
      </ul>

      <hr class="divider">

      <h3 style="margin-bottom:.75rem;">About This Event</h3>
      <div style="color:var(--txt2);line-height:1.8;font-size:.95rem;">
        <?= nl2br(htmlspecialchars($ev['description'] ?? 'No description available.')) ?>
      </div>

      <!-- Attendee count -->
      <div class="card" style="margin-top:2rem;padding:1.25rem;">
        <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
          <div style="text-align:center;">
            <div style="font-size:1.6rem;font-weight:800;color:var(--accent);"><?= $totalBooked ?></div>
            <div style="font-size:.78rem;color:var(--txt3);">Registered</div>
          </div>
          <div style="flex:1;min-width:120px;">
            <div style="background:var(--border);height:8px;border-radius:4px;overflow:hidden;margin-bottom:.35rem;">
              <div style="background:var(--accent);height:100%;border-radius:4px;width:<?= $ev['capacity'] > 0 ? min(100, round($totalBooked/$ev['capacity']*100)) : 0 ?>%;transition:width .5s ease;"></div>
            </div>
            <div style="font-size:.78rem;color:var(--txt3);"><?= $ev['capacity'] > 0 ? round($totalBooked/$ev['capacity']*100) : 0 ?>% filled &nbsp;·&nbsp; <?= $spotsLeft ?> left</div>
          </div>
          <div style="text-align:center;">
            <div style="font-size:1.6rem;font-weight:800;color:var(--txt);"><?= number_format($ev['capacity']) ?></div>
            <div style="font-size:.78rem;color:var(--txt3);">Total Capacity</div>
          </div>
        </div>
      </div>

      <div class="mt-3">
        <a href="<?= BASE_URL ?>" style="font-size:.85rem;color:var(--txt3);">← Back to Events</a>
      </div>
    </div>

    <!-- Right: Booking Card -->
    <div>
      <div class="card booking-card">
        <div class="card-header">
          <div style="font-size:1.4rem;font-weight:800;color:<?= $free ? 'var(--green)' : 'var(--accent)' ?>;">
            <?= $free ? '🎉 Free' : '₹' . number_format($ev['price'], 2) ?>
          </div>
          <?php if (!$free): ?>
            <div style="font-size:.8rem;color:var(--txt3);">per ticket</div>
          <?php endif; ?>
        </div>

        <div class="card-body">
          <?php if ($bookError): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($bookError) ?></div>
          <?php endif; ?>

          <?php if ($userBooked && $userBooking): ?>
            <div class="alert alert-success">✅ You've registered for this event!</div>
            <div style="font-size:.85rem;color:var(--txt2);margin-bottom:.5rem;">
              Tickets: <strong><?= $userBooking['tickets'] ?></strong> &nbsp;·&nbsp; 
              Booked: <?= date('M j, Y', strtotime($userBooking['booked_at'])) ?>
            </div>
            <div class="badge badge-green" style="margin-bottom:1rem;">✓ <?= ucfirst($userBooking['status']) ?></div>
            <a href="<?= BASE_URL ?>/user_dashboard.php" class="btn btn-ghost w-full">View My Bookings</a>

          <?php elseif ($ev['status'] === 'cancelled'): ?>
            <div class="alert alert-error">This event has been cancelled.</div>

          <?php elseif ($ev['status'] === 'completed'): ?>
            <div class="alert alert-warning">This event has already ended.</div>

          <?php elseif ($spotsLeft <= 0): ?>
            <div class="alert alert-error">⚡ This event is sold out.</div>

          <?php else: ?>
            <form method="POST">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="book" value="1">

              <div class="form-group">
                <label class="form-label">Number of Tickets</label>
                <select name="tickets" class="form-control">
                  <?php for ($t = 1; $t <= min(10, $spotsLeft); $t++): ?>
                    <option value="<?= $t ?>"><?= $t ?> Ticket<?= $t > 1 ? 's' : '' ?>
                      <?= !$free ? ' — ₹' . number_format($ev['price'] * $t, 2) : '' ?>
                    </option>
                  <?php endfor; ?>
                </select>
              </div>

              <?php if (!isUserLoggedIn()): ?>
                <div class="alert alert-info" style="font-size:.82rem;">
                  ℹ️ <a href="<?= BASE_URL ?>/user_login.php">Login</a> or 
                  <a href="<?= BASE_URL ?>/user_register.php">register</a> to book.
                </div>
              <?php endif; ?>

              <button type="submit" class="btn btn-primary w-full btn-lg"
                <?= !isUserLoggedIn() ? 'onclick="location.href=\''.BASE_URL.'/user_login.php\';return false;"' : '' ?>>
                <?= isUserLoggedIn() ? '🎟 Book Now' : '🔐 Login to Book' ?>
              </button>
            </form>
          <?php endif; ?>

          <div style="margin-top:1rem;font-size:.8rem;color:var(--txt3);text-align:center;">
            <?= $spotsLeft ?> of <?= $ev['capacity'] ?> spots remaining
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
