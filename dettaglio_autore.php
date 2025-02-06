<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

if (!isset($_GET['id'])) {
    echo "Autore non specificato.";
    exit;
}

$id_autore = intval($_GET['id']);
$message = "";
$error = "";

// Gestione rimozione associazione (tramite GET parameter "remove_assoc")
if (isset($_GET['remove_assoc'])) {
    $id_libro_remove = intval($_GET['remove_assoc']);
    // Verifica quanti autori ha questo libro
    $sqlCount = "SELECT COUNT(*) FROM libro_autore WHERE libro = ?";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute([$id_libro_remove]);
    $count = $stmtCount->fetchColumn();
    if ($count <= 1) {
        $error = "Impossibile rimuovere l'associazione. Il libro deve avere almeno un autore.";
    } else {
        try {
            $sqlDelete = "DELETE FROM libro_autore WHERE libro = ? AND autore = ?";
            $stmtDelete = $pdo->prepare($sqlDelete);
            $stmtDelete->execute([$id_libro_remove, $id_autore]);
            $message = "Associazione rimossa con successo.";
        } catch (PDOException $e) {
            $error = "Errore nella rimozione dell'associazione: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Gestione aggiunta associazione (tramite POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'aggiungi_libro') {
    $id_libro_da_aggiungere = intval($_POST['id_libro'] ?? 0);
    if ($id_libro_da_aggiungere > 0) {
        // Verifica se l'associazione esiste già
        $sqlCheck = "SELECT COUNT(*) FROM libro_autore WHERE libro = ? AND autore = ?";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$id_libro_da_aggiungere, $id_autore]);
        if ($stmtCheck->fetchColumn() > 0) {
            $error = "Il libro è già associato a questo autore.";
        } else {
            try {
                $sqlInsert = "INSERT INTO libro_autore (libro, autore) VALUES (?, ?)";
                $stmtInsert = $pdo->prepare($sqlInsert);
                $stmtInsert->execute([$id_libro_da_aggiungere, $id_autore]);
                $message = "Libro aggiunto a questo autore.";
            } catch (PDOException $e) {
                $error = "Errore nell'associazione del libro: " . htmlspecialchars($e->getMessage());
            }
        }
    } else {
        $error = "Selezionare un libro valido.";
    }
}

// Recupera le informazioni sull'autore
$sqlAutore = "SELECT * FROM autore WHERE id_autore = ?";
$stmtAutore = $pdo->prepare($sqlAutore);
$stmtAutore->execute([$id_autore]);
$autore = $stmtAutore->fetch(PDO::FETCH_ASSOC);
if (!$autore) {
    echo "Autore non trovato.";
    exit;
}

// Recupera i libri associati all'autore
$sqlLibri = "SELECT b.id_libro, b.titolo, b.isbn 
             FROM libro b 
             JOIN libro_autore la ON b.id_libro = la.libro 
             WHERE la.autore = ?";
$stmtLibri = $pdo->prepare($sqlLibri);
$stmtLibri->execute([$id_autore]);
$libriAssoc = $stmtLibri->fetchAll(PDO::FETCH_ASSOC);

// Recupera tutti i libri della biblioteca per il dropdown
$sqlTuttiLibri = "SELECT id_libro, titolo FROM libro ORDER BY titolo";
$stmtTuttiLibri = $pdo->query($sqlTuttiLibri);
$tuttiLibri = $stmtTuttiLibri->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Dettaglio Autore - <?php echo htmlspecialchars($autore['nome'] . " " . $autore['cognome']); ?></title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
  <h1>Dettaglio Autore</h1>
  <?php if ($message): ?>
    <div class="alert success"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  
  <h2><?php echo htmlspecialchars($autore['nome'] . " " . $autore['cognome']); ?></h2>
  <p><strong>Data di nascita:</strong> <?php echo htmlspecialchars($autore['data_nascita']); ?></p>
  <p><strong>Data di morte:</strong> <?php echo htmlspecialchars($autore['data_morte'] ? $autore['data_morte'] : '-'); ?></p>
  <p><strong>Biografia:</strong><br><?php echo nl2br(htmlspecialchars($autore['biografia'])); ?></p>
  
  <p>
      <a href="modifica_autore.php?id=<?php echo $id_autore; ?>">Modifica Autore</a>
  </p>
  
  <!-- Form per aggiungere un libro a questo autore -->
  <h3>Aggiungi libro a questo autore</h3>
  <form method="post" action="dettaglio_autore.php?id=<?php echo $id_autore; ?>">
      <input type="hidden" name="action" value="aggiungi_libro">
      <label for="id_libro">Seleziona un libro:</label>
      <select name="id_libro" id="id_libro" required>
          <option value="">-- Seleziona libro --</option>
          <?php foreach ($tuttiLibri as $libro): ?>
              <option value="<?php echo htmlspecialchars($libro['id_libro']); ?>">
                  <?php echo htmlspecialchars($libro['titolo']); ?>
              </option>
          <?php endforeach; ?>
      </select>
      <button type="submit">Aggiungi libro a questo autore</button>
  </form>
  
  <!-- Elenco dei libri associati all'autore con possibilità di rimozione -->
  <h3>Libri scritti</h3>
  <?php if (empty($libriAssoc)): ?>
      <p>Nessun libro associato.</p>
  <?php else: ?>
      <ul>
          <?php foreach ($libriAssoc as $libro): ?>
              <li>
                  <a href="dettaglio_libro.php?id=<?php echo urlencode($libro['id_libro']); ?>">
                      <?php echo htmlspecialchars($libro['titolo']); ?>
                  </a> (ISBN: <?php echo htmlspecialchars($libro['isbn']); ?>)
                  <?php
                  // Verifica se il libro ha più di un autore
                  $sqlCount = "SELECT COUNT(*) FROM libro_autore WHERE libro = ?";
                  $stmtCount = $pdo->prepare($sqlCount);
                  $stmtCount->execute([$libro['id_libro']]);
                  $numAutori = $stmtCount->fetchColumn();
                  if ($numAutori > 1):
                  ?>
                      <a href="dettaglio_autore.php?id=<?php echo $id_autore; ?>&remove_assoc=<?php echo $libro['id_libro']; ?>" onclick="return confirm('Sei sicuro di voler rimuovere questo autore dal libro?');">[Rimuovi dagli autori del libro]</a>
                  <?php endif; ?>
              </li>
          <?php endforeach; ?>
      </ul>
  <?php endif; ?>
  
  <p><a href="cerca_autori.php">Torna alla ricerca autori</a></p>
</div>
</body>
</html>
