------------------------;

ALTER TABLE `{tbls3}subjekt`
  CHANGE COLUMN `nazev_subjektu` `nazev_subjektu` varchar(255) NOT NULL DEFAULT '',
  CHANGE COLUMN `jmeno` `jmeno` varchar(24) NOT NULL DEFAULT '',
  CHANGE COLUMN `prijmeni` `prijmeni` varchar(35) NOT NULL DEFAULT '';

ALTER TABLE `{tbls3}osoba`
  CHANGE COLUMN `jmeno` `jmeno` varchar(150) NOT NULL DEFAULT '';
