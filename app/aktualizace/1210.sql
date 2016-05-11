ALTER TABLE [:PREFIX:epodatelna] DROP FOREIGN KEY [fk_epodatelna_subjekt1];
ALTER TABLE [:PREFIX:epodatelna] CHANGE [odesilatel_id] [subjekt_id] int DEFAULT NULL,
  ADD CONSTRAINT `fk_epodatelna_subjekt1` FOREIGN KEY ([subjekt_id]) REFERENCES [:PREFIX:subjekt] ([id]) ON DELETE SET NULL ON UPDATE NO ACTION;