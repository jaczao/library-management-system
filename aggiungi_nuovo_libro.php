<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
require_once 'config.php';

$message = "";
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dati del libro
    $isbn = trim($_POST['isbn'] ?? '');
    $titolo = trim($_POST['titolo'] ?? '');
    $trama = trim($_POST['trama'] ?? '');
    $casa_editrice = trim($_POST['casa_editrice'] ?? '');
    
    // Dati degli autori
    $nome_autori = $_POST['nome_autore'] ?? [];
    $cognome_autori = $_POST['cognome_autore'] ?? [];
    
    $autori_inseriti = [];
    $count = count($nome_autori);
    for ($i = 0; $i < $count; $i++) {
        $nome = trim($nome_autori[$i]);
        $cognome = trim($cognome_autori[$i]);
        if ($nome !== "" && $cognome !== "") {
            $autori_inseriti[] = ['nome' => $nome, 'cognome' => $cognome];
        }
    }
    
    if (empty($isbn) || empty($titolo) || empty($trama) || empty($casa_editrice) || count($autori_inseriti) == 0) {
        $error = "Tutti i campi e almeno un autore sono obbligatori.";
    } else {
        try {
            $pdo->beginTransaction();
            // Inserisce il nuovo libro e recupera l'id
            $sqlLibro = "INSERT INTO libro (isbn, titolo, trama, casa_editrice) VALUES (?, ?, ?, ?)
                         RETURNING id_libro";
            $stmtLibro = $pdo->prepare($sqlLibro);
            $stmtLibro->execute([$isbn, $titolo, $trama, $casa_editrice]);
            $id_libro = $stmtLibro->fetchColumn();
            
            // Per ogni autore inserito, controlla se esiste; se non esiste, crealo; poi crea l'associazione
            foreach ($autori_inseriti as $a) {
                $sqlCheck = "SELECT id_autore FROM autore WHERE LOWER(nome) = LOWER(?) AND LOWER(cognome) = LOWER(?)";
                $stmtCheck = $pdo->prepare($sqlCheck);
                $stmtCheck->execute([$a['nome'], $a['cognome']]);
                $autore = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                if ($autore) {
                    $id_autore = $autore['id_autore'];
                } else {
                    $defaultDataNascita = '1900-01-01';
                    $defaultBiografia = 'N/A';
                    $sqlInsertAutore = "INSERT INTO autore (nome, cognome, data_nascita, biografia) VALUES (?, ?, ?, ?)
                                        RETURNING id_autore";
                    $stmtInsertAutore = $pdo->prepare($sqlInsertAutore);
                    $stmtInsertAutore->execute([$a['nome'], $a['cognome'], $defaultDataNascita, $defaultBiografia]);
                    $id_autore = $stmtInsertAutore->fetchColumn();
                }
                // Crea l'associazione
                $sqlAssoc = "INSERT INTO libro_autore (libro, autore) VALUES (?, ?)";
                $stmtAssoc = $pdo->prepare($sqlAssoc);
                $stmtAssoc->execute([$id_libro, $id_autore]);
            }
            
            $pdo->commit();
            $message = "Nuovo libro aggiunto con successo.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Errore nell'aggiunta del libro: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Aggiungi Nuovo Libro - BiblioStudio</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
      .autore-group { margin-bottom: 10px; }
      .autore-group input { width: 45%; display: inline-block; }
      .autore-group button { margin-left: 5px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Aggiungi Nuovo Libro</h1>
    <?php if (!empty($message)): ?>
        <div class="alert success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" action="aggiungi_nuovo_libro.php">
        <label for="isbn">ISBN:</label>
        <input type="text" id="isbn" name="isbn" required>
        
        <label for="titolo">Titolo:</label>
        <input type="text" id="titolo" name="titolo" required>
        
        <label for="trama">Trama:</label>
        <textarea id="trama" name="trama" rows="5" required></textarea>
        
        <label for="casa_editrice">Casa Editrice:</label>
        <input type="text" id="casa_editrice" name="casa_editrice" required>
        
        <h3>Autori</h3>
        <div id="autori_container">
            <!-- Inizialmente nessun campo è precompilato; l'utente può aggiungere uno a uno -->
        </div>
        <button type="button" id="add_author">Aggiungi Autore</button>
        <br><br>
        <button type="submit">Aggiungi Libro</button>
    </form>
    <p><a href="cerca_libro.php">Torna alla ricerca libri</a></p>
</div>
<script>
document.getElementById('add_author').addEventListener('click', function(){
    var container = document.getElementById('autori_container');
    var div = document.createElement('div');
    div.className = 'autore-group';
    div.innerHTML = '<input type="text" name="nome_autore[]" placeholder="Nome autore" required> ' +
                    '<input type="text" name="cognome_autore[]" placeholder="Cognome autore" required> ' +
                    '<button type="button" class="remove_author">Rimuovi</button>';
    container.appendChild(div);
});
document.getElementById('autori_container').addEventListener('click', function(e){
    if(e.target && e.target.classList.contains('remove_author')){
        e.target.parentElement.remove();
    }
});
</script>
</body>
</html>
