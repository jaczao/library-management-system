CREATE MATERIALIZED VIEW sede_statistiche AS
SELECT 
    s.id_sede,
    s.codice_sede,
    COUNT(c.id_copia) AS totale_copie,
    COUNT(DISTINCT c.isbn) AS totale_libri,
    SUM(CASE WHEN p.data_restituzione IS NULL THEN 1 ELSE 0 END) AS prestiti_in_corso
FROM sede s
LEFT JOIN copia c ON s.id_sede = c.id_sede
LEFT JOIN prestito p ON c.id_copia = p.copia
GROUP BY s.id_sede, s.codice_sede;



CREATE VIEW sede_ritardi AS
SELECT 
    s.id_sede,
    s.codice_sede,
    lib.isbn,
    lib.titolo,
    p.id_prestito,
    p.data_inizio,
    p.data_scadenza,
    lett.codice_fiscale,
    lett.nome,
    lett.cognome
FROM prestito p
JOIN copia c ON p.copia = c.id_copia
JOIN sede s ON c.id_sede = s.id_sede
JOIN libro lib ON c.isbn = lib.isbn
JOIN lettore lett ON p.lettore = lett.id_lettore
WHERE p.data_restituzione IS NULL
  AND CURRENT_DATE > p.data_scadenza;

