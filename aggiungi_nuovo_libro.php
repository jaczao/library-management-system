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
    // Dati del libro
    $isbn = trim($_POST['isbn'] ?? '');
    $titolo = trim($_POST['titolo'] ?? '');
    $trama = trim($_POST['trama'] ?? '');
    $casa_editrice = trim($_POST['casa_editrice'] ?? '');
    
    // Dati dell'autore
    $nome_autore = trim($_POST['nome_autore'] ?? '');
    $cognome_autore = trim($_POST['cognome_autore'] ?? '');
    
    if (empty($isbn) || empty($titolo) || empty($trama) || empty($casa_editrice) || empty($nome_autore) || empty($cognome_autore)) {
        $error = "Tutti i campi sono obbligatori.";
    } else {
        try {
            $pdo->beginTransaction();
            // Inserisce il nuovo libro
            $sqlLibro = "INSERT INTO libro (isbn, titolo, trama, casa_editrice) VALUES (?, ?, ?, ?)
                         RETURNING id_libro";
            $stmtLibro = $pdo->prepare($sqlLibro);
            $stmtLibro->execute([$isbn, $titolo, $trama, $casa_editrice]);
            $id_libro = $stmtLibro->fetchColumn();
            
            // Gestione autore:
            // Cerca autore esistente (case-insensitive)
            $sqlAutore = "SELECT id_autore FROM autore WHERE LOWER(nome) = LOWER(?) AND LOWER(cognome) = LOWER(?)";
            $stmtAutore = $pdo->prepare($sqlAutore);
            $stmtAutore->execute([$nome_autore, $cognome_autore]);
            $autore = $stmtAutore->fetch(PDO::FETCH_ASSOC);
            if ($autore) {
                $id_autore = $autore['id_autore'];
            } else {
                // Se non esiste, crealo con valori predefiniti
                $defaultDataNascita = '1900-01-01';
                $defaultBiografia = 'N/A';
                $sqlInsertAutore = "INSERT INTO autore (nome, cognome, data_nascita, biografia)
                                    VALUES (?, ?, ?, ?)
                                    RETURNING id_autore";
                $stmtInsertAutore = $pdo->prepare($sqlInsertAutore);
                $stmtInsertAutore->execute([$nome_autore, $cognome_autore, $defaultDataNascita, $defaultBiografia]);
                $id_autore = $stmtInsertAutore->fetchColumn();
            }
            
            // Crea l'associazione in libro_autore
            $sqlAssoc = "INSERT INTO libro_autore (libro, autore) VALUES (?, ?)";
            $stmtAssoc = $pdo->prepare($sqlAssoc);
            $stmtAssoc->execute([$id_libro, $id_autore]);
            
            $pdo->commit();
            $message = "Nuovo libro aggiunto con successo.";
        } catch (PDOException $e) {
            $pdo->rollBack();
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
        
        <h3>Autore</h3>
        <label for="nome_autore">Nome:</label>
        <input type="text" id="nome_autore" name="nome_autore" required>
        
        <label for="cognome_autore">Cognome:</label>
        <input type="text" id="cognome_autore" name="cognome_autore" required>
        
        <button type="submit">Aggiungi Libro</button>
    </form>
    <p><a href="cerca_libri.php">Torna alla ricerca libri</a></p>
</div>
</body>
</html>
