------------------------;

ALTER TABLE `{tbls3}dokument`
  CHANGE COLUMN `nazev` `nazev` varchar(250) NOT NULL;

ALTER TABLE `{tbls3}dokument_historie`
  CHANGE COLUMN `nazev` `nazev` varchar(250) NOT NULL;

UPDATE `{tbls3}user_rule` SET `resource_id` = 23 WHERE `id` = 10;
UPDATE `{tbls3}user_rule` SET `resource_id` = 22 WHERE `id` = 11;
UPDATE `{tbls3}user_rule` SET `resource_id` = 24 WHERE `id` = 12;

UPDATE `{tbls3}file` SET `mime_type` = 'application/xml' WHERE `real_name` LIKE '%.xml';