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

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "Bostarter";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connessione al DB fallita: " . $conn->connect_error);
}

// Recupero delle viste
$top_creatori = $conn->query("SELECT * FROM v_top_creatori");
$progetti_vicini = $conn->query("SELECT * FROM v_progetti_vicini_completamento");
$top_finanziatori = $conn->query("SELECT * FROM v_top_finanziatori");

// Recupero progetti aperti
$progetti = [];
$result = $conn->query("CALL sp_visualizza_progetti_aperti()");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $progetti[] = $row;
    }
    $conn->next_result();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BOSTARTER - Home</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<nav class="navbar navbar-light bg-light px-4 shadow-sm justify-content-between">
  <a class="navbar-brand fw-bold text-primary" href="home.php">BOSTARTER</a>
  <div class="d-flex">
    <?php if (isset($_SESSION['email'])): ?>
      <form method="POST" class="d-inline me-2 align-self-center">
        <button type="submit" name="logout" class="btn btn-link text-decoration-underline py-1 px-0 align-baseline">Esci</button>
      </form>
      <a href="insert-skill.php" class="btn btn-outline-primary me-2">Gestisci</a>
      <a href="new-project.php" class="btn btn-success me-2">Crea</a>
    <?php else: ?>
      <a href="login.php" class="btn btn-primary">Login</a>
    <?php endif; ?>
  </div>
</nav>

<div class="container mt-5" style="padding-bottom: 120px;">
  <h2 class="mb-3">Classifica dei creatori più affidabili</h2>
  <table class="table table-bordered">
    <thead><tr><th>N°</th><th>Nickname</th><th>Affidabilità</th></tr></thead>
    <tbody>
      <?php
      $i = 1;
      while ($row = $top_creatori->fetch_assoc()): ?>
        <tr><td><?= $i ?>.</td><td><?= htmlspecialchars($row['Username']) ?></td><td><?= $row['Affidabilità'] ?>%</td></tr>
      <?php $i++; endwhile; ?>
      <?php while ($i <= 3): ?>
        <tr><td><?= $i ?>.</td><td></td><td></td></tr>
      <?php $i++; endwhile; ?>
    </tbody>
  </table>

  <h2 class="mb-3">Progetti vicini al completamento</h2>
  <table class="table table-bordered">
    <thead><tr><th>N°</th><th>Nome</th><th>Budget</th><th>Finanziato</th><th>Differenza</th></tr></thead>
    <tbody>
      <?php
      $i = 1;
      while ($row = $progetti_vicini->fetch_assoc()): ?>
        <tr>
          <td><?= $i ?>.</td>
          <td><?= htmlspecialchars($row['Nome']) ?></td>
          <td><?= $row['Budget'] ?></td>
          <td><?= $row['TotaleFinanziato'] ?></td>
          <td><?= $row['Differenza'] ?></td>
        </tr>
      <?php $i++; endwhile; ?>
      <?php while ($i <= 3): ?>
        <tr><td><?= $i ?>.</td><td></td><td></td><td></td><td></td></tr>
      <?php $i++; endwhile; ?>
    </tbody>
  </table>

  <h2 class="mb-3">Top Finanziatori</h2>
  <table class="table table-bordered">
    <thead><tr><th>N°</th><th>Nickname</th><th>Totale Finanziato</th></tr></thead>
    <tbody>
      <?php
      $i = 1;
      while ($row = $top_finanziatori->fetch_assoc()): ?>
        <tr><td><?= $i ?>.</td><td><?= htmlspecialchars($row['Username']) ?></td><td><?= $row['TotaleFinanziato'] ?>€</td></tr>
      <?php $i++; endwhile; ?>
      <?php while ($i <= 3): ?>
        <tr><td><?= $i ?>.</td><td></td><td></td></tr>
      <?php $i++; endwhile; ?>
    </tbody>
  </table>

  <h2 class="mt-5 mb-4">Progetti aperti</h2>
  <div class="row g-4">
    <?php foreach ($progetti as $progetto): ?>
      <div class="col-md-4">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($progetto['Titolo'] ?? $progetto['Nome']) ?></h5>
            <a href="dettagli_progetto.php?id=<?= urlencode($progetto['Nome']) ?>" class="btn btn-primary mt-2">Vedi dettagli →</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

</body>
</html>