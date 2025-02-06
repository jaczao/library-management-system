<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

$search = "";
$autori = [];
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    if ($search !== "") {
        $sql = "SELECT id_autore, nome, cognome, data_nascita, data_morte 
                FROM autore 
                WHERE nome ILIKE ? OR cognome ILIKE ? 
                ORDER BY nome, cognome";
        $stmt = $pdo->prepare($sql);
        $likeSearch = "%" . $search . "%";
        $stmt->execute([$likeSearch, $likeSearch]);
        $autori = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
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
    <h1>Cerca Autori</h1>
    <form method="get" action="cerca_autori.php">
        <label for="search">Inserisci nome o cognome:</label>
        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" required>
        <button type="submit">Cerca</button>
    </form>
    <?php if (isset($_GET['search'])): ?>
        <h2>Risultati della ricerca</h2>
        <?php if (empty($autori)): ?>
            <p>Nessun autore trovato per "<?php echo htmlspecialchars($search); ?>".</p>
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
    <?php endif; ?>
</div>
</body>
</html>
