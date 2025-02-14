<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

$search = "";
$autori = [];

if (!isset($_GET['search']) || trim($_GET['search']) == "") {
    // Nessun termine di ricerca: mostra tutti gli autori
    $sql = "SELECT id_autore, nome, cognome, data_nascita, data_morte 
            FROM autore 
            ORDER BY nome, cognome";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $autori = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $search = trim($_GET['search']);
    $sql = "SELECT id_autore, nome, cognome, data_nascita, data_morte 
            FROM autore 
            WHERE nome ILIKE ? OR cognome ILIKE ? 
            ORDER BY nome, cognome";
    $stmt = $pdo->prepare($sql);
    $likeSearch = "%" . $search . "%";
    $stmt->execute([$likeSearch, $likeSearch]);
    $autori = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Cerca Autori - BiblioStudio</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1>Cerca Autore</h1>
    <div class="inline-links">
        <a href="home_bibliotecario.php">Home</a>
    </div>
    <form method="get" action="cerca_autori.php">
        <label for="search">Inserisci nome o cognome:</label>
        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Cerca</button>
    </form>
    <?php if (!isset($_GET['search']) || trim($search) == ""): ?>
        <h2>Elenco Completo degli Autori</h2>
    <?php else: ?>
        <h2>Risultati della ricerca</h2>
    <?php endif; ?>
    <?php if (empty($autori)): ?>
        <p>Nessun autore trovato.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Cognome</th>
                    <th>Data di nascita</th>
                    <th>Data di morte</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($autori as $autore): ?>
                    <tr onclick="window.location.href='dettaglio_autore.php?id=<?php echo urlencode($autore['id_autore']); ?>'" style="cursor:pointer;">
                        <td><?php echo htmlspecialchars($autore['nome']); ?></td>
                        <td><?php echo htmlspecialchars($autore['cognome']); ?></td>
                        <td><?php echo htmlspecialchars($autore['data_nascita']); ?></td>
                        <td><?php echo htmlspecialchars($autore['data_morte'] ? $autore['data_morte'] : '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
