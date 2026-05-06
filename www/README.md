# 🏎️ FormulaLive

FormulaLive est une application web dynamique dédiée aux passionnés de
Formule 1. Elle permet d'explorer plus de 70 ans d'histoire de la F1
(pilotes, constructeurs, circuits) tout en suivant en temps réel la
saison 2026.

## ✨ Fonctionnalités

- **Base de données historique :** Recherche et filtrage approfondis
  des pilotes, écuries et circuits avec des statistiques détaillées
  (victoires, podiums, ratio de victoire, etc.).
- **Saison 2026 en direct :**
  - Classements actuels du championnat (Pilotes et Constructeurs).
  - Calendrier complet de la saison avec compte à rebours pour la
    prochaine course.
  - Résultats détaillés des Grands Prix, courses Sprint et séances
    de qualifications.
- **Système de cache optimisé :** Réduction des appels à l'API externe
  grâce à un cache local (PHP) d'une durée de 15 minutes, garantissant
  des temps de chargement rapides et évitant le dépassement des
  limites de requêtes.
- **Interface UI/UX :** Design responsive et moderne, développé
  sur-mesure en CSS, s'adaptant parfaitement aux environnements
  desktop, tablette et mobile.

## 🛠️ Technologies Utilisées

- **Backend :** PHP 8+ avec PDO pour une connexion sécurisée à la base
  de données.
- **Frontend :** HTML5, CSS3 (Variables natives, Flexbox, Grid).
- **Base de données :** MySQL (Données historiques initialement issues
  de Kaggle).
- **API Externe :** Jolpica F1 API (utilisée pour récupérer les
  statistiques en direct de la saison en cours).

## 🚀 Installation locale

Pour faire tourner ce projet sur ta machine, suis ces étapes :

1. **Cloner le dépôt :**

```bash
git clone https://github.com/ton-nom-utilisateur/FormulaLive.git
```

2. **Configuration de la Base de Données :**

- Lance ton serveur local (ex: XAMPP, WAMP, ou MAMP).
- Crée une base de données nommée `FormulaLive`.
- Importe le script SQL contenant les tables (drivers, circuits,
  constructors, races, results, etc.).
- Modifie le fichier `db.php` pour y insérer tes identifiants MySQL
  (par défaut : root et mot de passe vide).

3. **Configuration du Serveur :**

- Place le dossier du projet dans ton répertoire web (htdocs ou www).
- Assure-toi que PHP a les permissions nécessaires pour créer et
  écrire dans le dossier `cache/` (droits 0755).

4. **Lancement :**

- Ouvre ton navigateur et accède à http://localhost/FormulaLive

## 👨‍💻 Auteur

Johann Paimboeuf - Étudiant en développement web et
d'applications mobiles.
