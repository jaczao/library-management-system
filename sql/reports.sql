CREATE MATERIALIZED VIEW sede_statistiche AS
SELECT 
    s.codice_sede,
    COUNT(c.id_copia) AS totale_copie,
    COUNT(DISTINCT c.isbn) AS totale_libri,
    SUM(CASE WHEN p.id_prestito IS NOT NULL AND p.data_restituzione IS NULL THEN 1 ELSE 0 END) AS prestiti_in_corso
FROM sede s
LEFT JOIN copia c ON s.id_sede = c.id_sede
LEFT JOIN prestito p ON c.id_copia = p.copia
GROUP BY s.codice_sede;



CREATE MATERIALIZED VIEW sede_ritardi AS
SELECT 
    s.codice_sede,
    lib.isbn,
    lib.titolo,
    aut.nome,
    aut.cognome,
    p.data_inizio,
    p.data_scadenza,
    lett.codice_fiscale,
    lett.nome,
    lett.cognome
FROM prestito p
JOIN copia c ON p.copia = c.id_copia
JOIN sede s ON c.id_sede = s.id_sede
JOIN libro lib ON c.isbn = lib.isbn
JOIN libro_autore la ON la.libro = lib.id_libro
JOIN autore aut ON aut.id_autore = la.autore
JOIN lettore lett ON p.lettore = lett.id_lettore
WHERE p.data_restituzione IS NULL
  AND CURRENT_DATE > p.data_scadenza;

