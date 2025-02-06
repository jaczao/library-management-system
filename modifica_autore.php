<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

if (!isset($_GET['id'])) {
    echo "Autore non specificato.";
    exit;
}

$id_autore = intval($_GET['id']);
$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $cognome = trim($_POST['cognome'] ?? '');
    $data_nascita = trim($_POST['data_nascita'] ?? '');
    $data_morte = trim($_POST['data_morte'] ?? '');
    $biografia = trim($_POST['biografia'] ?? '');
    
    if (empty($nome) || empty($cognome) || empty($data_nascita) || empty($biografia)) {
        $error = "Nome, cognome, data di nascita e biografia sono obbligatori.";
    } else {
        try {
            $sqlUpdate = "UPDATE autore SET nome = ?, cognome = ?, data_nascita = ?, data_morte = ?, biografia = ? WHERE id_autore = ?";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([$nome, $cognome, $data_nascita, $data_morte, $biografia, $id_autore]);
            $message = "Autore aggiornato con successo.";
        } catch (PDOException $e) {
            $error = "Errore nell'aggiornamento dell'autore: " . htmlspecialchars($e->getMessage());
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM autore WHERE id_autore = ?");
$stmt->execute([$id_autore]);
$autore = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$autore) {
    echo "Autore non trovato.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Autore - BiblioStudio</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1>Modifica Autore</h1>
    <?php if ($message): ?>
        <div class="alert success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" action="modifica_autore.php?id=<?php echo $id_autore; ?>">
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($autore['nome']); ?>" required>
        
        <label for="cognome">Cognome:</label>
        <input type="text" id="cognome" name="cognome" value="<?php echo htmlspecialchars($autore['cognome']); ?>" required>
        
        <label for="data_nascita">Data di Nascita:</label>
        <input type="date" id="data_nascita" name="data_nascita" value="<?php echo htmlspecialchars($autore['data_nascita']); ?>" required>
        
        <label for="data_morte">Data di Morte (se applicabile):</label>
        <input type="date" id="data_morte" name="data_morte" value="<?php echo htmlspecialchars($autore['data_morte']); ?>">
        
        <label for="biografia">Biografia:</label>
        <textarea id="biografia" name="biografia" rows="5" required><?php echo htmlspecialchars($autore['biografia']); ?></textarea>
        
        <button type="submit">Salva Modifiche</button>
    </form>
    <p><a href="dettaglio_autore.php?id=<?php echo $id_autore; ?>">Torna al Dettaglio Autore</a></p>
</div>
</body>
</html>
