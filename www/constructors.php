<?php 
require_once 'db.php';
require_once 'includes/header.php'; 

$constructors_list = $pdo->query("SELECT name FROM constructors ORDER BY name ASC")->fetchAll();

$name = $_GET['search_name'] ?? '';

$sql = "SELECT * FROM constructors WHERE name LIKE :name LIMIT 50";
$stmt = $pdo->prepare($sql);
$stmt->execute(['name' => "%$name%"]);
$constructors = $stmt->fetchAll();
?>

<div class="page-header">
    <h1><span class="eyebrow">Database</span>Constructors</h1>
</div>

<section class="card">
    <form method="GET" class="filter-form">
        <div class="filter-group">
            <label>Constructor Name</label>
            <input type="text" name="search_name" list="list-constructors" placeholder="Search..." value="<?= htmlspecialchars($name) ?>">
            <datalist id="list-constructors">
                <?php foreach($constructors_list as $c): ?>
                    <option value="<?= htmlspecialchars($c['name']) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>

        <button type="submit" class="btn-primary">Search</button>
        <a href="constructors.php" class="btn-reset">Reset</a>
    </form>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Nationality</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($constructors) > 0): ?>
                    <?php foreach($constructors as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['name']) ?></td>
                        <td><?= htmlspecialchars($c['nationality']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="2"><div class="empty-state"><p>No constructors found.</p></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>