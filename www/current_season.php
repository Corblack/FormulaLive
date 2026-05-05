<?php
require_once 'includes/api_cache.php';
require_once 'includes/header.php';

$driverStandings  = api_driver_standings();
$constrStandings  = api_constructor_standings();
$schedule         = api_schedule();
$allResults       = api_all_results();
$allSprints       = api_all_sprint_results();
$lastQualifying   = api_qualifying();
$today            = date('Y-m-d');

$resultsByRound = [];
foreach ($allResults as $r)  $resultsByRound[$r['round']] = $r;
$sprintsByRound = [];
foreach ($allSprints as $r)  $sprintsByRound[$r['round']] = $r;

$nextRound = null; $lastRound = null;
foreach ($schedule as $race) {
    if ($race['date'] >= $today && $nextRound === null) $nextRound = $race;
    if ($race['date'] < $today)  $lastRound = $race;
}

$leaderPts = isset($driverStandings[0]) ? (float)$driverStandings[0]['points'] : 0;

$lastRaceResult = null;
foreach (array_reverse($allResults) as $r) {
    if (!empty($r['Results'])) { $lastRaceResult = $r; break; }
}
$lastSprintResult = null;
foreach (array_reverse($allSprints) as $r) {
    if (!empty($r['SprintResults'])) { $lastSprintResult = $r; break; }
}

$months = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
?>

<div class="page-header container">
    <h1><span class="eyebrow">Live Data</span>2026 Season</h1>
    <?php if ($nextRound): ?>
    <p class="page-subtitle">Next: <strong><?= htmlspecialchars($nextRound['raceName']) ?></strong> — <?= (new DateTime($nextRound['date']))->format('d M Y') ?></p>
    <?php endif; ?>
</div>

<div class="container">

<!-- ═══ 1. NEXT RACE ═══════════════════════════════════════════════════════ -->
<?php if ($nextRound):
    $raceDate = new DateTime($nextRound['date']);
    $daysLeft = max(0, (int)(new DateTime())->diff($raceDate)->format('%r%a'));
    $sessions = [];
    if (!empty($nextRound['FirstPractice']))  $sessions['Free Practice 1']     = $nextRound['FirstPractice'];
    if (!empty($nextRound['SecondPractice'])) $sessions['FP2 / Sprint Quali']  = $nextRound['SecondPractice'];
    if (!empty($nextRound['ThirdPractice']))  $sessions['Free Practice 3']     = $nextRound['ThirdPractice'];
    if (!empty($nextRound['Sprint']))         $sessions['Sprint Race']         = $nextRound['Sprint'];
    if (!empty($nextRound['Qualifying']))     $sessions['Qualifying']          = $nextRound['Qualifying'];
    $sessions['Grand Prix'] = ['date' => $nextRound['date'], 'time' => $nextRound['time'] ?? null];
?>
<section class="card season-card" id="next-race">
    <div class="season-section-title">
        <span class="eyebrow-label">Round <?= $nextRound['round'] ?> — Up Next</span>
        <h2><?= htmlspecialchars($nextRound['raceName']) ?></h2>
    </div>
    <div class="next-race-grid">
        <div class="next-race-meta">
            <div class="meta-item"><span class="detail-label">Circuit</span><span class="detail-val"><?= htmlspecialchars($nextRound['Circuit']['circuitName']) ?></span></div>
            <div class="meta-item"><span class="detail-label">Location</span><span class="detail-val"><?= htmlspecialchars($nextRound['Circuit']['Location']['locality'] ?? '') ?>, <?= htmlspecialchars($nextRound['Circuit']['Location']['country']) ?></span></div>
            <div class="meta-item"><span class="detail-label">Race Date</span><span class="detail-val"><?= $raceDate->format('l d F Y') ?></span></div>
            <div class="countdown-inline">
                <span class="countdown-num"><?= $daysLeft ?></span>
                <span class="countdown-label">days to go</span>
            </div>
        </div>
        <div class="schedule-sessions">
            <?php foreach ($sessions as $label => $session):
                if (!$session) continue;
                $dt      = new DateTime($session['date'] . ' ' . ($session['time'] ?? '12:00:00'));
                $isPast  = $dt < new DateTime();
                $isRace  = $label === 'Grand Prix';
                $isSprint= str_contains($label, 'Sprint');
            ?>
            <div class="session-row <?= $isPast?'session-past':'' ?> <?= $isRace?'session-race':'' ?> <?= $isSprint?'session-sprint':'' ?>">
                <span class="session-name"><?= $label ?></span>
                <span class="session-date"><?= $dt->format('D d M') ?></span>
                <span class="session-time"><?= $dt->format('H:i') ?> UTC</span>
                <?php if ($isPast): ?><span class="race-past-badge">DONE</span>
                <?php elseif ($isRace): ?><span class="race-next-badge">RACE DAY</span>
                <?php elseif ($isSprint): ?><span class="sprint-badge">⚡ SPRINT</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ═══ 2. DRIVER STANDINGS ════════════════════════════════════════════════ -->
