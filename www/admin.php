<?php
require_once 'admin_auth.php';
require_once 'db.php';

$flash = '';
$tab   = $_GET['tab'] ?? 'hero';

// Arborescence réelle :
// FORMULALIVE/doc/media/img/   <- images
// FORMULALIVE/www/admin.php    <- ce fichier
// Chemin filesystem : __DIR__ . '/../doc/media/img/'
// Chemin web        : '../doc/media/img/'

define('HERO_DIR',    __DIR__ . '/doc/media/img/');
define('HERO_CONFIG', __DIR__ . '/hero_image.txt');
define('HERO_WEB',    '/doc/media/img/');
define('HERO_DEFAULT','72499.jpg');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action']??'') === 'upload_hero') {
    $file = $_FILES['hero_image'] ?? null;
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
            $flash = 'err:Format non supporté (JPG/PNG/WEBP).';
        } elseif ($file['size'] > 8*1024*1024) {
            $flash = 'err:Image trop lourde (max 8 Mo).';
        } else {
            $fn = 'hero_custom_'.time().'.'.$ext;
            if (!is_dir(HERO_DIR)) mkdir(HERO_DIR, 0755, true);
            if (move_uploaded_file($file['tmp_name'], HERO_DIR.$fn)) {
                file_put_contents(HERO_CONFIG, $fn);
                $flash = 'ok:Image hero mise à jour !';
            } else {
                $flash = 'err:Échec upload — vérifier les permissions de '.HERO_DIR;
            }
        }
    } else { $flash = 'err:Aucun fichier reçu.'; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action']??'') === 'reset_hero') {
    file_put_contents(HERO_CONFIG, HERO_DEFAULT);
    $flash = 'ok:Image réinitialisée.';
}

$currentHero = file_exists(HERO_CONFIG) ? preg_replace('/[^a-zA-Z0-9._-]/','',trim(file_get_contents(HERO_CONFIG))) : HERO_DEFAULT;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action']??'') === 'driver_delete') {
    $pdo->prepare("DELETE FROM drivers WHERE id=?")->execute([(int)$_POST['id']]);
    $flash = 'ok:Pilote supprimé.'; $tab = 'drivers';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action']??'') === 'driver_save') {
    $p = ['fn'=>trim($_POST['forename']),'sn'=>trim($_POST['surname']),'nat'=>trim($_POST['nationality']),'dob'=>$_POST['dob']?:null];
    if (!empty($_POST['id'])) {
        $pdo->prepare("UPDATE drivers SET forename=:fn,surname=:sn,nationality=:nat,dob=:dob WHERE id=:id")->execute($p+['id'=>(int)$_POST['id']]);
        $flash = 'ok:Pilote mis à jour.';
    } else {
        $pdo->prepare("INSERT INTO drivers(forename,surname,nationality,dob) VALUES(:fn,:sn,:nat,:dob)")->execute($p);
        $flash = 'ok:Pilote créé.';
    }
    $tab = 'drivers';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action']??'') === 'constructor_delete') {
    $pdo->prepare("DELETE FROM constructors WHERE id=?")->execute([(int)$_POST['id']]);
    $flash = 'ok:Constructeur supprimé.'; $tab = 'constructors';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action']??'') === 'constructor_save') {
    $p = ['n'=>trim($_POST['name']),'nat'=>trim($_POST['nationality'])];
    if (!empty($_POST['id'])) {
        $pdo->prepare("UPDATE constructors SET name=:n,nationality=:nat WHERE id=:id")->execute($p+['id'=>(int)$_POST['id']]);
        $flash = 'ok:Constructeur mis à jour.';
    } else {
        $pdo->prepare("INSERT INTO constructors(name,nationality) VALUES(:n,:nat)")->execute($p);
        $flash = 'ok:Constructeur créé.';
    }
    $tab = 'constructors';
}

