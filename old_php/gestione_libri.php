<?php
// gestione_libri.php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['ruolo'] !== 'bibliotecario') {
    header("Location: login.php");
    exit;
}
require 'db_connection.php';
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isbn           = $_POST['isbn'];
    $titolo         = $_POST['titolo'];
    $trama          = $_POST['trama'];
    $casa_editrice  = $_POST['casa_editrice'];
    
    $stmt = $pdo->prepare("INSERT INTO libro (isbn, titolo, trama, casa_editrice)
                           VALUES (:isbn, :titolo, :trama, :casa_editrice)");
    try {
        $stmt->execute([
            'isbn'          => $isbn,
            'titolo'        => $titolo,
            'trama'         => $trama,
            'casa_editrice' => $casa_editrice
        ]);
        $message = "Libro aggiunto con successo.";
    } catch (Exception $e) {
        $message = "Errore: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
   <meta charset="UTF-8">
   <title>Gestione Libri</title>
</head>
<body>
   <h2>Gestione Libri</h2>
   <?php if ($message): ?>
      <p><?php echo htmlspecialchars($message); ?></p>
   <?php endif; ?>
   <form method="post" action="">
       <label>ISBN:</label>
       <input type="text" name="isbn" required><br/><br/>
       
       <label>Titolo:</label>
       <input type="text" name="titolo" required><br/><br/>
       
       <label>Trama:</label>
       <textarea name="trama"></textarea><br/><br/>
       
       <label>Casa Editrice:</label>
       <input type="text" name="casa_editrice"><br/><br/>
       
       <input type="submit" value="Aggiungi Libro">
   </form>
   <a href="bibliotecario_home.php">Torna a Home</a>
</body>
</html>
