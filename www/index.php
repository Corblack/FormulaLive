<?php
require_once 'db.php'; 
//Préparation et exécution de la requête SQL
$sql = "SELECT * FROM circuits LIMIT 10"; // On limite à 10 pour le test
$stmt = $pdo->query($sql);

// Récupération des données
$circuits = $stmt->fetchAll();

$sql = "
    SELECT 
        races.year AS Annee, 
        races.name AS Course,
        drivers.surname AS Pilote, 
        constructors.name AS Ecurie
    FROM results
    JOIN races ON results.raceId = races.id
    JOIN drivers ON results.driverId = drivers.id
    JOIN constructors ON results.constructorId = constructors.id
    WHERE results.positionOrder = 1 -- On ne prend que les vainqueurs
    ORDER BY races.date DESC -- Les courses les plus récentes en premier
    LIMIT 5 -- On limite aux 5 dernières pour ne pas surcharger la page d'accueil
";

$stmt = $pdo->query($sql);
$historique_victoires = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>F1 Data Explorer - Projet EC</title>
    <!-- Lien vers l'unique fichier CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>F1 Data Explorer</h1>
        <nav>
            <ul>
                <li><a href="#">Accueil</a></li>
                <li><a href="#">Saisons Passées (Base SQL)</a></li>
                <li><a href="#">Saison en cours (API)</a></li>
                <li><a href="#">Administration (CRUD)</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <!-- Section 1 : Pour tes futures données dynamiques (API) -->
        <section class="card">
            <h2>Derniers Résultats Historiques (Vainqueurs)</h2>
            <p>Ces données proviennent directement de ta base MySQL (fichier Kaggle).</p>
            <table>
                <thead>
                    <tr>
                        <th>Année</th>
                        <th>Course</th>
                        <th>Pilote</th>
                        <th>Écurie</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // On boucle sur notre tableau de résultats
                    foreach($historique_victoires as $victoire): 
                    ?>
                        <tr>
                            <!-- On affiche chaque donnée dans une cellule -->
                            <td><?= htmlspecialchars($victoire['Annee']) ?></td>
                            <td><?= htmlspecialchars($victoire['Course']) ?></td>
                            <td><?= htmlspecialchars($victoire['Pilote']) ?></td>
                            <td><?= htmlspecialchars($victoire['Ecurie']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>  

        <!-- Section 2 : Pour tes données historiques (Kaggle/MySQL) -->
        <section class="card">
            <h2>Derniers Résultats Historiques</h2>
            <p>Ce tableau sera rempli par ta base de données via une requête SELECT.</p>
            <table>
                <thead>
                    <tr>
                        <th>Année</th>
                        <th>Pilote</th>
                        <th>Écurie</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>2023</td>
                        <td>Max Verstappen</td>
                        <td>Red Bull</td>
                    </tr>
                    <tr>
                        <td>2022</td>
                        <td>Max Verstappen</td>
                        <td>Red Bull</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </main>

    <footer>
        <p>&copy; 2026 - Projet Web EC Coding Factory</p>
    </footer>
</body>
</html>