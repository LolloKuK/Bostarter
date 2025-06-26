<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_GET['nome'])) {
    die("Nessun progetto specificato.");
}

$nome_progetto = $_GET['nome'];

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "Bostarter";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connessione al DB fallita: " . $conn->connect_error);
}

// Dati progetto
$stmt = $conn->prepare("CALL sp_dettagli_progetto(?)");
$stmt->bind_param("s", $nome_progetto);
$stmt->execute();
$progetto_result = $stmt->get_result();
$progetto = $progetto_result->fetch_assoc();
$stmt->close();
$conn->next_result();

// Tipo progetto
$stmt = $conn->prepare("CALL sp_tipo_progetto(?)");
$stmt->bind_param("s", $nome_progetto);
$stmt->execute();
$tipo_result = $stmt->get_result();
$tipo_row = $tipo_result->fetch_assoc();
$tipo = $tipo_row['Tipo'];
$stmt->close();
$conn->next_result();

// Foto
$stmt = $conn->prepare("CALL sp_foto_progetto(?)");
$stmt->bind_param("s", $nome_progetto);
$stmt->execute();
$foto_result = $stmt->get_result();
$fotos = [];
while ($row = $foto_result->fetch_assoc()) {
    $fotos[] = $row['Percorso'];
}
$stmt->close();
$conn->next_result();

// Reward (con ID per il form)
$stmt = $conn->prepare("CALL sp_reward_progetto(?)");
$stmt->bind_param("s", $nome_progetto);
$stmt->execute();
$reward_result = $stmt->get_result();
$rewards = [];
while ($row = $reward_result->fetch_assoc()) {
    $rewards[] = $row;
}
$stmt->close();
$conn->next_result();

// Profili (software)
$profili = [];
if ($tipo === "software") {
    $stmt = $conn->prepare("CALL sp_profili_progetto(?)");
    $stmt->bind_param("s", $nome_progetto);
    $stmt->execute();
    $profilo_result = $stmt->get_result();
    while ($row = $profilo_result->fetch_assoc()) {
        $profili[] = $row;
    }
    $stmt->close();
    $conn->next_result();
}

// Componenti (hardware)
$componenti = [];
if ($tipo === "hardware") {
  $stmt = $conn->prepare("CALL sp_componenti_progetto(?)");
  $stmt->bind_param("s", $nome_progetto);
  $stmt->execute();
  $componenti_result = $stmt->get_result();
  while ($row = $componenti_result->fetch_assoc()) {
      $componenti[] = $row;
  }
  $stmt->close();
  $conn->next_result();
}

// Commenti + Risposte
$commenti_risposte = [];

$stmt = $conn->prepare("CALL sp_commenti_con_risposte(?)");
$stmt->bind_param("s", $nome_progetto);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $id = $row['IdCommento'];
    if (!isset($commenti_risposte[$id])) {
        $commenti_risposte[$id] = [
            'id' => $id,
            'testo' => $row['TestoCommento'],
            'data' => $row['DataCommento'],
            'email' => $row['EmailCommentatore'],
            'username' => $row['UsernameCommentatore'],
            'risposte' => []
        ];
    }
    if ($row['IdRisposta']) {
        $commenti_risposte[$id]['risposte'][] = [
            'testo' => $row['TestoRisposta'],
            'email' => $row['EmailRispondente'],
            'username' => $row['UsernameRispondente']
        ];
    }
}
$stmt->close();
$conn->next_result();

// Gestione nuovo commento
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['invia_commento'])) {
    $testo_commento = trim($_POST['testo_commento']);
    $email = $_SESSION['email'] ?? '';
    if ($email !== '' && $email !== $progetto['EmailUtente']) {
        $stmt = $conn->prepare("CALL sp_commenta_progetto(?, ?, ?)");
        $stmt->bind_param("sss", $testo_commento, $email, $nome_progetto);
        $stmt->execute();
        $stmt->close();
        $conn->next_result();
        header("Location: project-info.php?nome=" . urlencode($nome_progetto));
        exit;
    }
}

