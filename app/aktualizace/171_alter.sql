-- -----------------------------------------------------------------------------
-- revision 170
--
-- Nahradte rucne {tbls3} za odpovidajici hodnotu - prefix nebo nic
-- -----------------------------------------------------------------------------;

ALTER TABLE `{tbls3}spis` ADD `spisovy_znak_id` INT UNSIGNED NULL DEFAULT NULL AFTER `spousteci_udalost_id` ,
ADD INDEX ( `spisovy_znak_id` );
ALTER TABLE `{tbls3}spis` ADD FOREIGN KEY ( `spisovy_znak_id` ) REFERENCES `{tbls3}spisovy_znak` (
`id` 
) ON DELETE NO ACTION ON UPDATE NO ACTION ;
ALTER TABLE `spis` CHANGE `spisovy_znak` `spisovy_znak` VARCHAR( 45 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
CHANGE `spisovy_znak_plneurceny` `spisovy_znak_plneurceny` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL