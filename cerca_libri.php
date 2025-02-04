<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'lettore') {
    header("Location: login.php?role=lettore");
    exit;
}

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
        $books = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Cerca Libri - BiblioStudio</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        table, th, td { border: 1px solid #ccc; padding: 8px; }
        th { background-color: #f4f4f4; }
    </style>
</head>
<body>
    <h1>Cerca Libri</h1>
    <nav>
        <ul>
            <li><a href="home_lettore.php">Home</a></li>
            <li><a href="modifica_profilo.php">Modifica Profilo</a></li>
            <li><a href="visualizza_prenotazioni.php">Visualizza Prenotazioni</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

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
                        <tr>
                            <td>
                                <a href="dettaglio_libro.php?id=<?php echo urlencode($book['id_libro']); ?>">
                                    <?php echo htmlspecialchars($book['titolo']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                            <td><?php echo htmlspecialchars($book['autori']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
