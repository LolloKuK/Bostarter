// test-mongo.js

const { MongoClient } = require('mongodb');

const uri = "mongodb://localhost:27017"; // connessione locale
const client = new MongoClient(uri);

async function testConnection() {
    try {
        await client.connect();
        console.log("✅ Connessione a MongoDB riuscita!");

        const dbList = await client.db().admin().listDatabases();
        console.log("📂 Database disponibili:");
        dbList.databases.forEach(db => console.log(" -", db.name));
    } catch (err) {
        console.error("❌ Errore di connessione a MongoDB:", err);
    } finally {
        await client.close();
    }
}

testConnection();