<?php if (!empty($driverStandings)): ?>
<section class="card season-card" id="standings">
    <div class="season-section-title">
        <span class="eyebrow-label">2026 Championship</span>
        <h2>Driver Standings</h2>
    </div>
    <div class="standings-list">
        <?php foreach ($driverStandings as $s):
            $d       = $s['Driver'];
            $c       = $s['Constructors'][0] ?? null;
            $isLeader= $s['position'] === '1';
            $gap     = $leaderPts - (float)$s['points'];
            $pct     = $leaderPts > 0 ? min(100, round(((float)$s['points'] / $leaderPts) * 100, 1)) : 0;
        ?>
        <div class="standing-row <?= $isLeader?'standing-leader':'' ?>">
            <span class="standing-pos"><?= $s['position'] ?></span>
            <div class="standing-driver-info">
                <span class="standing-name"><?= htmlspecialchars($d['givenName']) ?> <strong><?= htmlspecialchars($d['familyName']) ?></strong></span>
                <?php if ($c): ?><span class="standing-team"><?= htmlspecialchars($c['name']) ?></span><?php endif; ?>
                <div class="standing-bar-track"><div class="standing-bar-fill" style="width:<?= $pct ?>%"></div></div>
            </div>
            <div class="standing-right">
                <span class="standing-pts"><?= $s['points'] ?> <span class="pts-label">pts</span></span>
                <span class="standing-wins"><?= $s['wins'] ?> W</span>
                <?php if (!$isLeader && $gap > 0): ?><span class="gap-label">-<?= $gap ?></span><?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ═══ 3. CONSTRUCTOR STANDINGS ═══════════════════════════════════════════ -->
<?php if (!empty($constrStandings)):
    $maxPts = (float)($constrStandings[0]['points'] ?? 1);
?>
<section class="card season-card" id="constructors">
    <div class="season-section-title">
        <span class="eyebrow-label">2026 Championship</span>
        <h2>Constructor Standings</h2>
    </div>
    <div class="constructor-standings-list">
        <?php foreach ($constrStandings as $s):
            $c   = $s['Constructor'];
            $pct = $maxPts > 0 ? min(100, round(((float)$s['points'] / $maxPts) * 100, 1)) : 0;
            $gap = $maxPts - (float)$s['points'];
        ?>
        <div class="constructor-standing-row">
            <span class="standing-pos"><?= $s['position'] ?></span>
            <div class="constr-info">
                <span class="constr-name"><?= htmlspecialchars($c['name']) ?></span>
                <span class="standing-team"><?= htmlspecialchars($c['nationality']) ?></span>
            </div>
            <div class="constr-bar-wrap"><div class="constr-bar" style="width:<?= $pct ?>%"></div></div>
            <span class="standing-pts"><?= $s['points'] ?> <span class="pts-label">pts</span></span>
            <?php if ($s['position'] !== '1' && $gap > 0): ?><span class="gap-label">-<?= $gap ?></span><?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ═══ 4. LAST RACE RESULTS ════════════════════════════════════════════════ -->
