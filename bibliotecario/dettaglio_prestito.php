<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'bibliotecario') {
    header("Location: login.php?role=bibliotecario");
    exit;
}

require_once __DIR__ . '/../config.php';

if (!isset($_GET['id'])) {
    echo "Prestito non specificato.";
    exit;
}

$id_prestito = intval($_GET['id']);
$message = "";
$error = "";

// Gestione delle azioni: segnare la restituzione o prorogare la scadenza
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'restituisci') {
        try {
            // Aggiorna il prestito impostando data_restituzione alla data corrente
            $stmt = $pdo->prepare("UPDATE prestito SET data_restituzione = CURRENT_DATE WHERE id_prestito = ?");
            $stmt->execute([$id_prestito]);
            $message = "Prestito aggiornato: copia restituita.";
        } catch (PDOException $e) {
            $error = "Errore nell'aggiornamento del prestito.";
        }
    } elseif ($_POST['action'] === 'proroga') {
        $new_scadenza = $_POST['new_scadenza'];
        // Verifica che la data sia valida e futura
        if (strtotime($new_scadenza) === false) {
            $error = "Data non valida.";
        } elseif (strtotime($new_scadenza) <= strtotime(date('Y-m-d'))) {
            $error = "La nuova scadenza deve essere una data futura.";
        } else {
            try {
                // Chiamata alla procedura proroga_prestito
                $stmt = $pdo->prepare("CALL proroga_prestito(?, ?)");
                $stmt->execute([$id_prestito, $new_scadenza]);
                $message = "Prestito prorogato con successo.";
            } catch (PDOException $e) {
                $error = "Errore nella proroga del prestito.";
            }
        }
    }
}

// Recupera le informazioni aggiornate del prestito
$sql = "SELECT p.*, l.nome AS lettore_nome, l.cognome AS lettore_cognome,
       b.titolo AS libro_titolo, b.isbn, b.casa_editrice, b.trama
        FROM prestito p
        JOIN lettore l ON p.lettore = l.id_lettore
        JOIN copia c ON p.copia = c.id_copia
        JOIN libro b ON c.libro = b.id_libro
        WHERE p.id_prestito = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_prestito]);
$prestito = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$prestito) {
    echo "Prestito non trovato.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dettaglio Prestito - BiblioStudio</title>
    <link rel="stylesheet" href="/../css/style.css">
</head>
<body>
<div class="container">
    <h1>Dettaglio Prestito</h1>
    <?php if ($message): ?>
        <div class="alert success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <h2>Informazioni Prestito</h2>
    <p><strong>ID Prestito:</strong> <?php echo htmlspecialchars($prestito['id_prestito']); ?></p>
    <p><strong>Data Inizio:</strong> <?php echo htmlspecialchars(date("d/m/Y", strtotime($prestito['data_inizio']))); ?></p>
    <p><strong>Data Scadenza:</strong> <?php echo htmlspecialchars(date("d/m/Y", strtotime($prestito['data_scadenza']))); ?></p>
    <p><strong>Data Restituzione:</strong> <?php echo $prestito['data_restituzione'] ? htmlspecialchars(date("d/m/Y", strtotime($prestito['data_restituzione']))) : '-'; ?></p>
    
    <h2>Informazioni Lettore</h2>
    <p><strong>Nome e Cognome:</strong> <?php echo htmlspecialchars($prestito['lettore_nome'] . ' ' . $prestito['lettore_cognome']); ?></p>

    <h2>Informazioni Libro</h2>
    <p><strong>Titolo:</strong> <?php echo htmlspecialchars($prestito['libro_titolo']); ?></p>
    <p><strong>ISBN:</strong> <?php echo htmlspecialchars($prestito['isbn']); ?></p>
    <p><strong>Casa Editrice:</strong> <?php echo htmlspecialchars($prestito['casa_editrice']); ?></p>
    <p><strong>Trama:</strong><br><?php echo nl2br(htmlspecialchars($prestito['trama'])); ?></p>

    <!-- Se il prestito è ancora in corso, mostra le opzioni per segnarne la restituzione e prorogarlo -->
    <?php if (!$prestito['data_restituzione']): ?>
    <form method="post" action="dettaglio_prestito.php?id=<?php echo $id_prestito; ?>">
        <input type="hidden" name="action" value="restituisci">
        <button type="submit">Segna restituzione</button>
    </form>
    
    <form method="post" action="dettaglio_prestito.php?id=<?php echo $id_prestito; ?>">
        <input type="hidden" name="action" value="proroga">
        <label for="new_scadenza">Nuova data di scadenza:</label>
        <input type="date" name="new_scadenza" id="new_scadenza" required>
        <button type="submit">Proroga prestito</button>
    </form>
    <?php endif; ?>

    <a href="elenco_prestiti.php">Torna all'elenco dei prestiti</a>
</div>
</body>
</html>
