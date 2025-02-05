<?php
session_start();
require_once 'config.php';

if (!isset($_GET['id'])) {
    echo "Libro non specificato.";
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
$libro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$libro) {
    echo "Libro non trovato.";
    exit;
}
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
    <h2><?php echo htmlspecialchars($libro['titolo']); ?></h2>
    <p><strong>ISBN:</strong> <?php echo htmlspecialchars($libro['isbn']); ?></p>
    <p><strong>Casa Editrice:</strong> <?php echo htmlspecialchars($libro['casa_editrice']); ?></p>
    <p><strong>Trama:</strong><br><?php echo nl2br(htmlspecialchars($libro['trama'])); ?></p>
    <p><strong>Autori:</strong> <?php echo htmlspecialchars($libro['autori']); ?></p>
    
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'lettore'): ?>
        <!-- Se l'utente è un lettore, visualizza le funzionalità di prenotazione (già implementate) -->
        <form method="post" action="prenota_libro.php?id=<?php echo $libro['id_libro']; ?>">
            <label for="sede">Seleziona una sede (facoltativo):</label>
            <select name="sede" id="sede">
                <option value="">-- Nessuna sede scelta --</option>
                <?php
                // Recupera l'elenco delle sedi
                $stmtSedi = $pdo->query("SELECT id_sede, codice_sede, citta, indirizzo FROM sede ORDER BY citta, codice_sede");
                $sedi = $stmtSedi->fetchAll(PDO::FETCH_ASSOC);
                foreach ($sedi as $sede):
                ?>
                    <option value="<?php echo htmlspecialchars($sede['id_sede']); ?>">
                        <?php echo htmlspecialchars($sede['codice_sede'] . " - " . $sede['citta'] . " (" . $sede['indirizzo'] . ")"); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br>
            <button type="submit">Prenota</button>
        </form>
    
    <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'bibliotecario'): ?>
        <!-- Se l'utente è un bibliotecario, visualizza le opzioni di gestione -->
        <div class="biblio-management">
            <p>
                <a href="modifica_libro.php?id=<?php echo $libro['id_libro']; ?>">Modifica libro</a> |
                <a href="rimuovi_libro.php?id=<?php echo $libro['id_libro']; ?>" onclick="return confirm('Sei sicuro di voler rimuovere questo libro?');">Rimuovi libro</a>
            </p>
            
            <h3>Gestione delle copie per questo libro</h3>
            <?php
            // Recupera l'elenco delle sedi con il numero totale di copie e copie disponibili per questo libro
            $sql_copie = "SELECT s.id_sede, s.codice_sede, s.citta, s.indirizzo,
                           COUNT(*) AS totale_copie,
                           SUM(CASE WHEN c.disponibile THEN 1 ELSE 0 END) AS copie_disponibili
                           FROM copia c
                           JOIN sede s ON c.sede = s.id_sede
                           WHERE c.libro = ?
                           GROUP BY s.id_sede, s.codice_sede, s.citta, s.indirizzo
                           ORDER BY s.citta, s.codice_sede";
            $stmtCopie = $pdo->prepare($sql_copie);
            $stmtCopie->execute([$id_libro]);
            $copie = $stmtCopie->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <?php if (count($copie) === 0): ?>
                <p>Non sono presenti copie per questo libro.</p>
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
                    <?php foreach ($copie as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['codice_sede'] . " - " . $row['citta']); ?></td>
                            <td><?php echo htmlspecialchars($row['totale_copie']); ?></td>
                            <td><?php echo htmlspecialchars($row['copie_disponibili']); ?></td>
                            <td>
                                <!-- Form per aggiornare il numero di copie per questa sede -->
                                <form method="post" action="aggiorna_copie.php" style="display:inline;">
                                    <input type="hidden" name="id_libro" value="<?php echo $id_libro; ?>">
                                    <input type="hidden" name="id_sede" value="<?php echo $row['id_sede']; ?>">
                                    <input type="number" name="nuovo_totale" value="<?php echo $row['totale_copie']; ?>" min="0" style="width:60px;">
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
    
    <a href="<?php echo ($_SESSION['role'] === 'bibliotecario') ? 'home_bibliotecario.php' : 'home_lettore.php'; ?>">Torna alla home</a>
</div>
</body>
</html>
