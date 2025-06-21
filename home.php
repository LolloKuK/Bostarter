<?php
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
      <a href="insert-skill.php" class="btn btn-outline-primary me-2">Skill</a>
      <a href="new-project.php" class="btn btn-success me-2">Crea</a>
      <a href="admin.php" class="btn btn-outline-primary">Dashboard</a>
    <?php else: ?>
      <a href="login.php" class="btn btn-primary">Login</a>
    <?php endif; ?>
  </div>
</nav>

<div class="container mt-5">
  <h2 class="mb-4">Progetti aperti</h2>
  <div class="row g-4">
    <?php foreach ($progetti as $progetto): ?>
      <div class="col-md-4">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($progetto['Titolo']) ?></h5>
            <a href="dettagli_progetto.php?id=<?= $progetto['ID'] ?>" class="btn btn-primary mt-2">Vedi dettagli →</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

</body>
</html>