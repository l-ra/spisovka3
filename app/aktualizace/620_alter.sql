--  Tento soubor je sloučením více dílčích změn do jednoho souboru;

UPDATE `{tbls3}workflow` SET `aktivni` = 0 WHERE `stav_osoby` >= 100;

------------------------;

DROP TABLE `{tbls3}zprava_osoba`;
DROP TABLE `{tbls3}zprava`;

------------------------;

ALTER TABLE `{tbls3}workflow`
  DROP COLUMN spis_id;

------------------------;

UPDATE `{tbls3}user_resource` SET `name` = 'Epodatelna - zobrazení přílohy' WHERE `name` = 'Epodatelna - zobrazeni prilohy';
UPDATE `{tbls3}user_resource` SET `name` = 'Přílohy' WHERE `name` = 'Prilohy';
UPDATE `{tbls3}user_resource` SET `name` = 'Spisovka - spojení dokumentů' WHERE `name` = 'Spisovka - spojeni dokumentu';
UPDATE `{tbls3}user_resource` SET `name` = 'Vyhledávání' WHERE `name` = 'Vyhledavani';
UPDATE `{tbls3}user_resource` SET `name` = 'Spisovna - vyhledávání' WHERE `name` = 'Spisovna - vyhledavani';
UPDATE `{tbls3}user_resource` SET `name` = 'Spisovna - zápůjčky' WHERE `name` = 'Spisovna - zapujcky';

------------------------;

ALTER TABLE `{tbls3}log_spis`
  DROP FOREIGN KEY `fk_log_spis_spis1`;

ALTER TABLE `{tbls3}log_spis`
  ADD CONSTRAINT `fk_log_spis_spis1` FOREIGN KEY (`spis_id`) REFERENCES `{tbls3}spis` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

------------------------;
  
ALTER TABLE `{tbls3}dokument`
  CHANGE COLUMN `datum_spousteci_udalosti` `datum_spousteci_udalosti` date DEFAULT NULL;
  
UPDATE `{tbls3}dokument` SET datum_spousteci_udalosti = datum_spousteci_udalosti - INTERVAL 1 DAY WHERE datum_spousteci_udalosti LIKE '%-01-01';

------------------------;

ALTER TABLE `{tbls3}dokument_odeslani`
  ADD COLUMN poznamka VARCHAR(200) DEFAULT NULL;
