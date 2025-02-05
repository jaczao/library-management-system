<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
require_once 'config.php';

$message = "";
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isbn = trim($_POST['isbn'] ?? '');
    $titolo = trim($_POST['titolo'] ?? '');
    $trama = trim($_POST['trama'] ?? '');
    $casa_editrice = trim($_POST['casa_editrice'] ?? '');
    
    if (empty($isbn) || empty($titolo) || empty($trama) || empty($casa_editrice)) {
        $error = "Tutti i campi sono obbligatori.";
    } else {
        try {
            $sql = "INSERT INTO libro (isbn, titolo, trama, casa_editrice) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$isbn, $titolo, $trama, $casa_editrice]);
            $message = "Nuovo libro aggiunto con successo.";
        } catch (PDOException $e) {
            $error = "Errore nell'aggiunta del libro: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Aggiungi Nuovo Libro - BiblioStudio</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1>Aggiungi Nuovo Libro</h1>
    <?php if (!empty($message)): ?>
        <div class="alert success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" action="aggiungi_nuovo_libro.php">
        <label for="isbn">ISBN:</label>
        <input type="text" id="isbn" name="isbn" required>
        
        <label for="titolo">Titolo:</label>
        <input type="text" id="titolo" name="titolo" required>
        
        <label for="trama">Trama:</label>
        <textarea id="trama" name="trama" rows="5" required></textarea>
        
        <label for="casa_editrice">Casa Editrice:</label>
        <input type="text" id="casa_editrice" name="casa_editrice" required>
        
        <button type="submit">Aggiungi Libro</button>
    </form>
    <p><a href="cerca_libri.php">Torna alla ricerca libri</a></p>
</div>
</body>
</html>
