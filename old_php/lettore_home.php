<?php
// lettore_home.php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['ruolo'] !== 'lettore') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
   <meta charset="UTF-8">
   <title>Home Lettore</title>
</head>
<body>
   <h2>Benvenuto, <?php echo htmlspecialchars($_SESSION['user']['nome']); ?></h2>
   <ul>
      <li><a href="ricerca_libri.php">Ricerca Libri</a></li>
      <li><a href="richiedi_prestito.php">Richiedi Prestito</a></li>
      <li><a href="storico_prestiti.php">Storico Prestiti</a></li>
      <li><a href="logout.php">Logout</a></li>
   </ul>
</body>
</html>
