<?php

$host = 'localhost';
$db   = 'biblioteca';
$user = 'postgres';
$pass = 'postgres';
$dsn = "pgsql:host=$host;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    // In produzione, loggare l'errore e mostrare un messaggio generico all'utente
    error_log("Errore di connessione: " . $e->getMessage());
    die("Errore di connessione al database.");
}
?>
