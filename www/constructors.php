<?php 
require_once 'db.php';
require_once 'includes/header.php'; 

$name = $_GET['search_name'] ?? '';

$sql = "
    SELECT 
        c.*,
        COUNT(DISTINCT res.driverId)                                  AS total_drivers,
        COUNT(DISTINCT res.raceId)                                    AS total_races,
        SUM(CASE WHEN res.positionOrder = 1 THEN 1 ELSE 0 END)       AS wins,
        SUM(CASE WHEN res.positionOrder <= 3 THEN 1 ELSE 0 END)      AS podiums,
        SUM(CASE WHEN res.rank = 1 THEN 1 ELSE 0 END)                AS fastest_laps,
        ROUND(SUM(res.points), 0)                                     AS total_points,
        MIN(r.year)                                                   AS first_year,
        MAX(r.year)                                                   AS last_year
    FROM constructors c
    LEFT JOIN results res ON c.id = res.constructorId
    LEFT JOIN races r     ON res.raceId = r.id
    WHERE c.name LIKE :name
    GROUP BY c.id
    ORDER BY wins DESC, c.name ASC
    LIMIT 60
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['name' => "%$name%"]);
$constructors = $stmt->fetchAll();
?>

<div class="page-header container">
    <h1><span class="eyebrow">Database</span>Constructors</h1>
</div>

<div class="container">
<section class="card">
    <form method="GET" class="filter-form">
        <div class="filter-group">
            <label>Constructor Name</label>
            <input type="text" name="search_name" placeholder="Search..." value="<?= htmlspecialchars($name) ?>">
        </div>
        <button type="submit" class="btn-primary">Search</button>
        <a href="constructors.php" class="btn-reset">Reset</a>
    </form>

    <?php if (count($constructors) > 0): ?>
    <div class="constructors-grid">
        <?php foreach($constructors as $c):
            $career = ($c['first_year'] && $c['last_year']) ? $c['first_year'] . ' – ' . $c['last_year'] : '—';
            $winRate = $c['total_races'] > 0 ? round(($c['wins'] / $c['total_races']) * 100, 1) : 0;
        ?>
        <div class="constructor-card" onclick="toggleCard(this)">
            <div class="constructor-card-top">
                <div class="constructor-card-meta">
                    <span class="driver-nat-badge"><?= htmlspecialchars($c['nationality']) ?></span>
                    <?php if ($c['wins'] > 0): ?>
                        <span class="driver-wins-badge">🏆 <?= $c['wins'] ?> W</span>
                    <?php endif; ?>
                </div>
                <div class="constructor-name"><?= htmlspecialchars($c['name']) ?></div>
                <div class="driver-card-quick">
                    <span><?= $c['total_races'] ?: '—' ?> races</span>
                    <span class="dot">·</span>
                    <span><?= $c['total_points'] ?: '0' ?> pts</span>
                    <span class="dot">·</span>
                    <span><?= $career ?></span>
                </div>
                <div class="driver-expand-icon">＋</div>
            </div>

            <div class="driver-card-body">
                <div class="driver-stats-grid">
                    <div class="dstat">
                        <span class="dstat-val"><?= $c['wins'] ?: '0' ?></span>
                        <span class="dstat-label">Wins</span>
                    </div>
                    <div class="dstat">
                        <span class="dstat-val"><?= $c['podiums'] ?: '0' ?></span>
                        <span class="dstat-label">Podiums</span>
                    </div>
                    <div class="dstat">
                        <span class="dstat-val"><?= $c['fastest_laps'] ?: '0' ?></span>
                        <span class="dstat-label">Fastest Laps</span>
                    </div>
                    <div class="dstat">
                        <span class="dstat-val"><?= $c['total_drivers'] ?: '0' ?></span>
                        <span class="dstat-label">Drivers</span>
                    </div>
                    <div class="dstat">
                        <span class="dstat-val"><?= number_format((float)$c['total_points']) ?></span>
                        <span class="dstat-label">Points</span>
                    </div>
                    <div class="dstat">
                        <span class="dstat-val"><?= $winRate ?>%</span>
                        <span class="dstat-label">Win Rate</span>
                    </div>
                </div>

                <div class="driver-details-row">
                    <div class="driver-detail-item">
                        <span class="detail-label">Career Span</span>
                        <span class="detail-val"><?= $career ?></span>
                    </div>
                    <div class="driver-detail-item">
                        <span class="detail-label">Nationality</span>
                        <span class="detail-val"><?= htmlspecialchars($c['nationality']) ?></span>
                    </div>
                </div>

                <?php if ($c['total_races'] > 0): ?>
                <div class="win-bar-wrap">
                    <div class="win-bar-label">
                        <span>Podium rate</span>
                        <span><?= round(($c['podiums'] / $c['total_races']) * 100, 1) ?>%</span>
                    </div>
                    <div class="win-bar-track">
                        <div class="win-bar-fill" style="width: <?= min(100, round(($c['podiums'] / $c['total_races']) * 100, 1)) ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <div class="empty-state"><p>No constructors found.</p></div>
    <?php endif; ?>
</section>
</div>

<script>
function toggleCard(card) {
    const isOpen = card.classList.contains('open');
    document.querySelectorAll('.constructor-card.open').forEach(c => c.classList.remove('open'));
    if (!isOpen) card.classList.add('open');
}
</script>

<?php require_once 'includes/footer.php'; ?>