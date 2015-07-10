-- SET FOREIGN_KEY_CHECKS=0;

ALTER TABLE `{tbls3}user_to_role`
  DROP FOREIGN KEY `fk_user_to_role_user1`;
ALTER TABLE `{tbls3}user_to_role`
  ADD CONSTRAINT `fk_user_to_role_user1` FOREIGN KEY (`user_id`) REFERENCES `{tbls3}user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `{tbls3}osoba_to_user`
  DROP FOREIGN KEY `fk_osoba_to_user_user1`;
ALTER TABLE `{tbls3}osoba_to_user`
  ADD CONSTRAINT `fk_osoba_to_user_user1` FOREIGN KEY (`user_id`) REFERENCES `{tbls3}user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;
  
-- SET FOREIGN_KEY_CHECKS=1;