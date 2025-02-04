<?php
// db_connection.php
$dsn = 'pgsql:host=localhost;dbname=biblioteca';
$db_user = 'postgres';
$db_password = 'Vizor22!';

try {
    $pdo = new PDO($dsn, $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connessione fallita: ' . $e->getMessage();
    exit;
}
?>
