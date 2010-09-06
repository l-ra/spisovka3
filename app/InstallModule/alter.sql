-- -----------------------------------------------------------------------------
-- Nahradte rucne {tbls3} za odpovidajici hodnotu - prefix nebo nic
-- -----------------------------------------------------------------------------

ALTER TABLE `{tbls3}cislo_jednaci` CHANGE `cjednaci_id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
CHANGE `podaci_denik` `podaci_denik` VARCHAR( 80 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'default',
CHANGE `urad_zkratka` `urad_zkratka` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';

ALTER TABLE `{tbls3}cislo_jednaci` DROP INDEX `search`;

-- ------------------

ALTER TABLE `{tbls3}dokument`
CHANGE `dokument_id` `id` INT(11) NOT NULL,
CHANGE `dokument_version` `version` INT(11) NOT NULL,
CHANGE `popis` `popis` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `cislo_jednaci` `cislo_jednaci` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `cislo_jednaci_odesilatele` `cislo_jednaci_odesilatele` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `podaci_denik` `podaci_denik` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `typ_dokumentu` `typ_dokumentu_id` INT(11) NULL DEFAULT NULL,
CHANGE `spisovy_plan` `spisovy_plan` VARCHAR( 45 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `spisovy_znak` `spisovy_znak_id` INT(11) NULL DEFAULT NULL,
CHANGE `skartacni_znak` `skartacni_znak` ENUM('A','S','V') CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
CHANGE `poznamka` `poznamka` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `zmocneni` `zmocneni_id` INT(11) NULL DEFAULT NULL,
CHANGE `zpusob_doruceni` `zpusob_doruceni_id` INT(11) NULL DEFAULT NULL,
CHANGE `zpusob_vyrizeni` `zpusob_vyrizeni_id` INT(11) NULL DEFAULT NULL,
CHANGE `ulozeni_dokumentu` `ulozeni_dokumentu` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `poznamka_vyrizeni` `poznamka_vyrizeni` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `spousteci_udalost` `spousteci_udalost` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';

-- --------------------

ALTER TABLE `{tbls3}dokument_odeslani` CHANGE `dokument_odeslani_id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
CHANGE `zprava` `zprava` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';

ALTER TABLE `{tbls3}dokument_odeslani` ADD INDEX `dokument` ( `dokument_id` , `dokument_version` );
ALTER TABLE `{tbls3}dokument_odeslani` ADD INDEX `subjekt` ( `subjekt_id` , `subjekt_version` );

-- --------------------

ALTER TABLE `{tbls3}dokument_to_file` ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `{tbls3}dokument_to_file` ADD INDEX `dokument` ( `dokument_id` , `dokument_version` );
ALTER TABLE `{tbls3}dokument_to_file` ADD INDEX `file` ( `file_id` , `file_version` );
ALTER TABLE `{tbls3}dokument_to_file` CHANGE `dokument_version` `dokument_version` INT( 11 ) NULL DEFAULT NULL ,
CHANGE `file_version` `file_version` INT( 11 ) NULL DEFAULT NULL;

-- --------------------

ALTER TABLE `{tbls3}dokument_to_spis` ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `{tbls3}dokument_to_spis` ADD INDEX `dokument` ( `dokument_id` , `dokument_version` );
ALTER TABLE `{tbls3}dokument_to_spis` ADD INDEX `spis` ( `spis_id` );

-- --------------------

ALTER TABLE `{tbls3}dokument_to_subjekt` ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `{tbls3}dokument_to_subjekt` ADD INDEX `dokument` ( `dokument_id` , `dokument_version` );
ALTER TABLE `{tbls3}dokument_to_subjekt` ADD INDEX `subjekt` ( `subjekt_id` , `subjekt_version` );

-- --------------------

ALTER TABLE `{tbls3}dokument_typ` CHANGE `dokument_typ_id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
CHANGE `popis` `popis` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';

-- --------------------

ALTER TABLE `{tbls3}epodatelna` CHANGE `epodatelna_id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
CHANGE `predmet` `predmet` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `popis` `popis` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `odesilatel` `odesilatel` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `adresat` `adresat` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
CHANGE `evidence` `evidence` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';

ALTER TABLE `{tbls3}epodatelna` DROP INDEX `hledat`;

-- --------------------

