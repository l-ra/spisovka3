------------------------;

ALTER TABLE `{tbls3}user_rule`
  ADD CONSTRAINT `fk_user_rule_user_resource1` FOREIGN KEY (`resource_id`) REFERENCES `{tbls3}user_resource` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

DELETE FROM `{tbls3}user_resource` WHERE `code` IN ('ErrorPresenter', 'Spisovka_ErrorPresenter', 'DefaultPresenter');

DELETE FROM `{tbls3}user_rule` WHERE `resource_id` IN (4, 5);


ALTER TABLE `{tbls3}user_to_role`
  DROP FOREIGN KEY `fk_user_to_role_user_role1`;
ALTER TABLE `{tbls3}user_to_role`
  ADD CONSTRAINT `fk_user_to_role_user_role1` FOREIGN KEY (`role_id`) REFERENCES `{tbls3}user_role` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

DELETE IGNORE FROM `{tbls3}user_role` WHERE `id` = 2;