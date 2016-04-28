
ALTER TABLE [:PREFIX:epodatelna]
    ADD COLUMN [typ] CHAR(1) NOT NULL AFTER [odchozi];

UPDATE [:PREFIX:epodatelna] SET [typ] = if([isds_id] IS NOT NULL, 'I', 'E');


