<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'bibliotecario') {
    header("Location: /login.php?role=bibliotecario");
    exit;
}
require_once __DIR__ . '/../config.php';

$message = "";
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codice_sede = trim($_POST['codice_sede'] ?? '');
    $citta = trim($_POST['citta'] ?? '');
    $indirizzo = trim($_POST['indirizzo'] ?? '');
    
    if (empty($codice_sede) || empty($citta) || empty($indirizzo)) {
        $error = "Tutti i campi sono obbligatori.";
    } else {
        try {
            $sql = "INSERT INTO sede (codice_sede, citta, indirizzo) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$codice_sede, $citta, $indirizzo]);
            $message = "Sede creata con successo.";
        } catch (PDOException $e) {
            $error = "Errore nella creazione della sede: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Crea Sede - BiblioStudio</title>
    <link rel="stylesheet" href="/../css/style.css">
</head>
<body>
<div class="container">
    <h1>Crea Sede</h1>
    <?php if (!empty($message)): ?>
        <div class="alert success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" action="crea_sede.php">
        <label for="codice_sede">Codice Sede:</label>
        <input type="text" id="codice_sede" name="codice_sede" required>
        
        <label for="citta">Città:</label>
        <input type="text" id="citta" name="citta" required>
        
        <label for="indirizzo">Indirizzo:</label>
        <input type="text" id="indirizzo" name="indirizzo" required>
        
        <button type="submit">Crea Sede</button>
    </form>
    <div class="inline-links"><p><a href="cerca_sede.php">Torna alla ricerca sedi</a></p></div>
</div>
</body>
</html>
