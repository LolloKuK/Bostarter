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
    AnnoNascita INT,
    Competenza VARCHAR (10),
    Livello INT
    );

CREATE TABLE Creatore(
	EmailUtenteCreatore VARCHAR(20) PRIMARY KEY,
    NrProgetti INT,
    Affidabilità INT DEFAULT 0,
	foreign key (EmailUtenteCreatore) references Utente(Email)
    );
    
CREATE TABLE Amministratore(
	EmailUtenteAmministratore VARCHAR(20) PRIMARY KEY,
    CodiceSicurezza INT,
    foreign key (EmailUtenteAmministratore) references Utente(Email)
    );

CREATE TABLE Competenza(
    Nome VARCHAR(20) PRIMARY KEY
    );

CREATE TABLE Progetto(
	Nome VARCHAR (20) PRIMARY KEY,
    DataInserimento DATE,
    Budget INt,
    Descrizione VARCHAR(200),
    Stato enum ('aperto', 'chiuso'),
    DataLimite DATE,
    EmailUtente VARCHAR(20),
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
    Percorso VARCHAR(50),
    NomeProgetto VARCHAR(20),
    foreign key (NomeProgetto) references Progetto(Nome)
    );

CREATE TABLE Commento(
	Id INT auto_increment PRIMARY KEY,
    Data DATE,
    Testo VARCHAR(100),
    EmailUtente VARCHAR(20),
    NomeProgetto VARCHAR(20),
    foreign key (EmailUtente) references Utente(Email),
    foreign key (NomeProgetto) references Progetto(Nome)
    );
    
CREATE TABLE Risposta(
	Id INT auto_increment PRIMARY KEY,
    IdCommento INT,
    EmailUtente VARCHAR(20),
    foreign key (EmailUtente) references Utente(Email)
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
    Descrizione varchar(20),
    Quantità int
    );
    
CREATE TABLE Composizione(
	NomeComponente varchar(20),
    NomeProgetto varchar(20),
    PRIMARY KEY (NomeComponente, NomeProgetto),
    foreign key (NomeComponente) references Componente(Nome),
	foreign key (NomeProgetto) references Progetto(Nome)
    );
    
CREATE TABLE Finanziamento(
	Id int auto_increment primary key,
	Importo int, 
	Data date,
    EmailUtente varchar(20),
    NomeProgetto varchar(20),
    CodiceReward int,
    FOREIGN KEY (EmailUtente) REFERENCES Utente(Email),
    FOREIGN KEY (NomeProgetto) REFERENCES Progetto(Nome),
    FOREIGN KEY (CodiceReward) REFERENCES Reward(Id)
	);
    
CREATE TABLE Profilo (
	Nome varchar(20) primary key,
    Competenza varchar(5),
    Livello int,
    EmailUtente varchar(20),
    foreign key (EmailUtente) references Utente(Email)
    );
    
CREATE TABLE Reclutamento (
	NomeProfilo varchar(20),
    NomeProgetto varchar (20),
    PRIMARY KEY (NomeProfilo, NomeProgetto),
    foreign key (NomeProfilo) references Profilo(Nome),
    foreign key (NomeProgetto) references Progetto(Nome)
    );

/*-----------------------------------------------------------------------------------------------------*/

/*-----------------------------------------------------------------------------------------------------*/
/*OPERAZIONI SUI DATI
operazioni che riguardano tutti gli utenti*/

/*registrazione utente*/
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

/*autenticazione utente*/
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

/*inserimento skill di curriculum*/
DELIMITER //
CREATE PROCEDURE sp_aggiungi_skill (
    IN p_email VARCHAR(50),
    IN p_competenza VARCHAR(5),
    IN p_livello INT
)
BEGIN
	UPDATE Utente
    SET Competenza = p_competenza,
        Livello = p_livello
    WHERE Email = p_email;
END //
DELIMITER ;

