<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "Bostarter";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connessione al DB fallita: " . $conn->connect_error);
}

// Registrazione
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    $username = $_POST["register_username"];
    $email = $_POST["register_email"];
    $password = $_POST["register_password"];
    $nome = $_POST["register_nome"];
    $cognome = $_POST["register_cognome"];
    $anno = intval($_POST["register_anno"]);
    $luogo = $_POST["register_luogo"];

    $stmt = $conn->prepare("CALL sp_registra_utente(?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssis", $username, $email, $password, $nome, $cognome, $anno, $luogo);

    if ($stmt->execute()) {
        $_SESSION['registrazione_successo'] = true;
        header("Location: login.php");
        exit();
    } else {
        echo "<script>alert('Errore: " . $stmt->error . "');</script>";
    }

    $stmt->close();
    $conn->next_result();
}

// Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $email = $_POST["login_email"];
    $password = $_POST["login_password"];

    $stmt = $conn->prepare("CALL sp_autentica_utente(?, ?)");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $utente = $result->fetch_assoc();
        $_SESSION['utente'] = $utente;
        $_SESSION['email'] = $utente['Email'];
        header("Location: home.php");
        exit();
    } else {
        $login_error = "Email o password errate, riprovare.";
    }

    $stmt->close();
    $conn->next_result();
}

// Admin Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["admin_login"])) {
    $email = $_POST["admin_email"];
    $password = $_POST["admin_password"];
    $codice = intval($_POST["admin_codice"]);

    $stmt = $conn->prepare("CALL sp_autentica_amministratore(?, ?, ?)");
    $stmt->bind_param("ssi", $email, $password, $codice);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $_SESSION['admin_email'] = $email;
        header("Location: admin.php");
        exit();
    } else {
        $admin_error = "Credenziali da amministratore errate.";
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
  <title>BOSTARTER - Login</title>
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

<div class="container mt-5" style="max-width: 500px;">
  <?php if (isset($_SESSION['registrazione_successo'])): ?>
    <div class="text-success fw-semibold text-center mb-3">
      Registrazione effettuata con successo
    </div>
    <?php unset($_SESSION['registrazione_successo']); ?>
  <?php endif; ?>
  <ul class="nav nav-tabs mb-3" id="authTabs">
    <li class="nav-item">
      <a class="nav-link active" id="tab-login" onclick="toggleForm('login')">Login</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" id="tab-register" onclick="toggleForm('register')">Registrazione</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" id="tab-admin" onclick="toggleForm('admin')">Admin</a>
    </li>
  </ul>

  <!-- Login -->
  <form id="login-form" method="POST" class="mb-4">
    <div class="mb-3">
      <label for="login_email" class="form-label">Email</label>
      <input type="email" class="form-control" id="login_email" name="login_email" required>
    </div>
    <div class="mb-3">
      <label for="login_password" class="form-label">Password</label>
      <div class="input-group">
        <input type="password" class="form-control" id="login_password" name="login_password" required>
        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('login_password')">Mostra</button>
      </div>
    </div>
    <button type="submit" name="login" class="btn btn-primary w-100">Accedi</button>
    <?php if (isset($login_error)): ?>
      <div class="text-danger fw-semibold text-center mt-2">
        <?= $login_error ?>
      </div>
    <?php endif; ?>
  </form>

  <!-- Registrazione -->
  <form id="register-form" method="POST" class="d-none">
    <div class="mb-3">
      <label class="form-label">Username</label>
      <input type="text" name="register_username" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="register_email" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <div class="input-group">
        <input type="password" name="register_password" id="register_password" class="form-control" required>
        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('register_password')">Mostra</button>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Nome</label>
      <input type="text" name="register_nome" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Cognome</label>
      <input type="text" name="register_cognome" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Anno di nascita</label>
      <input type="number" name="register_anno" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Luogo di nascita</label>
      <input type="text" name="register_luogo" class="form-control" required>
    </div>
    <button type="submit" name="register" class="btn btn-success w-100">Registrati</button>
  </form>

  <!-- Login Admin -->
  <form id="admin-form" method="POST" class="d-none">
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="admin_email" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <div class="input-group">
        <input type="password" name="admin_password" id="admin_password" class="form-control" required>
        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('admin_password')">Mostra</button>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Codice Sicurezza</label>
      <input type="number" name="admin_codice" class="form-control" required>
    </div>
    <button type="submit" name="admin_login" class="btn btn-danger w-100">Accedi come Admin</button>
    <?php if (isset($admin_error)): ?>
      <div class="text-danger fw-semibold text-center mt-2">
        <?= $admin_error ?>
      </div>
    <?php endif; ?>
</form>
</div>

<script>
  function toggleForm(tab) {
    document.getElementById('login-form').classList.toggle('d-none', tab !== 'login');
    document.getElementById('register-form').classList.toggle('d-none', tab !== 'register');
    document.getElementById('admin-form').classList.toggle('d-none', tab !== 'admin');
    document.getElementById('tab-login').classList.toggle('active', tab === 'login');
    document.getElementById('tab-register').classList.toggle('active', tab === 'register');
    document.getElementById('tab-admin').classList.toggle('active', tab === 'admin');
  }

  function togglePassword(id) {
    const field = document.getElementById(id);
    const button = field.nextElementSibling;
    if (field.type === "password") {
      field.type = "text";
      button.textContent = "Nascondi";
    } else {
      field.type = "password";
      button.textContent = "Mostra";
    }
  }
</script>
</body>
</html>