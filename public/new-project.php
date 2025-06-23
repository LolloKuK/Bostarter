<?php
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

$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = $_POST['nome'];
    $data_inserimento = $_POST['data_inserimento'];
    $budget = $_POST['budget'];
    $descrizione = $_POST['descrizione'];
    $data_limite = $_POST['data_limite'];
    $email_creatore = $_SESSION['email'];

    $stmt = $conn->prepare("CALL sp_inserisci_progetto(?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisss", $nome, $data_inserimento, $budget, $descrizione, $data_limite, $email_creatore);

    if ($stmt->execute()) {
        $success_message = "Progetto pubblicato con successo!";
    } else {
        $error_message = "Errore durante la pubblicazione del progetto: " . $conn->error;
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
  <title>Nuovo Progetto - BOSTARTER</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <style>
    /* Rimuove le freccette da input type=number */
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
  </nav>

<div class="container mt-5">
  <h2 class="mb-4">Crea un nuovo progetto</h2>

  <?php if ($success_message): ?>
    <div class="alert alert-success"><?= $success_message ?></div>
  <?php elseif ($error_message): ?>
    <div class="alert alert-danger"><?= $error_message ?></div>
  <?php endif; ?>

  <form method="POST">
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
    <button type="submit" class="btn btn-primary">Pubblica progetto</button>
  </form>
</div>

</body>
</html>