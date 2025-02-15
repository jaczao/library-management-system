<?php

session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'lettore') {
    header("Location: /login.php?role=lettore");
    exit;
}

$nome = htmlspecialchars($_SESSION['user']['nome']);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Home Lettore - BiblioStudio</title>
    <link rel="stylesheet" href="/../css/style.css">
</head>
<body>
    <div class="container">
    <h1>Home Lettore</h1>
    <nav>
        <ul>
            <li><a href="/../modifica_profilo.php">Modifica Profilo</a></li>
            <li><a href="/../cerca_libri.php">Cerca Libro</a></li>
            <li><a href="/../cerca_autori.php">Cerca Autore</a></li>
            <li><a href="visualizza_prestiti.php">Visualizza Prestiti</a></li>
            <li><a href="/../logout.php">Logout</a></li>
        </ul>
    </nav>
    <p>Benvenuto, <?php echo htmlspecialchars($_SESSION['user']['nome']); ?>!</p>
    <p>Da qui puoi accedere a tutte le funzionalità messe a disposizione da BiblioStudio.</p>
    </div>
</body>
</html>

