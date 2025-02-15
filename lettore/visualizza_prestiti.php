<?php

session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'lettore') {
    header("Location: /login.php?role=lettore");
    exit;
}

require_once __DIR__ . '/../config.php';

$lettoreId = $_SESSION['user']['id_lettore'];

// Recuperiamo i prestiti del lettore
$sql = "SELECT p.id_prestito, p.data_inizio, p.data_scadenza, p.data_restituzione,
               l.titolo, l.isbn
        FROM prestito p
        JOIN copia c ON p.copia = c.id_copia
        JOIN libro l ON c.libro = l.id_libro
        WHERE p.lettore = ?
        ORDER BY p.data_inizio DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$lettoreId]);
$prestiti = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Visualizza Prestiti - BiblioStudio</title>
    <link rel="stylesheet" href="/../css/style.css">
</head>
<body>
<div class="container">
    <h1>I tuoi Prestiti</h1>
    <nav>
        <ul>
            <li><a href="home_lettore.php">Home</a></li>
            <li><a href="/../modifica_profilo.php">Modifica Profilo</a></li>
            <li><a href="/../cerca_libri.php">Cerca Libro</a></li>
            <li><a href="/../cerca_autori.php">Cerca Autore</a></li>
            <li><a href="/../logout.php">Logout</a></li>
        </ul>
    </nav>
    <br>
    
    <?php if (empty($prestiti)): ?>
        <p>Non c'è nessun prestito.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Titolo Libro</th>
                    <th>ISBN</th>
                    <th>Stato</th>
                    <th>Data Inizio</th>
                    <th>Data Scadenza</th>
                    <th>Data Restituzione</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prestiti as $prestito): ?>
                    <?php 
                        // Determina lo stato del prestito e formatta le date
                        $dataInizio = date("d/m/Y", strtotime($prestito['data_inizio']));
                        $dataScadenza = date("d/m/Y", strtotime($prestito['data_scadenza']));
                        if (!empty($prestito['data_restituzione'])) {
                            $stato = "Restituito";
                            $dataRestituzione = date("d/m/Y", strtotime($prestito['data_restituzione']));
                        } else {
                            $stato = "In corso";
                            $dataRestituzione = "-";
                        }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($prestito['titolo']); ?></td>
                        <td><?php echo htmlspecialchars($prestito['isbn']); ?></td>
                        <td><?php echo $stato; ?></td>
                        <td><?php echo $dataInizio; ?></td>
                        <td><?php echo $dataScadenza; ?></td>
                        <td><?php echo $dataRestituzione; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
