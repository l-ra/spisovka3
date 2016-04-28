
ALTER TABLE [:PREFIX:epodatelna]
    ADD COLUMN [odchozi] BIT NOT NULL DEFAULT 0 AFTER [file_id];

UPDATE [:PREFIX:epodatelna] SET [odchozi] = [epodatelna_typ];

ALTER TABLE [:PREFIX:epodatelna] DROP COLUMN [epodatelna_typ];
