<?php
// STUB DI home_bibliotecario.php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'bibliotecario') {
    header("Location: login.php?role=bibliotecario");
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Home Bibliotecario - BiblioStudio</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Benvenuto, <?php echo htmlspecialchars($_SESSION['user']['nome']); ?></h1>
    <p>Questa è la home page del bibliotecario.</p>
    <!-- Inserisci qui le funzionalità per la gestione dei lettori, libri, sedi, ecc. -->
</body>
</html>
