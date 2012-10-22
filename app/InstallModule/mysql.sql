SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET foreign_key_checks = 0;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE IF NOT EXISTS `{tbls3}cislo_jednaci` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `podaci_denik` varchar(80) NOT NULL DEFAULT 'default',
  `rok` year(4) NOT NULL,
  `poradove_cislo` int(11) DEFAULT NULL,
  `urad_zkratka` varchar(50) DEFAULT NULL,
  `urad_poradi` int(11) DEFAULT NULL,
  `orgjednotka_id` int(10) unsigned DEFAULT NULL,
  `org_poradi` int(11) DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `user_poradi` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_cislo_jednaci_user1` (`user_id`),
  KEY `fk_cislo_jednaci_orgjednotka1` (`orgjednotka_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}dokument` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dokument_typ_id` int(11) NOT NULL,
  `zpusob_doruceni_id` int(10) unsigned DEFAULT NULL,
  `cislo_jednaci_id` int(11) DEFAULT NULL,
  `zpusob_vyrizeni_id` int(10) unsigned DEFAULT NULL,
  `zmocneni_id` int(11) DEFAULT NULL,
  `spousteci_udalost_id` int(11) DEFAULT NULL,
  `jid` varchar(100) NOT NULL,
  `nazev` varchar(100) NOT NULL,
  `popis` text,
  `cislo_jednaci` varchar(50) DEFAULT NULL,
  `poradi` smallint(6) DEFAULT '1',
  `cislo_jednaci_odesilatele` varchar(50) DEFAULT NULL,
  `podaci_denik` varchar(45) DEFAULT NULL,
  `podaci_denik_poradi` int(11) DEFAULT NULL,
  `podaci_denik_rok` year(4) DEFAULT NULL,
  `spisovy_plan` varchar(45) DEFAULT NULL,
  `spisovy_znak_id` int(11) DEFAULT NULL,
  `skartacni_znak` enum('A','S','V') DEFAULT NULL,
  `skartacni_lhuta` int(11) DEFAULT NULL,
  `poznamka` text,
  `lhuta` smallint(6) NOT NULL DEFAULT '30',
  `epodatelna_id` int(11) DEFAULT NULL,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `md5_hash` varchar(45) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `user_created` int(10) unsigned NOT NULL,
  `date_modified` datetime DEFAULT NULL,
  `user_modified` int(10) unsigned DEFAULT NULL,
  `datum_vzniku` datetime NOT NULL,
  `pocet_listu` int(11) DEFAULT NULL,
  `pocet_priloh` int(11) DEFAULT NULL,
  `typ_prilohy` varchar(150) DEFAULT NULL,
  `vyrizeni_pocet_listu` int(11) DEFAULT NULL,
  `vyrizeni_pocet_priloh` int(11) DEFAULT NULL,
  `vyrizeni_typ_prilohy` varchar(150) DEFAULT NULL,
  `ulozeni_dokumentu` text,
  `datum_vyrizeni` datetime DEFAULT NULL,
  `poznamka_vyrizeni` text,
  `datum_spousteci_udalosti` datetime DEFAULT NULL,
  `cislo_doporuceneho_dopisu` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_dokument_dokument_typ` (`dokument_typ_id`),
  KEY `fk_dokument_cislo_jednaci1` (`cislo_jednaci_id`),
  KEY `fk_dokument_zpusob_vyrizeni1` (`zpusob_vyrizeni_id`),
  KEY `fk_dokument_zmocneni1` (`zmocneni_id`),
  KEY `fk_dokument_spousteci_udalost1` (`spousteci_udalost_id`),
  KEY `fk_dokument_user1` (`user_created`),
  KEY `fk_dokument_user2` (`user_modified`),
  KEY `fk_dokument_zpusob_doruceni1` (`zpusob_doruceni_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}dokument_historie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dokument_id` int(11) NOT NULL,
  `dokument_typ_id` int(11) NOT NULL,
  `zpusob_doruceni_id` int(10) unsigned DEFAULT NULL,
  `cislo_jednaci_id` int(11) DEFAULT NULL,
  `zpusob_vyrizeni_id` int(10) unsigned DEFAULT NULL,
  `zmocneni_id` int(11) DEFAULT NULL,
  `spousteci_udalost_id` int(11) DEFAULT NULL,
  `jid` varchar(100) NOT NULL,
  `nazev` varchar(100) NOT NULL,
  `popis` text,
  `cislo_jednaci` varchar(50) DEFAULT NULL,
  `poradi` smallint(6) DEFAULT '1',
  `cislo_jednaci_odesilatele` varchar(50) DEFAULT NULL,
  `podaci_denik` varchar(45) DEFAULT NULL,
  `podaci_denik_poradi` int(11) DEFAULT NULL,
  `podaci_denik_rok` year(4) DEFAULT NULL,
  `spisovy_plan` varchar(45) DEFAULT NULL,
  `spisovy_znak_id` int(11) DEFAULT NULL,
  `skartacni_znak` enum('A','S','V') DEFAULT NULL,
  `skartacni_lhuta` int(11) DEFAULT NULL,
  `poznamka` text,
  `lhuta` tinyint(4) NOT NULL DEFAULT '30',
  `epodatelna_id` int(11) DEFAULT NULL,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `md5_hash` varchar(45) NOT NULL,
  `date_created` datetime NOT NULL,
  `user_created` int(10) unsigned NOT NULL,
  `datum_vzniku` datetime NOT NULL,
  `pocet_listu` int(11) DEFAULT NULL,
  `pocet_priloh` int(11) DEFAULT NULL,
  `typ_prilohy` varchar(150) DEFAULT NULL,
  `vyrizeni_pocet_listu` int(11) DEFAULT NULL,
  `vyrizeni_pocet_priloh` int(11) DEFAULT NULL,
  `vyrizeni_typ_prilohy` varchar(150) DEFAULT NULL,
  `ulozeni_dokumentu` text,
  `datum_vyrizeni` datetime DEFAULT NULL,
  `poznamka_vyrizeni` text,
  `datum_spousteci_udalosti` datetime DEFAULT NULL,
  `cislo_doporuceneho_dopisu` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_dokument_dokument_typ` (`dokument_typ_id`),
  KEY `fk_dokument_cislo_jednaci1` (`cislo_jednaci_id`),
  KEY `fk_dokument_zpusob_vyrizeni1` (`zpusob_vyrizeni_id`),
  KEY `fk_dokument_zmocneni1` (`zmocneni_id`),
  KEY `fk_dokument_spousteci_udalost1` (`spousteci_udalost_id`),
  KEY `fk_dokument_user1` (`user_created`),
  KEY `fk_dokument_zpusob_doruceni1` (`zpusob_doruceni_id`),
  KEY `fk_dokument_historie_dokument1` (`dokument_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}dokument_odeslani` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dokument_id` int(11) NOT NULL,
  `subjekt_id` int(11) NOT NULL,
  `zpusob_odeslani_id` int(10) unsigned NOT NULL,
  `epodatelna_id` int(11) DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `datum_odeslani` datetime NOT NULL,
  `zprava` text,
  `druh_zasilky` varchar(200) DEFAULT NULL,
  `cena` float DEFAULT NULL,
  `hmotnost` float DEFAULT NULL,
  `cislo_faxu` varchar(100) DEFAULT NULL,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `date_created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_dokument_odeslani_dokument1` (`dokument_id`),
  KEY `fk_dokument_odeslani_subjekt1` (`subjekt_id`),
  KEY `fk_dokument_odeslani_user1` (`user_id`),
  KEY `fk_dokument_odeslani_zpusob_odeslani1` (`zpusob_odeslani_id`),
  KEY `fk_dokument_odeslani_epodatelna1` (`epodatelna_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}dokument_to_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dokument_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_dokument_to_file_dokument1` (`dokument_id`),
  KEY `fk_dokument_to_file_file1` (`file_id`),
  KEY `fk_dokument_to_file_user1` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}dokument_to_spis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dokument_id` int(11) NOT NULL,
  `spis_id` int(11) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `poradi` int(11) NOT NULL DEFAULT '1',
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `date_added` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_dokument_to_spis_dokument1` (`dokument_id`),
  KEY `fk_dokument_to_spis_spis1` (`spis_id`),
  KEY `fk_dokument_to_spis_user1` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}dokument_to_subjekt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dokument_id` int(11) NOT NULL,
  `subjekt_id` int(11) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `typ` enum('A','O','AO') NOT NULL DEFAULT 'AO',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_dokument_to_subjekt_dokument1` (`dokument_id`),
  KEY `fk_dokument_to_subjekt_subjekt1` (`subjekt_id`),
  KEY `fk_dokument_to_subjekt_user1` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}dokument_typ` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` varchar(100) NOT NULL,
  `popis` varchar(255) DEFAULT NULL,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `smer` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0-prichozi, 1-odchozi',
  `podatelna` tinyint(1) NOT NULL DEFAULT '1',
  `referent` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `{tbls3}dokument_typ` (`id`, `nazev`, `popis`, `stav`, `smer`, `podatelna`, `referent`) VALUES
(1, 'příchozí', NULL, 1, 0, 1, 0),
(2, 'vlastní', NULL, 1, 1, 0, 1);

CREATE TABLE IF NOT EXISTS `{tbls3}druh_zasilky` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` varchar(150) NOT NULL,
  `fixed` tinyint(4) NOT NULL DEFAULT '0',
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `{tbls3}druh_zasilky` (`id`, `nazev`, `fixed`, `stav`) VALUES
(1, 'obyčejné', 1, 1),
(2, 'doporučené', 1, 1),
(3, 'balík', 1, 1),
(4, 'do vlastních rukou', 1, 1),
(5, 'dodejka', 1, 1),
(6, 'cenné psaní', 1, 1),
(7, 'cizina', 1, 1),
(8, 'EMS', 1, 1),
(9, 'dobírka', 1, 1);

