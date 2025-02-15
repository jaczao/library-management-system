<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'bibliotecario') {
    header("Location: login.php?role=bibliotecario");
    exit;
}
require_once __DIR__ . '/../config.php';

if (!isset($_GET['id'])) {
    echo "Sede non specificata.";
    exit;
}

$id_sede = intval($_GET['id']);
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
            $sql = "UPDATE sede SET codice_sede = ?, citta = ?, indirizzo = ? WHERE id_sede = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$codice_sede, $citta, $indirizzo, $id_sede]);
            $message = "Sede aggiornata con successo.";
        } catch (PDOException $e) {
            $error = "Errore nell'aggiornamento della sede: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Recupera i dati correnti della sede
$stmt = $pdo->prepare("SELECT * FROM sede WHERE id_sede = ?");
$stmt->execute([$id_sede]);
$sede = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$sede) {
    echo "Sede non trovata.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Sede - BiblioStudio</title>
    <link rel="stylesheet" href="/../css/style.css">
</head>
<body>
<div class="container">
    <h1>Modifica Sede</h1>
    <?php if ($message): ?>
        <div class="alert success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="post" action="bibliotecario/modifica_sede.php?id=<?php echo $id_sede; ?>">
        <label for="codice_sede">Codice Sede:</label>
        <input type="text" id="codice_sede" name="codice_sede" value="<?php echo htmlspecialchars($sede['codice_sede']); ?>" required>
        
        <label for="citta">Città:</label>
        <input type="text" id="citta" name="citta" value="<?php echo htmlspecialchars($sede['citta']); ?>" required>
        
        <label for="indirizzo">Indirizzo:</label>
        <input type="text" id="indirizzo" name="indirizzo" value="<?php echo htmlspecialchars($sede['indirizzo']); ?>" required>
        
        <button type="submit">Salva Modifiche</button>
    </form>
    
    <div class="inline-links"><p><a href="bibliotecario/dettaglio_sede.php?id=<?php echo $id_sede; ?>">Torna al Dettaglio Sede</a></p></div>
</div>
</body>
</html>