/*visualizzazione progetto disponibili (aperti)*/
DELIMITER //
CREATE PROCEDURE sp_visualizza_progetti_aperti()
BEGIN
	SELECT * FROM Progetto
    WHERE Stato = 'aperto';
END //
DELIMITER ;

/*finanziamento progetto aperto*/
DELIMITER //
CREATE PROCEDURE finanzia_progetto (
    IN p_id_utente INT,
    IN p_id_progetto INT,
    IN p_importo DECIMAL(10,2)
)
BEGIN
    DECLARE stato_progetto VARCHAR(20);

    SELECT stato INTO stato_progetto
    FROM Progetto
    WHERE id = p_id_progetto;

    IF stato_progetto IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Progetto inesistente.';
    ELSEIF stato_progetto <> 'aperto' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Il progetto non è aperto al finanziamento.';
    END IF;

    INSERT INTO Finanziamento (id_utente, id_progetto, importo, data_finanziamento)
    VALUES (p_id_utente, p_id_progetto, p_importo, CURRENT_DATE);
END //
DELIMITER ;

/*scelta reward*/
DELIMITER //
CREATE PROCEDURE sp_finanzia_progetto (
    IN p_importo INT,
    IN p_email_utente VARCHAR(50),
    IN p_nome_progetto VARCHAR(20),
    IN p_codice_reward INT
)
BEGIN
	INSERT INTO Finanziamento(Importo, Data, EmailUtente, NomeProgetto, CodiceReward)
    VALUES (p_importo, CURDATE(), p_email_utente, p_nome_progetto, p_codice_reward);
END //
DELIMITER ;

/*inserimento commento relativo al progetto*/
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

/*inserimento candidatura*/
DELIMITER //
CREATE PROCEDURE sp_inserisci_candidatura (
	IN p_email_utente VARCHAR(50),
    IN p_nome_progetto VARCHAR(20),
    IN p_nome_profilo VARCHAR(20)
)
BEGIN
	INSERT INTO Candidatura(EmailUtente, NomeProgetto, NomeProfilo)
    VALUES (p_email_utente, p_nome_progetto, p_nome_profilo);
END //
DELIMITER ;

/*-----------------------------------------------------------------------------------------------------*/
/*operazioni che riguardano solo amministratori*/

/*inserimento nuova stringa*/
DELIMITER //
CREATE PROCEDURE sp_aggiungi_o_modifica_skill (
    IN p_email VARCHAR(50),
    IN p_competenza VARCHAR(5),
    IN p_livello INT
)
BEGIN
    UPDATE Utente
    SET Competenza = p_competenza,
        Livello = p_livello
    WHERE Email = p_email;
END //
DELIMITER ;

/*autenticazione amministratore*/
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
/*operazioni che riguardano solo i creatori*/

/*inserimento nuovo progetto*/
DELIMITER //
CREATE PROCEDURE sp_inserisci_progetto (
    IN p_nome VARCHAR(20),
    IN p_data_inserimento DATE,
    IN p_budget INT,
    IN p_descrizione VARCHAR(200),
    IN p_data_limite DATE,
    IN p_email_creatore VARCHAR(50)
)
BEGIN
    INSERT INTO Progetto (
        Nome, DataInserimento, Budget, Descrizione, Stato, DataLimite, EmailUtente
    )
    VALUES (
        p_nome, p_data_inserimento, p_budget, p_descrizione, 'aperto', p_data_limite, p_email_creatore
    );

    UPDATE Creatore
    SET NrProgetti = NrProgetti + 1
    WHERE EmailUtenteCreatore = p_email_creatore;
END //
DELIMITER ;

/*inserimento reward per un progetto*/
DELIMITER //
CREATE PROCEDURE sp_inserisci_reward (
    IN p_codice INT,
    IN p_descrizione VARCHAR(20),
    IN p_foto INT,
    IN p_nome_progetto VARCHAR(20)
)
BEGIN
    INSERT INTO Reward(Codice, Descrizione, Foto, NomeProgetto)
    VALUES (p_codice, p_descrizione, p_foto, p_nome_progetto);
