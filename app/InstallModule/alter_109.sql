-- -----------------------------------------------------------------------------
-- revision 109
--
-- Nahradte rucne {tbls3} za odpovidajici hodnotu - prefix nebo nic
-- -----------------------------------------------------------------------------;

ALTER TABLE `{tbls3}epodatelna` ADD `identifikator` TEXT NULL AFTER `isds_signature`;