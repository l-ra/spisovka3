CREATE TABLE  `{tbls3}cislo_jednaci` (
  `cjednaci_id` int(11) NOT NULL auto_increment,
  `podaci_denik` varchar(80) NOT NULL default 'default',
  `rok` year(4) NOT NULL,
  `poradove_cislo` int(11) default NULL,
  `urad_zkratka` varchar(50) default NULL,
  `urad_poradi` int(11) default NULL,
  `orgjednotka_id` int(11) default NULL,
  `org_poradi` int(11) default NULL,
  `user_id` int(11) default NULL,
  `user_poradi` int(11) default NULL,
  PRIMARY KEY  (`cjednaci_id`),
  KEY `search` (`urad_zkratka`,`orgjednotka_id`,`user_id`,`podaci_denik`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE `{tbls3}dokument` (
  `dokument_id` int(11) NOT NULL,
  `dokument_version` int(11) NOT NULL,
  `jid` varchar(100) NOT NULL,
  `nazev` varchar(100) NOT NULL,
  `popis` varchar(255) default NULL,
  `cislojednaci_id` int(11) default NULL,
  `cislo_jednaci` varchar(50) default NULL,
  `poradi` smallint(6) default '1',
  `cislo_jednaci_odesilatele` varchar(50) default NULL,
  `podaci_denik` varchar(45) default NULL,
  `podaci_denik_poradi` int(11) default NULL,
  `podaci_denik_rok` year(4) default NULL,
  `typ_dokumentu` int(11) default NULL,
  `spisovy_plan` varchar(45) default NULL,
  `spisovy_znak` int(11) default NULL,
  `skartacni_znak` enum('A','S','V') default NULL,
  `skartacni_lhuta` int(11) default NULL,
  `poznamka` text,
  `zmocneni` int(11) default NULL,
  `lhuta` tinyint(4) NOT NULL default '30',
  `epodatelna_id` int(11) default NULL,
  `stav` tinyint(4) NOT NULL default '1',
  `md5_hash` varchar(45) NOT NULL,
  `date_created` datetime default NULL,
  `user_created` int(11) default NULL,
  `date_modified` datetime default NULL,
  `user_modified` int(11) default NULL,
  `datum_vzniku` datetime NOT NULL,
  `pocet_listu` int(11) default NULL,
  `pocet_priloh` int(11) default NULL,
  `zpusob_doruceni` int(11) default NULL,
  `zpusob_vyrizeni` int(11) default NULL,
  `vyrizeni_pocet_listu` int(11) default NULL,
  `vyrizeni_pocet_priloh` int(11) default NULL,
  `ulozeni_dokumentu` text,
  `datum_vyrizeni` datetime default NULL,
  `poznamka_vyrizeni` text,
  `spousteci_udalost` varchar(250) default NULL,
  PRIMARY KEY  (`dokument_id`,`dokument_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `{tbls3}dokument_odeslani` (
  `dokument_odeslani_id` int(11) NOT NULL auto_increment,
  `dokument_id` int(11) NOT NULL,
  `dokument_version` int(11) default NULL,
  `subjekt_id` int(11) NOT NULL,
  `subjekt_version` int(11) default NULL,
  `zpusob_odeslani` int(11) NOT NULL,
  `epodatelna_id` int(11) default NULL,
  `datum_odeslani` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_info` text,
  `zprava` text,
  `date_created` datetime default NULL,
  PRIMARY KEY  (`dokument_odeslani_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE `{tbls3}dokument_to_file` (
  `dokument_id` int(11) NOT NULL,
  `dokument_version` int(11) default '0',
  `file_id` int(11) NOT NULL,
  `file_version` int(11) default '0',
  `date_added` datetime default NULL,
  `user_added` int(11) default NULL,
  `active` tinyint(4) NOT NULL default '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `{tbls3}dokument_to_spis` (
  `dokument_id` int(11) NOT NULL,
  `dokument_version` int(11) default NULL,
  `spis_id` int(11) NOT NULL,
  `date_added` datetime default NULL,
  `user_added` int(11) default NULL,
  `poradi` int(11) NOT NULL default '1',
  `stav` tinyint(4) NOT NULL default '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `{tbls3}dokument_to_subjekt` (
  `dokument_id` int(11) NOT NULL,
  `dokument_version` int(11) default NULL,
  `subjekt_id` int(11) NOT NULL,
  `subjekt_version` int(11) default NULL,
  `typ` enum('A','O','AO') NOT NULL default 'AO',
  `date_added` datetime default NULL,
  `user_added` int(11) default NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{tbls3}dokument_typ` (
  `dokument_typ_id` int(11) NOT NULL auto_increment,
  `nazev` varchar(100) NOT NULL,
  `popis` varchar(255) default NULL,
  `stav` tinyint(4) NOT NULL default '1',
  `smer` tinyint(4) NOT NULL default '0' COMMENT '0-prichozi, 1-odchozi',
  `typ` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`dokument_typ_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 ;

INSERT INTO `{tbls3}dokument_typ` (`dokument_typ_id`, `nazev`, `popis`, `stav`, `smer`, `typ`) VALUES
(1, 'příchozí', NULL, 1, 0, 0),
(2, 'vlastní', NULL, 1, 1, 0),
(4, 'odpověď', NULL, 1, 1, 0),
(5, 'příchozí - doručeno emailem', NULL, 1, 0, 1),
(6, 'příchozí - doručeno datovou schránkou', NULL, 1, 0, 2),
(7, 'příchozí - doručeno datovým nosičem', NULL, 1, 0, 3),
(8, 'příchozí - doručeno faxem', NULL, 1, 0, 4),
(9, 'příchozí - doručeno v listinné podobě', NULL, 1, 0, 0);

CREATE TABLE `{tbls3}epodatelna` (
  `epodatelna_id` int(11) NOT NULL auto_increment,
  `epodatelna_typ` tinyint(4) NOT NULL default '0',
  `poradi` int(11) default NULL,
  `rok` year(4) default NULL,
  `email_signature` varchar(200) default NULL,
  `isds_signature` varchar(45) default NULL,
  `predmet` varchar(200) NOT NULL,
  `popis` text,
  `odesilatel` varchar(200) NOT NULL,
  `odesilatel_id` int(11) default NULL,
  `adresat` varchar(100) NOT NULL,
  `prijato_dne` datetime default NULL,
  `doruceno_dne` datetime default NULL,
  `prijal_kdo` int(11) default NULL,
  `prijal_info` text,
  `sha1_hash` varchar(50) NOT NULL,
  `prilohy` text,
  `evidence` varchar(100) default NULL,
  `dokument_id` int(11) default NULL,
  `stav` tinyint(4) NOT NULL default '0',
  `stav_info` varchar(255) default NULL,
  `source_id` varchar(40) default NULL,
  PRIMARY KEY  (`epodatelna_id`),
  KEY `hledat` (`email_signature`,`isds_signature`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE `{tbls3}file` (
  `file_id` int(11) NOT NULL,
  `file_version` tinyint(4) NOT NULL,
  `stav` tinyint(4) NOT NULL default '1',
  `typ` tinyint(4) NOT NULL default '1' COMMENT 'typ prilohy. Defaultne: (1)main, (2)enclosure, (3)signature, (4)meta, (5)source',
  `nazev` varchar(255) NOT NULL COMMENT 'jmeno souboru nebo nazev',
  `popis` varchar(45) default NULL,
  `mime_type` varchar(60) default NULL COMMENT 'mime typ souboru',
  `real_name` varchar(255) NOT NULL COMMENT 'skutečné jmeno souboru file.ext',
  `real_path` varchar(255) NOT NULL COMMENT 'realna cesta k souboru ',
  `real_type` varchar(45) NOT NULL default 'FILE' COMMENT 'typ fyzickeho mista. Default FILE - lokalni fyzicke misto',
  `date_created` datetime default NULL,
  `user_created` int(11) default NULL,
  `date_modified` datetime default NULL,
  `user_modified` int(11) default NULL,
  `guid` varchar(45) NOT NULL COMMENT 'jednoznacny identifikator',
  `md5_hash` varchar(45) NOT NULL COMMENT 'otisk souboru pro overeni pravosti',
  `size` int(11) default NULL,
  PRIMARY KEY  (`file_id`,`file_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `{tbls3}log_access` (
  `logaccess_id` int(10) unsigned NOT NULL auto_increment,
  `date` datetime default NULL,
  `user_id` int(11) default NULL,
  `ip` varchar(15) default NULL,
  `user_agent` varchar(200) default NULL,
  `stav` tinyint(4) default NULL,
  PRIMARY KEY  (`logaccess_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE `{tbls3}log_dokument` (
  `logdokument_id` int(11) NOT NULL auto_increment,
  `typ` tinyint(4) NOT NULL,
  `poznamka` text,
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `dokument_id` int(11) NOT NULL,
  `user_info` text,
  PRIMARY KEY  (`logdokument_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE `{tbls3}orgjednotka` (
  `orgjednotka_id` int(10) unsigned NOT NULL auto_increment,
  `plny_nazev` varchar(200) NOT NULL,
  `zkraceny_nazev` varchar(100) default NULL,
  `ciselna_rada` varchar(30) NOT NULL,
  `note` text,
  `stav` tinyint(4) NOT NULL default '0',
  `date_created` datetime default NULL,
  `date_modified` datetime default NULL,
  PRIMARY KEY  (`orgjednotka_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3;

INSERT INTO `{tbls3}orgjednotka` (`orgjednotka_id`, `plny_nazev`, `zkraceny_nazev`, `ciselna_rada`, `note`, `stav`, `date_created`, `date_modified`) VALUES
(1, 'Centrální podatelna', 'Centrální podatelna', 'POD', '', 1, NOW(), NULL);

CREATE TABLE `{tbls3}osoba` (
  `osoba_id` int(11) NOT NULL auto_increment,
  `prijmeni` varchar(255) NOT NULL,
  `jmeno` varchar(150) default NULL,
  `titul_pred` varchar(50) default NULL,
  `titul_za` varchar(50) default NULL,
  `email` varchar(200) default NULL,
  `telefon` varchar(20) default NULL,
  `pozice` varchar(50) default NULL,
  `stav` tinyint(4) NOT NULL,
  `date_created` datetime NOT NULL,
  `date_modified` datetime default NULL,
  PRIMARY KEY  (`osoba_id`),
  UNIQUE KEY `prijmeni` (`prijmeni`,`jmeno`,`stav`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE `{tbls3}osoba_to_user` (
  `osoba_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `date_added` datetime NOT NULL,
  `active` tinyint(4) NOT NULL default '1',
  UNIQUE KEY `osoba_id` (`osoba_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `{tbls3}sestava` (
  `sestava_id` int(11) NOT NULL auto_increment,
  `nazev` varchar(60) NOT NULL,
  `popis` varchar(150) default NULL,
  `parametry` text,
  `sloupce` text,
  `typ` tinyint(4) NOT NULL default '1',
  `filtr` tinyint(4) default '0',
  PRIMARY KEY  (`sestava_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

INSERT INTO `{tbls3}sestava` (`sestava_id`, `nazev`, `popis`, `parametry`, `sloupce`, `typ`, `filtr`) VALUES
(1, 'Podací deník', NULL, NULL, NULL, 2, 1);

CREATE TABLE `{tbls3}souvisejici_dokument` (
  `dokument_id` int(11) NOT NULL,
  `spojit_s` int(11) NOT NULL,
  `date_added` datetime NOT NULL,
  `user_added` int(11) NOT NULL,
  `type` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`dokument_id`,`spojit_s`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `{tbls3}spis` (
  `spis_id` int(11) NOT NULL auto_increment,
  `nazev` varchar(80) NOT NULL,
  `popis` varchar(200) NOT NULL,
  `spisovy_znak` int(11) default NULL,
  `typ` varchar(5) NOT NULL default 'S',
  `spis_parent` int(11) default NULL,
  `uroven` tinyint(4) default '0',
  `sekvence` varchar(200) default NULL,
  `stav` tinyint(4) NOT NULL default '1',
  `date_created` datetime default NULL,
  `user_created` int(11) default NULL,
  `date_modified` datetime default NULL,
  `user_modified` int(11) default NULL,
  `skartacni_znak` enum('A','S','V') default NULL,
  `skartacni_lhuta` int(11) default NULL,
  `spousteci_udalost` varchar(250) default NULL,
  PRIMARY KEY  (`spis_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

INSERT INTO `{tbls3}spis` (`spis_id`, `nazev`, `popis`, `spisovy_znak`, `typ`, `spis_parent`, `uroven`, `sekvence`, `stav`, `date_created`, `user_created`, `date_modified`, `user_modified`, `skartacni_znak`, `skartacni_lhuta`, `spousteci_udalost`) VALUES
(1, 'Spisy', 'Nejvyšší větev spisové hierarchie', NULL, 'VS', NULL, 0, '', 1, NULL, NULL, NOW(), 25, NULL, NULL, NULL);

CREATE TABLE `{tbls3}spisovy_znak` (
  `spisznak_id` int(11) NOT NULL auto_increment,
  `nazev` varchar(80) NOT NULL,
  `popis` varchar(200) NOT NULL,
  `spisznak_parent` int(11) default NULL,
  `uroven` tinyint(4) default '0',
  `sekvence` varchar(200) default NULL,
  `stav` tinyint(4) NOT NULL default '1',
  `date_created` datetime default NULL,
  `user_created` int(11) default NULL,
  `date_modified` datetime default NULL,
  `user_modified` int(11) default NULL,
  `skartacni_znak` enum('A','S','V') default NULL,
  `skartacni_lhuta` int(11) default NULL,
  `spousteci_udalost` int(11) default NULL,
  PRIMARY KEY  (`spisznak_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE `{tbls3}spousteci_udalost` (
  `spousteci_udalost_id` int(11) NOT NULL auto_increment,
  `nazev` text NOT NULL,
  `poznamka` text,
  `stav` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`spousteci_udalost_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=17 ;

INSERT INTO `{tbls3}spousteci_udalost` (`spousteci_udalost_id`, `nazev`, `poznamka`, `stav`) VALUES
(1, 'Skartační lhůta začíná plynout po ztrátě platnosti dokumentu.', NULL, 1),
(2, 'Skartační lhůta začíná plynout  po ukončení záruky.', NULL, 1),
(3, 'Skartační lhůta začíná plynout po uzavření dokumentu.', NULL, 1),
(4, 'Skartační lhůta počíná plynout po zařazení dokumentů z předávacích protokolů do skartačního řízení (předávací protokoly).', NULL, 1),
(5, 'Skartační lhůta začíná plynout po vyhodnocení dokumentu (Podkladový materiál k výkazům).', NULL, 1),
(6, 'Skartační lhůta začíná běžet po roce, v němž byla výpočetní a jiná technika naposledy použita, nebo po ukončení používání příslušného software (Provozní dokumentace, licence).', NULL, 1),
(7, 'Skartační lhůta začíná plynout po vyhlášení výsledků voleb.', NULL, 1),
(8, 'Skartační lhůta začíná plynout po zrušení zařízení.', NULL, 1),
(9, 'Nabytí účinnosti.', NULL, 1),
(10, 'Rozhodnutí, nabytí právní moci.', NULL, 1),
(11, 'Uvedení objektu do provozu.', NULL, 1),
(12, 'Ukončení studia.', NULL, 1),
(13, 'Ukončení pobytu.', NULL, 1),
(14, 'Ukončení pracovního/služebního poměru.', NULL, 1),
(15, 'Skartační lhůta u dokumentů celostátně vyhlášeného referenda začíná plynout po vyhlášení výsledků referenda prezidentem republiky ve Sbírce zákonů, popřípadě po vyhlášení nálezu Ústavního soudu, kterým rozhodl, že postup při provádění referenda nebyl v souladu s ústavním zákonem o referendu o přistoupení České republiky k Evropské unii nebo zákonem vydaným k jeho provedení s povinností zachování tří nepoužitých hlasovacích lístků pro referendum pro uložení v příslušném archivu. ', NULL, 1),
(16, 'Skartační lhůta u dokumentů krajského referenda začíná plynout po vyhlášení výsledků referenda s povinností zachování tří nepoužitých hlasovacích lístků pro referendum pro uložení v příslušném archivu.', NULL, 1);

CREATE TABLE `{tbls3}subjekt` (
  `subjekt_id` int(11) NOT NULL,
  `subjekt_version` int(11) NOT NULL default '1',
  `stav` tinyint(4) NOT NULL default '1',
  `type` varchar(15) NOT NULL,
  `ic` varchar(8) default NULL,
  `dic` varchar(12) default NULL,
  `nazev_subjektu` varchar(255) default NULL,
  `jmeno` varchar(24) default NULL,
  `prijmeni` varchar(35) default NULL,
  `prostredni_jmeno` varchar(35) default NULL,
  `titul_pred` varchar(35) default NULL,
  `titul_za` varchar(10) default NULL,
  `rodne_jmeno` varchar(35) default NULL,
  `datum_narozeni` date default NULL,
  `misto_narozeni` varchar(48) default NULL,
  `okres_narozeni` varchar(48) default NULL,
  `stat_narozeni` varchar(3) default NULL,
  `adresa_mesto` varchar(48) default NULL,
  `adresa_ulice` varchar(48) default NULL,
  `adresa_cp` varchar(10) default NULL,
  `adresa_co` varchar(10) default NULL,
  `adresa_psc` varchar(10) default NULL,
  `adresa_stat` varchar(3) default NULL,
  `narodnost` varchar(80) default NULL,
  `email` varchar(250) default NULL,
  `telefon` varchar(150) default NULL,
  `id_isds` varchar(50) default NULL,
  `poznamka` text,
  `date_created` datetime default NULL,
  `date_modified` datetime default NULL,
  `user_added` int(11) default NULL,
  PRIMARY KEY  (`subjekt_id`,`subjekt_version`),
  KEY `jmeno` (`nazev_subjektu`,`prijmeni`,`jmeno`),
  KEY `hledat` (`adresa_ulice`,`email`,`id_isds`,`telefon`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `{tbls3}user` (
  `user_id` int(10) unsigned NOT NULL auto_increment,
  `active` tinyint(4) NOT NULL,
  `date_created` datetime default NULL,
  `last_modified` datetime default NULL,
  `last_login` datetime default NULL,
  `username` varchar(150) NOT NULL,
  `password` varchar(50) default NULL,
  `last_ip` varchar(15) default NULL,
  PRIMARY KEY  (`user_id`),
  UNIQUE KEY `USERNAME` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

CREATE TABLE `{tbls3}user_acl` (
  `acl_id` int(10) unsigned NOT NULL auto_increment,
  `role_id` int(10) unsigned default NULL,
  `rule_id` int(10) unsigned default NULL,
  `allowed` enum('Y','N') NOT NULL default 'Y',
  PRIMARY KEY  (`acl_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC ;


INSERT INTO `{tbls3}user_acl` (`acl_id`,`role_id`,`rule_id`,`allowed`) VALUES
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
(22,8,17,'Y');

CREATE TABLE `{tbls3}user_resource` (
  `resource_id` int(10) unsigned NOT NULL auto_increment,
  `code` varchar(150) NOT NULL,
  `note` varchar(255) default NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY  (`resource_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=31 ;

INSERT INTO `{tbls3}user_resource` (`resource_id`, `code`, `note`, `name`) VALUES
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
(21, 'Admin_EpodatelnaPresenter', NULL, 'Administrace - Nastaveni epodatelny'),
(22, 'Epodatelna_EvidencePresenter', NULL, 'Epodatalne - evidence'),
(23, 'Epodatelna_SubjektyPresenter', NULL, 'Epodatelna - subjekty'),
(24, 'Epodatelna_PrilohyPresenter', NULL, 'Epodatelna - prilohy'),
(25, 'Spisovka_SestavyPresenter', NULL, 'Sestavy'),
(26, 'Spisovka_SpojitPresenter', NULL, 'Spojování dokumentu'),
(27, 'Spisovka_ErrorPresenter', NULL, 'Error'),
(28, 'Install_DefaultPresenter', NULL, 'Instalační procedura'),
(29, 'Spisovka_VyhledatPresenter', NULL, 'Vyhledavani');

CREATE TABLE `{tbls3}user_role` (
  `role_id` int(10) unsigned NOT NULL auto_increment,
  `parent_id` int(10) unsigned default NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `active` tinyint(4) NOT NULL default '0',
  `date_created` datetime default NULL,
  `date_modified` datetime default NULL,
  `note` varchar(250) default NULL,
  `orgjednotka_id` int(11) default NULL,
  `fixed` tinyint(4) default '0',
  `order` int(11) default NULL,
  PRIMARY KEY  (`role_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=32 ;

INSERT INTO `{tbls3}user_role` (`role_id`, `parent_id`, `code`, `name`, `active`, `date_created`, `date_modified`, `note`, `orgjednotka_id`, `fixed`, `order`) VALUES
(1, 0, 'admin', 'administrátor', 1, NOW(), NOW(), 'Má absolutní moc provádět všechno možné', NULL, 2, 100),
(2, 0, 'guest', 'host', 1, NOW(), NOW(), 'Role představující nepřihlášeného uživatele.\r\nTedy nastavení oprávnění v době, kdy k aplikaci není nikdo přihlášen.', NULL, 2, 0),
(3, 1, 'superadmin', 'SuperAdmin', 1, NOW(), NOW(), 'Administrátor se super právy.\r\nTo znamená, že může manipulovat s jakýmikoli daty. Včetně dokumentů bez ohledu na vlastníka a stavu. ', NULL, 2, 100),
(4, 0, 'referent', 'referent', 1, NOW(), NOW(), 'Základní role pracovníka spisové služby', NULL, 1, 10),
(5, 4, 'vedouci', 'vedoucí', 1, NOW(), NOW(), 'vedoucí organizační jednotky umožňující přijímat dokumenty', NULL, 1, 30),
(6, 0, 'podatelna', 'pracovník podatelny', 1, NOW(), NOW(), 'pracovník podatelny, který může přijímat nebo odesílat dokumenty', NULL, 1, 20),
(7, 5, 'vedouci_1', 'vedoucí POD', 1, NOW(), NULL, NULL, 1, 0, 30),
(8, 4, 'referent_1', 'referent POD', 1, NOW(), NULL, NULL, 1, 0, 10),
(9, 6, 'podatelna_1', 'podatelna POD', 1, NOW(), NULL, NULL, 1, 0, 20);

CREATE TABLE `{tbls3}user_rule` (
  `rule_id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  `note` varchar(250) default NULL,
  `resource_id` int(11) default NULL,
  `privilege` varchar(100) default NULL,
  PRIMARY KEY  (`rule_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

INSERT INTO `{tbls3}user_rule` (`rule_id`, `name`, `note`, `resource_id`, `privilege`) VALUES
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
(17, 'Oprávnění pro org. jednotku Centrální podatelna', 'Oprávnění platné pouze pro organizační jednotku Centrální podatelna', NULL, 'orgjednotka_1');

CREATE TABLE `{tbls3}user_to_role` (
  `user_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  `date_added` datetime NOT NULL,
  KEY `user_id` (`user_id`,`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `{tbls3}workflow` (
  `workflow_id` int(11) NOT NULL auto_increment,
  `dokument_id` int(11) NOT NULL,
  `dokument_version` int(11) default NULL,
  `stav_dokumentu` int(11) NOT NULL default '0',
  `prideleno` int(11) default NULL,
  `prideleno_info` text,
  `orgjednotka_id` int(11) default NULL,
  `orgjednotka_info` text,
  `stav_osoby` tinyint(4) NOT NULL default '0' COMMENT '0=neprirazen,1=prirazen,2=dokoncen,100>storno',
  `date` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_info` text,
  `poznamka` text,
  `date_predani` datetime default NULL,
  `aktivni` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`workflow_id`),
  KEY `dokument` (`dokument_id`,`dokument_version`,`stav_dokumentu`),
  KEY `osoba` (`prideleno`,`orgjednotka_id`,`stav_osoby`,`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE `{tbls3}zmocneni` (
  `zmocneni_id` int(11) NOT NULL auto_increment,
  `cislo_zakona` int(11) NOT NULL COMMENT 'LegalTitleLaw',
  `rok_vydani` year(4) NOT NULL COMMENT 'LegaltitleYear',
  `paragraf` varchar(50) NOT NULL COMMENT 'LegalTitleSect',
  `odstavec` varchar(50) NOT NULL COMMENT 'LegalTitlePar',
  `pismeno` varchar(10) NOT NULL COMMENT 'LegaltitlePoint',
  `stav` tinyint(4) NOT NULL,
  PRIMARY KEY  (`zmocneni_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `{tbls3}zpvyrizeni` (
  `zpvyrizeni_id` int(10) unsigned NOT NULL auto_increment,
  `nazev` varchar(80) NOT NULL,
  `stav` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`zpvyrizeni_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

INSERT INTO `{tbls3}zpvyrizeni` (`zpvyrizeni_id`, `nazev`, `stav`) VALUES
(1, 'vyřízení prvopisem', 1),
(2, 'postoupení', 1),
(3, 'vzetí na vědomí', 1),
(4, 'úřední záznam', 1),
(5, 'storno', 1),
(6, 'jiný způsob', 1);