CREATE TABLE IF NOT EXISTS `{tbls3}epodatelna` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dokument_id` int(11) DEFAULT NULL,
  `odesilatel_id` int(11) DEFAULT NULL,
  `file_id` int(11) DEFAULT NULL,
  `epodatelna_typ` tinyint(4) NOT NULL DEFAULT '0',
  `poradi` int(11) DEFAULT NULL,
  `rok` year(4) DEFAULT NULL,
  `email_signature` varchar(200) DEFAULT NULL,
  `isds_signature` varchar(45) DEFAULT NULL,
  `predmet` varchar(200) NOT NULL DEFAULT '',
  `popis` text,
  `odesilatel` varchar(200) NOT NULL DEFAULT '',
  `adresat` varchar(100) NOT NULL DEFAULT '',
  `prijato_dne` datetime DEFAULT NULL,
  `doruceno_dne` datetime DEFAULT NULL,
  `prijal_kdo` int(11) DEFAULT NULL,
  `prijal_info` text,
  `sha1_hash` varchar(50) NOT NULL,
  `prilohy` text,
  `identifikator` text,
  `evidence` varchar(100) DEFAULT NULL,
  `stav` tinyint(4) NOT NULL DEFAULT '0',
  `stav_info` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_epodatelna_dokument1` (`dokument_id`),
  KEY `fk_epodatelna_file1` (`file_id`),
  KEY `fk_epodatelna_subjekt1` (`odesilatel_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `typ` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'typ prilohy. Defaultne: (1)main, (2)enclosure, (3)signature, (4)meta, (5)source',
  `nazev` varchar(255) NOT NULL COMMENT 'jmeno souboru nebo nazev',
  `popis` varchar(255) DEFAULT NULL,
  `mime_type` varchar(60) DEFAULT NULL COMMENT 'mime typ souboru',
  `real_name` varchar(255) NOT NULL COMMENT 'skutečné jmeno souboru file.ext',
  `real_path` varchar(255) NOT NULL COMMENT 'realna cesta k souboru ',
  `real_type` varchar(45) NOT NULL DEFAULT 'FILE' COMMENT 'typ fyzickeho mista. Default FILE - lokalni fyzicke misto',
  `date_created` datetime DEFAULT NULL,
  `user_created` int(10) unsigned NOT NULL,
  `date_modified` datetime DEFAULT NULL,
  `user_modified` int(10) unsigned NOT NULL,
  `guid` varchar(45) NOT NULL COMMENT 'jednoznacny identifikator',
  `md5_hash` varchar(45) NOT NULL COMMENT 'otisk souboru pro overeni pravosti',
  `size` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_file_user1` (`user_created`),
  KEY `fk_file_user2` (`user_modified`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}file_historie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `typ` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'typ prilohy. Defaultne: (1)main, (2)enclosure, (3)signature, (4)meta, (5)source',
  `nazev` varchar(255) NOT NULL COMMENT 'jmeno souboru nebo nazev',
  `popis` varchar(45) DEFAULT NULL,
  `mime_type` varchar(60) DEFAULT NULL COMMENT 'mime typ souboru',
  `real_name` varchar(255) NOT NULL COMMENT 'skutečné jmeno souboru file.ext',
  `real_path` varchar(255) NOT NULL COMMENT 'realna cesta k souboru ',
  `real_type` varchar(45) NOT NULL DEFAULT 'FILE' COMMENT 'typ fyzickeho mista. Default FILE - lokalni fyzicke misto',
  `date_created` datetime DEFAULT NULL,
  `user_created` int(10) unsigned NOT NULL,
  `guid` varchar(45) NOT NULL COMMENT 'jednoznacny identifikator',
  `md5_hash` varchar(45) NOT NULL COMMENT 'otisk souboru pro overeni pravosti',
  `size` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_file_user1` (`user_created`),
  KEY `fk_file_historie_file1` (`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}log_access` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime DEFAULT NULL,
  `ip` varchar(15) DEFAULT NULL,
  `user_agent` varchar(200) DEFAULT NULL,
  `stav` tinyint(4) DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_log_access_user1` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}log_dokument` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dokument_id` int(11) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `typ` tinyint(4) NOT NULL,
  `poznamka` text,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_log_dokument_dokument1` (`dokument_id`),
  KEY `fk_log_dokument_user1` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}orgjednotka` (
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
  PRIMARY KEY (`id`),
  KEY `fk_orgjednotka_user1` (`user_created`),
  KEY `fk_orgjednotka_user2` (`user_modified`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}osoba` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prijmeni` varchar(255) NOT NULL,
  `jmeno` varchar(150) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `titul_pred` varchar(50) DEFAULT NULL,
  `titul_za` varchar(50) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `pozice` varchar(50) DEFAULT NULL,
  `stav` tinyint(4) NOT NULL,
  `date_created` datetime NOT NULL,
  `user_created` int(10) unsigned NOT NULL,
  `date_modified` datetime DEFAULT NULL,
  `user_modified` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_osoba_user1` (`user_created`),
  KEY `fk_osoba_user2` (`user_modified`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}osoba_historie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `osoba_id` int(11) NOT NULL,
  `prijmeni` varchar(255) NOT NULL,
  `jmeno` varchar(150) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `titul_pred` varchar(50) DEFAULT NULL,
  `titul_za` varchar(50) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `pozice` varchar(50) DEFAULT NULL,
  `stav` tinyint(4) NOT NULL,
  `date_created` datetime NOT NULL,
  `user_created` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_osoba_user1` (`user_created`),
  KEY `fk_osoba_historie_osoba1` (`osoba_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}osoba_to_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `osoba_id` int(11) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `date_added` datetime NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_osoba_to_user_osoba1` (`osoba_id`),
  KEY `fk_osoba_to_user_user1` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}sestava` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` varchar(60) NOT NULL,
  `popis` varchar(150) DEFAULT NULL,
  `parametry` text,
  `sloupce` text,
  `typ` tinyint(4) NOT NULL DEFAULT '1',
  `filtr` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `{tbls3}sestava` (`id`, `nazev`, `popis`, `parametry`, `sloupce`, `typ`, `filtr`) VALUES
(1, 'Podací deník', NULL, NULL, NULL, 2, 1);

CREATE TABLE IF NOT EXISTS `{tbls3}souvisejici_dokument` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dokument_id` int(11) NOT NULL,
  `spojit_s_id` int(11) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `date_added` datetime NOT NULL,
  `type` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_souvisejici_dokument_dokument1` (`dokument_id`),
  KEY `fk_souvisejici_dokument_dokument2` (`spojit_s_id`),
  KEY `fk_souvisejici_dokument_user1` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}spis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `spousteci_udalost_id` int(11) DEFAULT NULL,
  `spisovy_znak_id` int(10) DEFAULT NULL,
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
  `orgjednotka_id` int(10) unsigned DEFAULT NULL,
  `orgjednotka_id_predano` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_spis_spousteci_udalost1` (`spousteci_udalost_id`),
  KEY `fk_spis_spis1` (`parent_id`),
  KEY `fk_spis_user1` (`user_created`),
  KEY `fk_spis_user2` (`user_modified`),
  KEY `spisovy_znak_id` (`spisovy_znak_id`),
  KEY `orgjednotka_id` (`orgjednotka_id`),
  KEY `orgjednotka_id_predano` (`orgjednotka_id_predano`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `{tbls3}spis` (
`id`,`parent_id`,`spousteci_udalost_id`,`spisovy_znak_id`,`nazev`,`popis`,`typ`,`sekvence`,`sekvence_string`,`uroven`,`stav`,`date_created`,`user_created`,`date_modified`,`user_modified`,`spisovy_znak`,`spisovy_znak_plneurceny`,`skartacni_znak`,`skartacni_lhuta`,`datum_otevreni`,`datum_uzavreni`,`orgjednotka_id`,`orgjednotka_id_predano` )
VALUES (1 , NULL , 1, NULL , 'SPISY', '', 'SP', '1', 'SPISY.1', 0, 1, NOW(), 1, NULL , NULL , NULL , NULL , 'V', '100', NOW(), NULL, NULL, NULL );

CREATE TABLE IF NOT EXISTS `{tbls3}spisovy_znak` (
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
  `selected` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_spisovy_znak_spousteci_udalost1` (`spousteci_udalost_id`),
  KEY `fk_spisovy_znak_spisovy_znak1` (`parent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}spousteci_udalost` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` text NOT NULL,
  `poznamka` text,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `poznamka_k_datumu` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `{tbls3}spousteci_udalost` (`id`, `nazev`, `poznamka`, `stav`, `poznamka_k_datumu`) VALUES
(1, 'Skartační lhůta začíná plynout po ztrátě platnosti dokumentu.', NULL, 1,'ukončení platnosti dokumentu'),
(2, 'Skartační lhůta začíná plynout po ukončení záruky.', NULL, 1,'ukončení záruky'),
(3, 'Skartační lhůta začíná plynout po uzavření dokumentu.', NULL, 2,'uzavření/vyřízení dokumentu'),
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

CREATE TABLE IF NOT EXISTS `{tbls3}subjekt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `type` varchar(15) NOT NULL,
  `ic` varchar(8) DEFAULT NULL,
  `dic` varchar(12) DEFAULT NULL,
  `nazev_subjektu` varchar(255) DEFAULT NULL,
  `jmeno` varchar(24) DEFAULT NULL,
  `prijmeni` varchar(35) DEFAULT NULL,
  `prostredni_jmeno` varchar(35) DEFAULT NULL,
  `titul_pred` varchar(35) DEFAULT NULL,
  `titul_za` varchar(10) DEFAULT NULL,
  `rodne_jmeno` varchar(35) DEFAULT NULL,
  `datum_narozeni` date DEFAULT NULL,
  `misto_narozeni` varchar(48) DEFAULT NULL,
  `okres_narozeni` varchar(48) DEFAULT NULL,
  `stat_narozeni` varchar(3) DEFAULT NULL,
  `adresa_mesto` varchar(48) DEFAULT NULL,
  `adresa_ulice` varchar(48) DEFAULT NULL,
  `adresa_cp` varchar(10) DEFAULT NULL,
  `adresa_co` varchar(10) DEFAULT NULL,
  `adresa_psc` varchar(10) DEFAULT NULL,
  `adresa_stat` varchar(3) DEFAULT NULL,
  `narodnost` varchar(80) DEFAULT NULL,
  `email` varchar(250) DEFAULT NULL,
  `telefon` varchar(150) DEFAULT NULL,
  `id_isds` varchar(50) DEFAULT NULL,
  `poznamka` text,
  `date_created` datetime DEFAULT NULL,
  `user_created` int(10) unsigned NOT NULL,
  `date_modified` datetime DEFAULT NULL,
  `user_modified` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_subjekt_user1` (`user_created`),
  KEY `fk_subjekt_user2` (`user_modified`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}subjekt_historie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subjekt_id` int(11) NOT NULL,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `type` varchar(15) NOT NULL,
  `ic` varchar(8) DEFAULT NULL,
  `dic` varchar(12) DEFAULT NULL,
  `nazev_subjektu` varchar(255) DEFAULT NULL,
  `jmeno` varchar(24) DEFAULT NULL,
  `prijmeni` varchar(35) DEFAULT NULL,
  `prostredni_jmeno` varchar(35) DEFAULT NULL,
  `titul_pred` varchar(35) DEFAULT NULL,
  `titul_za` varchar(10) DEFAULT NULL,
  `rodne_jmeno` varchar(35) DEFAULT NULL,
  `datum_narozeni` date DEFAULT NULL,
  `misto_narozeni` varchar(48) DEFAULT NULL,
  `okres_narozeni` varchar(48) DEFAULT NULL,
  `stat_narozeni` varchar(3) DEFAULT NULL,
  `adresa_mesto` varchar(48) DEFAULT NULL,
  `adresa_ulice` varchar(48) DEFAULT NULL,
  `adresa_cp` varchar(10) DEFAULT NULL,
  `adresa_co` varchar(10) DEFAULT NULL,
  `adresa_psc` varchar(10) DEFAULT NULL,
  `adresa_stat` varchar(3) DEFAULT NULL,
  `narodnost` varchar(80) DEFAULT NULL,
  `email` varchar(250) DEFAULT NULL,
  `telefon` varchar(150) DEFAULT NULL,
  `id_isds` varchar(50) DEFAULT NULL,
  `poznamka` text,
  `date_created` datetime DEFAULT NULL,
  `user_created` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_subjekt_user1` (`user_created`),
  KEY `fk_subjekt_historie_subjekt1` (`subjekt_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `active` tinyint(4) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `last_modified` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `username` varchar(150) NOT NULL,
  `password` varchar(50) DEFAULT NULL,
  `last_ip` varchar(15) DEFAULT NULL,
  `local` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `USERNAME` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `{tbls3}user_acl` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(10) unsigned NOT NULL,
  `rule_id` int(10) unsigned NOT NULL,
  `allowed` enum('Y','N') NOT NULL DEFAULT 'Y',
  PRIMARY KEY (`id`),
  KEY `fk_user_acl_user_role1` (`role_id`),
  KEY `fk_user_acl_user_rule1` (`rule_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

INSERT INTO `{tbls3}user_acl` (`id`, `role_id`, `rule_id`, `allowed`) VALUES
(1, 1, 1, 'Y'),
(2, 2, 1, 'N'),
(3, 2, 2, 'Y'),
(4, 2, 3, 'Y'),
(5, 4, 5, 'Y'),
(6, 4, 4, 'Y'),
(7, 4, 7, 'Y'),
(8, 4, 14, 'Y'),
(9, 4, 8, 'Y'),
(10, 4, 13, 'Y'),
(11, 4, 6, 'Y'),
(12, 4, 2, 'Y'),
(13, 4, 3, 'Y'),
(14, 4, 15, 'Y'),
(15, 5, 16, 'Y'),
(16, 6, 9, 'Y'),
(17, 6, 10, 'Y'),
(18, 6, 11, 'Y'),
(19, 6, 12, 'Y'),
(23, 4, 18, 'Y'),
(24, 4, 19, 'Y'),
(25, 4, 20, 'Y'),
(26, 4, 21, 'Y'),
(27, 4, 22, 'Y'),
(28, 4, 23, 'Y'),
(29, 6, 24, 'Y'),
(30, 6, 5, 'Y'),
(31, 6, 4, 'Y'),
(32, 6, 18, 'Y'),
(33, 6, 7, 'Y'),
(34, 6, 14, 'Y'),
(35, 6, 8, 'Y'),
(36, 6, 13, 'Y'),
(37, 6, 6, 'Y'),
(38, 6, 2, 'Y'),
(39, 6, 3, 'Y'),
(40, 6, 15, 'Y'),
(41, 6, 19, 'Y'),
(42, 6, 20, 'Y'),
(43, 6, 21, 'Y'),
(44, 6, 22, 'Y'),
(45, 6, 23, 'Y'),
(46, 7, 5, 'Y'),
(47, 7, 4, 'Y'),
(48, 7, 18, 'Y'),
(49, 7, 7, 'Y'),
(50, 7, 14, 'Y'),
(51, 7, 8, 'Y'),
(52, 7, 13, 'Y'),
(53, 7, 6, 'Y'),
(54, 7, 2, 'Y'),
(55, 7, 3, 'Y'),
(56, 7, 15, 'Y'),
(57, 7, 19, 'Y'),
(58, 7, 20, 'Y'),
(59, 7, 21, 'Y'),
(60, 7, 22, 'Y'),
(61, 7, 23, 'Y'),
(62, 3, 1, 'Y');

CREATE TABLE IF NOT EXISTS `{tbls3}user_resource` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(150) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

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
(20, 'Spisovka_PrilohyPresenter', NULL, 'Přílohy'),
(21, 'Admin_EpodatelnaPresenter', NULL, 'Administrace - nastavení e-podatelny'),
(22, 'Epodatelna_PrilohyPresenter', NULL, 'Epodatelna - zobrazení přílohy'),
(23, 'Epodatelna_EvidencePresenter', NULL, 'Epodatelna - evidence'),
(24, 'Epodatelna_SubjektyPresenter', NULL, 'Epodatelna - subjekt'),
(25, 'Spisovka_SestavyPresenter', NULL, 'Sestavy'),
(26, 'Spisovka_SpojitPresenter', NULL, 'Spisovka - spojení dokumentů'),
(27, 'Spisovka_ErrorPresenter', NULL, 'Error'),
(28, 'Install_DefaultPresenter', NULL, 'Instalace'),
(29, 'Spisovka_VyhledatPresenter', NULL, 'Vyhledávání'),
(30, 'Admin_AkonverzePresenter', NULL, 'Administrace - Autorizovaná konverze'),
(31, 'Spisovka_NapovedaPresenter', NULL, 'Nápověda'),
(32, 'Spisovna_DefaultPresenter', NULL, 'Spisovna'),
(33, 'Spisovna_DokumentyPresenter', NULL, 'Spisovna - dokumenty'),
(34, 'Spisovna_SpisyPresenter', NULL, 'Spisovna - spisy'),
(35, 'Spisovna_VyhledatPresenter', NULL, 'Spisovna - vyhledávání'),
(36, 'Spisovna_ZapujckyPresenter', NULL, 'Spisovna - zápůjčky'),
(37, 'Spisovka_VypravnaPresenter', NULL, 'Výpravna'),
(38, 'Spisovka_ZpravyPresenter', NULL, 'Zprávy'),
(39, 'Spisovka_CronPresenter', 'Cron - zajišťuje opakované činnosti', 'Cron'),
(40, 'DatovaSchranka', NULL, 'Datová schránka');

CREATE TABLE IF NOT EXISTS `{tbls3}user_role` (
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
  PRIMARY KEY (`id`),
  KEY `fk_user_role_user_role1` (`parent_id`),
  KEY `fk_user_role_orgjednotka1` (`orgjednotka_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

INSERT INTO `{tbls3}user_role` (`id`, `parent_id`, `fixed_id`, `orgjednotka_id`, `code`, `name`, `note`, `fixed`, `order`, `active`, `date_created`, `date_modified`, `sekvence`, `sekvence_string`, `uroven`) VALUES
(1, NULL, NULL, NULL, 'admin', 'administrátor', 'Pracovník, který má na starost správu spisové služby', 2, 100, 1, NOW(), NOW(), '1', 'admin.1', NULL),
(2, NULL, NULL, NULL, 'guest', 'host', 'Role představující nepřihlášeného uživatele.\nTedy nastavení oprávnění v době, kdy k aplikaci není nikdo přihlášen.', 2, 0, 1, NOW(), NOW(), '2', 'guest.2', NULL),
(3, 1, NULL, NULL, 'superadmin', 'SuperAdmin', 'Administrátor se super právy.\nMůže manipulovat s jakýmikoli daty. Včetně dokumentů bez ohledu na vlastníka a stavu. ', 2, 100, 1, NOW(), NOW(), '3', 'superadmin.3', NULL),
(4, NULL, NULL, NULL, 'referent', 'pracovník', '(referent) Základní role pracovníka spisové služby', 1, 10, 1, NOW(), NOW(), '4', 'referent.4', NULL),
(5, 4, NULL, NULL, 'vedouci', 'sekretariát', '(vedoucí) Rozšířená role pracovníka spisové služby. Může nahlížet na podřízené uzly', 1, 50, 1, NOW(), NOW(), '5', 'vedouci.5', NULL),
(6, NULL, NULL, NULL, 'podatelna', 'pracovník podatelny', 'Pracovník podatelny, který může přijímat nebo odesílat dokumenty', 1, 20, 1, NOW(), NOW(), '6', 'podatelna.6', NULL),
(7, NULL, NULL, NULL, 'skartacni_dohled', 'pracovník spisovny', 'Má na starost spisovnu', 1, 30, 1, NOW(), NOW(), '7', 'skartacni_dohled.7', NULL),
(8, 4, NULL, NULL, 'skartacni_komise', 'člen skartační komise', 'člen skartační komise, která rozhoduje o skartaci nebo archivaci dokumentu.', 1, 40, 1, NOW(), NOW(), '8', 'skartacni_komise.8', NULL);

CREATE TABLE IF NOT EXISTS `{tbls3}user_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `resource_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `note` varchar(250) DEFAULT NULL,
  `privilege` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_user_rule_user_resource1` (`resource_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `{tbls3}user_rule` (`id`, `resource_id`, `name`, `note`, `privilege`) VALUES
(1, NULL, 'Bez omezení', NULL, NULL),
(2, 4, 'Přihlášení uživatele', NULL, 'login'),
(3, 4, 'Zobrazení úvodní obrazovky', NULL, NULL),
(4, 1, 'Zobrazení seznamu dokumentů', '', ''),
(5, 5, 'Základní obrazovka', '', ''),
(6, 19, 'Práce se subjekty', '', ''),
(7, 20, 'Práce s přílohami', '', ''),
(8, 18, 'Práce se spisy', '', ''),
(9, 2, 'Přístup do Epodatelny', '', ''),
(10, 22, 'E-podatelna - evidence', '', ''),
(11, 24, 'E-podatelna - přílohy', '', ''),
(12, 23, 'E-podatelna - subjekty', '', ''),
(13, 26, 'Spojování dokumentů', '', ''),
(14, 25, 'Sestavy', '', ''),
(15, 29, 'Vyhledávání', '', ''),
(16, NULL, 'Je vedoucí', 'Určuje, zda daná role je vedoucí role. Umožňuje přistupovat k dokumentům předané organizační jednotce', 'is_vedouci'),
(18, 31, 'Nápověda', 'Zobrazení nápovědy', ''),
(19, 32, 'Přístup do spisovny', '', ''),
(20, 33, 'Přístup k dokumentům ve spisovně', '', ''),
(21, 34, 'Přístup ke spisům ve spisovně', '', ''),
(22, 35, 'Vyhledávání dokumentů ve spisovně', '', ''),
(23, 36, 'Spisovna - zápůjčky', '', ''),
(24, 37, 'Výpravna', '', ''),
(25, 38, 'Zobrazení zpráv', '', ''),
(26, 39, 'Cron', '', ''),
(27, 40, 'Odesílání datových zpráv', '', 'odesilani');

CREATE TABLE IF NOT EXISTS `{tbls3}user_to_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_user_to_role_user1` (`user_id`),
  KEY `fk_user_to_role_user_role1` (`role_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}workflow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dokument_id` int(11) NOT NULL,
  `spis_id` int(11) DEFAULT NULL,
  `prideleno_id` int(10) unsigned DEFAULT NULL,
  `orgjednotka_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `stav_dokumentu` int(11) NOT NULL DEFAULT '0',
  `stav_osoby` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0=neprirazen,1=prirazen,2=dokoncen,100>storno',
  `date` datetime NOT NULL,
  `poznamka` text,
  `date_predani` datetime DEFAULT NULL,
  `aktivni` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_workflow_dokument1` (`dokument_id`),
  KEY `fk_workflow_user1` (`prideleno_id`),
  KEY `fk_workflow_orgjednotka1` (`orgjednotka_id`),
  KEY `fk_workflow_user2` (`user_id`),
  KEY `spis_id` (`spis_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}zmocneni` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cislo_zakona` int(11) NOT NULL COMMENT 'LegalTitleLaw',
  `rok_vydani` year(4) NOT NULL COMMENT 'LegaltitleYear',
  `paragraf` varchar(50) NOT NULL COMMENT 'LegalTitleSect',
  `odstavec` varchar(50) NOT NULL COMMENT 'LegalTitlePar',
  `pismeno` varchar(10) NOT NULL COMMENT 'LegaltitlePoint',
  `stav` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}zpusob_doruceni` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nazev` varchar(80) NOT NULL,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `fixed` tinyint(1) NOT NULL DEFAULT '0',
  `note` varchar(255) DEFAULT NULL,
  `epodatelna` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `{tbls3}zpusob_doruceni` (`id`, `nazev`, `stav`, `fixed`, `note`, `epodatelna`) VALUES
(1, 'emailem', 1, 1, NULL, 1),
(2, 'datovou schránkou', 1, 1, NULL, 1),
(3, 'datovým nosičem', 1, 1, NULL, 0),
(4, 'faxem', 1, 1, NULL, 0),
(5, 'v listinné podobě', 1, 1, NULL, 0);

CREATE TABLE IF NOT EXISTS `{tbls3}zpusob_odeslani` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nazev` varchar(80) NOT NULL,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `fixed` tinyint(4) NOT NULL DEFAULT '0',
  `note` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `{tbls3}zpusob_odeslani` (`id`, `nazev`, `stav`, `fixed`, `note`) VALUES
(1, 'emailem', 1, 1, NULL),
(2, 'datovou schránkou', 1, 1, NULL),
(3, 'poštou', 1, 1, NULL),
(4, 'faxem', 1, 1, ''),
(5, 'telefonicky', 1, 1, '');

CREATE TABLE IF NOT EXISTS `{tbls3}zpusob_vyrizeni` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nazev` varchar(80) NOT NULL,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `fixed` tinyint(1) NOT NULL DEFAULT '0',
  `note` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `{tbls3}zpusob_vyrizeni` (`id`, `nazev`, `stav`, `fixed`, `note`) VALUES
(1, 'vyřízení dokumentem', 1, 1, NULL),
(2, 'postoupení', 1, 1, NULL),
(3, 'vzetí na vědomí', 1, 1, NULL),
(4, 'jiný způsob', 1, 1, 'U tohoto způsobu je nutné vždy vyplnit poznámku k vyřízení.');

CREATE TABLE `{tbls3}stat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` varchar(150) NOT NULL,
  `kod` varchar(5) NOT NULL,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `{tbls3}stat` (`id`, `nazev`, `kod`, `stav`) VALUES
(1,	'Česká republika',	'CZE',	1),
(2,	'Slovenská republika',	'SVK',	1);

CREATE TABLE `{tbls3}zprava` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `zprava_typ_id` int(11) NOT NULL DEFAULT '1',
  `zprava` text NOT NULL,
  `date_created` datetime NOT NULL,
  `user_created` int(11) DEFAULT NULL COMMENT 'null=automat',
  `zobrazit_od` datetime DEFAULT NULL,
  `zobrazit_do` datetime DEFAULT NULL,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `uid` varchar(35) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}zprava_osoba` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `zprava_id` int(11) NOT NULL,
  `osoba_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `stav` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `zprava_id` (`zprava_id`),
  KEY `osoba_id` (`osoba_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;


ALTER TABLE `{tbls3}cislo_jednaci`
  ADD CONSTRAINT `fk_cislo_jednaci_orgjednotka1` FOREIGN KEY (`orgjednotka_id`) REFERENCES `{tbls3}orgjednotka` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_cislo_jednaci_user1` FOREIGN KEY (`user_id`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}dokument`
  ADD CONSTRAINT `fk_dokument_cislo_jednaci1` FOREIGN KEY (`cislo_jednaci_id`) REFERENCES `{tbls3}cislo_jednaci` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_dokument_typ` FOREIGN KEY (`dokument_typ_id`) REFERENCES `{tbls3}dokument_typ` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_spousteci_udalost1` FOREIGN KEY (`spousteci_udalost_id`) REFERENCES `{tbls3}spousteci_udalost` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_user1` FOREIGN KEY (`user_created`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_user2` FOREIGN KEY (`user_modified`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_zpusob_doruceni1` FOREIGN KEY (`zpusob_doruceni_id`) REFERENCES `{tbls3}zpusob_doruceni` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_zpusob_vyrizeni1` FOREIGN KEY (`zpusob_vyrizeni_id`) REFERENCES `{tbls3}zpusob_vyrizeni` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}dokument_historie`
  ADD CONSTRAINT `fk_dokument_cislo_jednaci10` FOREIGN KEY (`cislo_jednaci_id`) REFERENCES `{tbls3}cislo_jednaci` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_dokument_typ0` FOREIGN KEY (`dokument_typ_id`) REFERENCES `{tbls3}dokument_typ` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_historie_dokument1` FOREIGN KEY (`dokument_id`) REFERENCES `{tbls3}dokument` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_spousteci_udalost10` FOREIGN KEY (`spousteci_udalost_id`) REFERENCES `{tbls3}spousteci_udalost` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_user10` FOREIGN KEY (`user_created`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_zmocneni10` FOREIGN KEY (`zmocneni_id`) REFERENCES `{tbls3}zmocneni` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_zpusob_doruceni10` FOREIGN KEY (`zpusob_doruceni_id`) REFERENCES `{tbls3}zpusob_doruceni` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_zpusob_vyrizeni10` FOREIGN KEY (`zpusob_vyrizeni_id`) REFERENCES `{tbls3}zpusob_vyrizeni` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}dokument_odeslani`
  ADD CONSTRAINT `fk_dokument_odeslani_dokument1` FOREIGN KEY (`dokument_id`) REFERENCES `{tbls3}dokument` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_odeslani_epodatelna1` FOREIGN KEY (`epodatelna_id`) REFERENCES `{tbls3}epodatelna` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_odeslani_subjekt1` FOREIGN KEY (`subjekt_id`) REFERENCES `{tbls3}subjekt` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_odeslani_user1` FOREIGN KEY (`user_id`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_odeslani_zpusob_odeslani1` FOREIGN KEY (`zpusob_odeslani_id`) REFERENCES `{tbls3}zpusob_odeslani` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}dokument_to_file`
  ADD CONSTRAINT `fk_dokument_to_file_dokument1` FOREIGN KEY (`dokument_id`) REFERENCES `{tbls3}dokument` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_to_file_file1` FOREIGN KEY (`file_id`) REFERENCES `{tbls3}file` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_to_file_user1` FOREIGN KEY (`user_id`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}dokument_to_spis`
  ADD CONSTRAINT `fk_dokument_to_spis_dokument1` FOREIGN KEY (`dokument_id`) REFERENCES `{tbls3}dokument` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_to_spis_spis1` FOREIGN KEY (`spis_id`) REFERENCES `{tbls3}spis` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_to_spis_user1` FOREIGN KEY (`user_id`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}dokument_to_subjekt`
  ADD CONSTRAINT `fk_dokument_to_subjekt_dokument1` FOREIGN KEY (`dokument_id`) REFERENCES `{tbls3}dokument` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_to_subjekt_subjekt1` FOREIGN KEY (`subjekt_id`) REFERENCES `{tbls3}subjekt` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_dokument_to_subjekt_user1` FOREIGN KEY (`user_id`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}epodatelna`
  ADD CONSTRAINT `fk_epodatelna_dokument1` FOREIGN KEY (`dokument_id`) REFERENCES `{tbls3}dokument` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_epodatelna_file1` FOREIGN KEY (`file_id`) REFERENCES `{tbls3}file` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_epodatelna_subjekt1` FOREIGN KEY (`odesilatel_id`) REFERENCES `{tbls3}subjekt` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}file`
  ADD CONSTRAINT `fk_file_user1` FOREIGN KEY (`user_created`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_file_user2` FOREIGN KEY (`user_modified`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}file_historie`
  ADD CONSTRAINT `fk_file_historie_file1` FOREIGN KEY (`file_id`) REFERENCES `{tbls3}file` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_file_user10` FOREIGN KEY (`user_created`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}log_dokument`
  ADD CONSTRAINT `fk_log_dokument_dokument1` FOREIGN KEY (`dokument_id`) REFERENCES `{tbls3}dokument` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_log_dokument_user1` FOREIGN KEY (`user_id`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}log_spis`
  ADD CONSTRAINT `fk_log_spis_spis1` FOREIGN KEY (`spis_id`) REFERENCES `{tbls3}spis` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_log_spis_user1` FOREIGN KEY (`user_id`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}orgjednotka`
  ADD CONSTRAINT `fk_orgjednotka_user1` FOREIGN KEY (`user_created`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_orgjednotka_user2` FOREIGN KEY (`user_modified`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}osoba`
  ADD CONSTRAINT `fk_osoba_user1` FOREIGN KEY (`user_created`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_osoba_user2` FOREIGN KEY (`user_modified`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}osoba_historie`
  ADD CONSTRAINT `fk_osoba_historie_osoba1` FOREIGN KEY (`osoba_id`) REFERENCES `{tbls3}osoba` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_osoba_user10` FOREIGN KEY (`user_created`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}osoba_to_user`
  ADD CONSTRAINT `fk_osoba_to_user_osoba1` FOREIGN KEY (`osoba_id`) REFERENCES `{tbls3}osoba` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_osoba_to_user_user1` FOREIGN KEY (`user_id`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}souvisejici_dokument`
  ADD CONSTRAINT `fk_souvisejici_dokument_dokument1` FOREIGN KEY (`dokument_id`) REFERENCES `{tbls3}dokument` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_souvisejici_dokument_dokument2` FOREIGN KEY (`spojit_s_id`) REFERENCES `{tbls3}dokument` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_souvisejici_dokument_user1` FOREIGN KEY (`user_id`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}spis`
  ADD CONSTRAINT `fk_spis_spousteci_udalost1` FOREIGN KEY (`spousteci_udalost_id`) REFERENCES `{tbls3}spousteci_udalost` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_spis_user1` FOREIGN KEY (`user_created`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_spis_user2` FOREIGN KEY (`user_modified`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `spis_ibfk_1` FOREIGN KEY (`spisovy_znak_id`) REFERENCES `{tbls3}spisovy_znak` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}spisovy_znak`
  ADD CONSTRAINT `fk_spisovy_znak_spousteci_udalost1` FOREIGN KEY (`spousteci_udalost_id`) REFERENCES `{tbls3}spousteci_udalost` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `spisovy_znak_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `{tbls3}spisovy_znak` (`id`);

ALTER TABLE `{tbls3}subjekt`
  ADD CONSTRAINT `fk_subjekt_user1` FOREIGN KEY (`user_created`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_subjekt_user2` FOREIGN KEY (`user_modified`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}subjekt_historie`
  ADD CONSTRAINT `fk_subjekt_historie_subjekt1` FOREIGN KEY (`subjekt_id`) REFERENCES `{tbls3}subjekt` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_subjekt_user10` FOREIGN KEY (`user_created`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}user_acl`
  ADD CONSTRAINT `fk_user_acl_user_role1` FOREIGN KEY (`role_id`) REFERENCES `{tbls3}user_role` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `user_acl_ibfk_1` FOREIGN KEY (`rule_id`) REFERENCES `{tbls3}user_rule` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}user_role`
  ADD CONSTRAINT `fk_user_role_orgjednotka1` FOREIGN KEY (`orgjednotka_id`) REFERENCES `{tbls3}orgjednotka` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_user_role_user_role1` FOREIGN KEY (`parent_id`) REFERENCES `{tbls3}user_role` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}user_to_role`
  ADD CONSTRAINT `fk_user_to_role_user1` FOREIGN KEY (`user_id`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_user_to_role_user_role1` FOREIGN KEY (`role_id`) REFERENCES `{tbls3}user_role` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}workflow`
  ADD CONSTRAINT `fk_workflow_dokument1` FOREIGN KEY (`dokument_id`) REFERENCES `{tbls3}dokument` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_workflow_orgjednotka1` FOREIGN KEY (`orgjednotka_id`) REFERENCES `{tbls3}orgjednotka` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_workflow_user1` FOREIGN KEY (`prideleno_id`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_workflow_user2` FOREIGN KEY (`user_id`) REFERENCES `{tbls3}user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}zprava_osoba`
  ADD CONSTRAINT `zprava_osoba_ibfk_1` FOREIGN KEY (`zprava_id`) REFERENCES `{tbls3}zprava` (`id`),
  ADD CONSTRAINT `zprava_osoba_ibfk_2` FOREIGN KEY (`osoba_id`) REFERENCES `{tbls3}osoba` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
