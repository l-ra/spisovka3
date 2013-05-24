------------------------;

UPDATE `{tbls3}zpusob_odeslani` SET `stav` = 0 WHERE `nazev` = 'telefonicky';
DELETE IGNORE FROM `{tbls3}zpusob_odeslani` WHERE `nazev` = 'telefonicky';

INSERT INTO `{tbls3}zpusob_odeslani` SET `nazev` = 'osobní předání';
