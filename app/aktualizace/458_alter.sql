-- -----------------------------------------------------------------------------
-- revision 458
--
-- Nahradte rucne {tbls3} za odpovidajici hodnotu - prefix nebo nic
-- -----------------------------------------------------------------------------;

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

ALTER TABLE `{tbls3}zprava_osoba`
  ADD CONSTRAINT `zprava_osoba_ibfk_1` FOREIGN KEY (`zprava_id`) REFERENCES `{tbls3}zprava` (`id`),
  ADD CONSTRAINT `zprava_osoba_ibfk_2` FOREIGN KEY (`osoba_id`) REFERENCES `{tbls3}osoba` (`id`);
  
INSERT INTO `{tbls3}user_resource` (`code`, `name`) VALUES ('Spisovka_ZpravyPresenter', 'Zprávy');
SET @RESOURCE_ID=LAST_INSERT_ID();

INSERT INTO `{tbls3}user_rule` (`resource_id`, `name`) VALUES (@RESOURCE_ID, 'Zobrazení zpráv');
