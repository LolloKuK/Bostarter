<?php
function scriviLogLocale($azione, $email, $entita, $dettagli = []) {
    $log = [
        'azione' => $azione,
        'emailUtente' => $email,
        'entità' => $entita,
        'dettagli' => $dettagli,
        'timestamp' => date('c')
    ];

    // Definisce il percorso della cartella e del file di log
    $pathDir = '/Applications/XAMPP/xamppfiles/htdocs/bostarter/mongo/log';
    // Assicurati che il percorso sia corretto per il tuo ambiente
    $pathLog = $pathDir . '/log.jsonl';

    // Verifica se la cartella esiste, altrimenti la crea
    if (!is_dir($pathDir)) {
        mkdir($pathDir, 0755, true);
    }

    $linea = json_encode($log, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    file_put_contents($pathLog, $linea, FILE_APPEND | LOCK_EX);
}