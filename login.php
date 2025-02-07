<?php
// login.php
session_start();

// Verifica che il ruolo sia passato come parametro
$role = isset($_GET['role']) ? $_GET['role'] : '';
if (!in_array($role, ['bibliotecario', 'lettore'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config.php';
    
    // Recupera ed effettua il trim dei dati inviati
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = "Email e password sono obbligatorie.";
    } else {
        // Scegli la tabella in base al ruolo
        $table = ($role === 'bibliotecario') ? 'bibliotecario' : 'lettore';

        // Prepariamo la query per prevenire SQL injection
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Utilizziamo password_verify per confrontare la password in maniera sicura
            if (password_verify($password, $user['password'])) {
                // Login avvenuto con successo: creiamo le variabili di sessione
                $_SESSION['user'] = $user;
                $_SESSION['role'] = $role;

                // Redirect in base al ruolo
                if ($role === 'bibliotecario') {
                    header("Location: home_bibliotecario.php");
                } else {
                    header("Location: home_lettore.php");
                }
                exit;
            } else {
                $error = "Password errata.";
            }
        } else {
            $error = "Email non trovata.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login - BiblioStudio</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Login - <?php echo ucfirst(htmlspecialchars($role)); ?></h1>
    <?php if (!empty($error)): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="post" action="login.php?role=<?php echo urlencode($role); ?>">
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required>
        <br><br>
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>
        <br><br>
        <button type="submit">Accedi</button>
    </form>
    <div class="inline-links">
        <p>
            <a href="forgot_password.php?role=<?php echo urlencode($role); ?>">Ho dimenticato la mia password</a>
        </p>
        <p>
            <a href="index.php?role=<?php echo urlencode($role); ?>">Torna indietro</a>
        </p>
    </div>
</body>
</html>
