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

// Reward
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
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BOSTARTER - Project Details</title>
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
        <p><strong>Email Creatore:</strong> <?= $progetto['EmailUtente'] ?></p>
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

    <a href="home.php" class="btn btn-secondary">Torna alla Home</a>
  </div>
</body>
</html>