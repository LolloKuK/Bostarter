DROP DATABASE IF EXISTS Bostarter;
CREATE DATABASE IF NOT EXISTS Bostarter;
USE Bostarter;

/*-----------------------------------------------------------------------------------------------------*/
/*CREAZIONE TABELLE*/

CREATE TABLE Utente(
    Username VARCHAR(20),
	Email VARCHAR(50) PRIMARY KEY,
    Password VARCHAR(20),
    Nome VARCHAR(20),
    Cognome VARCHAR(20),
    LuogoNascita VARCHAR(20),
    AnnoNascita INT
    );

CREATE TABLE Competenza(
    Nome VARCHAR(20) PRIMARY KEY
    );

CREATE TABLE SkillUtente (
    EmailUtente VARCHAR(50),
    Nome VARCHAR(20),
    Livello INT CHECK (Livello BETWEEN 0 AND 5),
    PRIMARY KEY (EmailUtente, Nome),
    FOREIGN KEY (EmailUtente) REFERENCES Utente(Email),
    FOREIGN KEY (Nome) REFERENCES Competenza(Nome)
);

CREATE TABLE Amministratore(
	EmailUtenteAmministratore VARCHAR(50) PRIMARY KEY,
    CodiceSicurezza INT,
    foreign key (EmailUtenteAmministratore) references Utente(Email)
    );

CREATE TABLE Creatore(
	EmailUtenteCreatore VARCHAR(50) PRIMARY KEY,
    NrProgetti INT,
    Affidabilità INT DEFAULT 0,
	foreign key (EmailUtenteCreatore) references Utente(Email)
    );

CREATE TABLE Progetto(
	Nome VARCHAR (20) PRIMARY KEY,
    DataInserimento DATE,
    Budget INT,
    Descrizione VARCHAR(255),
    Stato ENUM('aperto', 'chiuso'),
    DataLimite DATE,
    EmailUtente VARCHAR(50),
    foreign key (EmailUtente) references Utente(Email)
    );

CREATE TABLE Reward(
	Id INT auto_increment PRIMARY KEY,
    Descrizione VARCHAR(50),
    Foto VARCHAR(50),
    NomeProgetto VARCHAR(20),
    foreign key (NomeProgetto) references Progetto(Nome)
    );

CREATE TABLE Foto(
    Id INT auto_increment PRIMARY KEY,
    Percorso VARCHAR(255),
    NomeProgetto VARCHAR(20),
    foreign key (NomeProgetto) references Progetto(Nome)
    );

CREATE TABLE ProgSoftware(
	NomeProgettoSoftware VARCHAR(20) PRIMARY KEY,
    foreign key (NomeProgettoSoftware) references Progetto(Nome)
    );

CREATE TABLE ProgHardware(
	NomeProgettoHardware VARCHAR(20) PRIMARY KEY,
	foreign key (NomeProgettoHardware) references Progetto(Nome)
    );

CREATE TABLE Componente(
	Nome varchar(20) primary key,
    Prezzo int,
    Descrizione varchar(50),
    Quantità int
    );

CREATE TABLE Composizione(
	NomeComponente varchar(20),
    NomeProgetto varchar(20),
    PRIMARY KEY (NomeComponente, NomeProgetto),
    foreign key (NomeComponente) references Componente(Nome),
	foreign key (NomeProgetto) references Progetto(Nome)
    );

CREATE TABLE Profilo (
    Id INT AUTO_INCREMENT PRIMARY KEY,
	Nome VARCHAR(20),
    Competenza VARCHAR(20),
    Livello INT CHECK (Livello BETWEEN 0 AND 5),
    NomeProgetto VARCHAR(20),
    foreign key (NomeProgetto) references Progetto(Nome)
    );

CREATE TABLE Finanziamento (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Importo INT NOT NULL,
    Data DATE NOT NULL,
    EmailUtente VARCHAR(50) NOT NULL,
    NomeProgetto VARCHAR(20) NOT NULL,
    CodiceReward INT DEFAULT NULL,
    FOREIGN KEY (EmailUtente) REFERENCES Utente(Email),
    FOREIGN KEY (NomeProgetto) REFERENCES Progetto(Nome),
    FOREIGN KEY (CodiceReward) REFERENCES Reward(Id),
    UNIQUE (EmailUtente, NomeProgetto, Data)
    );

CREATE TABLE Commento(
	Id INT auto_increment PRIMARY KEY,
    Data DATE,
    Testo VARCHAR(255),
    EmailUtente VARCHAR(50),
    NomeProgetto VARCHAR(20),
    foreign key (EmailUtente) references Utente(Email),
    foreign key (NomeProgetto) references Progetto(Nome)
    );
    
CREATE TABLE Risposta(
	Id INT auto_increment PRIMARY KEY,
    Testo VARCHAR(255),
    IdCommento INT,
    EmailUtente VARCHAR(50),
    FOREIGN KEY (IdCommento) REFERENCES Commento(Id),
    FOREIGN KEY (EmailUtente) REFERENCES Utente(Email)
    );

