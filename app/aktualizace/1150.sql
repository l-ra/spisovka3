
ALTER TABLE [{tbls3}epodatelna]
    ADD COLUMN [typ] CHAR(1) NOT NULL AFTER [odchozi];

UPDATE [{tbls3}epodatelna] SET [typ] = if([isds_id] IS NOT NULL, 'I', 'E');


