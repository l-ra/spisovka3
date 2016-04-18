ALTER TABLE [{tbls3}epodatelna]
    ADD COLUMN [user_id] int(10) unsigned DEFAULT NULL AFTER [file_id],
    ADD CONSTRAINT `fk_epodatelna_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;

UPDATE [{tbls3}epodatelna] set [user_id] = [prijal_kdo];

ALTER TABLE [{tbls3}epodatelna] DROP COLUMN [prijal_kdo];