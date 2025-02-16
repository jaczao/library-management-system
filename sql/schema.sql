-- Mi connetto a un database diverso prima di eliminare quello attuale
\c postgres

-- Termino tutte le connessioni al database "biblioteca"
SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = 'biblioteca';

-- Elimino il database se esiste
DROP DATABASE IF EXISTS biblioteca;

-- Creo nuovamente il database
CREATE DATABASE biblioteca;

-- Mi connetto al nuovo database
\c biblioteca;

-- Creo un tipo ENUM per la categoria dei lettori
CREATE TYPE categoria_lettore AS ENUM ('base', 'premium');


CREATE TABLE sede (
    id_sede serial PRIMARY KEY,
    codice_sede VARCHAR(50) UNIQUE NOT NULL,
    citta VARCHAR(100) NOT NULL,
    indirizzo VARCHAR(200) NOT NULL
);


CREATE TABLE autore (
    id_autore serial PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    data_nascita DATE NOT NULL,
    data_morte DATE,
    biografia TEXT NOT NULL
);


CREATE TABLE libro (
    id_libro serial PRIMARY KEY, 
    isbn VARCHAR(13) UNIQUE NOT NULL,
    titolo VARCHAR(200) NOT NULL,
    trama TEXT NOT NULL,
    casa_editrice VARCHAR(100) NOT NULL
);


CREATE TABLE libro_autore (
    libro INTEGER NOT NULL,
    autore INTEGER NOT NULL,
    PRIMARY KEY (libro, autore),
    FOREIGN KEY (libro) REFERENCES libro(id_libro),
    FOREIGN KEY (autore) REFERENCES autore(id_autore)
);


CREATE TABLE copia (
    id_copia serial PRIMARY KEY,
    libro INTEGER NOT NULL,
    sede INTEGER NOT NULL,
    disponibile BOOLEAN NOT NULL DEFAULT TRUE, 
    FOREIGN KEY (libro) REFERENCES libro(id_libro),
    FOREIGN KEY (sede) REFERENCES sede(id_sede)
);


CREATE TABLE lettore (
    id_lettore serial PRIMARY KEY,
    codice_fiscale VARCHAR(20) UNIQUE NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(100) NOT NULL,
    categoria categoria_lettore NOT NULL,
    ritardi INTEGER NOT NULL DEFAULT 0 
);


CREATE TABLE bibliotecario (
    id_bibliotecario serial PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cognome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(100) NOT NULL
);


CREATE TABLE prestito (
    id_prestito serial PRIMARY KEY,
    lettore INTEGER NOT NULL,
    copia INTEGER NOT NULL,
    data_inizio DATE NOT NULL DEFAULT CURRENT_DATE,
    data_scadenza DATE NOT NULL DEFAULT (CURRENT_DATE + INTERVAL '1 month')::date,
    data_restituzione DATE,
    FOREIGN KEY (lettore) REFERENCES lettore(id_lettore),
    FOREIGN KEY (copia) REFERENCES copia(id_copia)
);
