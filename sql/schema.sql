-- Creazione di un tipo ENUM per la categoria dei lettori
CREATE TYPE categoria_lettore AS ENUM ('base', 'premium');  --verifico se serve nelle funzioni o altro

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
    biografia TEXT NOT NULL
);

-----------------------------------------------------------
-- Tabella dei Libri
-----------------------------------------------------------
CREATE TABLE libro (
    id_libro serial PRIMARY KEY, 
    isbn VARCHAR(13) UNIQUE NOT NULL,
    titolo VARCHAR(200) NOT NULL,
    trama TEXT NOT NULL,
    casa_editrice VARCHAR(100) NOT NULL
);

-----------------------------------------------------------
-- Tabella di Associazione tra Libri e Autori (relazione molti-a-molti)
-----------------------------------------------------------
CREATE TABLE libro_autore (
    libro INTEGER NOT NULL,
    autore INTEGER NOT NULL,
    PRIMARY KEY (libro, autore),
    FOREIGN KEY (libro) REFERENCES libro(id_libro),
    FOREIGN KEY (autore) REFERENCES autore(id_autore)
);

-----------------------------------------------------------
-- Tabella delle Copie
-----------------------------------------------------------
CREATE TABLE copia (
    id_copia serial PRIMARY KEY,
    isbn VARCHAR(13) NOT NULL,
    id_sede INTEGER NOT NULL,
    disponibile BOOLEAN NOT NULL DEFAULT TRUE, --verifico se not null o meno in base alle funzioni
    FOREIGN KEY (isbn) REFERENCES libro(isbn),
    FOREIGN KEY (id_sede) REFERENCES sede(id_sede)
);

-----------------------------------------------------------
-- Tabella dei Lettori
-----------------------------------------------------------
CREATE TABLE lettore (
    id_lettore serial PRIMARY KEY
    codice_fiscale VARCHAR(20) UNIQUE NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(100) NOT NULL,
    categoria categoria_lettore NOT NULL,
    ritardi INTEGER NOT NULL DEFAULT 0 --verifico se not null o meno in base alle funzioni
);

-----------------------------------------------------------
-- Tabella dei Bibliotecari
-----------------------------------------------------------
CREATE TABLE bibliotecario (
    id_bibliotecario serial PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(100) NOT NULL
);

-----------------------------------------------------------
-- Tabella dei Prestiti
-----------------------------------------------------------
CREATE TABLE prestito (
    id_prestito serial PRIMARY KEY,
    lettore INTEGER NOT NULL,
    copia INTEGER NOT NULL,
    data_inizio DATE NOT NULL DEFAULT CURRENT_DATE,
    data_scadenza DATE NOT NULL DEFAULT (CURRENT_DATE + INTERVAL '1 month')::date,
    data_restituzione DATE,
    FOREIGN KEY (codice_fiscale) REFERENCES lettore(id_lettore),
    FOREIGN KEY (copia) REFERENCES copia(id_copia)
);
