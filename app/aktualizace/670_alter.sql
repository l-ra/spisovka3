------------------------;

ALTER TABLE `{tbls3}log_spis`
  DROP FOREIGN KEY `fk_log_spis_spis1`;

ALTER TABLE `{tbls3}log_spis`
  ADD CONSTRAINT `fk_log_spis_spis1` FOREIGN KEY (`spis_id`) REFERENCES `{tbls3}spis` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;
