<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['admin_email'])) {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "Bostarter";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Aggiunta competenza
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["aggiungi_competenza"])) {
    $nome = trim($_POST["nome_competenza"]);
    if (!empty($nome)) {
        $stmt = $conn->prepare("CALL sp_aggiungi_competenza(?)");
        $stmt->bind_param("s", $nome);
        $stmt->execute();
        $stmt->close();
        $conn->next_result();
    }
    header("Location: admin.php");
    exit();
}

// Eliminazione competenza
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["elimina_competenza"])) {
    $nome = $_POST["nome_da_eliminare"];
    $stmt = $conn->prepare("CALL sp_elimina_competenza(?)");
    $stmt->bind_param("s", $nome);
    $stmt->execute();
    $stmt->close();
    $conn->next_result();
    header("Location: admin.php");
    exit();
}

// Recupero competenze
$competenze = [];
$res = $conn->query("SELECT Nome FROM Competenza");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $competenze[] = $row['Nome'];
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BOSTARTER - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
</head>
<body>
  <nav class="navbar navbar-light bg-light px-4 shadow-sm">
    <a class="navbar-brand fw-bold text-primary" href="home.php">BOSTARTER</a>
    <form method="POST" class="ms-auto">
      <button type="submit" name="logout" class="btn btn-link text-decoration-underline">Esci</button>
    </form>
  </nav>

  <div class="container mt-5">
    <h2 class="mb-4">Gestione Competenze</h2>

    <!-- Aggiungi competenza -->
    <form method="POST" class="mb-4">
      <div class="row g-2 align-items-center">
        <div class="col-auto">
          <input type="text" name="nome_competenza" class="form-control" placeholder="Nuova competenza" required>
        </div>
        <div class="col-auto">
          <button type="submit" name="aggiungi_competenza" class="btn btn-success">Aggiungi</button>
        </div>
      </div>
    </form>

    <!-- Elenco competenze -->
    <table class="table table-bordered">
      <thead class="table-light">
        <tr>
          <th>Nome</th>
          <th style="width: 100px;">Azione</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($competenze as $nome): ?>
          <tr>
            <td><?= htmlspecialchars($nome) ?></td>
            <td>
              <form method="POST" class="d-inline">
                <input type="hidden" name="nome_da_eliminare" value="<?= htmlspecialchars($nome) ?>">
                <button type="submit" name="elimina_competenza" class="btn btn-sm btn-danger">Elimina</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (count($competenze) === 0): ?>
          <tr><td colspan="2" class="text-center text-muted">Nessuna competenza presente</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>