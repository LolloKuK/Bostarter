DROP DATABASE IF EXISTS Bostarter;
CREATE DATABASE IF NOT EXISTS Bostarter;
USE Bostarter;

/*CREAZIONE TABELLE*/
CREATE TABLE Utente(
	Email VARCHAR(50) PRIMARY KEY,
    Nome VARCHAR (20),
    Cognome VARCHAR (20),
    Competenza VARCHAR (5),
    Livello INT,
    LuogoNascita VARCHAR (20),
    Username VARCHAR(20),
    Password VARCHAR (20),
    AnnoNascita INT
    );
    
CREATE TABLE Creatore(
	EmailUtenteCreatore varchar(20) primary key,
    NrProgetti int,
    Affidabilità int,
	Foreign key (EmailUtenteCreatore) references Utente(Email)
    );
    
CREATE TABLE Amministratore(
	EmailUtenteAmministratore varchar(20) primary key,
    CodiceSicurezza int,
    Foreign key (EmailUtenteAmministratore) references Utente(Email)
    );
    
CREATE TABLE Progetto(
	Nome VARCHAR (20) PRIMARY KEY,
    DataInserimento date,
    Budget int,
    Descrizione varchar(20),
    Stato enum ('aperto', 'chiuso'),
    DataLimite date,
    EmailUtente varchar(20),
    foreign key (EmailUtente) references Utente(Email)
    );

CREATE TABLE Foto(
	Id int auto_increment primary key,
    NomeProgetto varchar(20),
    Foreign key (NomeProgetto) references Progetto(Nome)
    );
    
CREATE TABLE Software (
	NomeProgettoSoftware varchar(20) primary key,
    foreign key (NomeProgettoSoftware) references Progetto(Nome)
    );
    
CREATE TABLE Hardware (
	NomeProgettoHardware varchar(20) primary key,
	foreign key (NomeProgettoHardware) references Progetto(Nome)
    );
    
CREATE TABLE Componente (
	Nome varchar(20) primary key,
    Prezzo int,
    Descrizione varchar(20),
    Quantità int
    );
    
CREATE TABLE Reward(
	Codice int auto_increment primary key,
    Descrizione varchar(20),
    Foto int,
    NomeProgetto varchar(20),
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
    FOREIGN KEY (CodiceReward) REFERENCES Reward(Codice)
	);
    
CREATE TABLE Commento(
	Id int auto_increment primary key,
    Data date,
    Testo varchar(20),
    EmailUtente varchar(20),
    NomeProgetto varchar(20),
    Foreign key (EmailUtente) references Utente(Email),
    Foreign key (NomeProgetto) references Progetto(Nome)
    );
    
CREATE TABLE Risposta(
	Id int auto_increment primary key,
    EmailUtente varchar(20),
    foreign key (EmailUtente) references Utente(Email)
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
    
CREATE TABLE Composizione (
	NomeComponente varchar(20),
    NomeProgetto varchar(20),
    PRIMARY KEY (NomeComponente, NomeProgetto),
    foreign key (NomeComponente) references Componente(Nome),
	foreign key (NomeProgetto) references Progetto(Nome)
    );
/*FINE CREAZIONE TABELLE*/

/*OPERAZIONI SUI DATI*/    
/*operazioni che riguardano tutti gli utenti*/ 
#registrazione utente
DELIMITER //
CREATE PROCEDURE sp_registra_utente (
	IN p_email VARCHAR(50),
    IN p_nome VARCHAR(20),
    IN p_cognome VARCHAR(20),
    IN p_username VARCHAR(20),
    IN p_password VARCHAR(20),
    IN p_annoNascita INT,
    IN p_luogoNascita VARCHAR(20)
)
BEGIN
	INSERT INTO Utente (Email, Nome, Cognome, Username, Password, AnnoNascita, LuogoNascita)
    VALUES (p_email, p_nome, p_cognome, p_username, p_password, p_annoNascita, p_luogoNascita);
END //
DELIMITER ;

#autenticazione utentes
DELIMITER //
CREATE PROCEDURE sp_autentica_utente (
	IN p_email VARCHAR(50),
    IN p_password VARCHAR(20)
)
BEGIN
	SELECT Email, Nome, Cognome, Username
    FROM Utente
    WHERE Email = p_email AND Password = p_password;
END //
DELIMITER ;

#inserimento skill di curriculum
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

#visualizzazione progetto disponibili (aperti)
DELIMITER //
CREATE PROCEDURE sp_visualizza_progetti_aperti()
BEGIN
	SELECT * FROM Progetto
    WHERE Stato = 'aperto';
END //
DELIMITER ;

#scelta reward
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

#inserimento commento relativo al progetto
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

#inserimento candidatura
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
/*fine operazioni che riguardano tutti gli utenti*/

/*operazioni che riguardano solo amministratori*/
#inserimento nuova stringa
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

#autenticazione amministratore
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
/*fine operazioni solo amministratori*/

/*operazioni che riguardano solo i creatori*/
#inserimento nuovo progetto
DELIMITER //
CREATE PROCEDURE sp_inserisci_progetto (
    IN p_nome VARCHAR(20),
    IN p_data_inserimento DATE,
    IN p_budget INT,
    IN p_descrizione VARCHAR(20),
    IN p_data_limite DATE,
    IN p_email_creatore VARCHAR(50)
)
BEGIN
    -- Inserimento del progetto
    INSERT INTO Progetto (
        Nome, DataInserimento, Budget, Descrizione, Stato, DataLimite, EmailUtente
    )
    VALUES (
        p_nome, p_data_inserimento, p_budget, p_descrizione, 'aperto', p_data_limite, p_email_creatore
    );

    -- Aggiorna il numero di progetti del creatore
    UPDATE Creatore
    SET NrProgetti = NrProgetti + 1
    WHERE EmailUtenteCreatore = p_email_creatore;
END //
DELIMITER ;

#inserimento reward per un progetto
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

#inserimento risposta ad un commento
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

#inserimento di un profilo (solo per progetto software)
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

#accettazione o meno della candidatura
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
/*fine operazioni solo creatori*/
/*FINE OPERAZIONI SUI DATI*/

/*STATISTICHE (IMPLEMENTATE TRAMITE VISTE)*/
#Visualizzare la classifica	degli utenti creatori, in base al loro valore di affidabilità.	
#Mostrare solo il nickname dei primi 3 utenti.
SELECT EmailUtenteCreatore, Affidabilità
FROM Creatore
ORDER BY Affidabilità DESC
LIMIT 3;

#Visualizzare i	progetti APERTI	che	sono più vicini	al proprio completamento(=minore	
#differenza tra budget richiesto e somma totale dei finanziamenti ricevuti). Mostrare solo i	
#primi 3 progetti.
SELECT p.Nome, 
       p.Budget, 
       SUM(f.Importo) AS SommaFinanziamenti, 
       ABS(p.Budget - SUM(f.Importo)) AS Differenza
FROM Progetto p
JOIN Finanziamento f ON p.Nome = f.NomeProgetto
WHERE p.Stato = 'Aperto'
GROUP BY p.Nome
ORDER BY Differenza ASC
LIMIT 3;

#visualizzare la classifica degli utenti, ordinati in base al totale di finanziamenti erogati.
#Mostrare solo i nickname dei primi 3 utenti
SELECT f.EmailUtente, 
       SUM(f.Importo) AS TotaleFinanziamenti
FROM Finanziamento f
GROUP BY f.EmailUtente
ORDER BY TotaleFinanziamenti DESC
LIMIT 3;
/*FINE STATISTICHE*/    