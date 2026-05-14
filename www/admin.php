<?php
require_once 'admin_auth.php';
require_once 'db.php';

$flash = '';
$tab   = $_GET['tab'] ?? 'hero';

/* ══ HERO IMAGE UPLOAD ════════════════════════════════════════════════════ */
// On remonte d'un dossier (..) pour atteindre /doc depuis /www
define('HERO_DIR',     __DIR__ . '/../doc/media/img/');
define('HERO_CONFIG',  __DIR__ . '/hero_image.txt');
define('HERO_DEFAULT', '72499.jpg');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'upload_hero') {
    $file = $_FILES['hero_image'] ?? null;
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
            $flash = 'err:Format non supporté. JPG, PNG ou WEBP seulement.';
        } elseif ($file['size'] > 8 * 1024 * 1024) {
            $flash = 'err:Image trop lourde (max 8 Mo).';
        } else {
            $filename = 'hero_custom_' . time() . '.' . $ext;
            if (!is_dir(HERO_DIR)) mkdir(HERO_DIR, 0755, true);
            if (move_uploaded_file($file['tmp_name'], HERO_DIR . $filename)) {
                file_put_contents(HERO_CONFIG, $filename);
                $flash = 'ok:Image hero mise à jour !';
            } else {
                $flash = 'err:Erreur lors de l\'upload. Vérifier les permissions du dossier.';
            }
        }
    } else {
        $flash = 'err:Aucun fichier reçu.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'reset_hero') {
    file_put_contents(HERO_CONFIG, HERO_DEFAULT);
    $flash = 'ok:Image hero réinitialisée.';
}

$currentHero = file_exists(HERO_CONFIG) ? trim(file_get_contents(HERO_CONFIG)) : HERO_DEFAULT;

/* ══ DRIVERS CRUD ═════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'driver_delete') {
    $pdo->prepare("DELETE FROM drivers WHERE id=?")->execute([(int)$_POST['id']]);
    $flash = 'ok:Pilote supprimé.'; $tab = 'drivers';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'driver_save') {
    $p = ['fn'=>trim($_POST['forename']),'sn'=>trim($_POST['surname']),'nat'=>trim($_POST['nationality']),'dob'=>$_POST['dob']?:null];
    if (!empty($_POST['id'])) {
        $pdo->prepare("UPDATE drivers SET forename=:fn,surname=:sn,nationality=:nat,dob=:dob WHERE id=:id")->execute(array_merge($p,['id'=>(int)$_POST['id']]));
        $flash = 'ok:Pilote mis à jour.';
    } else {
        $pdo->prepare("INSERT INTO drivers(forename,surname,nationality,dob) VALUES(:fn,:sn,:nat,:dob)")->execute($p);
        $flash = 'ok:Pilote créé.';
    }
    $tab = 'drivers';
}

/* ══ CONSTRUCTORS CRUD ════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'constructor_delete') {
    $pdo->prepare("DELETE FROM constructors WHERE id=?")->execute([(int)$_POST['id']]);
    $flash = 'ok:Constructeur supprimé.'; $tab = 'constructors';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'constructor_save') {
    $p = ['n'=>trim($_POST['name']),'nat'=>trim($_POST['nationality'])];
    if (!empty($_POST['id'])) {
        $pdo->prepare("UPDATE constructors SET name=:n,nationality=:nat WHERE id=:id")->execute(array_merge($p,['id'=>(int)$_POST['id']]));
        $flash = 'ok:Constructeur mis à jour.';
    } else {
        $pdo->prepare("INSERT INTO constructors(name,nationality) VALUES(:n,:nat)")->execute($p);
        $flash = 'ok:Constructeur créé.';
    }
    $tab = 'constructors';
}

/* ══ FETCH DATA ═══════════════════════════════════════════════════════════ */
$dq = trim($_GET['dq'] ?? '');
$cq = trim($_GET['cq'] ?? '');

if ($dq) {
    $ds = $pdo->prepare("SELECT id,forename,surname,nationality,dob FROM drivers WHERE forename LIKE :q OR surname LIKE :q OR nationality LIKE :q ORDER BY surname ASC LIMIT 10");
    $ds->execute(['q'=>"%$dq%"]);
} else {
    $ds = $pdo->query("SELECT id,forename,surname,nationality,dob FROM drivers ORDER BY surname ASC LIMIT 10");
}
$drivers = $ds->fetchAll();

if ($cq) {
    $cs = $pdo->prepare("SELECT id,name,nationality FROM constructors WHERE name LIKE :q OR nationality LIKE :q ORDER BY name ASC LIMIT 10");
    $cs->execute(['q'=>"%$cq%"]);
} else {
    $cs = $pdo->query("SELECT id,name,nationality FROM constructors ORDER BY name ASC LIMIT 10");
}
$constructors = $cs->fetchAll();

