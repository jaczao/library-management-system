--
-- PostgreSQL database dump
--

-- Dumped from database version 17.2
-- Dumped by pg_dump version 17.2

-- Started on 2025-02-17 16:21:56 CET

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 2 (class 3079 OID 20773)
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA public;


--
-- TOC entry 3755 (class 0 OID 0)
-- Dependencies: 2
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


--
-- TOC entry 906 (class 1247 OID 20607)
-- Name: categoria_lettore; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.categoria_lettore AS ENUM (
    'base',
    'premium'
);


ALTER TYPE public.categoria_lettore OWNER TO postgres;

--
-- TOC entry 286 (class 1255 OID 20720)
-- Name: assegna_prestito(integer, integer, integer); Type: PROCEDURE; Schema: public; Owner: postgres
--

CREATE PROCEDURE public.assegna_prestito(IN p_id_lettore integer, IN p_id_libro integer, IN p_id_sede integer DEFAULT NULL::integer)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_id_copia INTEGER;
    available_sedi TEXT;
BEGIN
    IF p_id_sede IS NULL THEN
        -- Nessuna sede scelta: seleziona una copia disponibile in qualunque sede
        SELECT id_copia INTO v_id_copia
        FROM copia
        WHERE libro = p_id_libro AND disponibile = TRUE
        LIMIT 1;
        
        IF v_id_copia IS NULL THEN
           RAISE EXCEPTION 'Il libro non è disponibile in nessuna sede.';
        END IF;
    ELSE
        -- Sede scelta: cerca una copia disponibile in quella sede
        SELECT id_copia INTO v_id_copia
        FROM copia
        WHERE libro = p_id_libro AND disponibile = TRUE AND sede = p_id_sede
        LIMIT 1;
        
        IF v_id_copia IS NULL THEN
            -- Se non c'è copia disponibile in quella sede, recupera l'elenco delle sedi dove il libro è disponibile
            SELECT string_agg(s.codice_sede, ', ') INTO available_sedi
            FROM copia c JOIN sede s ON c.sede = s.id_sede
            WHERE c.libro = p_id_libro AND c.disponibile = TRUE;
            
            IF available_sedi IS NULL THEN
                RAISE EXCEPTION 'Il libro non è disponibile né in questa sede né in nessun altra.';
            ELSE
                RAISE EXCEPTION 'Il libro non è disponibile nella sede scelta. Disponibile nelle sedi: %', available_sedi;
            END IF;
        END IF;
    END IF;

    -- Inserimento del prestito
    INSERT INTO prestito (lettore, copia)
    VALUES (p_id_lettore, v_id_copia);
    
    -- Aggiornamento della disponibilità della copia assegnata
    UPDATE copia SET disponibile = FALSE WHERE id_copia = v_id_copia;
END;
$$;


ALTER PROCEDURE public.assegna_prestito(IN p_id_lettore integer, IN p_id_libro integer, IN p_id_sede integer) OWNER TO postgres;

--
-- TOC entry 287 (class 1255 OID 20721)
-- Name: azzera_ritardi(integer); Type: PROCEDURE; Schema: public; Owner: postgres
--

CREATE PROCEDURE public.azzera_ritardi(IN lettore_target integer)
    LANGUAGE plpgsql
    AS $$
BEGIN
    UPDATE lettore
       SET ritardi = 0
     WHERE id_lettore = lettore_target;
     
END;
$$;


ALTER PROCEDURE public.azzera_ritardi(IN lettore_target integer) OWNER TO postgres;

