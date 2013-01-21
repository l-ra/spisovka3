------------------------;

ALTER TABLE `{tbls3}dokument`
  CHANGE COLUMN `nazev` `nazev` varchar(250) NOT NULL;

ALTER TABLE `{tbls3}dokument_historie`
  CHANGE COLUMN `nazev` `nazev` varchar(250) NOT NULL;