$dq = trim($_GET['dq']??''); $cq = trim($_GET['cq']??'');
if ($dq) { $ds=$pdo->prepare("SELECT id,forename,surname,nationality,dob FROM drivers WHERE forename LIKE :q OR surname LIKE :q OR nationality LIKE :q ORDER BY surname LIMIT 10"); $ds->execute(['q'=>"%$dq%"]); }
else { $ds=$pdo->query("SELECT id,forename,surname,nationality,dob FROM drivers ORDER BY surname LIMIT 10"); }
$drivers=$ds->fetchAll();
if ($cq) { $cs=$pdo->prepare("SELECT id,name,nationality FROM constructors WHERE name LIKE :q OR nationality LIKE :q ORDER BY name LIMIT 10"); $cs->execute(['q'=>"%$cq%"]); }
else { $cs=$pdo->query("SELECT id,name,nationality FROM constructors ORDER BY name LIMIT 10"); }
$constructors=$cs->fetchAll();

$editDriver=null; $editConstructor=null;
if (isset($_GET['edit_driver'])) { $s=$pdo->prepare("SELECT * FROM drivers WHERE id=?"); $s->execute([(int)$_GET['edit_driver']]); $editDriver=$s->fetch(); $tab='drivers'; }
if (isset($_GET['edit_constructor'])) { $s=$pdo->prepare("SELECT * FROM constructors WHERE id=?"); $s->execute([(int)$_GET['edit_constructor']]); $editConstructor=$s->fetch(); $tab='constructors'; }
if (isset($_GET['add_driver'])) { $editDriver=[]; $tab='drivers'; }
if (isset($_GET['add_constructor'])) { $editConstructor=[]; $tab='constructors'; }
?>
<!DOCTYPE html><html lang="fr"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>FormulaLive — Admin</title>
<link rel="stylesheet" href="style.css">
<style>
.adm-top{background:var(--void);border-bottom:1px solid var(--border);height:56px;display:flex;align-items:center;justify-content:space-between;padding:0 32px;position:sticky;top:0;z-index:100}
.adm-logo{font-family:var(--fd);font-weight:900;font-size:1.4rem;text-transform:uppercase;color:var(--white);letter-spacing:-.02em;text-decoration:none}
.adm-logo span{color:var(--red);font-style:italic}
.adm-top-r{display:flex;align-items:center;gap:20px}
.adm-badge{font-family:var(--fm);font-size:.6rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--red);border:1px solid rgba(232,0,10,.3);padding:3px 10px}
.adm-top-r a{font-family:var(--fm);font-size:.72rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);text-decoration:none;transition:color .2s}
.adm-top-r a:hover{color:var(--white)}
.adm-body{max-width:1000px;margin:0 auto;padding:40px 32px}
.flash{font-family:var(--fm);font-size:.78rem;font-weight:600;letter-spacing:.08em;padding:11px 18px;margin-bottom:24px}
.flash-ok{border-left:3px solid #00c850;background:rgba(0,200,80,.07);color:#00c850}
.flash-err{border-left:3px solid var(--red);background:rgba(232,0,10,.07);color:var(--red)}
.adm-tabs{display:flex;border-bottom:1px solid var(--border);margin-bottom:32px}
.adm-tab{font-family:var(--fm);font-size:.8rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;padding:12px 28px;color:var(--muted);text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-1px;transition:color .2s,border-color .2s}
.adm-tab:hover{color:var(--white)}.adm-tab.active{color:var(--white);border-bottom-color:var(--red)}
.adm-title{font-family:var(--fd);font-weight:900;font-size:1.6rem;text-transform:uppercase;letter-spacing:-.01em;margin-bottom:4px}
.adm-sub{font-family:var(--fm);font-size:.68rem;font-weight:600;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:28px}
.hero-prev{position:relative;width:100%;height:200px;background:var(--surface);border:1px solid var(--border);margin-bottom:24px;overflow:hidden}
.hero-prev img{width:100%;height:100%;object-fit:cover;display:block}
.hero-prev::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--red);z-index:1}
.hero-prev-lbl{position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.75);font-family:var(--fm);font-size:.62rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);padding:8px 14px}
.drop-zone{border:2px dashed var(--border);padding:40px 24px;text-align:center;transition:border-color .2s,background .2s;cursor:pointer;position:relative;margin-bottom:16px}
.drop-zone:hover,.drop-zone.drag{border-color:var(--red);background:rgba(232,0,10,.04)}
.drop-zone input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.drop-icon{font-size:2rem;margin-bottom:10px;opacity:.35}
.drop-title{font-family:var(--fd);font-weight:700;font-size:1.1rem;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px}
.drop-hint{font-family:var(--fm);font-size:.68rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--muted)}
.adm-search{display:flex;gap:10px;margin-bottom:20px}
.adm-search input{flex:1;padding:10px 14px;background:var(--black);border:1px solid var(--border);border-bottom:2px solid var(--muted-2);color:var(--white);font-family:var(--fb);font-size:.88rem;outline:none;transition:border-color .2s}
.adm-search input:focus{border-bottom-color:var(--red)}
.adm-tbl-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
.adm-count{font-family:var(--fm);font-size:.7rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--muted)}
.adm-form{background:var(--surface);border:1px solid var(--border);padding:24px;margin-bottom:24px;position:relative}
.adm-form::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:var(--red)}
.adm-form-ttl{font-family:var(--fd);font-weight:900;font-size:1rem;text-transform:uppercase;letter-spacing:.04em;margin-bottom:18px}
.adm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-bottom:16px}
.adm-fld{display:flex;flex-direction:column;gap:5px}
.adm-fld label{font-family:var(--fm);font-size:.62rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:var(--muted)}
.adm-fld input{padding:10px 12px;background:var(--black);border:1px solid var(--border);border-bottom:2px solid var(--muted-2);color:var(--white);font-family:var(--fb);font-size:.88rem;outline:none;transition:border-color .2s;width:100%}
.adm-fld input:focus{background:var(--surface-2);border-bottom-color:var(--red)}
.adm-actions{display:flex;gap:10px;align-items:center}
.atbl{width:100%;border-collapse:collapse}
.atbl thead tr{border-bottom:2px solid var(--red)}
.atbl th{text-align:left;padding:9px 12px;font-family:var(--fm);font-size:.62rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);white-space:nowrap}
.atbl tbody tr{border-bottom:1px solid var(--border);transition:background .15s}
.atbl tbody tr:hover{background:rgba(232,0,10,.04)}
.atbl td{padding:11px 12px;font-size:.88rem;color:var(--white);font-weight:300;vertical-align:middle}
.atbl td:first-child{font-family:var(--fm);font-size:.68rem;color:var(--muted)}
.acts{display:flex;gap:6px}
.btn-ed,.btn-dl{font-family:var(--fm);font-size:.6rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;padding:5px 11px;border:1px solid;cursor:pointer;text-decoration:none;display:inline-block;transition:all .15s}
.btn-ed{color:var(--white);border-color:var(--border);background:var(--surface-2)}.btn-ed:hover{border-color:var(--white)}
.btn-dl{color:var(--red);border-color:rgba(232,0,10,.3);background:rgba(232,0,10,.06)}.btn-dl:hover{background:rgba(232,0,10,.15)}
.btn-cancel{font-family:var(--fm);font-size:.72rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);text-decoration:none;padding:10px 14px;border:1px solid var(--border);transition:color .2s}.btn-cancel:hover{color:var(--white)}
.empty-row td{text-align:center;padding:40px;color:var(--muted);font-family:var(--fm);font-size:.75rem;letter-spacing:.1em;text-transform:uppercase}
@media(max-width:600px){.adm-body{padding:20px 16px}.adm-top{padding:0 16px}.adm-tabs{overflow-x:auto}.adm-grid{grid-template-columns:1fr}}
</style></head><body>

