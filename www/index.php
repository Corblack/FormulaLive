<?php 
require_once 'db.php';
require_once 'includes/api_cache.php';

// ── Stats BDD ──────────────────────────────────────────────────────────────
try {
    $countDrivers      = $pdo->query("SELECT COUNT(*) FROM drivers")->fetchColumn();
    $countCircuits     = $pdo->query("SELECT COUNT(*) FROM circuits")->fetchColumn();
    $countConstructors = $pdo->query("SELECT COUNT(*) FROM constructors")->fetchColumn();
} catch (Exception $e) {
    $countDrivers = "800+"; $countCircuits = "77"; $countConstructors = "210+";
}

// ── Données API live ───────────────────────────────────────────────────────
$nextRace         = api_next_race();
$driverStandings  = array_slice(api_driver_standings(), 0, 5);
$lastRace         = api_last_race_results();
$lastSprint       = api_sprint_results();

require_once 'includes/header.php'; 
?>

</div><!-- ferme le .container ouvert dans header -->

<!-- ── HERO ──────────────────────────────────────────────────────────────── -->
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

<!-- ── STATS BAR ──────────────────────────────────────────────────────────── -->
<div class="stats-bar">
    <div class="stat-item"><span class="stat-number"><?= $countDrivers ?></span><span class="stat-label">Drivers</span></div>
    <div class="stat-item"><span class="stat-number"><?= $countCircuits ?></span><span class="stat-label">Circuits</span></div>
    <div class="stat-item"><span class="stat-number"><?= $countConstructors ?></span><span class="stat-label">Constructors</span></div>
    <div class="stat-item"><span class="stat-number">1950</span><span class="stat-label">Since</span></div>
</div>

<div class="container">

<!-- ── NEXT RACE BANNER ────────────────────────────────────────────────────── -->
<?php if ($nextRace): 
    $raceDate = new DateTime($nextRace['date']);
    $today    = new DateTime();
    $diff     = $today->diff($raceDate);
    $daysLeft = (int)$diff->format('%r%a');
?>
<section class="next-race-banner">
    <div class="next-race-left">
        <span class="eyebrow-label">Next Race — Round <?= $nextRace['round'] ?></span>
        <h2 class="next-race-name"><?= htmlspecialchars($nextRace['raceName']) ?></h2>
        <span class="next-race-circuit"><?= htmlspecialchars($nextRace['Circuit']['circuitName']) ?>, <?= htmlspecialchars($nextRace['Circuit']['Location']['country']) ?></span>
    </div>
    <div class="next-race-right">
        <div class="countdown-box">
            <span class="countdown-num"><?= max(0, $daysLeft) ?></span>
            <span class="countdown-label">days to go</span>
        </div>
        <div class="next-race-date-block">
            <span class="next-race-date"><?= $raceDate->format('d M Y') ?></span>
            <?php 
            $sessions = [];
            if (!empty($nextRace['FirstPractice']))  $sessions[] = ['FP1', $nextRace['FirstPractice']];
            if (!empty($nextRace['SecondPractice'])) $sessions[] = ['FP2 / Sprint Quali', $nextRace['SecondPractice']];
            if (!empty($nextRace['ThirdPractice']))  $sessions[] = ['FP3', $nextRace['ThirdPractice']];
            if (!empty($nextRace['Sprint']))         $sessions[] = ['SPRINT', $nextRace['Sprint']];
            if (!empty($nextRace['Qualifying']))     $sessions[] = ['QUALIFYING', $nextRace['Qualifying']];
            foreach ($sessions as [$label, $session]):
                $dt = new DateTime($session['date'] . ' ' . ($session['time'] ?? '12:00:00'));
            ?>
            <span class="session-line"><em><?= $label ?></em> <?= $dt->format('D d M') ?> <?= rtrim($dt->format('H:i'), ':00') ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <a href="current_season.php" class="btn-primary">Full 2026 Season →</a>
</section>
<?php endif; ?>

<!-- ── LAST RACE WINNER ────────────────────────────────────────────────────── -->
<?php 
$winner = null;
if (!empty($lastRace['Results'])) {
    foreach ($lastRace['Results'] as $r) {
        if ($r['position'] === '1') { $winner = $r; break; }
    }
}
if ($winner): ?>
<section class="winner-card">
    <div class="winner-info">
        <span class="label">Last Race Winner — <?= htmlspecialchars($lastRace['raceName']) ?> <?= $lastRace['season'] ?></span>
        <h3><?= htmlspecialchars($winner['Driver']['givenName'] . ' ' . $winner['Driver']['familyName']) ?></h3>
        <p><?= htmlspecialchars($winner['Constructor']['name']) ?> · <?= $winner['laps'] ?> laps · <?= $winner['Time']['time'] ?? $winner['status'] ?></p>
    </div>
    <a href="current_season.php#last-race" class="btn-primary">Race Results →</a>
</section>
<?php endif; ?>

<!-- ── SPRINT WINNER (si dispo) ───────────────────────────────────────────── -->
<?php 
$sprintWinner = null;
if (!empty($lastSprint['SprintResults'])) {
    foreach ($lastSprint['SprintResults'] as $r) {
        if ($r['position'] === '1') { $sprintWinner = $r; break; }
    }
}
if ($sprintWinner): ?>
<section class="winner-card winner-card-sprint">
    <div class="winner-info">
        <span class="label">⚡ Sprint Winner — <?= htmlspecialchars($lastSprint['raceName']) ?></span>
        <h3><?= htmlspecialchars($sprintWinner['Driver']['givenName'] . ' ' . $sprintWinner['Driver']['familyName']) ?></h3>
        <p><?= htmlspecialchars($sprintWinner['Constructor']['name']) ?> · <?= $sprintWinner['laps'] ?> laps · <?= $sprintWinner['Time']['time'] ?? $sprintWinner['status'] ?></p>
    </div>
    <a href="current_season.php#sprint" class="btn-primary">Sprint Results →</a>
</section>
<?php endif; ?>

<!-- ── MINI STANDINGS ─────────────────────────────────────────────────────── -->
<?php if (!empty($driverStandings)): ?>
<section class="card home-standings">
    <div class="home-standings-header">
        <div>
            <span class="eyebrow-label">2026 Championship</span>
            <h2 class="section-title">Driver Standings</h2>
        </div>
        <a href="current_season.php#standings" class="btn-reset">Full standings →</a>
    </div>
    <div class="standings-list">
        <?php foreach ($driverStandings as $s):
            $d = $s['Driver'];
            $c = $s['Constructors'][0] ?? null;
            $isLeader = $s['position'] === '1';
        ?>
        <div class="standing-row <?= $isLeader ? 'standing-leader' : '' ?>">
            <span class="standing-pos"><?= $s['position'] ?></span>
            <div class="standing-driver-info">
                <span class="standing-name"><?= htmlspecialchars($d['givenName']) ?> <strong><?= htmlspecialchars($d['familyName']) ?></strong></span>
                <?php if ($c): ?><span class="standing-team"><?= htmlspecialchars($c['name']) ?></span><?php endif; ?>
            </div>
            <div class="standing-right">
                <span class="standing-pts"><?= $s['points'] ?> <span class="pts-label">pts</span></span>
                <span class="standing-wins"><?= $s['wins'] ?> W</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
