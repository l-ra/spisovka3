-- -----------------------------------------------------------------------------
-- revision 309
--
-- Nahradte rucne {tbls3} za odpovidajici hodnotu - prefix nebo nic
-- -----------------------------------------------------------------------------;

ALTER TABLE `{tbls3}dokument` CHANGE `popis` `popis` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `{tbls3}dokument_historie` CHANGE `popis` `popis` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;