<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'lettore') {
    header("Location: login.php?role=lettore");
    exit;
}

require_once 'config.php';

// Verifica che sia passato l'id del libro tramite GET
if (!isset($_GET['id'])) {
    header("Location: cerca_libri.php");
    exit;
}

$id_libro = intval($_GET['id']);

// Recupera i dettagli del libro (inclusi autori)
$sql = "SELECT l.id_libro, l.isbn, l.titolo, l.trama, l.casa_editrice,
               string_agg(a.nome || ' ' || a.cognome, ', ') AS autori
        FROM libro l
        LEFT JOIN libro_autore la ON l.id_libro = la.libro
        LEFT JOIN autore a ON la.autore = a.id_autore
        WHERE l.id_libro = ?
        GROUP BY l.id_libro, l.isbn, l.titolo, l.trama, l.casa_editrice";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_libro]);
$book = $stmt->fetch();

if (!$book) {
    echo "Libro non trovato.";
    exit;
}

// Recupera l'elenco delle sedi
$stmtSedi = $pdo->query("SELECT id_sede, codice_sede, citta, indirizzo FROM sede ORDER BY citta, codice_sede");
$sedi = $stmtSedi->fetchAll();

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Se l'utente ha selezionato una sede, la usiamo; altrimenti null
    $sede_id = (isset($_POST['sede']) && $_POST['sede'] !== "") ? intval($_POST['sede']) : null;
    $lettoreId = $_SESSION['user']['id_lettore'];

    try {
        if ($sede_id !== null) {
            // Chiamata alla funzione assegna_prestito passando anche la sede
            $stmtPrenota = $pdo->prepare("SELECT assegna_prestito(?, ?, ?)");
            $stmtPrenota->execute([$lettoreId, $id_libro, $sede_id]);
        } else {
            // Chiamata alla funzione assegna_prestito senza specificare la sede
            $stmtPrenota = $pdo->prepare("SELECT assegna_prestito(?, ?)");
            $stmtPrenota->execute([$lettoreId, $id_libro]);
        }
        $result = $stmtPrenota->fetch();
        $prestito_id = $result[0];
        $message = "Prenotazione effettuata con successo. ID prestito: " . htmlspecialchars($prestito_id);
    } catch (PDOException $e) {
        $error = "Errore nella prenotazione: " . htmlspecialchars($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dettaglio Libro - BiblioStudio</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .info { margin-bottom: 20px; }
        .message { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Dettaglio Libro</h1>
    <nav>
        <ul>
            <li><a href="home_lettore.php">Home</a></li>
            <li><a href="cerca_libri.php">Cerca Libri</a></li>
            <li><a href="visualizza_prenotazioni.php">Visualizza Prenotazioni</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="info">
        <h2><?php echo htmlspecialchars($book['titolo']); ?></h2>
        <p><strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']); ?></p>
        <p><strong>Autori:</strong> <?php echo htmlspecialchars($book['autori']); ?></p>
        <p><strong>Casa Editrice:</strong> <?php echo htmlspecialchars($book['casa_editrice']); ?></p>
        <p><strong>Trama:</strong><br> <?php echo nl2br(htmlspecialchars($book['trama'])); ?></p>
    </div>

    <?php if ($message): ?>
        <p class="message"><?php echo $message; ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="post" action="dettaglio_libro.php?id=<?php echo urlencode($id_libro); ?>">
        <label for="sede">Seleziona una sede (facoltativo):</label>
        <select name="sede" id="sede">
            <option value="">-- Nessuna sede scelta --</option>
            <?php foreach ($sedi as $sede): ?>
                <option value="<?php echo htmlspecialchars($sede['id_sede']); ?>">
                    <?php echo htmlspecialchars($sede['codice_sede'] . " - " . $sede['citta'] . " (" . $sede['indirizzo'] . ")"); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>
        <button type="submit">Prenota</button>
    </form>
</body>
</html>