<div class="adm-top">
    <a href="admin.php" class="adm-logo">Formula<span>Live</span></a>
    <div class="adm-top-r">
        <span class="adm-badge">Admin</span>
        <a href="index.php">← Site</a>
        <a href="?logout=1">Logout</a>
    </div>
</div>

<div class="adm-body">
<?php if ($flash): [$ft,$fm]=explode(':',$flash,2); ?>
<div class="flash flash-<?= $ft==='ok'?'ok':'err' ?>"><?= $fm ?></div>
<?php endif; ?>

<div class="adm-tabs">
    <a href="?tab=hero"         class="adm-tab <?= $tab==='hero'?'active':''?>">🖼 Hero Image</a>
    <a href="?tab=drivers"      class="adm-tab <?= $tab==='drivers'?'active':''?>">◉ Drivers</a>
    <a href="?tab=constructors" class="adm-tab <?= $tab==='constructors'?'active':''?>">◈ Constructors</a>
</div>

<?php if ($tab==='hero'): ?>
<div class="adm-title">Hero Image</div>
<div class="adm-sub">Image de fond de l'accueil · ../doc/media/img/</div>

<div class="hero-prev">
    <img src="<?= HERO_WEB.htmlspecialchars($currentHero) ?>?v=<?= time() ?>" alt="Hero">
    <div class="hero-prev-lbl">Actuelle : <?= htmlspecialchars($currentHero) ?></div>
