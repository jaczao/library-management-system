<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'bibliotecario') {
    header("Location: login.php?role=bibliotecario");
    exit;
}
require_once 'config.php';

if (!isset($_GET['id'])) {
    echo "Libro non specificato.";
    exit;
}

$id_libro = intval($_GET['id']);
$message = "";
$error = "";

// Recupera i dati correnti del libro
$sql = "SELECT l.id_libro, l.isbn, l.titolo, l.trama, l.casa_editrice
        FROM libro l
        WHERE l.id_libro = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_libro]);
$libro = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$libro) {
    echo "Libro non trovato.";
    exit;
}

// Recupera gli autori attualmente associati (assumiamo che possano essere più di uno)
$sqlAutori = "SELECT a.id_autore, a.nome, a.cognome
              FROM autore a
              JOIN libro_autore la ON a.id_autore = la.autore
              WHERE la.libro = ?";
$stmtAutori = $pdo->prepare($sqlAutori);
$stmtAutori->execute([$id_libro]);
$autori_correnti = $stmtAutori->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isbn = trim($_POST['isbn'] ?? '');
    $titolo = trim($_POST['titolo'] ?? '');
    $trama = trim($_POST['trama'] ?? '');
    $casa_editrice = trim($_POST['casa_editrice'] ?? '');
    
    // Gli autori arrivano come array
    $nome_autori = $_POST['nome_autore'] ?? [];
    $cognome_autori = $_POST['cognome_autore'] ?? [];
    
    // Costruisci l'array degli autori inseriti (solo coppie non vuote)
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
            
            // Aggiorna il libro
            $sqlUpdate = "UPDATE libro SET isbn = ?, titolo = ?, trama = ?, casa_editrice = ? WHERE id_libro = ?";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([$isbn, $titolo, $trama, $casa_editrice, $id_libro]);
            
            // Elimina tutte le associazioni precedenti
            $stmtDeleteAssoc = $pdo->prepare("DELETE FROM libro_autore WHERE libro = ?");
            $stmtDeleteAssoc->execute([$id_libro]);
            
            // Per ogni autore inserito, controlla se esiste (case-insensitive); altrimenti lo crea, poi crea l'associazione
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
                    $sqlInsert = "INSERT INTO autore (nome, cognome, data_nascita, biografia) VALUES (?, ?, ?, ?)
                                  RETURNING id_autore";
                    $stmtInsert = $pdo->prepare($sqlInsert);
                    $stmtInsert->execute([$a['nome'], $a['cognome'], $defaultDataNascita, $defaultBiografia]);
                    $id_autore = $stmtInsert->fetchColumn();
                }
                // Crea l'associazione
                $sqlAssoc = "INSERT INTO libro_autore (libro, autore) VALUES (?, ?)";
                $stmtAssoc = $pdo->prepare($sqlAssoc);
                $stmtAssoc->execute([$id_libro, $id_autore]);
            }
            
            $pdo->commit();
            $message = "Libro e autori aggiornati con successo.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Errore durante l'aggiornamento: " . htmlspecialchars($e->getMessage());
        }
    }
    
    // Ricarica i dati aggiornati
    $stmt->execute([$id_libro]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmtAutori->execute([$id_libro]);
    $autori_correnti = $stmtAutori->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Libro - <?php echo htmlspecialchars($libro['titolo']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
      .autore-group { margin-bottom: 10px; }
      .autore-group input { width: 45%; display: inline-block; }
      .autore-group button { margin-left: 5px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Modifica Libro</h1>
    <?php if ($message): ?>
        <div class="alert success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post" action="modifica_libro.php?id=<?php echo $id_libro; ?>">
        <label for="isbn">ISBN:</label>
        <input type="text" id="isbn" name="isbn" value="<?php echo htmlspecialchars($libro['isbn']); ?>" required>
        
        <label for="titolo">Titolo:</label>
        <input type="text" id="titolo" name="titolo" value="<?php echo htmlspecialchars($libro['titolo']); ?>" required>
        
        <label for="trama">Trama:</label>
        <textarea id="trama" name="trama" rows="5" required><?php echo htmlspecialchars($libro['trama']); ?></textarea>
        
        <label for="casa_editrice">Casa Editrice:</label>
        <input type="text" id="casa_editrice" name="casa_editrice" value="<?php echo htmlspecialchars($libro['casa_editrice']); ?>" required>
        
        <h3>Autori</h3>
        <div id="autori_container">
            <?php
            if (!empty($autori_correnti)):
                foreach ($autori_correnti as $a):
            ?>
            <div class="autore-group">
                <input type="text" name="nome_autore[]" placeholder="Nome autore" value="<?php echo htmlspecialchars($a['nome']); ?>" required>
                <input type="text" name="cognome_autore[]" placeholder="Cognome autore" value="<?php echo htmlspecialchars($a['cognome']); ?>" required>
                <button type="button" class="remove_author">Rimuovi</button>
            </div>
            <?php
                endforeach;
            endif;
            ?>
        </div>
        <button type="button" id="add_author">Aggiungi Autore</button>
        <br><br>
        <button type="submit" name="update_book">Salva Modifiche</button>
    </form>
    <div class="inline-links"><p><a href="dettaglio_libro.php?id=<?php echo $id_libro; ?>">Torna al Dettaglio Libro</a></p></div>
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
