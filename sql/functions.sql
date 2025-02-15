CREATE OR REPLACE FUNCTION check_prestito_conditions()
RETURNS TRIGGER AS $$
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
        RAISE EXCEPTION 'La copia % non è disponibile', NEW.copia;  --checko se giusto fare che disponibilita la vedo proprio su copia
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger BEFORE INSERT sulla tabella prestito
CREATE TRIGGER trg_check_prestito
BEFORE INSERT ON prestito
FOR EACH ROW EXECUTE FUNCTION check_prestito_conditions();



CREATE OR REPLACE FUNCTION mark_copia_unavailable()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE copia 
       SET disponibile = FALSE 
     WHERE id_copia = NEW.copia;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_mark_copia_unavailable
AFTER INSERT ON prestito
FOR EACH ROW EXECUTE FUNCTION mark_copia_unavailable();



CREATE OR REPLACE FUNCTION process_restituzione()
RETURNS TRIGGER AS $$
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
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_process_restituzione
AFTER UPDATE ON prestito
FOR EACH ROW EXECUTE FUNCTION process_restituzione();



CREATE OR REPLACE PROCEDURE proroga_prestito(p_id INTEGER, p_new_scadenza DATE)
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
    
    -- Aggiorna la data di scadenza con il nuovo valore
    UPDATE prestito
       SET data_scadenza = p_new_scadenza
     WHERE id_prestito = p_id;  --posso aggiungere alla fine COMMIT;
     
END;
$$;



CREATE OR REPLACE PROCEDURE assegna_prestito(
    p_id_lettore INTEGER,
    p_id_libro INTEGER,
    p_id_sede INTEGER DEFAULT NULL
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_id_copia INTEGER;
    available_sedi TEXT;
BEGIN
    IF p_id_sede IS NULL THEN
        -- Nessuna sede scelta: seleziona a caso una copia disponibile del libro
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




CREATE OR REPLACE PROCEDURE azzera_ritardi(lettore_target INTEGER)
LANGUAGE plpgsql
AS $$
BEGIN
    UPDATE lettore
       SET ritardi = 0
     WHERE id_lettore = lettore_target;
     
    -- Se necessario, COMMIT;
END;
$$;



-- 1. Funzione trigger che refresha direttamente la materialized view sede_statistiche
CREATE OR REPLACE FUNCTION trg_refresh_sede_statistiche()
RETURNS trigger AS $$
BEGIN
    REFRESH MATERIALIZED VIEW sede_statistiche;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

-- 2. Primo trigger che si attiva su prestito
CREATE TRIGGER trg_refresh_prestiti
AFTER INSERT OR UPDATE OR DELETE ON prestito
FOR EACH STATEMENT
EXECUTE FUNCTION trg_refresh_sede_statistiche();

-- 3. Secondo trigger che si attiva su  copia
CREATE TRIGGER trg_refresh_copie
AFTER INSERT OR UPDATE OR DELETE ON copia
FOR EACH STATEMENT
EXECUTE FUNCTION trg_refresh_sede_statistiche();
