<?php
session_start();

// Mostra errori se ci sono
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

$esito = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_skill']) && isset($_POST['register_livello'])) {
    $skill = $_POST['register_skill'];
    $livello = $_POST['register_livello'];
    $email = $_SESSION['email'];

    $stmt = $conn->prepare("CALL sp_aggiungi_o_modifica_skill(?, ?, ?)");
    $stmt->bind_param("ssi", $email, $skill, $livello);

    if ($stmt->execute()) {
        $esito = '<div class="alert alert-success mt-3">Skill inserita o aggiornata con successo!</div>';
    } else {
        $esito = '<div class="alert alert-danger mt-3">Errore nell\'inserimento della skill.</div>';
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BOSTARTER - Inserisci Skill</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
</head>
<body>
  <nav class="navbar navbar-light bg-light px-4 shadow-sm d-flex justify-content-between">
    <a class="navbar-brand fw-bold text-primary" href="home.php">BOSTARTER</a>
    <a href="home.php" class="btn btn-outline-secondary">Torna indietro</a>
  </nav>

  <div class="container mt-5">
    <h3 class="mb-4">Inserisci la tua competenza</h3>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Skill</label>
        <select name="register_skill" class="form-select" required>
          <option value="">Seleziona una skill</option>
          <option value="Sql">Sql</option>
          <option value="Java">Java</option>
          <option value="Js">Js</option>
          <option value="Web Dev.">Web Dev.</option>
          <option value="Maintenance Hw">Maintenance Hw</option>
          <option value="Hw design">Hw design</option>
          <option value="Data analysis">Data analysis</option>
          <option value="Leadership">Leadership</option>
          <option value="Teamwork">Teamwork</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Livello (0-5)</label>
        <select name="register_livello" class="form-select" required>
          <option value="">Seleziona un livello</option>
          <option value="0">0</option>
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4">4</option>
          <option value="5">5</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary">Salva competenza</button>
    </form>

    <?= $esito ?>
  </div>
</body>
</html>