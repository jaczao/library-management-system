<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'bibliotecario') {
    header("Location: login.php?role=bibliotecario");
    exit;
}

require_once __DIR__ . '/../config.php';

$search = "";
$lettori = [];

if (!isset($_GET['search']) || trim($_GET['search']) == "") {
    // Nessun termine di ricerca: mostra tutti i lettori
    $sql = "SELECT id_lettore, codice_fiscale, nome, cognome, email, categoria 
            FROM lettore 
            ORDER BY nome, cognome";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $lettori = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $search = trim($_GET['search']);
    $sql = "SELECT id_lettore, codice_fiscale, nome, cognome, email, categoria 
            FROM lettore 
            WHERE codice_fiscale ILIKE ? OR nome ILIKE ? OR cognome ILIKE ? OR email ILIKE ? 
            ORDER BY nome, cognome";
    $stmt = $pdo->prepare($sql);
    $likeSearch = "%" . $search . "%";
    $stmt->execute([$likeSearch, $likeSearch, $likeSearch, $likeSearch]);
    $lettori = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Cerca Lettore - BiblioStudio</title>
    <link rel="stylesheet" href="/../css/style.css">
</head>
<body>
<div class="container">
    <h1>Cerca Lettore</h1>
    <div class="inline-links">
        <a href="home_bibliotecario.php">Home</a> | 
        <a href="crea_lettore.php">Crea Nuovo Profilo Lettore</a>
</div>
    <form method="get" action="cerca_lettore.php">
        <label for="search">Inserisci codice fiscale, nome, cognome o email:</label>
        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Cerca</button>
    </form>
    <?php if (!isset($_GET['search']) || trim($search) == ""): ?>
        <h2>Elenco Completo dei Lettori</h2>
    <?php else: ?>
        <h2>Risultati della ricerca</h2>
    <?php endif; ?>
    <?php if (empty($lettori)): ?>
        <p>Nessun lettore trovato.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Codice Fiscale</th>
                    <th>Nome</th>
                    <th>Cognome</th>
                    <th>Email</th>
                    <th>Categoria</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lettori as $lettore): ?>
                <tr onclick="window.location.href='profilo_lettore.php?id=<?php echo urlencode($lettore['id_lettore']); ?>'" style="cursor:pointer;">
                    <td><?php echo htmlspecialchars($lettore['codice_fiscale']); ?></td>
                    <td><?php echo htmlspecialchars($lettore['nome']); ?></td>
                    <td><?php echo htmlspecialchars($lettore['cognome']); ?></td>
                    <td><?php echo htmlspecialchars($lettore['email']); ?></td>
                    <td><?php echo htmlspecialchars($lettore['categoria']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
