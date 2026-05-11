<?php
require_once 'admin_auth.php';
require_once 'includes/header.php';

$id = $_GET['id'] ?? null;
$driver = ['forename' => '', 'surname' => '', 'nationality' => '', 'dob' => ''];

// Si modification, on récupère les données actuelles
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
    $stmt->execute([$id]);
    $driver = $stmt->fetch();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $forename = $_POST['forename'];
    $surname = $_POST['surname'];
    $nationality = $_POST['nationality'];
    $dob = $_POST['dob'];

    if ($id) {
        $sql = "UPDATE drivers SET forename=?, surname=?, nationality=?, dob=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$forename, $surname, $nationality, $dob, $id]);
    } else {
        $sql = "INSERT INTO drivers (forename, surname, nationality, dob) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$forename, $surname, $nationality, $dob]);
    }
    header("Location: admin.php");
    exit;
}
?>

<div class="container" style="margin-top: 50px;">
    <section class="card">
        <h2><?= $id ? "Modifier" : "Ajouter" ?> un pilote</h2>
        <form method="POST" class="filter-form" style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:20px">
            <div class="filter-group">
                <label>Prénom</label>
                <input type="text" name="forename" value="<?= htmlspecialchars($driver['forename']) ?>" required>
            </div>
            <div class="filter-group">
                <label>Nom</label>
                <input type="text" name="surname" value="<?= htmlspecialchars($driver['surname']) ?>" required>
            </div>
            <div class="filter-group">
                <label>Nationalité</label>
                <input type="text" name="nationality" value="<?= htmlspecialchars($driver['nationality']) ?>">
            </div>
            <div class="filter-group">
                <label>Date de naissance</label>
                <input type="date" name="dob" value="<?= $driver['dob'] ?>" style="padding:12px; background:var(--black); border:1px solid var(--border); color:white;">
            </div>
            <div style="grid-column: span 2; display:flex; gap:10px">
                <button type="submit" class="btn-primary">Enregistrer</button>
                <a href="admin.php" class="btn-reset">Annuler</a>
            </div>
        </form>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>