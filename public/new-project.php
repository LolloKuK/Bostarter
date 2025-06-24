<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "Bostarter";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connessione al DB fallita: " . $conn->connect_error);
}

// Recupero competenze per il menu a tendina
$lista_competenze = [];
$res = $conn->query("SELECT Nome FROM Competenza ORDER BY Nome ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $lista_competenze[] = $row['Nome'];
    }
}

$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = $_POST['nome'];
    $data_inserimento = $_POST['data_inserimento'];
    $budget = $_POST['budget'];
    $descrizione = $_POST['descrizione'];
    $data_limite = $_POST['data_limite'];
    $tipo_progetto = $_POST['tipo_progetto'];
    $email_creatore = $_SESSION['email'];

    // Controlla se esiste già un progetto con lo stesso nome
    $check = $conn->prepare("SELECT COUNT(*) FROM Progetto WHERE Nome = ?");
    $check->bind_param("s", $nome);
    $check->execute();
    $check->bind_result($count);
    $check->fetch();
    $check->close();

    if ($count > 0) {
        $error_message = "Esiste già un progetto con questo nome. Scegli un nome diverso.";
    } else {
        // Qui tutto il codice che già avevi (con prepare, execute, immagini, ecc.)
        // A partire da:
        if ($tipo_progetto === "hardware") {
            $stmt = $conn->prepare("CALL sp_inserisci_progetto_hardware(?, ?, ?, ?, ?, ?)");
        } else {
            $stmt = $conn->prepare("CALL sp_inserisci_progetto_software(?, ?, ?, ?, ?, ?)");
        }

        $stmt->bind_param("ssisss", $nome, $data_inserimento, $budget, $descrizione, $data_limite, $email_creatore);

        if ($stmt->execute()) {
        $conn->next_result();

        if ($tipo_progetto === "hardware" && isset($_POST['componenti_nome'])) {
            foreach ($_POST['componenti_nome'] as $i => $nome_componente) {
                $prezzo = $_POST['componenti_prezzo'][$i];
                $descr = $_POST['componenti_descrizione'][$i];
                $quantita = $_POST['componenti_quantita'][$i];

                $stmt_comp = $conn->prepare("CALL sp_inserisci_componente(?, ?, ?, ?, ?)");
                $stmt_comp->bind_param("sisis", $nome_componente, $prezzo, $descr, $quantita, $nome);
                $stmt_comp->execute();
                $stmt_comp->close();
                $conn->next_result();
            }
        } elseif ($tipo_progetto === "software" && isset($_POST['profili_nome'])) {
            foreach ($_POST['profili_nome'] as $i => $nome_profilo) {
                $competenza = $_POST['profili_competenza'][$i];
                $livello = $_POST['profili_livello'][$i];

                $stmt_prof = $conn->prepare("CALL sp_inserisci_profilo(?, ?, ?, ?)");
                $stmt_prof->bind_param("ssis", $nome_profilo, $competenza, $livello, $nome);
                $stmt_prof->execute();
                $stmt_prof->close();
                $conn->next_result();
            }
        }

        $cartella_upload = "images/";
        if (!is_dir($cartella_upload)) {
            mkdir($cartella_upload, 0777, true);
        }

        foreach ($_FILES['immagini']['tmp_name'] as $index => $tmp_name) {
            $nome_originale = basename($_FILES['immagini']['name'][$index]);
            $percorso_finale = $cartella_upload . time() . "_" . $nome_originale;

            if (move_uploaded_file($tmp_name, $percorso_finale)) {
                $relative_path = "images/" . basename($percorso_finale);
                $stmt_foto = $conn->prepare("CALL sp_inserisci_foto(?, ?)");
                $stmt_foto->bind_param("ss", $relative_path, $nome);
                $stmt_foto->execute();
                $stmt_foto->close();
                $conn->next_result();
            }
        }

        if (isset($_POST['reward_descrizione']) && isset($_FILES['reward_foto'])) {
          foreach ($_POST['reward_descrizione'] as $i => $descrizione_reward) {
            $nome_file = basename($_FILES['reward_foto']['name'][$i]);
            $tmp_name = $_FILES['reward_foto']['tmp_name'][$i];
            $path_finale = "images/" . time() . "_" . $nome_file;

            if (move_uploaded_file($tmp_name, $path_finale)) {
              $relative_path = "images/" . basename($path_finale);

              $stmt_reward = $conn->prepare("CALL sp_inserisci_reward(?, ?, ?)");
              $stmt_reward->bind_param("sss", $descrizione_reward, $relative_path, $nome);
              $stmt_reward->execute();
              $stmt_reward->close();
              $conn->next_result();
            }
          }
        }

        $success_message = "Progetto pubblicato con successo!";
    } else {
        $error_message = "Errore: " . $conn->error;
    }

    $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BOSTARTER - New Project</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script>
    const competenzeDisponibili = <?= json_encode($lista_competenze) ?>;

    function toggleTipoProgetto(tipo) {
      document.getElementById("hardware_fields").style.display = tipo === 'hardware' ? 'block' : 'none';
      document.getElementById("software_fields").style.display = tipo === 'software' ? 'block' : 'none';
    }

    function addComponente() {
      const container = document.getElementById("hardware_fields");
      const html = `<div class="row mb-2">
        <div class="col"><input type="text" name="componenti_nome[]" class="form-control" placeholder="Nome" required></div>
        <div class="col"><input type="number" name="componenti_prezzo[]" class="form-control" placeholder="Prezzo" required></div>
        <div class="col"><input type="text" name="componenti_descrizione[]" class="form-control" placeholder="Descrizione" required></div>
        <div class="col"><input type="number" name="componenti_quantita[]" class="form-control" placeholder="Quantità" required></div>
      </div>`;
      container.insertAdjacentHTML("beforeend", html);
    }

    function addProfilo() {
      const container = document.getElementById("software_fields");
      const livelli = Array.from({length: 6}, (_, i) => `<option value=\"${i}\">${i}</option>`).join('');
      const competenze = competenzeDisponibili.map(c => `<option value=\"${c}\">${c}</option>`).join('');
      const html = `<div class="row mb-2">
        <div class="col"><input type="text" name="profili_nome[]" class="form-control" placeholder="Nome profilo" required></div>
        <div class="col">
          <select name="profili_competenza[]" class="form-select" required>
            <option value=\"\">Seleziona competenza</option>${competenze}
          </select>
        </div>
        <div class="col">
          <select name="profili_livello[]" class="form-select" required>
            <option value=\"\">Seleziona livello</option>${livelli}
          </select>
        </div>
      </div>`;
      container.insertAdjacentHTML("beforeend", html);
    }

    function addReward() {
      const container = document.getElementById("reward_fields");
      const html = `<div class="row mb-2">
        <div class="col">
          <input type="text" name="reward_descrizione[]" class="form-control" placeholder="Descrizione" maxlength="50" required>
        </div>
        <div class="col">
          <input type="file" name="reward_foto[]" class="form-control" accept="image/*" required>
        </div>
      </div>`;
      container.insertAdjacentHTML("beforeend", html);
    }

  </script>
  <style>
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }
    input[type=number] {
      appearance: textfield;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-light bg-light px-4 shadow-sm">
  <a class="navbar-brand fw-bold text-primary" href="home.php">BOSTARTER</a>
  <a href="home.php" class="btn btn-outline-secondary">Torna indietro</a>
</nav>

<div class="container mt-5" style="padding-bottom: 120px;">
  <h2 class="mb-4">Crea un nuovo progetto</h2>

  <?php if ($success_message): ?>
    <div class="alert alert-success"><?= $success_message ?></div>
  <?php elseif ($error_message): ?>
    <div class="alert alert-danger"><?= $error_message ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
      <label for="nome" class="form-label">Titolo del progetto</label>
      <input type="text" class="form-control" id="nome" name="nome" required maxlength="20">
    </div>
    <div class="mb-3">
      <label for="data_inserimento" class="form-label">Data di inserimento</label>
      <input type="date" class="form-control" id="data_inserimento" name="data_inserimento" required>
    </div>
    <div class="mb-3">
      <label for="budget" class="form-label">Budget (in €)</label>
      <input type="number" class="form-control" id="budget" name="budget" required>
    </div>
    <div class="mb-3">
      <label for="descrizione" class="form-label">Descrizione</label>
      <input type="text" class="form-control" id="descrizione" name="descrizione" required maxlength="200">
    </div>
    <div class="mb-3">
      <label for="data_limite" class="form-label">Data limite</label>
      <input type="date" class="form-control" id="data_limite" name="data_limite" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Tipo di progetto</label><br>
      <input type="radio" name="tipo_progetto" value="hardware" onclick="toggleTipoProgetto('hardware')" required> Hardware
      <input type="radio" name="tipo_progetto" value="software" onclick="toggleTipoProgetto('software')" required class="ms-3"> Software
    </div>

    <div id="hardware_fields" style="display:none">
      <h5>Componenti</h5>
      <button type="button" class="btn btn-secondary btn-sm mb-2" onclick="addComponente()">Aggiungi componente</button>
    </div>

    <div id="software_fields" style="display:none">
      <h5>Profili richiesti</h5>
      <button type="button" class="btn btn-secondary btn-sm mb-2" onclick="addProfilo()">Aggiungi profilo</button>
    </div>

    <hr>
    <h4>Immagini del progetto</h4>
    <div class="mb-3">
      <label for="immagini" class="form-label">Carica immagini (puoi selezionare più file)</label>
      <input type="file" class="form-control" name="immagini[]" id="immagini" accept="image/*" multiple>
    </div>

    <hr>
    <h4>Reward del progetto</h4>
    <div id="reward_fields">
      <button type="button" class="btn btn-secondary btn-sm mb-2" onclick="addReward()">Aggiungi reward</button>
    </div>

    <button type="submit" class="btn btn-primary">Pubblica progetto</button>
  </form>
</div>
</body>
</html>