------------------------;

ALTER TABLE `{tbls3}druh_zasilky` ADD COLUMN `order` INT NOT NULL, DROP COLUMN `fixed`;

UPDATE `{tbls3}druh_zasilky` SET `order` = '10' WHERE `id` = 1;
UPDATE `{tbls3}druh_zasilky` SET `order` = '20' WHERE `id` = 2;
UPDATE `{tbls3}druh_zasilky` SET `order` = '30' WHERE `id` = 6;
UPDATE `{tbls3}druh_zasilky` SET `order` = '40' WHERE `id` = 3;
UPDATE `{tbls3}druh_zasilky` SET `order` = '50' WHERE `id` = 8;
UPDATE `{tbls3}druh_zasilky` SET `order` = '60' WHERE `id` = 7;
UPDATE `{tbls3}druh_zasilky` SET `order` = '400' WHERE `id` = 9;
UPDATE `{tbls3}druh_zasilky` SET `order` = '410' WHERE `id` = 5;
UPDATE `{tbls3}druh_zasilky` SET `order` = '420' WHERE `id` = 4;