CREATE TABLE Candidatura (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    EmailUtente VARCHAR(50),
    IdProfilo INT,
    Stato ENUM('in attesa', 'accettata', 'rifiutata') DEFAULT 'in attesa',
    FOREIGN KEY (EmailUtente) REFERENCES Utente(Email),
    FOREIGN KEY (IdProfilo) REFERENCES Profilo(Id)
    );

/*-----------------------------------------------------------------------------------------------------*/

/*-----------------------------------------------------------------------------------------------------*/
/*OPERAZIONI SUI DATI*/

-- login.php

-- registrazione utente
DELIMITER //
CREATE PROCEDURE sp_registra_utente (
	IN p_username VARCHAR(20),
    IN p_email VARCHAR(50),
    IN p_password VARCHAR(20),
    IN p_nome VARCHAR(20),
    IN p_cognome VARCHAR(20),
    IN p_annoNascita INT,
    IN p_luogoNascita VARCHAR(20)
)
BEGIN
	INSERT INTO Utente (Username, Email, Password, Nome, Cognome, AnnoNascita, LuogoNascita)
    VALUES (p_username, p_email, p_password, p_nome, p_cognome, p_annoNascita, p_luogoNascita);
END //
DELIMITER ;

-- autenticazione utente
DELIMITER //
CREATE PROCEDURE sp_autentica_utente (
	IN p_email VARCHAR(50),
    IN p_password VARCHAR(20)
)
BEGIN
	SELECT Email, Nome, Cognome, Username
    FROM Utente
    WHERE Email = p_email AND LOWER(Password) = LOWER(p_password);
END //
DELIMITER ;

-- autenticazione amministratore
DELIMITER //
CREATE PROCEDURE sp_autentica_amministratore (
    IN p_email VARCHAR(50),
    IN p_password VARCHAR(20),
    IN p_codice_sicurezza INT
)
BEGIN
    SELECT u.Email, u.Nome, u.Cognome
    FROM Utente u
    JOIN Amministratore a ON u.Email = a.EmailUtenteAmministratore
    WHERE u.Email = p_email
      AND u.Password = p_password
      AND a.CodiceSicurezza = p_codice_sicurezza;
END //
DELIMITER ;

/*-----------------------------------------------------------------------------------------------------*/
-- home.php

-- visualizzazione progetto disponibili (aperti)
DELIMITER //
CREATE PROCEDURE sp_visualizza_progetti_aperti()
BEGIN
	SELECT * FROM Progetto
    WHERE Stato = 'aperto';
END //
DELIMITER ;

-- Viasualizzazione foto copertina progetto
DELIMITER //
CREATE PROCEDURE sp_foto_copertina_progetto(IN p_nome_progetto VARCHAR(100))
BEGIN
    SELECT Percorso
    FROM Foto
    WHERE NomeProgetto = p_nome_progetto
    ORDER BY Id ASC
    LIMIT 1;
END //
DELIMITER ;

/*-----------------------------------------------------------------------------------------------------*/
-- insert-skill.php

-- Visualizza skill utente
DELIMITER //
CREATE PROCEDURE sp_visualizza_skill_utente(IN p_email VARCHAR(50))
BEGIN
    SELECT Nome, Livello
    FROM SkillUtente
    WHERE EmailUtente = p_email;
END //
DELIMITER ;

-- Inserisci skill nel curriculum
DELIMITER //
CREATE PROCEDURE sp_aggiungi_o_modifica_skill(
    IN p_email VARCHAR(50),
    IN p_nome_competenza VARCHAR(20),
    IN p_livello INT
)
BEGIN
    -- Se esiste già la skill, la aggiorno
    IF EXISTS (
        SELECT 1 FROM SkillUtente 
        WHERE EmailUtente = p_email AND Nome = p_nome_competenza
    ) THEN
        UPDATE SkillUtente
        SET Livello = p_livello
        WHERE EmailUtente = p_email AND Nome = p_nome_competenza;
    ELSE
        -- Altrimenti la inserisco
        INSERT INTO SkillUtente (EmailUtente, Nome, Livello)
        VALUES (p_email, p_nome_competenza, p_livello);
    END IF;
END //
DELIMITER ;

/*-----------------------------------------------------------------------------------------------------*/
-- admin.php

-- Visualizza competenze
DELIMITER //
CREATE PROCEDURE sp_visualizza_competenze()
BEGIN
    SELECT Nome FROM Competenza ORDER BY Nome ASC;
END //
DELIMITER ;

-- Inserimento nuova competenza
DELIMITER //
CREATE PROCEDURE sp_aggiungi_competenza(IN p_nome VARCHAR(20))
BEGIN
    INSERT INTO Competenza (Nome) VALUES (p_nome);