END //
DELIMITER ;

/*inserimento risposta ad un commento*/
DELIMITER //
CREATE PROCEDURE sp_rispondi_a_commento (
    IN p_id_commento INT,
    IN p_email_creatore VARCHAR(50)
)
BEGIN
    INSERT INTO Risposta(Id, EmailUtente)
    VALUES (p_id_commento, p_email_creatore);
END //
DELIMITER ;

/*inserimento di un profilo (solo per progetto software)*/
DELIMITER //
CREATE PROCEDURE sp_inserisci_profilo (
    IN p_nome VARCHAR(20),
    IN p_competenza VARCHAR(5),
    IN p_livello INT,
    IN p_email_creatore VARCHAR(50)
)
BEGIN
    INSERT INTO Profilo(Nome, Competenza, Livello, EmailUtente)
    VALUES (p_nome, p_competenza, p_livello, p_email_creatore);
END //
DELIMITER ;

/*accettazione o meno della candidatura*/
DELIMITER //
CREATE PROCEDURE sp_valuta_candidatura_senza_tabella (
    IN p_email_utente VARCHAR(50),
    IN p_nome_progetto VARCHAR(20),
    IN p_nome_profilo VARCHAR(20),
    IN p_esito ENUM('accettata', 'rifiutata')
)
BEGIN
    UPDATE Profilo
    SET StatoCandidatura = p_esito
    WHERE EmailUtente = p_email_utente
      AND NomeProgetto = p_nome_progetto
      AND Nome = p_nome_profilo;
END //
DELIMITER ;
/*-----------------------------------------------------------------------------------------------------*/

/*-----------------------------------------------------------------------------------------------------*/
/*STATISTICHE (IMPLEMENTATE TRAMITE VISTE)*/

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

/*Incrementa NrProgetti e aggiorna affidabilità al momento della creazione progetto*/
DELIMITER //
CREATE TRIGGER trg_incrementa_progetti_affidabilita
AFTER INSERT ON Progetto
FOR EACH ROW
BEGIN
    
    #Incrementa numero progetti del creatore
    UPDATE Creatore
    SET NrProgetti = NrProgetti + 1
    WHERE EmailUtenteCreatore = NEW.EmailUtente;

	#Ricalcola affidabilità: % di progetti con almeno un finanziamento
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

/*Aggiorna affidabilità e chiude progetto se il budget è raggiunto*/
CREATE TRIGGER trg_aggiorna_affidabilita_e_chiusura
AFTER INSERT ON Finanziamento
FOR EACH ROW
BEGIN
    DECLARE v_email_creatore VARCHAR(50);
    DECLARE v_budget INT;
    DECLARE v_totale INT;

    #Ottieni creatore e budget del progetto
    SELECT EmailUtente, Budget INTO v_email_creatore, v_budget
    FROM Progetto
    WHERE Nome = NEW.NomeProgetto;

    #Calcola totale finanziamenti
    SELECT SUM(Importo) INTO v_totale
    FROM Finanziamento
    WHERE NomeProgetto = NEW.NomeProgetto;

    #Chiudi progetto se il budget è stato raggiunto
    IF v_totale >= v_budget THEN
        UPDATE Progetto
        SET Stato = 'chiuso'
        WHERE Nome = NEW.NomeProgetto;
    END IF;

    #Ricalcola affidabilità del creatore
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

/*incremento nr_progetti*/
DELIMITER //
CREATE TRIGGER trg_incrementa_nr_progetti
AFTER INSERT ON Progetto
FOR EACH ROW
BEGIN
    UPDATE Creatore
    SET NrProgetti = NrProgetti + 1
    WHERE EmailUtenteCreatore = NEW.EmailUtente;
END //
DELIMITER ;
/*-----------------------------------------------------------------------------------------------------*/

/*-----------------------------------------------------------------------------------------------------*/
/*IMPLEMENTAZIONE EVENTO*/

