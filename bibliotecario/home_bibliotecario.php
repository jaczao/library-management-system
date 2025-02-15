<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'bibliotecario') {
    header("Location: /login.php?role=bibliotecario");
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Home Bibliotecario - BiblioStudio</title>
    <link rel="stylesheet" href="/../css/style.css">
</head>
<body>
<div class="container">
    <h1>Home Bibliotecario</h1>
    <nav>
        <ul>
            <li><a href="/../modifica_profilo.php">Modifica Profilo</a></li>
            <li><a href="cerca_lettore.php">Cerca Lettore</a></li>
            <li><a href="/../cerca_libri.php">Cerca Libro</a></li>
            <li><a href="/../cerca_autori.php">Cerca Autore</a></li>
            <li><a href="cerca_sede.php">Cerca Sede</a></li>
            <li><a href="elenco_prestiti.php">Elenco Prestiti</a></li>
            <li><a href="statistiche.php">Statistiche</a></li>
            <li><a href="/../logout.php">Logout</a></li>
        </ul>
    </nav>
    <p>Benvenuto, <?php echo htmlspecialchars($_SESSION['user']['nome']); ?>!</p>
    <p>Utilizza il menu sopra per navigare tra le funzionalità del sistema.</p>
</div>
</body>
</html>

