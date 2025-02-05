<?php
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
    <title>Cerca Libri - BiblioStudio (Bibliotecario)</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1>BiblioStudio - Bibliotecario</h1>
    <nav>
        <ul>
            <li><a href="home_bibliotecario.php">Home</a></li>
            <li><a href="modifica_profilo.php">Modifica Profilo</a></li>
            <li><a href="cerca_libri.php">Cerca Libri</a></li>
            <li><a href="elenco_prestiti.php">Elenco Prestiti</a></li>
            <li><a href="statistiche.php">Statistiche</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
