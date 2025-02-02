<?php
// ricerca_libri.php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['ruolo'] !== 'lettore') {
    header("Location: login.php");
    exit;
}
require 'db_connection.php';
$books = [];
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $stmt = $pdo->prepare("SELECT * FROM libro WHERE isbn ILIKE :search OR titolo ILIKE :search");
    $stmt->execute(['search' => '%'.$search.'%']);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
   <meta charset="UTF-8">
   <title>Ricerca Libri</title>
</head>
<body>
   <h2>Ricerca Libri</h2>
   <form method="get" action="">
      <input type="text" name="search" placeholder="Inserisci ISBN o titolo" required>
      <input type="submit" value="Cerca">
   </form>
   <h3>Risultati:</h3>
   <ul>
      <?php foreach ($books as $book): ?>
         <li>
           <?php echo htmlspecialchars($book['titolo']); ?> (ISBN: <?php echo htmlspecialchars($book['isbn']); ?>)
         </li>
      <?php endforeach; ?>
   </ul>
   <a href="lettore_home.php">Torna a Home</a>
</body>
</html>