// Gestione risposta a commento
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['invia_risposta'])) {
    $id_commento = (int) $_POST['id_commento'];
    $testo_risposta = trim($_POST['testo_risposta']);
    $email = $_SESSION['email'] ?? '';

    if ($email === $progetto['EmailUtente']) {
        // Verifica se già esiste una risposta per questo commento
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Risposta WHERE IdCommento = ?");
        $stmt->bind_param("i", $id_commento);
        $stmt->execute();
        $stmt->bind_result($risposte_esistenti);
        $stmt->fetch();
        $stmt->close();

        if ($risposte_esistenti == 0) {
            $stmt = $conn->prepare("CALL sp_rispondi_a_commento(?, ?, ?)");
            $stmt->bind_param("iss", $id_commento, $email, $testo_risposta);
            $stmt->execute();
            $stmt->close();
            $conn->next_result();
        }

        header("Location: project-info.php?nome=" . urlencode($nome_progetto));
        exit;
    }
}

// GESTIONE FINANZIAMENTO
$messaggio_successo = "";
$messaggio_errore = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finanzia'])) {
    $importo = (int) $_POST['importo'];
    $codice_reward = $_POST['reward'] !== "" ? (int) $_POST['reward'] : NULL;
    $email_utente = $_SESSION['email'] ?? '';

    if ($email_utente === '' || $email_utente === $progetto['EmailUtente']) {
        $messaggio_errore = "Non puoi finanziare se non sei autenticato o sei il creatore del progetto.";
    } else {
        try {
            $stmt = $conn->prepare("CALL sp_finanzia_progetto(?, ?, ?, ?)");
            $stmt->bind_param("issi", $importo, $email_utente, $nome_progetto, $codice_reward);
            $stmt->execute();
            $messaggio_successo = "Finanziamento registrato con successo!";
        } catch (mysqli_sql_exception $e) {
            $messaggio_errore = "Errore: " . $e->getMessage();
        } finally {
            if (isset($stmt)) $stmt->close();
            $conn->next_result();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BOSTARTER - Dettagli Progetto</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <nav class="navbar navbar-light bg-light px-4 shadow-sm mb-4">
    <a class="navbar-brand fw-bold text-primary" href="home.php">BOSTARTER</a>
  </nav>

  <div class="container mt-5" style="padding-bottom: 120px;">
    <h1 class="mb-4 text-center text-primary fw-bold"><?= htmlspecialchars($progetto['Nome']) ?></h1>

    <div class="card mb-4 shadow">
      <div class="card-body">
        <p><strong>Descrizione:</strong> <?= htmlspecialchars($progetto['Descrizione']) ?></p>
        <p><strong>Data Inserimento:</strong> <?= $progetto['DataInserimento'] ?></p>
        <p><strong>Data Limite:</strong> <?= $progetto['DataLimite'] ?></p>
        <p><strong>Budget:</strong> €<?= $progetto['Budget'] ?></p>
        <p><strong>Stato:</strong> <?= ucfirst($progetto['Stato']) ?></p>
        <p><strong>Creatore:</strong> <?= htmlspecialchars($progetto['NomeCreatore']) ?></p>
        <p><strong>Tipo Progetto:</strong> <?= ucfirst($tipo) ?></p>
      </div>
    </div>

    <?php if (!empty($fotos)): ?>
      <h4 class="mb-2">Foto</h4>
      <div class="row mb-4">
        <?php foreach ($fotos as $path): ?>
          <div class="col-md-4 mb-3">
            <img src="<?= htmlspecialchars($path) ?>" class="img-fluid rounded border" style="max-width: 100%; max-height: 250px; object-fit: cover;" alt="Foto progetto">
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($rewards)): ?>
      <h4 class="mb-2">Reward</h4>
      <ul class="list-group mb-4">
        <?php foreach ($rewards as $reward): ?>
          <li class="list-group-item d-flex align-items-center">
            <img src="<?= htmlspecialchars($reward['Foto']) ?>" alt="Reward" style="width: 60px; height: 60px; object-fit: cover; margin-right: 15px;" class="rounded border">
            <div>
              <strong><?= htmlspecialchars($reward['Descrizione']) ?></strong>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if (!empty($profili)): ?>
      <h4 class="mb-2">Profili Richiesti</h4>
      <ul class="list-group mb-4">
        <?php foreach ($profili as $profilo): ?>
          <li class="list-group-item">
            <strong><?= htmlspecialchars($profilo['Nome']) ?></strong> - <?= htmlspecialchars($profilo['Competenza']) ?> (Livello <?= $profilo['Livello'] ?>)
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if (!empty($componenti)): ?>
      <h4 class="mb-2">Componenti Hardware</h4>
      <ul class="list-group mb-4">
        <?php foreach ($componenti as $comp): ?>
          <li class="list-group-item">
            <strong><?= htmlspecialchars($comp['Nome']) ?></strong> - <?= htmlspecialchars($comp['Descrizione']) ?> | Prezzo: €<?= $comp['Prezzo'] ?> | Quantità: <?= $comp['Quantità'] ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <h4 class="mb-3">Commenti</h4>
    <?php if (empty($commenti_risposte)): ?>
      <div class="alert alert-light border text-muted">Non ci sono ancora commenti su questo progetto.</div>
    <?php else: ?>
      <?php foreach ($commenti_risposte as $commento): ?>
        <div class="card mb-3">
          <div class="card-body">
            <p><strong><?= htmlspecialchars($commento['username']) ?></strong> <small class="text-muted">(<?= $commento['data'] ?>)</small></p>
            <p><?= htmlspecialchars($commento['testo']) ?></p>

            <?php if (!empty($commento['risposte'])): ?>
              <div class="ms-4">
                <?php foreach ($commento['risposte'] as $risposta): ?>
                  <div class="border-start ps-3 mb-2">
                    <p><strong><?= htmlspecialchars($risposta['username']) ?> (Creatore)</strong>: <?= htmlspecialchars($risposta['testo']) ?></p>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <?php if (
                isset($_SESSION['email']) &&
                $_SESSION['email'] === $progetto['EmailUtente'] &&
                empty($commento['risposte'])  // mostra il form solo se non ci sono risposte
            ): ?>
              <form method="POST" class="mt-3">
                <input type="hidden" name="id_commento" value="<?= $commento['id'] ?>">
                <div class="mb-2">
                  <textarea name="testo_risposta" rows="2" class="form-control" placeholder="Rispondi al commento..." required></textarea>
                </div>
                <button type="submit" name="invia_risposta" class="btn btn-sm btn-outline-primary">Rispondi</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['email']) && $_SESSION['email'] !== $progetto['EmailUtente']): ?>
      <form method="POST" class="mb-5">
        <div class="mb-3">
          <label for="testo_commento" class="form-label">Lascia un commento</label>
          <textarea name="testo_commento" id="testo_commento" class="form-control" rows="3" required></textarea>
        </div>
        <button type="submit" name="invia_commento" class="btn btn-primary">Invia commento</button>
      </form>
    <?php endif; ?>

    <?php if (isset($_SESSION['email']) && $_SESSION['email'] !== $progetto['EmailUtente']): ?>
      <div class="card mb-5 shadow">
        <div class="card-body">
          <h4 class="mb-3">Sostieni questo progetto</h4>

          <?php if ($messaggio_successo): ?>
            <div class="alert alert-success"><?= $messaggio_successo ?></div>
          <?php elseif ($messaggio_errore): ?>
            <div class="alert alert-danger"><?= $messaggio_errore ?></div>
          <?php endif; ?>

          <form method="POST">
            <div class="mb-3">
              <label for="importo" class="form-label">Importo (€)</label>
              <input type="number" name="importo" id="importo" min="1" class="form-control" required>
            </div>

            <div class="mb-3">
              <label for="reward" class="form-label">Seleziona una reward (opzionale)</label>
              <select name="reward" id="reward" class="form-select">
                <option value="">Nessuna reward</option>
                <?php foreach ($rewards as $reward): ?>
                  <option value="<?= $reward['Id'] ?>"><?= htmlspecialchars($reward['Descrizione']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <button type="submit" name="finanzia" class="btn btn-success">Finanzia il progetto</button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <a href="home.php" class="btn btn-secondary">Torna alla Home</a>
  </div>
</body>
</html>