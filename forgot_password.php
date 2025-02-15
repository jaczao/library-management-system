<?php

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
<div class="container">
    <h1>Password Dimenticata</h1>
    <p>Ti abbiamo inviato una mail all'indirizzo indicato in cui troverai un link per creare una nuova password.</p>
    <div class="inline-links">
    <p><a href="login.php?role=<?php echo urlencode($role); ?>">Torna al login</a></p>
    </div>
</div>
</body>
</html>
