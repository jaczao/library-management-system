<?php
session_start();
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

// Gestione della modifica, se il form è stato inviato
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isbn = trim($_POST['isbn'] ?? '');
    $titolo = trim($_POST['titolo'] ?? '');
    $trama = trim($_POST['trama'] ?? '');
    $casa_editrice = trim($_POST['casa_editrice'] ?? '');
    
    if (empty($isbn) || empty($titolo)) {
        $error = "ISBN e Titolo sono obbligatori.";
    } else {
        try {
            $sqlUpdate = "UPDATE libro SET isbn = ?, titolo = ?, trama = ?, casa_editrice = ? WHERE id_libro = ?";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([$isbn, $titolo, $trama, $casa_editrice, $id_libro]);
            $message = "Libro aggiornato con successo.";
        } catch (PDOException $e) {
            $error = "Errore durante l'aggiornamento del libro.";
        }
    }
}

// Recupera i dati correnti del libro
$sql = "SELECT * FROM libro WHERE id_libro = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_libro]);
$libro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$libro) {
    echo "Libro non trovato.";
    exit;
}

// Recupera gli autori associati al libro
$sqlAutori = "SELECT a.id_autore, a.nome, a.cognome
              FROM autore a
              JOIN libro_autore la ON a.id_autore = la.autore
              WHERE la.libro = ?";
$stmtAutori = $pdo->prepare($sqlAutori);
$stmtAutori->execute([$id_libro]);
$autori = $stmtAutori->fetchAll(PDO::FETCH_ASSOC);
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
        <textarea id="trama" name="trama" rows="5"><?php echo htmlspecialchars($libro['trama']); ?></textarea>
        
        <label for="casa_editrice">Casa Editrice:</label>
        <input type="text" id="casa_editrice" name="casa_editrice" value="<?php echo htmlspecialchars($libro['casa_editrice']); ?>">
        
        <button type="submit">Salva Modifiche</button>
    </form>
    
    <h2>Autori del Libro</h2>
    <?php if (count($autori) === 0): ?>
        <p>Nessun autore associato.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($autori as $autore): ?>
                <li>
                    <a href="autore.php?id=<?php echo urlencode($autore['id_autore']); ?>">
                        <?php echo htmlspecialchars($autore['nome'] . " " . $autore['cognome']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    
    <a href="dettaglio_libro.php?id=<?php echo $id_libro; ?>">Torna al Dettaglio Libro</a>
</div>
</body>
</html>
