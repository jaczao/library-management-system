<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role']; 

if ($role === 'lettore') {
    // Campi per il lettore
    $id = $_SESSION['user']['id_lettore'];
    $codiceFiscale = $_SESSION['user']['codice_fiscale'];
} else {
    // Campi per il bibliotecario
    $id = $_SESSION['user']['id_bibliotecario'];
}

$nome     = $_SESSION['user']['nome'];
$cognome  = $_SESSION['user']['cognome'];
$email    = $_SESSION['user']['email'];
// Le password non vengono mostrate nel form, per ragioni di sicurezza
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recupero i valori inviati dal form
    $nome     = trim($_POST['nome'] ?? '');
    $cognome  = trim($_POST['cognome'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    
    // Per i lettori, recupero anche il codice fiscale
    if ($role === 'lettore') {
        $codiceFiscale = trim($_POST['codice_fiscale'] ?? '');
    }
    
    // I campi della password vengono aggiornati solo se l'utente inserisce un nuovo valore
    $password        = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    
    // Validazioni
    if (empty($nome)) {
        $errors[] = "Il nome è obbligatorio.";
    }
    if (empty($cognome)) {
        $errors[] = "Il cognome è obbligatorio.";
    }
    if (empty($email)) {
        $errors[] = "L'email è obbligatoria.";
    }
    if ($role === 'lettore' && empty($codiceFiscale)) {
        $errors[] = "Il codice fiscale è obbligatorio.";
    }
    
    // Se l'utente vuole aggiornare la password, controllo che i due campi coincidano
    $updatePassword = false;
    if (!empty($password) || !empty($confirmPassword)) {
        if ($password !== $confirmPassword) {
            $errors[] = "Le password non coincidono.";
        } else {
            $updatePassword = true;
            // Hashiamo la nuova password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        }
    }
    
    // Se non ci sono errori procedo con l'update
    if (empty($errors)) {
        try {
            if ($role === 'lettore') {
                // Preparo l'update per i lettori
                if ($updatePassword) {
                    $sql = "UPDATE lettore 
                            SET nome = ?, cognome = ?, codice_fiscale = ?, email = ?, password = ?
                            WHERE id_lettore = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$nome, $cognome, $codiceFiscale, $email, $hashedPassword, $id]);
                } else {
                    $sql = "UPDATE lettore 
                            SET nome = ?, cognome = ?, codice_fiscale = ?, email = ?
                            WHERE id_lettore = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$nome, $cognome, $codiceFiscale, $email, $id]);
                }
                // Recupero i dati aggiornati
                $stmt = $pdo->prepare("SELECT * FROM lettore WHERE id_lettore = ?");
                $stmt->execute([$id]);
                $_SESSION['user'] = $stmt->fetch();
            } else { // bibliotecario
                if ($updatePassword) {
                    $sql = "UPDATE bibliotecario 
                            SET nome = ?, cognome = ?, email = ?, password = ?
                            WHERE id_bibliotecario = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$nome, $cognome, $email, $hashedPassword, $id]);
                } else {
                    $sql = "UPDATE bibliotecario 
                            SET nome = ?, cognome = ?, email = ?
                            WHERE id_bibliotecario = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$nome, $cognome, $email, $id]);
                }
                // Recupero i dati aggiornati
                $stmt = $pdo->prepare("SELECT * FROM bibliotecario WHERE id_bibliotecario = ?");
                $stmt->execute([$id]);
                $_SESSION['user'] = $stmt->fetch();
            }
            
            $success = "Profilo aggiornato con successo.";
        } catch (PDOException $e) {
            $errors[] = "Errore durante l'aggiornamento: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Profilo - BiblioStudio</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h1>Modifica Profilo</h1>
    
    <?php
    if (!empty($errors)) {
        echo '<ul style="color:red;">';
        foreach ($errors as $err) {
            echo '<li>' . htmlspecialchars($err) . '</li>';
        }
        echo '</ul>';
    }
    if ($success) {
        echo '<p style="color:green;">' . htmlspecialchars($success) . '</p>';
    }
    ?>
    
    <form method="post" action="modifica_profilo.php">
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
        <br><br>
        
        <label for="cognome">Cognome:</label>
        <input type="text" id="cognome" name="cognome" value="<?php echo htmlspecialchars($cognome); ?>" required>
        <br><br>
        
        <?php if ($role === 'lettore'): ?>
            <label for="codice_fiscale">Codice Fiscale:</label>
            <input type="text" id="codice_fiscale" name="codice_fiscale" value="<?php echo htmlspecialchars($codiceFiscale); ?>" required>
            <br><br>
        <?php endif; ?>
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        <br><br>
        
        <label for="password">Nuova Password:</label>
        <input type="password" id="password" name="password">
        <br><br>
        
        <label for="confirm_password">Conferma Nuova Password:</label>
        <input type="password" id="confirm_password" name="confirm_password">
        <br><br>
        
        <button type="submit">Aggiorna Profilo</button>
    </form>
    
    <div class="inline-links">
    <a href="<?php echo ($_SESSION['role'] === 'bibliotecario') ? 'bibliotecario/home_bibliotecario.php' : 'lettore/home_lettore.php'; ?>">Torna alla home</a>
    </div>
</div>
</body>
</html>
