<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'bibliotecario') {
    header("Location: login.php?role=bibliotecario");
    exit;
}

require_once 'config.php';

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codice_fiscale = trim($_POST['codice_fiscale'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $cognome = trim($_POST['cognome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    
    if (empty($codice_fiscale) || empty($nome) || empty($cognome) || empty($email) || empty($categoria)) {
        $error = "Tutti i campi sono obbligatori.";
    } else {
        // Hash della password "1234"
        $password_hash = password_hash("1234", PASSWORD_DEFAULT);
        try {
            $sql = "INSERT INTO lettore (codice_fiscale, nome, cognome, email, password, categoria)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$codice_fiscale, $nome, $cognome, $email, $password_hash, $categoria]);
            $message = "Profilo lettore creato con successo.";
        } catch (PDOException $e) {
            $error = "Errore nella creazione del profilo: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Crea Nuovo Profilo Lettore - BiblioStudio</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1>Crea Nuovo Profilo Lettore</h1>
    <?php if (!empty($message)): ?>
        <div class="alert success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" action="crea_lettore.php">
        <label for="codice_fiscale">Codice Fiscale:</label>
        <input type="text" id="codice_fiscale" name="codice_fiscale" required>
        
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" required>
        
        <label for="cognome">Cognome:</label>
        <input type="text" id="cognome" name="cognome" required>
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        
        <label for="categoria">Categoria:</label>
        <select id="categoria" name="categoria" required>
            <option value="">-- Seleziona categoria --</option>
            <option value="base">Base</option>
            <option value="premium">Premium</option>
        </select>
        
        <button type="submit">Crea Profilo Lettore</button>
    </form>
    <p><a href="cerca_lettore.php">Torna alla ricerca lettori</a></p>
</div>
</body>
</html>
