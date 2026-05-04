<?php
// 1. Définition des variables de connexion
$host = 'localhost'; 
$dbname = 'FormulaLive';
$username = 'root';
$password = ''; 

// 2. Construction du DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

// 3. Tentative de connexion avec le bloc try/catch pour gérer les erreurs
try {
    // Création de l'objet PDO
    $pdo = new PDO($dsn, $username, $password);
    
    // Configuration des options PDO
    // On demande à PDO de nous afficher les vraies erreurs SQL s'il y en a
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // On demande à PDO de nous renvoyer les données sous forme de tableaux associatifs
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si la connexion échoue, on arrête tout (die) et on affiche l'erreur
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>