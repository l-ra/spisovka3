-- -----------------------------------------------------------------------------
-- revision 350
--
-- Nahradte rucne {tbls3} za odpovidajici hodnotu - prefix nebo nic
-- -----------------------------------------------------------------------------;

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