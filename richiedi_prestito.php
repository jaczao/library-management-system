<?php
// richiedi_prestito.php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['ruolo'] !== 'lettore') {
    header("Location: login.php");
    exit;
}
require 'db_connection.php';
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isbn  = $_POST['isbn'];
    $sede  = $_POST['sede']; // opzionale
    if (empty($sede)) {
        $sede = null;
    }
    // Si richiama la funzione SQL assegna_prestito
    $stmt = $pdo->prepare("SELECT assegna_prestito(:cf, :isbn, :sede) AS prestito_id");
    $stmt->execute([
       'cf'   => $_SESSION['user']['codice_fiscale'],
       'isbn' => $isbn,
       'sede' => $sede
    ]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && $result['prestito_id']) {
        $message = "Prestito assegnato con successo (ID: " . $result['prestito_id'] . ")";
    } else {
        $message = "Errore durante l'assegnazione del prestito.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
   <meta charset="UTF-8">
   <title>Richiedi Prestito</title>
</head>
<body>
   <h2>Richiedi Prestito</h2>
   <?php if ($message): ?>
      <p><?php echo htmlspecialchars($message); ?></p>
   <?php endif; ?>
   <form method="post" action="">
       <label>ISBN del libro:</label>
       <input type="text" name="isbn" required><br/><br/>
       <label>ID Sede (opzionale):</label>
       <input type="text" name="sede"><br/><br/>
       <input type="submit" value="Richiedi Prestito">
   </form>
   <a href="lettore_home.php">Torna a Home</a>
</body>
</html>