--
-- TOC entry 240 (class 1255 OID 20713)
-- Name: controlla_condizioni_prestito(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.controlla_condizioni_prestito() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    cnt_ritardi INTEGER;
    cat categoria_lettore;
    cnt_attivi INTEGER;
    available BOOLEAN;
BEGIN
    -- Verifica il contatore dei ritardi e la categoria del lettore
    SELECT ritardi, categoria INTO cnt_ritardi, cat 
      FROM lettore 
     WHERE id_lettore = NEW.lettore;
     
    IF cnt_ritardi >= 5 THEN
        RAISE EXCEPTION 'Il lettore ha % ritardi, non può richiedere nuovi prestiti', cnt_ritardi;
    END IF;
    
    -- Conta i prestiti attivi (senza restituzione) per il lettore
    SELECT COUNT(*) INTO cnt_attivi FROM prestito
     WHERE lettore = NEW.lettore AND data_restituzione IS NULL;
    
    IF cat = 'base' AND cnt_attivi >= 3 THEN
        RAISE EXCEPTION 'Il lettore è di categoria base e ha già % prestiti attivi', cnt_attivi;
    ELSIF cat = 'premium' AND cnt_attivi >= 5 THEN
        RAISE EXCEPTION 'Il lettore è di categoria premium e ha già % prestiti attivi', cnt_attivi;
    END IF;
    
    -- Verifica che la copia richiesta sia disponibile
    SELECT disponibile INTO available FROM copia WHERE id_copia = NEW.copia;

    IF NOT available THEN
        RAISE EXCEPTION 'La copia % non è disponibile', NEW.copia;
    END IF;
    
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.controlla_condizioni_prestito() OWNER TO postgres;

--
-- TOC entry 263 (class 1255 OID 20717)
-- Name: processa_restituzione(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.processa_restituzione() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    actual_return DATE;
    scadenza DATE;
BEGIN
    -- Elaborare solo se si passa da prestito non restituito a restituito
    IF OLD.data_restituzione IS NULL AND NEW.data_restituzione IS NOT NULL THEN
        actual_return := NEW.data_restituzione;
        scadenza := NEW.data_scadenza;
        
        -- Rende disponibile la copia
        UPDATE copia SET disponibile = TRUE WHERE id_copia = NEW.copia;
        
        -- Se la restituzione è in ritardo, incrementa il contatore dei ritardi
        IF actual_return > scadenza THEN
            UPDATE lettore SET ritardi = ritardi + 1 WHERE id_lettore = NEW.lettore;
        END IF;
    END IF;
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.processa_restituzione() OWNER TO postgres;

--
-- TOC entry 274 (class 1255 OID 20719)
-- Name: proroga_prestito(integer, date); Type: PROCEDURE; Schema: public; Owner: postgres
--

CREATE PROCEDURE public.proroga_prestito(IN p_id integer, IN p_new_scadenza date)
    LANGUAGE plpgsql
    AS $$
DECLARE
    current_scadenza DATE;
BEGIN
    -- Recupera la data di scadenza corrente per il prestito indicato
    SELECT data_scadenza INTO current_scadenza
      FROM prestito
     WHERE id_prestito = p_id;
     
    IF current_scadenza IS NULL THEN
        RAISE EXCEPTION 'Prestito non trovato';
    END IF;
    
    -- Se il prestito è già in ritardo, non è consentita la proroga
    IF CURRENT_DATE > current_scadenza THEN
        RAISE EXCEPTION 'Il prestito è già in ritardo e non può essere prorogato';
    END IF;
    
    -- Se la nuova data non è successiva all'attuale data di scadenza, solleva un'eccezione
    IF p_new_scadenza <= current_scadenza THEN
        RAISE EXCEPTION 'La nuova scadenza deve essere successiva all’attuale data di scadenza';
    END IF;
    
    -- Aggiorna la data di scadenza con il nuovo valore
    UPDATE prestito
       SET data_scadenza = p_new_scadenza
     WHERE id_prestito = p_id;
END;
$$;


ALTER PROCEDURE public.proroga_prestito(IN p_id integer, IN p_new_scadenza date) OWNER TO postgres;

--
-- TOC entry 288 (class 1255 OID 20722)
-- Name: refresh_sede_statistiche(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.refresh_sede_statistiche() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    REFRESH MATERIALIZED VIEW sede_statistiche;
    RETURN NULL;
END;
$$;


ALTER FUNCTION public.refresh_sede_statistiche() OWNER TO postgres;

--
-- TOC entry 241 (class 1255 OID 20715)
-- Name: segna_copia_non_disponibile(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.segna_copia_non_disponibile() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    UPDATE copia 
       SET disponibile = FALSE 
     WHERE id_copia = NEW.copia;
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.segna_copia_non_disponibile() OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 221 (class 1259 OID 20621)
-- Name: autore; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.autore (
    id_autore integer NOT NULL,
    nome character varying(100) NOT NULL,
    cognome character varying(100) NOT NULL,
    data_nascita date NOT NULL,
    data_morte date,
    biografia text NOT NULL
);


ALTER TABLE public.autore OWNER TO postgres;

--
-- TOC entry 220 (class 1259 OID 20620)
-- Name: autore_id_autore_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.autore_id_autore_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.autore_id_autore_seq OWNER TO postgres;

--
-- TOC entry 3756 (class 0 OID 0)
-- Dependencies: 220
-- Name: autore_id_autore_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.autore_id_autore_seq OWNED BY public.autore.id_autore;


--
-- TOC entry 230 (class 1259 OID 20686)
-- Name: bibliotecario; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.bibliotecario (
    id_bibliotecario integer NOT NULL,
    nome character varying(100) NOT NULL,
    cognome character varying(100) NOT NULL,
    email character varying(100) NOT NULL,
    password character varying(100) NOT NULL
);


ALTER TABLE public.bibliotecario OWNER TO postgres;

--
-- TOC entry 229 (class 1259 OID 20685)
-- Name: bibliotecario_id_bibliotecario_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.bibliotecario_id_bibliotecario_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.bibliotecario_id_bibliotecario_seq OWNER TO postgres;

--
-- TOC entry 3757 (class 0 OID 0)
-- Dependencies: 229
-- Name: bibliotecario_id_bibliotecario_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.bibliotecario_id_bibliotecario_seq OWNED BY public.bibliotecario.id_bibliotecario;


--
-- TOC entry 226 (class 1259 OID 20656)
-- Name: copia; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.copia (
    id_copia integer NOT NULL,
    libro integer NOT NULL,
    sede integer NOT NULL,
    disponibile boolean DEFAULT true NOT NULL
);


ALTER TABLE public.copia OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 20655)
-- Name: copia_id_copia_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.copia_id_copia_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.copia_id_copia_seq OWNER TO postgres;

--
-- TOC entry 3758 (class 0 OID 0)
-- Dependencies: 225
-- Name: copia_id_copia_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.copia_id_copia_seq OWNED BY public.copia.id_copia;


--
-- TOC entry 228 (class 1259 OID 20674)
-- Name: lettore; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.lettore (
    id_lettore integer NOT NULL,
    codice_fiscale character varying(20) NOT NULL,
    nome character varying(100) NOT NULL,
    cognome character varying(100) NOT NULL,
    email character varying(100) NOT NULL,
    password character varying(100) NOT NULL,
    categoria public.categoria_lettore NOT NULL,
    ritardi integer DEFAULT 0 NOT NULL
);


ALTER TABLE public.lettore OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 20673)
-- Name: lettore_id_lettore_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.lettore_id_lettore_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.lettore_id_lettore_seq OWNER TO postgres;

--
-- TOC entry 3759 (class 0 OID 0)
-- Dependencies: 227
-- Name: lettore_id_lettore_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.lettore_id_lettore_seq OWNED BY public.lettore.id_lettore;


--
-- TOC entry 223 (class 1259 OID 20630)
-- Name: libro; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.libro (
    id_libro integer NOT NULL,
    isbn character varying(13) NOT NULL,
    titolo character varying(200) NOT NULL,
    trama text NOT NULL,
    casa_editrice character varying(100) NOT NULL
);


ALTER TABLE public.libro OWNER TO postgres;

--
-- TOC entry 224 (class 1259 OID 20640)
-- Name: libro_autore; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.libro_autore (
    libro integer NOT NULL,
    autore integer NOT NULL
);


ALTER TABLE public.libro_autore OWNER TO postgres;

--
-- TOC entry 222 (class 1259 OID 20629)
-- Name: libro_id_libro_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.libro_id_libro_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.libro_id_libro_seq OWNER TO postgres;

--
-- TOC entry 3760 (class 0 OID 0)
-- Dependencies: 222
-- Name: libro_id_libro_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.libro_id_libro_seq OWNED BY public.libro.id_libro;


--
-- TOC entry 232 (class 1259 OID 20695)
-- Name: prestito; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prestito (
    id_prestito integer NOT NULL,
    lettore integer NOT NULL,
    copia integer NOT NULL,
    data_inizio date DEFAULT CURRENT_DATE NOT NULL,
    data_scadenza date DEFAULT ((CURRENT_DATE + '1 mon'::interval))::date NOT NULL,
    data_restituzione date
);


ALTER TABLE public.prestito OWNER TO postgres;

--
-- TOC entry 231 (class 1259 OID 20694)
-- Name: prestito_id_prestito_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.prestito_id_prestito_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.prestito_id_prestito_seq OWNER TO postgres;

--
-- TOC entry 3761 (class 0 OID 0)
-- Dependencies: 231
-- Name: prestito_id_prestito_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.prestito_id_prestito_seq OWNED BY public.prestito.id_prestito;


--
-- TOC entry 219 (class 1259 OID 20612)
-- Name: sede; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sede (
    id_sede integer NOT NULL,
    codice_sede character varying(50) NOT NULL,
    citta character varying(100) NOT NULL,
    indirizzo character varying(200) NOT NULL
);


ALTER TABLE public.sede OWNER TO postgres;

--
-- TOC entry 218 (class 1259 OID 20611)
-- Name: sede_id_sede_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.sede_id_sede_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.sede_id_sede_seq OWNER TO postgres;

--
-- TOC entry 3762 (class 0 OID 0)
-- Dependencies: 218
-- Name: sede_id_sede_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.sede_id_sede_seq OWNED BY public.sede.id_sede;


--
-- TOC entry 234 (class 1259 OID 20733)
-- Name: sede_ritardi; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.sede_ritardi AS
 SELECT s.codice_sede,
    s.citta,
    s.indirizzo,
    lib.isbn,
    lib.titolo,
    p.id_prestito,
    p.data_inizio,
    p.data_scadenza,
    lett.codice_fiscale,
    lett.nome AS nome_lettore,
    lett.cognome AS cognome_lettore
   FROM ((((public.prestito p
     JOIN public.copia c ON ((p.copia = c.id_copia)))
     JOIN public.sede s ON ((c.sede = s.id_sede)))
     JOIN public.libro lib ON ((c.libro = lib.id_libro)))
     JOIN public.lettore lett ON ((p.lettore = lett.id_lettore)))
  WHERE ((p.data_restituzione IS NULL) AND (CURRENT_DATE > p.data_scadenza));


ALTER VIEW public.sede_ritardi OWNER TO postgres;

--
-- TOC entry 233 (class 1259 OID 20725)
-- Name: sede_statistiche; Type: MATERIALIZED VIEW; Schema: public; Owner: postgres
--

CREATE MATERIALIZED VIEW public.sede_statistiche AS
 SELECT s.codice_sede,
    s.citta,
    s.indirizzo,
    count(c.id_copia) AS totale_copie,
    count(DISTINCT c.libro) AS totale_isbn,
    sum(
        CASE
            WHEN ((p.id_prestito IS NOT NULL) AND (p.data_restituzione IS NULL)) THEN 1
            ELSE 0
        END) AS prestiti_in_corso
   FROM ((public.sede s
     LEFT JOIN public.copia c ON ((s.id_sede = c.sede)))
     LEFT JOIN public.prestito p ON ((c.id_copia = p.copia)))
  GROUP BY s.codice_sede, s.citta, s.indirizzo
  WITH NO DATA;


ALTER MATERIALIZED VIEW public.sede_statistiche OWNER TO postgres;

--
-- TOC entry 3540 (class 2604 OID 20624)
-- Name: autore id_autore; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.autore ALTER COLUMN id_autore SET DEFAULT nextval('public.autore_id_autore_seq'::regclass);


--
-- TOC entry 3546 (class 2604 OID 20689)
-- Name: bibliotecario id_bibliotecario; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bibliotecario ALTER COLUMN id_bibliotecario SET DEFAULT nextval('public.bibliotecario_id_bibliotecario_seq'::regclass);


--
-- TOC entry 3542 (class 2604 OID 20659)
-- Name: copia id_copia; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.copia ALTER COLUMN id_copia SET DEFAULT nextval('public.copia_id_copia_seq'::regclass);


--
-- TOC entry 3544 (class 2604 OID 20677)
-- Name: lettore id_lettore; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lettore ALTER COLUMN id_lettore SET DEFAULT nextval('public.lettore_id_lettore_seq'::regclass);


--
-- TOC entry 3541 (class 2604 OID 20633)
-- Name: libro id_libro; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.libro ALTER COLUMN id_libro SET DEFAULT nextval('public.libro_id_libro_seq'::regclass);


--
-- TOC entry 3547 (class 2604 OID 20698)
-- Name: prestito id_prestito; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prestito ALTER COLUMN id_prestito SET DEFAULT nextval('public.prestito_id_prestito_seq'::regclass);


--
-- TOC entry 3539 (class 2604 OID 20615)
-- Name: sede id_sede; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sede ALTER COLUMN id_sede SET DEFAULT nextval('public.sede_id_sede_seq'::regclass);


--
-- TOC entry 3737 (class 0 OID 20621)
-- Dependencies: 221
-- Data for Name: autore; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.autore (id_autore, nome, cognome, data_nascita, data_morte, biografia) FROM stdin;
1	Umberto	Eco	1932-01-05	2016-02-19	Scrittore, filosofo e semiologo italiano.
2	Italo	Calvino	1923-10-15	1985-09-19	Scrittore italiano, autore de "Il barone rampante".
3	J.K.	Rowling	1965-07-31	\N	Scrittrice britannica, autrice di Harry Potter.
4	George	Orwell	1903-06-25	1950-01-21	Scrittore britannico, autore di 1984 e La fattoria degli animali.
5	Jane	Austen	1775-12-16	1817-07-18	Scrittrice britannica famosa per "Orgoglio e Pregiudizio".
6	Elena	Ferrante	1940-12-16	\N	Autrice italiana della saga "L’amica geniale".
7	F. Scott	Fitzgerald	1896-09-24	1940-12-21	Scrittore americano, autore de "Il grande Gatsby".
8	Alessandro	Manzoni	1785-03-07	1873-05-22	Autore del romanzo storico "I Promessi Sposi".
\.


--
-- TOC entry 3746 (class 0 OID 20686)
-- Dependencies: 230
-- Data for Name: bibliotecario; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.bibliotecario (id_bibliotecario, nome, cognome, email, password) FROM stdin;
1	Luca	Verdi	luca.verdi@email.com	$2a$06$RMBhpoybf3QUqzsBXvuAduaVaj61Lwg/TM.f8ph0ny9TWXOsEkGXG
2	Anna	Esposito	anna.esposito@email.com	$2a$06$UDvrBgQEP7BZVL7n4lnusOregq5kJ2/UjQMuOFDrMx8n62T4Lb/DW
\.


--
-- TOC entry 3742 (class 0 OID 20656)
-- Dependencies: 226
-- Data for Name: copia; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.copia (id_copia, libro, sede, disponibile) FROM stdin;
1	1	1	t
2	2	2	t
3	3	3	t
6	6	2	t
7	7	3	t
9	9	1	t
11	11	3	t
12	12	4	t
10	10	2	f
5	5	1	f
4	4	4	t
8	8	4	t
\.


--
-- TOC entry 3744 (class 0 OID 20674)
-- Dependencies: 228
-- Data for Name: lettore; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.lettore (id_lettore, codice_fiscale, nome, cognome, email, password, categoria, ritardi) FROM stdin;
1	RSSMRC80A01H501Z	Marco	Rossi	marco.rossi@email.com	$2a$06$YItQJYBjTfNRX5ylC4hFwOEIs1xCwhM01UACt6LGl8kQcqpO1EP22	base	0
2	BNCGLI85C41H501Y	Giulia	Bianchi	giulia.bianchi@email.com	$2a$06$LW4kF/4.GM9VyRzM2sKIoudKULjIs7jGmgDZH4W4m7PsWrdrFFulG	premium	2
\.


--
-- TOC entry 3739 (class 0 OID 20630)
-- Dependencies: 223
-- Data for Name: libro; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.libro (id_libro, isbn, titolo, trama, casa_editrice) FROM stdin;
1	9788806227022	Il nome della rosa	Un giallo medievale ambientato in un monastero.	Bompiani
2	9788804681963	Il barone rampante	Un ragazzo che vive sugli alberi per protesta.	Mondadori
3	9780747532743	Harry Potter e la pietra filosofale	Il primo libro della saga di Harry Potter.	Bloomsbury
4	9780451524935	1984	Un romanzo distopico sulla società del controllo.	Penguin
5	9788806210048	Se una notte d’inverno un viaggiatore	Un libro che racconta più storie interrotte.	Einaudi
6	9788806069882	Le città invisibili	Un viaggio immaginario tra città fantastiche.	Einaudi
7	9788804491022	Marcovaldo	Storie di un uomo semplice in una città moderna.	Einaudi
8	9788817061624	La coscienza di Zeno	Un romanzo di introspezione psicologica.	Feltrinelli
9	9780374528379	Il grande Gatsby	Un classico della letteratura americana.	Scribner
10	9780141439556	Orgoglio e pregiudizio	Una storia d’amore e orgoglio nella società inglese.	Penguin
11	9788806218726	I promessi sposi	Un romanzo storico ambientato nel Seicento.	Einaudi
12	9788869183157	L’amica geniale	Il primo volume di una saga napoletana.	Edizioni E/O
\.


--
-- TOC entry 3740 (class 0 OID 20640)
-- Dependencies: 224
-- Data for Name: libro_autore; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.libro_autore (libro, autore) FROM stdin;
1	1
2	2
3	3
4	4
5	2
6	2
7	2
8	1
9	7
10	5
11	8
12	6
\.


--
-- TOC entry 3748 (class 0 OID 20695)
-- Dependencies: 232
-- Data for Name: prestito; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.prestito (id_prestito, lettore, copia, data_inizio, data_scadenza, data_restituzione) FROM stdin;
1	1	10	2024-01-01	2024-02-01	\N
2	2	5	2024-02-01	2024-03-01	\N
3	2	4	2024-02-01	2024-05-01	2024-04-01
4	2	8	2024-02-01	2024-03-01	2024-04-01
\.


--
-- TOC entry 3735 (class 0 OID 20612)
-- Dependencies: 219
-- Data for Name: sede; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sede (id_sede, codice_sede, citta, indirizzo) FROM stdin;
1	SEDE001	Milano	Via Roma 10
2	SEDE002	Roma	Piazza Venezia 5
3	SEDE003	Firenze	Via Tornabuoni 15
4	SEDE004	Napoli	Via Toledo 20
\.


--
-- TOC entry 3763 (class 0 OID 0)
-- Dependencies: 220
-- Name: autore_id_autore_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.autore_id_autore_seq', 8, true);


--
-- TOC entry 3764 (class 0 OID 0)
-- Dependencies: 229
-- Name: bibliotecario_id_bibliotecario_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.bibliotecario_id_bibliotecario_seq', 2, true);


--
-- TOC entry 3765 (class 0 OID 0)
-- Dependencies: 225
-- Name: copia_id_copia_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.copia_id_copia_seq', 12, true);


--
-- TOC entry 3766 (class 0 OID 0)
-- Dependencies: 227
-- Name: lettore_id_lettore_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.lettore_id_lettore_seq', 2, true);


--
-- TOC entry 3767 (class 0 OID 0)
-- Dependencies: 222
-- Name: libro_id_libro_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.libro_id_libro_seq', 12, true);


--
-- TOC entry 3768 (class 0 OID 0)
-- Dependencies: 231
-- Name: prestito_id_prestito_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.prestito_id_prestito_seq', 4, true);


--
-- TOC entry 3769 (class 0 OID 0)
-- Dependencies: 218
-- Name: sede_id_sede_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.sede_id_sede_seq', 4, true);


--
-- TOC entry 3555 (class 2606 OID 20628)
-- Name: autore autore_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.autore
    ADD CONSTRAINT autore_pkey PRIMARY KEY (id_autore);


--
-- TOC entry 3571 (class 2606 OID 20693)
-- Name: bibliotecario bibliotecario_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bibliotecario
    ADD CONSTRAINT bibliotecario_email_key UNIQUE (email);


--
-- TOC entry 3573 (class 2606 OID 20691)
-- Name: bibliotecario bibliotecario_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.bibliotecario
    ADD CONSTRAINT bibliotecario_pkey PRIMARY KEY (id_bibliotecario);


--
-- TOC entry 3563 (class 2606 OID 20662)
-- Name: copia copia_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.copia
    ADD CONSTRAINT copia_pkey PRIMARY KEY (id_copia);


--
-- TOC entry 3565 (class 2606 OID 20682)
-- Name: lettore lettore_codice_fiscale_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lettore
    ADD CONSTRAINT lettore_codice_fiscale_key UNIQUE (codice_fiscale);


--
-- TOC entry 3567 (class 2606 OID 20684)
-- Name: lettore lettore_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lettore
    ADD CONSTRAINT lettore_email_key UNIQUE (email);


--
-- TOC entry 3569 (class 2606 OID 20680)
-- Name: lettore lettore_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lettore
    ADD CONSTRAINT lettore_pkey PRIMARY KEY (id_lettore);


--
-- TOC entry 3561 (class 2606 OID 20644)
-- Name: libro_autore libro_autore_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.libro_autore
    ADD CONSTRAINT libro_autore_pkey PRIMARY KEY (libro, autore);


--
-- TOC entry 3557 (class 2606 OID 20639)
-- Name: libro libro_isbn_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.libro
    ADD CONSTRAINT libro_isbn_key UNIQUE (isbn);


--
-- TOC entry 3559 (class 2606 OID 20637)
-- Name: libro libro_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.libro
    ADD CONSTRAINT libro_pkey PRIMARY KEY (id_libro);


--
-- TOC entry 3575 (class 2606 OID 20702)
-- Name: prestito prestito_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prestito
    ADD CONSTRAINT prestito_pkey PRIMARY KEY (id_prestito);


--
-- TOC entry 3551 (class 2606 OID 20619)
-- Name: sede sede_codice_sede_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sede
    ADD CONSTRAINT sede_codice_sede_key UNIQUE (codice_sede);


--
-- TOC entry 3553 (class 2606 OID 20617)
-- Name: sede sede_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sede
    ADD CONSTRAINT sede_pkey PRIMARY KEY (id_sede);


--
-- TOC entry 3583 (class 2620 OID 20714)
-- Name: prestito trg_controlla_condizioni_prestito; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_controlla_condizioni_prestito BEFORE INSERT ON public.prestito FOR EACH ROW EXECUTE FUNCTION public.controlla_condizioni_prestito();


--
-- TOC entry 3584 (class 2620 OID 20718)
-- Name: prestito trg_processa_restituzione; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_processa_restituzione AFTER UPDATE ON public.prestito FOR EACH ROW EXECUTE FUNCTION public.processa_restituzione();


--
-- TOC entry 3582 (class 2620 OID 20724)
-- Name: copia trg_refresh_copie; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_refresh_copie AFTER INSERT OR DELETE OR UPDATE ON public.copia FOR EACH STATEMENT EXECUTE FUNCTION public.refresh_sede_statistiche();


--
-- TOC entry 3585 (class 2620 OID 20723)
-- Name: prestito trg_refresh_prestiti; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_refresh_prestiti AFTER INSERT OR DELETE OR UPDATE ON public.prestito FOR EACH STATEMENT EXECUTE FUNCTION public.refresh_sede_statistiche();


--
-- TOC entry 3586 (class 2620 OID 20716)
-- Name: prestito trg_segna_copia_non_disponibile; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_segna_copia_non_disponibile AFTER INSERT ON public.prestito FOR EACH ROW EXECUTE FUNCTION public.segna_copia_non_disponibile();


--
-- TOC entry 3578 (class 2606 OID 20663)
-- Name: copia copia_libro_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.copia
    ADD CONSTRAINT copia_libro_fkey FOREIGN KEY (libro) REFERENCES public.libro(id_libro);


--
-- TOC entry 3579 (class 2606 OID 20668)
-- Name: copia copia_sede_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.copia
    ADD CONSTRAINT copia_sede_fkey FOREIGN KEY (sede) REFERENCES public.sede(id_sede);


--
-- TOC entry 3576 (class 2606 OID 20650)
-- Name: libro_autore libro_autore_autore_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.libro_autore
    ADD CONSTRAINT libro_autore_autore_fkey FOREIGN KEY (autore) REFERENCES public.autore(id_autore);


--
-- TOC entry 3577 (class 2606 OID 20645)
-- Name: libro_autore libro_autore_libro_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.libro_autore
    ADD CONSTRAINT libro_autore_libro_fkey FOREIGN KEY (libro) REFERENCES public.libro(id_libro);


--
-- TOC entry 3580 (class 2606 OID 20708)
-- Name: prestito prestito_copia_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prestito
    ADD CONSTRAINT prestito_copia_fkey FOREIGN KEY (copia) REFERENCES public.copia(id_copia);


--
-- TOC entry 3581 (class 2606 OID 20703)
-- Name: prestito prestito_lettore_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prestito
    ADD CONSTRAINT prestito_lettore_fkey FOREIGN KEY (lettore) REFERENCES public.lettore(id_lettore);


--
-- TOC entry 3749 (class 0 OID 20725)
-- Dependencies: 233 3751
-- Name: sede_statistiche; Type: MATERIALIZED VIEW DATA; Schema: public; Owner: postgres
--

REFRESH MATERIALIZED VIEW public.sede_statistiche;


-- Completed on 2025-02-17 16:21:56 CET

--
-- PostgreSQL database dump complete
--

