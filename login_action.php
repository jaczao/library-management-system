<?php
// login_action.php
session_start();
require 'db_connection.php';

$ruolo    = $_POST['ruolo'];
$username = $_POST['username'];
$password = $_POST['password'];

if ($ruolo === 'bibliotecario') {
    $stmt = $pdo->prepare("SELECT * FROM bibliotecario WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        $_SESSION['ruolo'] = 'bibliotecario';
        header("Location: bibliotecario_home.php");
        exit;
    } else {
        echo "Credenziali non valide per il ruolo bibliotecario.";
    }
} else { // lettore
    $stmt = $pdo->prepare("SELECT * FROM lettore WHERE codice_fiscale = :cf");
    $stmt->execute(['cf' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        $_SESSION['ruolo'] = 'lettore';
        header("Location: lettore_home.php");
        exit;
    } else {
        echo "Credenziali non valide per il ruolo lettore.";
    }
}
?>
