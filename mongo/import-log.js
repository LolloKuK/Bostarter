const fs = require('fs');
const readline = require('readline');
const { MongoClient } = require('mongodb');

const MONGO_URI = 'mongodb://localhost:27017';
// Percorso del file JSONL con i log da importare
const FILE_PATH = '/Applications/XAMPP/xamppfiles/htdocs/bostarter/mongo/log/log.jsonl';

async function importaLog() {
  // Controlla che il file esista prima di procedere
  if (!fs.existsSync(FILE_PATH)) {
    console.log("Nessun file di log trovato.");
    return;
  }

  const client = new MongoClient(MONGO_URI);
  await client.connect();

  const db = client.db('Bostarter');
  const collection = db.collection('Log');

  // Legge il file riga per riga (ogni riga è un oggetto JSON)
  const rl = readline.createInterface({
    input: fs.createReadStream(FILE_PATH),
    crlfDelay: Infinity
  });

  const logs = [];

  for await (const line of rl) {
    if (line.trim()) {
      try {
        const parsed = JSON.parse(line);
        // Converte il timestamp in oggetto Date, utile per le query
        parsed.timestamp = new Date(parsed.timestamp);
        logs.push(parsed);
      } catch (err) {
        // Log non valido, lo ignoriamo
        console.warn("Log corrotto ignorato:", line);
      }
    }
  }

  // Se ci sono log validi, li inserisce nella collection e cancella il file
  if (logs.length > 0) {
    const result = await collection.insertMany(logs);
    console.log(`${result.insertedCount} log importati in MongoDB.`);
    fs.unlinkSync(FILE_PATH); // elimina il file dopo l'import
  } else {
    console.log("Nessun log valido da importare.");
  }

  await client.close();
}

importaLog();