<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'];

require_once 'config.php';

$search = "";
$books = [];

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    if ($search !== "") {
        $sql = "SELECT l.id_libro, l.isbn, l.titolo,
                       string_agg(a.nome || ' ' || a.cognome, ', ') AS autori
                FROM libro l
                LEFT JOIN libro_autore la ON l.id_libro = la.libro
                LEFT JOIN autore a ON la.autore = a.id_autore
                WHERE l.titolo ILIKE ? OR l.isbn ILIKE ?
                GROUP BY l.id_libro, l.isbn, l.titolo
                ORDER BY l.titolo";
        $stmt = $pdo->prepare($sql);
        $likeSearch = "%" . $search . "%";
        $stmt->execute([$likeSearch, $likeSearch]);
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Cerca Libri - BiblioStudio</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1>Cerca Libri</h1>

    <!-- Home Button: il link viene scelto in base al ruolo -->
    <p>
        <a href="<?php echo ($role === 'bibliotecario') ? 'home_bibliotecario.php' : 'home_lettore.php'; ?>">Home</a>
        <?php if ($role === 'bibliotecario'): ?>
            | <a href="aggiungi_nuovo_libro.php">Aggiungi Nuovo Libro</a>
        <?php endif; ?>
    </p>
    
    <form method="get" action="cerca_libri.php">
        <label for="search">Inserisci ISBN o titolo:</label>
        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" required>
        <button type="submit">Cerca</button>
    </form>
    
    <?php if (isset($_GET['search'])): ?>
        <h2>Risultati della ricerca</h2>
        <?php if (empty($books)): ?>
            <p>Nessun libro trovato per "<?php echo htmlspecialchars($search); ?>".</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Titolo</th>
                        <th>ISBN</th>
                        <th>Autori</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $book): ?>
                    <tr onclick="window.location.href='dettaglio_libro.php?id=<?php echo urlencode($book['id_libro']); ?>'" style="cursor:pointer;">
                        <td><?php echo htmlspecialchars($book['titolo']); ?></td>
                        <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                        <td><?php echo htmlspecialchars($book['autori']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
