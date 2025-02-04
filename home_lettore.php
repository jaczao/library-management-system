<?php
// STUB DI home_lettore.php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'lettore') {
    header("Location: login.php?role=lettore");
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Home Lettore - BiblioStudio</title>
</head>
<body>
    <h1>Benvenuto, <?php echo htmlspecialchars($_SESSION['user']['nome']); ?></h1>
    <p>Questa è la home page del lettore.</p>
    <!-- Inserisci qui le funzionalità per la visualizzazione dei cataloghi, richiesta prestiti, ecc. -->
</body>
</html>
