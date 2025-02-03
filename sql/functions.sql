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
        UPDATE copia SET disponibile = TRUE WHERE id_copia = NEW.id_copia;
        
        -- Se la restituzione è in ritardo, incrementa il contatore dei ritardi
        IF actual_return > scadenza THEN
            UPDATE lettore SET ritardi = ritardi + 1 WHERE codice_fiscale = NEW.codice_fiscale;
        END IF;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_process_restituzione
AFTER UPDATE ON prestito
FOR EACH ROW EXECUTE FUNCTION process_restituzione();



CREATE OR REPLACE FUNCTION proroga_prestito(p_id INTEGER, p_new_scadenza DATE)
RETURNS VOID AS $$
DECLARE
    current_scadenza DATE;
BEGIN
    SELECT data_scadenza INTO current_scadenza 
      FROM prestito 
     WHERE id_prestito = p_id;
     
    IF current_scadenza IS NULL THEN
        RAISE EXCEPTION 'Prestito % non trovato', p_id;
    END IF;
    
    IF CURRENT_DATE > current_scadenza THEN
        RAISE EXCEPTION 'Il prestito % è già in ritardo e non può essere prorogato', p_id;
    END IF;
    
    UPDATE prestito 
       SET data_scadenza = p_new_scadenza 
     WHERE id_prestito = p_id;
END;
$$ LANGUAGE plpgsql;



CREATE OR REPLACE FUNCTION assegna_prestito(
    p_codice_fiscale VARCHAR,
    p_isbn VARCHAR,
    p_id_sede INTEGER DEFAULT NULL,
    p_data_scadenza DATE DEFAULT (CURRENT_DATE + INTERVAL '1 month')::date
) 
RETURNS INTEGER AS $$
DECLARE
    v_id_copia INTEGER;
    v_selected_sede INTEGER;
    v_id_prestito INTEGER;
BEGIN
    IF p_id_sede IS NOT NULL THEN
        -- Tenta di trovare una copia disponibile nella sede specificata
        SELECT id_copia INTO v_id_copia 
          FROM copia 
         WHERE isbn = p_isbn 
           AND id_sede = p_id_sede 
           AND disponibile = TRUE
         LIMIT 1;
         
        IF v_id_copia IS NULL THEN
            -- Se non ci sono copie disponibili nella sede indicata, cerca in altre sedi
            SELECT id_copia, id_sede INTO v_id_copia, v_selected_sede 
              FROM copia 
             WHERE isbn = p_isbn 
               AND disponibile = TRUE
             LIMIT 1;
            IF v_id_copia IS NULL THEN
                RAISE EXCEPTION 'Nessuna copia disponibile per il libro %', p_isbn;
            ELSE
                RAISE NOTICE 'Copia assegnata dalla sede % invece di quella richiesta %', v_selected_sede, p_id_sede;
            END IF;
        END IF;
    ELSE
        -- Nessuna sede specificata: cerca in qualsiasi sede
        SELECT id_copia INTO v_id_copia 
          FROM copia 
         WHERE isbn = p_isbn 
           AND disponibile = TRUE
         LIMIT 1;
        IF v_id_copia IS NULL THEN
            RAISE EXCEPTION 'Nessuna copia disponibile per il libro %', p_isbn;
        END IF;
    END IF;
    
    -- Inserisce il prestito (i trigger verificheranno i vincoli e aggiorneranno la disponibilità)
    INSERT INTO prestito(codice_fiscale, id_copia, data_scadenza)
    VALUES (p_codice_fiscale, v_id_copia, p_data_scadenza)
    RETURNING id_prestito INTO v_id_prestito;
    
    RETURN v_id_prestito;
END;
$$ LANGUAGE plpgsql;



CREATE OR REPLACE FUNCTION azzera_ritardi(p_codice_fiscale VARCHAR)
RETURNS VOID AS $$
BEGIN
    UPDATE lettore 
       SET ritardi = 0 
     WHERE codice_fiscale = p_codice_fiscale;
END;
$$ LANGUAGE plpgsql;



