<?php
// home_lettore.php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'lettore') {
    header("Location: login.php?role=lettore");
    exit;
}

$nome = htmlspecialchars($_SESSION['user']['nome']);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Home Lettore - BiblioStudio</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Benvenuto, <?php echo $nome; ?>!</h1>
    <nav>
        <ul>
            <li><a href="modifica_profilo.php">Modifica Profilo</a></li>
            <li><a href="cerca_libri.php">Cerca Libri</a></li>
            <li><a href="visualizza_prenotazioni.php">Visualizza Prenotazioni</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    <p>Da qui potrai accedere a tutte le funzionalità messe a disposizione da BiblioStudio.</p>
</body>
</html>

