
-- Pokus o opravu pripadne chyby v datech
UPDATE `{tbls3}zapujcka` SET `user_id` = 1 WHERE `user_id` = 0;

ALTER TABLE `{tbls3}zapujcka`
  ADD CONSTRAINT `fk_zapujcka_user1` FOREIGN KEY (`user_id`) REFERENCES `{tbls3}user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}zapujcka`
  DROP COLUMN `user_vytvoril_id`,
  DROP COLUMN `user_prijal_id`,
  DROP COLUMN `user_schvalil_id`,
  DROP COLUMN `date_prijeti`,
  DROP COLUMN `date_schvaleni`;
