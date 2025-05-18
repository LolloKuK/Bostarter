<!DOCTYPE html>
<html lang="it">
<head>
  <!-- Impostazioni base del documento HTML -->
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BOSTARTER - Home</title>

  <!-- Includiamo TailwindCSS da CDN per gli stili rapidi -->
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">

  <!-- NAVBAR -->
  <nav class="bg-white shadow p-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold text-blue-600"><a href="index.php">BOSTARTER</a></h1>
    <div class="space-x-4">
      <a href="authentication.php" class="text-blue-500 hover:underline">Login</a>
      <a href="dashboard.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Dashboard</a>
    </div>
  </nav>

  <!-- HERO - messaggio iniziale di benvenuto -->
  <section class="text-center py-8">
    <h2 class="text-3xl font-semibold mb-2">Scopri progetti innovativi da finanziare</h2>
    <p class="text-gray-600">Unisciti alla community di sviluppatori e creatori hardware/software</p>
  </section>

  <!-- LISTA DEI PROGETTI -->
  <main class="px-8 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 mb-12">
    <!-- CARD di un progetto -->
    <div class="bg-white rounded-lg shadow p-4">
      <!-- Immagine placeholder -->
      <img src="https://via.placeholder.com/300x200" alt="Progetto 1" class="rounded mb-4">
      
      <!-- Titolo del progetto -->
      <h3 class="text-xl font-semibold">Bombardilo Cocodrilo</h3>
      
      <!-- Breve descrizione -->
      <p class="text-gray-600 text-sm mt-1 mb-3">
        Un dispositivo per controllare ogni aspetto della tua casa con un click.
      </p>
      
      <!-- Informazioni di budget e data -->
      <div class="text-sm text-gray-500 mb-2">
        Budget: €5.000 • Scadenza: 15/05/2025
      </div>

      <!-- Link alla pagina di dettaglio del progetto -->
      <a href="project_details.php" class="text-blue-500 hover:underline">Vedi dettagli →</a>
    </div>

    <!-- Puoi copiare/incollare altri blocchi di <div> come questo per mostrare più progetti -->
  </main>

</body>
</html>