<?php
/**
 * api_cache.php — Jolpica API helper avec cache fichier
 * Inclure avec : require_once 'includes/api_cache.php';
 * Utiliser avec : $data = jolpica_get('2026/driverstandings');
 */

define('CACHE_DIR', __DIR__ . '/../cache/');
define('CACHE_TTL', 900); // 15 minutes
define('JOLPICA_BASE', 'https://api.jolpi.ca/ergast/f1/');

if (!is_dir(CACHE_DIR)) {
    @mkdir(CACHE_DIR, 0755, true);
}

function jolpica_get(string $endpoint, int $limit = 100): ?array {
    $key  = preg_replace('/[^a-z0-9_]/', '_', strtolower($endpoint));
    $file = CACHE_DIR . $key . '.json';

    // Cache valide ?
    if (file_exists($file) && (time() - filemtime($file)) < CACHE_TTL) {
        $raw = file_get_contents($file);
        return $raw ? json_decode($raw, true) : null;
    }

    // Fetch API
    $url = JOLPICA_BASE . ltrim($endpoint, '/') . '.json?limit=' . $limit;
    $ctx = stream_context_create(['http' => [
        'timeout' => 6,
        'header'  => "User-Agent: FormulaLive/1.0\r\n",
    ]]);

    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) return null;

    file_put_contents($file, $raw);
    return json_decode($raw, true);
}

/* ── Helpers spécialisés ──────────────────────────────────────────────────── */

function api_driver_standings(): array {
    $d = jolpica_get('2026/driverstandings');
    return $d['MRData']['StandingsTable']['StandingsLists'][0]['DriverStandings'] ?? [];
}

function api_constructor_standings(): array {
    $d = jolpica_get('2026/constructorstandings');
    return $d['MRData']['StandingsTable']['StandingsLists'][0]['ConstructorStandings'] ?? [];
}

function api_schedule(): array {
    $d = jolpica_get('2026');
    return $d['MRData']['RaceTable']['Races'] ?? [];
}

function api_last_race_results(): array {
    $d = jolpica_get('2026/results', 30);
    $races = $d['MRData']['RaceTable']['Races'] ?? [];
    if (empty($races)) return [];
    // Prend le dernier round avec résultats
    foreach (array_reverse($races) as $race) {
        if (!empty($race['Results'])) return $race;
    }
    return [];
}

function api_sprint_results(): array {
    $d = jolpica_get('2026/sprint', 30);
    $races = $d['MRData']['RaceTable']['Races'] ?? [];
    foreach (array_reverse($races) as $race) {
        if (!empty($race['SprintResults'])) return $race;
    }
    return [];
}

function api_qualifying(): array {
    $d = jolpica_get('2026/qualifying', 30);
    $races = $d['MRData']['RaceTable']['Races'] ?? [];
    foreach (array_reverse($races) as $race) {
        if (!empty($race['QualifyingResults'])) return $race;
    }
    return [];
}

function api_all_results(): array {
    $d = jolpica_get('2026/results', 300);
    return $d['MRData']['RaceTable']['Races'] ?? [];
}

function api_all_sprint_results(): array {
    $d = jolpica_get('2026/sprint', 100);
    return $d['MRData']['RaceTable']['Races'] ?? [];
}

function api_next_race(): ?array {
    $races = api_schedule();
    $today = date('Y-m-d');
    foreach ($races as $race) {
        if ($race['date'] >= $today) return $race;
    }
    return null;
}

function api_last_completed_race(): ?array {
    $races = api_schedule();
    $today = date('Y-m-d');
    $past  = [];
    foreach ($races as $race) {
        if ($race['date'] < $today) $past[] = $race;
    }
    return !empty($past) ? end($past) : null;
}

/* ── Stats agrégées pour un pilote ───────────────────────────────────────── */
function api_driver_season_stats(string $driverSurname): array {
    $standings = api_driver_standings();
    foreach ($standings as $s) {
        if (stripos($s['Driver']['familyName'], $driverSurname) !== false) {
            return $s;
        }
    }
    return [];
}