END //
DELIMITER ;

-- Eliminazione competenza
DELIMITER //
CREATE PROCEDURE sp_elimina_competenza(IN p_nome VARCHAR(20))
BEGIN
    DELETE FROM Competenza WHERE Nome = p_nome;
END //
DELIMITER ;

/*-----------------------------------------------------------------------------------------------------*/
-- new-project.php

-- Controllo se esiste già un progetto con lo stesso nome
DELIMITER //
CREATE PROCEDURE sp_conta_progetto_nome(IN p_nome VARCHAR(50), OUT p_count INT)
BEGIN
    SELECT COUNT(*) INTO p_count
    FROM Progetto
    WHERE Nome = p_nome;
END //
DELIMITER ;

-- Inserimento progetto hardware
DELIMITER //
CREATE PROCEDURE sp_inserisci_progetto_hardware (
    IN p_nome VARCHAR(20),
    IN p_data_inserimento DATE,
    IN p_budget INT,
    IN p_descrizione VARCHAR(255),
    IN p_data_limite DATE,
    IN p_email_creatore VARCHAR(50)
)
BEGIN
    -- Se l'utente non è ancora un creatore, lo inseriamo
    IF NOT EXISTS (
        SELECT 1 FROM Creatore WHERE EmailUtenteCreatore = p_email_creatore
    ) THEN
        INSERT INTO Creatore (EmailUtenteCreatore, NrProgetti, Affidabilità)
        VALUES (p_email_creatore, 0, 0);
    END IF;

    -- Inserimento progetto
    INSERT INTO Progetto (Nome, DataInserimento, Budget, Descrizione, Stato, DataLimite, EmailUtente)
    VALUES (p_nome, p_data_inserimento, p_budget, p_descrizione, 'aperto', p_data_limite, p_email_creatore);

    -- Inserimento relazione hardware
    INSERT INTO ProgHardware (NomeProgettoHardware)
    VALUES (p_nome);
END //
DELIMITER ;

-- Inserimento progetto software
DELIMITER //
CREATE PROCEDURE sp_inserisci_progetto_software (
    IN p_nome VARCHAR(20),
    IN p_data_inserimento DATE,
    IN p_budget INT,
    IN p_descrizione VARCHAR(255),
    IN p_data_limite DATE,
    IN p_email_creatore VARCHAR(50)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM Creatore WHERE EmailUtenteCreatore = p_email_creatore
    ) THEN
        INSERT INTO Creatore (EmailUtenteCreatore, NrProgetti, Affidabilità)
        VALUES (p_email_creatore, 0, 0);
    END IF;

    INSERT INTO Progetto (Nome, DataInserimento, Budget, Descrizione, Stato, DataLimite, EmailUtente)
    VALUES (p_nome, p_data_inserimento, p_budget, p_descrizione, 'aperto', p_data_limite, p_email_creatore);

    INSERT INTO ProgSoftware (NomeProgettoSoftware)
    VALUES (p_nome);
END //
DELIMITER ;

-- Inserimento componente
DELIMITER //
CREATE PROCEDURE sp_inserisci_componente (
    IN p_nome VARCHAR(20),
    IN p_prezzo INT,
    IN p_descrizione VARCHAR(50),
    IN p_quantita INT,
    IN p_nome_progetto VARCHAR(20)
)
BEGIN
    INSERT INTO Componente (Nome, Prezzo, Descrizione, Quantità)
    VALUES (p_nome, p_prezzo, p_descrizione, p_quantita);

    INSERT INTO Composizione (NomeComponente, NomeProgetto)
    VALUES (p_nome, p_nome_progetto);
END //
DELIMITER ;

-- Inserimento profilo richiesto
DELIMITER //
CREATE PROCEDURE sp_inserisci_profilo (
    IN p_nome VARCHAR(20),
    IN p_competenza VARCHAR(20),
    IN p_livello INT,
    IN p_nome_progetto VARCHAR(20)
)
BEGIN
    INSERT INTO Profilo(Nome, Competenza, Livello, NomeProgetto)
    VALUES (p_nome, p_competenza, p_livello, p_nome_progetto);
END //
DELIMITER ;

-- Inserimento foto per un progetto
DELIMITER //
CREATE PROCEDURE sp_inserisci_foto (
    IN p_percorso VARCHAR(255),
    IN p_nome_progetto VARCHAR(20)
)
BEGIN
    INSERT INTO Foto (Percorso, NomeProgetto)
    VALUES (p_percorso, p_nome_progetto);
END //
DELIMITER ;

-- inserimento reward per un progetto
DELIMITER //
CREATE PROCEDURE sp_inserisci_reward (
    IN p_descrizione VARCHAR(50),
    IN p_foto VARCHAR(50),
    IN p_nome_progetto VARCHAR(20)
)
BEGIN
    INSERT INTO Reward(Descrizione, Foto, NomeProgetto)
    VALUES (p_descrizione, p_foto, p_nome_progetto);
