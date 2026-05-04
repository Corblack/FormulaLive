<?php 
require_once 'db.php';
require_once 'includes/header.php'; 

$nationalities_list = $pdo->query("SELECT DISTINCT nationality FROM drivers ORDER BY nationality ASC")->fetchAll();
$constructors_list = $pdo->query("SELECT name FROM constructors ORDER BY name ASC")->fetchAll();

$name = $_GET['search_name'] ?? '';
$constructor = $_GET['search_constructor'] ?? '';
$nationality = $_GET['search_nationality'] ?? '';

$sql = "SELECT DISTINCT d.* FROM drivers d
        LEFT JOIN results r ON d.id = r.driverId
        LEFT JOIN constructors c ON r.constructorId = c.id
        WHERE (d.forename LIKE :name OR d.surname LIKE :name)";
$params = ['name' => "%$name%"];

if (!empty($constructor)) {
    $sql .= " AND c.name LIKE :constructor";
    $params['constructor'] = "%$constructor%";
}
if (!empty($nationality)) {
    $sql .= " AND d.nationality LIKE :nationality";
    $params['nationality'] = "%$nationality%";
}
$sql .= " ORDER BY d.surname ASC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$drivers = $stmt->fetchAll();
?>

<div class="page-header">
    <h1><span class="eyebrow">Database</span>Legendary Drivers</h1>
</div>

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

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>Nationality</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($drivers) > 0): ?>
                    <?php foreach($drivers as $d): 
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($d['surname']) ?></strong></td>
                        <td><?= htmlspecialchars($d['forename']) ?></td>
                        <td><?= htmlspecialchars($d['nationality']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4"><div class="empty-state"><p>No drivers found.</p></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>