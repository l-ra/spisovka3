SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE  TABLE IF NOT EXISTS `{tbls3}user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `active` tinyint(4) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `last_modified` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `username` varchar(150) NOT NULL,
  `password` varchar(50) DEFAULT NULL,
  `last_ip` varchar(15) DEFAULT NULL,
  `local` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `USERNAME` (`username` ASC) )
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 CHECKSUM = 1 DELAY_KEY_WRITE = 1 ROW_FORMAT = DYNAMIC;

CREATE  TABLE IF NOT EXISTS `{tbls3}orgjednotka` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `plny_nazev` varchar(200) NOT NULL,
  `zkraceny_nazev` varchar(100) DEFAULT NULL,
  `ciselna_rada` varchar(30) NOT NULL,
  `note` text,
  `stav` tinyint(4) NOT NULL DEFAULT '0',
  `date_created` datetime DEFAULT NULL,
  `user_created` int(10) unsigned NOT NULL,
  `date_modified` datetime DEFAULT NULL,
  `user_modified` int(10) unsigned NOT NULL,
  `sekvence` varchar(300) DEFAULT NULL,
  `sekvence_string` varchar(1000) DEFAULT NULL,
  `uroven` tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`) ,
  INDEX `parent_id` (`parent_id` ASC),
  INDEX `fk_orgjednotka_user1` (`user_created` ASC) ,
  INDEX `fk_orgjednotka_user2` (`user_modified` ASC) ,
  CONSTRAINT `fk_orgjednotka_user1`
    FOREIGN KEY (`user_created` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_orgjednotka_user2`
    FOREIGN KEY (`user_modified` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

INSERT INTO `{tbls3}orgjednotka` (`id`, `plny_nazev`, `zkraceny_nazev`, `ciselna_rada`, `note`, `stav`, `user_created`, `date_created`, `user_modified`, `date_modified`) VALUES
(1, 'Centrální podatelna', 'Centrální podatelna', 'POD', '', 1, 1, NOW(), 1, NOW() );

CREATE  TABLE IF NOT EXISTS `{tbls3}cislo_jednaci` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `podaci_denik` VARCHAR(80) NOT NULL DEFAULT 'default' ,
  `rok` YEAR NOT NULL ,
  `poradove_cislo` INT(11) NULL DEFAULT NULL ,
  `urad_zkratka` VARCHAR(50) NULL DEFAULT NULL ,
  `urad_poradi` INT(11) NULL DEFAULT NULL ,
  `orgjednotka_id` INT(10) UNSIGNED NULL DEFAULT NULL ,
  `org_poradi` INT(11) NULL DEFAULT NULL ,
  `user_id` INT(10) UNSIGNED NULL DEFAULT NULL ,
  `user_poradi` INT(11) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_cislo_jednaci_user1` (`user_id` ASC) ,
  INDEX `fk_cislo_jednaci_orgjednotka1` (`orgjednotka_id` ASC) ,
  CONSTRAINT `fk_cislo_jednaci_user1`
    FOREIGN KEY (`user_id` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_cislo_jednaci_orgjednotka1`
    FOREIGN KEY (`orgjednotka_id` )
    REFERENCES `{tbls3}orgjednotka` (`id` )
    ON DELETE NO ACTION ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `{tbls3}dokument_typ` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `nazev` VARCHAR(100) NOT NULL ,
  `popis` VARCHAR(255) NULL DEFAULT NULL ,
  `stav` TINYINT(4) NOT NULL DEFAULT '1' ,
  `smer` TINYINT(4) NOT NULL DEFAULT '0' COMMENT '0-prichozi, 1-odchozi' ,
  `podatelna` TINYINT(1) NOT NULL DEFAULT 1 ,
  `referent` TINYINT(1) NOT NULL DEFAULT 1 ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

INSERT INTO `{tbls3}dokument_typ` (`id`, `nazev`, `popis`, `stav`, `smer`, `podatelna`, `referent`) VALUES
(1, 'příchozí', NULL, 1, 0, 1, 0),
(2, 'vlastní', NULL, 1, 1, 0, 1),
(3, 'odpověď', NULL, 1, 1, 0, 1);

CREATE  TABLE IF NOT EXISTS `{tbls3}zpusob_vyrizeni` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `nazev` VARCHAR(80) NOT NULL ,
  `stav` TINYINT(4) NOT NULL DEFAULT '1' ,
  `fixed` TINYINT(1) NOT NULL DEFAULT 0 ,
  `note` VARCHAR(200) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

INSERT INTO `{tbls3}zpusob_vyrizeni` (`id`, `nazev`, `stav`) VALUES
(1, 'vyřízení prvopisem', 1),
(2, 'postoupení', 1),
(3, 'vzetí na vědomí', 1),
(4, 'úřední záznam', 1),
(5, 'storno', 1),
(6, 'jiný způsob', 1);

CREATE  TABLE IF NOT EXISTS `{tbls3}zmocneni` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `cislo_zakona` INT(11) NOT NULL COMMENT 'LegalTitleLaw' ,
  `rok_vydani` YEAR NOT NULL COMMENT 'LegaltitleYear' ,
  `paragraf` VARCHAR(50) NOT NULL COMMENT 'LegalTitleSect' ,
  `odstavec` VARCHAR(50) NOT NULL COMMENT 'LegalTitlePar' ,
  `pismeno` VARCHAR(10) NOT NULL COMMENT 'LegaltitlePoint' ,
  `stav` TINYINT(4) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `{tbls3}spousteci_udalost` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `nazev` TEXT NOT NULL ,
  `poznamka` TEXT NULL DEFAULT NULL ,
  `stav` TINYINT(4) NOT NULL DEFAULT '1' ,
  `poznamka_k_datumu` VARCHAR(150) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

INSERT INTO `{tbls3}spousteci_udalost` (`id`, `nazev`, `poznamka`, `stav`, `poznamka_k_datumu`) VALUES
(1, 'Skartační lhůta začíná plynout po ztrátě platnosti dokumentu.', NULL, 1,'ukončení platnosti dokumentu'),
(2, 'Skartační lhůta začíná plynout po ukončení záruky.', NULL, 1,'ukončení záruky'),
(3, 'Skartační lhůta začíná plynout po uzavření dokumentu.', NULL, 1,'uzavření/vyřízení dokumentu'),
(4, 'Skartační lhůta počíná plynout po zařazení dokumentů z předávacích protokolů do skartačního řízení (předávací protokoly).', NULL, 1, 'zařazení dokumentů'),
(5, 'Skartační lhůta začíná plynout po vyhodnocení dokumentu (Podkladový materiál k výkazům).', NULL, 1, 'vyhotovení dokumentu'),
(6, 'Skartační lhůta začíná běžet po roce, v němž byla výpočetní a jiná technika naposledy použita, nebo po ukončení používání příslušného software (Provozní dokumentace, licence).', NULL, 1,'posledního použití nebo ukončení použití'),
(7, 'Skartační lhůta začíná plynout po vyhlášení výsledků voleb.', NULL, 1,'vyhlášení výsledku voleb'),
(8, 'Skartační lhůta začíná plynout po zrušení zařízení.', NULL, 1,'zrušení zařízení'),
(9, 'Nabytí účinnosti.', NULL, 1,'nabytí účinnosti'),
(10, 'Rozhodnutí, nabytí právní moci.', NULL, 1,'rozhodnutí'),
(11, 'Uvedení objektu do provozu.', NULL, 1,'udevení objektu do provozu'),
(12, 'Ukončení studia.', NULL, 1,'ukončení studia'),
(13, 'Ukončení pobytu.', NULL, 1,'ukončení pobytu'),
(14, 'Ukončení pracovního/služebního poměru.', NULL, 1,'ukončení pracovního/služebního poměru'),
(15, 'Skartační lhůta u dokumentů celostátně vyhlášeného referenda začíná plynout po vyhlášení výsledků referenda prezidentem republiky ve Sbírce zákonů, popřípadě po vyhlášení nálezu Ústavního soudu, kterým rozhodl, že postup při provádění referenda nebyl v souladu s ústavním zákonem o referendu o přistoupení České republiky k Evropské unii nebo zákonem vydaným k jeho provedení s povinností zachování tří nepoužitých hlasovacích lístků pro referendum pro uložení v příslušném archivu.', NULL, 1,'vyhlášení výsledků referenda'),
(16, 'Skartační lhůta u dokumentů krajského referenda začíná plynout po vyhlášení výsledků referenda s povinností zachování tří nepoužitých hlasovacích lístků pro referendum pro uložení v příslušném archivu.', NULL, 1,'vyhlášení výsledků referenda');

CREATE  TABLE IF NOT EXISTS `{tbls3}zpusob_doruceni` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `nazev` VARCHAR(80) NOT NULL ,
  `stav` TINYINT(4) NOT NULL DEFAULT '1' ,
  `fixed` TINYINT(1) NOT NULL DEFAULT 0 ,
  `note` VARCHAR(255) NULL ,
  `epodatelna` TINYINT(1) NULL DEFAULT 0 ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

INSERT INTO `{tbls3}zpusob_doruceni` (`id`, `nazev`, `stav`, `fixed`, `note`, `epodatelna`) VALUES
(1, 'emailem', 1, 1, NULL, 1),
(2, 'datovou schránkou', 1, 1, NULL, 1),
(3, 'datovým nosičem', 1, 1, NULL, 0),
(4, 'faxem', 1, 1, NULL, 0),
(5, 'v listinné podobě', 1, 1, NULL, 0);

CREATE  TABLE IF NOT EXISTS `{tbls3}dokument` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `dokument_typ_id` INT(11) NOT NULL ,
  `zpusob_doruceni_id` INT(10) UNSIGNED NULL ,
  `cislo_jednaci_id` INT(11) NULL ,
  `zpusob_vyrizeni_id` INT(10) UNSIGNED NULL ,
  `zmocneni_id` INT(11) NULL ,
  `spousteci_udalost_id` INT(11) NULL ,
  `jid` VARCHAR(100) NOT NULL ,
  `nazev` VARCHAR(100) NOT NULL ,
  `popis` VARCHAR(255) NULL ,
  `cislo_jednaci` VARCHAR(50) NULL ,
  `poradi` SMALLINT(6) NULL DEFAULT '1' ,
  `cislo_jednaci_odesilatele` VARCHAR(50) NULL ,
  `podaci_denik` VARCHAR(45) NULL ,
  `podaci_denik_poradi` INT(11) NULL DEFAULT NULL ,
  `podaci_denik_rok` YEAR NULL DEFAULT NULL ,
  `spisovy_plan` VARCHAR(45) NULL DEFAULT NULL ,
  `spisovy_znak_id` INT(11) NULL DEFAULT NULL ,
  `skartacni_znak` ENUM('A','S','V') NULL DEFAULT NULL ,
  `skartacni_lhuta` INT(11) NULL DEFAULT NULL ,
  `poznamka` TEXT NULL ,
  `lhuta` TINYINT(4) NOT NULL DEFAULT '30' ,
  `epodatelna_id` INT(11) NULL DEFAULT NULL ,
  `stav` TINYINT(4) NOT NULL DEFAULT '1' ,
  `md5_hash` VARCHAR(45) NOT NULL ,
  `date_created` DATETIME NULL DEFAULT NULL ,
  `user_created` INT(10) UNSIGNED NOT NULL ,
  `date_modified` DATETIME NULL DEFAULT NULL ,
  `user_modified` INT(10) UNSIGNED NULL ,
  `datum_vzniku` DATETIME NULL DEFAULT NULL ,
  `pocet_listu` INT(11) NULL DEFAULT 0 ,
  `pocet_priloh` INT(11) NULL DEFAULT 0 ,
  `vyrizeni_pocet_listu` INT(11) NULL DEFAULT NULL ,
  `vyrizeni_pocet_priloh` INT(11) NULL DEFAULT NULL ,
  `ulozeni_dokumentu` TEXT NULL ,
  `datum_vyrizeni` DATETIME NULL DEFAULT NULL ,
  `poznamka_vyrizeni` TEXT NULL ,
  `datum_spousteci_udalosti` DATETIME NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_dokument_dokument_typ` (`dokument_typ_id` ASC) ,
  INDEX `fk_dokument_cislo_jednaci1` (`cislo_jednaci_id` ASC) ,
  INDEX `fk_dokument_zpusob_vyrizeni1` (`zpusob_vyrizeni_id` ASC) ,
  INDEX `fk_dokument_zmocneni1` (`zmocneni_id` ASC) ,
  INDEX `fk_dokument_spousteci_udalost1` (`spousteci_udalost_id` ASC) ,
  INDEX `fk_dokument_user1` (`user_created` ASC) ,
  INDEX `fk_dokument_user2` (`user_modified` ASC) ,
  INDEX `fk_dokument_zpusob_doruceni1` (`zpusob_doruceni_id` ASC) ,
  CONSTRAINT `fk_dokument_dokument_typ`
    FOREIGN KEY (`dokument_typ_id` )
    REFERENCES `{tbls3}dokument_typ` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_cislo_jednaci1`
    FOREIGN KEY (`cislo_jednaci_id` )
    REFERENCES `{tbls3}cislo_jednaci` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_zpusob_vyrizeni1`
    FOREIGN KEY (`zpusob_vyrizeni_id` )
    REFERENCES `{tbls3}zpusob_vyrizeni` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_zmocneni1`
    FOREIGN KEY (`zmocneni_id` )
    REFERENCES `{tbls3}zmocneni` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_spousteci_udalost1`
    FOREIGN KEY (`spousteci_udalost_id` )
    REFERENCES `{tbls3}spousteci_udalost` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_user1`
    FOREIGN KEY (`user_created` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_user2`
    FOREIGN KEY (`user_modified` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_zpusob_doruceni1`
    FOREIGN KEY (`zpusob_doruceni_id` )
    REFERENCES `{tbls3}zpusob_doruceni` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `{tbls3}subjekt` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `stav` TINYINT(4) NOT NULL DEFAULT '1' ,
  `type` VARCHAR(15) NOT NULL ,
  `ic` VARCHAR(8) NULL ,
  `dic` VARCHAR(12) NULL ,
  `nazev_subjektu` VARCHAR(255) NULL ,
  `jmeno` VARCHAR(24) NULL ,
  `prijmeni` VARCHAR(35) NULL ,
  `prostredni_jmeno` VARCHAR(35) NULL ,
  `titul_pred` VARCHAR(35) NULL ,
  `titul_za` VARCHAR(10) NULL ,
  `rodne_jmeno` VARCHAR(35) NULL ,
  `datum_narozeni` DATE NULL DEFAULT NULL ,
  `misto_narozeni` VARCHAR(48) NULL ,
  `okres_narozeni` VARCHAR(48) NULL ,
  `stat_narozeni` VARCHAR(3) NULL ,
  `adresa_mesto` VARCHAR(48) NULL ,
  `adresa_ulice` VARCHAR(48) NULL ,
  `adresa_cp` VARCHAR(10) NULL ,
  `adresa_co` VARCHAR(10) NULL ,
  `adresa_psc` VARCHAR(10) NULL ,
  `adresa_stat` VARCHAR(3) NULL ,
  `narodnost` VARCHAR(80) NULL ,
  `email` VARCHAR(250) NULL ,
  `telefon` VARCHAR(150) NULL ,
  `id_isds` VARCHAR(50) NULL ,
  `poznamka` TEXT NULL ,
  `date_created` DATETIME NULL DEFAULT NULL ,
  `user_created` INT(10) UNSIGNED NOT NULL ,
  `date_modified` DATETIME NULL DEFAULT NULL ,
  `user_modified` INT(10) UNSIGNED NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_subjekt_user1` (`user_created` ASC) ,
  INDEX `fk_subjekt_user2` (`user_modified` ASC) ,
  CONSTRAINT `fk_subjekt_user1`
    FOREIGN KEY (`user_created` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_subjekt_user2`
    FOREIGN KEY (`user_modified` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `{tbls3}zpusob_odeslani` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `nazev` VARCHAR(80) NOT NULL ,
  `stav` TINYINT(4) NOT NULL DEFAULT '1' ,
  `note` VARCHAR(200) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

INSERT INTO `{tbls3}zpusob_odeslani` (`id`, `nazev`, `stav`, `note`) VALUES
(1, 'emailem', 1, NULL),
(2, 'datovou schránkou', 1, NULL),
(3, 'poštou', 1, NULL),
(4, 'telefonicky', 1, NULL),
(5, 'faxem', 1, NULL),
(6, 'osobně', 1, NULL);

CREATE  TABLE IF NOT EXISTS `{tbls3}file` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `stav` TINYINT(4) NOT NULL DEFAULT '1' ,
  `typ` TINYINT(4) NOT NULL DEFAULT '1' COMMENT 'typ prilohy. Defaultne: (1)main, (2)enclosure, (3)signature, (4)meta, (5)source' ,
  `nazev` VARCHAR(255) NOT NULL COMMENT 'jmeno souboru nebo nazev' ,
  `popis` VARCHAR(45) NULL DEFAULT NULL ,
  `mime_type` VARCHAR(60) NULL DEFAULT NULL COMMENT 'mime typ souboru' ,
  `real_name` VARCHAR(255) NOT NULL COMMENT 'skutečné jmeno souboru file.ext' ,
  `real_path` VARCHAR(255) NOT NULL COMMENT 'realna cesta k souboru ' ,
  `real_type` VARCHAR(45) NOT NULL DEFAULT 'FILE' COMMENT 'typ fyzickeho mista. Default FILE - lokalni fyzicke misto' ,
  `date_created` DATETIME NULL ,
  `user_created` INT(10) UNSIGNED NOT NULL ,
  `date_modified` DATETIME NULL ,
  `user_modified` INT(10) UNSIGNED NULL ,
  `guid` VARCHAR(45) NOT NULL COMMENT 'jednoznacny identifikator' ,
  `md5_hash` VARCHAR(45) NOT NULL COMMENT 'otisk souboru pro overeni pravosti' ,
  `size` INT(11) NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_file_user1` (`user_created` ASC) ,
  INDEX `fk_file_user2` (`user_modified` ASC) ,
  CONSTRAINT `fk_file_user1`
    FOREIGN KEY (`user_created` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_file_user2`
    FOREIGN KEY (`user_modified` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `{tbls3}epodatelna` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `dokument_id` INT(11) NULL ,
  `odesilatel_id` INT(11) NULL ,
  `file_id` INT(11) NULL ,
  `epodatelna_typ` TINYINT(4) NOT NULL DEFAULT '0' ,
  `poradi` INT(11) NULL DEFAULT NULL ,
  `rok` YEAR NULL DEFAULT NULL ,
  `email_signature` VARCHAR(200) NULL DEFAULT NULL ,
  `isds_signature` VARCHAR(45) NULL DEFAULT NULL ,
  `predmet` VARCHAR(200) NOT NULL DEFAULT '' ,
  `popis` TEXT NULL DEFAULT NULL ,
  `odesilatel` VARCHAR(200) NOT NULL DEFAULT '' ,
  `adresat` VARCHAR(100) NOT NULL DEFAULT '' ,
  `prijato_dne` DATETIME NULL DEFAULT NULL ,
  `doruceno_dne` DATETIME NULL DEFAULT NULL ,
  `prijal_kdo` INT(11) NULL DEFAULT NULL ,
  `prijal_info` TEXT NULL DEFAULT NULL ,
  `sha1_hash` VARCHAR(50) NOT NULL ,
  `prilohy` TEXT NULL DEFAULT NULL ,
  `identifikator` TEXT NULL ,
  `evidence` VARCHAR(100) NULL DEFAULT NULL ,
  `stav` TINYINT(4) NOT NULL DEFAULT '0' ,
  `stav_info` VARCHAR(255) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_epodatelna_dokument1` (`dokument_id` ASC) ,
  INDEX `fk_epodatelna_file1` (`file_id` ASC) ,
  INDEX `fk_epodatelna_subjekt1` (`odesilatel_id` ASC) ,
  CONSTRAINT `fk_epodatelna_dokument1`
    FOREIGN KEY (`dokument_id` )
    REFERENCES `{tbls3}dokument` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_epodatelna_file1`
    FOREIGN KEY (`file_id` )
    REFERENCES `{tbls3}file` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_epodatelna_subjekt1`
    FOREIGN KEY (`odesilatel_id` )
    REFERENCES `{tbls3}subjekt` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `{tbls3}dokument_odeslani` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `dokument_id` INT(11) NOT NULL ,
  `subjekt_id` INT(11) NOT NULL ,
  `zpusob_odeslani_id` INT(10) UNSIGNED NOT NULL ,
  `epodatelna_id` INT(11) NULL ,
  `user_id` INT(10) UNSIGNED NOT NULL ,
  `datum_odeslani` DATETIME NOT NULL ,
  `zprava` TEXT NULL DEFAULT NULL ,
  `date_created` DATETIME NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_dokument_odeslani_dokument1` (`dokument_id` ASC) ,
  INDEX `fk_dokument_odeslani_subjekt1` (`subjekt_id` ASC) ,
  INDEX `fk_dokument_odeslani_user1` (`user_id` ASC) ,
  INDEX `fk_dokument_odeslani_zpusob_odeslani1` (`zpusob_odeslani_id` ASC) ,
  INDEX `fk_dokument_odeslani_epodatelna1` (`epodatelna_id` ASC) ,
  CONSTRAINT `fk_dokument_odeslani_dokument1`
    FOREIGN KEY (`dokument_id` )
    REFERENCES `{tbls3}dokument` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_odeslani_subjekt1`
    FOREIGN KEY (`subjekt_id` )
    REFERENCES `{tbls3}subjekt` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_odeslani_user1`
    FOREIGN KEY (`user_id` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_odeslani_zpusob_odeslani1`
    FOREIGN KEY (`zpusob_odeslani_id` )
    REFERENCES `{tbls3}zpusob_odeslani` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_odeslani_epodatelna1`
    FOREIGN KEY (`epodatelna_id` )
    REFERENCES `{tbls3}epodatelna` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `{tbls3}dokument_to_file` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `dokument_id` INT(11) NOT NULL ,
  `file_id` INT(11) NOT NULL ,
  `user_id` INT(10) UNSIGNED NOT NULL ,
  `active` TINYINT(4) NOT NULL DEFAULT '1' ,
  `date_added` DATETIME NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_dokument_to_file_dokument1` (`dokument_id` ASC) ,
  INDEX `fk_dokument_to_file_file1` (`file_id` ASC) ,
  INDEX `fk_dokument_to_file_user1` (`user_id` ASC) ,
  CONSTRAINT `fk_dokument_to_file_dokument1`
    FOREIGN KEY (`dokument_id` )
    REFERENCES `{tbls3}dokument` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_to_file_file1`
    FOREIGN KEY (`file_id` )
    REFERENCES `{tbls3}file` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_to_file_user1`
    FOREIGN KEY (`user_id` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `{tbls3}spis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `spousteci_udalost_id` int(11) DEFAULT NULL,
  `spisovy_znak_id` int(11) DEFAULT NULL,
  `nazev` varchar(80) NOT NULL,
  `popis` varchar(200) NOT NULL,
  `typ` varchar(5) NOT NULL DEFAULT 'S',
  `sekvence` varchar(200) DEFAULT NULL,
  `sekvence_string` varchar(1000) DEFAULT NULL,
  `uroven` tinyint(4) DEFAULT NULL,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `date_created` datetime NOT NULL,
  `user_created` int(10) unsigned NOT NULL,
  `date_modified` datetime DEFAULT NULL,
  `user_modified` int(10) unsigned DEFAULT NULL,
  `spisovy_znak` varchar(45) DEFAULT NULL,
  `spisovy_znak_plneurceny` varchar(200) DEFAULT NULL,
  `skartacni_znak` enum('A','S','V') NOT NULL DEFAULT 'A',
  `skartacni_lhuta` int(11) NOT NULL DEFAULT '10',
  `datum_otevreni` datetime DEFAULT NULL,
  `datum_uzavreni` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) ,
  INDEX `fk_spis_spousteci_udalost1` (`spousteci_udalost_id` ASC) ,
  INDEX `fk_spis_spisovy_znak1` (`spisovy_znak_id` ASC) ,
  INDEX `fk_spis_spis1` (`parent_id` ASC) ,
  INDEX `fk_spis_user1` (`user_created` ASC) ,
  INDEX `fk_spis_user2` (`user_modified` ASC) ,
  CONSTRAINT `fk_spis_spousteci_udalost1`
    FOREIGN KEY (`spousteci_udalost_id` )
    REFERENCES `{tbls3}spousteci_udalost` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_spis_spisovy_znak1`
    FOREIGN KEY (`spisovy_znak_id` )
    REFERENCES `{tbls3}spisovy_znak` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_spis_spis1`
    FOREIGN KEY (`parent_id` )
    REFERENCES `{tbls3}spis` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_spis_user1`
    FOREIGN KEY (`user_created` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_spis_user2`
    FOREIGN KEY (`user_modified` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `{tbls3}dokument_to_spis` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `dokument_id` INT(11) NOT NULL ,
  `spis_id` INT(11) NOT NULL ,
  `user_id` INT(10) UNSIGNED NOT NULL ,
  `poradi` INT(11) NOT NULL DEFAULT '1' ,
  `stav` TINYINT(4) NOT NULL DEFAULT '1' ,
  `date_added` DATETIME NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_dokument_to_spis_dokument1` (`dokument_id` ASC) ,
  INDEX `fk_dokument_to_spis_spis1` (`spis_id` ASC) ,
  INDEX `fk_dokument_to_spis_user1` (`user_id` ASC) ,
  CONSTRAINT `fk_dokument_to_spis_dokument1`
    FOREIGN KEY (`dokument_id` )
    REFERENCES `{tbls3}dokument` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_to_spis_spis1`
    FOREIGN KEY (`spis_id` )
    REFERENCES `{tbls3}spis` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_to_spis_user1`
    FOREIGN KEY (`user_id` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `{tbls3}dokument_to_subjekt` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `dokument_id` INT(11) NOT NULL ,
  `subjekt_id` INT(11) NOT NULL ,
  `user_id` INT(10) UNSIGNED NOT NULL ,
  `typ` ENUM('A','O','AO') NOT NULL DEFAULT 'AO' ,
  `date_added` DATETIME NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_dokument_to_subjekt_dokument1` (`dokument_id` ASC) ,
  INDEX `fk_dokument_to_subjekt_subjekt1` (`subjekt_id` ASC) ,
  INDEX `fk_dokument_to_subjekt_user1` (`user_id` ASC) ,
  CONSTRAINT `fk_dokument_to_subjekt_dokument1`
    FOREIGN KEY (`dokument_id` )
    REFERENCES `{tbls3}dokument` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_to_subjekt_subjekt1`
    FOREIGN KEY (`subjekt_id` )
    REFERENCES `{tbls3}subjekt` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_to_subjekt_user1`
    FOREIGN KEY (`user_id` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `{tbls3}log_access` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `date` DATETIME NULL DEFAULT NULL ,
  `ip` VARCHAR(50) NULL DEFAULT NULL ,
  `user_agent` VARCHAR(200) NULL DEFAULT NULL ,
  `stav` TINYINT(4) NULL DEFAULT NULL ,
  `user_id` INT(10) UNSIGNED NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_log_access_user1` (`user_id` ASC) ,
  CONSTRAINT `fk_log_access_user1`
    FOREIGN KEY (`user_id` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = MyISAM DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `{tbls3}log_dokument` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `dokument_id` INT(11) NOT NULL ,
  `user_id` INT(10) UNSIGNED NOT NULL ,
  `typ` TINYINT(4) NOT NULL ,
  `poznamka` TEXT NULL DEFAULT NULL ,
  `date` DATETIME NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_log_dokument_dokument1` (`dokument_id` ASC) ,
  INDEX `fk_log_dokument_user1` (`user_id` ASC) ,
  CONSTRAINT `fk_log_dokument_dokument1`
    FOREIGN KEY (`dokument_id` )
    REFERENCES `{tbls3}dokument` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_log_dokument_user1`
    FOREIGN KEY (`user_id` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `{tbls3}osoba` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `prijmeni` VARCHAR(255) NOT NULL ,
  `jmeno` VARCHAR(150) BINARY NULL DEFAULT NULL ,
  `titul_pred` VARCHAR(50) NULL DEFAULT NULL ,
  `titul_za` VARCHAR(50) NULL DEFAULT NULL ,
  `email` VARCHAR(200) NULL DEFAULT NULL ,
  `telefon` VARCHAR(20) NULL DEFAULT NULL ,
  `pozice` VARCHAR(50) NULL DEFAULT NULL ,
  `stav` TINYINT(4) NOT NULL ,
  `date_created` DATETIME NOT NULL ,
  `user_created` INT(10) UNSIGNED NOT NULL ,
  `date_modified` DATETIME NULL DEFAULT NULL ,
  `user_modified` INT(10) UNSIGNED NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_osoba_user1` (`user_created` ASC) ,
  INDEX `fk_osoba_user2` (`user_modified` ASC) ,
  CONSTRAINT `fk_osoba_user1`
    FOREIGN KEY (`user_created` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_osoba_user2`
    FOREIGN KEY (`user_modified` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `{tbls3}osoba_to_user` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `osoba_id` INT(11) NOT NULL ,
  `user_id` INT(10) UNSIGNED NOT NULL ,
  `date_added` DATETIME NOT NULL ,
  `active` TINYINT(4) NOT NULL DEFAULT '1' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_osoba_to_user_osoba1` (`osoba_id` ASC) ,
  INDEX `fk_osoba_to_user_user1` (`user_id` ASC) ,
  CONSTRAINT `fk_osoba_to_user_osoba1`
    FOREIGN KEY (`osoba_id` )
    REFERENCES `{tbls3}osoba` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_osoba_to_user_user1`
    FOREIGN KEY (`user_id` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `{tbls3}sestava` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `nazev` VARCHAR(60) NOT NULL ,
  `popis` VARCHAR(150) NULL DEFAULT NULL ,
  `parametry` TEXT NULL DEFAULT NULL ,
  `sloupce` TEXT NULL DEFAULT NULL ,
  `typ` TINYINT(4) NOT NULL DEFAULT '1' ,
  `filtr` TINYINT(4) NULL DEFAULT '0' ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB AUTO_INCREMENT = 2 DEFAULT CHARACTER SET = utf8;

INSERT INTO `{tbls3}sestava` (`id`, `nazev`, `popis`, `parametry`, `sloupce`, `typ`, `filtr`) VALUES
(1, 'Podací deník', NULL, NULL, NULL, 2, 1);

CREATE  TABLE IF NOT EXISTS `{tbls3}souvisejici_dokument` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `dokument_id` INT(11) NOT NULL ,
  `spojit_s_id` INT(11) NOT NULL ,
  `user_id` INT(10) UNSIGNED NOT NULL ,
  `date_added` DATETIME NOT NULL ,
  `type` TINYINT(4) NOT NULL DEFAULT '1' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_souvisejici_dokument_dokument1` (`dokument_id` ASC) ,
  INDEX `fk_souvisejici_dokument_dokument2` (`spojit_s_id` ASC) ,
  INDEX `fk_souvisejici_dokument_user1` (`user_id` ASC) ,
  CONSTRAINT `fk_souvisejici_dokument_dokument1`
    FOREIGN KEY (`dokument_id` )
    REFERENCES `{tbls3}dokument` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_souvisejici_dokument_dokument2`
    FOREIGN KEY (`spojit_s_id` )
    REFERENCES `{tbls3}dokument` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_souvisejici_dokument_user1`
    FOREIGN KEY (`user_id` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `{tbls3}spisovy_znak` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `nazev` varchar(80) NOT NULL,
  `popis` varchar(200) NOT NULL,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `sekvence` varchar(300) DEFAULT NULL,
  `sekvence_string` varchar(1000) DEFAULT NULL,
  `uroven` tinyint(4) DEFAULT '0',
  `date_created` datetime DEFAULT NULL,
  `user_created` int(11) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `user_modified` int(11) DEFAULT NULL,
  `skartacni_znak` enum('A','S','V') DEFAULT NULL,
  `skartacni_lhuta` int(11) DEFAULT NULL,
  `spousteci_udalost_id` int(11) NOT NULL,
  PRIMARY KEY (`id`) ,
  INDEX `fk_spisovy_znak_spousteci_udalost1` (`spousteci_udalost_id` ASC) ,
  INDEX `fk_spisovy_znak_spisovy_znak1` (`parent_id` ASC) ,
  CONSTRAINT `fk_spisovy_znak_spousteci_udalost1`
    FOREIGN KEY (`spousteci_udalost_id` )
    REFERENCES `{tbls3}spousteci_udalost` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_spisovy_znak_spisovy_znak1`
    FOREIGN KEY (`parent_id` )
    REFERENCES `{tbls3}spisovy_znak` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `{tbls3}user_role` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `fixed_id` int(10) unsigned DEFAULT NULL,
  `orgjednotka_id` int(10) unsigned DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `note` varchar(250) DEFAULT NULL,
  `fixed` tinyint(4) DEFAULT '0',
  `order` int(11) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '0',
  `date_created` datetime DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `sekvence` varchar(300) DEFAULT NULL,
  `sekvence_string` varchar(1000) DEFAULT NULL,
  `uroven` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_role_user_role1` (`parent_id` ASC) ,
  INDEX `fk_user_role_orgjednotka1` (`orgjednotka_id` ASC) ,
  CONSTRAINT `fk_user_role_user_role1`
    FOREIGN KEY (`parent_id` )
    REFERENCES `{tbls3}user_role` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_role_orgjednotka1`
    FOREIGN KEY (`orgjednotka_id` )
    REFERENCES `{tbls3}orgjednotka` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 CHECKSUM = 1 DELAY_KEY_WRITE = 1 ROW_FORMAT = DYNAMIC;

INSERT INTO `{tbls3}user_role` (`id`, `parent_id`, `fixed_id`, `orgjednotka_id`, `code`, `name`, `note`, `fixed`, `order`, `active`, `date_created`, `date_modified`, `sekvence`, `sekvence_string`, `uroven`) VALUES
(1,	NULL,	NULL,	NULL,	'admin',	'administrátor',	'Má absolutní moc provádět všechno možné',	2,	100,	1,	'2011-05-18 15:32:11',	'2011-05-18 15:32:11',	'1',	'admin.1',	NULL),
(2,	NULL,	NULL,	NULL,	'guest',	'host',	'Role představující nepřihlášeného uživatele.\\r\\nTedy nastavení oprávnění v době, kdy k aplikaci není nikdo přihlášen.',	2,	0,	1,	'2011-05-18 15:32:11',	'2011-05-18 15:32:11',	'2',	'guest.2',	NULL),
(3,	1,	NULL,	NULL,	'superadmin',	'SuperAdmin',	'Administrátor se super právy.\\r\\nTo znamená, že může manipulovat s jakýmikoli daty. Včetně dokumentů bez ohledu na vlastníka a stavu. ',	2,	100,	1,	'2011-05-18 15:32:11',	'2011-05-18 15:32:11',	'3',	'superadmin.3',	NULL),
(4,	NULL,	NULL,	NULL,	'referent',	'referent',	'Základní role pracovníka spisové služby',	1,	10,	1,	'2011-05-18 15:32:11',	'2011-05-18 15:32:11',	'4',	'referent.4',	NULL),
(5,	4,	NULL,	NULL,	'vedouci',	'vedoucí',	'Vedoucí organizační jednotky umožňující přijímat dokumenty',	1,	30,	1,	'2011-05-18 15:32:11',	'2011-05-18 15:32:11',	'5',	'vedouci.5',	NULL),
(6,	4,	NULL,	NULL,	'podatelna',	'pracovník podatelny',	'Pracovník podatelny, který může přijímat nebo odesílat dokumenty',	1,	20,	1,	'2011-05-18 15:32:11',	'2011-05-18 15:32:11',	'6',	'podatelna.6',	NULL),
(7,	4,	NULL,	NULL,	'skartacni_dohled',	'pracovník spisovny',	'Pracovník spisovny, který spravuje dokumenty ve spisovně',	1,	20,	1,	'2011-05-18 15:32:11',	'2011-05-18 15:32:11',	'7',	'skartacni_dohled.7',	NULL),
(8,	4,	NULL,	NULL,	'skartacni_komise',	'pracovník podatelny',	'Pracovník podatelny, který může přijímat nebo odesílat dokumenty',	1,	20,	1,	'2011-05-18 15:32:11',	'2011-05-18 15:32:11',	'8',	'skartacni_komise.8',	NULL);

CREATE  TABLE IF NOT EXISTS `{tbls3}user_resource` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `code` VARCHAR(150) NOT NULL ,
  `note` VARCHAR(255) NULL DEFAULT NULL ,
  `name` VARCHAR(100) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 CHECKSUM = 1 DELAY_KEY_WRITE = 1 ROW_FORMAT = DYNAMIC;

INSERT INTO `{tbls3}user_resource` (`id`, `code`, `note`, `name`) VALUES
(1, 'Spisovka_DokumentyPresenter', NULL, 'Seznam dokumentů'),
(2, 'Epodatelna_DefaultPresenter', NULL, 'E-podatelna - úvodní obrazovka'),
(3, 'Admin_DefaultPresenter', NULL, 'Administrace - úvodní obrazovka'),
(4, 'Spisovka_UzivatelPresenter', NULL, 'Přihlašování a změna osobních údajů uživatele'),
(5, 'Spisovka_DefaultPresenter', NULL, 'Úvodní obrazovka (rozšířená)'),
(6, 'Admin_ZamestnanciPresenter', NULL, 'Administrace - zaměstnanci'),
(7, 'DefaultPresenter', NULL, 'Úvodní obrazovka (základní)'),
(8, 'ErrorPresenter', NULL, 'Chybové hlášky'),
(9, 'Admin_OpravneniPresenter', NULL, 'Administrace - oprávnění'),
(10, 'Admin_OrgjednotkyPresenter', NULL, 'Administrace - organizační jednotky'),
(11, 'Admin_NastaveniPresenter', NULL, 'Administrace - nastavení'),
(12, 'Admin_SubjektyPresenter', NULL, 'Administrace - subjekty'),
(13, 'Admin_PrilohyPresenter', NULL, 'Administrace - soubory'),
(14, 'Admin_CiselnikyPresenter', NULL, 'Administrace - číselníky'),
(15, 'Admin_SpisyPresenter', NULL, 'Administrace - spisový plán'),
(16, 'Admin_SpisznakPresenter', NULL, 'Administrace - spisové znaky'),
(17, 'Admin_ProtokolPresenter', NULL, 'Administrace - protokoly'),
(18, 'Spisovka_SpisyPresenter', NULL, 'Spisy'),
(19, 'Spisovka_SubjektyPresenter', NULL, 'Subjekty'),
(20, 'Spisovka_PrilohyPresenter', NULL, 'Prilohy'),
(21, 'Admin_EpodatelnaPresenter', NULL, 'Administrace - nastavení e-podatelny'),
(22, 'Epodatelna_PrilohyPresenter', NULL, 'Epodatelna - zobrazeni prilohy'),
(23, 'Epodatelna_EvidencePresenter', NULL, 'Epodatelna - evidence'),
(24, 'Epodatelna_SubjektyPresenter', NULL, 'Epodatelna - subjekt'),
(25, 'Spisovka_SestavyPresenter', NULL, 'Sestavy'),
(26, 'Spisovka_SpojitPresenter', NULL, 'Spisovka - spojeni dokumentu'),
(27, 'Spisovka_ErrorPresenter', NULL, 'Error'),
(28, 'Install_DefaultPresenter', NULL, 'Instalace'),
(29, 'Spisovka_VyhledatPresenter', NULL, 'Vyhledavani'),
(30, 'Admin_AkonverzePresenter', NULL, 'Administrace - Autorizovaná konverze'),
(31, 'Spisovka_NapovedaPresenter', NULL, 'Nápověda'),
(32, 'Spisovna_DefaultPresenter', NULL,	'Spisovna'),
(33, 'Spisovna_DokumentyPresenter', NULL, 'Spisovna - dokumenty'),
(34, 'Spisovna_SpisyPresenter', NULL, 'Spisovna - spisy'),
(35, 'Spisovna_VyhledatPresenter', NULL, 'Spisovna - vyhledávani'),
(36, 'Spisovna_ZapujckyPresenter', NULL, 'Spisovna - zápůjčky');

CREATE  TABLE IF NOT EXISTS `{tbls3}user_rule` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `resource_id` INT(10) UNSIGNED NULL DEFAULT NULL ,
  `name` VARCHAR(100) NOT NULL ,
  `note` VARCHAR(250) NULL DEFAULT NULL ,
  `privilege` VARCHAR(100) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_rule_user_resource1` (`resource_id` ASC) ,
  CONSTRAINT `fk_user_rule_user_resource1`
    FOREIGN KEY (`resource_id` )
    REFERENCES `{tbls3}user_resource` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

INSERT INTO `{tbls3}user_rule` (`id`, `name`, `note`, `resource_id`, `privilege`) VALUES
(1, 'Bez omezení', NULL, NULL, NULL),
(2, 'Přihlášení uživatele', NULL, 4, 'login'),
(3, 'Zobrazeni uvodní obrazovky', NULL, 4, NULL),
(4, 'Zobrazení seznamu dokumentu', '', 1, ''),
(5, 'Základní obrazovka', '', 5, ''),
(6, 'Práce se subjekty', '', 19, ''),
(7, 'Práce s přílohy', '', 20, ''),
(8, 'Práce se spisy', '', 18, ''),
(9, 'Přístup do Epodatelny', '', 2, ''),
(10, 'E-podatelna - evidence', '', 22, ''),
(11, 'E-podatelna - přílohy', '', 24, ''),
(12, 'E-podatelna - subjekty', '', 23, ''),
(13, 'Spojování dokumentů', '', 26, ''),
(14, 'Sestavy', '', 25, ''),
(15, 'Vyhledávání', '', 29, ''),
(16, 'Je vedoucí', 'Určuje, zda daná role je vedoucí role. Umožňuje přistupovat k dokumentům předané orgaizační jednotce', NULL, 'is_vedouci'),
(17, 'Oprávnění pro org. jednotku Centrální podatelna', 'Oprávnění platné pouze pro organizační jednotku Centrální podatelna', NULL, 'orgjednotka_1'),
(18, 'Nápověda', 'Zobrazení nápovědy', 31, ''),
(19, 'Přístup do spisovny',	'', 32, ''),
(20, 'Přístup k dokumentům ve spisovně', '', 33, ''),
(21, 'Přístup ke spisům ve spisovně', '', 34, ''),
(22, 'Vyhledávání dokumentů ve spisovně', '', 35, ''),
(23, 'Spisovna - zápůjčky', '', 36, '');

CREATE  TABLE IF NOT EXISTS `{tbls3}user_acl` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `role_id` INT(10) UNSIGNED NOT NULL ,
  `rule_id` INT(10) UNSIGNED NOT NULL ,
  `allowed` ENUM('Y','N') NOT NULL DEFAULT 'Y' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_acl_user_role1` (`role_id` ASC) ,
  INDEX `fk_user_acl_user_rule1` (`rule_id` ASC) ,
  CONSTRAINT `fk_user_acl_user_role1`
    FOREIGN KEY (`role_id` )
    REFERENCES `{tbls3}user_role` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_acl_user_rule1`
    FOREIGN KEY (`rule_id` )
    REFERENCES `{tbls3}user_rule` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8 CHECKSUM = 1 DELAY_KEY_WRITE = 1 ROW_FORMAT = DYNAMIC;

INSERT INTO `{tbls3}user_acl` (`id`, `role_id`, `rule_id`, `allowed`) VALUES
(1,1,1,'Y'),
(2,2,1,'N'),
(3,2,2,'Y'),
(4,2,3,'Y'),
(5,4,5,'Y'),
(6,4,4,'Y'),
(7,4,7,'Y'),
(8,4,14,'Y'),
(9,4,8,'Y'),
(10,4,13,'Y'),
(11,4,6,'Y'),
(12,4,2,'Y'),
(13,4,3,'Y'),
(14,4,15,'Y'),
(15,5,16,'Y'),
(16,6,9,'Y'),
(17,6,10,'Y'),
(18,6,11,'Y'),
(19,6,12,'Y'),
(20,7,17,'Y'),
(21,9,17,'Y'),
(22,8,17,'Y'),
(23,4,18,'Y'),
(24,4,19,'Y'),
(25,4,20,'Y'),
(26,4,21,'Y'),
(27,4,22,'Y'),
(28,4,23,'Y');

CREATE  TABLE IF NOT EXISTS `{tbls3}user_to_role` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `user_id` INT(10) UNSIGNED NOT NULL ,
  `role_id` INT(10) UNSIGNED NOT NULL ,
  `date_added` DATETIME NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_to_role_user1` (`user_id` ASC) ,
  INDEX `fk_user_to_role_user_role1` (`role_id` ASC) ,
  CONSTRAINT `fk_user_to_role_user1`
    FOREIGN KEY (`user_id` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_to_role_user_role1`
    FOREIGN KEY (`role_id` )
    REFERENCES `{tbls3}user_role` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `{tbls3}workflow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dokument_id` int(11) NOT NULL,
  `prideleno_id` int(10) unsigned DEFAULT NULL,
  `orgjednotka_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `stav_dokumentu` int(11) NOT NULL DEFAULT '0',
  `stav_osoby` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0=neprirazen,1=prirazen,2=dokoncen,100>storno',
  `date` datetime NOT NULL,
  `poznamka` text,
  `date_predani` datetime DEFAULT NULL,
  `aktivni` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`) ,
  INDEX `fk_workflow_dokument1` (`dokument_id` ASC) ,
  INDEX `fk_workflow_user1` (`prideleno_id` ASC) ,
  INDEX `fk_workflow_orgjednotka1` (`orgjednotka_id` ASC) ,
  INDEX `fk_workflow_user2` (`user_id` ASC) ,
  CONSTRAINT `fk_workflow_dokument1`
    FOREIGN KEY (`dokument_id` )
    REFERENCES `{tbls3}dokument` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_workflow_user1`
    FOREIGN KEY (`prideleno_id` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_workflow_orgjednotka1`
    FOREIGN KEY (`orgjednotka_id` )
    REFERENCES `{tbls3}orgjednotka` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_workflow_user2`
    FOREIGN KEY (`user_id` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `{tbls3}dokument_historie` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `dokument_id` INT(11) NOT NULL ,
  `dokument_typ_id` INT(11) NOT NULL ,
  `zpusob_doruceni_id` INT(10) UNSIGNED NULL ,
  `cislo_jednaci_id` INT(11) NULL ,
  `zpusob_vyrizeni_id` INT(10) UNSIGNED NULL ,
  `zmocneni_id` INT(11) NULL ,
  `spousteci_udalost_id` INT(11) NULL ,
  `jid` VARCHAR(100) NOT NULL ,
  `nazev` VARCHAR(100) NOT NULL ,
  `popis` VARCHAR(255) NULL ,
  `cislo_jednaci` VARCHAR(50) NULL ,
  `poradi` SMALLINT(6) NULL DEFAULT '1' ,
  `cislo_jednaci_odesilatele` VARCHAR(50) NULL ,
  `podaci_denik` VARCHAR(45) NULL ,
  `podaci_denik_poradi` INT(11) NULL DEFAULT NULL ,
  `podaci_denik_rok` YEAR NULL DEFAULT NULL ,
  `spisovy_plan` VARCHAR(45) NULL DEFAULT NULL ,
  `spisovy_znak_id` INT(11) NULL DEFAULT NULL ,
  `skartacni_znak` ENUM('A','S','V') NULL DEFAULT NULL ,
  `skartacni_lhuta` INT(11) NULL DEFAULT NULL ,
  `poznamka` TEXT NULL ,
  `lhuta` TINYINT(4) NOT NULL DEFAULT '30' ,
  `epodatelna_id` INT(11) NULL DEFAULT NULL ,
  `stav` TINYINT(4) NOT NULL DEFAULT '1' ,
  `md5_hash` VARCHAR(45) NOT NULL ,
  `date_created` DATETIME NOT NULL ,
  `user_created` INT(10) UNSIGNED NOT NULL ,
  `datum_vzniku` DATETIME NULL DEFAULT NULL ,
  `pocet_listu` INT(11) NULL DEFAULT NULL ,
  `pocet_priloh` INT(11) NULL DEFAULT NULL ,
  `vyrizeni_pocet_listu` INT(11) NULL DEFAULT NULL ,
  `vyrizeni_pocet_priloh` INT(11) NULL DEFAULT NULL ,
  `ulozeni_dokumentu` TEXT NULL ,
  `datum_vyrizeni` DATETIME NULL ,
  `poznamka_vyrizeni` TEXT NULL ,
  `datum_spousteci_udalosti` DATETIME NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_dokument_dokument_typ` (`dokument_typ_id` ASC) ,
  INDEX `fk_dokument_cislo_jednaci1` (`cislo_jednaci_id` ASC) ,
  INDEX `fk_dokument_zpusob_vyrizeni1` (`zpusob_vyrizeni_id` ASC) ,
  INDEX `fk_dokument_zmocneni1` (`zmocneni_id` ASC) ,
  INDEX `fk_dokument_spousteci_udalost1` (`spousteci_udalost_id` ASC) ,
  INDEX `fk_dokument_user1` (`user_created` ASC) ,
  INDEX `fk_dokument_zpusob_doruceni1` (`zpusob_doruceni_id` ASC) ,
  INDEX `fk_dokument_historie_dokument1` (`dokument_id` ASC) ,
  CONSTRAINT `fk_dokument_dokument_typ0`
    FOREIGN KEY (`dokument_typ_id` )
    REFERENCES `{tbls3}dokument_typ` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_cislo_jednaci10`
    FOREIGN KEY (`cislo_jednaci_id` )
    REFERENCES `{tbls3}cislo_jednaci` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_zpusob_vyrizeni10`
    FOREIGN KEY (`zpusob_vyrizeni_id` )
    REFERENCES `{tbls3}zpusob_vyrizeni` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_zmocneni10`
    FOREIGN KEY (`zmocneni_id` )
    REFERENCES `{tbls3}zmocneni` (`id` )
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
  CONSTRAINT `fk_dokument_zpusob_doruceni10`
    FOREIGN KEY (`zpusob_doruceni_id` )
    REFERENCES `{tbls3}zpusob_doruceni` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_dokument_historie_dokument1`
    FOREIGN KEY (`dokument_id` )
    REFERENCES `{tbls3}dokument` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

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
  `date_created` DATETIME NULL ,
  `user_created` INT(10) UNSIGNED NOT NULL ,
  `guid` VARCHAR(45) NOT NULL COMMENT 'jednoznacny identifikator' ,
  `md5_hash` VARCHAR(45) NOT NULL COMMENT 'otisk souboru pro overeni pravosti' ,
  `size` INT(11) NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_file_user1` (`user_created` ASC) ,
  INDEX `fk_file_historie_file1` (`file_id` ASC) ,
  CONSTRAINT `fk_file_user10`
    FOREIGN KEY (`user_created` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_file_historie_file1`
    FOREIGN KEY (`file_id` )
    REFERENCES `{tbls3}file` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `{tbls3}subjekt_historie` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `subjekt_id` INT(11) NOT NULL ,
  `stav` TINYINT(4) NOT NULL DEFAULT '1' ,
  `type` VARCHAR(15) NOT NULL ,
  `ic` VARCHAR(8) NULL ,
  `dic` VARCHAR(12) NULL ,
  `nazev_subjektu` VARCHAR(255) NULL ,
  `jmeno` VARCHAR(24) NULL ,
  `prijmeni` VARCHAR(35) NULL ,
  `prostredni_jmeno` VARCHAR(35) NULL ,
  `titul_pred` VARCHAR(35) NULL ,
  `titul_za` VARCHAR(10) NULL ,
  `rodne_jmeno` VARCHAR(35) NULL ,
  `datum_narozeni` DATE NULL DEFAULT NULL ,
  `misto_narozeni` VARCHAR(48) NULL ,
  `okres_narozeni` VARCHAR(48) NULL ,
  `stat_narozeni` VARCHAR(3) NULL ,
  `adresa_mesto` VARCHAR(48) NULL ,
  `adresa_ulice` VARCHAR(48) NULL ,
  `adresa_cp` VARCHAR(10) NULL ,
  `adresa_co` VARCHAR(10) NULL ,
  `adresa_psc` VARCHAR(10) NULL ,
  `adresa_stat` VARCHAR(3) NULL ,
  `narodnost` VARCHAR(80) NULL ,
  `email` VARCHAR(250) NULL ,
  `telefon` VARCHAR(150) NULL ,
  `id_isds` VARCHAR(50) NULL ,
  `poznamka` TEXT NULL ,
  `date_created` DATETIME NULL DEFAULT NULL ,
  `user_created` INT(10) UNSIGNED NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_subjekt_user1` (`user_created` ASC) ,
  INDEX `fk_subjekt_historie_subjekt1` (`subjekt_id` ASC) ,
  CONSTRAINT `fk_subjekt_user10`
    FOREIGN KEY (`user_created` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_subjekt_historie_subjekt1`
    FOREIGN KEY (`subjekt_id` )
    REFERENCES `{tbls3}subjekt` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `{tbls3}osoba_historie` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `osoba_id` INT(11) NOT NULL ,
  `prijmeni` VARCHAR(255) NOT NULL ,
  `jmeno` VARCHAR(150) BINARY NULL DEFAULT NULL ,
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
  CONSTRAINT `fk_osoba_user10`
    FOREIGN KEY (`user_created` )
    REFERENCES `{tbls3}user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_osoba_historie_osoba1`
    FOREIGN KEY (`osoba_id` )
    REFERENCES `{tbls3}osoba` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}zapujcka` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dokument_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `user_vytvoril_id` int(10) unsigned NOT NULL,
  `user_prijal_id` int(10) unsigned DEFAULT NULL,
  `user_schvalil_id` int(10) unsigned DEFAULT NULL,
  `duvod` text,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `date_od` date NOT NULL,
  `date_do` date DEFAULT NULL,
  `date_do_skut` date DEFAULT NULL,
  `date_created` datetime NOT NULL,
  `date_schvaleni` datetime DEFAULT NULL,
  `date_prijeti` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dokument_id` (`dokument_id`),
  KEY `user_id` (`user_id`),
  KEY `user_vytvoril_id` (`user_vytvoril_id`),
  KEY `user_prijal_id` (`user_prijal_id`),
  KEY `user_schvalil_id` (`user_schvalil_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
