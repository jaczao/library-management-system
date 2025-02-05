<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'bibliotecario') {
    header("Location: login.php?role=bibliotecario");
    exit;
}
require_once 'config.php';

if (!isset($_GET['id'])) {
    echo "Lettore non specificato.";
    exit;
}

$id_lettore = intval($_GET['id']);
$message = "";
$error = "";

// Gestione del form di modifica e del reset dei ritardi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Se viene cliccato il pulsante per azzerare i ritardi
    if (isset($_POST['reset_ritardi'])) {
        try {
            $stmtReset = $pdo->prepare("UPDATE lettore SET ritardi = 0 WHERE id_lettore = ?");
            $stmtReset->execute([$id_lettore]);
            $message = "Ritardi azzerati con successo.";
        } catch (PDOException $e) {
            $error = "Errore nell'azzeramento dei ritardi: " . htmlspecialchars($e->getMessage());
        }
    }
    // Se viene inviato il form per aggiornare il profilo
    if (isset($_POST['update_profile'])) {
        $codice_fiscale = trim($_POST['codice_fiscale'] ?? '');
        $nome = trim($_POST['nome'] ?? '');
        $cognome = trim($_POST['cognome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        
        if (empty($codice_fiscale) || empty($nome) || empty($cognome) || empty($email) || empty($categoria)) {
            $error = "Tutti i campi sono obbligatori.";
        } else {
            try {
                $stmtUpdate = $pdo->prepare("UPDATE lettore SET codice_fiscale = ?, nome = ?, cognome = ?, email = ?, categoria = ? WHERE id_lettore = ?");
                $stmtUpdate->execute([$codice_fiscale, $nome, $cognome, $email, $categoria, $id_lettore]);
                $message = "Profilo aggiornato con successo.";
            } catch (PDOException $e) {
                $error = "Errore nell'aggiornamento del profilo: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// Recupera i dati aggiornati del profilo del lettore
$stmt = $pdo->prepare("SELECT * FROM lettore WHERE id_lettore = ?");
$stmt->execute([$id_lettore]);
$lettore = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$lettore) {
    echo "Profilo non trovato.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Profilo Lettore - BiblioStudio</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1>Modifica Profilo Lettore</h1>
    
    <?php if ($message): ?>
        <div class="alert success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="post" action="modifica_profilo_lettore.php?id=<?php echo $id_lettore; ?>">
        <label for="codice_fiscale">Codice Fiscale:</label>
        <input type="text" id="codice_fiscale" name="codice_fiscale" value="<?php echo htmlspecialchars($lettore['codice_fiscale']); ?>" required>
        
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($lettore['nome']); ?>" required>
        
        <label for="cognome">Cognome:</label>
        <input type="text" id="cognome" name="cognome" value="<?php echo htmlspecialchars($lettore['cognome']); ?>" required>
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($lettore['email']); ?>" required>
        
        <label for="categoria">Categoria:</label>
        <select id="categoria" name="categoria" required>
            <option value="">-- Seleziona categoria --</option>
            <option value="base" <?php if ($lettore['categoria'] === 'base') echo 'selected'; ?>>Base</option>
            <option value="premium" <?php if ($lettore['categoria'] === 'premium') echo 'selected'; ?>>Premium</option>
        </select>
        
        <button type="submit" name="update_profile">Salva Modifiche</button>
    </form>
    
    <h2>Ritardi: <?php echo htmlspecialchars($lettore['ritardi']); ?></h2>
    <form method="post" action="modifica_profilo_lettore.php?id=<?php echo $id_lettore; ?>">
        <button type="submit" name="reset_ritardi" onclick="return confirm('Sei sicuro di voler azzerare i ritardi?');">Azzera Ritardi</button>
    </form>
    
    <p><a href="profilo_lettore.php?id=<?php echo $id_lettore; ?>">Torna al Profilo Lettore</a></p>
</div>
</body>
</html>