END //
DELIMITER ;

/*-----------------------------------------------------------------------------------------------------*/
-- project-info.php

-- Visualizza dettagli progetto
DELIMITER //
CREATE PROCEDURE sp_dettagli_progetto (
    IN p_nome_progetto VARCHAR(20)
)
BEGIN
    SELECT 
        p.Nome,
        p.DataInserimento,
        p.Budget,
        p.Descrizione,
        p.Stato,
        p.DataLimite,
        u.Username AS NomeCreatore,
        u.Email AS EmailUtente
    FROM Progetto p
    JOIN Utente u ON p.EmailUtente = u.Email
    WHERE p.Nome = p_nome_progetto;
END //
DELIMITER ;

-- Verifica tipo progetto (hardware o software)
DELIMITER //
CREATE PROCEDURE sp_tipo_progetto (
    IN p_nome_progetto VARCHAR(20)
)
BEGIN
    IF EXISTS (SELECT 1 FROM ProgSoftware WHERE NomeProgettoSoftware = p_nome_progetto) THEN
        SELECT 'software' AS Tipo;
    ELSEIF EXISTS (SELECT 1 FROM ProgHardware WHERE NomeProgettoHardware = p_nome_progetto) THEN
        SELECT 'hardware' AS Tipo;
    ELSE
        SELECT 'generico' AS Tipo;
    END IF;
END //
DELIMITER ;

-- Componenti richiesti (solo se hardware)
DELIMITER //
CREATE PROCEDURE sp_componenti_progetto (
    IN p_nome_progetto VARCHAR(20)
)
BEGIN
    SELECT c.Nome, c.Prezzo, c.Descrizione, c.Quantità
    FROM Componente c
    JOIN Composizione comp ON c.Nome = comp.NomeComponente
    WHERE comp.NomeProgetto = p_nome_progetto;
END //
DELIMITER ;

-- Profili richiesti (solo se software)
DELIMITER //
CREATE PROCEDURE sp_profili_progetto (
    IN p_nome_progetto VARCHAR(20)
)
BEGIN
    SELECT Id, Nome, Competenza, Livello
    FROM Profilo
    WHERE NomeProgetto = p_nome_progetto;
END //
DELIMITER ;

-- Reward associati al progetto
DELIMITER //
CREATE PROCEDURE sp_reward_progetto (
    IN p_nome_progetto VARCHAR(20)
)
BEGIN
    SELECT Id, Descrizione, Foto
    FROM Reward
    WHERE NomeProgetto = p_nome_progetto;
END //
DELIMITER ;

-- Foto associate al progetto
DELIMITER //
CREATE PROCEDURE sp_foto_progetto (
    IN p_nome_progetto VARCHAR(20)
)
BEGIN
    SELECT Percorso FROM Foto WHERE NomeProgetto = p_nome_progetto;
END //
DELIMITER ;

-- Finanziamento di un progetto e scelta reward
DELIMITER //
CREATE PROCEDURE sp_finanzia_progetto (
    IN p_importo INT,
    IN p_email_utente VARCHAR(50),
    IN p_nome_progetto VARCHAR(20),
    IN p_codice_reward INT
)
BEGIN
    DECLARE stato_progetto VARCHAR(20);
    DECLARE reward_esiste INT;

    -- Verifica che il progetto esista e sia aperto
    SELECT Stato INTO stato_progetto
    FROM Progetto
    WHERE Nome = p_nome_progetto;

    IF stato_progetto IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Progetto inesistente.';
    ELSEIF stato_progetto <> 'aperto' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Il progetto non è aperto.';
    END IF;

    -- Se è stato fornito un reward (> 0), controlla che esista ed è legato a quel progetto
    IF p_codice_reward IS NOT NULL THEN
        SELECT COUNT(*) INTO reward_esiste
        FROM Reward
        WHERE Id = p_codice_reward AND NomeProgetto = p_nome_progetto;

        IF reward_esiste = 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Reward non valida per questo progetto.';
        END IF;
    END IF;

    -- Inserimento finanziamento (CodiceReward può essere NULL)
    INSERT INTO Finanziamento (Importo, Data, EmailUtente, NomeProgetto, CodiceReward)
    VALUES (p_importo, CURDATE(), p_email_utente, p_nome_progetto, p_codice_reward);
END //
DELIMITER ;

-- Verifica se esiste un commento per il progetto
DELIMITER //
CREATE PROCEDURE sp_verifica_risposta_commento(IN p_id INT, OUT p_esiste INT)
BEGIN
    SELECT COUNT(*) INTO p_esiste
    FROM Risposta
    WHERE IdCommento = p_id;
END //
DELIMITER ;

