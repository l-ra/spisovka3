ALTER TABLE [:PREFIX:epodatelna]
    ADD COLUMN [user_id] int(10) unsigned DEFAULT NULL AFTER [file_id],
    ADD CONSTRAINT [fk_epodatelna_user] FOREIGN KEY ([user_id]) REFERENCES [:PREFIX:user] ([id]) ON DELETE SET NULL ON UPDATE NO ACTION;

UPDATE [:PREFIX:epodatelna] set [user_id] = [prijal_kdo];

ALTER TABLE [:PREFIX:epodatelna] DROP COLUMN [prijal_kdo];