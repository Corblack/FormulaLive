<?php 
require_once 'db.php';
require_once 'includes/header.php'; 

$nationalities_list = $pdo->query("SELECT DISTINCT nationality FROM drivers ORDER BY nationality ASC")->fetchAll();

$name        = $_GET['search_name'] ?? '';
$nationality = $_GET['search_nationality'] ?? '';

$sql = "
    SELECT 
        d.*,
        COUNT(DISTINCT r.id)                                          AS total_races,
        SUM(CASE WHEN res.positionOrder = 1 THEN 1 ELSE 0 END)       AS wins,
        SUM(CASE WHEN res.positionOrder <= 3 THEN 1 ELSE 0 END)      AS podiums,
        SUM(CASE WHEN res.rank = 1 THEN 1 ELSE 0 END)                AS fastest_laps,
        ROUND(SUM(res.points), 0)                                     AS total_points,
        MIN(r.year)                                                   AS first_year,
        MAX(r.year)                                                   AS last_year,
        GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR '||') AS teams
    FROM drivers d
    LEFT JOIN results res ON d.id = res.driverId
    LEFT JOIN races r     ON res.raceId = r.id
    LEFT JOIN constructors c ON res.constructorId = c.id
    WHERE (d.forename LIKE :name OR d.surname LIKE :name)
";
$params = ['name' => "%$name%"];

if (!empty($nationality)) {
    $sql .= " AND d.nationality LIKE :nationality";
    $params['nationality'] = "%$nationality%";
}
$sql .= " GROUP BY d.id ORDER BY wins DESC, d.surname ASC LIMIT 60";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$drivers = $stmt->fetchAll();
?>

<div class="page-header container">
    <h1><span class="eyebrow">Database</span>Legendary Drivers</h1>
</div>

<div class="container">
<section class="card">
    <form method="GET" class="filter-form">
        <div class="filter-group">
            <label>Driver Name</label>
            <input type="text" name="search_name" placeholder="Search..." value="<?= htmlspecialchars($name) ?>">
        </div>
        <div class="filter-group">
            <label>Nationality</label>
            <input type="text" name="search_nationality" list="list-nationalities" placeholder="All..." value="<?= htmlspecialchars($nationality) ?>">
            <datalist id="list-nationalities">
                <?php foreach($nationalities_list as $n): ?>
                    <option value="<?= htmlspecialchars($n['nationality']) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>
        <button type="submit" class="btn-primary">Search</button>
        <a href="drivers.php" class="btn-reset">Reset</a>
    </form>

    <?php if (count($drivers) > 0): ?>
    <div class="drivers-grid">
        <?php foreach($drivers as $d):
            $teams = $d['teams'] ? explode('||', $d['teams']) : [];
            $age = $d['dob'] && $d['dob'] !== '0000-00-00'
                   ? (int)((time() - strtotime($d['dob'])) / 31557600) . ' yrs'
                   : '—';
            $career = ($d['first_year'] && $d['last_year'])
                   ? $d['first_year'] . ' – ' . $d['last_year']
                   : '—';
            $winRate = $d['total_races'] > 0
                   ? round(($d['wins'] / $d['total_races']) * 100, 1)
                   : 0;
        ?>
        <div class="driver-card" onclick="toggleDriver(this)">
            <!-- CARD HEADER -->
            <div class="driver-card-top">
                <div class="driver-card-meta">
                    <span class="driver-nat-badge"><?= htmlspecialchars($d['nationality']) ?></span>
                    <?php if ($d['wins'] > 0): ?>
                        <span class="driver-wins-badge">🏆 <?= $d['wins'] ?> W</span>
                    <?php endif; ?>
                </div>
                <div class="driver-card-name">
                    <span class="driver-surname"><?= htmlspecialchars($d['surname']) ?></span>
                    <span class="driver-forename"><?= htmlspecialchars($d['forename']) ?></span>
                </div>
                <div class="driver-card-quick">
                    <span><?= $d['total_races'] ?: '—' ?> races</span>
                    <span class="dot">·</span>
                    <span><?= $d['total_points'] ?: '0' ?> pts</span>
                </div>
                <div class="driver-expand-icon">＋</div>
            </div>

            <!-- EXPANDED SECTION -->
            <div class="driver-card-body">
                <div class="driver-stats-grid">
                    <div class="dstat">
                        <span class="dstat-val"><?= $d['wins'] ?: '0' ?></span>
                        <span class="dstat-label">Wins</span>
                    </div>
                    <div class="dstat">
                        <span class="dstat-val"><?= $d['podiums'] ?: '0' ?></span>
                        <span class="dstat-label">Podiums</span>
                    </div>
                    <div class="dstat">
                        <span class="dstat-val"><?= $d['fastest_laps'] ?: '0' ?></span>
                        <span class="dstat-label">Fastest Laps</span>
                    </div>
                    <div class="dstat">
                        <span class="dstat-val"><?= $d['total_races'] ?: '0' ?></span>
                        <span class="dstat-label">Races</span>
                    </div>
                    <div class="dstat">
                        <span class="dstat-val"><?= number_format((float)$d['total_points']) ?></span>
                        <span class="dstat-label">Points</span>
                    </div>
                    <div class="dstat">
                        <span class="dstat-val"><?= $winRate ?>%</span>
                        <span class="dstat-label">Win Rate</span>
                    </div>
                </div>

                <div class="driver-details-row">
                    <div class="driver-detail-item">
                        <span class="detail-label">Career</span>
                        <span class="detail-val"><?= $career ?></span>
                    </div>
                    <div class="driver-detail-item">
                        <span class="detail-label">Date of Birth</span>
                        <span class="detail-val"><?= ($d['dob'] && $d['dob'] !== '0000-00-00') ? date('d M Y', strtotime($d['dob'])) . ' (' . $age . ')' : '—' ?></span>
                    </div>
                </div>

                <?php if (!empty($teams)): ?>
                <div class="driver-teams">
                    <span class="detail-label">Teams</span>
                    <div class="teams-list">
                        <?php foreach(array_slice($teams, 0, 6) as $t): ?>
                            <span class="team-chip"><?= htmlspecialchars($t) ?></span>
                        <?php endforeach; ?>
                        <?php if (count($teams) > 6): ?>
                            <span class="team-chip team-chip-more">+<?= count($teams) - 6 ?> more</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Win rate bar -->
                <?php if ($d['total_races'] > 0): ?>
                <div class="win-bar-wrap">
                    <div class="win-bar-label">
                        <span>Podium rate</span>
                        <span><?= $d['total_races'] > 0 ? round(($d['podiums']/$d['total_races'])*100,1) : 0 ?>%</span>
                    </div>
                    <div class="win-bar-track">
                        <div class="win-bar-fill" style="width: <?= min(100, $d['total_races'] > 0 ? round(($d['podiums']/$d['total_races'])*100,1) : 0) ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <div class="empty-state"><p>No drivers found.</p></div>
    <?php endif; ?>
</section>
</div>

<script>
function toggleDriver(card) {
    const isOpen = card.classList.contains('open');
    // Close all
    document.querySelectorAll('.driver-card.open').forEach(c => c.classList.remove('open'));
    if (!isOpen) card.classList.add('open');
}
</script>

<?php require_once 'includes/footer.php'; ?>