-- Inserimento commento relativo al progetto
DELIMITER //
CREATE PROCEDURE sp_commenta_progetto (
	IN p_testo VARCHAR(255),
    IN p_email_utente VARCHAR(50),
    IN p_nome_progetto VARCHAR(20)
)
BEGIN
	INSERT INTO Commento(Data, Testo, EmailUtente, NomeProgetto)
    VALUES (CURDATE(), p_testo, p_email_utente, p_nome_progetto);
END //
DELIMITER ;

-- Inserimento risposta ad un commento
DELIMITER //
CREATE PROCEDURE sp_rispondi_a_commento (
    IN p_id_commento INT,
    IN p_email_creatore VARCHAR(50),
    IN p_testo VARCHAR(255)
)
BEGIN
    INSERT INTO Risposta(IdCommento, EmailUtente, Testo)
    VALUES (p_id_commento, p_email_creatore, p_testo);
END //
DELIMITER ;

-- Recupera commenti e risposte per un progetto
DELIMITER //
CREATE PROCEDURE sp_commenti_con_risposte (
    IN p_nome_progetto VARCHAR(20)
)
BEGIN
    SELECT 
        c.Id AS IdCommento,
        c.Testo AS TestoCommento,
        c.Data AS DataCommento,
        c.EmailUtente AS EmailCommentatore,
        u.Username AS UsernameCommentatore,
        r.Id AS IdRisposta,
        r.Testo AS TestoRisposta,
        r.EmailUtente AS EmailRispondente,
        ur.Username AS UsernameRispondente
    FROM Commento c
    JOIN Utente u ON c.EmailUtente = u.Email
    LEFT JOIN Risposta r ON r.IdCommento = c.Id
    LEFT JOIN Utente ur ON r.EmailUtente = ur.Email
    WHERE c.NomeProgetto = p_nome_progetto
    ORDER BY c.Data ASC, r.Id ASC;
END //
DELIMITER ;

-- Verifica se l'utente ha una skill con un certo livello
DELIMITER //
CREATE PROCEDURE sp_utente_ha_skill(IN p_email VARCHAR(50), IN p_nome_skill VARCHAR(20), IN p_livello INT, OUT p_valido INT)
BEGIN
    SELECT COUNT(*) INTO p_valido
    FROM SkillUtente
    WHERE EmailUtente = p_email AND Nome = p_nome_skill AND Livello >= p_livello;
END //
DELIMITER ;

-- Verifica se l'utente ha una candidatura per un profilo
DELIMITER //
CREATE PROCEDURE sp_verifica_candidatura(IN p_email VARCHAR(50), IN p_id_profilo INT, OUT p_gia_inviata INT)
BEGIN
    SELECT COUNT(*) INTO p_gia_inviata
    FROM Candidatura
    WHERE EmailUtente = p_email AND IdProfilo = p_id_profilo;
END //
DELIMITER ;

-- Inserimento candidatura per un profilo
DELIMITER //
CREATE PROCEDURE sp_candidati_profilo (
    IN p_email VARCHAR(50),
    IN p_id_profilo INT
)
BEGIN
    -- Verifica se esiste già una candidatura dello stesso utente per lo stesso profilo
    IF NOT EXISTS (
        SELECT 1 FROM Candidatura 
        WHERE EmailUtente = p_email AND IdProfilo = p_id_profilo
    ) THEN
        INSERT INTO Candidatura (EmailUtente, IdProfilo)
        VALUES (p_email, p_id_profilo);
    END IF;
END //
DELIMITER ;

-- Aggiorna lo stato di una candidatura
DELIMITER //
CREATE PROCEDURE sp_aggiorna_stato_candidatura (
    IN p_id INT,
    IN p_nuovo_stato ENUM('in attesa', 'accettata', 'rifiutata')
)
BEGIN
    UPDATE Candidatura
    SET Stato = p_nuovo_stato
    WHERE Id = p_id;
END //
DELIMITER ;

DELIMITER //

-- Visualizza candidature per un progetto software
DELIMITER //
CREATE PROCEDURE sp_visualizza_candidature_progetto (
    IN p_email_creatore VARCHAR(50),
    IN p_nome_progetto VARCHAR(50)
)
BEGIN
    SELECT 
        c.Id AS IdCandidatura,
        c.EmailUtente,
        u.Username,
        pr.Nome AS NomeProfilo,
        pr.Competenza,
        pr.Livello,
        c.Stato
    FROM Candidatura c
    JOIN Profilo pr ON c.IdProfilo = pr.Id
    JOIN Progetto p ON pr.NomeProgetto = p.Nome
    JOIN Utente u ON u.Email = c.EmailUtente
    WHERE p.EmailUtente = p_email_creatore
      AND p.Nome = p_nome_progetto;
END //
DELIMITER ;

