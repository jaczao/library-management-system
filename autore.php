<?php
session_start();
require_once 'config.php';

if (!isset($_GET['id'])) {
    echo "Autore non specificato.";
    exit;
}

$id_autore = intval($_GET['id']);

// Recupera le informazioni sull'autore
$sql_autore = "SELECT * FROM autore WHERE id_autore = ?";
$stmt = $pdo->prepare($sql_autore);
$stmt->execute([$id_autore]);
$autore = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$autore) {
    echo "Autore non trovato.";
    exit;
}

// Recupera i libri scritti dall'autore
$sql_libri = "SELECT l.id_libro, l.isbn, l.titolo
              FROM libro l
              JOIN libro_autore la ON l.id_libro = la.libro
              WHERE la.autore = ?";
$stmt2 = $pdo->prepare($sql_libri);
$stmt2->execute([$id_autore]);
$libri = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dettaglio Autore - <?php echo htmlspecialchars($autore['nome'] . " " . $autore['cognome']); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1>Dettaglio Autore</h1>
    <h2><?php echo htmlspecialchars($autore['nome'] . " " . $autore['cognome']); ?></h2>
    <p><strong>Data di nascita:</strong> <?php echo htmlspecialchars($autore['data_nascita']); ?></p>
    <p><strong>Data di morte:</strong> <?php echo htmlspecialchars($autore['data_morte'] ? $autore['data_morte'] : 'N/A'); ?></p>
    <p><strong>Biografia:</strong></p>
    <p><?php echo nl2br(htmlspecialchars($autore['biografia'])); ?></p>
    
    <h3>Libri scritti</h3>
    <?php if (count($libri) === 0): ?>
        <p>Nessun libro trovato per questo autore.</p>
    <?php else: ?>
        <ul>
        <?php foreach ($libri as $libro): ?>
            <li>
                <a href="dettaglio_libro.php?id=<?php echo urlencode($libro['id_libro']); ?>">
                    <?php echo htmlspecialchars($libro['titolo']); ?>
                </a>
                (ISBN: <?php echo htmlspecialchars($libro['isbn']); ?>)
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
</body>
</html>
