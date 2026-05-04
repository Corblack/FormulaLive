<?php 
require_once 'db.php';
require_once 'includes/header.php'; 

$countries_list     = $pdo->query("SELECT DISTINCT country FROM circuits ORDER BY country ASC")->fetchAll();
$circuit_names_list = $pdo->query("SELECT name FROM circuits ORDER BY name ASC")->fetchAll();

$name    = $_GET['search_name'] ?? '';
$country = $_GET['search_country'] ?? '';

$sql = "
    SELECT 
        ci.*,
        COUNT(DISTINCT ra.id)          AS total_races,
        MIN(ra.year)                   AS first_year,
        MAX(ra.year)                   AS last_year,
        (SELECT CONCAT(d.forename, ' ', d.surname)
            FROM results res2
            JOIN drivers d ON res2.driverId = d.id
            JOIN races ra2 ON res2.raceId = ra2.id
            WHERE ra2.circuitId = ci.id AND res2.positionOrder = 1
            ORDER BY ra2.date DESC LIMIT 1
        ) AS last_winner,
        (SELECT ra2.year
            FROM races ra2
            JOIN results res2 ON res2.raceId = ra2.id
            WHERE ra2.circuitId = ci.id AND res2.positionOrder = 1
            ORDER BY ra2.date DESC LIMIT 1
        ) AS last_winner_year,
        (SELECT COUNT(DISTINCT res2.driverId)
            FROM results res2
            JOIN races ra2 ON res2.raceId = ra2.id
            WHERE ra2.circuitId = ci.id AND res2.positionOrder = 1
        ) AS unique_winners
    FROM circuits ci
    LEFT JOIN races ra ON ra.circuitId = ci.id
    WHERE 1=1
";
$params = [];

if (!empty($name)) {
    $sql .= " AND ci.name LIKE :name";
    $params['name'] = "%$name%";
}
if (!empty($country)) {
    $sql .= " AND ci.country LIKE :country";
    $params['country'] = "%$country%";
}
$sql .= " GROUP BY ci.id ORDER BY total_races DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$circuits = $stmt->fetchAll();
?>

<div class="page-header container">
    <h1><span class="eyebrow">Database</span>Historical Circuits</h1>
</div>

<div class="container">
<section class="card">
    <form method="GET" class="filter-form">
        <div class="filter-group">
            <label>Circuit Name</label>
            <input type="text" name="search_name" list="list-names" placeholder="Search..." value="<?= htmlspecialchars($name) ?>">
            <datalist id="list-names">
                <?php foreach($circuit_names_list as $c): ?>
                    <option value="<?= htmlspecialchars($c['name']) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>
        <div class="filter-group">
            <label>Country</label>
            <input type="text" name="search_country" list="list-countries" placeholder="All..." value="<?= htmlspecialchars($country) ?>">
            <datalist id="list-countries">
                <?php foreach($countries_list as $co): ?>
                    <option value="<?= htmlspecialchars($co['country']) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>
        <button type="submit" class="btn-primary">Filter</button>
        <a href="circuits.php" class="btn-reset">Reset</a>
    </form>

    <?php if (count($circuits) > 0): ?>
    <div class="circuits-grid">
        <?php foreach($circuits as $c):
            $career = ($c['first_year'] && $c['last_year']) ? $c['first_year'] . ' – ' . $c['last_year'] : '—';
        ?>
        <div class="circuit-card" onclick="toggleCircuit(this)">
            <div class="circuit-card-top">
                <div class="circuit-flag-wrap">
                    <span class="driver-nat-badge"><?= htmlspecialchars($c['country']) ?></span>
                    <span class="circuit-race-count"><?= $c['total_races'] ?: '0' ?> races</span>
                </div>
                <div class="circuit-name"><?= htmlspecialchars($c['name']) ?></div>
                <div class="circuit-location-line">
                    <span class="circuit-location-icon">📍</span>
                    <?= htmlspecialchars($c['location']) ?>, <?= htmlspecialchars($c['country']) ?>
                </div>
                <div class="driver-expand-icon">＋</div>
            </div>

            <div class="driver-card-body">
                <div class="driver-stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                    <div class="dstat">
                        <span class="dstat-val"><?= $c['total_races'] ?: '0' ?></span>
                        <span class="dstat-label">Races Held</span>
                    </div>
                    <div class="dstat">
                        <span class="dstat-val"><?= $c['unique_winners'] ?: '—' ?></span>
                        <span class="dstat-label">Unique Winners</span>
                    </div>
                    <div class="dstat">
                        <span class="dstat-val"><?= $career ?></span>
                        <span class="dstat-label">Active Period</span>
                    </div>
                </div>

                <?php if ($c['last_winner']): ?>
                <div class="circuit-last-winner">
                    <span class="detail-label">Last Winner (<?= $c['last_winner_year'] ?>)</span>
                    <span class="circuit-winner-name"><?= htmlspecialchars($c['last_winner']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <div class="empty-state"><p>No circuits found.</p></div>
    <?php endif; ?>
</section>
</div>

<script>
function toggleCircuit(card) {
    const isOpen = card.classList.contains('open');
    document.querySelectorAll('.circuit-card.open').forEach(c => c.classList.remove('open'));
    if (!isOpen) card.classList.add('open');
}
</script>

<?php require_once 'includes/footer.php'; ?>