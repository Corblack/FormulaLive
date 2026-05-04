<?php require_once 'includes/header.php'; ?>

<div class="page-header container">
    <h1><span class="eyebrow">Live Data</span>2026 Season</h1>
</div>

<div class="container">

<!-- DRIVER STANDINGS -->
<section class="card" id="standings-section">
    <div class="season-section-title">
        <span class="eyebrow-label">Championship</span>
        <h2>Driver Standings</h2>
    </div>
    <div id="driver-standings-wrap">
        <div class="loading-pulse">
            <div class="pulse-bar"></div><div class="pulse-bar"></div><div class="pulse-bar"></div>
        </div>
    </div>
</section>

<!-- CONSTRUCTOR STANDINGS -->
<section class="card" id="constructor-section">
    <div class="season-section-title">
        <span class="eyebrow-label">Championship</span>
        <h2>Constructor Standings</h2>
    </div>
    <div id="constructor-standings-wrap">
        <div class="loading-pulse">
            <div class="pulse-bar"></div><div class="pulse-bar"></div><div class="pulse-bar"></div>
        </div>
    </div>
</section>

<!-- RACE CALENDAR -->
<section class="card" id="calendar-section">
    <div class="season-section-title">
        <span class="eyebrow-label">2026 Calendar</span>
        <h2>Race Schedule</h2>
    </div>
    <div id="race-calendar-wrap">
        <div class="loading-pulse">
            <div class="pulse-bar"></div><div class="pulse-bar"></div><div class="pulse-bar"></div>
        </div>
    </div>
</section>

</div>

<script>
const BASE = 'https://api.jolpi.ca/ergast/f1/2026';
const today = new Date();

// ── DRIVER STANDINGS ──────────────────────────────────────────────────────────
fetch(`${BASE}/driverstandings.json`)
    .then(r => r.json())
    .then(data => {
        const wrap = document.getElementById('driver-standings-wrap');
        const standings = data?.MRData?.StandingsTable?.StandingsLists?.[0]?.DriverStandings;
        if (!standings || standings.length === 0) {
            wrap.innerHTML = '<div class="empty-state"><p>No standings available yet.</p></div>';
            return;
        }
        let html = '<div class="standings-list">';
        standings.slice(0, 20).forEach((s, i) => {
            const d = s.Driver;
            const c = s.Constructors?.[0];
            const isLeader = s.position === '1';
            html += `
            <div class="standing-row ${isLeader ? 'standing-leader' : ''}">
                <span class="standing-pos">${s.position}</span>
                <div class="standing-driver-info">
                    <span class="standing-name">${d.givenName} <strong>${d.familyName}</strong></span>
                    <span class="standing-team">${c?.name || '—'}</span>
                </div>
                <div class="standing-right">
                    <span class="standing-pts">${s.points} <span class="pts-label">pts</span></span>
                    <span class="standing-wins">${s.wins} W</span>
                </div>
            </div>`;
        });
        html += '</div>';
        wrap.innerHTML = html;
    })
    .catch(() => {
        document.getElementById('driver-standings-wrap').innerHTML =
            '<div class="empty-state"><p>Could not load standings.</p></div>';
    });

// ── CONSTRUCTOR STANDINGS ─────────────────────────────────────────────────────
fetch(`${BASE}/constructorstandings.json`)
    .then(r => r.json())
    .then(data => {
        const wrap = document.getElementById('constructor-standings-wrap');
        const standings = data?.MRData?.StandingsTable?.StandingsLists?.[0]?.ConstructorStandings;
        if (!standings || standings.length === 0) {
            wrap.innerHTML = '<div class="empty-state"><p>No standings available yet.</p></div>';
            return;
        }
        const max = parseFloat(standings[0].points) || 1;
        let html = '<div class="constructor-standings-list">';
        standings.slice(0, 10).forEach(s => {
            const c = s.Constructor;
            const pct = Math.min(100, Math.round((parseFloat(s.points) / max) * 100));
            html += `
            <div class="constructor-standing-row">
                <span class="standing-pos">${s.position}</span>
                <div class="constr-info">
                    <span class="constr-name">${c.name}</span>
                    <span class="standing-team">${c.nationality}</span>
                </div>
                <div class="constr-bar-wrap">
                    <div class="constr-bar" style="width:${pct}%"></div>
                </div>
                <span class="standing-pts">${s.points}</span>
            </div>`;
        });
        html += '</div>';
        wrap.innerHTML = html;
    })
    .catch(() => {
        document.getElementById('constructor-standings-wrap').innerHTML =
            '<div class="empty-state"><p>Could not load constructor standings.</p></div>';
    });

// ── RACE CALENDAR ─────────────────────────────────────────────────────────────
fetch(`${BASE}.json`)
    .then(r => r.json())
    .then(data => {
        const wrap = document.getElementById('race-calendar-wrap');
        const races = data?.MRData?.RaceTable?.Races;
        if (!races || races.length === 0) {
            wrap.innerHTML = '<div class="empty-state"><p>No races scheduled yet.</p></div>';
            return;
        }
        let html = '<div class="calendar-grid">';
        races.forEach(race => {
            const raceDate = new Date(race.date);
            const isPast   = raceDate < today;
            const isNext   = !isPast && races.find(r => new Date(r.date) >= today)?.round === race.round;
            const months   = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
            const dd       = String(raceDate.getDate()).padStart(2,'0');
            const mon      = months[raceDate.getMonth()];

            html += `
            <div class="calendar-race ${isPast ? 'race-past' : ''} ${isNext ? 'race-next' : ''}">
                <div class="race-round">R${race.round}</div>
                <div class="race-date-box">
                    <span class="race-day">${dd}</span>
                    <span class="race-month">${mon}</span>
                </div>
                <div class="race-info">
                    <span class="race-name">${race.raceName}</span>
                    <span class="race-circuit">${race.Circuit.circuitName}, ${race.Circuit.Location.country}</span>
                </div>
                ${isNext ? '<span class="race-next-badge">NEXT</span>' : ''}
                ${isPast ? '<span class="race-past-badge">DONE</span>' : ''}
            </div>`;
        });
        html += '</div>';
        wrap.innerHTML = html;
    })
    .catch(() => {
        document.getElementById('race-calendar-wrap').innerHTML =
            '<div class="empty-state"><p>Could not load race calendar.</p></div>';
    });
</script>

<?php require_once 'includes/footer.php'; ?>