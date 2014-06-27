-- -----------------------------------------------------------------------------
-- revision 174
--
-- Nahradte rucne {tbls3} za odpovidajici hodnotu - prefix nebo nic
-- -----------------------------------------------------------------------------;

ALTER TABLE `{tbls3}dokument` ADD `cislo_doporuceneho_dopisu` VARCHAR( 150 ) NULL;
ALTER TABLE `{tbls3}dokument_historie` ADD `cislo_doporuceneho_dopisu` VARCHAR( 150 ) NULL;