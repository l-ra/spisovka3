-- -----------------------------------------------------------------------------
-- aktualizace 3.0.3 na 3.2.0
--
-- Nahradte rucne {tbls3} za odpovidajici hodnotu - prefix nebo nic
--
-- -----------------------------------------------------------------------------;

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;
SET @OLD_SQL_MODE=@@SQL_MODE;
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='';

ALTER TABLE `{tbls3}user` ADD COLUMN `local` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'  AFTER `last_ip` , CHANGE COLUMN `last_ip` `last_ip` VARCHAR(15) NULL DEFAULT NULL  ;

ALTER TABLE `{tbls3}orgjednotka` ADD COLUMN `parent_id` INT(10) UNSIGNED NULL DEFAULT NULL  AFTER `id` , 
ADD COLUMN `user_created` INT(10) UNSIGNED NOT NULL  AFTER `date_created` , 
ADD COLUMN `user_modified` INT(10) UNSIGNED NOT NULL  AFTER `date_modified` , 
ADD COLUMN `sekvence` VARCHAR(300) NULL DEFAULT NULL  AFTER `user_modified` , 
ADD COLUMN `sekvence_string` VARCHAR(1000) NULL DEFAULT NULL  AFTER `sekvence` , 
ADD COLUMN `uroven` TINYINT(3) UNSIGNED NULL DEFAULT NULL  AFTER `sekvence_string` ,
  ADD CONSTRAINT `fk_orgjednotka_user1`
  FOREIGN KEY (`user_created` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_orgjednotka_user2`
  FOREIGN KEY (`user_modified` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, ADD INDEX `fk_orgjednotka_user1` (`user_created` ASC) 
, ADD INDEX `fk_orgjednotka_user2` (`user_modified` ASC) 
, ADD INDEX `parent_id` (`parent_id` ASC) ;

UPDATE {tbls3}orgjednotka SET parent_id = NULL, sekvence = id, sekvence_string = CONCAT(ciselna_rada,'.',id), uroven = 0;

ALTER TABLE `{tbls3}cislo_jednaci` CHANGE COLUMN `orgjednotka_id` `orgjednotka_id` INT(10) UNSIGNED NULL DEFAULT NULL  , CHANGE COLUMN `user_id` `user_id` INT(10) UNSIGNED NULL DEFAULT NULL  ,
  ADD CONSTRAINT `fk_cislo_jednaci_orgjednotka1`
  FOREIGN KEY (`orgjednotka_id` )
  REFERENCES `{tbls3}orgjednotka` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_cislo_jednaci_user1`
  FOREIGN KEY (`user_id` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, ADD INDEX `fk_cislo_jednaci_user1` (`user_id` ASC) 
, ADD INDEX `fk_cislo_jednaci_orgjednotka1` (`orgjednotka_id` ASC)
, DROP INDEX `orgjednotka_id`
, DROP INDEX `user_id`
, DROP INDEX `urad_zkratka` ;


UPDATE `{tbls3}dokument` AS d1 LEFT JOIN `{tbls3}dokument_typ` AS dt ON dt.id = d1.typ_dokumentu_id SET d1.zpusob_doruceni_id = IF(dt.typ>0,dt.typ,NULL);

ALTER TABLE `{tbls3}dokument_typ` 
DROP COLUMN `typ` , ADD COLUMN `podatelna` TINYINT(1) NOT NULL DEFAULT '1'  AFTER `smer` , 
ADD COLUMN `referent` TINYINT(1) NOT NULL DEFAULT '1'  AFTER `podatelna` ;

UPDATE {tbls3}dokument_typ SET referent = smer, podatelna = IF(smer=1,0,1);

CREATE  TABLE IF NOT EXISTS `{tbls3}zpusob_doruceni` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `nazev` VARCHAR(80) NOT NULL ,
  `stav` TINYINT(4) NOT NULL DEFAULT '1' ,
  `fixed` TINYINT(1) NOT NULL DEFAULT '0' ,
  `note` VARCHAR(255) NULL DEFAULT NULL ,
  `epodatelna` TINYINT(1) NULL DEFAULT '0' ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_general_ci;

INSERT INTO `{tbls3}zpusob_doruceni` (`id`, `nazev`, `stav`, `fixed`, `note`, `epodatelna`) VALUES
(1,	'emailem',	1,	1,	NULL,	1),
(2,	'datovou schránkou',	1,	1,	NULL,	1),
(3,	'datovým nosičem',	1,	1,	NULL,	0),
(4,	'faxem',	1,	1,	NULL,	0),
(5,	'v listinné podobě',	1,	1,	NULL,	0);

ALTER TABLE `{tbls3}zpusob_vyrizeni` ENGINE = InnoDB , ADD COLUMN `fixed` TINYINT(1) NOT NULL DEFAULT '0'  AFTER `stav` , ADD COLUMN `note` VARCHAR(200) NULL DEFAULT NULL  AFTER `fixed` ;

CREATE  TABLE IF NOT EXISTS `{tbls3}dokument_historie` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `dokument_id` INT(11) NOT NULL ,
  `dokument_typ_id` INT(11) NOT NULL ,
  `zpusob_doruceni_id` INT(10) UNSIGNED NULL DEFAULT NULL ,
  `cislo_jednaci_id` INT(11) NULL DEFAULT NULL ,
  `zpusob_vyrizeni_id` INT(10) UNSIGNED NULL DEFAULT NULL ,
  `zmocneni_id` INT(11) NULL DEFAULT NULL ,
  `spousteci_udalost_id` INT(11) NULL DEFAULT NULL ,
  `jid` VARCHAR(100) NOT NULL ,
  `nazev` VARCHAR(100) NOT NULL ,
  `popis` TEXT NULL DEFAULT NULL ,
  `cislo_jednaci` VARCHAR(50) NULL DEFAULT NULL ,
  `poradi` SMALLINT(6) NULL DEFAULT '1' ,
  `cislo_jednaci_odesilatele` VARCHAR(50) NULL DEFAULT NULL ,
  `podaci_denik` VARCHAR(45) NULL DEFAULT NULL ,
  `podaci_denik_poradi` INT(11) NULL DEFAULT NULL ,
  `podaci_denik_rok` YEAR NULL DEFAULT NULL ,
  `spisovy_plan` VARCHAR(45) NULL DEFAULT NULL ,
  `spisovy_znak_id` INT(11) NULL DEFAULT NULL ,
  `skartacni_znak` ENUM('A','S','V') NULL DEFAULT NULL ,
  `skartacni_lhuta` INT(11) NULL DEFAULT NULL ,
  `poznamka` TEXT NULL DEFAULT NULL ,
  `lhuta` TINYINT(4) NOT NULL DEFAULT '30' ,
  `epodatelna_id` INT(11) NULL DEFAULT NULL ,
  `stav` TINYINT(4) NOT NULL DEFAULT '1' ,
  `md5_hash` VARCHAR(45) NOT NULL ,
  `date_created` DATETIME NOT NULL ,
  `user_created` INT(10) UNSIGNED NOT NULL ,
  `datum_vzniku` DATETIME NULL ,
  `pocet_listu` INT(11) NULL DEFAULT NULL ,
  `pocet_priloh` INT(11) NULL DEFAULT NULL ,
  `typ_prilohy` VARCHAR(150) NULL DEFAULT NULL ,
  `vyrizeni_pocet_listu` INT(11) NULL DEFAULT NULL ,
  `vyrizeni_pocet_priloh` INT(11) NULL DEFAULT NULL ,
  `vyrizeni_typ_prilohy` VARCHAR(150) NULL DEFAULT NULL ,
  `ulozeni_dokumentu` TEXT NULL DEFAULT NULL ,
  `datum_vyrizeni` DATETIME NULL DEFAULT NULL ,
  `poznamka_vyrizeni` TEXT NULL DEFAULT NULL ,
  `datum_spousteci_udalosti` DATETIME NULL DEFAULT NULL ,
  `cislo_doporuceneho_dopisu` VARCHAR(150) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_dokument_dokument_typ` (`dokument_typ_id` ASC) ,
  INDEX `fk_dokument_cislo_jednaci1` (`cislo_jednaci_id` ASC) ,
  INDEX `fk_dokument_zpusob_vyrizeni1` (`zpusob_vyrizeni_id` ASC) ,
  INDEX `fk_dokument_zmocneni1` (`zmocneni_id` ASC) ,
  INDEX `fk_dokument_spousteci_udalost1` (`spousteci_udalost_id` ASC) ,
  INDEX `fk_dokument_user1` (`user_created` ASC) ,
  INDEX `fk_dokument_zpusob_doruceni1` (`zpusob_doruceni_id` ASC) ,
  INDEX `fk_dokument_historie_dokument1` (`dokument_id` ASC) ,
  CONSTRAINT `fk_dokument_cislo_jednaci10`
    FOREIGN KEY (`cislo_jednaci_id` )
    REFERENCES `{tbls3}cislo_jednaci` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_dokument_typ0`
    FOREIGN KEY (`dokument_typ_id` )
    REFERENCES `{tbls3}dokument_typ` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_historie_dokument1`
    FOREIGN KEY (`dokument_id` )
    REFERENCES `{tbls3}dokument` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_spousteci_udalost10`
    FOREIGN KEY (`spousteci_udalost_id` )
    REFERENCES `{tbls3}spousteci_udalost` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_user10`
    FOREIGN KEY (`user_created` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_zmocneni10`
    FOREIGN KEY (`zmocneni_id` )
    REFERENCES `{tbls3}zmocneni` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_zpusob_doruceni10`
    FOREIGN KEY (`zpusob_doruceni_id` )
    REFERENCES `{tbls3}zpusob_doruceni` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_zpusob_vyrizeni10`
    FOREIGN KEY (`zpusob_vyrizeni_id` )
    REFERENCES `{tbls3}zpusob_vyrizeni` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_general_ci;

INSERT INTO `{tbls3}dokument_historie` (dokument_id,jid,nazev,popis,cislo_jednaci_id,cislo_jednaci,poradi,cislo_jednaci_odesilatele,podaci_denik,podaci_denik_poradi,podaci_denik_rok,dokument_typ_id,spisovy_plan,spisovy_znak_id,skartacni_znak,skartacni_lhuta,poznamka,lhuta,epodatelna_id,stav,md5_hash,date_created,user_created,datum_vzniku,pocet_listu,pocet_priloh,zpusob_doruceni_id,zpusob_vyrizeni_id,vyrizeni_pocet_listu,vyrizeni_pocet_priloh,ulozeni_dokumentu,datum_vyrizeni,poznamka_vyrizeni,datum_spousteci_udalosti)
SELECT d2.id,d2.jid,d2.nazev,d2.popis,d2.cislojednaci_id,d2.cislo_jednaci,d2.poradi,d2.cislo_jednaci_odesilatele,d2.podaci_denik,d2.podaci_denik_poradi,d2.podaci_denik_rok,d2.typ_dokumentu_id,d2.spisovy_plan,d2.spisovy_znak_id,d2.skartacni_znak,d2.skartacni_lhuta,d2.poznamka,d2.lhuta,d2.epodatelna_id,d2.stav,d2.md5_hash,d2.date_created,d2.user_created,d2.datum_vzniku,d2.pocet_listu,d2.pocet_priloh,d2.zpusob_doruceni_id,d2.zpusob_vyrizeni_id,d2.vyrizeni_pocet_listu,d2.vyrizeni_pocet_priloh,d2.ulozeni_dokumentu,d2.datum_vyrizeni,d2.poznamka_vyrizeni,d2.datum_spousteci_udalosti FROM `{tbls3}dokument` AS d2 WHERE d2.stav >= 100;

DELETE FROM `{tbls3}dokument` WHERE stav >= 100;

ALTER TABLE `{tbls3}dokument` CHANGE COLUMN `zpusob_doruceni_id` `zpusob_doruceni_id` INT(10) UNSIGNED NULL DEFAULT NULL  AFTER `dokument_typ_id` , 
CHANGE COLUMN `zpusob_vyrizeni_id` `zpusob_vyrizeni_id` INT(10) UNSIGNED NULL DEFAULT NULL, 
CHANGE COLUMN `typ_dokumentu_id` `dokument_typ_id` INT(11) NOT NULL, 
CHANGE COLUMN `cislojednaci_id` `cislo_jednaci_id` INT(11) NULL DEFAULT NULL, 
CHANGE COLUMN `zmocneni_id` `zmocneni_id` INT(11) NULL DEFAULT NULL , 
CHANGE COLUMN `id` `id` INT(11) NOT NULL AUTO_INCREMENT  , 
CHANGE COLUMN `popis` `popis` TEXT NULL DEFAULT NULL  , 
CHANGE COLUMN `user_created` `user_created` INT(10) UNSIGNED NOT NULL  , 
CHANGE COLUMN `user_modified` `user_modified` INT(10) UNSIGNED NULL DEFAULT NULL  ,
CHANGE COLUMN `datum_vzniku` `datum_vzniku` datetime NULL,
ADD COLUMN `spousteci_udalost_id` INT(11) NULL DEFAULT NULL  AFTER `zmocneni_id` , 
ADD COLUMN `typ_prilohy` VARCHAR(150) NULL DEFAULT NULL  AFTER `pocet_priloh` , 
ADD COLUMN `vyrizeni_typ_prilohy` VARCHAR(150) NULL DEFAULT NULL  AFTER `vyrizeni_pocet_priloh` , 
ADD COLUMN `cislo_doporuceneho_dopisu` VARCHAR(150) NULL DEFAULT NULL  AFTER `datum_spousteci_udalosti` , 
  ADD CONSTRAINT `fk_dokument_cislo_jednaci1`
  FOREIGN KEY (`cislo_jednaci_id` )
  REFERENCES `{tbls3}cislo_jednaci` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_dokument_dokument_typ`
  FOREIGN KEY (`dokument_typ_id` )
  REFERENCES `{tbls3}dokument_typ` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_dokument_spousteci_udalost1`
  FOREIGN KEY (`spousteci_udalost_id` )
  REFERENCES `{tbls3}spousteci_udalost` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_dokument_user1`
  FOREIGN KEY (`user_created` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_dokument_user2`
  FOREIGN KEY (`user_modified` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_dokument_zpusob_doruceni1`
  FOREIGN KEY (`zpusob_doruceni_id` )
  REFERENCES `{tbls3}zpusob_doruceni` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_dokument_zpusob_vyrizeni1`
  FOREIGN KEY (`zpusob_vyrizeni_id` )
  REFERENCES `{tbls3}zpusob_vyrizeni` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, DROP PRIMARY KEY 
, ADD PRIMARY KEY (`id`) 
, ADD INDEX `fk_dokument_dokument_typ` (`dokument_typ_id` ASC) 
, ADD INDEX `fk_dokument_cislo_jednaci1` (`cislo_jednaci_id` ASC) 
, ADD INDEX `fk_dokument_zpusob_vyrizeni1` (`zpusob_vyrizeni_id` ASC) 
, ADD INDEX `fk_dokument_zmocneni1` (`zmocneni_id` ASC) 
, ADD INDEX `fk_dokument_spousteci_udalost1` (`spousteci_udalost_id` ASC) 
, ADD INDEX `fk_dokument_user1` (`user_created` ASC) 
, ADD INDEX `fk_dokument_user2` (`user_modified` ASC) 
, ADD INDEX `fk_dokument_zpusob_doruceni1` (`zpusob_doruceni_id` ASC) 
, DROP INDEX `typ_dokumentu_id`
, DROP INDEX `cislojednaci_id`
, DROP INDEX `zpusob_vyrizeni_id`
, DROP INDEX `zmocneni_id`
, DROP INDEX `zpusob_doruceni_id` ;

UPDATE `{tbls3}dokument` AS d1 LEFT JOIN `{tbls3}spousteci_udalost` AS su ON su.nazev = d1.spousteci_udalost SET d1.spousteci_udalost_id = IF(su.id,su.id,NULL);

CREATE  TABLE IF NOT EXISTS `{tbls3}subjekt_historie` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `subjekt_id` INT(11) NOT NULL ,
  `stav` TINYINT(4) NOT NULL DEFAULT '1' ,
  `type` VARCHAR(15) NOT NULL ,
  `ic` VARCHAR(8) NULL DEFAULT '' ,
  `dic` VARCHAR(12) NULL DEFAULT '' ,
  `nazev_subjektu` VARCHAR(255) NULL DEFAULT '' ,
  `jmeno` VARCHAR(24) NULL DEFAULT '' ,
  `prijmeni` VARCHAR(35) NULL DEFAULT '' ,
  `prostredni_jmeno` VARCHAR(35) NULL DEFAULT '' ,
  `titul_pred` VARCHAR(35) NULL DEFAULT '' ,
  `titul_za` VARCHAR(10) NULL DEFAULT '' ,
  `rodne_jmeno` VARCHAR(35) NULL DEFAULT '' ,
  `datum_narozeni` DATE NULL DEFAULT NULL ,
  `misto_narozeni` VARCHAR(48) NULL DEFAULT '' ,
  `okres_narozeni` VARCHAR(48) NULL DEFAULT '' ,
  `stat_narozeni` VARCHAR(3) NULL DEFAULT '' ,
  `adresa_mesto` VARCHAR(48) NULL DEFAULT '' ,
  `adresa_ulice` VARCHAR(48) NULL DEFAULT '' ,
  `adresa_cp` VARCHAR(10) NULL DEFAULT '' ,
  `adresa_co` VARCHAR(10) NULL DEFAULT '' ,
  `adresa_psc` VARCHAR(10) NULL DEFAULT '' ,
  `adresa_stat` VARCHAR(3) NULL DEFAULT '' ,
  `narodnost` VARCHAR(80) NULL DEFAULT '' ,
  `email` VARCHAR(250) NULL DEFAULT '' ,
  `telefon` VARCHAR(150) NULL DEFAULT '' ,
  `id_isds` VARCHAR(50) NULL DEFAULT '' ,
  `poznamka` TEXT NULL DEFAULT NULL ,
  `date_created` DATETIME NULL DEFAULT NULL ,
  `user_created` INT(10) UNSIGNED NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_subjekt_user1` (`user_created` ASC) ,
  INDEX `fk_subjekt_historie_subjekt1` (`subjekt_id` ASC) ,
  CONSTRAINT `fk_subjekt_historie_subjekt1`
    FOREIGN KEY (`subjekt_id` )
    REFERENCES `{tbls3}subjekt` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_subjekt_user10`
    FOREIGN KEY (`user_created` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_general_ci;

INSERT INTO `{tbls3}subjekt_historie` (subjekt_id,stav,`type`,ic,dic,nazev_subjektu,jmeno,prijmeni,prostredni_jmeno,titul_pred,titul_za,rodne_jmeno,datum_narozeni,misto_narozeni,okres_narozeni,stat_narozeni,adresa_mesto,adresa_ulice,adresa_cp,adresa_co,adresa_psc,adresa_stat,narodnost,email,telefon,id_isds,poznamka,date_created,user_created)
SELECT s2.id,s2.stav,s2.type,s2.ic,s2.dic,s2.nazev_subjektu,s2.jmeno,s2.prijmeni,s2.prostredni_jmeno,s2.titul_pred,s2.titul_za,s2.rodne_jmeno,s2.datum_narozeni,s2.misto_narozeni,s2.okres_narozeni,s2.stat_narozeni,s2.adresa_mesto,s2.adresa_ulice,s2.adresa_cp,s2.adresa_co,s2.adresa_psc,s2.adresa_stat,s2.narodnost,s2.email,s2.telefon,s2.id_isds,s2.poznamka,s2.date_created,IFNULL(s2.user_added,1) FROM `{tbls3}subjekt` AS s2 WHERE s2.stav >= 100;

DELETE FROM `{tbls3}subjekt` WHERE stav >= 100;

ALTER TABLE `{tbls3}subjekt` DROP COLUMN `version` , 
CHANGE COLUMN `user_added` `user_created` INT(10) UNSIGNED NOT NULL AFTER `date_created` , 
ADD COLUMN `user_modified` INT(10) UNSIGNED NULL DEFAULT NULL  AFTER `date_modified` , 
CHANGE COLUMN `id` `id` INT(11) NOT NULL AUTO_INCREMENT  , 
  ADD CONSTRAINT `fk_subjekt_user1`
  FOREIGN KEY (`user_created` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_subjekt_user2`
  FOREIGN KEY (`user_modified` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, DROP PRIMARY KEY 
, ADD PRIMARY KEY (`id`) 
, ADD INDEX `fk_subjekt_user1` (`user_created` ASC) 
, ADD INDEX `fk_subjekt_user2` (`user_modified` ASC) ;

CREATE  TABLE IF NOT EXISTS `{tbls3}file_historie` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `file_id` INT(11) NOT NULL ,
  `stav` TINYINT(4) NOT NULL DEFAULT '1' ,
  `typ` TINYINT(4) NOT NULL DEFAULT '1' COMMENT 'typ prilohy. Defaultne: (1)main, (2)enclosure, (3)signature, (4)meta, (5)source' ,
  `nazev` VARCHAR(255) NOT NULL COMMENT 'jmeno souboru nebo nazev' ,
  `popis` VARCHAR(45) NULL DEFAULT NULL ,
  `mime_type` VARCHAR(60) NULL DEFAULT NULL COMMENT 'mime typ souboru' ,
  `real_name` VARCHAR(255) NOT NULL COMMENT 'skutečné jmeno souboru file.ext' ,
  `real_path` VARCHAR(255) NOT NULL COMMENT 'realna cesta k souboru ' ,
  `real_type` VARCHAR(45) NOT NULL DEFAULT 'FILE' COMMENT 'typ fyzickeho mista. Default FILE - lokalni fyzicke misto' ,
  `date_created` DATETIME NULL DEFAULT NULL ,
  `user_created` INT(10) UNSIGNED NOT NULL ,
  `guid` VARCHAR(45) NOT NULL COMMENT 'jednoznacny identifikator' ,
  `md5_hash` VARCHAR(45) NOT NULL COMMENT 'otisk souboru pro overeni pravosti' ,
  `size` INT(11) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_file_user1` (`user_created` ASC) ,
  INDEX `fk_file_historie_file1` (`file_id` ASC) ,
  CONSTRAINT `fk_file_historie_file1`
    FOREIGN KEY (`file_id` )
    REFERENCES `{tbls3}file` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_file_user10`
    FOREIGN KEY (`user_created` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_general_ci;

INSERT INTO `{tbls3}file_historie` 
(file_id,stav,typ,nazev,popis,mime_type,real_name,real_path,real_type,date_created,user_created,guid,md5_hash,size) 
SELECT f1.id,f1.stav,f1.typ,f1.nazev,f1.popis,f1.mime_type,f1.real_name,f1.real_path,f1.real_type,f1.date_created,f1.user_created,f1.guid,f1.md5_hash,f1.size FROM `{tbls3}file` AS f1 LEFT JOIN (SELECT id, MAX(VERSION) AS VERSION FROM `{tbls3}file` WHERE 1 GROUP BY id) AS sub ON (f1.id=sub.id AND f1.version = sub.version) WHERE sub.id IS NULL;

DELETE `{tbls3}file` FROM `{tbls3}file` LEFT JOIN (SELECT id, MAX(VERSION) AS VERSION FROM `{tbls3}file` WHERE 1 GROUP BY id) AS sub ON (`{tbls3}file`.id=sub.id AND `{tbls3}file`.version = sub.version) WHERE sub.id IS NULL;

ALTER TABLE `{tbls3}file` DROP COLUMN `version` , CHANGE COLUMN `id` `id` INT(11) NOT NULL AUTO_INCREMENT  , CHANGE COLUMN `popis` `popis` VARCHAR(255) NULL DEFAULT NULL  , CHANGE COLUMN `user_created` `user_created` INT(10) UNSIGNED NOT NULL  , CHANGE COLUMN `user_modified` `user_modified` INT(10) UNSIGNED NOT NULL  ,
  ADD CONSTRAINT `fk_file_user1`
  FOREIGN KEY (`user_created` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_file_user2`
  FOREIGN KEY (`user_modified` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, DROP PRIMARY KEY 
, ADD PRIMARY KEY (`id`) 
, ADD INDEX `fk_file_user1` (`user_created` ASC) 
, ADD INDEX `fk_file_user2` (`user_modified` ASC) ;


ALTER TABLE `{tbls3}epodatelna` CHANGE COLUMN `source_id` `file_id` INT(11) NULL DEFAULT NULL, 
CHANGE COLUMN `dokument_id` `dokument_id` INT(11) NULL DEFAULT NULL, 
CHANGE COLUMN `odesilatel_id` `odesilatel_id` INT(11) NULL DEFAULT NULL, 
CHANGE COLUMN `identifikator` `identifikator` TEXT NULL DEFAULT NULL, 
CHANGE COLUMN `evidence` `evidence` VARCHAR(100) NULL DEFAULT NULL  ,
  ADD CONSTRAINT `fk_epodatelna_dokument1`
  FOREIGN KEY (`dokument_id` )
  REFERENCES `{tbls3}dokument` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_epodatelna_file1`
  FOREIGN KEY (`file_id` )
  REFERENCES `{tbls3}file` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_epodatelna_subjekt1`
  FOREIGN KEY (`odesilatel_id` )
  REFERENCES `{tbls3}subjekt` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, ADD INDEX `fk_epodatelna_dokument1` (`dokument_id` ASC) 
, ADD INDEX `fk_epodatelna_file1` (`file_id` ASC) 
, ADD INDEX `fk_epodatelna_subjekt1` (`odesilatel_id` ASC) ;

CREATE  TABLE IF NOT EXISTS `{tbls3}zpusob_odeslani` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `nazev` VARCHAR(80) NOT NULL ,
  `stav` TINYINT(4) NOT NULL DEFAULT '1' ,
  `fixed` TINYINT(4) NOT NULL DEFAULT '0' ,
  `note` VARCHAR(200) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB AUTO_INCREMENT = 6 DEFAULT CHARACTER SET = utf8 COLLATE = utf8_general_ci;

INSERT INTO `{tbls3}zpusob_odeslani` (`id`, `nazev`, `stav`, `fixed`, `note`) VALUES
(1,	'emailem',	1,	1,	NULL),
(2,	'datovou schránkou',	1,	1,	NULL),
(3,	'poštou',	1,	1,	NULL),
(4,	'faxem',	1,	1,	''),
(5,	'telefonicky',	1,	1,	'');

ALTER TABLE `{tbls3}dokument_odeslani` DROP COLUMN `user_info` , 
DROP COLUMN `subjekt_version` , 
DROP COLUMN `dokument_version` , 
CHANGE COLUMN `zpusob_odeslani` `zpusob_odeslani_id` INT(10) UNSIGNED NOT NULL , 
ADD COLUMN `druh_zasilky` VARCHAR(200) NULL DEFAULT NULL  AFTER `zprava` , 
ADD COLUMN `cena` FLOAT(11) NULL DEFAULT NULL  AFTER `druh_zasilky` , 
ADD COLUMN `hmotnost` FLOAT(11) NULL DEFAULT NULL  AFTER `cena` , 
ADD COLUMN `cislo_faxu` VARCHAR(100) NULL DEFAULT NULL  AFTER `hmotnost` , 
ADD COLUMN `stav` TINYINT(4) NOT NULL DEFAULT '1'  AFTER `cislo_faxu` , 
CHANGE COLUMN `user_id` `user_id` INT(10) UNSIGNED NOT NULL  AFTER `epodatelna_id` , 
CHANGE COLUMN `date_created` `date_created` DATETIME NOT NULL  ,
  ADD CONSTRAINT `fk_dokument_odeslani_dokument1`
  FOREIGN KEY (`dokument_id` )
  REFERENCES `{tbls3}dokument` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_dokument_odeslani_epodatelna1`
  FOREIGN KEY (`epodatelna_id` )
  REFERENCES `{tbls3}epodatelna` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_dokument_odeslani_subjekt1`
  FOREIGN KEY (`subjekt_id` )
  REFERENCES `{tbls3}subjekt` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_dokument_odeslani_user1`
  FOREIGN KEY (`user_id` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_dokument_odeslani_zpusob_odeslani1`
  FOREIGN KEY (`zpusob_odeslani_id` )
  REFERENCES `{tbls3}zpusob_odeslani` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, ADD INDEX `fk_dokument_odeslani_dokument1` (`dokument_id` ASC) 
, ADD INDEX `fk_dokument_odeslani_subjekt1` (`subjekt_id` ASC) 
, ADD INDEX `fk_dokument_odeslani_user1` (`user_id` ASC) 
, ADD INDEX `fk_dokument_odeslani_zpusob_odeslani1` (`zpusob_odeslani_id` ASC) 
, ADD INDEX `fk_dokument_odeslani_epodatelna1` (`epodatelna_id` ASC) 
, DROP INDEX `dokument`
, DROP INDEX `subjekt`;

UPDATE `{tbls3}dokument_odeslani` SET stav=2;

ALTER TABLE `{tbls3}dokument_to_file` DROP COLUMN `file_version` , 
DROP COLUMN `dokument_version` , 
CHANGE COLUMN `user_added` `user_id` INT(10) UNSIGNED NOT NULL , 
CHANGE COLUMN `active` `active` TINYINT(4) NOT NULL DEFAULT '1' , 
CHANGE COLUMN `date_added` `date_added` DATETIME NOT NULL  ,
  ADD CONSTRAINT `fk_dokument_to_file_dokument1`
  FOREIGN KEY (`dokument_id` )
  REFERENCES `{tbls3}dokument` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_dokument_to_file_file1`
  FOREIGN KEY (`file_id` )
  REFERENCES `{tbls3}file` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_dokument_to_file_user1`
  FOREIGN KEY (`user_id` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, ADD INDEX `fk_dokument_to_file_dokument1` (`dokument_id` ASC) 
, ADD INDEX `fk_dokument_to_file_file1` (`file_id` ASC) 
, ADD INDEX `fk_dokument_to_file_user1` (`user_id` ASC) 
, DROP INDEX `dokument`
, DROP INDEX `file`;

ALTER TABLE `{tbls3}spisovy_znak` CHANGE COLUMN `spisznak_parent` `parent_id` INT(11) NULL DEFAULT NULL , 
CHANGE COLUMN `spousteci_udalost` `spousteci_udalost_id` INT(11) NULL DEFAULT NULL , 
CHANGE COLUMN `stav` `stav` TINYINT(4) NOT NULL DEFAULT '1' , 
CHANGE COLUMN `sekvence` `sekvence` VARCHAR(300) NULL DEFAULT NULL ,
ADD COLUMN `sekvence_string` VARCHAR(1000) NULL DEFAULT NULL  AFTER `sekvence` , 
ADD COLUMN `selected` tinyint(1) NOT NULL DEFAULT '1' AFTER `spousteci_udalost_id`,
  ADD CONSTRAINT `fk_spisovy_znak_spousteci_udalost1`
  FOREIGN KEY (`spousteci_udalost_id` )
  REFERENCES `{tbls3}spousteci_udalost` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `spisovy_znak_ibfk_1`
  FOREIGN KEY (`parent_id` )
  REFERENCES `spisovy_znak` (`id` )
, ADD INDEX `fk_spisovy_znak_spousteci_udalost1` (`spousteci_udalost_id` ASC) 
, ADD INDEX `fk_spisovy_znak_spisovy_znak1` (`parent_id` ASC) ;

ALTER TABLE `{tbls3}spis` CHANGE COLUMN `spis_parent` `parent_id` INT(11) NULL DEFAULT NULL , 
ADD COLUMN `spousteci_udalost_id` INT(11) NULL DEFAULT NULL AFTER `spousteci_udalost`, 
CHANGE COLUMN `spisovy_znak` `spisovy_znak_id` INT(10) NULL DEFAULT NULL  AFTER `spousteci_udalost_id` , 
ADD COLUMN `sekvence_string` VARCHAR(1000) NULL DEFAULT NULL  AFTER `sekvence` , 
ADD COLUMN `datum_otevreni` DATETIME NULL DEFAULT NULL  AFTER `skartacni_lhuta` , 
ADD COLUMN `datum_uzavreni` DATETIME NULL DEFAULT NULL  AFTER `datum_otevreni` , 
ADD COLUMN `orgjednotka_id` INT(10) UNSIGNED NULL DEFAULT NULL  AFTER `datum_uzavreni` , 
ADD COLUMN `orgjednotka_id_predano` INT(10) UNSIGNED NULL DEFAULT NULL  AFTER `orgjednotka_id` , 
CHANGE COLUMN `sekvence` `sekvence` VARCHAR(200) NULL DEFAULT NULL , 
CHANGE COLUMN `uroven` `uroven` TINYINT(4) NULL DEFAULT NULL  , 
CHANGE COLUMN `date_created` `date_created` DATETIME NOT NULL  , 
CHANGE COLUMN `user_created` `user_created` INT(10) UNSIGNED NOT NULL  , 
CHANGE COLUMN `user_modified` `user_modified` INT(10) UNSIGNED NULL DEFAULT NULL  , 
CHANGE COLUMN `skartacni_znak` `skartacni_znak` ENUM('A','S','V') NOT NULL DEFAULT 'A'  ,
CHANGE COLUMN `skartacni_lhuta` `skartacni_lhuta` INT(11) NOT NULL DEFAULT '10'  ,
  ADD CONSTRAINT `fk_spis_spousteci_udalost1`
  FOREIGN KEY (`spousteci_udalost_id` )
  REFERENCES `{tbls3}spousteci_udalost` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_spis_user1`
  FOREIGN KEY (`user_created` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_spis_user2`
  FOREIGN KEY (`user_modified` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `spis_ibfk_1`
  FOREIGN KEY (`spisovy_znak_id` )
  REFERENCES `{tbls3}spisovy_znak` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, ADD INDEX `fk_spis_spousteci_udalost1` (`spousteci_udalost_id` ASC) 
, ADD INDEX `fk_spis_spis1` (`parent_id` ASC) 
, ADD INDEX `fk_spis_user1` (`user_created` ASC) 
, ADD INDEX `fk_spis_user2` (`user_modified` ASC) 
, ADD INDEX `spisovy_znak_id` (`spisovy_znak_id` ASC) 
, ADD INDEX `orgjednotka_id` (`orgjednotka_id` ASC) 
, ADD INDEX `orgjednotka_id_predano` (`orgjednotka_id_predano` ASC) ;

ALTER TABLE `{tbls3}spis` ADD COLUMN `spisovy_znak` VARCHAR(45) NULL DEFAULT NULL  AFTER `user_modified`,
ADD COLUMN `spisovy_znak_plneurceny` VARCHAR(200) NULL DEFAULT NULL  AFTER `spisovy_znak`;

ALTER TABLE `{tbls3}dokument_to_spis` DROP COLUMN `dokument_version` , 
CHANGE COLUMN `user_added` `user_id` INT(10) UNSIGNED NOT NULL , 
CHANGE COLUMN `date_added` `date_added` DATETIME NULL DEFAULT NULL ,
  ADD CONSTRAINT `fk_dokument_to_spis_dokument1`
  FOREIGN KEY (`dokument_id` )
  REFERENCES `{tbls3}dokument` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_dokument_to_spis_spis1`
  FOREIGN KEY (`spis_id` )
  REFERENCES `{tbls3}spis` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_dokument_to_spis_user1`
  FOREIGN KEY (`user_id` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, ADD INDEX `fk_dokument_to_spis_dokument1` (`dokument_id` ASC) 
, ADD INDEX `fk_dokument_to_spis_spis1` (`spis_id` ASC) 
, ADD INDEX `fk_dokument_to_spis_user1` (`user_id` ASC) 
, DROP INDEX `dokument` 
, DROP INDEX `spis` ;

ALTER TABLE `{tbls3}dokument_to_subjekt` DROP COLUMN `subjekt_version` , 
DROP COLUMN `dokument_version` , 
CHANGE COLUMN `user_added` `user_id` INT(10) UNSIGNED NOT NULL , 
CHANGE COLUMN `date_added` `date_added` DATETIME NOT NULL  ,
  ADD CONSTRAINT `fk_dokument_to_subjekt_dokument1`
  FOREIGN KEY (`dokument_id` )
  REFERENCES `{tbls3}dokument` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_dokument_to_subjekt_subjekt1`
  FOREIGN KEY (`subjekt_id` )
  REFERENCES `{tbls3}subjekt` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_dokument_to_subjekt_user1`
  FOREIGN KEY (`user_id` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, ADD INDEX `fk_dokument_to_subjekt_dokument1` (`dokument_id` ASC) 
, ADD INDEX `fk_dokument_to_subjekt_subjekt1` (`subjekt_id` ASC) 
, ADD INDEX `fk_dokument_to_subjekt_user1` (`user_id` ASC) 
, DROP INDEX `dokument` 
, DROP INDEX `subjekt` ;

CREATE  TABLE IF NOT EXISTS `{tbls3}druh_zasilky` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `nazev` VARCHAR(150) NOT NULL ,
  `fixed` TINYINT(4) NOT NULL DEFAULT '0' ,
  `stav` TINYINT(4) NOT NULL DEFAULT '1' ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_general_ci;

INSERT INTO `{tbls3}druh_zasilky` (`id`, `nazev`, `fixed`, `stav`) VALUES
(1,	'obyčejné',	1,	1),
(2,	'doporučené',	1,	1),
(3,	'balík',	1,	1),
(4,	'do vlastních rukou',	1,	1),
(5,	'dodejka',	1,	1),
(6,	'cenné psaní',	1,	1),
(7,	'cizina',	1,	1),
(8,	'EMS',	1,	1),
(9,	'dobírka',	1,	1);


ALTER TABLE `{tbls3}log_access` CHANGE COLUMN `user_id` `user_id` INT(10) UNSIGNED NOT NULL  AFTER `stav` , 
CHANGE COLUMN `ip` `ip` VARCHAR(40) NULL DEFAULT NULL  , 
ADD INDEX `fk_log_access_user1` (`user_id` ASC) ;

ALTER TABLE `{tbls3}log_dokument` DROP COLUMN `user_info` , 
CHANGE COLUMN `dokument_id` `dokument_id` INT(11) NOT NULL  AFTER `id` , 
CHANGE COLUMN `user_id` `user_id` INT(10) UNSIGNED NOT NULL  AFTER `dokument_id` ,
  ADD CONSTRAINT `fk_log_dokument_dokument1`
  FOREIGN KEY (`dokument_id` )
  REFERENCES `{tbls3}dokument` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_log_dokument_user1`
  FOREIGN KEY (`user_id` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, ADD INDEX `fk_log_dokument_dokument1` (`dokument_id` ASC) 
, ADD INDEX `fk_log_dokument_user1` (`user_id` ASC) 
, DROP INDEX `dokument` ;

CREATE  TABLE IF NOT EXISTS `{tbls3}log_spis` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `spis_id` INT(11) NOT NULL ,
  `user_id` INT(10) UNSIGNED NOT NULL ,
  `typ` TINYINT(4) NOT NULL ,
  `poznamka` TEXT NULL DEFAULT NULL ,
  `date` DATETIME NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_log_spis_spis1` (`spis_id` ASC) ,
  INDEX `fk_log_spis_user1` (`user_id` ASC) ,
  CONSTRAINT `fk_log_spis_spis1`
    FOREIGN KEY (`spis_id` )
    REFERENCES `{tbls3}spis` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_log_spis_user1`
    FOREIGN KEY (`user_id` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_general_ci; 

CREATE  TABLE IF NOT EXISTS `{tbls3}osoba_historie` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `osoba_id` INT(11) NOT NULL ,
  `prijmeni` VARCHAR(255) NOT NULL ,
  `jmeno` VARCHAR(150) CHARACTER SET 'utf8' COLLATE 'utf8_bin' NULL DEFAULT NULL ,
  `titul_pred` VARCHAR(50) NULL DEFAULT NULL ,
  `titul_za` VARCHAR(50) NULL DEFAULT NULL ,
  `email` VARCHAR(200) NULL DEFAULT NULL ,
  `telefon` VARCHAR(20) NULL DEFAULT NULL ,
  `pozice` VARCHAR(50) NULL DEFAULT NULL ,
  `stav` TINYINT(4) NOT NULL ,
  `date_created` DATETIME NOT NULL ,
  `user_created` INT(10) UNSIGNED NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_osoba_user1` (`user_created` ASC) ,
  INDEX `fk_osoba_historie_osoba1` (`osoba_id` ASC) ,
  CONSTRAINT `fk_osoba_historie_osoba1`
    FOREIGN KEY (`osoba_id` )
    REFERENCES `{tbls3}osoba` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_osoba_user10`
    FOREIGN KEY (`user_created` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_general_ci;

ALTER TABLE `{tbls3}osoba` ADD COLUMN `user_created` INT(10) UNSIGNED NOT NULL  AFTER `date_created` , ADD COLUMN `user_modified` INT(10) UNSIGNED NULL DEFAULT NULL  AFTER `date_modified` , CHANGE COLUMN `jmeno` `jmeno` VARCHAR(150) CHARACTER SET 'utf8' COLLATE 'utf8_bin' NULL DEFAULT NULL  , CHANGE COLUMN `titul_pred` `titul_pred` VARCHAR(50) NULL DEFAULT NULL  , CHANGE COLUMN `titul_za` `titul_za` VARCHAR(50) NULL DEFAULT NULL  , CHANGE COLUMN `email` `email` VARCHAR(200) NULL DEFAULT NULL  , CHANGE COLUMN `telefon` `telefon` VARCHAR(20) NULL DEFAULT NULL  , CHANGE COLUMN `pozice` `pozice` VARCHAR(50) NULL DEFAULT NULL  ,
  ADD CONSTRAINT `fk_osoba_user1`
  FOREIGN KEY (`user_created` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_osoba_user2`
  FOREIGN KEY (`user_modified` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, ADD INDEX `fk_osoba_user1` (`user_created` ASC) 
, ADD INDEX `fk_osoba_user2` (`user_modified` ASC) ;

UPDATE `{tbls3}osoba` SET stav = 10 WHERE stav = 0;
UPDATE `{tbls3}osoba` SET stav = 0 WHERE stav = 1;
UPDATE `{tbls3}osoba` SET stav = 1 WHERE stav = 10;

ALTER TABLE `{tbls3}osoba_to_user` CHANGE COLUMN `osoba_id` `osoba_id` INT(11) NOT NULL  ,
  ADD CONSTRAINT `fk_osoba_to_user_osoba1`
  FOREIGN KEY (`osoba_id` )
  REFERENCES `{tbls3}osoba` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_osoba_to_user_user1`
  FOREIGN KEY (`user_id` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, ADD INDEX `fk_osoba_to_user_osoba1` (`osoba_id` ASC) 
, ADD INDEX `fk_osoba_to_user_user1` (`user_id` ASC)
, DROP INDEX `osoba`
, DROP INDEX `user` ;


ALTER TABLE `{tbls3}souvisejici_dokument` CHANGE COLUMN `spojit_s` `spojit_s_id` INT(11) NOT NULL , 
CHANGE COLUMN `user_added` `user_id` INT(10) UNSIGNED NOT NULL ,
  ADD CONSTRAINT `fk_souvisejici_dokument_dokument1`
  FOREIGN KEY (`dokument_id` )
  REFERENCES `{tbls3}dokument` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_souvisejici_dokument_dokument2`
  FOREIGN KEY (`spojit_s_id` )
  REFERENCES `{tbls3}dokument` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_souvisejici_dokument_user1`
  FOREIGN KEY (`user_id` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, ADD INDEX `fk_souvisejici_dokument_dokument1` (`dokument_id` ASC) 
, ADD INDEX `fk_souvisejici_dokument_dokument2` (`spojit_s_id` ASC) 
, ADD INDEX `fk_souvisejici_dokument_user1` (`user_id` ASC) 
, DROP INDEX `dokument`
, DROP INDEX `spojit` ;

ALTER TABLE `{tbls3}user_role` ADD COLUMN `fixed_id` INT(10) UNSIGNED NULL DEFAULT NULL  AFTER `parent_id` , 
ADD COLUMN `sekvence` VARCHAR(300) NULL DEFAULT NULL  AFTER `date_modified` , 
ADD COLUMN `sekvence_string` VARCHAR(1000) NULL DEFAULT NULL  AFTER `sekvence` , 
ADD COLUMN `uroven` TINYINT(4) NULL DEFAULT NULL  AFTER `sekvence_string` , 
CHANGE COLUMN `orgjednotka_id` `orgjednotka_id` INT(10) UNSIGNED NULL DEFAULT NULL  AFTER `fixed_id` , 
  ADD CONSTRAINT `fk_user_role_orgjednotka1`
  FOREIGN KEY (`orgjednotka_id` )
  REFERENCES `{tbls3}orgjednotka` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_user_role_user_role1`
  FOREIGN KEY (`parent_id` )
  REFERENCES `{tbls3}user_role` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, ADD INDEX `fk_user_role_user_role1` (`parent_id` ASC) 
, ADD INDEX `fk_user_role_orgjednotka1` (`orgjednotka_id` ASC) ;

ALTER TABLE `{tbls3}user_rule` ENGINE = InnoDB , CHANGE COLUMN `resource_id` `resource_id` INT(10) UNSIGNED NULL DEFAULT NULL  AFTER `id` , CHANGE COLUMN `note` `note` VARCHAR(250) NULL DEFAULT NULL
, ADD INDEX `fk_user_rule_user_resource1` (`resource_id` ASC) 
, DROP INDEX `resource_id` ;

ALTER TABLE `{tbls3}user_acl` CHANGE COLUMN `role_id` `role_id` INT(10) UNSIGNED NOT NULL  , CHANGE COLUMN `rule_id` `rule_id` INT(10) UNSIGNED NOT NULL  ,
  ADD CONSTRAINT `fk_user_acl_user_role1`
  FOREIGN KEY (`role_id` )
  REFERENCES `{tbls3}user_role` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `user_acl_ibfk_1`
  FOREIGN KEY (`rule_id` )
  REFERENCES `{tbls3}user_rule` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, ADD INDEX `fk_user_acl_user_role1` (`role_id` ASC) 
, ADD INDEX `fk_user_acl_user_rule1` (`rule_id` ASC) 
, DROP INDEX `role`
, DROP INDEX `rule` ;

ALTER TABLE `{tbls3}user_to_role`
  ADD CONSTRAINT `fk_user_to_role_user1`
  FOREIGN KEY (`user_id` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_user_to_role_user_role1`
  FOREIGN KEY (`role_id` )
  REFERENCES `{tbls3}user_role` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, ADD INDEX `fk_user_to_role_user1` (`user_id` ASC) 
, ADD INDEX `fk_user_to_role_user_role1` (`role_id` ASC)
, DROP INDEX `user`
, DROP INDEX `role` ;

ALTER TABLE `{tbls3}workflow` DROP COLUMN `user_info` , 
DROP COLUMN `orgjednotka_info` , 
DROP COLUMN `prideleno_info` , 
DROP COLUMN `dokument_version` , 
ADD COLUMN `spis_id` INT(11) NULL DEFAULT NULL  AFTER `dokument_id` , 
CHANGE COLUMN `prideleno` `prideleno_id` INT(10) UNSIGNED NULL DEFAULT NULL , 
CHANGE COLUMN `orgjednotka_id` `orgjednotka_id` INT(10) UNSIGNED NULL DEFAULT NULL , 
CHANGE COLUMN `user_id` `user_id` INT(10) UNSIGNED NOT NULL ,
  ADD CONSTRAINT `fk_workflow_dokument1`
  FOREIGN KEY (`dokument_id` )
  REFERENCES `{tbls3}dokument` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_workflow_orgjednotka1`
  FOREIGN KEY (`orgjednotka_id` )
  REFERENCES `{tbls3}orgjednotka` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_workflow_user1`
  FOREIGN KEY (`prideleno_id` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_workflow_user2`
  FOREIGN KEY (`user_id` )
  REFERENCES `{tbls3}user` (`id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, ADD INDEX `fk_workflow_dokument1` (`dokument_id` ASC) 
, ADD INDEX `fk_workflow_user1` (`prideleno_id` ASC) 
, ADD INDEX `fk_workflow_orgjednotka1` (`orgjednotka_id` ASC) 
, ADD INDEX `fk_workflow_user2` (`user_id` ASC) 
, ADD INDEX `spis_id` (`spis_id` ASC)
, DROP INDEX `dokument`
, DROP INDEX `prideleno` ;

CREATE  TABLE IF NOT EXISTS `{tbls3}zapujcka` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `dokument_id` INT(10) UNSIGNED NOT NULL ,
  `user_id` INT(10) UNSIGNED NOT NULL ,
  `user_vytvoril_id` INT(10) UNSIGNED NOT NULL ,
  `user_prijal_id` INT(10) UNSIGNED NULL DEFAULT NULL ,
  `user_schvalil_id` INT(10) UNSIGNED NULL DEFAULT NULL ,
  `duvod` TEXT NULL DEFAULT NULL ,
  `stav` TINYINT(4) NOT NULL DEFAULT '1' ,
  `date_od` DATE NOT NULL ,
  `date_do` DATE NULL DEFAULT NULL ,
  `date_do_skut` DATE NULL DEFAULT NULL ,
  `date_created` DATETIME NOT NULL ,
  `date_schvaleni` DATETIME NULL DEFAULT NULL ,
  `date_prijeti` DATETIME NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `dokument_id` (`dokument_id` ASC) ,
  INDEX `user_id` (`user_id` ASC) ,
  INDEX `user_vytvoril_id` (`user_vytvoril_id` ASC) ,
  INDEX `user_prijal_id` (`user_prijal_id` ASC) ,
  INDEX `user_schvalil_id` (`user_schvalil_id` ASC) )
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 COLLATE = utf8_general_ci;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
