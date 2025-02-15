<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Verifica che l'utente loggato sia un bibliotecario
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'bibliotecario') {
    header("Location: login.php?role=bibliotecario");
    exit;
}

require_once 'config.php';

// Il bibliotecario visualizza il profilo di un lettore selezionato tramite parametro GET
if (!isset($_GET['id'])) {
    echo "Lettore non specificato.";
    exit;
}

$id_lettore = intval($_GET['id']);
$message = "";
$error = "";

// Gestione dell'eliminazione del profilo lettore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'elimina_profilo') {
    try {
        $pdo->beginTransaction();
        // Elimina tutti i prestiti relativi al lettore
        $stmt = $pdo->prepare("DELETE FROM prestito WHERE lettore = ?");
        $stmt->execute([$id_lettore]);
        // Elimina il profilo del lettore
        $stmt = $pdo->prepare("DELETE FROM lettore WHERE id_lettore = ?");
        $stmt->execute([$id_lettore]);
        $pdo->commit();
        header("Location: home_bibliotecario.php?message=" . urlencode("Profilo lettore eliminato con successo."));
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Errore durante l'eliminazione del profilo: " . htmlspecialchars($e->getMessage());
    }
}

// Recupera le informazioni del lettore
$stmt = $pdo->prepare("SELECT * FROM lettore WHERE id_lettore = ?");
$stmt->execute([$id_lettore]);
$lettore = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$lettore) {
    echo "Profilo non trovato.";
    exit;
}

// Recupera tutti i prestiti del lettore (sia in corso che non) ordinati dal più recente
$stmt = $pdo->prepare("SELECT * FROM prestito WHERE lettore = ? ORDER BY data_inizio DESC");
$stmt->execute([$id_lettore]);
$prestiti = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Profilo Lettore - BiblioStudio</title>
    <link rel="stylesheet" href="/../css/style.css">
</head>
<body>
<div class="container">
    <h1>Profilo Lettore</h1>
    
    <?php if ($message): ?>
        <div class="alert success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <h2>Informazioni Personali</h2>
    <p><strong>Codice Fiscale:</strong> <?php echo htmlspecialchars($lettore['codice_fiscale']); ?></p>
    <p><strong>Nome:</strong> <?php echo htmlspecialchars($lettore['nome']); ?></p>
    <p><strong>Cognome:</strong> <?php echo htmlspecialchars($lettore['cognome']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($lettore['email']); ?></p>
    <p><strong>Categoria:</strong> <?php echo htmlspecialchars($lettore['categoria']); ?></p>
    <p><strong>Ritardi:</strong> <?php echo htmlspecialchars($lettore['ritardi']); ?></p>
    
    <div class="inline-links">
    <p>
        <a href="modifica_profilo_lettore.php?id=<?php echo $id_lettore; ?>">Modifica Profilo</a>
    </p>
    </div>
    
    <form method="post" action="profilo_lettore.php?id=<?php echo $id_lettore; ?>" onsubmit="return confirm('Sei sicuro di voler eliminare questo profilo? Questa operazione eliminerà anche tutti i prestiti associati.');">
        <input type="hidden" name="action" value="elimina_profilo">
        <button type="submit">Elimina Profilo</button>
    </form>
    
    <h2>Elenco Prestiti</h2>
    <?php if (empty($prestiti)): ?>
        <p>Nessun prestito trovato.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID Prestito</th>
                    <th>Data Inizio</th>
                    <th>Data Scadenza</th>
                    <th>Data Restituzione</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prestiti as $prestito): ?>
                    <tr onclick="window.location.href='dettaglio_prestito.php?id=<?php echo urlencode($prestito['id_prestito']); ?>'" style="cursor:pointer;">
                        <td><?php echo htmlspecialchars($prestito['id_prestito']); ?></td>
                        <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($prestito['data_inizio']))); ?></td>
                        <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($prestito['data_scadenza']))); ?></td>
                        <td><?php echo $prestito['data_restituzione'] ? htmlspecialchars(date("d/m/Y", strtotime($prestito['data_restituzione']))) : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <div class="inline-links"><p><a href="home_bibliotecario.php">Torna alla Home</a></p></div>
</div>
</body>
</html>
