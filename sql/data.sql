-- Popolamento della tabella Sede
INSERT INTO sede (codice_sede, citta, indirizzo) VALUES
('SEDE001', 'Milano', 'Via Roma 10'),
('SEDE002', 'Roma', 'Piazza Venezia 5'),
('SEDE003', 'Firenze', 'Via Tornabuoni 15'),
('SEDE004', 'Napoli', 'Via Toledo 20');

-- Popolamento della tabella Autore
INSERT INTO autore (nome, cognome, data_nascita, data_morte, biografia) VALUES
('Umberto', 'Eco', '1932-01-05', '2016-02-19', 'Scrittore, filosofo e semiologo italiano.'),
('Italo', 'Calvino', '1923-10-15', '1985-09-19', 'Scrittore italiano, autore de "Il barone rampante".'),
('J.K.', 'Rowling', '1965-07-31', NULL, 'Scrittrice britannica, autrice di Harry Potter.'),
('George', 'Orwell', '1903-06-25', '1950-01-21', 'Scrittore britannico, autore di 1984 e La fattoria degli animali.'),
('Jane', 'Austen', '1775-12-16', '1817-07-18', 'Scrittrice britannica famosa per "Orgoglio e Pregiudizio".'),
('Elena', 'Ferrante', 'Unknown', NULL, 'Autrice italiana della saga "L’amica geniale".'),
('F. Scott', 'Fitzgerald', '1896-09-24', '1940-12-21', 'Scrittore americano, autore de "Il grande Gatsby".'),
('Alessandro', 'Manzoni', '1785-03-07', '1873-05-22', 'Autore del romanzo storico "I Promessi Sposi".');

-- Popolamento della tabella Libro
INSERT INTO libro (isbn, titolo, trama, casa_editrice) VALUES
('9788806227022', 'Il nome della rosa', 'Un giallo medievale ambientato in un monastero.', 'Bompiani'),
('9788804681963', 'Il barone rampante', 'Un ragazzo che vive sugli alberi per protesta.', 'Mondadori'),
('9780747532743', 'Harry Potter e la pietra filosofale', 'Il primo libro della saga di Harry Potter.', 'Bloomsbury'),
('9780451524935', '1984', 'Un romanzo distopico sulla società del controllo.', 'Penguin'),
('9788806210048', 'Se una notte d’inverno un viaggiatore', 'Un libro che racconta più storie interrotte.', 'Einaudi'),
('9788806069882', 'Le città invisibili', 'Un viaggio immaginario tra città fantastiche.', 'Einaudi'),
('9788804491022', 'Marcovaldo', 'Storie di un uomo semplice in una città moderna.', 'Einaudi'),
('9788817061624', 'La coscienza di Zeno', 'Un romanzo di introspezione psicologica.', 'Feltrinelli'),
('9780374528379', 'Il grande Gatsby', 'Un classico della letteratura americana.', 'Scribner'),
('9780141439556', 'Orgoglio e pregiudizio', 'Una storia d’amore e orgoglio nella società inglese.', 'Penguin'),
('9788806218726', 'I promessi sposi', 'Un romanzo storico ambientato nel Seicento.', 'Einaudi'),
('9788869183157', 'L’amica geniale', 'Il primo volume di una saga napoletana.', 'Edizioni E/O');

-- Popolamento della tabella LibroAutore (Associazione libri-autori con corrispondenza corretta)
INSERT INTO libro_autore (libro, autore) VALUES
(1, 1),  -- Il nome della rosa - Umberto Eco
(2, 2),  -- Il barone rampante - Italo Calvino
(3, 3),  -- Harry Potter - J.K. Rowling
(4, 4),  -- 1984 - George Orwell
(5, 2),  -- Se una notte d’inverno un viaggiatore - Italo Calvino
(6, 2),  -- Le città invisibili - Italo Calvino
(7, 2),  -- Marcovaldo - Italo Calvino
(8, 1),  -- La coscienza di Zeno - Umberto Eco
(9, 7),  -- Il grande Gatsby - F. Scott Fitzgerald
(10, 5), -- Orgoglio e pregiudizio - Jane Austen
(11, 8), -- I promessi sposi - Alessandro Manzoni
(12, 6); -- L’amica geniale - Elena Ferrante

-- Popolamento della tabella Copia (distribuzione casuale sulle sedi)
INSERT INTO copia (isbn, id_sede, disponibile) VALUES
('9788806227022', 1, TRUE),
('9788806227022', 2, TRUE),
('9788804681963', 3, TRUE),
('9780747532743', 4, TRUE),
('9780451524935', 1, TRUE),
('9788806210048', 2, TRUE),
('9788806069882', 3, TRUE),
('9788804491022', 4, TRUE),
('9788817061624', 1, TRUE),
('9780374528379', 2, TRUE),
('9780141439556', 3, TRUE),
('9788806218726', 4, TRUE);

-- Popolamento della tabella Lettore
INSERT INTO lettore (codice_fiscale, nome, cognome, email, password, categoria, ritardi) VALUES
('RSSMRC80A01H501Z', 'Marco', 'Rossi', 'marco.rossi@email.com', '1234', 'base', 0),
('BNCGLI85C41H501Y', 'Giulia', 'Bianchi', 'giulia.bianchi@email.com', '1234', 'premium', 1);

-- Popolamento della tabella Bibliotecario
INSERT INTO bibliotecario (nome, cognome, email, password) VALUES
('Luca', 'Verdi', 'luca.verdi@email.com', '1234'),
('Anna', 'Esposito', 'anna.esposito@email.com', '1234');

-- Nessun prestito registrato inizialmente