<?php if ($lastRaceResult): ?>
<section class="card season-card" id="last-race">
    <div class="season-section-title">
        <span class="eyebrow-label">Round <?= $lastRaceResult['round'] ?> — Race Result</span>
        <h2><?= htmlspecialchars($lastRaceResult['raceName']) ?> <span class="title-year"><?= $lastRaceResult['season'] ?></span></h2>
        <p class="season-subtitle"><?= htmlspecialchars($lastRaceResult['Circuit']['circuitName']) ?> · <?= (new DateTime($lastRaceResult['date']))->format('d M Y') ?></p>
    </div>
    <div class="race-results-table">
        <div class="rr-head"><span>Pos</span><span>Driver</span><span>Team</span><span>Grid</span><span>Laps</span><span>Time / Status</span><span>Pts</span></div>
        <?php foreach ($lastRaceResult['Results'] as $r):
            $isPodium = (int)$r['position'] <= 3;
            $isWinner = $r['position'] === '1';
        ?>
        <div class="rr-row <?= $isWinner?'rr-winner':($isPodium?'rr-podium':'') ?>">
            <span class="rr-pos"><?= $r['position'] ?></span>
            <span class="rr-driver"><?php if (!empty($r['Driver']['code'])): ?><span class="driver-code"><?= $r['Driver']['code'] ?></span><?php endif; ?><?= htmlspecialchars($r['Driver']['givenName'].' '.$r['Driver']['familyName']) ?></span>
            <span class="rr-team"><?= htmlspecialchars($r['Constructor']['name']) ?></span>
            <span class="rr-grid"><?= $r['grid']=='0'?'PL':$r['grid'] ?></span>
            <span class="rr-laps"><?= $r['laps'] ?></span>
            <span class="rr-time"><?= htmlspecialchars($r['Time']['time'] ?? $r['status']) ?></span>
            <span class="rr-pts"><?= $r['points']>0?'+'.$r['points']:'—' ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
    $fl = null;
    foreach ($lastRaceResult['Results'] as $r) {
        if (($r['FastestLap']['rank'] ?? '') === '1') { $fl = $r; break; }
    }
    if ($fl): ?>
    <div class="race-footnote">
        <span class="detail-label">⚡ Fastest Lap</span>
        <span><?= htmlspecialchars($fl['Driver']['familyName']) ?> — <?= $fl['FastestLap']['Time']['time'] ?? '—' ?> (Lap <?= $fl['FastestLap']['lap'] ?? '—' ?>)</span>
    </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<!-- ═══ 5. SPRINT RESULTS ══════════════════════════════════════════════════ -->
<section class="card season-card" id="sprint">
    <div class="season-section-title">
        <span class="eyebrow-label">⚡ Sprint Race</span>
        <h2>Sprint Results<?php if ($lastSprintResult): ?> — <?= htmlspecialchars($lastSprintResult['raceName']) ?><?php endif; ?></h2>
        <?php if ($lastSprintResult): ?>
        <p class="season-subtitle"><?= htmlspecialchars($lastSprintResult['Circuit']['circuitName'] ?? '') ?> · Round <?= $lastSprintResult['round'] ?></p>
        <?php endif; ?>
    </div>
    <?php if ($lastSprintResult && !empty($lastSprintResult['SprintResults'])): ?>
    <div class="race-results-table sprint-table">
        <div class="rr-head"><span>Pos</span><span>Driver</span><span>Team</span><span>Grid</span><span>Laps</span><span>Time / Status</span><span>Pts</span></div>
        <?php foreach ($lastSprintResult['SprintResults'] as $r):
            $isPodium = (int)$r['position'] <= 3;
            $isWinner = $r['position'] === '1';
        ?>
        <div class="rr-row <?= $isWinner?'rr-winner':($isPodium?'rr-podium':'') ?>">
            <span class="rr-pos"><?= $r['position'] ?></span>
            <span class="rr-driver"><?php if (!empty($r['Driver']['code'])): ?><span class="driver-code"><?= $r['Driver']['code'] ?></span><?php endif; ?><?= htmlspecialchars($r['Driver']['givenName'].' '.$r['Driver']['familyName']) ?></span>
            <span class="rr-team"><?= htmlspecialchars($r['Constructor']['name']) ?></span>
            <span class="rr-grid"><?= $r['grid']=='0'?'PL':$r['grid'] ?></span>
            <span class="rr-laps"><?= $r['laps'] ?></span>
            <span class="rr-time"><?= htmlspecialchars($r['Time']['time'] ?? $r['status']) ?></span>
            <span class="rr-pts"><?= $r['points']>0?'+'.$r['points']:'—' ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state"><p>No sprint results yet for 2026. Sprint Qualifying results will appear here once the Jolpica API exposes the endpoint (coming soon).</p></div>
    <?php endif; ?>
</section>

<!-- ═══ 6. QUALIFYING ══════════════════════════════════════════════════════ -->
<?php if (!empty($lastQualifying['QualifyingResults'])): ?>
<section class="card season-card" id="qualifying">
    <div class="season-section-title">
        <span class="eyebrow-label">Qualifying — Round <?= $lastQualifying['round'] ?></span>
        <h2><?= htmlspecialchars($lastQualifying['raceName']) ?> Grid</h2>
        <p class="season-subtitle"><?= htmlspecialchars($lastQualifying['Circuit']['circuitName']) ?></p>
    </div>
    <div class="race-results-table quali-table">
        <div class="rr-head"><span>Pos</span><span>Driver</span><span>Team</span><span>Q1</span><span>Q2</span><span>Q3</span></div>
        <?php foreach ($lastQualifying['QualifyingResults'] as $r):
            $isP1   = $r['position'] === '1';
            $isFront= (int)$r['position'] <= 3;
        ?>
        <div class="rr-row <?= $isP1?'rr-winner':($isFront?'rr-podium':'') ?>">
            <span class="rr-pos"><?= $r['position'] ?></span>
            <span class="rr-driver"><?php if (!empty($r['Driver']['code'])): ?><span class="driver-code"><?= $r['Driver']['code'] ?></span><?php endif; ?><?= htmlspecialchars($r['Driver']['givenName'].' '.$r['Driver']['familyName']) ?></span>
            <span class="rr-team"><?= htmlspecialchars($r['Constructor']['name']) ?></span>
            <span class="rr-time"><?= $r['Q1'] ?? '—' ?></span>
            <span class="rr-time"><?= $r['Q2'] ?? '—' ?></span>
            <span class="rr-time rr-q3"><?= $r['Q3'] ?? '—' ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ═══ 7. RACE BY RACE ════════════════════════════════════════════════════ -->
