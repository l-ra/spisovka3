-- -----------------------------------------------------------------------------
-- revision 202
--
-- Nahradte rucne {tbls3} za odpovidajici hodnotu - prefix nebo nic
-- -----------------------------------------------------------------------------;

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


ALTER TABLE `{tbls3}dokument_odeslani` ADD `druh_zasilky` VARCHAR( 200 ) NULL AFTER `zprava` ,
ADD `cena` FLOAT NULL AFTER `druh_zasilky` ,
ADD `hmotnost` FLOAT NULL AFTER `cena` ,
ADD `cislo_faxu` VARCHAR( 100 ) NULL AFTER `hmotnost` ,
ADD `stav` TINYINT NOT NULL DEFAULT '1' AFTER `cislo_faxu`;

ALTER TABLE `{tbls3}dokument` ADD `typ_prilohy` VARCHAR( 150 ) NULL DEFAULT '' AFTER `pocet_priloh`;
ALTER TABLE `{tbls3}dokument` ADD `vyrizeni_typ_prilohy` VARCHAR( 150 ) NULL DEFAULT '' AFTER `vyrizeni_pocet_priloh`;
ALTER TABLE `{tbls3}dokument_historie` ADD `typ_prilohy` VARCHAR( 150 ) NULL DEFAULT '' AFTER `pocet_priloh`;
ALTER TABLE `{tbls3}dokument_historie` ADD `vyrizeni_typ_prilohy` VARCHAR( 150 ) NULL DEFAULT '' AFTER `vyrizeni_pocet_priloh`;

INSERT INTO `user_resource` (`id` ,`code` ,`note` ,`name` )
VALUES (NULL , 'Spisovka_VypravnaPresenter', '', 'Výpravna');