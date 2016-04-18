
ALTER TABLE [{tbls3}epodatelna]
    ADD COLUMN [odchozi] BIT NOT NULL DEFAULT 0 AFTER [file_id];

UPDATE [{tbls3}epodatelna] SET [odchozi] = [epodatelna_typ];

ALTER TABLE [{tbls3}epodatelna] DROP COLUMN [epodatelna_typ];
