CREATE OR REPLACE MATERIALIZED VIEW sede_statistiche AS
SELECT 
    s.codice_sede,
    COUNT(c.id_copia) AS totale_copie,
    COUNT(DISTINCT c.libro) AS totale_libri,
    SUM(CASE WHEN p.id_prestito IS NOT NULL AND p.data_restituzione IS NULL THEN 1 ELSE 0 END) AS prestiti_in_corso
FROM sede s
LEFT JOIN copia c ON s.id_sede = c.sede
LEFT JOIN prestito p ON c.id_copia = p.copia
GROUP BY s.codice_sede;



CREATE OR REPLACE VIEW sede_ritardi AS
SELECT 
    s.codice_sede,
    lib.isbn,
    lib.titolo,
    aut.nome as nome_autore,
    aut.cognome as cognome_autore,
    p.data_inizio,
    p.data_scadenza,
    lett.codice_fiscale,
    lett.nome as nome_lettore,
    lett.cognome as cognome_lettore
FROM prestito p
JOIN copia c ON p.copia = c.id_copia
JOIN sede s ON c.sede = s.id_sede
JOIN libro lib ON c.libro = lib.id_libro
JOIN libro_autore la ON la.libro = lib.id_libro
JOIN autore aut ON aut.id_autore = la.autore
JOIN lettore lett ON p.lettore = lett.id_lettore
WHERE p.data_restituzione IS NULL
  AND CURRENT_DATE > p.data_scadenza;

