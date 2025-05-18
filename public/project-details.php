<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dettaglio Progetto - BOSTARTER</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">

  <!-- Navbar -->
  <nav class="bg-white shadow p-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold text-blue-600"><a href="index.php">BOSTARTER</a></h1>
    <a href="index.php" class="text-blue-500 hover:underline">← Torna alla home</a>
  </nav>

  <!-- Titolo Progetto -->
  <header class="max-w-4xl mx-auto px-4 py-8">
    <h2 class="text-3xl font-bold mb-2">Smart Home Controller</h2>
    <p class="text-gray-600">Creato da: <span class="font-semibold">tech_master92</span></p>
    <p class="text-gray-500 text-sm">Stato: <span class="text-green-600">Aperto</span> • Budget: €5000 • Scadenza: 15/05/2025</p>
  </header>

  <!-- Descrizione + Immagine -->
  <section class="max-w-4xl mx-auto px-4 grid md:grid-cols-2 gap-8 mb-8">
    <img src="https://via.placeholder.com/400x300" alt="Immagine progetto" class="rounded shadow">
    <div>
      <h3 class="text-xl font-semibold mb-2">Descrizione</h3>
      <p class="text-gray-700">
        Questo progetto mira a creare un dispositivo smart per il controllo completo dell'ambiente domestico: luci, temperatura, sicurezza, e molto altro.
      </p>
    </div>
  </section>

  <!-- Sezione Rewards -->
  <section class="max-w-4xl mx-auto px-4 mb-12">
    <h3 class="text-xl font-semibold mb-4">Rewards disponibili</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Reward -->
      <div class="bg-white p-4 shadow rounded">
        <img src="https://via.placeholder.com/300x200" alt="Reward 1" class="rounded mb-2">
        <p class="font-medium">T-shirt ufficiale del progetto</p>
        <p class="text-sm text-gray-500">Codice: REW123</p>
      </div>
      <!-- Altro reward... -->
    </div>
  </section>

  <!-- Sezione Finanziamento -->
  <section class="max-w-4xl mx-auto px-4 mb-12">
    <h3 class="text-xl font-semibold mb-4">Finanzia questo progetto</h3>
    <form class="bg-white p-6 rounded shadow space-y-4">
      <input type="number" placeholder="Importo in €" class="w-full border border-gray-300 rounded px-4 py-2" required />
      <select class="w-full border border-gray-300 rounded px-4 py-2">
        <option>Seleziona una reward</option>
        <option>T-shirt ufficiale</option>
        <!-- Altre reward -->
      </select>
      <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Finanzia</button>
    </form>
  </section>

  <!-- Sezione Commenti -->
  <section class="max-w-4xl mx-auto px-4 mb-12">
    <h3 class="text-xl font-semibold mb-4">Commenti</h3>
    <div class="space-y-6">
      <!-- Commento -->
      <div class="bg-white p-4 rounded shadow">
        <p class="font-semibold">utente97 <span class="text-sm text-gray-500">| 14/04/2025</span></p>
        <p class="text-gray-700">Questo progetto mi sembra molto interessante! È prevista l'integrazione con Alexa?</p>
        <!-- Risposta -->
        <div class="bg-gray-100 p-3 mt-2 rounded text-sm">
          <p class="text-gray-600"><strong>Risposta del creatore:</strong> Sì! Sarà una delle prime integrazioni disponibili.</p>
        </div>
      </div>
      <!-- Altro commento... -->
    </div>
    <!-- Form nuovo commento -->
    <form class="mt-6 bg-white p-4 rounded shadow space-y-3">
      <textarea placeholder="Scrivi un commento..." class="w-full border border-gray-300 rounded px-4 py-2" rows="3"></textarea>
      <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Invia commento</button>
    </form>
  </section>

  <!-- Sezione Candidature (solo per progetti software) -->
  <section class="max-w-4xl mx-auto px-4 mb-20">
    <h3 class="text-xl font-semibold mb-4">Candidati per uno dei profili richiesti</h3>
    <form class="bg-white p-6 rounded shadow space-y-4">
      <select class="w-full border border-gray-300 rounded px-4 py-2">
        <option>Seleziona un profilo (es. Esperto AI)</option>
        <option>Esperto AI</option>
        <option>Full Stack Developer</option>
        <!-- altri profili -->
      </select>
      <button class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Invia candidatura</button>
    </form>
  </section>

</body>
</html>