/*cambio stato di un progetto*/
DELIMITER //
CREATE EVENT IF NOT EXISTS ev_chiudi_progetti_scaduti
ON SCHEDULE EVERY 1 DAY
DO
BEGIN
    UPDATE Progetto
    SET Stato = 'chiuso'
    WHERE DataChiusura < CURDATE()
      AND Stato <> 'chiuso';
END //
DELIMITER ;
/*-----------------------------------------------------------------------------------------------------*/

INSERT INTO Utente(Username, Email, Password, Nome, Cognome, LuogoNascita, AnnoNascita) VALUES("Lollo", "lorenzo@cuoco.it", "Lollo", "Lorenzo", "Cuoco", "Bologna", 2003);
INSERT INTO Amministratore(EmailUtenteAmministratore, CodiceSicurezza) VALUES("lorenzo@cuoco.it", 2785);
INSERT INTO Utente(Username, Email, Password, Nome, Cognome, LuogoNascita, AnnoNascita) VALUES("Fede", "federico@tessari.it", "Fede", "Federico", "Tessari", "Soave", 2003);
INSERT INTO Amministratore(EmailUtenteAmministratore, CodiceSicurezza) VALUES("federico@tessari.it", 6437);
INSERT INTO Utente(Username, Email, Password, Nome, Cognome, LuogoNascita, AnnoNascita) VALUES("Maz", "giacomo@mazzoli.it", "Maz", "Giacomo", "Mazzoli", "Corticella", 2003);
INSERT INTO Amministratore(EmailUtenteAmministratore, CodiceSicurezza) VALUES("giacomo@mazzoli.it", 9632);

-- CREATORI
INSERT INTO Utente VALUES ('marco', 'marco@mail.it', 'pwd123', 'Marco', 'Rossi', 'Milano', 1995, NULL, NULL);
INSERT INTO Creatore VALUES ('marco@mail.it', 1, 0);

INSERT INTO Utente VALUES ('giulia', 'giulia@mail.it', 'pwd456', 'Giulia', 'Verdi', 'Roma', 1990, NULL, NULL);
INSERT INTO Creatore VALUES ('giulia@mail.it', 1, 0);

-- FINANZIATORI
INSERT INTO Utente VALUES ('luca', 'luca@mail.it', 'pwd789', 'Luca', 'Bianchi', 'Napoli', 1999, NULL, NULL);
INSERT INTO Utente VALUES ('anna', 'anna@mail.it', 'pwd000', 'Anna', 'Neri', 'Torino', 2001, NULL, NULL);

-- PROGETTI
INSERT INTO Progetto VALUES ('SmartLamp', CURDATE(), 1000, 'Lampada smart per la casa', 'aperto', CURDATE() + INTERVAL 10 DAY, 'marco@mail.it');
INSERT INTO Progetto VALUES ('EcoPrinter', CURDATE(), 2000, 'Stampante ecologica', 'aperto', CURDATE() + INTERVAL 5 DAY, 'giulia@mail.it');

-- REWARD
INSERT INTO Reward (Descrizione, Foto, NomeProgetto) VALUES ('Sticker pack', 'img1.jpg', 'SmartLamp');
INSERT INTO Reward (Descrizione, Foto, NomeProgetto) VALUES ('T-shirt', 'img2.jpg', 'EcoPrinter');

-- FINANZIAMENTI
INSERT INTO Finanziamento (Importo, Data, EmailUtente, NomeProgetto, CodiceReward) VALUES (300, CURDATE(), 'luca@mail.it', 'SmartLamp', 1);
INSERT INTO Finanziamento (Importo, Data, EmailUtente, NomeProgetto, CodiceReward) VALUES (500, CURDATE(), 'anna@mail.it', 'SmartLamp', 1);
INSERT INTO Finanziamento (Importo, Data, EmailUtente, NomeProgetto, CodiceReward) VALUES (800, CURDATE(), 'luca@mail.it', 'EcoPrinter', 2);