<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// ── Filters ──────────────────────────────────────────────────────
$search   = trim($_GET['q']    ?? '');
$cat      = (int)($_GET['cat'] ?? 0);
$status   = trim($_GET['status'] ?? 'upcoming');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 9;
$offset   = ($page - 1) * $perPage;

// ── Categories ────────────────────────────────────────────────────
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// ── Events Query ──────────────────────────────────────────────────
$where  = ["1=1"];
$params = [];

if ($search) {
    $where[]  = "(e.title LIKE ? OR e.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($cat > 0) {
    $where[]  = "e.category_id = ?";
    $params[] = $cat;
}
if ($status) {
    $where[]  = "e.status = ?";
    $params[] = $status;
}

$where = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) FROM events e WHERE $where");
$total->execute($params);
$totalCount = (int)$total->fetchColumn();
$totalPages = max(1, ceil($totalCount / $perPage));

$stmt = $pdo->prepare("
    SELECT e.*, c.name AS cat_name, c.color AS cat_color, c.icon AS cat_icon,
           (SELECT COUNT(*) FROM bookings b WHERE b.event_id = e.id AND b.status='confirmed') AS booked
    FROM events e
    LEFT JOIN categories c ON c.id = e.category_id
    WHERE $where
    ORDER BY e.event_date ASC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$events = $stmt->fetchAll();

$pageTitle = 'Browse Events';
include __DIR__ . '/includes/header.php';

function bannerColors(string $color): array {
    // Darken/lighten for gradient
    return ['from' => $color . 'aa', 'to' => '#0d0d14'];
}
?>

<!-- Hero -->
<section class="hero">
  <div class="container">
    <h1 class="hero-title">Find Your Next <span>Experience</span></h1>
    <p class="hero-sub">Discover and register for amazing events happening around you.</p>

    <form class="search-bar" method="GET" action="">
      <span style="display:flex;align-items:center;padding-left:.5rem;color:var(--txt3);">🔍</span>
      <input type="text" name="q" placeholder="Search events, locations…" value="<?= htmlspecialchars($search) ?>">

      <select name="cat">
        <option value="0">All Categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $cat == $c['id'] ? 'selected' : '' ?>>
            <?= $c['icon'] ?> <?= htmlspecialchars($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="status">
        <option value=""        <?= $status===''          ? 'selected':'' ?>>All Status</option>
        <option value="upcoming"<?= $status==='upcoming'  ? 'selected':'' ?>>Upcoming</option>
        <option value="ongoing" <?= $status==='ongoing'   ? 'selected':'' ?>>Ongoing</option>
        <option value="completed"<?= $status==='completed'? 'selected':'' ?>>Completed</option>
      </select>

      <button type="submit" class="btn btn-primary">Search</button>
      <?php if ($search || $cat || $status !== 'upcoming'): ?>
        <a href="<?= BASE_URL ?>" class="btn btn-ghost">Clear</a>
      <?php endif; ?>
    </form>
  </div>
</section>

<!-- Category Chips -->
<div class="container" style="padding-top:1.75rem;">
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.75rem;">
    <a href="?status=<?= urlencode($status) ?>" class="badge <?= $cat===0 ? 'badge-accent' : 'badge-gray' ?>" style="padding:.35rem .9rem;font-size:.82rem;text-decoration:none;">All</a>
    <?php foreach ($categories as $c): ?>
      <a href="?cat=<?= $c['id'] ?>&status=<?= urlencode($status) ?>"
         class="badge <?= $cat==$c['id'] ? 'badge-accent' : 'badge-gray' ?>"
         style="padding:.35rem .9rem;font-size:.82rem;text-decoration:none;">
        <?= $c['icon'] ?> <?= htmlspecialchars($c['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Results count -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
    <p style="font-size:.875rem;color:var(--txt3);">
      <?= $totalCount ?> event<?= $totalCount !== 1 ? 's' : '' ?> found
    </p>
  </div>

  <!-- Events Grid -->
  <?php if (empty($events)): ?>
    <div class="empty-state">
      <div class="empty-icon">🗓</div>
      <h3>No events found</h3>
      <p>Try adjusting your search or filters.</p>
      <a href="<?= BASE_URL ?>" class="btn btn-outline mt-2">Browse All Events</a>
    </div>
  <?php else: ?>
    <div class="events-grid">
      <?php foreach ($events as $ev):
        $color = $ev['cat_color'] ?? '#6366f1';
        $icon  = $ev['cat_icon']  ?? '🎪';
        $free  = (float)$ev['price'] === 0.0;
        $statusLabels = [
          'upcoming'  => ['badge-blue',  'Upcoming'],
          'ongoing'   => ['badge-green', 'Live 🔴'],
          'completed' => ['badge-gray',  'Ended'],
          'cancelled' => ['badge-red',   'Cancelled'],
        ];
        [$sBadge, $sLabel] = $statusLabels[$ev['status']] ?? ['badge-gray', $ev['status']];
        $spotsLeft = max(0, $ev['capacity'] - $ev['booked']);
      ?>
        <a href="<?= BASE_URL ?>/event_detail.php?id=<?= $ev['id'] ?>" class="event-card card-hover" style="text-decoration:none;">
          <div class="event-banner" style="--banner-from:<?= $color ?>88;--banner-to:#0a0a14;">
            <div class="event-banner-gradient"></div>
            <span style="position:relative;z-index:1;font-size:2.8rem;"><?= $icon ?></span>
          </div>

          <div class="event-card-body">
            <div class="event-meta">
              <span class="badge <?= $sBadge ?>"><?= $sLabel ?></span>
              <?php if ($ev['cat_name']): ?>
                <span class="badge badge-gray"><?= $ev['cat_icon'] ?> <?= htmlspecialchars($ev['cat_name']) ?></span>
              <?php endif; ?>
            </div>

            <div class="event-card-title"><?= htmlspecialchars($ev['title']) ?></div>

            <div style="display:flex;flex-direction:column;gap:.35rem;margin-top:.5rem;">
              <div class="event-meta-item">
                📅 <span><?= date('D, M j Y', strtotime($ev['event_date'])) ?> at <?= date('g:i A', strtotime($ev['event_time'])) ?></span>
              </div>
              <div class="event-meta-item">
                📍 <span><?= htmlspecialchars($ev['location']) ?></span>
              </div>
              <div class="event-meta-item">
                🎟 <span><?= $spotsLeft > 0 ? $spotsLeft . ' spots left' : '<span style="color:var(--red)">Sold out</span>' ?></span>
              </div>
            </div>

            <div class="event-card-footer">
              <span class="price-tag <?= $free ? 'free' : '' ?>">
                <?= $free ? '🎉 Free' : '₹' . number_format($ev['price'], 2) ?>
              </span>
              <span class="btn btn-primary btn-sm">View →</span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
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
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
