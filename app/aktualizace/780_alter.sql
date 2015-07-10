
UPDATE `{tbls3}user` SET `local` = 1, `password` = NULL WHERE `local` <> 0;
ALTER TABLE `{tbls3}user`
  CHANGE COLUMN `local` `external_auth` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';

