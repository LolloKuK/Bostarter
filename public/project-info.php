<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once 'log-mongo.php';

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

        scriviLogLocale(
            "commento_inserito",
            $email,
            "Progetto",
            ["nome_progetto" => $nome_progetto, "testo_commento" => $testo_commento]
        );

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
        $risposte_esistenti = -1;
        $stmt = $conn->prepare("CALL sp_verifica_risposta_commento(?, @esiste)");
        $stmt->bind_param("i", $id_commento);
        $stmt->execute();
        $stmt->close();
        $conn->next_result();

        $res = $conn->query("SELECT @esiste AS esiste");
        if ($res) {
            $row = $res->fetch_assoc();
            $risposte_esistenti = $row['esiste'];
        }

        if ($risposte_esistenti == 0) {
            $stmt = $conn->prepare("CALL sp_rispondi_a_commento(?, ?, ?)");
            $stmt->bind_param("iss", $id_commento, $email, $testo_risposta);
            $stmt->execute();
            $stmt->close();
            $conn->next_result();

            scriviLogLocale(
                "risposta_commento_inserita",
                $email,
                "Progetto",
                ["nome_progetto" => $nome_progetto, "id_commento" => $id_commento, "testo_risposta" => $testo_risposta]
            );
        }

        header("Location: project-info.php?nome=" . urlencode($nome_progetto));
        exit;
    }
}

// Gestione finanziamento
$messaggio_successo = "";
$messaggio_errore = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finanzia'])) {
    $importo = (int) $_POST['importo'];
    $codice_reward = $_POST['reward'] !== "" ? (int) $_POST['reward'] : NULL;
    $email_utente = $_SESSION['email'] ?? '';

    if ($email_utente === '') {
        $messaggio_errore = "Non puoi finanziare se non sei autenticato";
    } else {
        try {
            $stmt = $conn->prepare("CALL sp_finanzia_progetto(?, ?, ?, ?)");
            $stmt->bind_param("issi", $importo, $email_utente, $nome_progetto, $codice_reward);
            $stmt->execute();

            scriviLogLocale(
                "finanziamento_inserito",
                $email_utente,
                "Progetto",
                ["nome_progetto" => $nome_progetto, "importo" => $importo, "codice_reward" => $codice_reward]
            );

            $messaggio_successo = "Finanziamento registrato con successo!";
        } catch (mysqli_sql_exception $e) {
            $messaggio_errore = "Impossibile inserire più di un finanziamento nella stessa data";

            scriviLogLocale(
                "finanziamento_fallito",
                $email_utente,
                "Progetto",
                ["nome_progetto" => $nome_progetto, "importo" => $importo, "errore" => $e->getMessage()]
            );
        } finally {
            if (isset($stmt)) $stmt->close();
            $conn->next_result();
        }
    }
}

// Verifica se l'utente può candidarsi
$utente_corrente = $_SESSION['email'] ?? '';
$candidabile = false;

$profili_candidabili = [];

if ($tipo === "software" && $utente_corrente !== '' && $utente_corrente !== $progetto['EmailUtente']) {
    foreach ($profili as $p) {
        $esiste = 0;
        $stmt = $conn->prepare("CALL sp_utente_ha_skill(?, ?, ?, @valido)");
        $stmt->bind_param("ssi", $utente_corrente, $p['Competenza'], $p['Livello']);
        $stmt->execute();
        $stmt->close();
        $conn->next_result();

        $res = $conn->query("SELECT @valido AS valido");
        if ($res) {
            $row = $res->fetch_assoc();
            if ($row['valido'] > 0) {
                $profili_candidabili[] = $p;
                $candidabile = true;
            }
        }
    }
}

// Gestione invio candidatura
$messaggio_candidatura = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['invia_candidatura'])) {
    $id_profilo = (int) ($_POST['profilo'] ?? 0);
    $utente_corrente = $_SESSION['email'] ?? '';

    if ($id_profilo > 0 && $utente_corrente !== '') {

        // Controllo se esiste già candidatura per lo stesso profilo
        $gia_inviata = 0;
        $stmt = $conn->prepare("CALL sp_verifica_candidatura(?, ?, @gia)");
        $stmt->bind_param("si", $utente_corrente, $id_profilo);
        $stmt->execute();
        $stmt->close();
        $conn->next_result();

        $res = $conn->query("SELECT @gia AS gia");
        if ($res) {
            $row = $res->fetch_assoc();
            if ($row['gia'] > 0) {
                $messaggio_candidatura = "Hai già inviato una candidatura per questo profilo.";

                scriviLogLocale(
                    "candidatura_fallita",
                    $utente_corrente,
                    "Progetto",
                    ["nome_progetto" => $nome_progetto, "id_profilo" => $id_profilo, "motivo" => "già candidata"]
                );
            } else {
                // Inserisci candidatura
                $stmt = $conn->prepare("CALL sp_candidati_profilo(?, ?)");
                $stmt->bind_param("si", $utente_corrente, $id_profilo);
                $stmt->execute();
                $stmt->close();
                $conn->next_result();

                scriviLogLocale(
                    "candidatura_inviata",
                    $utente_corrente,
                    "Progetto",
                    ["nome_progetto" => $nome_progetto, "id_profilo" => $id_profilo]
                );

                header("Location: project-info.php?nome=" . urlencode($nome_progetto));
                exit;
            }
        }
    }
}

