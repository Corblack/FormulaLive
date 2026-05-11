<?php
require_once 'admin_auth.php'; // Vérifie la session
require_once 'includes/header.php';

// Gestion de la suppression
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM drivers WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    header("Location: admin.php?msg=Supprimé");
    exit;
}

$drivers = $pdo->query("SELECT id, forename, surname, nationality FROM drivers ORDER BY id DESC LIMIT 50")->fetchAll();
?>

<div class="page-header container">
    <h1><span class="eyebrow">Administration</span>Gestion des Pilotes</h1>
    <a href="admin_edit.php" class="btn-primary" style="margin-top:20px">+ Ajouter un pilote</a>
    <a href="?logout=1" class="btn-reset">Déconnexion</a>
</div>

<div class="container">
    <section class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Nationalité</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($drivers as $d): ?>
                    <tr>
                        <td><?= $d['id'] ?></td>
                        <td><?= htmlspecialchars($d['forename'] . ' ' . $d['surname']) ?></td>
                        <td><?= htmlspecialchars($d['nationality']) ?></td>
                        <td>
                            <a href="admin_edit.php?id=<?= $d['id'] ?>" class="btn-secondary" style="padding:5px 10px; font-size:12px">Modifier</a>
                            <a href="?delete_id=<?= $d['id'] ?>" class="btn-primary" 
                               style="padding:5px 10px; font-size:12px; background:var(--red-dark)" 
                               onclick="return confirm('Supprimer ce pilote ?')">Supprimer</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>