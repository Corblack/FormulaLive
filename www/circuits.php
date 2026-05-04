<?php 
require_once 'db.php';
require_once 'includes/header.php'; 

// Lists for autocomplete
$countries_list = $pdo->query("SELECT DISTINCT country FROM circuits ORDER BY country ASC")->fetchAll();
$circuit_names_list = $pdo->query("SELECT name FROM circuits ORDER BY name ASC")->fetchAll();

$name = $_GET['search_name'] ?? '';
$country = $_GET['search_country'] ?? '';

$sql = "SELECT * FROM circuits WHERE 1=1";
$params = [];

if (!empty($name)) {
    $sql .= " AND name LIKE :name";
    $params['name'] = "%$name%";
}
if (!empty($country)) {
    $sql .= " AND country LIKE :country";
    $params['country'] = "%$country%";
}
$sql .= " LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$circuits = $stmt->fetchAll();
?>

<div class="page-header">
    <h1><span class="eyebrow">Database</span>Historical Circuits</h1>
</div>

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

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Circuit</th>
                    <th>Location</th>
                    <th>Country</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($circuits) > 0): ?>
                    <?php foreach($circuits as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['name']) ?></td>
                        <td><?= htmlspecialchars($c['location']) ?></td>
                        <td><?= htmlspecialchars($c['country']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3"><div class="empty-state"><p>No circuits found.</p></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>