</div>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="_action" value="upload_hero">
    <div class="drop-zone" id="dz">
        <input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp" onchange="prevHero(this)">
        <div class="drop-icon">⬆</div>
        <div class="drop-title">Déposer ou cliquer pour choisir</div>
        <div class="drop-hint">JPG · PNG · WEBP — max 8 Mo — recommandé 1920×1080</div>
    </div>
    <div id="prev-wrap" style="display:none;margin-bottom:16px">
        <img id="prev-img" src="" style="width:100%;max-height:180px;object-fit:cover;border:1px solid var(--border)">
        <div style="font-family:var(--fm);font-size:.65rem;color:var(--muted);margin-top:6px;letter-spacing:.08em;text-transform:uppercase">Aperçu — non enregistré</div>
    </div>
    <button type="submit" class="btn-primary">Enregistrer →</button>
</form>
<form method="POST" style="margin-top:12px">
    <input type="hidden" name="_action" value="reset_hero">
    <button type="submit" class="btn-reset" onclick="return confirm('Réinitialiser ?')">↩ Image par défaut (<?= HERO_DEFAULT ?>)</button>
</form>

<?php elseif ($tab==='drivers'): ?>
<div class="adm-title">Drivers</div>
<div class="adm-sub">10 résultats max — Create · Update · Delete</div>

<?php if ($editDriver!==null): $isNew=empty($editDriver); ?>
<div class="adm-form">
    <div class="adm-form-ttl"><?= $isNew?'+ Nouveau pilote':'✎ Modifier #'.$editDriver['id'] ?></div>
    <form method="POST">
        <input type="hidden" name="_action" value="driver_save">
        <input type="hidden" name="id" value="<?= $editDriver['id']??'' ?>">
        <div class="adm-grid">
            <div class="adm-fld"><label>Prénom *</label><input type="text" name="forename" required value="<?= htmlspecialchars($editDriver['forename']??'') ?>"></div>
            <div class="adm-fld"><label>Nom *</label><input type="text" name="surname" required value="<?= htmlspecialchars($editDriver['surname']??'') ?>"></div>
            <div class="adm-fld"><label>Nationalité</label><input type="text" name="nationality" value="<?= htmlspecialchars($editDriver['nationality']??'') ?>"></div>
            <div class="adm-fld"><label>Date de naissance</label><input type="date" name="dob" value="<?= htmlspecialchars($editDriver['dob']??'') ?>"></div>
        </div>
        <div class="adm-actions">
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
<div class="adm-tbl-top">
    <span class="adm-count"><?= count($drivers) ?> résultat<?= count($drivers)>1?'s':'' ?> (max 10)</span>
    <a href="?tab=drivers&add_driver=1<?= $dq?'&dq='.urlencode($dq):'' ?>" class="btn-primary">+ Ajouter</a>
