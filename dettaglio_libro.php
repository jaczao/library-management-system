<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

if (!isset($_GET['id'])) {
    echo "Libro non specificato.";
    exit;
}

$id_libro = intval($_GET['id']);
$message = "";
$error = "";

// Gestione delle azioni in POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Azione di prenotazione per i lettori
    if ($action === 'prenota' && $_SESSION['role'] === 'lettore') {
        $sede_id = (isset($_POST['sede']) && $_POST['sede'] !== "") ? intval($_POST['sede']) : null;
        try {
            if ($sede_id !== null) {
                $stmtPrenota = $pdo->prepare("SELECT assegna_prestito(?, ?, ?)");
                $stmtPrenota->execute([$_SESSION['user']['id_lettore'], $id_libro, $sede_id]);
            } else {
                $stmtPrenota = $pdo->prepare("SELECT assegna_prestito(?, ?)");
                $stmtPrenota->execute([$_SESSION['user']['id_lettore'], $id_libro]);
            }
            $result = $stmtPrenota->fetch(PDO::FETCH_NUM);
            $message = "Prenotazione effettuata con successo.";
        } catch (PDOException $e) {
            $error = "Errore nella prenotazione: " . htmlspecialchars($e->getMessage());
        }
    }
    // Azione di aggiornamento copie per il bibliotecario
    elseif ($action === 'aggiorna_copie' && $_SESSION['role'] === 'bibliotecario') {
        $id_sede = intval($_POST['id_sede']);
        $nuovo_totale = intval($_POST['nuovo_totale']);

        // Recupera il totale attuale di copie per questo libro e sede
        $sqlCopie = "SELECT COUNT(*) AS totale FROM copia WHERE libro = ? AND sede = ?";
        $stmtCopie = $pdo->prepare($sqlCopie);
        $stmtCopie->execute([$id_libro, $id_sede]);
        $row = $stmtCopie->fetch(PDO::FETCH_ASSOC);
        $totale_corrente = $row ? intval($row['totale']) : 0;

        // Recupera il numero di copie disponibili (cioè non in prestito) per questo libro e sede
        $sqlDisponibili = "SELECT COUNT(*) AS disponibili FROM copia WHERE libro = ? AND sede = ? AND disponibile = TRUE";
        $stmtDisp = $pdo->prepare($sqlDisponibili);
        $stmtDisp->execute([$id_libro, $id_sede]);
        $rowDisp = $stmtDisp->fetch(PDO::FETCH_ASSOC);
        $copie_disponibili = $rowDisp ? intval($rowDisp['disponibili']) : 0;

        try {
            if ($nuovo_totale > $totale_corrente) {
                // Aggiungi copie: inserisci (nuovo_totale - totale_corrente) nuove copie
                $da_aggiungere = $nuovo_totale - $totale_corrente;
                $stmtInsert = $pdo->prepare("INSERT INTO copia (libro, sede, disponibile) VALUES (?, ?, TRUE)");
                for ($i = 0; $i < $da_aggiungere; $i++) {
                    $stmtInsert->execute([$id_libro, $id_sede]);
                }
                $message = "Aggiornamento copie: aggiunte $da_aggiungere copie.";
            } elseif ($nuovo_totale < $totale_corrente) {
                // Calcola quante copie si intende rimuovere
                $da_rimuovere = $totale_corrente - $nuovo_totale;
                // Controlla che non si rimuovano più copie di quelle disponibili
                if ($da_rimuovere > $copie_disponibili) {
                    $error = "Errore: non puoi rimuovere $da_rimuovere copie perché solo $copie_disponibili sono disponibili.";
                } else {
                    $stmtDelete = $pdo->prepare("DELETE FROM copia WHERE id_copia IN (
                        SELECT id_copia FROM copia 
                        WHERE libro = ? AND sede = ? AND disponibile = TRUE
                        LIMIT ?
                    )");
                    $stmtDelete->execute([$id_libro, $id_sede, $da_rimuovere]);
                    $message = "Aggiornamento copie: rimosse $da_rimuovere copie.";
                }
            } else {
                $message = "Nessun aggiornamento necessario per le copie.";
            }
        } catch (PDOException $e) {
            $error = "Errore nell'aggiornamento delle copie: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Recupera i dettagli del libro (senza aggregare gli autori, per gestirli separatamente)
$sqlLibro = "SELECT id_libro, isbn, titolo, trama, casa_editrice FROM libro WHERE id_libro = ?";
$stmtLibro = $pdo->prepare($sqlLibro);
$stmtLibro->execute([$id_libro]);
$libro = $stmtLibro->fetch(PDO::FETCH_ASSOC);
if (!$libro) {
    echo "Libro non trovato.";
    exit;
}

// Recupera gli autori associati al libro (come record separati)
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
    <title>Dettaglio Libro - <?php echo htmlspecialchars($libro['titolo']); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1>Dettaglio Libro</h1>
    <?php if (!empty($message)): ?>
        <div class="alert success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <h2><?php echo htmlspecialchars($libro['titolo']); ?></h2>
    <p><strong>ISBN:</strong> <?php echo htmlspecialchars($libro['isbn']); ?></p>
    <p><strong>Casa Editrice:</strong> <?php echo htmlspecialchars($libro['casa_editrice']); ?></p>
    <p><strong>Trama:</strong><br><?php echo nl2br(htmlspecialchars($libro['trama'])); ?></p>
    
    <p><strong>Autori:</strong>
        <?php if (count($autori) === 0): ?>
            Nessun autore associato.
        <?php else: ?>
            <?php foreach ($autori as $index => $autore): ?>
                <a href="dettaglio_autore.php?id=<?php echo urlencode($autore['id_autore']); ?>">
                    <?php echo htmlspecialchars($autore['nome'] . " " . $autore['cognome']); ?>
                </a><?php if ($index < count($autori) - 1) echo ", "; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </p>
    
    <?php if ($_SESSION['role'] === 'lettore'): ?>
        <!-- Sezione per i lettori: modulo di prenotazione -->
        <form method="post" action="dettaglio_libro.php?id=<?php echo $id_libro; ?>">
            <input type="hidden" name="action" value="prenota">
            <label for="sede">Seleziona una sede (facoltativo):</label>
            <select name="sede" id="sede">
                <option value="">-- Nessuna sede scelta --</option>
                <?php
                // Recupera tutte le sedi, anche quelle senza copie per questo libro
                $sqlSedi = "SELECT s.id_sede, s.codice_sede, s.citta, s.indirizzo 
                            FROM sede s
                            ORDER BY s.citta, s.codice_sede";
                $stmtSedi = $pdo->query($sqlSedi);
                $sedi = $stmtSedi->fetchAll(PDO::FETCH_ASSOC);
                foreach ($sedi as $sede):
                    // Conta le copie per questo libro in questa sede
                    $sqlCopie = "SELECT COUNT(*) AS totale, 
                                        SUM(CASE WHEN c.disponibile THEN 1 ELSE 0 END) AS disponibili
                                 FROM copia c
                                 WHERE c.libro = ? AND c.sede = ?";
                    $stmtCopie = $pdo->prepare($sqlCopie);
                    $stmtCopie->execute([$id_libro, $sede['id_sede']]);
                    $row = $stmtCopie->fetch(PDO::FETCH_ASSOC);
                    $totale = $row ? intval($row['totale']) : 0;
                    $disponibili = ($row && $row['disponibili'] !== null) ? intval($row['disponibili']) : 0;
                ?>
                    <option value="<?php echo htmlspecialchars($sede['id_sede']); ?>">
                        <?php echo htmlspecialchars($sede['codice_sede'] . " - " . $sede['citta'] . " (" . $sede['indirizzo'] . ") - Disponibili: $disponibili"); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Prenota</button>
        </form>
    <?php elseif ($_SESSION['role'] === 'bibliotecario'): ?>
        <!-- Sezione per il bibliotecario: gestione del libro -->
        <div class="biblio-management">
            <!-- Link per modificare il libro -->
            <div class="inline-links"> 
            <p><a href="modifica_libro.php?id=<?php echo $id_libro; ?>">Modifica libro</a></p>
            </div>

            <!-- Gestione delle copie per questo libro -->
            <h3>Gestione delle copie</h3>
            <?php
            // Recupera tutte le sedi, anche quelle senza copie per questo libro
            $sqlSedi = "SELECT s.id_sede, s.codice_sede, s.citta, s.indirizzo 
                        FROM sede s
                        ORDER BY s.citta, s.codice_sede";
            $stmtSedi = $pdo->query($sqlSedi);
            $sedi = $stmtSedi->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <?php if (count($sedi) === 0): ?>
                <p>Nessuna sede trovata.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Sede</th>
                            <th>Totale Copie</th>
                            <th>Copie Disponibili</th>
                            <th>Aggiorna copie</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($sedi as $sede): 
                        // Calcola il numero di copie per questa sede per il libro corrente
                        $sqlCopie = "SELECT COUNT(*) AS totale, 
                                            SUM(CASE WHEN c.disponibile THEN 1 ELSE 0 END) AS disponibili
                                     FROM copia c
                                     WHERE c.libro = ? AND c.sede = ?";
                        $stmtCopie = $pdo->prepare($sqlCopie);
                        $stmtCopie->execute([$id_libro, $sede['id_sede']]);
                        $row = $stmtCopie->fetch(PDO::FETCH_ASSOC);
                        $totale = $row ? intval($row['totale']) : 0;
                        $disponibili = ($row && $row['disponibili'] !== null) ? intval($row['disponibili']) : 0;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sede['codice_sede'] . " - " . $sede['citta']); ?></td>
                            <td><?php echo htmlspecialchars($totale); ?></td>
                            <td><?php echo htmlspecialchars($disponibili); ?></td>
                            <td>
                                <form method="post" action="dettaglio_libro.php?id=<?php echo $id_libro; ?>" style="display:inline;">
                                    <input type="hidden" name="action" value="aggiorna_copie">
                                    <input type="hidden" name="id_sede" value="<?php echo $sede['id_sede']; ?>">
                                    <input type="number" name="nuovo_totale" value="<?php echo $totale; ?>" min="0" style="width:60px;">
                                    <button type="submit">Aggiorna</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="inline-links">
    <a href="<?php echo ($_SESSION['role'] === 'bibliotecario') ? 'home_bibliotecario.php' : 'home_lettore.php'; ?>">Torna alla home</a>
    </div>
</div>
</body>
</html>
