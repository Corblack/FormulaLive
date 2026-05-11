<?php
session_start();
require_once 'db.php';

$password_required = "12345678";

if (isset($_POST['login'])) {
    if ($_POST['password'] === $password_required) {
        $_SESSION['admin_logged'] = true;
    } else {
        $error = "Mot de passe incorrect.";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// Si pas connecté, on affiche le formulaire de login
if (!isset($_SESSION['admin_logged'])): 
require_once 'includes/header.php';
?>
<div class="container" style="margin-top: 100px; max-width: 400px;">
    <section class="card">
        <h2 class="section-title">Connexion Admin</h2>
        <?php if(isset($error)) echo "<p style='color:var(--red)'>$error</p>"; ?>
        <form method="POST" class="filter-form" style="flex-direction: column;">
            <div class="filter-group">
                <label>Mot de passe</label>
                <input type="password" name="password" required style="width:100%; padding:10px; background:var(--black); border:1px solid var(--border); color:white;">
            </div>
            <button type="submit" name="login" class="btn-primary" style="margin-top:20px">Se connecter</button>
        </form>
    </section>
</div>
<?php 
require_once 'includes/footer.php';
exit; 
endif; 
?>