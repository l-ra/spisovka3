-- -----------------------------------------------------------------------------
-- revision 321
--
-- Nahradte rucne {tbls3} za odpovidajici hodnotu - prefix nebo nic
-- -----------------------------------------------------------------------------;

ALTER TABLE `{tbls3}spisovy_znak` ADD `selected` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1';