-- Visualizza candidature per un utente specifico
DELIMITER //
CREATE PROCEDURE sp_visualizza_candidature_utente (
    IN p_email_utente VARCHAR(50),
    IN p_nome_progetto VARCHAR(50)
)
BEGIN
    SELECT 
        c.Id AS IdCandidatura,
        c.EmailUtente,
        u.Username,
        pr.Nome AS NomeProfilo,
        pr.Competenza,
        pr.Livello,
        c.Stato
    FROM Candidatura c
    JOIN Profilo pr ON c.IdProfilo = pr.Id
    JOIN Progetto p ON pr.NomeProgetto = p.Nome
    JOIN Utente u ON u.Email = c.EmailUtente
    WHERE c.EmailUtente = p_email_utente
      AND p.Nome = p_nome_progetto;
END //
DELIMITER ;

/*-----------------------------------------------------------------------------------------------------*/
-- STATISTICHE

-- Classifica TOP 3 creatori per affidabilità
CREATE OR REPLACE VIEW v_top_creatori AS
SELECT u.Username, c.Affidabilità
FROM Creatore c
JOIN Utente u ON u.Email = c.EmailUtenteCreatore
ORDER BY c.Affidabilità DESC
LIMIT 3;

-- Progetti APERTI più vicini al completamento
CREATE OR REPLACE VIEW v_progetti_vicini_completamento AS
SELECT 
    p.Nome,
    p.Budget,
    COALESCE(SUM(f.Importo), 0) AS TotaleFinanziato,
    ABS(p.Budget - COALESCE(SUM(f.Importo), 0)) AS Differenza
FROM Progetto p
LEFT JOIN Finanziamento f ON p.Nome = f.NomeProgetto
WHERE p.Stato = 'aperto'
GROUP BY p.Nome, p.Budget
ORDER BY Differenza ASC
LIMIT 3;

-- Classifica TOP 3 utenti per totale finanziamenti erogati
CREATE OR REPLACE VIEW v_top_finanziatori AS
SELECT u.Username, SUM(f.Importo) AS TotaleFinanziato
FROM Finanziamento f
JOIN Utente u ON f.EmailUtente = u.Email
GROUP BY u.Username
ORDER BY TotaleFinanziato DESC
LIMIT 3;

/*-----------------------------------------------------------------------------------------------------*/   

/*-----------------------------------------------------------------------------------------------------*/
/*IMPLEMENTAZIONE TRIGGER*/

DELIMITER //
CREATE TRIGGER trg_progetto_insert
AFTER INSERT ON Progetto
FOR EACH ROW
BEGIN
    -- Incrementa NrProgetti
    UPDATE Creatore
    SET NrProgetti = NrProgetti + 1
    WHERE EmailUtenteCreatore = NEW.EmailUtente;

    -- Ricalcola affidabilità
    UPDATE Creatore c
    SET Affidabilità = (
        SELECT
            IF(COUNT(*) = 0, 0,
                (SELECT COUNT(DISTINCT p.Nome)
                 FROM Progetto p
                 WHERE p.EmailUtente = c.EmailUtenteCreatore
                   AND EXISTS (
                       SELECT 1
                       FROM Finanziamento f
                       WHERE f.NomeProgetto = p.Nome
                   )
                ) * 100 / COUNT(*)
            )
        FROM Progetto p2
        WHERE p2.EmailUtente = c.EmailUtenteCreatore
    )
    WHERE c.EmailUtenteCreatore = NEW.EmailUtente;
END //
DELIMITER ;

-- Chiude il progetto se il budget è raggiunto e aggiornare l’affidabilità
DELIMITER //
CREATE TRIGGER trg_aggiorna_affidabilita_e_chiusura
AFTER INSERT ON Finanziamento
FOR EACH ROW
BEGIN
    DECLARE v_email_creatore VARCHAR(50);
    DECLARE v_budget INT;
    DECLARE v_totale INT;

    -- Ottieni creatore e budget del progetto
    SELECT EmailUtente, Budget INTO v_email_creatore, v_budget
    FROM Progetto
    WHERE Nome = NEW.NomeProgetto;

    -- Calcola totale finanziamenti
    SELECT SUM(Importo) INTO v_totale
    FROM Finanziamento
    WHERE NomeProgetto = NEW.NomeProgetto;

    -- Chiudi progetto se il budget è stato raggiunto
    IF v_totale >= v_budget THEN
        UPDATE Progetto
        SET Stato = 'chiuso'
        WHERE Nome = NEW.NomeProgetto;
    END IF;

    -- Ricalcola affidabilità del creatore
    UPDATE Creatore c
    SET Affidabilità = (
        SELECT
            IF(COUNT(*) = 0, 0,
                (SELECT COUNT(DISTINCT p.Nome)
                 FROM Progetto p
                 WHERE p.EmailUtente = c.EmailUtenteCreatore
                   AND EXISTS (
                       SELECT 1
                       FROM Finanziamento f
                       WHERE f.NomeProgetto = p.Nome
                   )
                ) * 100 / COUNT(*)
            )
        FROM Progetto p2
        WHERE p2.EmailUtente = c.EmailUtenteCreatore
    )
    WHERE c.EmailUtenteCreatore = v_email_creatore;
END //
DELIMITER ;

/*-----------------------------------------------------------------------------------------------------*/

/*-----------------------------------------------------------------------------------------------------*/
/*IMPLEMENTAZIONE EVENTO*/

-- cambio stato di un progetto
DELIMITER //
CREATE EVENT IF NOT EXISTS ev_chiudi_progetti_scaduti
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
    UPDATE Progetto
    SET Stato = 'chiuso'
    WHERE DataLimite < CURDATE()
      AND Stato <> 'chiuso';
END //
DELIMITER ;
/*-----------------------------------------------------------------------------------------------------*/

/*-----------------------------------------------------------------------------------------------------*/
/*INSERIMENTO DATI DEFAULT*/

-- AMMINISTRATORI
INSERT INTO Utente(Username, Email, Password, Nome, Cognome, LuogoNascita, AnnoNascita) VALUES("Lollo", "lorenzo@cuoco.it", "Lollo", "Lorenzo", "Cuoco", "Bologna", 2003);
INSERT INTO Amministratore(EmailUtenteAmministratore, CodiceSicurezza) VALUES("lorenzo@cuoco.it", 2785);
INSERT INTO Utente(Username, Email, Password, Nome, Cognome, LuogoNascita, AnnoNascita) VALUES("Fede", "federico@tessari.it", "Fede", "Federico", "Tessari", "Soave", 2003);
INSERT INTO Amministratore(EmailUtenteAmministratore, CodiceSicurezza) VALUES("federico@tessari.it", 6437);
INSERT INTO Utente(Username, Email, Password, Nome, Cognome, LuogoNascita, AnnoNascita) VALUES("Maz", "giacomo@mazzoli.it", "Maz", "Giacomo", "Mazzoli", "Corticella", 2003);
INSERT INTO Amministratore(EmailUtenteAmministratore, CodiceSicurezza) VALUES("giacomo@mazzoli.it", 9632);

-- COMPETENZE
INSERT INTO Competenza (Nome) VALUES 
('Sql'),
('Java'),
('Js'),
('Web Dev.'),
('Maintenance Hw'),
('Hw design'),
('Data analysis'),
('Leadership'),
('Teamwork');

-- UTENTI
CALL sp_registra_utente('MarioR', 'mario@rossi.it', 'pw123', 'Mario', 'Rossi', 1990, 'Roma');
CALL sp_registra_utente('AnnaB', 'anna@bianchi.it', 'pw456', 'Anna', 'Bianchi', 1995, 'Milano');
CALL sp_registra_utente('CarloP', 'carlo@pesce.it', 'pw789', 'Carlo', 'Pesce', 2002, 'Rovigo');
CALL sp_registra_utente('Vane', 'vanessa@franchi.it', 'pw101', 'Vanessa', 'Franchi', 2000, 'MilRiminiano');

-- COMPETENZE UTENTI
CALL sp_aggiungi_o_modifica_skill('mario@rossi.it', 'Java', 4);
CALL sp_aggiungi_o_modifica_skill('anna@bianchi.it', 'Web Dev.', 3);
CALL sp_aggiungi_o_modifica_skill('mario@rossi.it', 'Teamwork', 5);
CALL sp_aggiungi_o_modifica_skill('mario@rossi.it', 'Leadership', 3);
CALL sp_aggiungi_o_modifica_skill('anna@bianchi.it', 'Java', 2);
CALL sp_aggiungi_o_modifica_skill('anna@bianchi.it', 'Sql', 4);
CALL sp_aggiungi_o_modifica_skill('carlo@pesce.it', 'Maintenance Hw', 3);
CALL sp_aggiungi_o_modifica_skill('carlo@pesce.it', 'Hw design', 4);
CALL sp_aggiungi_o_modifica_skill('vanessa@franchi.it', 'Web Dev.', 5);
CALL sp_aggiungi_o_modifica_skill('vanessa@franchi.it', 'Teamwork', 4);

-- PROGETTI SOFTWARE
CALL sp_inserisci_progetto_software('SmartApp', '2025-07-01', 5000, 'Un\'app intelligente.', '2025-07-31', 'mario@rossi.it');
CALL sp_inserisci_profilo('Frontend Dev', 'Web Dev.', 3, 'SmartApp');
CALL sp_inserisci_profilo('Backend Dev', 'Java', 4, 'SmartApp');
CALL sp_inserisci_profilo('Web Designer', 'Web Dev.', 2, 'SmartApp');
CALL sp_inserisci_profilo('Data Analyst', 'Data analysis', 3, 'SmartApp');
CALL sp_inserisci_progetto_software('EcoPlanner', '2025-07-01', 6000, 'App per pianificare la sostenibilità quotidiana.', '2025-08-01', 'vanessa@franchi.it');
CALL sp_inserisci_profilo('Sviluppatore Mobile', 'Java', 4, 'EcoPlanner');
CALL sp_inserisci_profilo('Web Specialist', 'Web Dev.', 5, 'EcoPlanner');

