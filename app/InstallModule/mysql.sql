-- -----------------------------------------------------------------------------
-- Pouziti vyhradne pro instalacni skript
--
-- V pripade rucni instalace nahradte {tbls3} za odpovidajici hodnotu - prefix nebo nic
-- -----------------------------------------------------------------------------;

CREATE TABLE `{tbls3}cislo_jednaci` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `podaci_denik` varchar(80) NOT NULL DEFAULT 'default',
  `rok` year(4) NOT NULL,
  `poradove_cislo` int(11) DEFAULT NULL,
  `urad_zkratka` varchar(50) DEFAULT '',
  `urad_poradi` int(11) DEFAULT NULL,
  `orgjednotka_id` int(11) DEFAULT NULL,
  `org_poradi` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_poradi` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orgjednotka_id` (`orgjednotka_id`),
  KEY `user_id` (`user_id`),
  KEY `urad_zkratka` (`urad_zkratka`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `{tbls3}dokument` (
  `id` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `jid` varchar(100) NOT NULL,
  `nazev` varchar(100) NOT NULL,
  `popis` varchar(255) DEFAULT '',
  `cislojednaci_id` int(11) DEFAULT NULL,
  `cislo_jednaci` varchar(50) DEFAULT '',
  `poradi` smallint(6) NOT NULL DEFAULT '1',
  `cislo_jednaci_odesilatele` varchar(50) DEFAULT '',
  `podaci_denik` varchar(45) DEFAULT '',
  `podaci_denik_poradi` int(11) DEFAULT NULL,
  `podaci_denik_rok` year(4) DEFAULT NULL,
  `typ_dokumentu_id` int(11) DEFAULT NULL,
  `spisovy_plan` varchar(45) DEFAULT '',
  `spisovy_znak_id` int(11) DEFAULT NULL,
  `skartacni_znak` enum('A','S','V') DEFAULT NULL,
  `skartacni_lhuta` int(11) DEFAULT NULL,
  `poznamka` text,
  `zmocneni_id` int(11) DEFAULT NULL,
  `lhuta` tinyint(4) NOT NULL DEFAULT '30',
  `epodatelna_id` int(11) DEFAULT NULL,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `md5_hash` varchar(45) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `user_created` int(11) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `user_modified` int(11) DEFAULT NULL,
  `datum_vzniku` datetime NOT NULL,
  `pocet_listu` int(11) DEFAULT NULL,
  `pocet_priloh` int(11) DEFAULT NULL,
  `zpusob_doruceni_id` int(11) DEFAULT NULL,
  `zpusob_vyrizeni_id` int(11) DEFAULT NULL,
  `vyrizeni_pocet_listu` int(11) DEFAULT NULL,
  `vyrizeni_pocet_priloh` int(11) DEFAULT NULL,
  `ulozeni_dokumentu` text,
  `datum_vyrizeni` datetime DEFAULT NULL,
  `poznamka_vyrizeni` text,
  `spousteci_udalost` varchar(250) DEFAULT '',
  `datum_spousteci_udalosti` datetime DEFAULT NULL,
  PRIMARY KEY (`id`,`version`),
  KEY `cislojednaci_id` (`cislojednaci_id`),
  KEY `typ_dokumentu_id` (`typ_dokumentu_id`),
  KEY `spisovy_znak_id` (`spisovy_znak_id`),
  KEY `zmocneni_id` (`zmocneni_id`),
  KEY `zpusob_doruceni_id` (`zpusob_doruceni_id`),
  KEY `zpusob_vyrizeni_id` (`zpusob_vyrizeni_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `{tbls3}dokument_odeslani` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dokument_id` int(11) NOT NULL,
  `dokument_version` int(11) DEFAULT NULL,
  `subjekt_id` int(11) NOT NULL,
  `subjekt_version` int(11) DEFAULT NULL,
  `zpusob_odeslani` int(11) NOT NULL,
  `epodatelna_id` int(11) DEFAULT NULL,
  `datum_odeslani` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_info` text,
  `zprava` text,
  `date_created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dokument` (`dokument_id`,`dokument_version`),
  KEY `subjekt` (`subjekt_id`,`subjekt_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `{tbls3}dokument_to_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dokument_id` int(11) NOT NULL,
  `dokument_version` int(11) DEFAULT NULL,
  `file_id` int(11) NOT NULL,
  `file_version` int(11) DEFAULT NULL,
  `date_added` datetime DEFAULT NULL,
  `user_added` int(11) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `dokument` (`dokument_id`,`dokument_version`),
  KEY `file` (`file_id`,`file_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `{tbls3}dokument_to_spis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dokument_id` int(11) NOT NULL,
  `dokument_version` int(11) DEFAULT NULL,
  `spis_id` int(11) NOT NULL,
  `date_added` datetime DEFAULT NULL,
  `user_added` int(11) DEFAULT NULL,
  `poradi` int(11) NOT NULL DEFAULT '1',
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `dokument` (`dokument_id`,`dokument_version`),
  KEY `spis` (`spis_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `{tbls3}dokument_to_subjekt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dokument_id` int(11) NOT NULL,
  `dokument_version` int(11) DEFAULT NULL,
  `subjekt_id` int(11) NOT NULL,
  `subjekt_version` int(11) DEFAULT NULL,
  `typ` enum('A','O','AO') NOT NULL DEFAULT 'AO',
  `date_added` datetime DEFAULT NULL,
  `user_added` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dokument` (`dokument_id`,`dokument_version`),
  KEY `subjekt` (`subjekt_id`,`subjekt_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `{tbls3}dokument_typ` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` varchar(100) NOT NULL,
  `popis` varchar(255) DEFAULT '',
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `smer` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0-prichozi, 1-odchozi',
  `typ` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `{tbls3}dokument_typ` (`id`, `nazev`, `popis`, `stav`, `smer`, `typ`) VALUES
(1, 'příchozí', NULL, 1, 0, 0),
(2, 'vlastní', NULL, 1, 1, 0),
(3, 'odpověď', NULL, 1, 1, 0),
(4, 'příchozí - doručeno emailem', NULL, 1, 0, 1),
(5, 'příchozí - doručeno datovou schránkou', NULL, 1, 0, 2),
(6, 'příchozí - doručeno datovým nosičem', NULL, 1, 0, 3),
(7, 'příchozí - doručeno faxem', NULL, 1, 0, 4),
(8, 'příchozí - doručeno v listinné podobě', NULL, 1, 0, 0);

CREATE TABLE `{tbls3}epodatelna` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `epodatelna_typ` tinyint(4) NOT NULL DEFAULT '0',
  `poradi` int(11) DEFAULT NULL,
  `rok` year(4) DEFAULT NULL,
  `email_signature` varchar(200) DEFAULT NULL,
  `isds_signature` varchar(45) DEFAULT NULL,
  `predmet` varchar(200) NOT NULL DEFAULT '',
  `popis` text,
  `odesilatel` varchar(200) NOT NULL DEFAULT '',
  `odesilatel_id` int(11) DEFAULT NULL,
  `adresat` varchar(100) NOT NULL DEFAULT '',
  `prijato_dne` datetime DEFAULT NULL,
  `doruceno_dne` datetime DEFAULT NULL,
  `prijal_kdo` int(11) DEFAULT NULL,
  `prijal_info` text,
  `sha1_hash` varchar(50) NOT NULL,
  `prilohy` text,
  `evidence` varchar(100) DEFAULT '',
  `dokument_id` int(11) DEFAULT NULL,
  `stav` tinyint(4) NOT NULL DEFAULT '0',
  `stav_info` varchar(255) DEFAULT NULL,
  `source_id` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `{tbls3}file` (
  `id` int(11) NOT NULL,
  `version` tinyint(4) NOT NULL,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `typ` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'typ prilohy. Defaultne: (1)main, (2)enclosure, (3)signature, (4)meta, (5)source',
  `nazev` varchar(255) NOT NULL COMMENT 'jmeno souboru nebo nazev',
  `popis` varchar(45) DEFAULT '',
  `mime_type` varchar(60) DEFAULT '' COMMENT 'mime typ souboru',
  `real_name` varchar(255) NOT NULL COMMENT 'skutečné jmeno souboru file.ext',
  `real_path` varchar(255) NOT NULL COMMENT 'realna cesta k souboru ',
  `real_type` varchar(45) NOT NULL DEFAULT 'FILE' COMMENT 'typ fyzickeho mista. Default FILE - lokalni fyzicke misto',
  `date_created` datetime DEFAULT NULL,
  `user_created` int(11) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `user_modified` int(11) DEFAULT NULL,
  `guid` varchar(45) NOT NULL COMMENT 'jednoznacny identifikator',
  `md5_hash` varchar(45) NOT NULL COMMENT 'otisk souboru pro overeni pravosti',
  `size` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`,`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `{tbls3}log_access` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip` varchar(15) DEFAULT '',
  `user_agent` varchar(200) DEFAULT '',
  `stav` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `{tbls3}log_dokument` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `typ` tinyint(4) NOT NULL,
  `poznamka` text,
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `dokument_id` int(11) NOT NULL,
  `user_info` text,
  PRIMARY KEY (`id`),
  KEY `dokument` (`dokument_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `{tbls3}orgjednotka` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `plny_nazev` varchar(200) NOT NULL,
  `zkraceny_nazev` varchar(100) DEFAULT '',
  `ciselna_rada` varchar(30) NOT NULL,
  `note` text,
  `stav` tinyint(4) NOT NULL DEFAULT '0',
  `date_created` datetime DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `{tbls3}orgjednotka` (`id`, `plny_nazev`, `zkraceny_nazev`, `ciselna_rada`, `note`, `stav`, `date_created`, `date_modified`) VALUES
(1, 'Centrální podatelna', 'Centrální podatelna', 'POD', '', 1, NOW(), NULL);

CREATE TABLE `{tbls3}osoba` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prijmeni` varchar(255) NOT NULL,
  `jmeno` varchar(150) DEFAULT '',
  `titul_pred` varchar(50) DEFAULT '',
  `titul_za` varchar(50) DEFAULT '',
  `email` varchar(200) DEFAULT '',
  `telefon` varchar(20) DEFAULT '',
  `pozice` varchar(50) DEFAULT '',
  `stav` tinyint(4) NOT NULL,
  `date_created` datetime NOT NULL,
  `date_modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `{tbls3}osoba_to_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `osoba_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `date_added` datetime NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `osoba` (`osoba_id`),
  KEY `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `{tbls3}sestava` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` varchar(60) NOT NULL,
  `popis` varchar(150) DEFAULT '',
  `parametry` text,
  `sloupce` text,
  `typ` tinyint(4) NOT NULL DEFAULT '1',
  `filtr` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `{tbls3}sestava` (`id`, `nazev`, `popis`, `parametry`, `sloupce`, `typ`, `filtr`) VALUES
(1, 'Podací deník', NULL, NULL, NULL, 2, 1);

CREATE TABLE `{tbls3}souvisejici_dokument` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dokument_id` int(11) NOT NULL,
  `spojit_s` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `user_added` int(11) NOT NULL,
  `type` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `dokument` (`dokument_id`),
  KEY `spojit` (`spojit_s`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `{tbls3}spis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` varchar(80) NOT NULL,
  `popis` varchar(200) NOT NULL,
  `spisovy_znak` int(11) DEFAULT NULL,
  `typ` varchar(5) NOT NULL DEFAULT 'S',
  `spis_parent` int(11) DEFAULT NULL,
  `uroven` tinyint(4) DEFAULT '0',
  `sekvence` varchar(200) DEFAULT NULL,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `date_created` datetime DEFAULT NULL,
  `user_created` int(11) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `user_modified` int(11) DEFAULT NULL,
  `skartacni_znak` enum('A','S','V') DEFAULT NULL,
  `skartacni_lhuta` int(11) DEFAULT NULL,
  `spousteci_udalost` varchar(250) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `{tbls3}spis` (`id`, `nazev`, `popis`, `spisovy_znak`, `typ`, `spis_parent`, `uroven`, `sekvence`, `stav`, `date_created`, `user_created`, `date_modified`, `user_modified`, `skartacni_znak`, `skartacni_lhuta`, `spousteci_udalost`) VALUES
(1, 'Spisy', 'Nejvyšší větev spisové hierarchie', NULL, 'VS', NULL, 0, '', 1, NULL, NULL, NOW(), 1, NULL, NULL, NULL);

CREATE TABLE `{tbls3}spisovy_znak` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` varchar(80) NOT NULL,
  `popis` varchar(200) NOT NULL DEFAULT '',
  `spisznak_parent` int(11) DEFAULT NULL,
  `uroven` tinyint(4) DEFAULT '0',
  `sekvence` varchar(200) DEFAULT NULL,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `date_created` datetime DEFAULT NULL,
  `user_created` int(11) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `user_modified` int(11) DEFAULT NULL,
  `skartacni_znak` enum('A','S','V') DEFAULT NULL,
  `skartacni_lhuta` int(11) DEFAULT NULL,
  `spousteci_udalost` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `{tbls3}spousteci_udalost` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` text NOT NULL,
  `poznamka` text,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `poznamka_k_datumu` varchar(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

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


CREATE TABLE `{tbls3}subjekt` (
  `id` int(11) NOT NULL,
  `version` int(11) NOT NULL DEFAULT '1',
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  `type` varchar(15) NOT NULL,
  `ic` varchar(8) DEFAULT '',
  `dic` varchar(12) DEFAULT '',
  `nazev_subjektu` varchar(255) DEFAULT '',
  `jmeno` varchar(24) DEFAULT '',
  `prijmeni` varchar(35) DEFAULT '',
  `prostredni_jmeno` varchar(35) DEFAULT '',
  `titul_pred` varchar(35) DEFAULT '',
  `titul_za` varchar(10) DEFAULT '',
  `rodne_jmeno` varchar(35) DEFAULT '',
  `datum_narozeni` date DEFAULT NULL,
  `misto_narozeni` varchar(48) DEFAULT '',
  `okres_narozeni` varchar(48) DEFAULT '',
  `stat_narozeni` varchar(3) DEFAULT '',
  `adresa_mesto` varchar(48) DEFAULT '',
  `adresa_ulice` varchar(48) DEFAULT '',
  `adresa_cp` varchar(10) DEFAULT '',
  `adresa_co` varchar(10) DEFAULT '',
  `adresa_psc` varchar(10) DEFAULT '',
  `adresa_stat` varchar(3) DEFAULT '',
  `narodnost` varchar(80) DEFAULT '',
  `email` varchar(250) DEFAULT '',
  `telefon` varchar(150) DEFAULT '',
  `id_isds` varchar(50) DEFAULT '',
  `poznamka` text,
  `date_created` datetime DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `user_added` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`,`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `{tbls3}user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `active` tinyint(4) NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `last_modified` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `username` varchar(150) NOT NULL,
  `password` varchar(50) DEFAULT NULL,
  `last_ip` varchar(15) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `USERNAME` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;


CREATE TABLE `{tbls3}user_acl` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(10) unsigned DEFAULT NULL,
  `rule_id` int(10) unsigned DEFAULT NULL,
  `allowed` enum('Y','N') NOT NULL DEFAULT 'Y',
  PRIMARY KEY (`id`),
  KEY `role` (`role_id`),
  KEY `rule` (`rule_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

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
(23,4,18,'Y');

CREATE TABLE `{tbls3}user_resource` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(150) NOT NULL,
  `note` varchar(255) DEFAULT '',
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
(31, 'Spisovka_NapovedaPresenter', NULL, 'Nápověda');

CREATE TABLE `{tbls3}user_role` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '0',
  `date_created` datetime DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `note` varchar(250) DEFAULT '',
  `orgjednotka_id` int(11) DEFAULT NULL,
  `fixed` tinyint(4) DEFAULT '0',
  `order` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

INSERT INTO `{tbls3}user_role` (`id`, `parent_id`, `code`, `name`, `active`, `date_created`, `date_modified`, `note`, `orgjednotka_id`, `fixed`, `order`) VALUES
(1, 0, 'admin', 'administrátor', 1, NOW(), NOW(), 'Má absolutní moc provádět všechno možné', NULL, 2, 100),
(2, 0, 'guest', 'host', 1, NOW(), NOW(), 'Role představující nepřihlášeného uživatele.\r\nTedy nastavení oprávnění v době, kdy k aplikaci není nikdo přihlášen.', NULL, 2, 0),
(3, 1, 'superadmin', 'SuperAdmin', 1, NOW(), NOW(), 'Administrátor se super právy.\r\nTo znamená, že může manipulovat s jakýmikoli daty. Včetně dokumentů bez ohledu na vlastníka a stavu. ', NULL, 2, 100),
(4, 0, 'referent', 'referent', 1, NOW(), NOW(), 'Základní role pracovníka spisové služby', NULL, 1, 10),
(5, 4, 'vedouci', 'vedoucí', 1, NOW(), NOW(), 'Vedoucí organizační jednotky umožňující přijímat dokumenty', NULL, 1, 30),
(6, 4, 'podatelna', 'pracovník podatelny', 1, NOW(), NOW(), 'Pracovník podatelny, který může přijímat nebo odesílat dokumenty', NULL, 1, 20),
(7, 5, 'vedouci_1', 'vedoucí POD', 1, NOW(), NULL, NULL, 1, 0, 30),
(8, 4, 'referent_1', 'referent POD', 1, NOW(), NULL, NULL, 1, 0, 10),
(9, 6, 'podatelna_1', 'podatelna POD', 1, NOW(), NULL, NULL, 1, 0, 20);

CREATE TABLE `{tbls3}user_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `note` varchar(250) DEFAULT '',
  `resource_id` int(11) DEFAULT NULL,
  `privilege` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resource_id` (`resource_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

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
(18, 'Nápověda', 'Zobrazení nápovědy', 31, '');


CREATE TABLE `{tbls3}user_to_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user` (`user_id`),
  KEY `role` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `{tbls3}workflow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dokument_id` int(11) NOT NULL,
  `dokument_version` int(11) DEFAULT NULL,
  `stav_dokumentu` int(11) NOT NULL DEFAULT '0',
  `prideleno` int(11) DEFAULT NULL,
  `prideleno_info` text,
  `orgjednotka_id` int(11) DEFAULT NULL,
  `orgjednotka_info` text,
  `stav_osoby` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0=neprirazen,1=prirazen,2=dokoncen,100>storno',
  `date` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_info` text,
  `poznamka` text,
  `date_predani` datetime DEFAULT NULL,
  `aktivni` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `dokument` (`dokument_id`,`dokument_version`),
  KEY `prideleno` (`prideleno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `{tbls3}zmocneni` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cislo_zakona` int(11) NOT NULL COMMENT 'LegalTitleLaw',
  `rok_vydani` year(4) NOT NULL COMMENT 'LegaltitleYear',
  `paragraf` varchar(50) NOT NULL COMMENT 'LegalTitleSect',
  `odstavec` varchar(50) NOT NULL COMMENT 'LegalTitlePar',
  `pismeno` varchar(10) NOT NULL COMMENT 'LegaltitlePoint',
  `stav` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `{tbls3}zpusob_vyrizeni` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nazev` varchar(80) NOT NULL,
  `stav` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

INSERT INTO `{tbls3}zpusob_vyrizeni` (`id`, `nazev`, `stav`) VALUES
(1, 'vyřízení prvopisem', 1),
(2, 'postoupení', 1),
(3, 'vzetí na vědomí', 1),
(4, 'úřední záznam', 1),
(5, 'storno', 1),
(6, 'jiný způsob', 1);