/* ══ EDIT MODE ════════════════════════════════════════════════════════════ */
$editDriver = null;
if (isset($_GET['edit_driver'])) {
    $s = $pdo->prepare("SELECT * FROM drivers WHERE id=?"); $s->execute([(int)$_GET['edit_driver']]);
    $editDriver = $s->fetch(); $tab = 'drivers';
}
$editConstructor = null;
if (isset($_GET['edit_constructor'])) {
    $s = $pdo->prepare("SELECT * FROM constructors WHERE id=?"); $s->execute([(int)$_GET['edit_constructor']]);
    $editConstructor = $s->fetch(); $tab = 'constructors';
}
if (isset($_GET['add_driver']))      { $editDriver = []; $tab = 'drivers'; }
if (isset($_GET['add_constructor'])) { $editConstructor = []; $tab = 'constructors'; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>FormulaLive — Admin</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <div class="header-inner">
        <a href="admin.php" class="site-logo">Formula<span>Live</span></a>
        <nav>
            <ul>
                <li><span class="circuit-race-count" style="margin-right: 15px;">Admin</span></li>
                <li><a href="index.php">← Site</a></li>
                <li><a href="?logout=1">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="container" style="padding-top: 40px; padding-bottom: 80px;">

    <?php if ($flash): [$ft,$fm] = explode(':', $flash, 2); ?>
    <div class="flash flash-<?= $ft==='ok'?'ok':'err' ?>"><?= $fm ?></div>
    <?php endif; ?>

    <div class="adm-tabs">
        <a href="?tab=hero"         class="adm-tab <?= $tab==='hero'?'active':'' ?>">🖼 Hero Image</a>
        <a href="?tab=drivers"      class="adm-tab <?= $tab==='drivers'?'active':'' ?>">◉ Drivers</a>
        <a href="?tab=constructors" class="adm-tab <?= $tab==='constructors'?'active':'' ?>">◈ Constructors</a>
    </div>

    <?php if ($tab === 'hero'): ?>
    <div class="adm-section-title">Hero Image</div>
    <div class="adm-section-sub">Image de fond de la section hero — accueil</div>

    <div class="hero-preview">
        <img src="../doc/media/img/<?= htmlspecialchars($currentHero) ?>?v=<?= time() ?>" alt="Hero actuel">
        <div class="hero-preview-label">Actuelle : <?= htmlspecialchars($currentHero) ?></div>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="_action" value="upload_hero">
        <div class="upload-zone" id="dropzone">
            <input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp">
            <div class="upload-icon">⬆</div>
            <div class="upload-title">Déposer ou cliquer pour choisir</div>
            <div class="upload-hint">JPG · PNG · WEBP — max 8 Mo — idéal 1920 × 1080</div>
        </div>
        <button type="submit" class="btn-primary">Enregistrer cette image →</button>
    </form>

    <form method="POST" style="margin-top:12px">
        <input type="hidden" name="_action" value="reset_hero">
        <button type="submit" class="btn-reset" onclick="return confirm('Revenir à l\'image par défaut ?')">↩ Réinitialiser</button>
    </form>

    <?php elseif ($tab === 'drivers'): ?>
    <div class="adm-section-title">Drivers</div>
    <div class="adm-section-sub">Résultats limités à 10 — Create · Update · Delete</div>

    <?php if ($editDriver !== null): $isNew = empty($editDriver); ?>
    <div class="adm-form">
        <div class="adm-form-title"><?= $isNew ? '+ Nouveau pilote' : '✎ Modifier #'.$editDriver['id'] ?></div>
        <form method="POST">
            <input type="hidden" name="_action" value="driver_save">
            <input type="hidden" name="id" value="<?= $editDriver['id'] ?? '' ?>">
            <div class="adm-form-grid">
                <div class="adm-field"><label>Prénom *</label><input type="text" name="forename" required value="<?= htmlspecialchars($editDriver['forename'] ?? '') ?>"></div>
                <div class="adm-field"><label>Nom *</label><input type="text" name="surname" required value="<?= htmlspecialchars($editDriver['surname'] ?? '') ?>"></div>
                <div class="adm-field"><label>Nationalité</label><input type="text" name="nationality" value="<?= htmlspecialchars($editDriver['nationality'] ?? '') ?>"></div>
                <div class="adm-field"><label>Date de naissance</label><input type="date" name="dob" value="<?= htmlspecialchars($editDriver['dob'] ?? '') ?>"></div>
            </div>
            <div class="adm-form-actions">
                <button type="submit" class="btn-primary"><?= $isNew?'Créer':'Sauvegarder' ?></button>
                <a href="?tab=drivers<?= $dq?'&dq='.urlencode($dq):'' ?>" class="btn-cancel">Annuler</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <form method="GET" class="adm-search">
        <input type="hidden" name="tab" value="drivers">
        <input type="text" name="dq" placeholder="Rechercher par nom ou nationalité…" value="<?= htmlspecialchars($dq) ?>">
        <button type="submit" class="btn-primary">Chercher</button>
        <?php if ($dq): ?><a href="?tab=drivers" class="btn-cancel">✕</a><?php endif; ?>
    </form>

    <div class="adm-table-top">
        <span class="adm-count"><?= count($drivers) ?> résultat<?= count($drivers)>1?'s':'' ?> (max 10)</span>
        <a href="?tab=drivers&add_driver=1<?= $dq?'&dq='.urlencode($dq):'' ?>" class="btn-primary">+ Ajouter</a>
    </div>

    <table class="adm-table">
        <thead><tr><th>#</th><th>Nom</th><th>Prénom</th><th>Nationalité</th><th>Naissance</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($drivers)): ?>
            <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--muted);font-family:var(--fm);font-size:.75rem;letter-spacing:.1em;text-transform:uppercase">Aucun résultat.</td></tr>
        <?php endif; ?>
        <?php foreach ($drivers as $d): ?>
        <tr>
            <td><?= $d['id'] ?></td>
            <td><strong style="font-family:var(--fd);font-size:1rem;text-transform:uppercase"><?= htmlspecialchars($d['surname']) ?></strong></td>
            <td><?= htmlspecialchars($d['forename']) ?></td>
            <td><?= htmlspecialchars($d['nationality']) ?></td>
            <td><?= ($d['dob'] && $d['dob'] !== '0000-00-00') ? date('d M Y', strtotime($d['dob'])) : '—' ?></td>
            <td><div class="td-acts">
                <a href="?tab=drivers&edit_driver=<?= $d['id'] ?><?= $dq?'&dq='.urlencode($dq):'' ?>" class="btn-ed">Edit</a>
                <form method="POST" onsubmit="return confirm('Supprimer <?= htmlspecialchars(addslashes($d['forename'].' '.$d['surname'])) ?> ?')" style="display:inline">
                    <input type="hidden" name="_action" value="driver_delete">
                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                    <button type="submit" class="btn-dl">Delete</button>
                </form>
            </div></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php elseif ($tab === 'constructors'): ?>
    <div class="adm-section-title">Constructors</div>
    <div class="adm-section-sub">Résultats limités à 10 — Create · Update · Delete</div>

    <?php if ($editConstructor !== null): $isNew = empty($editConstructor); ?>
    <div class="adm-form">
        <div class="adm-form-title"><?= $isNew ? '+ Nouveau constructeur' : '✎ Modifier #'.$editConstructor['id'] ?></div>
        <form method="POST">
            <input type="hidden" name="_action" value="constructor_save">
            <input type="hidden" name="id" value="<?= $editConstructor['id'] ?? '' ?>">
            <div class="adm-form-grid">
                <div class="adm-field"><label>Nom *</label><input type="text" name="name" required value="<?= htmlspecialchars($editConstructor['name'] ?? '') ?>"></div>
                <div class="adm-field"><label>Nationalité</label><input type="text" name="nationality" value="<?= htmlspecialchars($editConstructor['nationality'] ?? '') ?>"></div>
            </div>
            <div class="adm-form-actions">
                <button type="submit" class="btn-primary"><?= $isNew?'Créer':'Sauvegarder' ?></button>
                <a href="?tab=constructors<?= $cq?'&cq='.urlencode($cq):'' ?>" class="btn-cancel">Annuler</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <form method="GET" class="adm-search">
        <input type="hidden" name="tab" value="constructors">
        <input type="text" name="cq" placeholder="Rechercher par nom ou nationalité…" value="<?= htmlspecialchars($cq) ?>">
        <button type="submit" class="btn-primary">Chercher</button>
        <?php if ($cq): ?><a href="?tab=constructors" class="btn-cancel">✕</a><?php endif; ?>
    </form>

    <div class="adm-table-top">
        <span class="adm-count"><?= count($constructors) ?> résultat<?= count($constructors)>1?'s':'' ?> (max 10)</span>
        <a href="?tab=constructors&add_constructor=1<?= $cq?'&cq='.urlencode($cq):'' ?>" class="btn-primary">+ Ajouter</a>
    </div>

    <table class="adm-table">
        <thead><tr><th>#</th><th>Nom</th><th>Nationalité</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($constructors)): ?>
            <tr><td colspan="4" style="text-align:center;padding:40px;color:var(--muted);font-family:var(--fm);font-size:.75rem;letter-spacing:.1em;text-transform:uppercase">Aucun résultat.</td></tr>
        <?php endif; ?>
        <?php foreach ($constructors as $c): ?>
        <tr>
            <td><?= $c['id'] ?></td>
            <td><strong style="font-family:var(--fd);font-size:1rem;text-transform:uppercase"><?= htmlspecialchars($c['name']) ?></strong></td>
            <td><?= htmlspecialchars($c['nationality']) ?></td>
            <td><div class="td-acts">
                <a href="?tab=constructors&edit_constructor=<?= $c['id'] ?><?= $cq?'&cq='.urlencode($cq):'' ?>" class="btn-ed">Edit</a>
                <form method="POST" onsubmit="return confirm('Supprimer <?= htmlspecialchars(addslashes($c['name'])) ?> ?')" style="display:inline">
                    <input type="hidden" name="_action" value="constructor_delete">
                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <button type="submit" class="btn-dl">Delete</button>
                </form>
            </div></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php endif; ?>
</div>

</body>
</html>