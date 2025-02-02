-- Creazione di un tipo ENUM per la categoria dei lettori
CREATE TYPE categoria_utente AS ENUM ('base', 'premium');

-----------------------------------------------------------
-- Tabella delle Sedi della Biblioteca
-----------------------------------------------------------
CREATE TABLE sede (
    id_sede serial PRIMARY KEY,
    codice_sede VARCHAR(50) UNIQUE NOT NULL,
    citta VARCHAR(100) NOT NULL,
    indirizzo VARCHAR(200) NOT NULL
);

-----------------------------------------------------------
-- Tabella degli Autori
-----------------------------------------------------------
CREATE TABLE autore (
    id_autore serial PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    data_nascita DATE NOT NULL,
    data_morte DATE,
    biografia TEXT
);

-----------------------------------------------------------
-- Tabella dei Libri
-----------------------------------------------------------
CREATE TABLE libro (
    isbn VARCHAR(20) PRIMARY KEY,
    titolo VARCHAR(200) NOT NULL,
    trama TEXT,
    casa_editrice VARCHAR(100)
);

-----------------------------------------------------------
-- Tabella di Associazione tra Libri e Autori (relazione molti-a-molti)
-----------------------------------------------------------
CREATE TABLE libro_autore (
    isbn VARCHAR(20) NOT NULL,
    id_autore INTEGER NOT NULL,
    PRIMARY KEY (isbn, id_autore),
    FOREIGN KEY (isbn) REFERENCES libro(isbn),
    FOREIGN KEY (id_autore) REFERENCES autore(id_autore)
);

-----------------------------------------------------------
-- Tabella delle Copie
-----------------------------------------------------------
CREATE TABLE copia (
    id_copia serial PRIMARY KEY,
    isbn VARCHAR(20) NOT NULL,
    id_sede INTEGER NOT NULL,
    disponibile BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (isbn) REFERENCES libro(isbn),
    FOREIGN KEY (id_sede) REFERENCES sede(id_sede)
);

-----------------------------------------------------------
-- Tabella dei Lettori
-----------------------------------------------------------
CREATE TABLE lettore (
    codice_fiscale VARCHAR(16) PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    categoria categoria_utente NOT NULL,
    ritardi INTEGER DEFAULT 0
);

-----------------------------------------------------------
-- Tabella dei Prestiti
-----------------------------------------------------------
CREATE TABLE prestito (
    id_prestito serial PRIMARY KEY,
    codice_fiscale VARCHAR(16) NOT NULL,
    id_copia INTEGER NOT NULL,
    data_inizio DATE NOT NULL DEFAULT CURRENT_DATE,
    -- Data di scadenza di default: 1 mese dalla data corrente
    data_scadenza DATE NOT NULL DEFAULT (CURRENT_DATE + INTERVAL '1 month')::date,
    data_restituzione DATE,
    FOREIGN KEY (codice_fiscale) REFERENCES lettore(codice_fiscale),
    FOREIGN KEY (id_copia) REFERENCES copia(id_copia)
);
