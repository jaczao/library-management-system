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
    echo "Libro non specificato.";
    exit;
}

$id_libro = intval($_GET['id']);
$message = "";
$error = "";

// In fase di GET, recuperiamo i dati correnti del libro e il relativo autore
// Per semplicità, assumiamo che il libro abbia al massimo un autore associato.
$sql = "SELECT l.id_libro, l.isbn, l.titolo, l.trama, l.casa_editrice,
               a.id_autore, a.nome AS autore_nome, a.cognome AS autore_cognome
        FROM libro l
        LEFT JOIN libro_autore la ON l.id_libro = la.libro
        LEFT JOIN autore a ON la.autore = a.id_autore
        WHERE l.id_libro = ?
        GROUP BY l.id_libro, l.isbn, l.titolo, l.trama, l.casa_editrice, a.id_autore, a.nome, a.cognome";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_libro]);
$libro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$libro) {
    echo "Libro non trovato.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Raccogliamo i dati del libro
    $isbn = trim($_POST['isbn'] ?? '');
    $titolo = trim($_POST['titolo'] ?? '');
    $trama = trim($_POST['trama'] ?? '');
    $casa_editrice = trim($_POST['casa_editrice'] ?? '');
    
    // Raccogliamo i dati dell'autore
    $nome_autore = trim($_POST['nome_autore'] ?? '');
    $cognome_autore = trim($_POST['cognome_autore'] ?? '');
    
    if (empty($isbn) || empty($titolo) || empty($trama) || empty($casa_editrice) || empty($nome_autore) || empty($cognome_autore)) {
        $error = "Tutti i campi sono obbligatori.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Aggiorna il libro
            $sqlUpdate = "UPDATE libro SET isbn = ?, titolo = ?, trama = ?, casa_editrice = ? WHERE id_libro = ?";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([$isbn, $titolo, $trama, $casa_editrice, $id_libro]);
            
            // Gestione autore:
            // Cerca autore esistente (case-insensitive)
            $sqlAutore = "SELECT id_autore FROM autore WHERE LOWER(nome) = LOWER(?) AND LOWER(cognome) = LOWER(?)";
            $stmtAutore = $pdo->prepare($sqlAutore);
            $stmtAutore->execute([$nome_autore, $cognome_autore]);
            $autore = $stmtAutore->fetch(PDO::FETCH_ASSOC);
            
            if ($autore) {
                $id_autore = $autore['id_autore'];
            } else {
                // Se non esiste, crealo con dati predefiniti per gli altri campi
                $defaultDataNascita = '1900-01-01';
                $defaultBiografia = 'N/A';
                $sqlInsertAutore = "INSERT INTO autore (nome, cognome, data_nascita, biografia) VALUES (?, ?, ?, ?)
                                    RETURNING id_autore";
                $stmtInsertAutore = $pdo->prepare($sqlInsertAutore);
                $stmtInsertAutore->execute([$nome_autore, $cognome_autore, $defaultDataNascita, $defaultBiografia]);
                $id_autore = $stmtInsertAutore->fetchColumn();
            }
            
            // Rimuovi eventuali associazioni precedenti nella tabella libro_autore per questo libro
            $sqlDeleteAssoc = "DELETE FROM libro_autore WHERE libro = ?";
            $stmtDeleteAssoc = $pdo->prepare($sqlDeleteAssoc);
            $stmtDeleteAssoc->execute([$id_libro]);
            
            // Crea nuova associazione
            $sqlInsertAssoc = "INSERT INTO libro_autore (libro, autore) VALUES (?, ?)";
            $stmtInsertAssoc = $pdo->prepare($sqlInsertAssoc);
            $stmtInsertAssoc->execute([$id_libro, $id_autore]);
            
            $pdo->commit();
            $message = "Libro e autore aggiornati con successo.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Errore durante l'aggiornamento: " . htmlspecialchars($e->getMessage());
        }
    }
    
    // Dopo la modifica, ricarichiamo i dati aggiornati
    $stmt->execute([$id_libro]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Libro - <?php echo htmlspecialchars($libro['titolo']); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1>Modifica Libro</h1>
    
    <?php if ($message): ?>
        <div class="alert success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="post" action="modifica_libro.php?id=<?php echo $id_libro; ?>">
        <label for="isbn">ISBN:</label>
        <input type="text" id="isbn" name="isbn" value="<?php echo htmlspecialchars($libro['isbn']); ?>" required>
        
        <label for="titolo">Titolo:</label>
        <input type="text" id="titolo" name="titolo" value="<?php echo htmlspecialchars($libro['titolo']); ?>" required>
        
        <label for="trama">Trama:</label>
        <textarea id="trama" name="trama" rows="5" required><?php echo htmlspecialchars($libro['trama']); ?></textarea>
        
        <label for="casa_editrice">Casa Editrice:</label>
        <input type="text" id="casa_editrice" name="casa_editrice" value="<?php echo htmlspecialchars($libro['casa_editrice']); ?>" required>
        
        <h3>Autore</h3>
        <label for="nome_autore">Nome:</label>
        <input type="text" id="nome_autore" name="nome_autore" value="<?php echo htmlspecialchars($libro['autore_nome'] ?? ''); ?>" required>
        
        <label for="cognome_autore">Cognome:</label>
        <input type="text" id="cognome_autore" name="cognome_autore" value="<?php echo htmlspecialchars($libro['autore_cognome'] ?? ''); ?>" required>
        
        <button type="submit" name="update_book">Salva Modifiche</button>
    </form>
    <p><a href="dettaglio_libro.php?id=<?php echo $id_libro; ?>">Torna al Dettaglio Libro</a></p>
</div>
</body>
</html>
