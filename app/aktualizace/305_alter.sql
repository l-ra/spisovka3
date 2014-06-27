-- -----------------------------------------------------------------------------
-- revision 305
--
-- Nahradte rucne {tbls3} za odpovidajici hodnotu - prefix nebo nic
-- -----------------------------------------------------------------------------;

ALTER TABLE `{tbls3}spis` ADD `orgjednotka_id` INT( 10 ) UNSIGNED NULL DEFAULT NULL ,
ADD INDEX ( `orgjednotka_id` );

ALTER TABLE `{tbls3}spis` ADD `orgjednotka_id_predano` INT( 10 ) UNSIGNED NULL DEFAULT NULL ,
ADD INDEX ( `orgjednotka_id_predano` );


ALTER TABLE `{tbls3}spis` ADD FOREIGN KEY ( `orgjednotka_id` ) REFERENCES `{tbls3}orgjednotka` (
`id`) ON DELETE RESTRICT ON UPDATE RESTRICT ;

ALTER TABLE `{tbls3}spis` ADD FOREIGN KEY ( `orgjednotka_id_predano` ) REFERENCES `{tbls3}orgjednotka` (
`id`) ON DELETE RESTRICT ON UPDATE RESTRICT ;


ALTER TABLE `{tbls3}workflow` ADD `spis_id` INT NULL AFTER `dokument_id`, ADD INDEX ( `spis_id` );


CREATE TABLE IF NOT EXISTS `{tbls3}log_spis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `spis_id` int(11) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `typ` tinyint(4) NOT NULL,
  `poznamka` text,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_log_spis_spis1` (`spis_id`),
  KEY `fk_log_spis_user1` (`user_id`)
) ENGINE=InnoDB;

ALTER TABLE `{tbls3}log_spis`
  ADD CONSTRAINT `fk_log_spis_spis1` FOREIGN KEY (`spis_id`) REFERENCES `{tbls3}spis` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_log_spis_user1` FOREIGN KEY (`user_id`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