</div>
<table class="atbl">
    <thead><tr><th>#</th><th>Nom</th><th>Prénom</th><th>Nationalité</th><th>Naissance</th><th></th></tr></thead>
    <tbody>
    <?php if (empty($drivers)): ?><tr class="empty-row"><td colspan="6">Aucun résultat.</td></tr>
    <?php endif; ?>
    <?php foreach ($drivers as $d): ?>
    <tr>
        <td><?= $d['id'] ?></td>
        <td><strong style="font-family:var(--fd);font-size:1rem;text-transform:uppercase"><?= htmlspecialchars($d['surname']) ?></strong></td>
        <td><?= htmlspecialchars($d['forename']) ?></td>
        <td><?= htmlspecialchars($d['nationality']) ?></td>
        <td><?= ($d['dob']&&$d['dob']!=='0000-00-00')?date('d M Y',strtotime($d['dob'])):'—' ?></td>
        <td><div class="acts">
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

<?php elseif ($tab==='constructors'): ?>
<div class="adm-title">Constructors</div>
<div class="adm-sub">10 résultats max — Create · Update · Delete</div>

<?php if ($editConstructor!==null): $isNew=empty($editConstructor); ?>
<div class="adm-form">
    <div class="adm-form-ttl"><?= $isNew?'+ Nouveau constructeur':'✎ Modifier #'.$editConstructor['id'] ?></div>
    <form method="POST">
        <input type="hidden" name="_action" value="constructor_save">
        <input type="hidden" name="id" value="<?= $editConstructor['id']??'' ?>">
        <div class="adm-grid">
            <div class="adm-fld"><label>Nom *</label><input type="text" name="name" required value="<?= htmlspecialchars($editConstructor['name']??'') ?>"></div>
            <div class="adm-fld"><label>Nationalité</label><input type="text" name="nationality" value="<?= htmlspecialchars($editConstructor['nationality']??'') ?>"></div>
        </div>
        <div class="adm-actions">
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
<div class="adm-tbl-top">
    <span class="adm-count"><?= count($constructors) ?> résultat<?= count($constructors)>1?'s':'' ?> (max 10)</span>
    <a href="?tab=constructors&add_constructor=1<?= $cq?'&cq='.urlencode($cq):'' ?>" class="btn-primary">+ Ajouter</a>
</div>
<table class="atbl">
    <thead><tr><th>#</th><th>Nom</th><th>Nationalité</th><th></th></tr></thead>
    <tbody>
    <?php if (empty($constructors)): ?><tr class="empty-row"><td colspan="4">Aucun résultat.</td></tr>
    <?php endif; ?>
    <?php foreach ($constructors as $c): ?>
    <tr>
        <td><?= $c['id'] ?></td>
        <td><strong style="font-family:var(--fd);font-size:1rem;text-transform:uppercase"><?= htmlspecialchars($c['name']) ?></strong></td>
        <td><?= htmlspecialchars($c['nationality']) ?></td>
        <td><div class="acts">
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

<script>
function prevHero(i){if(!i.files||!i.files[0])return;document.getElementById('prev-img').src=URL.createObjectURL(i.files[0]);document.getElementById('prev-wrap').style.display='block';}
const dz=document.getElementById('dz');
if(dz){dz.addEventListener('dragover',e=>{e.preventDefault();dz.classList.add('drag')});dz.addEventListener('dragleave',()=>dz.classList.remove('drag'));dz.addEventListener('drop',()=>dz.classList.remove('drag'));}
</script>
</body></html>