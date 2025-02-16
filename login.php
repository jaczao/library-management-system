<?php

session_start();

$role = isset($_GET['role']) ? $_GET['role'] : '';
if (!in_array($role, ['bibliotecario', 'lettore'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config.php';
    
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = "Email e password sono obbligatorie.";
    } else {
        // Scelgo la tabella in base al ruolo
        $table = ($role === 'bibliotecario') ? 'bibliotecario' : 'lettore';

        $stmt = $pdo->prepare("SELECT * FROM $table WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user'] = $user;
                $_SESSION['role'] = $role;

                // Redirect in base al ruolo
                if ($role === 'bibliotecario') {
                    header("Location: bibliotecario/home_bibliotecario.php");
                } else {
                    header("Location: lettore/home_lettore.php");
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
    <div class="container">
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
    </div>
</body>
</html>
