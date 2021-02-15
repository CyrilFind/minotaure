<?php
session_start();
include "connexion.php";

$nom = $_POST['nom'];
$pass = $_POST['pass'];
$stat = $_POST['stat'];
$probleme = NULL;

if (empty($nom) || empty($pass)) {
  $probleme = 'Veuillez remplir le champ : ' . (empty($nom) ? 'nom' : 'mot de passe') . '.';
}
elseif (preg_match('/^[A-Za-z0-9-]+$/D', $nom) === 0) {
  $probleme = 'Veuillez utiliser uniquement des chiffres et des lettres pour votre login.';
}
else {
  $nom = strtolower($nom);
  $pass = $pass . substr($nom, 0, 3) . substr($nom, -1);
  $pass = md5($pass);
  $stmt = $db->prepare("SELECT id FROM hrpg WHERE nom=:nom");
  $stmt->execute([
          ':nom' => $nom,
  ]);

  $row = $stmt->fetch();
  $id = $row[0];
  if (!empty($id)) {
    $probleme = 'Ce nom est déjà utilisé. Veuillez en choisir un autre.';
  }
}
include 'header.php'; ?>
<div>
  <?php
  if (empty($probleme)) {
    if (empty($stat)) {
      $carac2 = array_rand([1, 2, 4, 5], 1);
      $carac1 = 6 - $carac2;
    }
    else {
      $carac2 = $stat[1];
      $carac1 = $stat[0];
    }
    $hp = 5 + rand(0, 5);
    $stmt = $db->prepare("SELECT id FROM tag WHERE category = 1 ORDER BY RAND()");
    $stmt->execute();
    $row = $stmt->fetch();
    $tags[] = $row[0];

    $stmt = $db->prepare("SELECT id FROM tag WHERE category = 2 ORDER BY RAND()");
    $stmt->execute();
    $row = $stmt->fetch();
    $tags[] = $row[0];

    $stmt = $db->prepare("SELECT id FROM tag WHERE category = 3 ORDER BY RAND()");
    $stmt->execute();
    $row = $stmt->fetch();
    $tags[] = $row[0];

    try {
      $stmt = $db->prepare("INSERT INTO hrpg (nom,mdp,carac2,carac1,hp,active) VALUES(:nom,:pass,:carac2,:carac1,:hp,:active)");
      $stmt->execute([
        ':nom' => $nom,
        ':pass' => $pass,
        ':carac2' => $carac2,
        ':carac1' => $carac1,
        ':hp' => $hp,
        ':active' => 1
      ]);
      $id = $db->lastInsertId();

      foreach($tags as $tag) {
        if (!empty($tag)) {
          $stmt = $db->prepare("INSERT INTO character_tag (id_player,id_tag) VALUES(:id_player,:id_tag)");
          $stmt->execute([':id_player' => $id, ':id_tag' => $tag]);
        }
      }

    } catch (Exception $e) {
      die($e->getMessage());
    }

    $_SESSION['id'] = $id;
    $_SESSION['nom'] = $nom;
    ?>
    <?php if ($id != 1): ?>
      <div><span class="pj-name"><?php print $nom; ?></span> entre en scène.</div>
      <div>Bienvenue dans notre grande aventure.</div>
      <div><a href="main.php">C'est parti.</a></div>
    <?php else: ?>
      <div>Le compte d'administration a été créé.</div>
      <div>Bienvenue dans votre aventure.</div>
      <div><a href="ecran.php">Aller sur l'écran du MJ.</a></div>
    <?php endif; ?>
    <?php
  }
  else {
    ?>
    <div>Impossible de créer votre personnage 😢.</div>
    <div><?php print $probleme; ?></div>
    <div><a href=new.php>Réessayez</a> ou retournez <a href=index.php>au menu principal</a></div>
    <?php
  }
  ?>
</div>
<?php include "footer.php"; ?>