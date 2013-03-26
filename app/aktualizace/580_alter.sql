------------------------;

ALTER TABLE `{tbls3}dokument_historie`
  DROP FOREIGN KEY `fk_dokument_historie_dokument1`;
ALTER TABLE `{tbls3}dokument_historie`
  ADD CONSTRAINT `fk_dokument_historie_dokument1` FOREIGN KEY (`dokument_id`) REFERENCES `{tbls3}dokument` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}dokument_odeslani`
  DROP FOREIGN KEY `fk_dokument_odeslani_dokument1`;
ALTER TABLE `{tbls3}dokument_odeslani`
  ADD CONSTRAINT `fk_dokument_odeslani_dokument1` FOREIGN KEY (`dokument_id`) REFERENCES `{tbls3}dokument` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}dokument_to_file`
  DROP FOREIGN KEY `fk_dokument_to_file_dokument1`;
ALTER TABLE `{tbls3}dokument_to_file`
  ADD CONSTRAINT `fk_dokument_to_file_dokument1` FOREIGN KEY (`dokument_id`) REFERENCES `{tbls3}dokument` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;
  
ALTER TABLE `{tbls3}dokument_to_spis`
  DROP FOREIGN KEY `fk_dokument_to_spis_dokument1`;
ALTER TABLE `{tbls3}dokument_to_spis`
  ADD CONSTRAINT `fk_dokument_to_spis_dokument1` FOREIGN KEY (`dokument_id`) REFERENCES `{tbls3}dokument` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}dokument_to_subjekt`
  DROP FOREIGN KEY `fk_dokument_to_subjekt_dokument1`;
ALTER TABLE `{tbls3}dokument_to_subjekt`
  ADD CONSTRAINT `fk_dokument_to_subjekt_dokument1` FOREIGN KEY (`dokument_id`) REFERENCES `{tbls3}dokument` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}epodatelna`
  DROP FOREIGN KEY `fk_epodatelna_dokument1`;
ALTER TABLE `{tbls3}epodatelna`
  ADD CONSTRAINT `fk_epodatelna_dokument1` FOREIGN KEY (`dokument_id`) REFERENCES `{tbls3}dokument` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}log_dokument`
  DROP FOREIGN KEY `fk_log_dokument_dokument1`;
ALTER TABLE `{tbls3}log_dokument`
  ADD CONSTRAINT `fk_log_dokument_dokument1` FOREIGN KEY (`dokument_id`) REFERENCES `{tbls3}dokument` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}souvisejici_dokument`
  DROP FOREIGN KEY `fk_souvisejici_dokument_dokument1`,
  DROP FOREIGN KEY `fk_souvisejici_dokument_dokument2`;
ALTER TABLE `{tbls3}souvisejici_dokument`
  ADD CONSTRAINT `fk_souvisejici_dokument_dokument1` FOREIGN KEY (`dokument_id`) REFERENCES `{tbls3}dokument` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_souvisejici_dokument_dokument2` FOREIGN KEY (`spojit_s_id`) REFERENCES `{tbls3}dokument` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}workflow`
  DROP FOREIGN KEY `fk_workflow_dokument1`;  
ALTER TABLE `{tbls3}workflow`
  ADD CONSTRAINT `fk_workflow_dokument1` FOREIGN KEY (`dokument_id`) REFERENCES `{tbls3}dokument` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;
