<?php
session_start();

// Mostra errori se ci sono
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'log-mongo.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$user = "root";
$password = "";
$dbname = "Bostarter";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Recupera competenze disponibili dalla tabella Competenza
$lista_competenze = [];
$stmt = $conn->prepare("CALL sp_visualizza_competenze()");
$stmt->execute();
$res = $stmt->get_result();
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $lista_competenze[] = $row['Nome'];
    }
}
$stmt->close();
$conn->next_result();

$esito = "";

// Inserimento o modifica skill
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_skill']) && isset($_POST['register_livello'])) {
    $skill = $_POST['register_skill'];
    $livello = $_POST['register_livello'];
    $email = $_SESSION['email'];

    $stmt = $conn->prepare("CALL sp_aggiungi_o_modifica_skill(?, ?, ?)");
    $stmt->bind_param("ssi", $email, $skill, $livello);

    if ($stmt->execute()) {
        $esito = '<div class="alert alert-success mt-3">Skill inserita o aggiornata con successo!</div>';
        // Log successo inserimento skill
        scriviLogLocale(
            "skill_inserita_aggiornata",
            $email,
            "SkillUtente",
            ["skill" => $skill, "livello" => $livello, "esito" => "successo"]
        );
    } else {
        $esito = '<div class="alert alert-danger mt-3">Errore nell\'inserimento della skill.</div>';
        // Log errore inserimento skill
        scriviLogLocale(
            "skill_inserita_aggiornata",
            $email,
            "SkillUtente",
            ["skill" => $skill, "livello" => $livello, "esito" => "errore", "error_msg" => $stmt->error]
        );
    }

    $stmt->close();
    $conn->next_result();
}

// Recupero skill già associate all'utente
$skill_utente = [];
$stmt = $conn->prepare("CALL sp_visualizza_skill_utente(?)");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $skill_utente[] = $row;
}
$stmt->close();
$conn->next_result();
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BOSTARTER - Skill</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
</head>
<body>
  <nav class="navbar navbar-light bg-light px-4 shadow-sm d-flex justify-content-between">
    <a class="navbar-brand fw-bold text-primary" href="home.php">BOSTARTER</a>
    <a href="home.php" class="btn btn-outline-secondary">Torna indietro</a>
  </nav>

  <div class="container mt-5">
    <h3 class="mb-4">Inserisci o aggiorna una tua competenza</h3>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Skill</label>
        <select name="register_skill" class="form-select" required>
          <option value="">Seleziona una skill</option>
          <?php foreach ($lista_competenze as $comp): ?>
            <option value="<?= htmlspecialchars($comp) ?>"><?= htmlspecialchars($comp) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Livello (0-5)</label>
        <select name="register_livello" class="form-select" required>
          <option value="">Seleziona un livello</option>
          <?php for ($i = 0; $i <= 5; $i++): ?>
            <option value="<?= $i ?>"><?= $i ?></option>
          <?php endfor; ?>
        </select>
      </div>

      <button type="submit" class="btn btn-primary">Salva competenza</button>
    </form>

    <?= $esito ?>

    <?php if (!empty($skill_utente)): ?>
      <h4 class="mt-5">Le tue competenze attuali</h4>
      <table class="table table-bordered mt-3">
        <thead class="table-light">
          <tr>
            <th>Skill</th>
            <th>Livello</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($skill_utente as $entry): ?>
            <tr>
              <td><?= htmlspecialchars($entry['Nome']) ?></td>
              <td><?= $entry['Livello'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="text-muted mt-5">Nessuna competenza salvata.</div>
    <?php endif; ?>
  </div>
</body>
</html>