// Caricamento candidature (se creatore)
$candidature = [];
if ($utente_corrente === $progetto['EmailUtente']) {
    $stmt = $conn->prepare("CALL sp_visualizza_candidature_progetto(?, ?)");
    $stmt->bind_param("ss", $utente_corrente, $nome_progetto);
    $stmt->execute();
    $ris = $stmt->get_result();
    while ($row = $ris->fetch_assoc()) {
        $candidature[] = $row;
    }
    $stmt->close();
    $conn->next_result();
}

// Caricamento candidature personali (se candidato)
$candidature_utente = [];
if ($utente_corrente !== '' && $utente_corrente !== $progetto['EmailUtente']) {
    $stmt = $conn->prepare("CALL sp_visualizza_candidature_utente(?, ?)");
    $stmt->bind_param("ss", $utente_corrente, $nome_progetto);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $candidature_utente[] = $row;
    }
    $stmt->close();
    $conn->next_result();
}

// Gestione accettazione/rifiuto candidatura
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_candidatura'])) {
    $id = (int) $_POST['id_candidatura'];
    $nuovo_stato = '';

    if (isset($_POST['accetta_candidatura'])) {
        $nuovo_stato = 'accettata';
    } elseif (isset($_POST['rifiuta_candidatura'])) {
        $nuovo_stato = 'rifiutata';
    }

    if ($nuovo_stato !== '') {
        $stmt = $conn->prepare("CALL sp_aggiorna_stato_candidatura(?, ?)");
        $stmt->bind_param("is", $id, $nuovo_stato);
        $stmt->execute();
        $stmt->close();
        $conn->next_result();

        scriviLogLocale(
            "stato_candidatura_aggiornato",
            $_SESSION['email'] ?? '',
            "Progetto",
            ["nome_progetto" => $nome_progetto, "id_candidatura" => $id, "nuovo_stato" => $nuovo_stato]
        );

        header("Location: project-info.php?nome=" . urlencode($nome_progetto));
        exit;
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
    <a href="home.php" class="btn btn-outline-secondary">Torna indietro</a>
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

    <?php if (isset($_SESSION['email'])): ?>
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

    <?php if ($tipo === "software"): ?>
      <h4 class="mb-2">Candidature</h4>
      <?php if ($utente_corrente === $progetto['EmailUtente']): ?>
        <?php if (empty($candidature)): ?>
          <div class="alert alert-light border">Nessuna candidatura ricevuta.</div>
        <?php else: ?>
          <ul class="list-group mb-4">
            <?php foreach ($candidature as $c): ?>
              <li class="list-group-item">
                <strong><?= htmlspecialchars($c['Username']) ?></strong> si è candidato per <strong><?= htmlspecialchars($c['NomeProfilo']) ?></strong>
                <br><span class="text-muted"><?= htmlspecialchars($c['Competenza']) ?> - Livello <?= $c['Livello'] ?></span>
                <span class="badge bg-secondary ms-2"><?= htmlspecialchars($c['Stato']) ?></span>

                <?php if ($c['Stato'] === 'in attesa'): ?>
                  <form method="POST" class="d-inline ms-3">
                    <input type="hidden" name="id_candidatura" value="<?= $c['IdCandidatura'] ?>">
                    <button type="submit" name="accetta_candidatura" class="btn btn-sm btn-success">Accetta</button>
                  </form>
                  <form method="POST" class="d-inline">
                    <input type="hidden" name="id_candidatura" value="<?= $c['IdCandidatura'] ?>">
                    <button type="submit" name="rifiuta_candidatura" class="btn btn-sm btn-danger">Rifiuta</button>
                  </form>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

      <?php elseif ($candidabile): ?>
        <?php if ($messaggio_candidatura): ?>
          <div class="alert alert-warning"><?= htmlspecialchars($messaggio_candidatura) ?></div>
        <?php endif; ?>
        <?php if (!empty($candidature_utente)): ?>
          <div class="mb-3">
            <h5>Le tue candidature</h5>
            <ul class="list-group">
              <?php foreach ($candidature_utente as $c): ?>
                <li class="list-group-item">
                  Hai inviato candidatura per <strong><?= htmlspecialchars($c['NomeProfilo']) ?></strong> (<?= $c['Competenza'] ?> - Livello <?= $c['Livello'] ?>)
                  <span class="badge bg-secondary ms-2"><?= htmlspecialchars($c['Stato']) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        <form method="POST" class="mb-4">
          <div class="mb-3">
            <label for="profilo" class="form-label">Seleziona un profilo</label>
            <select name="profilo" id="profilo" class="form-select" required>
              <?php foreach ($profili_candidabili as $p): ?>
                <option value="<?= htmlspecialchars($p['Id']) ?>">
                    <?= htmlspecialchars($p['Nome']) ?> - <?= htmlspecialchars($p['Competenza']) ?> (Livello <?= $p['Livello'] ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" name="invia_candidatura" class="btn btn-primary">Invia candidatura</button>
        </form>
      <?php else: ?>
        <div class="alert alert-warning">Non possiedi le competenze richieste per candidarti a questo progetto.</div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>
</html>