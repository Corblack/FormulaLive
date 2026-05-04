<?php 
require_once 'db.php'; 

try {
    $countDrivers = $pdo->query("SELECT COUNT(*) FROM drivers")->fetchColumn();
    $countCircuits = $pdo->query("SELECT COUNT(*) FROM circuits")->fetchColumn();
    $countConstructors = $pdo->query("SELECT COUNT(*) FROM constructors")->fetchColumn();
    
    $lastWinnerQuery = $pdo->query("
        SELECT d.forename, d.surname, r.name as race_name, r.year 
        FROM results res
        JOIN drivers d ON res.driverId = d.id
        JOIN races r ON res.raceId = r.id
        WHERE res.positionOrder = 1
        ORDER BY r.date DESC
        LIMIT 1
    ");
    $lastWinner = $lastWinnerQuery->fetch();
} catch (Exception $e) {
    $countDrivers = "800+"; $countCircuits = "77"; $countConstructors = "210+";
}

require_once 'includes/header.php'; 
?>


</div>

<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-content container">
        <p class="hero-eyebrow">70 years of competition</p>
        <h1>History<br>of F1<em>In your hands</em></h1>
        <p>More than <strong><?= $countDrivers ?> drivers</strong>, <strong><?= $countCircuits ?> circuits</strong> and decades of statistics accessible for free.</p>
        <div class="hero-actions">
            <a href="drivers.php" class="btn-primary">Explore drivers</a>
            <a href="current_season.php" class="btn-secondary">2026 Season</a>
        </div>
    </div>
</section>

<!-- Full-width stats bar -->
<div class="stats-bar">
    <div class="stat-item">
        <span class="stat-number"><?= $countDrivers ?></span>
        <span class="stat-label">Drivers</span>
    </div>
    <div class="stat-item">
        <span class="stat-number"><?= $countCircuits ?></span>
        <span class="stat-label">Circuits</span>
    </div>
    <div class="stat-item">
        <span class="stat-number"><?= $countConstructors ?></span>
        <span class="stat-label">Constructors</span>
    </div>
    <div class="stat-item">
        <span class="stat-number">1950</span>
        <span class="stat-label">Since</span>
    </div>
</div>

<div class="container">

<?php if ($lastWinner): ?>
<section class="winner-card">
    <div class="winner-info">
        <span class="label">Last Winner — Database</span>
        <h3><?= htmlspecialchars($lastWinner['forename'] . ' ' . $lastWinner['surname']) ?></h3>
        <p><?= htmlspecialchars($lastWinner['race_name']) ?> &mdash; <?= $lastWinner['year'] ?></p>
    </div>
    <a href="drivers.php" class="btn-primary">View all drivers</a>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>