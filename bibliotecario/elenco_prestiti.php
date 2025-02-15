<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'bibliotecario') {
    header("Location: login.php?role=bibliotecario");
    exit;
}

require_once __DIR__ . '/../config.php';

// Selezione dello stato: "in_corso" (prestiti attivi, data_restituzione IS NULL)
// oppure "non_in_corso" (prestiti completati, data_restituzione IS NOT NULL)
// Il parametro GET "status" determina la visualizzazione; default "in_corso"
$status = isset($_GET['status']) ? $_GET['status'] : 'in_corso';
if ($status === 'non_in_corso') {
    $condition = "p.data_restituzione IS NOT NULL";
} else {
    $condition = "p.data_restituzione IS NULL";
}

// Query per recuperare l'elenco dei prestiti, ordinati dal più recente al meno recente
$sql = "SELECT p.id_prestito, p.data_inizio, p.data_scadenza, p.data_restituzione,
        l.nome AS lettore_nome, l.cognome AS lettore_cognome, 
        b.titolo AS libro_titolo
        FROM prestito p
        JOIN lettore l ON p.lettore = l.id_lettore
        JOIN copia c ON p.copia = c.id_copia
        JOIN libro b ON c.libro = b.id_libro
        WHERE $condition
        ORDER BY p.data_inizio DESC";
$stmt = $pdo->query($sql);
$prestiti = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Elenco Prestiti - BiblioStudio</title>
    <link rel="stylesheet" href="/../css/style.css">
</head>
<body>
<div class="container">
    <h1>Elenco Prestiti</h1>
    <!-- Menu di navigazione -->
    <nav>
        <ul>
            <li><a href="bibliotecario/home_bibliotecario.php">Home</a></li>
            <li><a href="modifica_profilo.php">Modifica Profilo</a></li>
            <li><a href="bibliotecario/statistiche.php">Statistiche</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    
    <!-- Switch per selezionare lo stato dei prestiti -->
    <div class="inline-links">
        <a href="bibliotecario/elenco_prestiti.php?status=in_corso" <?php if($status==='in_corso') echo 'style="font-weight:bold;"'; ?>>In Corso</a> |
        <a href="bibliotecario/elenco_prestiti.php?status=non_in_corso" <?php if($status==='non_in_corso') echo 'style="font-weight:bold;"'; ?>>Conclusi</a>
    </div>
    
    <?php if (count($prestiti) == 0): ?>
        <p>Nessun prestito trovato per lo stato selezionato.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID Prestito</th>
                <th>Data Inizio</th>
                <th>Data Scadenza</th>
                <th>Data Restituzione</th>
                <th>Lettore</th>
                <th>Libro</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($prestiti as $p): ?>
            <tr onclick="window.location.href='bibliotecario/dettaglio_prestito.php?id=<?php echo $p['id_prestito']; ?>'" style="cursor:pointer;">
                <td><?php echo htmlspecialchars($p['id_prestito']); ?></td>
                <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($p['data_inizio']))); ?></td>
                <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($p['data_scadenza']))); ?></td>
                <td><?php echo $p['data_restituzione'] ? htmlspecialchars(date("d/m/Y", strtotime($p['data_restituzione']))) : '-'; ?></td>
                <td><?php echo htmlspecialchars($p['lettore_nome'] . ' ' . $p['lettore_cognome']); ?></td>
                <td><?php echo htmlspecialchars($p['libro_titolo']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</body>
</html>