<section class="card season-card" id="race-by-race">
    <div class="season-section-title">
        <span class="eyebrow-label">Season Overview</span>
        <h2>Race by Race</h2>
    </div>
    <div class="rbr-grid">
        <?php foreach ($schedule as $race):
            $isPast    = $race['date'] < $today;
            $isNext    = $nextRound && $race['round'] === $nextRound['round'];
            $hasSprint = !empty($race['Sprint']);
            $raceRes   = $resultsByRound[$race['round']] ?? null;
            $winner    = null;
            if ($raceRes) foreach ($raceRes['Results'] as $r) { if ($r['position']==='1') { $winner=$r; break; } }
            $sprintWin = null;
            if (isset($sprintsByRound[$race['round']])) {
                foreach ($sprintsByRound[$race['round']]['SprintResults'] as $r) { if ($r['position']==='1') { $sprintWin=$r; break; } }
            }
            $dt = new DateTime($race['date']);
        ?>
        <div class="rbr-card <?= $isPast?'rbr-past':'' ?> <?= $isNext?'rbr-next':'' ?>">
            <div class="rbr-round">R<?= $race['round'] ?></div>
            <div class="rbr-info">
                <span class="rbr-name"><?= htmlspecialchars($race['raceName']) ?></span>
                <span class="rbr-circuit"><?= htmlspecialchars($race['Circuit']['Location']['country']) ?> · <?= $dt->format('d M') ?></span>
                <?php if ($winner): ?><span class="rbr-winner">🏆 <?= htmlspecialchars($winner['Driver']['familyName']) ?> — <?= htmlspecialchars($winner['Constructor']['name']) ?></span><?php endif; ?>
                <?php if ($sprintWin): ?><span class="rbr-sprint-winner">⚡ <?= htmlspecialchars($sprintWin['Driver']['familyName']) ?></span><?php endif; ?>
            </div>
            <div class="rbr-badges">
                <?php if ($hasSprint): ?><span class="sprint-badge-small">⚡ SPRINT</span><?php endif; ?>
                <?php if ($isNext): ?><span class="race-next-badge">NEXT</span>
                <?php elseif ($isPast && !$winner): ?><span class="race-past-badge">PENDING</span>
                <?php elseif ($isPast): ?><span class="race-past-badge">DONE</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ═══ 8. FULL CALENDAR ══════════════════════════════════════════════════ -->
<section class="card season-card" id="calendar">
    <div class="season-section-title">
        <span class="eyebrow-label">2026 Full Calendar</span>
        <h2>All Sessions</h2>
    </div>
    <div class="calendar-grid">
        <?php foreach ($schedule as $race):
            $raceDate  = new DateTime($race['date']);
            $isPast    = $race['date'] < $today;
            $isNext    = $nextRound && $race['round'] === $nextRound['round'];
            $hasSprint = !empty($race['Sprint']);
            $dd  = $raceDate->format('d');
            $mon = $months[(int)$raceDate->format('n') - 1];
        ?>
        <div class="calendar-race <?= $isPast?'race-past':'' ?> <?= $isNext?'race-next':'' ?>">
            <span class="race-round">R<?= $race['round'] ?></span>
            <div class="race-date-box"><span class="race-day"><?= $dd ?></span><span class="race-month"><?= $mon ?></span></div>
            <div class="race-info">
                <span class="race-name"><?= htmlspecialchars($race['raceName']) ?></span>
                <span class="race-circuit"><?= htmlspecialchars($race['Circuit']['circuitName']) ?>, <?= htmlspecialchars($race['Circuit']['Location']['country']) ?></span>
            </div>
            <?php if ($hasSprint): ?><span class="sprint-badge-small">⚡</span><?php endif; ?>
            <?php if ($isNext): ?><span class="race-next-badge">NEXT</span>
            <?php elseif ($isPast): ?><span class="race-past-badge">DONE</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>

</div>

<?php require_once 'includes/footer.php'; ?>
