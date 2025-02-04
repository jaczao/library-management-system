<?php
// forgot_password.php
$role = isset($_GET['role']) ? $_GET['role'] : '';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Password Dimenticata - BiblioStudio</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Password Dimenticata</h1>
    <p>Ti abbiamo inviato una mail in cui troverai un link per creare una nuova password.</p>
    <p><a href="login.php?role=<?php echo urlencode($role); ?>">Torna al login</a></p>
</body>
</html>
