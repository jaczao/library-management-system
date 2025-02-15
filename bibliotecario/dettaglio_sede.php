<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'bibliotecario') {
    header("Location: /login.php?role=bibliotecario");
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

// Gestione della cancellazione della sede: elimina prestiti, copie e infine la sede
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'elimina_sede') {
    try {
        $pdo->beginTransaction();
        // Elimina i prestiti associati alle copie di questa sede
        $stmtPrestiti = $pdo->prepare("DELETE FROM prestito WHERE copia IN (SELECT id_copia FROM copia WHERE sede = ?)");
        $stmtPrestiti->execute([$id_sede]);
        // Elimina le copie associate alla sede
        $stmtCopie = $pdo->prepare("DELETE FROM copia WHERE sede = ?");
        $stmtCopie->execute([$id_sede]);
        // Elimina la sede
        $stmtSede = $pdo->prepare("DELETE FROM sede WHERE id_sede = ?");
        $stmtSede->execute([$id_sede]);
        $pdo->commit();
        header("Location: cerca_sede.php?message=" . urlencode("Sede eliminata con successo."));
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Errore nell'eliminazione della sede: " . htmlspecialchars($e->getMessage());
    }
}

// (Il resto del file rimane invariato; vedi il file precedente per il recupero di sede, libri e prestiti)
$stmt = $pdo->prepare("SELECT * FROM sede WHERE id_sede = ?");
$stmt->execute([$id_sede]);
$sede = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$sede) {
    echo "Sede non trovata.";
    exit;
}

// Recupera i libri associati alla sede
$sqlLibri = "SELECT DISTINCT b.id_libro, b.titolo, b.isbn
             FROM copia c
             JOIN libro b ON c.libro = b.id_libro
             WHERE c.sede = ?";
$stmtLibri = $pdo->prepare($sqlLibri);
$stmtLibri->execute([$id_sede]);
$libri = $stmtLibri->fetchAll(PDO::FETCH_ASSOC);

// Recupera i prestiti associati alla sede (tramite le copie)
$sqlPrestiti = "SELECT p.id_prestito, p.data_inizio, p.data_scadenza, p.data_restituzione, 
                b.titolo AS libro_titolo
                FROM prestito p
                JOIN copia c ON p.copia = c.id_copia
                JOIN libro b ON c.libro = b.id_libro
                WHERE c.sede = ?
                ORDER BY p.data_inizio DESC";
$stmtPrestiti = $pdo->prepare($sqlPrestiti);
$stmtPrestiti->execute([$id_sede]);
$prestiti = $stmtPrestiti->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dettaglio Sede - BiblioStudio</title>
    <link rel="stylesheet" href="/../css/style.css">
</head>
<body>
<div class="container">
    <h1>Dettaglio Sede</h1>
    <?php if ($message): ?>
        <div class="alert success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <h2><?php echo htmlspecialchars($sede['codice_sede']); ?></h2>
    <p><strong>Città:</strong> <?php echo htmlspecialchars($sede['citta']); ?></p>
    <p><strong>Indirizzo:</strong> <?php echo htmlspecialchars($sede['indirizzo']); ?></p>
    
    <div class="inline-links">
    <p>
        <a href="modifica_sede.php?id=<?php echo $id_sede; ?>">Modifica Sede</a>
    </p>
    </div>
    
    <!-- Form per eliminare la sede -->
    <form method="post" action="dettaglio_sede.php?id=<?php echo $id_sede; ?>" onsubmit="return confirm('Sei sicuro di voler eliminare questa sede? Verranno eliminate anche tutte le copie e i prestiti associati.');">
        <input type="hidden" name="action" value="elimina_sede">
        <button type="submit">Elimina Sede</button>
    </form>
    
    <h2>Libri Associati</h2>
    <?php if (empty($libri)): ?>
        <p>Nessun libro associato a questa sede.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($libri as $libro): ?>
                <li>
                    <a href="/../dettaglio_libro.php?id=<?php echo urlencode($libro['id_libro']); ?>">
                        <?php echo htmlspecialchars($libro['titolo']); ?>
                    </a> (ISBN: <?php echo htmlspecialchars($libro['isbn']); ?>)
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    
    <h2>Prestiti Associati</h2>
    <?php if (empty($prestiti)): ?>
        <p>Nessun prestito associato a questa sede.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID Prestito</th>
                    <th>Libro</th>
                    <th>Data Inizio</th>
                    <th>Data Scadenza</th>
                    <th>Data Restituzione</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prestiti as $prestito): ?>
                    <tr onclick="window.location.href='dettaglio_prestito.php?id=<?php echo urlencode($prestito['id_prestito']); ?>'" style="cursor:pointer;">
                        <td><?php echo htmlspecialchars($prestito['id_prestito']); ?></td>
                        <td><?php echo htmlspecialchars($prestito['libro_titolo']); ?></td>
                        <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($prestito['data_inizio']))); ?></td>
                        <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($prestito['data_scadenza']))); ?></td>
                        <td><?php echo $prestito['data_restituzione'] ? htmlspecialchars(date("d/m/Y", strtotime($prestito['data_restituzione']))) : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <div class="inline-links"><p><a href="cerca_sede.php">Torna alla ricerca sedi</a></p></div>
</div>
</body>
</html>
