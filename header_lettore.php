<?php
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
    <title>Cerca Libri - BiblioStudio (Lettore)</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1>BiblioStudio - Lettore</h1>
    <nav>
        <ul>
            <li><a href="home_lettore.php">Home</a></li>
            <li><a href="modifica_profilo.php">Modifica Profilo</a></li>
            <li><a href="cerca_libri.php">Cerca Libri</a></li>
            <li><a href="visualizza_prenotazioni.php">Visualizza Prenotazioni</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
