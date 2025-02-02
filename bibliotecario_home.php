<?php
// bibliotecario_home.php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['ruolo'] !== 'bibliotecario') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
   <meta charset="UTF-8">
   <title>Home Bibliotecario</title>
</head>
<body>
   <h2>Benvenuto, <?php echo htmlspecialchars($_SESSION['user']['username']); ?></h2>
   <ul>
      <li><a href="gestione_lettori.php">Gestione Lettori</a></li>
      <li><a href="gestione_libri.php">Gestione Libri</a></li>
      <li><a href="gestione_sedi.php">Gestione Sedi</a></li>
      <li><a href="proroga_prestito.php">Proroga Prestito</a></li>
      <li><a href="azzera_ritardi.php">Azzera Ritardi</a></li>
      <li><a href="logout.php">Logout</a></li>
   </ul>
</body>
</html>
