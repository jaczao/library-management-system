<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'bibliotecario') {
    header("Location: login.php?role=bibliotecario");
    exit;
}
require_once 'config.php';

$search = "";
$sedi = [];

if (!isset($_GET['search']) || trim($_GET['search']) == "") {
    // Nessun termine di ricerca: mostra tutte le sedi
    $sql = "SELECT id_sede, codice_sede, citta, indirizzo 
            FROM sede 
            ORDER BY citta, codice_sede";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $sedi = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $search = trim($_GET['search']);
    $sql = "SELECT id_sede, codice_sede, citta, indirizzo 
            FROM sede 
            WHERE codice_sede ILIKE ? OR citta ILIKE ? OR indirizzo ILIKE ?
            ORDER BY citta, codice_sede";
    $stmt = $pdo->prepare($sql);
    $likeSearch = "%" . $search . "%";
    $stmt->execute([$likeSearch, $likeSearch, $likeSearch]);
    $sedi = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Cerca Sede - BiblioStudio</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1>Cerca Sede</h1>
    <p>
        <a href="home_bibliotecario.php">Home</a> | 
        <a href="crea_sede.php">Crea Sede</a>
    </p>
    <form method="get" action="cerca_sede.php">
        <label for="search">Inserisci codice sede, città o indirizzo:</label>
        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Cerca</button>
    </form>
    
    <?php if (!isset($_GET['search']) || trim($search) == ""): ?>
        <h2>Elenco Completo delle Sedi</h2>
    <?php else: ?>
        <h2>Risultati della ricerca</h2>
    <?php endif; ?>
    
    <?php if (empty($sedi)): ?>
        <p>Nessuna sede trovata.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Codice Sede</th>
                    <th>Città</th>
                    <th>Indirizzo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sedi as $sede): ?>
                    <tr onclick="window.location.href='dettaglio_sede.php?id=<?php echo urlencode($sede['id_sede']); ?>'" style="cursor:pointer;">
                        <td><?php echo htmlspecialchars($sede['codice_sede']); ?></td>
                        <td><?php echo htmlspecialchars($sede['citta']); ?></td>
                        <td><?php echo htmlspecialchars($sede['indirizzo']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