-- PROGETTI HARDWARE
CALL sp_inserisci_progetto_hardware('ArduinoHome', '2025-07-01', 10000, 'Sistema domotico su Arduino.', '2025-07-31', 'anna@bianchi.it');
CALL sp_inserisci_componente('Sensore IR', 15, 'Sensore movimento', 3, 'ArduinoHome');
CALL sp_inserisci_componente('LED Strip', 10, 'Illuminazione RGB', 5, 'ArduinoHome');
CALL sp_inserisci_componente('Scheda Relay', 8, 'Controllo dispositivi AC', 4, 'ArduinoHome');
CALL sp_inserisci_componente('Modulo WiFi ESP8266', 12, 'Connessione wireless', 2, 'ArduinoHome');
CALL sp_inserisci_progetto_hardware('FishBot', '2025-07-01', 8000, 'Un robot acquatico intelligente.', '2025-08-01', 'carlo@pesce.it');
CALL sp_inserisci_componente('Propulsore Acqua', 50, 'Sistema di movimento idrodinamico', 2, 'FishBot');
CALL sp_inserisci_componente('Sensore Temperatura', 15, 'Controllo ambientale', 4, 'FishBot');

-- FOTO PROGETTI
CALL sp_inserisci_foto('images/random2.jpg', 'SmartApp');
CALL sp_inserisci_foto('images/random4.jpg', 'ArduinoHome');
CALL sp_inserisci_foto('images/dadi.jpg', 'ArduinoHome');
CALL sp_inserisci_foto('images/random1.jpg', 'EcoPlanner');
CALL sp_inserisci_foto('images/random.jpg', 'EcoPlanner');
CALL sp_inserisci_foto('images/random5.jpg', 'FishBot');

-- REWARD PROGETTI
CALL sp_inserisci_reward('T-shirt SmartApp', 'images/maglia.png', 'SmartApp');
CALL sp_inserisci_reward('Arduino Kit Base', 'images/random5.jpg', 'ArduinoHome');
CALL sp_inserisci_reward('Guanti Premium', 'images/guanti.png', 'EcoPlanner');
CALL sp_inserisci_reward('FishBot Pro', 'images/fish-bot.jpg', 'FishBot');

-- FINANZIAMENTI PROGETTI
CALL sp_finanzia_progetto(200, 'anna@bianchi.it', 'SmartApp', 1);
CALL sp_finanzia_progetto(300, 'mario@rossi.it', 'ArduinoHome', 2);
CALL sp_finanzia_progetto(350, 'carlo@pesce.it', 'EcoPlanner', 3);
CALL sp_finanzia_progetto(500, 'vanessa@franchi.it', 'FishBot', 4);

-- COMMENTI
CALL sp_commenta_progetto('Progetto interessante!', 'anna@bianchi.it', 'SmartApp');
CALL sp_commenta_progetto('Ottima idea.', 'mario@rossi.it', 'ArduinoHome');
CALL sp_commenta_progetto('Ci lavorerei volentieri!', 'mario@rossi.it', 'SmartApp');
CALL sp_commenta_progetto('Serve una hardware particolare?', 'anna@bianchi.it', 'SmartApp');
CALL sp_commenta_progetto('Davvero innovativo!', 'carlo@pesce.it', 'EcoPlanner');
CALL sp_commenta_progetto('Lo vorrei testare!', 'vanessa@franchi.it', 'FishBot');

-- RISPOSTE AI COMMENTI
CALL sp_rispondi_a_commento(3, 'mario@rossi.it', 'Certo, ti contatto!');
CALL sp_rispondi_a_commento(4, 'anna@bianchi.it', 'Sì, visita il nostro sito');
CALL sp_rispondi_a_commento(5, 'vanessa@franchi.it', 'Scrivimi e te lo mostro in anteprima!');
CALL sp_rispondi_a_commento(6, 'carlo@pesce.it', 'Volentieri, ti invio i file.');

-- CANDIDATURE
CALL sp_candidati_profilo('anna@bianchi.it', 1);
CALL sp_candidati_profilo('mario@rossi.it', 2);
CALL sp_candidati_profilo('anna@bianchi.it', 3);
CALL sp_candidati_profilo('mario@rossi.it', 4);
CALL sp_candidati_profilo('carlo@pesce.it', 5);
CALL sp_candidati_profilo('mario@rossi.it', 6);

-- ACCETTAZIONE CANDIDATURA
CALL sp_aggiorna_stato_candidatura(1, 'accettata');
CALL sp_aggiorna_stato_candidatura(4, 'rifiutata');
CALL sp_aggiorna_stato_candidatura(5, 'accettata');
