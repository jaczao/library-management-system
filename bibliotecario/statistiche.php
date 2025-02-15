<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'bibliotecario') {
    header("Location: login.php?role=bibliotecario");
    exit;
}
require_once __DIR__ . '/../config.php';

// Recupera le statistiche per ogni sede dalla vista sede_statistiche
$sqlStats = "SELECT * FROM sede_statistiche ORDER BY codice_sede";
$stmtStats = $pdo->query($sqlStats);
$sediStats = $stmtStats->fetchAll(PDO::FETCH_ASSOC);

// Recupera il report dei ritardi per ogni sede dalla vista sede_ritardi
$sqlRitardi = "SELECT * FROM sede_ritardi ORDER BY codice_sede";
$stmtRitardi = $pdo->query($sqlRitardi);
$sediRitardi = $stmtRitardi->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Statistiche - BiblioStudio</title>
    <link rel="stylesheet" href="/../css/style.css">
</head>
<body>
<div class="container">
    <h1>Statistiche della Biblioteca</h1>
    <nav>
        <ul>
            <li><a href="bibliotecario/home_bibliotecario.php">Home</a></li>
            <li><a href="modifica_profilo.php">Modifica Profilo</a></li>
            <li><a href="bibliotecario/elenco_prestiti.php">Elenco Prestiti</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    
    <!-- Sezione Statistiche per Sede -->
    <h2>Statistiche per Sede</h2>
    <?php if (count($sediStats) === 0): ?>
        <p>Nessuna statistica disponibile.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Codice Sede</th>
                    <th>Totale Copie</th>
                    <th>Totale ISBN</th>
                    <th>Prestiti in Corso</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sediStats as $stat): ?>
                <tr>
                    <td><?php echo htmlspecialchars($stat['codice_sede']); ?></td>
                    <td><?php echo htmlspecialchars($stat['totale_copie']); ?></td>
                    <td><?php echo htmlspecialchars($stat['totale_libri']); ?></td>
                    <td><?php echo htmlspecialchars($stat['prestiti_in_corso']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <!-- Sezione Ritardi per Sede -->
    <h2>Report dei Ritardi</h2>
    <?php if (count($sediRitardi) === 0): ?>
        <p>Nessun prestito in ritardo.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Codice Sede</th>
                    <th>ISBN</th>
                    <th>Titolo</th>
                    <th>Autore</th>
                    <th>Data Inizio</th>
                    <th>Data Scadenza</th>
                    <th>Codice Fiscale Lettore</th>
                    <th>Lettore</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sediRitardi as $ritardo): ?>
                <tr>
                    <td><?php echo htmlspecialchars($ritardo['codice_sede']); ?></td>
                    <td><?php echo htmlspecialchars($ritardo['isbn']); ?></td>
                    <td><?php echo htmlspecialchars($ritardo['titolo']); ?></td>
                    <td><?php echo htmlspecialchars($ritardo['nome_autore'] . ' ' . $ritardo['cognome_autore']); ?></td>
                    <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($ritardo['data_inizio']))); ?></td>
                    <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($ritardo['data_scadenza']))); ?></td>
                    <td><?php echo htmlspecialchars($ritardo['codice_fiscale']); ?></td>
                    <td><?php echo htmlspecialchars($ritardo['nome_lettore'] . ' ' . $ritardo['cognome_lettore']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