ALTER TABLE `{tbls3}file` CHANGE `file_id` `id` INT( 11 ) NOT NULL ,
CHANGE `file_version` `version` TINYINT( 4 ) NOT NULL ,
CHANGE `nazev` `nazev` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'jmeno souboru nebo nazev',
CHANGE `popis` `popis` VARCHAR( 45 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `mime_type` `mime_type` VARCHAR( 60 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT 'mime typ souboru',
CHANGE `real_name` `real_name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'skutečné jmeno souboru file.ext',
CHANGE `real_path` `real_path` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'realna cesta k souboru ';

-- --------------------

ALTER TABLE `{tbls3}log_access` CHANGE `logaccess_id` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
CHANGE `ip` `ip` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `user_agent` `user_agent` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';

-- --------------------

ALTER TABLE `{tbls3}log_dokument` CHANGE `logdokument_id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
CHANGE `poznamka` `poznamka` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';

ALTER TABLE `{tbls3}log_dokument` ADD INDEX `dokument` ( `dokument_id` );

-- --------------------

ALTER TABLE `{tbls3}orgjednotka` CHANGE `orgjednotka_id` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
CHANGE `zkraceny_nazev` `zkraceny_nazev` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `note` `note` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';

-- --------------------

ALTER TABLE `{tbls3}osoba` CHANGE `osoba_id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
CHANGE `jmeno` `jmeno` VARCHAR( 150 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `titul_pred` `titul_pred` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `titul_za` `titul_za` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `email` `email` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `telefon` `telefon` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `pozice` `pozice` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';

ALTER TABLE `{tbls3}osoba` DROP INDEX `prijmeni`;

-- --------------------

ALTER TABLE `{tbls3}osoba_to_user` ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `{tbls3}osoba_to_user` DROP INDEX `osoba_id`;
ALTER TABLE `{tbls3}osoba_to_user` ADD INDEX `osoba` ( `osoba_id` );
ALTER TABLE `{tbls3}osoba_to_user` ADD INDEX `user` ( `user_id` );

-- --------------------

ALTER TABLE `{tbls3}sestava` CHANGE `sestava_id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
CHANGE `popis` `popis` VARCHAR( 150 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';

-- --------------------

ALTER TABLE `{tbls3}souvisejici_dokument` DROP PRIMARY KEY;
ALTER TABLE `{tbls3}souvisejici_dokument` ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `{tbls3}souvisejici_dokument` ADD INDEX `dokument` ( `dokument_id` );
ALTER TABLE `{tbls3}souvisejici_dokument` ADD INDEX `spojit` ( `spojit_s` );

-- --------------------

ALTER TABLE `{tbls3}spis` CHANGE `spis_id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
CHANGE `spousteci_udalost` `spousteci_udalost` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';

-- --------------------

ALTER TABLE `{tbls3}spisovy_znak` CHANGE `spisznak_id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
CHANGE `popis` `popis` VARCHAR( 200 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

-- --------------------

ALTER TABLE `{tbls3}spousteci_udalost` CHANGE `spousteci_udalost_id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
CHANGE `poznamka` `poznamka` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';

-- --------------------

ALTER TABLE `{tbls3}subjekt` CHANGE `subjekt_id` `id` INT( 11 ) NOT NULL ,
CHANGE `subjekt_version` `version` INT( 11 ) NOT NULL DEFAULT '1';
ALTER TABLE `{tbls3}subjekt` CHANGE `ic` `ic` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `dic` `dic` VARCHAR( 12 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `nazev_subjektu` `nazev_subjektu` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `jmeno` `jmeno` VARCHAR( 24 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `prijmeni` `prijmeni` VARCHAR( 35 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `prostredni_jmeno` `prostredni_jmeno` VARCHAR( 35 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';
ALTER TABLE `{tbls3}subjekt` CHANGE `titul_pred` `titul_pred` VARCHAR( 35 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `titul_za` `titul_za` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `rodne_jmeno` `rodne_jmeno` VARCHAR( 35 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `misto_narozeni` `misto_narozeni` VARCHAR( 48 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `okres_narozeni` `okres_narozeni` VARCHAR( 48 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `stat_narozeni` `stat_narozeni` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';
ALTER TABLE `{tbls3}subjekt` CHANGE `adresa_mesto` `adresa_mesto` VARCHAR( 48 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `adresa_ulice` `adresa_ulice` VARCHAR( 48 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `adresa_cp` `adresa_cp` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `adresa_co` `adresa_co` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `adresa_psc` `adresa_psc` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `adresa_stat` `adresa_stat` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';
ALTER TABLE `{tbls3}subjekt` CHANGE `narodnost` `narodnost` VARCHAR( 80 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `email` `email` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `telefon` `telefon` VARCHAR( 150 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `id_isds` `id_isds` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
CHANGE `poznamka` `poznamka` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';

ALTER TABLE `{tbls3}subjekt` DROP INDEX `jmeno`;
ALTER TABLE `{tbls3}subjekt` DROP INDEX `hledat`;

-- --------------------

ALTER TABLE `{tbls3}user` CHANGE `user_id` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
CHANGE `last_ip` `last_ip` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';

-- --------------------

ALTER TABLE `{tbls3}user_acl` CHANGE `acl_id` `id` INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `{tbls3}user_acl` ADD INDEX `role` ( `role_id` );
ALTER TABLE `{tbls3}user_acl` ADD INDEX `rule` ( `rule_id` );

-- --------------------

ALTER TABLE `{tbls3}user_resource` CHANGE `resource_id` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
CHANGE `note` `note` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';

-- --------------------

ALTER TABLE `{tbls3}user_role` CHANGE `role_id` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
CHANGE `note` `note` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';

-- --------------------

ALTER TABLE `{tbls3}user_rule` CHANGE `rule_id` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
CHANGE `note` `note` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';


-- --------------------

ALTER TABLE `{tbls3}user_to_role` DROP INDEX `user_id`;
ALTER TABLE `{tbls3}user_to_role` ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE `{tbls3}user_to_role` ADD INDEX `user` ( `user_id` );
ALTER TABLE `{tbls3}user_to_role` ADD INDEX `role` ( `role_id` );

-- --------------------

ALTER TABLE `{tbls3}workflow` CHANGE `workflow_id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
CHANGE `poznamka` `poznamka` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '';
ALTER TABLE `{tbls3}workflow` DROP INDEX `dokument`;
ALTER TABLE `{tbls3}workflow` DROP INDEX `osoba`;
ALTER TABLE `{tbls3}workflow` ADD INDEX `dokument` ( `dokument_id` , `dokument_version` );
ALTER TABLE `{tbls3}workflow` ADD INDEX `prideleno` ( `prideleno` );

-- --------------------

ALTER TABLE `{tbls3}zmocneni` CHANGE `zmocneni_id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT;

-- --------------------

RENAME TABLE `{tbls3}zpvyrizeni` TO `{tbls3}zpusob_vyrizeni` ;
ALTER TABLE `{tbls3}zpusob_vyrizeni` CHANGE `zpvyrizeni_id` `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT;

-- --------------------